<?php

declare(strict_types=1);

namespace App\Cms\Application;

use App\Cms\Domain\PageSlug;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

final class PublicSlugRegistry
{
    public function __construct(private readonly Connection $connection) {}

    public function isAvailable(string $slug, string $ownerType, ?int $ownerId = null, string $locale = ''): bool
    {
        if ($locale === '' && PageSlug::isReserved($slug)) return false;
        if ($locale === '' && (int) $this->connection->fetchOne('SELECT COUNT(*) FROM cms_url_redirect WHERE active = 1 AND source_path = :path', ['path' => '/'.$slug]) > 0) return false;
        $owner = $this->connection->fetchAssociative(
            'SELECT owner_type, owner_id FROM public_slug WHERE locale = :locale AND slug = :slug',
            ['locale' => $locale, 'slug' => $slug],
        );
        return $owner === false || ($owner['owner_type'] === $ownerType && (int) $owner['owner_id'] === $ownerId);
    }

    public function claim(string $slug, string $ownerType, int $ownerId, string $locale = ''): void
    {
        if (!$this->isAvailable($slug, $ownerType, $ownerId, $locale)) throw new PublicSlugUnavailable($slug);
        $this->connection->transactional(function () use ($slug, $ownerType, $ownerId, $locale): void {
            $this->connection->executeStatement('DELETE FROM public_slug WHERE owner_type = :type AND owner_id = :id AND locale = :locale', ['type' => $ownerType, 'id' => $ownerId, 'locale' => $locale]);
            try {
                $this->connection->insert('public_slug', ['locale' => $locale, 'slug' => $slug, 'owner_type' => $ownerType, 'owner_id' => $ownerId]);
            } catch (UniqueConstraintViolationException $exception) {
                throw new PublicSlugUnavailable($slug, previous: $exception);
            }
        });
    }

    public function release(string $ownerType, int $ownerId, ?string $locale = null): void
    {
        $criteria=['owner_type'=>$ownerType,'owner_id'=>$ownerId];if($locale!==null)$criteria['locale']=$locale;
        $this->connection->delete('public_slug',$criteria);
    }

    /** @return array{type:string,id:int}|null */
    public function owner(string $slug,string $locale=''):?array
    {
        $row=$this->connection->fetchAssociative('SELECT owner_type, owner_id FROM public_slug WHERE locale = :locale AND slug = :slug',['locale'=>$locale,'slug'=>$slug]);
        return $row===false?null:['type'=>(string)$row['owner_type'],'id'=>(int)$row['owner_id']];
    }
}

<?php

declare(strict_types=1);

namespace App\Cms\Domain\Entity;

use App\Cms\Domain\PageSlug;
use App\Cms\Infrastructure\Persistence\Doctrine\UrlRedirectRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: UrlRedirectRepository::class)]
#[ORM\Table(name: 'cms_url_redirect')]
#[UniqueEntity(fields: ['sourcePath'], message: 'validation.redirect.source_unique')]
class UrlRedirect
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id = null;
    #[ORM\Column(length: 500, unique: true)]
    private string $sourcePath = '/';
    #[ORM\Column(length: 500)]
    private string $targetPath = '/';
    #[ORM\Column(options: ['default' => true])] private bool $permanent = true;
    #[ORM\Column(options: ['default' => true])] private bool $active = true;
    #[ORM\Column(options: ['default' => 0])] private int $hits = 0;
    #[ORM\Column(nullable: true)] private ?DateTimeImmutable $lastUsedAt = null;

    public function getId(): ?int { return $this->id; }
    public function getSourcePath(): string { return $this->sourcePath; }
    public function setSourcePath(string $value): void { $this->sourcePath = self::normalize($value, false); }
    #[Assert\Callback]
    public function validatePaths(ExecutionContextInterface $context): void
    {
        if (!self::isSafeInternalPath($this->sourcePath, false)) {
            $context->buildViolation('validation.redirect.internal_path')->atPath('sourcePath')->addViolation();
        }
        if (!self::isSafeInternalPath($this->targetPath, true)) {
            $context->buildViolation('validation.redirect.internal_path')->atPath('targetPath')->addViolation();
        }
        if (PageSlug::isReservedPath($this->sourcePath)) {
            $context->buildViolation('validation.redirect.source_reserved')->atPath('sourcePath')->addViolation();
        }
    }
    public function getTargetPath(): string { return $this->targetPath; }
    public function setTargetPath(string $value): void { $this->targetPath = self::normalize($value, true); }
    public function isPermanent(): bool { return $this->permanent; }
    public function setPermanent(bool $value): void { $this->permanent = $value; }
    public function isActive(): bool { return $this->active; }
    public function setActive(bool $value): void { $this->active = $value; }
    public function getHits(): int { return $this->hits; }
    public function getLastUsedAt(): ?DateTimeImmutable { return $this->lastUsedAt; }
    public function registerHit(): void { ++$this->hits; $this->lastUsedAt = new DateTimeImmutable(); }

    private static function isSafeInternalPath(string $path, bool $allowQuery): bool
    {
        if (!str_starts_with($path, '/') || str_starts_with($path, '//')) return false;
        if (preg_match('/[\x00-\x20\\\\]/u', $path) === 1) return false;

        return $allowQuery || !str_contains($path, '?') && !str_contains($path, '#');
    }

    private static function normalize(string $value, bool $allowQuery): string
    {
        $value = trim($value);
        if ($value === '') return '/';
        if (!str_starts_with($value, '/')) $value = '/'.$value;
        if (!$allowQuery) $value = explode('?', $value, 2)[0];
        return preg_replace('#/+#', '/', $value) ?? $value;
    }
}

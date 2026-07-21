<?php

declare(strict_types=1);

namespace App\Cms\Domain\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'cms_page_revision')]
#[ORM\UniqueConstraint(name: 'uniq_page_revision', columns: ['page_id', 'version'])]
class PageRevision
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id = null;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')] private Page $page;
    #[ORM\Column] private int $version;
    /** @var array<string,mixed> */
    #[ORM\Column(type: Types::JSON)] private array $data;
    #[ORM\Column(length: 180, nullable: true)] private ?string $createdBy;
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)] private DateTimeImmutable $createdAt;

    /** @param array<string,mixed> $data */
    public function __construct(Page $page, int $version, array $data, ?string $createdBy)
    {
        $this->page = $page; $this->version = $version; $this->data = $data; $this->createdBy = $createdBy; $this->createdAt = new DateTimeImmutable();
    }
    public function getId(): ?int { return $this->id; }
    public function getPage(): Page { return $this->page; }
    public function getVersion(): int { return $this->version; }
    /** @return array<string,mixed> */ public function getData(): array { return $this->data; }
    public function getCreatedBy(): ?string { return $this->createdBy; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
    public function getTitle(): string { return (string) ($this->data['title'] ?? ''); }
    public function getSlug(): string { return (string) ($this->data['slug'] ?? ''); }
    public function isPublished(): bool { return (bool) ($this->data['published'] ?? false); }
}

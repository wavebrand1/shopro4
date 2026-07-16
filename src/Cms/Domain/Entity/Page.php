<?php

declare(strict_types=1);

namespace App\Cms\Domain\Entity;

use App\Cms\Infrastructure\Persistence\Doctrine\PageRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PageRepository::class)]
#[ORM\Table(name: 'cms_page')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['slug'], message: 'Podstrona z takim adresem już istnieje.')]
class Page
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 200)]
    private string $title = '';

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', message: 'Slug może zawierać małe litery, cyfry i myślniki.')]
    private string $slug = '';

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private string $content = '';

    #[ORM\Column(options: ['default' => false])]
    private bool $published = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
    }

    public function getId(): ?int { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): void { $this->title = trim($title); }
    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): void { $this->slug = mb_strtolower(trim($slug)); }
    public function getContent(): string { return $this->content; }
    public function setContent(string $content): void { $this->content = trim($content); }
    public function isPublished(): bool { return $this->published; }
    public function setPublished(bool $published): void { $this->published = $published; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): DateTimeImmutable { return $this->updatedAt; }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}

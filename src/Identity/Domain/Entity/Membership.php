<?php

declare(strict_types=1);

namespace App\Identity\Domain\Entity;

use App\Identity\Infrastructure\Persistence\Doctrine\MembershipRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MembershipRepository::class)]
#[ORM\Table(name: 'membership')]
class Membership
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id = null;
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank, Assert\Length(max: 255)]
    private string $title = '';
    #[ORM\Column(type: Types::TEXT)] private string $description = '';
    #[ORM\Column(options: ['default' => true])] private bool $active = true;
    #[ORM\Column] private \DateTimeImmutable $createdAt;
    #[ORM\Column] private \DateTimeImmutable $updatedAt;

    public function __construct() { $this->createdAt = $this->updatedAt = new \DateTimeImmutable(); }
    public function getId(): ?int { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): void { $this->title = trim($title); $this->touch(); }
    public function getDescription(): string { return $this->description; }
    public function setDescription(?string $description): void { $this->description = trim((string) $description); $this->touch(); }
    public function isActive(): bool { return $this->active; }
    public function setActive(bool $active): void { $this->active = $active; $this->touch(); }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    private function touch(): void { $this->updatedAt = new \DateTimeImmutable(); }
}

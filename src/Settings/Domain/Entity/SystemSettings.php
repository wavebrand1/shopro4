<?php

declare(strict_types=1);

namespace App\Settings\Domain\Entity;

use App\Settings\Infrastructure\Persistence\Doctrine\SystemSettingsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SystemSettingsRepository::class)]
#[ORM\Table(name: 'system_settings')]
class SystemSettings
{
    #[ORM\Id]
    #[ORM\Column]
    private int $id = 1;

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON)]
    private array $configuration = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $smtpPassword = null;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): int { return $this->id; }
    /** @return array<string, mixed> */
    public function getConfiguration(): array { return $this->configuration; }
    /** @param array<string, mixed> $configuration */
    public function setConfiguration(array $configuration): void { $this->configuration = $configuration; $this->updatedAt = new \DateTimeImmutable(); }
    public function getSmtpPassword(): ?string { return $this->smtpPassword; }
    public function setSmtpPassword(?string $password): void { if (null !== $password && '' !== trim($password)) $this->smtpPassword = $password; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
}

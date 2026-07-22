<?php

declare(strict_types=1);

namespace App\Module\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'installed_module')]
class InstalledModule
{
    #[ORM\Id, ORM\Column(length: 80)] private string $code;
    #[ORM\Column(length: 40)] private string $version;
    #[ORM\Column(options: ['default' => true])] private bool $enabled = true;
    #[ORM\Column] private \DateTimeImmutable $installedAt;
    #[ORM\Column] private \DateTimeImmutable $updatedAt;

    public function __construct(string $code, string $version)
    {
        $this->code = $code;
        $this->version = $version;
        $this->installedAt = $this->updatedAt = new \DateTimeImmutable();
    }

    public function getCode(): string { return $this->code; }
    public function getVersion(): string { return $this->version; }
    public function isEnabled(): bool { return $this->enabled; }
    public function getInstalledAt(): \DateTimeImmutable { return $this->installedAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function synchronize(string $version): void { $this->version = $version; $this->updatedAt = new \DateTimeImmutable(); }
    public function enable(): void { $this->enabled = true; $this->updatedAt = new \DateTimeImmutable(); }
    public function disable(): void { $this->enabled = false; $this->updatedAt = new \DateTimeImmutable(); }
}

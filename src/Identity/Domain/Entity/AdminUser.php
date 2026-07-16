<?php

declare(strict_types=1);

namespace App\Identity\Domain\Entity;

use App\Identity\Infrastructure\Persistence\Doctrine\AdminUserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: AdminUserRepository::class)]
#[ORM\Table(name: 'admin_user')]
#[UniqueEntity(fields: ['email'], message: 'Konto z tym adresem e-mail już istnieje.')]
#[UniqueEntity(fields: ['username'], message: 'Konto z tym loginem już istnieje.')]
final class AdminUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private string $email;

    #[ORM\Column(length: 80, unique: true)]
    private string $username;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $active = true;

    #[ORM\Column(options: ['default' => false])]
    private bool $newsletter = false;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $roles = [];

    #[ORM\Column]
    private string $password = '';

    public function __construct(string $email, ?string $username = null)
    {
        $this->email = mb_strtolower(trim($email));
        $this->username = mb_strtolower(trim($username ?: strstr($this->email, '@', true) ?: $this->email));
    }

    public function getId(): ?int { return $this->id; }
    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): void { $this->email = mb_strtolower(trim($email)); }
    public function getUsername(): string { return $this->username; }
    public function setUsername(string $username): void { $this->username = mb_strtolower(trim($username)); }
    public function getFirstName(): ?string { return $this->firstName; }
    public function setFirstName(?string $firstName): void { $this->firstName = $firstName ?: null; }
    public function getLastName(): ?string { return $this->lastName; }
    public function setLastName(?string $lastName): void { $this->lastName = $lastName ?: null; }
    public function isActive(): bool { return $this->active; }
    public function setActive(bool $active): void { $this->active = $active; }
    public function isNewsletter(): bool { return $this->newsletter; }
    public function setNewsletter(bool $newsletter): void { $this->newsletter = $newsletter; }
    public function getDisplayName(): string { return trim(($this->firstName ?? '').' '.($this->lastName ?? '')) ?: $this->username; }
    public function getUserIdentifier(): string { return $this->username; }

    /** @return list<string> */
    public function getRoles(): array
    {
        return array_values(array_unique([...$this->roles, 'ROLE_ADMIN']));
    }

    /** @param list<string> $roles */
    public function setRoles(array $roles): void { $this->roles = $roles; }
    public function getPassword(): string { return $this->password; }
    public function setPassword(string $password): void { $this->password = $password; }
    public function eraseCredentials(): void {}
}

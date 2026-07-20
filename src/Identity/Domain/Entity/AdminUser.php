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
#[UniqueEntity(fields: ['email'], message: 'validation.user.email_exists')]
#[UniqueEntity(fields: ['username'], message: 'validation.user.username_exists')]
class AdminUser implements UserInterface, PasswordAuthenticatedUserInterface
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

    #[ORM\Column(options: ['default' => false])]
    private bool $apiEnabled = false;

    #[ORM\Column(length: 64, nullable: true, unique: true)]
    private ?string $apiTokenHash = null;

    #[ORM\Column(length: 12, nullable: true)]
    private ?string $apiTokenPrefix = null;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $apiScopes = [];

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $roles = ['ROLE_ADMIN'];

    #[ORM\Column]
    private string $password = '';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastLoginAt = null;

    public function __construct(string $email, ?string $username = null)
    {
        $this->email = mb_strtolower(trim($email));
        $this->username = mb_strtolower(trim($username ?: strstr($this->email, '@', true) ?: $this->email));
        $this->createdAt = new \DateTimeImmutable();
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
    public function isApiEnabled(): bool { return $this->apiEnabled; }
    public function setApiEnabled(bool $enabled): void { $this->apiEnabled = $enabled; }
    public function getApiTokenHash(): ?string { return $this->apiTokenHash; }
    public function getApiTokenPrefix(): ?string { return $this->apiTokenPrefix; }
    /** @return list<string> */
    public function getApiScopes(): array { return $this->apiScopes; }
    /** @param list<string> $scopes */
    public function setApiScopes(array $scopes): void { $this->apiScopes = array_values(array_unique($scopes)); }
    public function hasApiScope(string $scope): bool { return in_array($scope, $this->apiScopes, true); }
    public function rotateApiToken(): string
    {
        $token = 'shp4_'.bin2hex(random_bytes(32));
        $this->apiTokenHash = hash('sha256', $token);
        $this->apiTokenPrefix = substr($token, 0, 12);
        $this->apiEnabled = true;
        return $token;
    }
    public function revokeApiToken(): void { $this->apiEnabled = false; $this->apiTokenHash = null; $this->apiTokenPrefix = null; }
    public function getDisplayName(): string { return trim(($this->firstName ?? '').' '.($this->lastName ?? '')) ?: $this->username; }
    public function getUserIdentifier(): string { return $this->username; }

    /** @return list<string> */
    public function getRoles(): array
    {
        return array_values(array_unique([...$this->roles, 'ROLE_USER']));
    }

    /** @param list<string> $roles */
    public function setRoles(array $roles): void { $this->roles = $roles; }
    /** @return list<string> */
    public function getAssignedRoles(): array { return $this->roles; }
    /** @param list<string> $roles */
    public function setAssignedRoles(array $roles): void { $this->roles = array_values(array_intersect($roles, ['ROLE_ADMIN', 'ROLE_EDITOR'])); }
    public function isAdministrator(): bool { return in_array('ROLE_ADMIN', $this->roles, true); }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getLastLoginAt(): ?\DateTimeImmutable { return $this->lastLoginAt; }
    public function recordLogin(): void { $this->lastLoginAt = new \DateTimeImmutable(); }
    public function getPassword(): string { return $this->password; }
    public function setPassword(string $password): void { $this->password = $password; }
    public function eraseCredentials(): void {}
}

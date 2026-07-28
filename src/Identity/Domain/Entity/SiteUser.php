<?php

declare(strict_types=1);

namespace App\Identity\Domain\Entity;

use App\Identity\Infrastructure\Persistence\Doctrine\SiteUserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SiteUserRepository::class)]
#[ORM\Table(name: 'site_user')]
#[UniqueEntity(fields: ['email'], message: 'validation.user.email_exists')]
#[UniqueEntity(fields: ['username'], message: 'validation.user.username_exists')]
class SiteUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;
    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank, Assert\Email, Assert\Length(max: 180)]
    private string $email;
    #[ORM\Column(length: 80, unique: true)]
    #[Assert\NotBlank, Assert\Length(min: 2, max: 80), Assert\Regex(pattern: '/^[a-z0-9._-]+$/i')]
    private string $username;
    #[ORM\Column]
    private string $password = '';
    #[ORM\Column(options: ['default' => true])]
    private bool $active = true;
    #[ORM\Column(options: ['default' => false])]
    private bool $newsletter = false;
    /** @var Collection<int, Membership> */
    #[ORM\ManyToMany(targetEntity: Membership::class)]
    #[ORM\JoinTable(name: 'site_user_membership')]
    private Collection $memberships;
    #[ORM\Column]
    private \DateTimeImmutable $createdAt;
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastLoginAt = null;
    #[ORM\Column(length: 64, nullable: true, unique: true)]
    private ?string $activationTokenHash = null;
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $activationExpiresAt = null;

    public function __construct(string $email, ?string $username = null)
    {
        $this->email = mb_strtolower(trim($email));
        $this->username = mb_strtolower(trim($username ?: strstr($this->email, '@', true) ?: $this->email));
        $this->memberships = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getEmail(): string { return $this->email; }
    public function setEmail(?string $email): void { $this->email = mb_strtolower(trim($email ?? '')); }
    public function getUsername(): string { return $this->username; }
    public function setUsername(?string $username): void { $this->username = mb_strtolower(trim($username ?? '')); }
    public function getUserIdentifier(): string { return $this->username; }
    public function getPassword(): string { return $this->password; }
    public function setPassword(string $password): void { $this->password = $password; }
    public function isActive(): bool { return $this->active; }
    public function setActive(bool $active): void { $this->active = $active; }
    public function isNewsletter(): bool { return $this->newsletter; }
    public function setNewsletter(bool $newsletter): void { $this->newsletter = $newsletter; }
    /** @return list<string> */
    public function getRoles(): array { return ['ROLE_SITE_USER']; }
    public function eraseCredentials(): void {}
    /** @return Collection<int, Membership> */
    public function getMemberships(): Collection { return $this->memberships; }
    public function addMembership(Membership $membership): void { if (!$this->memberships->contains($membership)) $this->memberships->add($membership); }
    public function removeMembership(Membership $membership): void { $this->memberships->removeElement($membership); }
    public function hasActiveMembership(Membership $membership): bool { return $membership->isActive() && $this->memberships->contains($membership); }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getLastLoginAt(): ?\DateTimeImmutable { return $this->lastLoginAt; }
    public function recordLogin(): void { $this->lastLoginAt = new \DateTimeImmutable(); }
    public function issueActivationToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->activationTokenHash = hash('sha256', $token);
        $this->activationExpiresAt = new \DateTimeImmutable('+24 hours');
        $this->active = false;
        return $token;
    }
    public function activateWithToken(string $token): bool
    {
        if ($this->activationTokenHash === null || $this->activationExpiresAt === null || $this->activationExpiresAt < new \DateTimeImmutable() || !hash_equals($this->activationTokenHash, hash('sha256', $token))) return false;
        $this->active = true; $this->activationTokenHash = null; $this->activationExpiresAt = null;
        return true;
    }
}

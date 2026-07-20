<?php
declare(strict_types=1);

namespace App\Audit\Domain\Entity;

use App\Audit\Infrastructure\Persistence\Doctrine\AuditLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuditLogRepository::class)]
#[ORM\Table(name: 'audit_log')]
#[ORM\Index(columns: ['created_at'], name: 'idx_audit_created')]
#[ORM\Index(columns: ['type'], name: 'idx_audit_type')]
class AuditLog
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;
    #[ORM\Column(length: 20)]
    private string $type;
    #[ORM\Column(length: 120)]
    private string $action;
    #[ORM\Column(length: 255)]
    private string $message;
    #[ORM\Column(length: 180, nullable: true)]
    private ?string $username;
    #[ORM\Column(length: 45, nullable: true)]
    private ?string $ipAddress;
    /** @var array<string,mixed> */
    #[ORM\Column(type: Types::JSON)]
    private array $data;
    #[ORM\Column]
    private bool $important;
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /** @param array<string,mixed> $data */
    public function __construct(string $type, string $action, string $message, ?string $username = null, ?string $ipAddress = null, array $data = [], bool $important = false)
    {
        $this->type = $type;
        $this->action = $action;
        $this->message = $message;
        $this->username = $username;
        $this->ipAddress = $ipAddress;
        $this->data = $data;
        $this->important = $important;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getType(): string { return $this->type; }
    public function getAction(): string { return $this->action; }
    public function getMessage(): string { return $this->message; }
    public function getUsername(): ?string { return $this->username; }
    public function getIpAddress(): ?string { return $this->ipAddress; }
    /** @return array<string,mixed> */ public function getData(): array { return $this->data; }
    public function isImportant(): bool { return $this->important; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}

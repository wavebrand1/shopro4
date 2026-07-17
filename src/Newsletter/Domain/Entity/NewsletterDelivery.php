<?php

declare(strict_types=1);

namespace App\Newsletter\Domain\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'newsletter_delivery')]
#[ORM\UniqueConstraint(name: 'uniq_campaign_recipient', columns: ['campaign_id', 'recipient'])]
class NewsletterDelivery
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id = null;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')] private NewsletterCampaign $campaign;
    #[ORM\Column(length: 180)] private string $recipient;
    #[ORM\Column(length: 20)] private string $status = 'queued';
    #[ORM\Column(type: Types::TEXT, nullable: true)] private ?string $error = null;
    #[ORM\Column] private \DateTimeImmutable $createdAt;
    #[ORM\Column(nullable: true)] private ?\DateTimeImmutable $sentAt = null;
    public function __construct(NewsletterCampaign $campaign, string $recipient) { $this->campaign = $campaign; $this->recipient = $recipient; $this->createdAt = new \DateTimeImmutable(); }
    public function getId(): ?int { return $this->id; }
    public function getCampaign(): NewsletterCampaign { return $this->campaign; }
    public function getRecipient(): string { return $this->recipient; }
    public function getStatus(): string { return $this->status; }
    public function getError(): ?string { return $this->error; }
    public function getSentAt(): ?\DateTimeImmutable { return $this->sentAt; }
    public function markSent(): void { $this->status = 'sent'; $this->sentAt = new \DateTimeImmutable(); $this->error = null; }
    public function markFailed(string $error): void { $this->status = 'failed'; $this->error = mb_substr($error, 0, 4000); }
}

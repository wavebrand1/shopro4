<?php

declare(strict_types=1);

namespace App\Newsletter\Domain\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'newsletter_campaign')]
class NewsletterCampaign
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id = null;
    #[ORM\Column(length: 180)] private string $subject = '';
    #[ORM\Column(type: Types::TEXT)] private string $content = '';
    #[ORM\Column(length: 20)] private string $status = 'draft';
    #[ORM\Column] private \DateTimeImmutable $createdAt;
    #[ORM\Column(nullable: true)] private ?\DateTimeImmutable $queuedAt = null;
    #[ORM\Column(options: ['default' => true])] private bool $includeSubscribers = true;
    /** @var list<int> */
    #[ORM\Column(type: Types::JSON)] private array $selectedUserIds = [];
    /** @var list<int> */
    #[ORM\Column(type: Types::JSON)] private array $selectedSiteUserIds = [];
    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)] private array $customEmails = [];
    public function __construct() { $this->createdAt = new \DateTimeImmutable(); }
    public function getId(): ?int { return $this->id; }
    public function getSubject(): string { return $this->subject; }
    public function setSubject(string $subject): void { $this->subject = trim($subject); }
    public function getContent(): string { return $this->content; }
    public function setContent(string $content): void { $this->content = $content; }
    public function getStatus(): string { return $this->status; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getQueuedAt(): ?\DateTimeImmutable { return $this->queuedAt; }
    public function isIncludeSubscribers(): bool { return $this->includeSubscribers; }
    public function setIncludeSubscribers(bool $value): void { $this->includeSubscribers = $value; }
    /** @return list<int> */ public function getSelectedUserIds(): array { return $this->selectedUserIds; }
    /** @param list<int|string> $ids */ public function setSelectedUserIds(array $ids): void { $this->selectedUserIds = array_values(array_unique(array_map('intval', $ids))); }
    /** @return list<int> */ public function getSelectedSiteUserIds(): array { return $this->selectedSiteUserIds; }
    /** @param list<int|string> $ids */ public function setSelectedSiteUserIds(array $ids): void { $this->selectedSiteUserIds = array_values(array_unique(array_map('intval', $ids))); }
    /** @return list<string> */ public function getCustomEmails(): array { return $this->customEmails; }
    /** @param list<string> $emails */ public function setCustomEmails(array $emails): void { $this->customEmails = array_values(array_unique(array_map(static fn(string $email): string => mb_strtolower(trim($email)), $emails))); }
    public function markQueued(): void { $this->status = 'queued'; $this->queuedAt = new \DateTimeImmutable(); }
    public function markCompleted(): void { $this->status = 'sent'; }
    public function markFailed(): void { $this->status = 'failed'; }
    public function markWithoutRecipients(): void { $this->status = 'no_recipients'; }
}

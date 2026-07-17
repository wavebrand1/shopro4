<?php

declare(strict_types=1);

namespace App\Mail\Domain\Entity;

use App\Mail\Infrastructure\Persistence\Doctrine\EmailTemplateRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EmailTemplateRepository::class)]
#[ORM\Table(name: 'email_template')]
class EmailTemplate
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id = null;
    #[ORM\Column(length: 80, unique: true)] private string $code = '';
    #[ORM\Column(length: 160)] private string $name = '';
    #[ORM\Column(length: 180)] private string $subject = '';
    #[ORM\Column(type: Types::TEXT)] private string $content = '';
    #[ORM\Column(options: ['default' => false])] private bool $system = false;
    public function getId(): ?int { return $this->id; }
    public function getCode(): string { return $this->code; } public function setCode(string $value): void { $this->code = trim($value); }
    public function getName(): string { return $this->name; } public function setName(string $value): void { $this->name = trim($value); }
    public function getSubject(): string { return $this->subject; } public function setSubject(string $value): void { $this->subject = trim($value); }
    public function getContent(): string { return $this->content; } public function setContent(string $value): void { $this->content = $value; }
    public function isSystem(): bool { return $this->system; } public function setSystem(bool $value): void { $this->system = $value; }
}

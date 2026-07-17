<?php
declare(strict_types=1);
namespace App\Language\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'language')]
#[ORM\UniqueConstraint(name: 'uniq_language_code', columns: ['code'])]
class Language
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id = null;
    #[ORM\Column(length: 80)] private string $name = '';
    #[ORM\Column(length: 2)] private string $code = '';
    #[ORM\Column(length: 3)] private string $direction = 'ltr';
    #[ORM\Column(length: 120, nullable: true)] private ?string $author = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $subdomain = null;
    #[ORM\Column(options: ['default'=>true])] private bool $active = true;
    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $v): void { $this->name=trim($v); }
    public function getCode(): string { return $this->code; }
    public function setCode(string $v): void { $this->code=mb_strtolower(trim($v)); }
    public function getDirection(): string { return $this->direction; }
    public function setDirection(string $v): void { $this->direction=in_array($v,['ltr','rtl'],true)?$v:'ltr'; }
    public function getAuthor(): ?string { return $this->author; }
    public function setAuthor(?string $v): void { $this->author=$v ? trim($v) : null; }
    public function getSubdomain(): ?string { return $this->subdomain; }
    public function setSubdomain(?string $v): void { $this->subdomain=$v ? trim($v) : null; }
    public function isActive(): bool { return $this->active; }
    public function setActive(bool $v): void { $this->active=$v; }
}

<?php

declare(strict_types=1);

namespace App\Cms\Domain\Entity;

use App\Cms\Infrastructure\Persistence\Doctrine\MenuItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: MenuItemRepository::class)]
#[ORM\Table(name: 'cms_menu_item')]
class MenuItem
{
    public const PLACE_FOOTER = 0;
    public const PLACE_HEADER = 1;
    public const TYPE_PAGE = 'page';
    public const TYPE_WEB = 'web';
    public const TYPE_PLACEHOLDER = 'placeholder';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: self::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?self $parent = null;

    #[ORM\ManyToOne(targetEntity: Page::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Page $page = null;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 120)]
    private string $name = '';

    #[ORM\Column(length: 200, nullable: true)]
    #[Assert\Length(max: 200)]
    private ?string $caption = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [self::TYPE_PAGE, self::TYPE_WEB, self::TYPE_PLACEHOLDER])]
    private string $contentType = self::TYPE_PAGE;

    #[ORM\Column(length: 500, nullable: true)]
    #[Assert\Length(max: 500)]
    private ?string $link = null;

    #[ORM\Column(length: 10, options: ['default' => '_self'])]
    #[Assert\Choice(choices: ['_self', '_blank'])]
    private string $target = '_self';

    #[ORM\Column(options: ['default' => 0])]
    #[Assert\Range(min: 0, max: 9999)]
    private int $position = 0;

    #[ORM\Column(options: ['default' => false])]
    private bool $homePage = false;

    #[ORM\Column(options: ['default' => self::PLACE_HEADER])]
    #[Assert\Choice(choices: [self::PLACE_FOOTER, self::PLACE_HEADER])]
    private int $place = self::PLACE_HEADER;

    #[ORM\Column(options: ['default' => true])]
    private bool $active = true;

    public function getId(): ?int { return $this->id; }
    public function getParent(): ?self { return $this->parent; }
    public function setParent(?self $parent): void { $this->parent = $parent; }
    public function getPage(): ?Page { return $this->page; }
    public function setPage(?Page $page): void { $this->page = $page; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): void { $this->name = trim($name); }
    public function getCaption(): ?string { return $this->caption; }
    public function setCaption(?string $caption): void { $this->caption = ($value = trim((string) $caption)) !== '' ? $value : null; }
    public function getContentType(): string { return $this->contentType; }
    public function setContentType(string $contentType): void { $this->contentType = $contentType; }
    public function getLink(): ?string { return $this->link; }
    public function setLink(?string $link): void { $this->link = ($value = trim((string) $link)) !== '' ? $value : null; }
    public function getTarget(): string { return $this->target; }
    public function setTarget(string $target): void { $this->target = $target; }
    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): void { $this->position = $position; }
    public function isHomePage(): bool { return $this->homePage; }
    public function setHomePage(bool $homePage): void { $this->homePage = $homePage; }
    public function getPlace(): int { return $this->place; }
    public function setPlace(int $place): void { $this->place = $place; }
    public function isActive(): bool { return $this->active; }
    public function setActive(bool $active): void { $this->active = $active; }

    #[Assert\Callback]
    public function validateLinkConfiguration(ExecutionContextInterface $context): void
    {
        if ($this->contentType === self::TYPE_PAGE && $this->page === null && !$this->homePage) {
            $context->buildViolation('Wybierz podstronę dla tej pozycji menu.')->atPath('page')->addViolation();
        }

        if ($this->contentType === self::TYPE_WEB && $this->link === null) {
            $context->buildViolation('Podaj adres linku zewnętrznego.')->atPath('link')->addViolation();
        }
    }
}

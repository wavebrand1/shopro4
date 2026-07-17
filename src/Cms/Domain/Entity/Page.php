<?php

declare(strict_types=1);

namespace App\Cms\Domain\Entity;

use App\Cms\Infrastructure\Persistence\Doctrine\PageRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PageRepository::class)]
#[ORM\Table(name: 'cms_page')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['slug'], message: 'Podstrona z takim adresem już istnieje.')]
class Page
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 200)]
    private string $title = '';

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', message: 'Slug może zawierać małe litery, cyfry i myślniki.')]
    private string $slug = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $content = '';

    #[ORM\Column(length: 20, options: ['default' => 'rich_text'])]
    #[Assert\Choice(choices: ['rich_text', 'components'])]
    private string $editorMode = 'components';

    #[ORM\Column(type: Types::TEXT, options: ['default' => ''])]
    private string $builderData = '';

    #[ORM\Column(type: Types::TEXT, options: ['default' => ''])]
    private string $builderCss = '';

    #[ORM\Column(options: ['default' => false])]
    private bool $published = false;

    #[ORM\Column(length: 600, options: ['default' => ''])]
    private string $caption = '';
    #[ORM\Column(length: 200, options: ['default' => ''])]
    private string $seoTitle = '';
    #[ORM\Column(type: Types::TEXT, options: ['default' => ''])]
    private string $description = '';
    #[ORM\Column(type: Types::TEXT, options: ['default' => ''])]
    private string $keywords = '';
    #[ORM\Column(type: Types::TEXT, options: ['default' => ''])]
    private string $meta = '';
    #[ORM\Column(type: Types::TEXT, options: ['default' => ''])]
    private string $javascript = '';
    #[ORM\Column(length: 300, options: ['default' => ''])]
    private string $canonical = '';
    #[ORM\Column(length: 20, options: ['default' => 'Public'])]
    #[Assert\Choice(choices: ['Public', 'Registered', 'Membership'])]
    private string $access = 'Public';
    #[ORM\Column(options: ['default' => true])]
    private bool $follow = true;
    #[ORM\Column(options: ['default' => false])]
    private bool $homePage = false;
    #[ORM\Column(options: ['default' => false])]
    private bool $errorPage = false;
    #[ORM\Column(options: ['default' => false])]
    private bool $adminOnly = false;
    #[ORM\Column(options: ['default' => false])]
    private bool $loginPage = false;
    #[ORM\Column(options: ['default' => false])]
    private bool $activationPage = false;
    #[ORM\Column(options: ['default' => false])]
    private bool $accountPage = false;
    #[ORM\Column(options: ['default' => false])]
    private bool $registrationPage = false;
    #[ORM\Column(options: ['default' => false])]
    private bool $searchPage = false;
    #[ORM\Column(options: ['default' => false])]
    private bool $sitemapPage = false;
    #[ORM\Column(options: ['default' => false])]
    private bool $profilePage = false;
    #[ORM\Column(options: ['default' => false])]
    private bool $termsPage = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
        $this->builderData = json_encode([[
            'id' => 'initial-section',
            'type' => 'layout_section',
            'data' => ['container' => 'grid', 'widths' => [100], 'columns' => [[[
                'id' => 'initial-text',
                'type' => 'rich_text',
                'data' => ['content' => '<p>Rozpocznij pisanie treści…</p>'],
            ]]]],
        ]], JSON_THROW_ON_ERROR);
    }

    public function getId(): ?int { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): void { $this->title = trim($title); }
    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): void { $this->slug = mb_strtolower(trim($slug)); }
    public function getContent(): string { return $this->content; }
    public function setContent(?string $content): void { $this->content = trim($content ?? ''); }
    public function getEditorMode(): string { return $this->editorMode; }
    public function setEditorMode(string $editorMode): void { $this->editorMode = $editorMode; }
    public function usesComponentBuilder(): bool { return $this->editorMode === 'components'; }
    public function getBuilderData(): string { return $this->builderData; }
    public function setBuilderData(?string $value): void { $this->builderData = trim($value ?? ''); }
    /** @return list<array{id: string, type: string, data: array<string, mixed>}> */
    public function getBuilderBlocks(): array
    {
        try {
            $blocks = json_decode($this->builderData, true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        if (!is_array($blocks)) return [];

        return array_values(array_filter($blocks, static fn (mixed $block): bool =>
            is_array($block) && isset($block['id'], $block['type'], $block['data']) && is_array($block['data'])
        ));
    }
    public function getBuilderCss(): string { return $this->builderCss; }
    public function setBuilderCss(?string $value): void { $this->builderCss = trim($value ?? ''); }
    public function isPublished(): bool { return $this->published; }
    public function setPublished(bool $published): void { $this->published = $published; }
    public function getCaption(): string { return $this->caption; }
    public function setCaption(?string $v): void { $this->caption = trim($v ?? ''); }
    public function getSeoTitle(): string { return $this->seoTitle; }
    public function setSeoTitle(?string $v): void { $this->seoTitle = trim($v ?? ''); }
    public function getEffectiveSeoTitle(): string { return $this->seoTitle ?: $this->title; }
    public function getDescription(): string { return $this->description; }
    public function setDescription(?string $v): void { $this->description = trim($v ?? ''); }
    public function getKeywords(): string { return $this->keywords; }
    public function setKeywords(?string $v): void { $this->keywords = trim($v ?? ''); }
    public function getMeta(): string { return $this->meta; }
    public function setMeta(?string $v): void { $this->meta = trim($v ?? ''); }
    public function getJavascript(): string { return $this->javascript; }
    public function setJavascript(?string $v): void { $this->javascript = trim($v ?? ''); }
    public function getCanonical(): string { return $this->canonical; }
    public function setCanonical(?string $v): void { $this->canonical = trim($v ?? ''); }
    public function getAccess(): string { return $this->access; }
    public function setAccess(string $v): void { $this->access = $v; }
    public function isFollow(): bool { return $this->follow; }
    public function setFollow(bool $v): void { $this->follow = $v; }
    public function isHomePage(): bool { return $this->homePage; }
    public function setHomePage(bool $v): void { $this->homePage = $v; }
    public function isErrorPage(): bool { return $this->errorPage; }
    public function setErrorPage(bool $v): void { $this->errorPage = $v; }
    public function isAdminOnly(): bool { return $this->adminOnly; }
    public function setAdminOnly(bool $v): void { $this->adminOnly = $v; }
    public function isLoginPage(): bool { return $this->loginPage; }
    public function setLoginPage(bool $v): void { $this->loginPage = $v; }
    public function isActivationPage(): bool { return $this->activationPage; }
    public function setActivationPage(bool $v): void { $this->activationPage = $v; }
    public function isAccountPage(): bool { return $this->accountPage; }
    public function setAccountPage(bool $v): void { $this->accountPage = $v; }
    public function isRegistrationPage(): bool { return $this->registrationPage; }
    public function setRegistrationPage(bool $v): void { $this->registrationPage = $v; }
    public function isSearchPage(): bool { return $this->searchPage; }
    public function setSearchPage(bool $v): void { $this->searchPage = $v; }
    public function isSitemapPage(): bool { return $this->sitemapPage; }
    public function setSitemapPage(bool $v): void { $this->sitemapPage = $v; }
    public function isProfilePage(): bool { return $this->profilePage; }
    public function setProfilePage(bool $v): void { $this->profilePage = $v; }
    public function isTermsPage(): bool { return $this->termsPage; }
    public function setTermsPage(bool $v): void { $this->termsPage = $v; }
    public function isSystemPage(): bool { return $this->loginPage || $this->activationPage || $this->accountPage || $this->registrationPage || $this->searchPage || $this->sitemapPage || $this->profilePage || $this->errorPage || $this->termsPage; }
    public function copyAs(string $slug): self
    {
        $copy = clone $this;
        $copy->id = null; $copy->slug = $slug; $copy->homePage = $copy->errorPage = false;
        $copy->loginPage = $copy->activationPage = $copy->accountPage = $copy->registrationPage = false;
        $copy->searchPage = $copy->sitemapPage = $copy->profilePage = $copy->termsPage = false;
        $copy->createdAt = $copy->updatedAt = new DateTimeImmutable();
        return $copy;
    }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): DateTimeImmutable { return $this->updatedAt; }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}

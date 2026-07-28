<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Twig;

use App\Cms\Domain\Entity\Page;
use App\Cms\Domain\Entity\PageTranslation;
use App\Cms\Infrastructure\Persistence\Doctrine\PageRepository;
use App\Language\Domain\Entity\Language;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class SystemPageExtension extends AbstractExtension
{
    /** @var array<string, array{property: string, fallback: string}> */
    private const ROLES = [
        'login' => ['property' => 'loginPage', 'fallback' => 'site_login'],
        'activation' => ['property' => 'activationPage', 'fallback' => 'site_activation_resend'],
        'account' => ['property' => 'accountPage', 'fallback' => 'site_account'],
        'registration' => ['property' => 'registrationPage', 'fallback' => 'site_register'],
        'search' => ['property' => 'searchPage', 'fallback' => 'cms_search'],
        'sitemap' => ['property' => 'sitemapPage', 'fallback' => 'cms_sitemap_page'],
        'profile' => ['property' => 'profilePage', 'fallback' => 'site_account_profile'],
    ];

    public function __construct(
        private readonly PageRepository $pages,
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requests,
        private readonly UrlGeneratorInterface $urls,
    ) {
    }

    public function getFunctions(): array
    {
        return [new TwigFunction('shopro_system_page_url', $this->url(...))];
    }

    public function url(string $role): string
    {
        $definition = self::ROLES[$role] ?? null;
        if ($definition === null) return '#';

        $page = $this->pages->findOneBy([$definition['property'] => true, 'deletedAt' => null]);
        $language = $this->requests->getCurrentRequest()?->attributes->get('_shopro_language');
        if (!$page instanceof Page) {
            if ($language instanceof Language && !$language->isDefaultLanguage() && in_array($role, ['search', 'sitemap'], true)) {
                return $this->urls->generate($definition['fallback'].'_localized', ['_locale' => $language->getCode()]);
            }
            return $this->urls->generate($definition['fallback']);
        }

        if ($language instanceof Language && !$language->isDefaultLanguage()) {
            $translation = $this->entityManager->getRepository(PageTranslation::class)->findOneBy([
                'page' => $page,
                'language' => $language,
                'published' => true,
            ]);
            if ($translation instanceof PageTranslation) {
                return $this->urls->generate('cms_page_show_localized', [
                    '_locale' => $language->getCode(),
                    'slug' => $translation->getSlug(),
                ]);
            }
        }

        return $this->urls->generate('cms_page_show', ['slug' => $page->getSlug()]);
    }
}

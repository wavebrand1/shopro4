<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http;

use App\Cms\Domain\Entity\Page;
use App\Cms\Domain\Entity\PageTranslation;
use App\Cms\Infrastructure\Persistence\Doctrine\PageRepository;
use App\Identity\Presentation\Http\SiteAccountController;
use App\Identity\Presentation\Http\SiteLoginController;
use App\Identity\Presentation\Http\SiteRegistrationController;
use App\Language\Domain\Entity\Language;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class SystemPageSlugSubscriber
{
    /**
     * @var array<string, array{route: string, controller: string, methods: list<string>}>
     */
    private const ROLE_ROUTES = [
        'loginPage' => ['route' => 'site_login', 'controller' => SiteLoginController::class.'::login', 'methods' => ['GET', 'POST']],
        'activationPage' => ['route' => 'site_activation_resend', 'controller' => SiteRegistrationController::class.'::resend', 'methods' => ['GET', 'POST']],
        'accountPage' => ['route' => 'site_account', 'controller' => SiteAccountController::class.'::index', 'methods' => ['GET']],
        'registrationPage' => ['route' => 'site_register', 'controller' => SiteRegistrationController::class.'::register', 'methods' => ['GET', 'POST']],
        'searchPage' => ['route' => 'cms_search', 'controller' => SearchController::class.'::search', 'methods' => ['GET']],
        'sitemapPage' => ['route' => 'cms_sitemap_page', 'controller' => SeoController::class.'::sitemapPage', 'methods' => ['GET']],
        'profilePage' => ['route' => 'site_account_profile', 'controller' => SiteAccountController::class.'::profile', 'methods' => ['GET', 'POST']],
    ];

    /** @var array<string, string> */
    private const TECHNICAL_PATHS = [
        'site_login' => '/login',
        'site_activation_resend' => '/activation/resend',
        'site_account' => '/account',
        'site_register' => '/rejestracja',
        'cms_search' => '/search',
        'cms_search_localized' => '/search',
        'cms_sitemap_page' => '/site-map',
        'cms_sitemap_page_localized' => '/site-map',
        'site_account_profile' => '/account/profile',
    ];

    public function __construct(
        private readonly PageRepository $pages,
        private readonly EntityManagerInterface $entityManager,
        private readonly UrlGeneratorInterface $urls,
    ) {
    }

    #[AsEventListener(event: 'kernel.request', priority: 40)]
    public function routeEditableSlug(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) return;
        $request = $event->getRequest();
        if ($request->getPathInfo() === '/install' || str_starts_with($request->getPathInfo(), '/install/')) return;
        if (!in_array($request->getMethod(), ['GET', 'POST', 'HEAD'], true)) return;
        $effectiveMethod = $request->isMethod('HEAD') ? 'GET' : $request->getMethod();

        [$page, $language] = $this->pageFromPath($request);
        if (!$page instanceof Page) return;

        foreach (self::ROLE_ROUTES as $property => $target) {
            $getter = 'is'.ucfirst($property);
            if (!$page->{$getter}() || !in_array($effectiveMethod, $target['methods'], true)) continue;

            $route = $target['route'];
            $controller = $target['controller'];
            $parameters = [];
            if ($language instanceof Language && !$language->isDefaultLanguage()) {
                if ($property === 'searchPage') {
                    $route = 'cms_search_localized';
                    $controller = SearchController::class.'::localized';
                } elseif ($property === 'sitemapPage') {
                    $route = 'cms_sitemap_page_localized';
                    $controller = SeoController::class.'::localizedSitemapPage';
                }
                $parameters['_locale'] = $language->getCode();
                $request->attributes->set('_locale', $language->getCode());
            }

            $request->attributes->set('_controller', $controller);
            $request->attributes->set('_route', $route);
            $request->attributes->set('_route_params', $parameters);
            foreach ($parameters as $name => $value) $request->attributes->set($name, $value);
            return;
        }
    }

    #[AsEventListener(event: 'kernel.request', priority: 15)]
    public function redirectTechnicalAddress(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) return;
        $request = $event->getRequest();
        if (!$request->isMethodSafe(false)) return;

        $route = (string) $request->attributes->get('_route');
        $technicalPath = self::TECHNICAL_PATHS[$route] ?? null;
        if ($technicalPath === null || !$this->isTechnicalPath($request->getPathInfo(), $technicalPath, $route)) return;

        $page = $this->pageForRoute($route);
        if (!$page instanceof Page) return;

        $language = $request->attributes->get('_shopro_language');
        $target = $this->canonicalUrl($page, $language instanceof Language ? $language : null);
        if ($target === $request->getPathInfo()) return;
        if ($query = $request->getQueryString()) $target .= '?'.$query;

        $event->setResponse(new RedirectResponse($target, Response::HTTP_PERMANENTLY_REDIRECT));
    }

    /** @return array{Page|null, Language|null} */
    private function pageFromPath(Request $request): array
    {
        $segments = array_values(array_filter(explode('/', trim($request->getPathInfo(), '/')), 'strlen'));
        if (count($segments) === 1) {
            return [$this->pages->findPublishedBySlug($segments[0]), null];
        }
        if (count($segments) !== 2 || !preg_match('/^[a-z]{2}$/', $segments[0])) return [null, null];

        $language = $this->entityManager->getRepository(Language::class)->findOneBy([
            'code' => $segments[0],
            'active' => true,
        ]);
        if (!$language instanceof Language || $language->isDefaultLanguage()) return [null, null];

        $translation = $this->entityManager->getRepository(PageTranslation::class)->findOneBy([
            'language' => $language,
            'slug' => $segments[1],
            'published' => true,
        ]);
        if (!$translation instanceof PageTranslation || !$translation->getPage()->isPubliclyAvailable()) return [null, null];

        return [$translation->getPage(), $language];
    }

    private function pageForRoute(string $route): ?Page
    {
        $property = match ($route) {
            'site_login' => 'loginPage',
            'site_activation_resend' => 'activationPage',
            'site_account' => 'accountPage',
            'site_register' => 'registrationPage',
            'cms_search', 'cms_search_localized' => 'searchPage',
            'cms_sitemap_page', 'cms_sitemap_page_localized' => 'sitemapPage',
            'site_account_profile' => 'profilePage',
            default => null,
        };

        return $property === null ? null : $this->pages->findOneBy([$property => true, 'deletedAt' => null]);
    }

    private function canonicalUrl(Page $page, ?Language $language): string
    {
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

    private function isTechnicalPath(string $path, string $technicalPath, string $route): bool
    {
        if (str_ends_with($route, '_localized')) {
            return (bool) preg_match('#^/[a-z]{2}'.preg_quote($technicalPath, '#').'$#', $path);
        }

        return $path === $technicalPath;
    }
}

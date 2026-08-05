<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http;

use App\Cms\Application\SystemPageRenderer;
use App\Cms\Domain\Entity\Page;
use App\Language\Domain\Entity\Language;
use App\Cms\Infrastructure\Persistence\Doctrine\PageRepository;
use App\Settings\Application\SettingsProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use App\Cms\Application\SitemapProvider;

#[\App\Module\Application\RequiresModule('cms')]
final class SeoController extends AbstractController
{
    public function __construct(#[AutowireIterator('shopro.sitemap_provider')] private readonly iterable $sitemapProviders) {}
    #[Route('/site-map', name: 'cms_sitemap_page', methods: ['GET'], priority: 100)]
    public function sitemapPage(PageRepository $pages, SystemPageRenderer $systemPages): Response
    {
        return $this->renderSitemapPage($pages, $systemPages, null);
    }

    #[Route('/{_locale}/site-map', name: 'cms_sitemap_page_localized', requirements: ['_locale' => '[a-z]{2}'], methods: ['GET'], priority: 100)]
    public function localizedSitemapPage(string $_locale, PageRepository $pages, EntityManagerInterface $entityManager, Request $request, SystemPageRenderer $systemPages): Response
    {
        $language = $entityManager->getRepository(Language::class)->findOneBy(['code' => $_locale, 'active' => true]);
        if (!$language) {
            throw $this->createNotFoundException();
        }
        if ($language->isDefaultLanguage()) {
            return $this->redirectToRoute('cms_sitemap_page', $request->query->all());
        }

        return $this->renderSitemapPage($pages, $systemPages, $language);
    }

    #[Route('/sitemap.xml', name: 'cms_sitemap', methods: ['GET'], priority: 100)]
    public function sitemap(PageRepository $pages): Response
    {
        $entries = [];
        foreach ($pages->findPublicForSitemap() as $page) {
            $entries[] = [
                'location' => $this->pageUrl($page),
                'modified' => $page->getUpdatedAt(),
                'alternates' => $this->translationUrls($page, $pages),
            ];
        }
        foreach ($this->sitemapProviders as $provider) {
            if (!$provider instanceof SitemapProvider) continue;
            array_push($entries, ...$provider->entries());
        }

        $response = $this->render('cms/seo/sitemap.xml.twig', ['entries' => $entries]);
        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');
        $this->preventStaleSeoResponse($response);

        return $response;
    }

    #[Route('/robots.txt', name: 'cms_robots', methods: ['GET'], priority: 100)]
    public function robots(SettingsProvider $settings): Response
    {
        $lines = ['User-agent: *'];
        if ((bool) $settings->get('maintenance', false)) {
            $lines[] = 'Disallow: /';
        } else {
            $lines[] = 'Allow: /';
            $lines[] = 'Disallow: /admin';
        }
        $lines[] = 'Sitemap: '.$this->generateUrl('cms_sitemap', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $response = new Response(implode("\n", $lines)."\n", Response::HTTP_OK, ['Content-Type' => 'text/plain; charset=UTF-8']);
        $this->preventStaleSeoResponse($response);

        return $response;
    }

    private function preventStaleSeoResponse(Response $response): void
    {
        // Publication windows and maintenance mode can change independently of
        // a deployment. A shared cache must therefore never keep an obsolete
        // sitemap or robots policy after such a boundary has been crossed.
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-store');
        $response->headers->addCacheControlDirective('must-revalidate');
    }

    private function renderSitemapPage(PageRepository $pages, SystemPageRenderer $systemPages, ?Language $language): Response
    {
        $entries = [];
        foreach ($pages->findPublicForSitemap() as $page) {
            if ($language !== null) {
                $translation = null;
                foreach ($pages->findPublishedActiveTranslations($page) as $candidate) {
                    if ($candidate->getLanguage()->getId() === $language->getId()) {
                        $translation = $candidate;
                        break;
                    }
                }
                if ($translation === null) {
                    continue;
                }
                $entries[] = [
                    'title' => $translation->getTitle(),
                    'url' => $this->generateUrl('cms_page_show_localized', [
                        '_locale' => $language->getCode(),
                        'slug' => $translation->getSlug(),
                    ]),
                ];
                continue;
            }
            $entries[] = [
                'title' => $page->getTitle(),
                'url' => $this->generateUrl($page->isHomePage() ? 'app_home' : 'cms_page_show', $page->isHomePage() ? [] : ['slug' => $page->getSlug()]),
            ];
        }

        $context = ['entries' => $entries];
        return $systemPages->render(['sitemapPage' => true], 'cms/seo/_sitemap_content.html.twig', $context)
            ?? $this->render('cms/seo/sitemap.html.twig', $context);
    }

    private function pageUrl(Page $page): string
    {
        return $this->generateUrl($page->isHomePage() ? 'app_home' : 'cms_page_show', $page->isHomePage() ? [] : ['slug' => $page->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /** @return list<array{language:string, location:string}> */
    private function translationUrls(Page $page, PageRepository $pages): array
    {
        $urls = [];
        foreach ($pages->findPublishedActiveTranslations($page) as $translation) {
            $language = $translation->getLanguage();
            $urls[] = [
                'language' => $language->getCode(),
                'location' => $this->generateUrl('cms_page_show_localized', ['_locale' => $language->getCode(), 'slug' => $translation->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL),
            ];
        }

        return $urls;
    }
}

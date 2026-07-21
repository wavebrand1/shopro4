<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http;

use App\Cms\Domain\Entity\Page;
use App\Cms\Infrastructure\Persistence\Doctrine\PageRepository;
use App\Settings\Application\SettingsProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class SeoController extends AbstractController
{
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

        $response = $this->render('cms/seo/sitemap.xml.twig', ['entries' => $entries]);
        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');
        $response->setPublic();
        $response->setMaxAge(900);

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
        $response->setPublic();
        $response->setMaxAge(900);

        return $response;
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

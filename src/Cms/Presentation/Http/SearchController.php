<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http;

use App\Cms\Application\SystemPageRenderer;
use App\Cms\Domain\Entity\Page;
use App\Cms\Domain\Entity\PageTranslation;
use App\Cms\Infrastructure\Persistence\Doctrine\PageRepository;
use App\Language\Domain\Entity\Language;
use App\Settings\Application\SettingsProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[\App\Module\Application\RequiresModule('cms')]
final class SearchController extends AbstractController
{
    #[Route('/search', name: 'cms_search', methods: ['GET'], priority: 90)]
    public function search(Request $request, PageRepository $pages, SettingsProvider $settings, SystemPageRenderer $systemPages): Response
    {
        return $this->renderResults($request, $pages, $settings, $systemPages, null);
    }

    #[Route('/{_locale}/search', name: 'cms_search_localized', requirements: ['_locale' => '[a-z]{2}'], methods: ['GET'], priority: 90)]
    public function localized(string $_locale, Request $request, PageRepository $pages, SettingsProvider $settings, EntityManagerInterface $em, SystemPageRenderer $systemPages): Response
    {
        $language = $em->getRepository(Language::class)->findOneBy(['code' => $_locale, 'active' => true]);
        if (!$language) throw $this->createNotFoundException();
        if ($language->isDefaultLanguage()) return $this->redirectToRoute('cms_search', $request->query->all());

        return $this->renderResults($request, $pages, $settings, $systemPages, $language);
    }

    private function renderResults(Request $request, PageRepository $pages, SettingsProvider $settings, SystemPageRenderer $systemPages, ?Language $language): Response
    {
        $query = trim(mb_substr((string) $request->query->get('q', ''), 0, 120));
        $perPage = min(50, max(5, (int) $settings->get('per_page', 10)));
        $page = max(1, $request->query->getInt('page', 1));
        $searchable = mb_strlen($query) >= 2;
        $total = 0;
        $items = [];

        if ($searchable) {
            $total = $language ? $pages->countPublicTranslationSearch($language, $query) : $pages->countPublicSearch($query);
            $lastPage = max(1, (int) ceil($total / $perPage));
            $page = min($page, $lastPage);
            $matches = $language
                ? $pages->searchPublicTranslations($language, $query, $perPage, ($page - 1) * $perPage)
                : $pages->searchPublic($query, $perPage, ($page - 1) * $perPage);
            foreach ($matches as $match) $items[] = $this->result($match, $language, $query);
        }

        $context = [
            'query' => $query, 'searchable' => $searchable, 'results' => $items, 'total' => $total,
            'current_page' => $page, 'last_page' => max(1, (int) ceil($total / $perPage)), 'language' => $language,
        ];
        $response = $systemPages->render(['searchPage' => true], 'cms/search/_content.html.twig', $context)
            ?? $this->render('cms/search/index.html.twig', $context);
        $response->headers->set('X-Robots-Tag', 'noindex, follow');

        return $response;
    }

    /** @return array{title:string, caption:string, snippet:string, url:string} */
    private function result(Page|PageTranslation $match, ?Language $language, string $query): array
    {
        $text = trim(strip_tags($match->getCaption().' '.$match->getDescription().' '.$match->getContent().' '.$this->builderText($match->getBuilderData())));
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';
        $position = mb_stripos($text, $query);
        $start = $position === false ? 0 : max(0, $position - 70);
        $snippet = mb_substr($text, $start, 220);
        if ($start > 0) $snippet = '…'.$snippet;
        if ($start + 220 < mb_strlen($text)) $snippet .= '…';

        return [
            'title' => $match->getTitle(), 'caption' => $match->getCaption(), 'snippet' => $snippet,
            'url' => $language && $match instanceof PageTranslation
                ? $this->generateUrl('cms_page_show_localized', ['_locale' => $language->getCode(), 'slug' => $match->getSlug()])
                : ($match instanceof Page && $match->isHomePage() ? $this->generateUrl('app_home') : $this->generateUrl('cms_page_show', ['slug' => $match->getSlug()])),
        ];
    }

    private function builderText(string $json): string
    {
        try { $value = json_decode($json, true, 64, JSON_THROW_ON_ERROR); } catch (\JsonException) { return ''; }
        $parts = [];
        $walk = static function (mixed $item) use (&$walk, &$parts): void {
            if (is_string($item)) $parts[] = $item;
            elseif (is_array($item)) foreach ($item as $value) $walk($value);
        };
        $walk($value);

        return implode(' ', $parts);
    }
}

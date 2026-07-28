<?php

declare(strict_types=1);

namespace App\Cms\Application;

use App\Cms\Domain\Entity\Page;
use App\Cms\Domain\Entity\PageTranslation;
use App\Cms\Domain\SystemRoleComponent;
use App\Cms\Infrastructure\Persistence\Doctrine\PageRepository;
use App\Language\Domain\Entity\Language;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

final class SystemPageRenderer
{
    public function __construct(
        private readonly PageRepository $pages,
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requests,
        private readonly Environment $twig,
    ) {
    }

    /**
     * @param array<string, mixed> $roleCriteria
     * @param array<string, mixed> $contentContext
     */
    public function render(
        array $roleCriteria,
        string $contentTemplate,
        array $contentContext = [],
        int $status = Response::HTTP_OK,
    ): ?Response {
        $page = $this->pages->findOneBy($roleCriteria + ['deletedAt' => null]);
        if (!$page instanceof Page || SystemRoleComponent::count($page->getBuilderData()) !== 1) {
            return null;
        }

        $displayPage = $this->translatedPage($page);
        if (SystemRoleComponent::count($displayPage->getBuilderData()) !== 1) {
            $displayPage = $page;
        }

        return new Response($this->twig->render('cms/page/show.html.twig', [
            'page' => $displayPage,
            'source_page' => $page,
            'alternates' => $this->pages->findPublishedActiveTranslations($page),
            'system_role_content' => $this->twig->render($contentTemplate, $contentContext),
        ]), $status);
    }

    private function translatedPage(Page $page): Page|PageTranslation
    {
        $language = $this->requests->getCurrentRequest()?->attributes->get('_shopro_language');
        if (!$language instanceof Language || $language->isDefaultLanguage()) {
            return $page;
        }

        $translation = $this->entityManager->getRepository(PageTranslation::class)->findOneBy([
            'page' => $page,
            'language' => $language,
            'published' => true,
        ]);

        return $translation instanceof PageTranslation ? $translation : $page;
    }
}

<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http;

use App\Cms\Domain\Entity\PageTranslation;
use App\Cms\Infrastructure\Persistence\Doctrine\PageRepository;
use App\Language\Domain\Entity\Language;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PublicPageController extends AbstractController
{
    #[Route('/{_locale}/{slug}', name: 'cms_page_show_localized', requirements: ['_locale' => '[a-z]{2}', 'slug' => '[a-z0-9-]+'], methods: ['GET'], priority: -90)]
    public function localized(string $_locale, string $slug, EntityManagerInterface $em): Response
    {
        $language = $em->getRepository(Language::class)->findOneBy(['code' => $_locale, 'active' => true]);
        $translation = $language ? $em->getRepository(PageTranslation::class)->findOneBy(['language' => $language, 'slug' => $slug, 'published' => true]) : null;
        if (!$translation) throw $this->createNotFoundException('Tłumaczenie podstrony nie istnieje lub nie jest opublikowane.');
        $page = $translation->getPage();
        if ($page->isAdminOnly() && !$this->isGranted('ROLE_ADMIN')) throw $this->createNotFoundException('Podstrona nie istnieje.');
        if ('Public' !== $page->getAccess() && !$this->getUser()) return $this->redirectToRoute('admin_login');

        return $this->render('cms/page/show.html.twig', ['page' => $translation, 'source_page' => $page, 'alternates' => $em->getRepository(PageTranslation::class)->findBy(['page' => $page, 'published' => true])]);
    }

    #[Route('/{slug}', name: 'cms_page_show', requirements: ['slug' => '[a-z0-9-]+'], methods: ['GET'], priority: -100)]
    public function __invoke(string $slug, PageRepository $pages, EntityManagerInterface $em): Response
    {
        $page = $pages->findPublishedBySlug($slug);
        if ($page === null) {
            throw $this->createNotFoundException('Podstrona nie istnieje lub nie jest opublikowana.');
        }
        if ($page->isAdminOnly() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException('Podstrona nie istnieje.');
        }
        if ('Public' !== $page->getAccess() && !$this->getUser()) {
            return $this->redirectToRoute('admin_login');
        }

        return $this->render('cms/page/show.html.twig', ['page' => $page, 'source_page' => $page, 'alternates' => $em->getRepository(PageTranslation::class)->findBy(['page' => $page, 'published' => true])]);
    }
}

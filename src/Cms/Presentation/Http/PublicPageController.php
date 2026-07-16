<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http;

use App\Cms\Infrastructure\Persistence\Doctrine\PageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PublicPageController extends AbstractController
{
    #[Route('/{slug}', name: 'cms_page_show', requirements: ['slug' => '[a-z0-9-]+'], methods: ['GET'], priority: -100)]
    public function __invoke(string $slug, PageRepository $pages): Response
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

        return $this->render('cms/page/show.html.twig', ['page' => $page]);
    }
}

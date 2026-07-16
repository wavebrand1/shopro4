<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http;

use App\Cms\Infrastructure\Persistence\Doctrine\PageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PublicPageController extends AbstractController
{
    #[Route('/strona/{slug}', name: 'cms_page_show', requirements: ['slug' => '[a-z0-9-]+'], methods: ['GET'])]
    public function __invoke(string $slug, PageRepository $pages): Response
    {
        $page = $pages->findPublishedBySlug($slug);
        if ($page === null) {
            throw $this->createNotFoundException('Podstrona nie istnieje lub nie jest opublikowana.');
        }

        return $this->render('cms/page/show.html.twig', ['page' => $page]);
    }
}

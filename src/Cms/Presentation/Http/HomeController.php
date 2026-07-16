<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http;

use App\Cms\Infrastructure\Persistence\Doctrine\PageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function __invoke(PageRepository $pages): Response
    {
        if (null !== $page = $pages->findPublishedHomePage()) {
            return $this->render('cms/page/show.html.twig', ['page' => $page]);
        }
        return $this->render('cms/home.html.twig');
    }
}

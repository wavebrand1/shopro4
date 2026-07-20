<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Admin;

use App\Cms\Domain\Entity\MenuItem;
use App\Cms\Infrastructure\Persistence\Doctrine\PageRepository;
use App\Identity\Domain\Entity\AdminUser;
use App\Language\Domain\Entity\Language;
use App\Mail\Domain\Entity\EmailTemplate;
use App\Newsletter\Domain\Entity\NewsletterCampaign;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_EDITOR')]
final class DashboardController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard', methods: ['GET'])]
    public function __invoke(PageRepository $pages, EntityManagerInterface $entityManager): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'page_count' => $pages->count([]),
            'published_count' => $pages->count(['published' => true]),
            'menu_count' => $entityManager->getRepository(MenuItem::class)->count([]),
            'user_count' => $entityManager->getRepository(AdminUser::class)->count([]),
            'language_count' => $entityManager->getRepository(Language::class)->count(['active' => true]),
            'campaign_count' => $entityManager->getRepository(NewsletterCampaign::class)->count([]),
            'email_template_count' => $entityManager->getRepository(EmailTemplate::class)->count([]),
        ]);
    }
}

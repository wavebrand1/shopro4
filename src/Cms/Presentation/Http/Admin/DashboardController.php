<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Admin;

use App\Cms\Domain\Entity\MenuItem;
use App\Cms\Infrastructure\Persistence\Doctrine\PageRepository;
use App\Identity\Domain\Entity\AdminUser;
use App\Identity\Domain\Entity\SiteUser;
use App\Language\Domain\Entity\Language;
use App\Mail\Domain\Entity\EmailTemplate;
use App\Module\Application\ModuleAvailability;
use App\Newsletter\Domain\Entity\NewsletterCampaign;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_EDITOR')]
#[\App\Module\Application\RequiresModule('cms')]
final class DashboardController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard', methods: ['GET'])]
    public function __invoke(PageRepository $pages, EntityManagerInterface $entityManager, ModuleAvailability $modules): Response
    {
        $enabled = [];
        foreach (['cms', 'identity', 'language', 'media', 'newsletter', 'settings'] as $code) $enabled[$code] = $modules->isEnabled($code);

        return $this->render('admin/dashboard.html.twig', [
            'page_count' => $pages->count(['deletedAt' => null]),
            'published_count' => $pages->countPubliclyAvailable(),
            'menu_count' => $entityManager->getRepository(MenuItem::class)->count([]),
            'user_count' => $enabled['identity'] ? $entityManager->getRepository(AdminUser::class)->count([]) : null,
            'site_user_count' => $enabled['identity'] ? $entityManager->getRepository(SiteUser::class)->count([]) : null,
            'language_count' => $enabled['language'] ? $entityManager->getRepository(Language::class)->count(['active' => true]) : null,
            'campaign_count' => $enabled['newsletter'] ? $entityManager->getRepository(NewsletterCampaign::class)->count([]) : null,
            'email_template_count' => $enabled['settings'] ? $entityManager->getRepository(EmailTemplate::class)->count([]) : null,
            'enabled_modules' => $enabled,
        ]);
    }
}

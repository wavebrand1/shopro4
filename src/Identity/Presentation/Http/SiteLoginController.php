<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Http;

use App\Cms\Domain\Entity\Page;
use App\Cms\Infrastructure\Persistence\Doctrine\PageRepository;
use App\Identity\Domain\Entity\SiteUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[\App\Module\Application\RequiresModule('identity')]
final class SiteLoginController extends AbstractController
{
    #[Route('/login', name: 'site_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils, EntityManagerInterface $entityManager, PageRepository $pages): Response
    {
        $user = $this->getUser();
        if ($user instanceof SiteUser) {
            if ($user->getLastLoginAt() === null) { $user->recordLogin(); $entityManager->flush(); }
            return $this->redirectToRoute('app_home');
        }

        $context = [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ];
        $page = $pages->findOneBy(['loginPage' => true, 'deletedAt' => null]);
        if ($page instanceof Page && str_contains($page->getBuilderData(), '"type":"system_role"')) {
            return $this->render('cms/page/show.html.twig', [
                'page' => $page, 'source_page' => $page,
                'alternates' => $pages->findPublishedActiveTranslations($page),
                'system_role_content' => $this->renderView('cms/security/_login_content.html.twig', $context),
            ]);
        }

        return $this->render('cms/security/login.html.twig', $context);
    }

    #[Route('/logout', name: 'site_logout', methods: ['POST'])]
    public function logout(): never { throw new \LogicException('Logout is handled by the Symfony firewall.'); }
}

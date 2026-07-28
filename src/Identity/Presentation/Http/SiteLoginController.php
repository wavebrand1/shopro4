<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Http;

use App\Cms\Application\SystemPageRenderer;
use App\Identity\Domain\Entity\SiteUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[\App\Module\Application\RequiresModule('identity')]
final class SiteLoginController extends AbstractController
{
    public function __construct(private readonly SystemPageRenderer $systemPages) {}

    #[Route('/login', name: 'site_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils, EntityManagerInterface $entityManager): Response
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
        $systemPage = $this->systemPages->render(['loginPage' => true], 'cms/security/_login_content.html.twig', $context);
        if ($systemPage !== null) return $systemPage;

        return $this->render('cms/security/login.html.twig', $context);
    }

    #[Route('/logout', name: 'site_logout', methods: ['POST'])]
    public function logout(): never { throw new \LogicException('Logout is handled by the Symfony firewall.'); }
}

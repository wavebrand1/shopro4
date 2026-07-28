<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Http;

use App\Audit\Domain\Entity\AuditLog;
use App\Audit\Infrastructure\Persistence\Doctrine\AuditLogRepository;
use App\Cms\Application\SystemPageRenderer;
use App\Identity\Domain\Entity\SiteUser;
use App\Identity\Infrastructure\Persistence\Doctrine\SiteUserRepository;
use App\Identity\Presentation\Form\SitePasswordChangeType;
use App\Identity\Presentation\Form\SiteProfileType;
use App\Language\Application\SystemTranslator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/account')]
#[\App\Module\Application\RequiresModule('identity')]
final class SiteAccountController extends AbstractController
{
    #[Route('', name: 'site_account', methods: ['GET'])]
    public function index(Request $request, SystemPageRenderer $systemPages): Response
    {
        $user = $this->getUser();
        if (!$user instanceof SiteUser) return $this->requireLogin($request);
        $memberships = array_values(array_filter($user->getMemberships()->toArray(), static fn ($membership): bool => $membership->isActive()));
        $context = ['site_user' => $user, 'memberships' => $memberships];
        return $systemPages->render(
            ['accountPage' => true],
            'cms/account/_index_content.html.twig',
            $context,
        ) ?? $this->render('cms/account/index.html.twig', $context);
    }

    #[Route('/profile', name: 'site_account_profile', methods: ['GET', 'POST'])]
    public function profile(Request $request, SiteUserRepository $users, AuditLogRepository $logs, SystemTranslator $translator, SystemPageRenderer $systemPages): Response
    {
        $user = $this->getUser();
        if (!$user instanceof SiteUser) return $this->requireLogin($request);
        $form = $this->createForm(SiteProfileType::class, $user); $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $users->save($user);
            $logs->save(new AuditLog('user', 'site_profile_updated', 'Użytkownik witryny zaktualizował dane konta.', $user->getUsername(), $request->getClientIp(), [], true));
            $this->addFlash('success', $translator->translate('site_account.profile_saved'));
            return $this->redirectToRoute('site_account_profile');
        }
        $context = ['form' => $form->createView()];
        return $systemPages->render(
            ['profilePage' => true],
            'cms/account/_profile_content.html.twig',
            $context,
        ) ?? $this->render('cms/account/profile.html.twig', $context);
    }

    #[Route('/password', name: 'site_account_password', methods: ['GET', 'POST'])]
    public function password(Request $request, SiteUserRepository $users, UserPasswordHasherInterface $hasher, AuditLogRepository $logs, SystemTranslator $translator): Response
    {
        $user = $this->siteUser(); $form = $this->createForm(SitePasswordChangeType::class); $form->handleRequest($request); $error = null;
        if ($form->isSubmitted() && $form->isValid()) {
            if (!$hasher->isPasswordValid($user, (string) $form->get('currentPassword')->getData())) {
                $error = 'site_account.current_password_invalid';
            } else {
                $user->setPassword($hasher->hashPassword($user, (string) $form->get('newPassword')->getData()));
                $users->save($user);
                $logs->save(new AuditLog('user', 'site_password_changed', 'Użytkownik witryny zmienił hasło w swoim koncie.', $user->getUsername(), $request->getClientIp(), [], true));
                $this->addFlash('success', $translator->translate('site_account.password_saved'));
                return $this->redirectToRoute('site_account_password');
            }
        }
        return $this->render('cms/account/password.html.twig', ['form' => $form, 'error' => $error]);
    }

    private function siteUser(): SiteUser
    {
        $user = $this->getUser();
        if (!$user instanceof SiteUser) throw $this->createAccessDeniedException();
        return $user;
    }

    private function requireLogin(Request $request): Response
    {
        $request->getSession()->set('_security.frontend.target_path', $request->getUri());
        return $this->redirectToRoute('site_login');
    }
}

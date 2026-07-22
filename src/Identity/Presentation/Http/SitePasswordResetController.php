<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Http;

use App\Audit\Domain\Entity\AuditLog;
use App\Audit\Infrastructure\Persistence\Doctrine\AuditLogRepository;
use App\Identity\Application\SitePasswordResetMailer;
use App\Identity\Application\SitePasswordResetManager;
use App\Identity\Domain\Entity\SiteUser;
use App\Identity\Infrastructure\Persistence\Doctrine\SiteUserRepository;
use App\Language\Application\SystemTranslator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[\App\Module\Application\RequiresModule('identity')]
final class SitePasswordResetController extends AbstractController
{
    #[Route('/password/forgot', name: 'site_password_forgot', methods: ['GET', 'POST'])]
    public function forgot(Request $request, SiteUserRepository $users, SitePasswordResetManager $resets, SitePasswordResetMailer $mailer, AuditLogRepository $logs): Response
    {
        $sent = false;
        if ($request->isMethod('POST') && $this->isCsrfTokenValid('site-password-forgot', (string) $request->request->get('_token'))) {
            $user = $users->loadUserByIdentifier(trim((string) $request->request->get('identifier')));
            if ($user instanceof SiteUser) {
                try {
                    $rawToken = $resets->create($user);
                    $mailer->send($user, $this->generateUrl('site_password_reset', ['token' => $rawToken], UrlGeneratorInterface::ABSOLUTE_URL));
                    $logs->save(new AuditLog('user', 'site_password_reset_requested', 'Wysłano użytkownikowi witryny link do zmiany hasła.', $user->getUsername(), $request->getClientIp()));
                } catch (\Throwable) {
                    // Odpowiedź nie ujawnia istnienia konta ani stanu konfiguracji poczty.
                }
            }
            $sent = true;
        }
        return $this->render('cms/security/forgot_password.html.twig', ['sent' => $sent]);
    }

    #[Route('/password/reset/{token}', name: 'site_password_reset', requirements: ['token' => '[a-f0-9]{64}'], methods: ['GET', 'POST'])]
    public function reset(string $token, Request $request, SitePasswordResetManager $resets, SiteUserRepository $users, UserPasswordHasherInterface $hasher, AuditLogRepository $logs, SystemTranslator $translator): Response
    {
        $resetToken = $resets->findUsable($token);
        if ($resetToken === null) return $this->render('cms/security/reset_password.html.twig', ['invalid' => true, 'token' => $token], new Response(status: Response::HTTP_GONE));
        $error = null;
        if ($request->isMethod('POST') && $this->isCsrfTokenValid('site-password-reset-'.$token, (string) $request->request->get('_token'))) {
            $password = (string) $request->request->get('password');
            if (mb_strlen($password) < 12) $error = 'auth.reset_password_length';
            elseif (!hash_equals($password, (string) $request->request->get('password_confirmation'))) $error = 'auth.reset_password_mismatch';
            else {
                $user = $resetToken->getUser();
                $user->setPassword($hasher->hashPassword($user, $password));
                $users->save($user); $resets->consume($resetToken);
                $logs->save(new AuditLog('user', 'site_password_reset_completed', 'Użytkownik witryny zmienił hasło przez link odzyskiwania.', $user->getUsername(), $request->getClientIp(), [], true));
                $this->addFlash('success', $translator->translate('auth.reset_password_success'));
                return $this->redirectToRoute('site_login');
            }
        }
        return $this->render('cms/security/reset_password.html.twig', ['invalid' => false, 'token' => $token, 'error' => $error]);
    }
}

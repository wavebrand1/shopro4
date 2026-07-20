<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Http;

use App\Audit\Domain\Entity\AuditLog;
use App\Audit\Infrastructure\Persistence\Doctrine\AuditLogRepository;
use App\Identity\Application\PasswordResetMailer;
use App\Identity\Application\PasswordResetManager;
use App\Identity\Domain\Entity\AdminUser;
use App\Identity\Infrastructure\Persistence\Doctrine\AdminUserRepository;
use App\Language\Application\SystemTranslator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class PasswordResetController extends AbstractController
{
    #[Route('/admin/password/forgot', name: 'admin_password_forgot', methods: ['GET', 'POST'])]
    public function forgot(Request $request, AdminUserRepository $users, PasswordResetManager $resets, PasswordResetMailer $mailer, AuditLogRepository $logs): Response
    {
        $sent = false;
        if ($request->isMethod('POST') && $this->isCsrfTokenValid('password-forgot', (string) $request->request->get('_token'))) {
            $identifier = trim((string) $request->request->get('identifier'));
            $user = $users->loadUserByIdentifier($identifier);
            if ($user instanceof AdminUser) {
                try {
                    $rawToken = $resets->create($user);
                    $mailer->send($user, $this->generateUrl('admin_password_reset', ['token' => $rawToken], UrlGeneratorInterface::ABSOLUTE_URL));
                    $logs->save(new AuditLog('user', 'password_reset_requested', 'Wysłano link do zmiany hasła.', $user->getUsername(), $request->getClientIp()));
                } catch (\Throwable) {
                    // Odpowiedź pozostaje jednakowa i nie ujawnia stanu konta ani poczty.
                }
            }
            $sent = true;
        }
        return $this->render('admin/security/forgot_password.html.twig', ['sent' => $sent]);
    }

    #[Route('/admin/password/reset/{token}', name: 'admin_password_reset', requirements: ['token' => '[a-f0-9]{64}'], methods: ['GET', 'POST'])]
    public function reset(string $token, Request $request, PasswordResetManager $resets, AdminUserRepository $users, UserPasswordHasherInterface $hasher, AuditLogRepository $logs, SystemTranslator $translator): Response
    {
        $resetToken = $resets->findUsable($token);
        if ($resetToken === null) return $this->render('admin/security/reset_password.html.twig', ['invalid' => true, 'token' => $token], new Response(status: 410));

        $error = null;
        if ($request->isMethod('POST') && $this->isCsrfTokenValid('password-reset-'.$token, (string) $request->request->get('_token'))) {
            $password = (string) $request->request->get('password');
            if (mb_strlen($password) < 12) $error = 'auth.reset_password_length';
            elseif (!hash_equals($password, (string) $request->request->get('password_confirmation'))) $error = 'auth.reset_password_mismatch';
            else {
                $user = $resetToken->getUser();
                $user->setPassword($hasher->hashPassword($user, $password));
                $users->save($user);
                $resets->consume($resetToken);
                $logs->save(new AuditLog('user', 'password_reset_completed', 'Hasło zostało zmienione przez link odzyskiwania.', $user->getUsername(), $request->getClientIp(), [], true));
                $this->addFlash('success', $translator->translate('auth.reset_password_success'));
                return $this->redirectToRoute('admin_login');
            }
        }
        return $this->render('admin/security/reset_password.html.twig', ['invalid' => false, 'token' => $token, 'error' => $error]);
    }
}

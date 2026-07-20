<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Http;

use App\Identity\Application\SiteRegistrationMailer;
use App\Identity\Domain\Entity\SiteUser;
use App\Identity\Infrastructure\Persistence\Doctrine\SiteUserRepository;
use App\Identity\Presentation\Form\SiteRegistrationType;
use App\Language\Application\SystemTranslator;
use App\Settings\Application\SettingsProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final class SiteRegistrationController extends AbstractController
{
    public function __construct(private readonly SystemTranslator $translator, private readonly RateLimiterFactory $activationResendLimiter) {}
    #[Route('/register', name: 'site_register', methods: ['GET', 'POST'])]
    public function register(Request $request, SettingsProvider $settings, SiteUserRepository $users, UserPasswordHasherInterface $hasher, SiteRegistrationMailer $mailer): Response
    {
        if (!(bool) $settings->get('registration_allowed', false)) {
            return $this->render('cms/security/registration_unavailable.html.twig', [
                'message' => 'site_registration.disabled',
            ], new Response(status: Response::HTTP_NOT_FOUND));
        }
        $limit = max(0, (int) $settings->get('user_limit', 0));
        if ($limit > 0 && $users->count([]) >= $limit) {
            return $this->render('cms/security/registration_unavailable.html.twig', [
                'message' => 'site_registration.limit_reached',
            ]);
        }
        $user = new SiteUser('', ''); $form = $this->createForm(SiteRegistrationType::class, $user); $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($hasher->hashPassword($user, (string) $form->get('plainPassword')->getData()));
            $verify = (bool) $settings->get('registration_verify', true) && !(bool) $settings->get('registration_auto_verify', false);
            $token = $verify ? $user->issueActivationToken() : null;
            $user->setActive(!$verify); $users->save($user);
            $emailSent = true;
            if ($token !== null) {
                try {
                    $mailer->sendActivation($user, $this->generateUrl('site_activate', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL));
                } catch (\Throwable) {
                    $emailSent = false;
                }
            } else {
                try { $mailer->sendWelcome($user, $this->generateUrl('site_login', [], UrlGeneratorInterface::ABSOLUTE_URL)); } catch (\Throwable) {}
            }
            if ((bool) $settings->get('notify_admin', true)) {
                try { $mailer->notifyAdministrator($user, $request->getClientIp() ?? '', $this->generateUrl('admin_site_user_edit', ['id' => $user->getId()], UrlGeneratorInterface::ABSOLUTE_URL)); } catch (\Throwable) {}
            }
            $this->addFlash(
                $emailSent ? 'success' : 'error',
                $this->translator->translate(!$emailSent ? 'site_registration.email_failed' : ($verify ? 'site_registration.check_email' : 'site_registration.ready')),
            );
            return $this->redirectToRoute('site_login');
        }
        return $this->render('cms/security/register.html.twig', ['form' => $form]);
    }

    #[Route('/activate/{token}', name: 'site_activate', requirements: ['token' => '[a-f0-9]{64}'], methods: ['GET'])]
    public function activate(string $token, EntityManagerInterface $entityManager, SiteRegistrationMailer $mailer): Response
    {
        $user = $entityManager->getRepository(SiteUser::class)->findOneBy(['activationTokenHash' => hash('sha256', $token)]);
        if (!$user || !$user->activateWithToken($token)) {
            return $this->render('cms/security/activation_invalid.html.twig', [], new Response(status: Response::HTTP_GONE));
        }
        $entityManager->flush();
        try { $mailer->sendWelcome($user, $this->generateUrl('site_login', [], UrlGeneratorInterface::ABSOLUTE_URL)); } catch (\Throwable) {}
        $this->addFlash('success', $this->translator->translate('site_registration.activated'));
        return $this->redirectToRoute('site_login');
    }

    #[Route('/activation/resend', name: 'site_activation_resend', methods: ['GET', 'POST'])]
    public function resend(Request $request, SiteUserRepository $users, SiteRegistrationMailer $mailer): Response
    {
        $sent = false;
        if ($request->isMethod('POST') && $this->isCsrfTokenValid('site-activation-resend', (string) $request->request->get('_token'))) {
            $identifier = mb_strtolower(trim((string) $request->request->get('identifier')));
            $rateLimit = $this->activationResendLimiter->create(hash('sha256', ($request->getClientIp() ?? '').'|'.$identifier))->consume();
            $user = $rateLimit->isAccepted() ? $users->findByIdentifierIncludingInactive($identifier) : null;
            if ($user instanceof SiteUser && !$user->isActive()) {
                try {
                    $token = $user->issueActivationToken();
                    $mailer->sendActivation($user, $this->generateUrl('site_activate', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL));
                    $users->save($user);
                } catch (\Throwable) {
                    // Neutralna odpowiedź nie ujawnia istnienia ani stanu konta.
                }
            }
            $sent = true;
        }
        return $this->render('cms/security/resend_activation.html.twig', ['sent' => $sent]);
    }
}

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

final class SiteRegistrationController extends AbstractController
{
    public function __construct(private readonly SystemTranslator $translator) {}
    #[Route('/register', name: 'site_register', methods: ['GET', 'POST'])]
    public function register(Request $request, SettingsProvider $settings, SiteUserRepository $users, UserPasswordHasherInterface $hasher, SiteRegistrationMailer $mailer): Response
    {
        if (!(bool) $settings->get('registration_allowed', false)) throw $this->createNotFoundException();
        $limit = max(0, (int) $settings->get('user_limit', 0));
        if ($limit > 0 && $users->count([]) >= $limit) return $this->render('cms/security/registration_unavailable.html.twig');
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
    public function activate(string $token, EntityManagerInterface $entityManager): Response
    {
        $user = $entityManager->getRepository(SiteUser::class)->findOneBy(['activationTokenHash' => hash('sha256', $token)]);
        if (!$user || !$user->activateWithToken($token)) throw $this->createNotFoundException($this->translator->translate('site_registration.activation_invalid'));
        $entityManager->flush(); $this->addFlash('success', $this->translator->translate('site_registration.activated'));
        return $this->redirectToRoute('site_login');
    }
}

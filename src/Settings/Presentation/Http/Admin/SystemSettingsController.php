<?php

declare(strict_types=1);

namespace App\Settings\Presentation\Http\Admin;

use App\Settings\Infrastructure\Persistence\Doctrine\SystemSettingsRepository;
use App\Settings\Presentation\Form\SystemSettingsType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/configuration/system', name: 'admin_system_settings_')]
#[IsGranted('ROLE_ADMIN')]
final class SystemSettingsController extends AbstractController
{
    #[Route('', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, SystemSettingsRepository $repository): Response
    {
        $settings = $repository->get();
        $storageAvailable = $repository->isStorageAvailable();
        $data = array_replace($this->defaults($request), $settings->getConfiguration());
        $form = $this->createForm(SystemSettingsType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && !$storageAvailable) {
            $this->addFlash('error', 'Konfiguracja nie może zostać zapisana, ponieważ migracja bazy danych nie została jeszcze wykonana. Uruchom ponownie wdrożenie.');
            return $this->redirectToRoute('admin_system_settings_edit');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array<string, mixed> $configuration */
            $configuration = $form->getData();
            unset($configuration['smtp_password']);
            $settings->setConfiguration($configuration);
            $settings->setSmtpPassword($form->get('smtp_password')->getData());
            $repository->save($settings);
            $this->addFlash('success', 'Konfiguracja systemu została zapisana.');
            return $this->redirectToRoute('admin_system_settings_edit');
        }

        return $this->render('admin/settings/system.html.twig', ['form' => $form, 'settings' => $settings, 'storage_available' => $storageAvailable]);
    }

    /** @return array<string, mixed> */
    private function defaults(Request $request): array
    {
        return [
            'site_name' => 'Shopro 4.0', 'company' => '', 'site_url' => $request->getSchemeAndHttpHost(), 'site_email' => '', 'theme' => 'default',
            'locale' => 'pl_PL', 'timezone' => 'Europe/Warsaw', 'language' => 'pl', 'date_short' => 'Y-m-d', 'date_long' => 'd F Y', 'time_format' => 'H:i', 'week_start' => 1,
            'show_login' => true, 'show_search' => true, 'show_breadcrumbs' => true, 'show_language' => false, 'eu_cookie' => true,
            'maintenance' => false, 'maintenance_from' => '', 'maintenance_message' => '',
            'image_width' => 1600, 'image_height' => 1200, 'thumbnail_width' => 400, 'thumbnail_height' => 300, 'avatar_width' => 256, 'avatar_height' => 256, 'image_quality' => 85,
            'per_page' => 20, 'currency' => 'PLN', 'currency_symbol' => 'zł',
            'registration_allowed' => false, 'registration_verify' => true, 'registration_auto_verify' => false, 'notify_admin' => true, 'user_limit' => 0, 'login_attempts' => 5, 'flood_seconds' => 30, 'logging' => true,
            'facebook' => '', 'instagram' => '', 'twitter' => '', 'pinterest' => '', 'meta_keywords' => '', 'meta_description' => '', 'analytics' => '',
            'mailer' => 'PHP', 'sendmail_path' => '/usr/sbin/sendmail', 'smtp_host' => '', 'smtp_user' => '', 'smtp_port' => 587, 'smtp_encryption' => 'tls',
        ];
    }
}

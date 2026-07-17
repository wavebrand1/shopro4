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
        $data['date_short'] = ['Y-m-d' => '%e-%m-%Y', 'd-m-Y' => '%e-%m-%Y', 'd.m.Y' => '%e-%m-%Y', 'd/m/Y' => '%e-%m-%Y'][$data['date_short']] ?? $data['date_short'];
        $data['date_long'] = ['d F Y' => '%d %B, %Y', 'F d, Y' => '%B %d, %Y', 'l, d F Y' => '%A %d %B %Y'][$data['date_long']] ?? $data['date_long'];
        $data['time_format'] = ['H:i' => '%H:%M', 'h:i a' => '%I:%M %P'][$data['time_format']] ?? $data['time_format'];
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
            'site_name' => 'Shopro 4.0', 'company' => '', 'site_url' => $request->getSchemeAndHttpHost(), 'site_dir' => '', 'site_email' => '',
            'theme' => 'modernize', 'theme_variant' => 'default', 'admin_theme' => 'modernize', 'admin_theme_variant' => 'default',
            'locale' => 'pl_PL', 'timezone' => 'Europe/Warsaw', 'language' => 'pl', 'date_short' => '%e-%m-%Y', 'date_long' => '%d %B %Y %I:%M %p', 'time_format' => '%H:%M', 'week_start' => 1,
            'show_login' => true, 'show_search' => true, 'show_breadcrumbs' => true, 'show_language' => false, 'eu_cookie' => true,
            'maintenance' => false, 'maintenance_date' => '', 'maintenance_time' => '', 'maintenance_message' => '',
            'image_width' => 1600, 'image_height' => 1200, 'thumbnail_width' => 400, 'thumbnail_height' => 300,
            'small_image_width' => 640, 'small_image_height' => 480, 'medium_image_width' => 1024, 'medium_image_height' => 768,
            'avatar_width' => 256, 'avatar_height' => 256, 'image_quality' => 85,
            'per_page' => 20, 'currency' => 'PLN', 'currency_symbol' => 'zł', 'thousands_separator' => ' ', 'decimal_separator' => ',',
            'registration_allowed' => false, 'registration_verify' => true, 'registration_auto_verify' => false, 'notify_admin' => true,
            'user_limit' => 0, 'login_attempts' => 5, 'flood_seconds' => 30, 'logging' => true, 'alert_email_template' => 0,
            'facebook' => '', 'instagram' => '', 'twitter' => '', 'pinterest' => '', 'linkedin' => '', 'youtube' => '', 'tiktok' => '',
            'meta_keywords' => '', 'meta_description' => '', 'analytics' => '', 'tenor_api_key' => '', 'api_auth_module' => 'none',
            'mailer' => 'PHP', 'sendmail_path' => '/usr/sbin/sendmail', 'smtp_host' => '', 'smtp_user' => '', 'smtp_port' => 587, 'smtp_ssl' => false,
        ];
    }
}

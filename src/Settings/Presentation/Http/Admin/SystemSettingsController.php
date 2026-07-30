<?php

declare(strict_types=1);

namespace App\Settings\Presentation\Http\Admin;

use App\Settings\Application\SettingsProvider;
use App\Settings\Application\BrandingAssetManager;
use App\Settings\Application\SensitiveDataCipher;
use App\Settings\Infrastructure\Persistence\Doctrine\SystemSettingsRepository;
use App\Settings\Presentation\Form\SystemSettingsType;
use App\Language\Application\SystemTranslator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormError;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/configuration/system', name: 'admin_system_settings_')]
#[IsGranted('ROLE_ADMIN')]
#[\App\Module\Application\RequiresModule('settings')]
final class SystemSettingsController extends AbstractController
{
    public function __construct(private readonly SystemTranslator $translator) {}
    #[Route('', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, SystemSettingsRepository $repository, SensitiveDataCipher $cipher, BrandingAssetManager $branding): Response
    {
        $settings = $repository->get();
        $storageAvailable = $repository->isStorageAvailable();
        $data = array_replace(SettingsProvider::defaults(), ['site_url' => $request->getSchemeAndHttpHost()], $settings->getConfiguration());
        $data['date_short'] = ['Y-m-d' => '%e-%m-%Y', 'd-m-Y' => '%e-%m-%Y', 'd.m.Y' => '%e-%m-%Y', 'd/m/Y' => '%e-%m-%Y'][$data['date_short']] ?? $data['date_short'];
        $data['date_long'] = ['d F Y' => '%d %B, %Y', 'F d, Y' => '%B %d, %Y', 'l, d F Y' => '%A %d %B %Y'][$data['date_long']] ?? $data['date_long'];
        $data['time_format'] = ['H:i' => '%H:%M', 'h:i a' => '%I:%M %P'][$data['time_format']] ?? $data['time_format'];
        $form = $this->createForm(SystemSettingsType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && !$storageAvailable) {
            $this->addFlash('error', $this->translator->translate('settings.save_storage_error'));
            return $this->redirectToRoute('admin_system_settings_edit');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array<string, mixed> $configuration */
            $configuration = array_replace($settings->getConfiguration(), $form->getData());
            unset($configuration['smtp_password'], $configuration['site_logo_file'], $configuration['favicon_file'], $configuration['social_image_file'], $configuration['remove_site_logo'], $configuration['remove_favicon'], $configuration['remove_social_image']);
            if ($form->get('remove_site_logo')->getData()) {
                $branding->remove($configuration['site_logo'] ?? null);
                $configuration['site_logo'] = '';
            }
            if ($form->get('remove_favicon')->getData()) {
                $branding->remove($configuration['favicon'] ?? null);
                $configuration['favicon'] = '';
            }
            if ($form->get('remove_social_image')->getData()) {
                $branding->remove($configuration['social_image'] ?? null);
                $configuration['social_image'] = '';
            }
            try {
                if ($file = $form->get('site_logo_file')->getData()) {
                    $previous = $configuration['site_logo'] ?? null;
                    $configuration['site_logo'] = $branding->store($file, 'logo');
                    $branding->remove($previous);
                }
                if ($file = $form->get('favicon_file')->getData()) {
                    $previous = $configuration['favicon'] ?? null;
                    $configuration['favicon'] = $branding->store($file, 'favicon');
                    $branding->remove($previous);
                }
                if ($file = $form->get('social_image_file')->getData()) {
                    $previous = $configuration['social_image'] ?? null;
                    $configuration['social_image'] = $branding->store($file, 'social');
                    $branding->remove($previous);
                }
            } catch (\InvalidArgumentException|\RuntimeException $exception) {
                $field = $form->get('site_logo_file')->getData() ? 'site_logo_file' : ($form->get('favicon_file')->getData() ? 'favicon_file' : 'social_image_file');
                $form->get($field)->addError(new FormError($exception->getMessage()));

                return $this->render('admin/settings/system.html.twig', ['form' => $form, 'settings' => $settings, 'storage_available' => $storageAvailable], new Response('', Response::HTTP_UNPROCESSABLE_ENTITY));
            }
            $settings->setConfiguration($configuration);
            if ($password = $form->get('smtp_password')->getData()) $settings->setSmtpPassword($cipher->encrypt($password));
            $repository->save($settings);
            $this->addFlash('success', $this->translator->translate('settings.saved'));
            return $this->redirectToRoute('admin_system_settings_edit');
        }

        return $this->render('admin/settings/system.html.twig', ['form' => $form, 'settings' => $settings, 'storage_available' => $storageAvailable]);
    }

}

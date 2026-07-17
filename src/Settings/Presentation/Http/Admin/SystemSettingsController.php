<?php

declare(strict_types=1);

namespace App\Settings\Presentation\Http\Admin;

use App\Settings\Application\SettingsProvider;
use App\Settings\Application\SensitiveDataCipher;
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
    public function edit(Request $request, SystemSettingsRepository $repository, SensitiveDataCipher $cipher): Response
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
            $this->addFlash('error', 'Konfiguracja nie może zostać zapisana, ponieważ migracja bazy danych nie została jeszcze wykonana. Uruchom ponownie wdrożenie.');
            return $this->redirectToRoute('admin_system_settings_edit');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array<string, mixed> $configuration */
            $configuration = array_replace($settings->getConfiguration(), $form->getData());
            unset($configuration['smtp_password']);
            $settings->setConfiguration($configuration);
            if ($password = $form->get('smtp_password')->getData()) $settings->setSmtpPassword($cipher->encrypt($password));
            $repository->save($settings);
            $this->addFlash('success', 'Konfiguracja systemu została zapisana.');
            return $this->redirectToRoute('admin_system_settings_edit');
        }

        return $this->render('admin/settings/system.html.twig', ['form' => $form, 'settings' => $settings, 'storage_available' => $storageAvailable]);
    }

}

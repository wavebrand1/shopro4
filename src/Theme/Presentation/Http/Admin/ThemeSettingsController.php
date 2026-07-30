<?php

declare(strict_types=1);

namespace App\Theme\Presentation\Http\Admin;

use App\Settings\Application\BrandingAssetManager;
use App\Settings\Infrastructure\Persistence\Doctrine\SystemSettingsRepository;
use App\Theme\Application\ThemeRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/configuration/theme', name: 'admin_theme_settings_')]
#[IsGranted('ROLE_ADMIN')]
final class ThemeSettingsController extends AbstractController
{
    #[Route('', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ThemeRegistry $themes, SystemSettingsRepository $repository, BrandingAssetManager $branding): Response
    {
        $settings = $repository->get();
        $configuration = $settings->getConfiguration();
        $code = (string) ($configuration['theme'] ?? 'modernize');
        $theme = $themes->get($code) ?? $themes->require('modernize');

        if ($theme->settings === []) {
            $this->addFlash('info', 'Wybrany szablon nie udostępnia dodatkowych ustawień.');
            return $this->redirectToRoute('admin_system_settings_edit');
        }

        $stored = (array) (($configuration['theme_settings'][$theme->code] ?? []));
        $data = [];
        $assetPreviews = [];
        foreach ($theme->settings as $key => $definition) {
            if ($definition['type'] === 'file') {
                $data[$key] = null;
                $assetPreviews[$key] = $stored[$definition['asset_key'] ?? $key] ?? null;
                continue;
            }
            if (isset($definition['remove_asset_key'])) {
                $data[$key] = false;
                continue;
            }
            $data[$key] = $stored[$key] ?? ($definition['default'] ?? ($definition['type'] === 'checkbox' ? false : ''));
        }

        $builder = $this->createFormBuilder($data, ['csrf_token_id' => 'theme-settings-'.$theme->code]);
        foreach ($theme->settings as $key => $definition) {
            $type = match ($definition['type']) { 'textarea' => TextareaType::class, 'checkbox' => CheckboxType::class, 'file' => FileType::class, default => TextType::class };
            $builder->add($key, $type, ['label' => $definition['label'], 'help' => $definition['help'] ?? null, 'required' => false]);
        }
        $form = $builder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $updated = $stored;
            $submitted = $form->getData();
            foreach ($theme->settings as $key => $definition) {
                if ($definition['type'] === 'file') {
                    /** @var UploadedFile|null $file */
                    $file = $submitted[$key] ?? null;
                    if ($file instanceof UploadedFile) {
                        $assetKey = $definition['asset_key'] ?? $key;
                        $branding->remove($updated[$assetKey] ?? null);
                        $updated[$assetKey] = $branding->store($file, 'logo');
                    }
                    continue;
                }
                if (isset($definition['remove_asset_key'])) {
                    if (($submitted[$key] ?? false) === true) {
                        $assetKey = $definition['remove_asset_key'];
                        $branding->remove($updated[$assetKey] ?? null);
                        unset($updated[$assetKey]);
                    }
                    continue;
                }
                $updated[$key] = $submitted[$key] ?? null;
            }
            $configuration['theme_settings'] ??= [];
            $configuration['theme_settings'][$theme->code] = $updated;
            $settings->setConfiguration($configuration);
            $repository->save($settings);
            $this->addFlash('success', 'Ustawienia szablonu zostały zapisane.');
            return $this->redirectToRoute('admin_theme_settings_edit');
        }

        return $this->render('admin/theme/settings.html.twig', ['form' => $form, 'theme' => $theme, 'asset_previews' => $assetPreviews]);
    }
}

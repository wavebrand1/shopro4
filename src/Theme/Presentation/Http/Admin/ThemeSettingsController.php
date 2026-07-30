<?php

declare(strict_types=1);

namespace App\Theme\Presentation\Http\Admin;

use App\Settings\Infrastructure\Persistence\Doctrine\SystemSettingsRepository;
use App\Theme\Application\ThemeRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/configuration/theme', name: 'admin_theme_settings_')]
#[IsGranted('ROLE_ADMIN')]
final class ThemeSettingsController extends AbstractController
{
    #[Route('', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ThemeRegistry $themes, SystemSettingsRepository $repository): Response
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
        foreach ($theme->settings as $key => $definition) $data[$key] = $stored[$key] ?? ($definition['default'] ?? ($definition['type'] === 'checkbox' ? false : ''));

        $builder = $this->createFormBuilder($data, ['csrf_token_id' => 'theme-settings-'.$theme->code]);
        foreach ($theme->settings as $key => $definition) {
            $type = match ($definition['type']) { 'textarea' => TextareaType::class, 'checkbox' => CheckboxType::class, default => TextType::class };
            $builder->add($key, $type, ['label' => $definition['label'], 'help' => $definition['help'] ?? null, 'required' => $definition['type'] !== 'checkbox']);
        }
        $form = $builder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $configuration['theme_settings'] ??= [];
            $configuration['theme_settings'][$theme->code] = $form->getData();
            $settings->setConfiguration($configuration);
            $repository->save($settings);
            $this->addFlash('success', 'Ustawienia szablonu zostały zapisane.');
            return $this->redirectToRoute('admin_theme_settings_edit');
        }

        return $this->render('admin/theme/settings.html.twig', ['form' => $form, 'theme' => $theme]);
    }
}

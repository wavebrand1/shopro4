<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Admin;

use App\Cms\Domain\Entity\MenuItem;
use App\Cms\Domain\Entity\MenuItemTranslation;
use App\Cms\Presentation\Form\MenuItemTranslationType;
use App\Language\Application\SystemTranslator;
use App\Language\Domain\Entity\Language;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/menu/{id}/translations')]
#[IsGranted('ROLE_EDITOR')]
#[\App\Module\Application\RequiresModule('cms')]
final class MenuTranslationController extends AbstractController
{
    public function __construct(private readonly SystemTranslator $translator)
    {
    }

    #[Route('', name: 'admin_menu_translation_index', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function index(MenuItem $item, EntityManagerInterface $entityManager): Response
    {
        $translations = [];
        foreach ($entityManager->getRepository(MenuItemTranslation::class)->findBy(['menuItem' => $item]) as $translation) {
            $translations[$translation->getLanguage()->getId()] = $translation;
        }

        return $this->render('admin/menu/translations.html.twig', [
            'item' => $item,
            'languages' => $entityManager->getRepository(Language::class)->findBy(['active' => true, 'defaultLanguage' => false], ['name' => 'ASC']),
            'translations' => $translations,
        ]);
    }

    #[Route('/{languageId}', name: 'admin_menu_translation_edit', requirements: ['id' => '\d+', 'languageId' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(MenuItem $item, int $languageId, Request $request, EntityManagerInterface $entityManager): Response
    {
        $language = $entityManager->find(Language::class, $languageId);
        if (!$language || $language->isDefaultLanguage()) {
            throw $this->createNotFoundException($this->translator->translate('page.language_version_missing'));
        }

        $translation = $entityManager->getRepository(MenuItemTranslation::class)->findOneBy([
            'menuItem' => $item,
            'language' => $language,
        ]) ?? new MenuItemTranslation($item, $language);
        $form = $this->createForm(MenuItemTranslationType::class, $translation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($translation);
            $entityManager->flush();
            $this->addFlash('success', $this->translator->translate('menu.translation_saved'));

            return $this->redirectToRoute('admin_menu_translation_index', ['id' => $item->getId()]);
        }

        return $this->render('admin/menu/translation_form.html.twig', [
            'item' => $item,
            'language' => $language,
            'form' => $form,
        ]);
    }
}

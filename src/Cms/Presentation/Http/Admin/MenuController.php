<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Admin;

use App\Cms\Domain\Entity\MenuItem;
use App\Cms\Infrastructure\Persistence\Doctrine\MenuItemRepository;
use App\Cms\Presentation\Form\MenuItemType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/menu')]
#[IsGranted('ROLE_ADMIN')]
final class MenuController extends AbstractController
{
    #[Route('', name: 'admin_menu_index', methods: ['GET'])]
    public function index(MenuItemRepository $items): Response
    {
        $allItems = $items->findAllForAdministration();
        $groups = [];
        foreach ($allItems as $item) {
            $key = $item->getPlace().'-'.($item->getParent()?->getId() ?? 0);
            $groups[$key] ??= [
                'key' => $key,
                'label' => ($item->getPlace() === MenuItem::PLACE_HEADER ? 'Menu górne' : 'Menu dolne').($item->getParent() ? ' / '.$item->getParent()->getName() : ' / poziom główny'),
                'items' => [],
            ];
            $groups[$key]['items'][] = $item;
        }
        return $this->render('admin/menu/index.html.twig', ['items' => $allItems, 'groups' => $groups]);
    }

    #[Route('/reorder', name: 'admin_menu_reorder', methods: ['POST'])]
    public function reorder(Request $request, MenuItemRepository $items): JsonResponse
    {
        if (!$this->isCsrfTokenValid('reorder-menu', (string) $request->request->get('_token'))) {
            return $this->json(['message' => 'Nieprawidłowy token bezpieczeństwa.'], Response::HTTP_FORBIDDEN);
        }
        $orderedIds = array_map('intval', $request->request->all('items'));
        try {
            $items->reorderSiblings($orderedIds);
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        return $this->json(['message' => 'Kolejność menu została zapisana.']);
    }

    #[Route('/new', name: 'admin_menu_new', methods: ['GET', 'POST'])]
    public function new(Request $request, MenuItemRepository $items): Response
    {
        return $this->handleForm($request, new MenuItem(), $items, 'Pozycja menu została utworzona.');
    }

    #[Route('/{id}/edit', name: 'admin_menu_edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function edit(MenuItem $item, Request $request, MenuItemRepository $items): Response
    {
        return $this->handleForm($request, $item, $items, 'Pozycja menu została zaktualizowana.');
    }

    #[Route('/{id}/delete', name: 'admin_menu_delete', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function delete(MenuItem $item, Request $request, MenuItemRepository $items): Response
    {
        if ($this->isCsrfTokenValid('delete-menu-'.$item->getId(), (string) $request->request->get('_token'))) {
            $items->remove($item);
            $this->addFlash('success', 'Pozycja menu została usunięta.');
        }

        return $this->redirectToRoute('admin_menu_index');
    }

    private function handleForm(Request $request, MenuItem $item, MenuItemRepository $items, string $message): Response
    {
        $wasNew = $item->getId() === null;
        $originalParentId = $item->getParent()?->getId();
        $originalPlace = $item->getPlace();
        $form = $this->createForm(MenuItemType::class, $item);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->createsCycle($item)) {
                $this->addFlash('error', 'Wybrany element nadrzędny tworzyłby pętlę w drzewie menu.');
            } else {
                if ($wasNew || $originalParentId !== $item->getParent()?->getId() || $originalPlace !== $item->getPlace()) {
                    $item->setPosition($items->nextPosition($item->getParent(), $item->getPlace()));
                }
                $items->save($item);
                $this->addFlash('success', $message);

                return $this->redirectToRoute('admin_menu_index');
            }
        }

        return $this->render('admin/menu/form.html.twig', ['form' => $form, 'item' => $item]);
    }

    private function createsCycle(MenuItem $item): bool
    {
        $parent = $item->getParent();
        $visited = [];
        while ($parent !== null) {
            if ($parent === $item || ($parent->getId() !== null && in_array($parent->getId(), $visited, true))) {
                return true;
            }
            if ($parent->getId() !== null) {
                $visited[] = $parent->getId();
            }
            $parent = $parent->getParent();
        }

        return false;
    }
}

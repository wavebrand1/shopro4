<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Admin;

use App\Cms\Domain\Entity\MenuItem;
use App\Cms\Infrastructure\Persistence\Doctrine\MenuItemRepository;
use App\Cms\Presentation\Form\MenuItemType;
use App\Language\Application\SystemTranslator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/menu')]
#[IsGranted('ROLE_EDITOR')]
final class MenuController extends AbstractController
{
    public function __construct(private readonly SystemTranslator $translator)
    {
    }

    #[Route('', name: 'admin_menu_index', methods: ['GET'])]
    public function index(MenuItemRepository $items): Response
    {
        $allItems = $items->findAllForAdministration();
        $children = [];
        foreach ($allItems as $item) {
            $children[$item->getPlace()][$item->getParent()?->getId() ?? 0][] = $item;
        }

        $trees = [];
        foreach ([MenuItem::PLACE_HEADER => $this->translator->translate('menu.header'), MenuItem::PLACE_FOOTER => $this->translator->translate('menu.footer')] as $place => $label) {
            $flatItems = [];
            $visited = [];
            $appendBranch = function (int $parentId, int $depth) use (&$appendBranch, &$flatItems, &$visited, $children, $place): void {
                foreach ($children[$place][$parentId] ?? [] as $item) {
                    $id = (int) $item->getId();
                    if (isset($visited[$id])) continue;
                    $visited[$id] = true;
                    $flatItems[] = ['item' => $item, 'depth' => $depth];
                    $appendBranch($id, $depth + 1);
                }
            };
            $appendBranch(0, 0);
            // Zachowaj widoczność historycznych, osieroconych rekordów zamiast zakładać, że można je usunąć.
            foreach ($allItems as $item) {
                if ($item->getPlace() === $place && !isset($visited[(int) $item->getId()])) {
                    $appendBranch((int) ($item->getParent()?->getId() ?? 0), 0);
                }
            }
            $trees[] = ['place' => $place, 'label' => $label, 'items' => $flatItems];
        }

        return $this->render('admin/menu/index.html.twig', ['items' => $allItems, 'trees' => $trees]);
    }

    #[Route('/reorder', name: 'admin_menu_reorder', methods: ['POST'])]
    public function reorder(Request $request, MenuItemRepository $items): JsonResponse
    {
        if (!$this->isCsrfTokenValid('reorder-menu', (string) $request->request->get('_token'))) {
            return $this->json(['message' => $this->translator->translate('menu.invalid_token')], Response::HTTP_FORBIDDEN);
        }
        $orderedIds = array_map('intval', $request->request->all('items'));
        try {
            $items->reorderSiblings($orderedIds);
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['message' => $this->translator->translate($exception->getMessage())], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        return $this->json(['message' => $this->translator->translate('menu.order_saved')]);
    }

    #[Route('/move', name: 'admin_menu_move', methods: ['POST'])]
    public function move(Request $request, MenuItemRepository $items): JsonResponse
    {
        if (!$this->isCsrfTokenValid('reorder-menu', (string) $request->request->get('_token'))) {
            return $this->json(['message' => $this->translator->translate('menu.invalid_token')], Response::HTTP_FORBIDDEN);
        }
        $item = $items->find($request->request->getInt('item'));
        $parentId = $request->request->getInt('parent');
        $parent = $parentId > 0 ? $items->find($parentId) : null;
        if (!$item || ($parentId > 0 && !$parent)) return $this->json(['message' => $this->translator->translate('menu.not_found')], Response::HTTP_NOT_FOUND);
        try {
            $items->move($item, $parent, $request->request->getInt('place'));
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['message' => $this->translator->translate($exception->getMessage())], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        return $this->json(['message' => $this->translator->translate('menu.hierarchy_saved')]);
    }

    #[Route('/new', name: 'admin_menu_new', methods: ['GET', 'POST'])]
    public function new(Request $request, MenuItemRepository $items): Response
    {
        return $this->handleForm($request, new MenuItem(), $items, 'menu.created');
    }

    #[Route('/{id}/edit', name: 'admin_menu_edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function edit(MenuItem $item, Request $request, MenuItemRepository $items): Response
    {
        return $this->handleForm($request, $item, $items, 'menu.updated');
    }

    #[Route('/{id}/delete', name: 'admin_menu_delete', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function delete(MenuItem $item, Request $request, MenuItemRepository $items): Response
    {
        if ($this->isCsrfTokenValid('delete-menu-'.$item->getId(), (string) $request->request->get('_token'))) {
            $items->remove($item);
            $this->addFlash('success', $this->translator->translate('menu.deleted'));
        }

        return $this->redirectToRoute('admin_menu_index');
    }

    private function handleForm(Request $request, MenuItem $item, MenuItemRepository $items, string $messageKey): Response
    {
        $wasNew = $item->getId() === null;
        $originalParentId = $item->getParent()?->getId();
        $originalPlace = $item->getPlace();
        $form = $this->createForm(MenuItemType::class, $item);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->createsCycle($item)) {
                $this->addFlash('error', $this->translator->translate('menu.cycle_error'));
            } else {
                if ($wasNew || $originalParentId !== $item->getParent()?->getId() || $originalPlace !== $item->getPlace()) {
                    $item->setPosition($items->nextPosition($item->getParent(), $item->getPlace()));
                }
                $items->save($item);
                $this->addFlash('success', $this->translator->translate($messageKey));

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

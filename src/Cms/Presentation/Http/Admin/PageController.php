<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Admin;

use App\Cms\Domain\Entity\Page;
use App\Cms\Infrastructure\Persistence\Doctrine\PageRepository;
use App\Cms\Presentation\Form\PageType;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/pages')]
#[IsGranted('ROLE_ADMIN')]
final class PageController extends AbstractController
{
    #[Route('', name: 'admin_page_index', methods: ['GET'])]
    public function index(PageRepository $pages): Response
    {
        return $this->render('admin/page/index.html.twig', ['pages' => $pages->findAllForAdministration()]);
    }

    #[Route('/new', name: 'admin_page_new', methods: ['GET', 'POST'])]
    public function new(Request $request, PageRepository $pages): Response
    {
        return $this->handleForm($request, new Page(), $pages, 'Podstrona została utworzona.');
    }

    #[Route('/{id}/edit', name: 'admin_page_edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function edit(Page $page, Request $request, PageRepository $pages): Response
    {
        return $this->handleForm($request, $page, $pages, 'Zmiany zostały zapisane.');
    }

    #[Route('/{id}/delete', name: 'admin_page_delete', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function delete(Page $page, Request $request, PageRepository $pages): Response
    {
        if ($page->isSystemPage()) {
            $this->addFlash('error', 'Strony systemowej nie można usunąć. Najpierw przypisz jej rolę innej podstronie.');
            return $this->redirectToRoute('admin_page_index');
        }
        if ($this->isCsrfTokenValid('delete-page-'.$page->getId(), (string) $request->request->get('_token'))) {
            $pages->remove($page);
            $this->addFlash('success', 'Podstrona została usunięta.');
        }

        return $this->redirectToRoute('admin_page_index');
    }

    #[Route('/{id}/duplicate', name: 'admin_page_duplicate', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function duplicate(Page $page, Request $request, PageRepository $pages): Response
    {
        if ($this->isCsrfTokenValid('duplicate-page-'.$page->getId(), (string) $request->request->get('_token'))) {
            $copy = $page->copyAs('kopia-'.date('YmdHis'));
            $pages->save($copy);
            $this->addFlash('success', 'Podstrona została zduplikowana.');
            return $this->redirectToRoute('admin_page_edit', ['id' => $copy->getId()]);
        }
        return $this->redirectToRoute('admin_page_index');
    }

    private function handleForm(Request $request, Page $page, PageRepository $pages, string $message): Response
    {
        $form = $this->createForm(PageType::class, $page);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $pages->save($page);
                $this->addFlash('success', $message);

                if ('stay' === $request->request->get('_save_action')) {
                    return $this->redirectToRoute('admin_page_edit', ['id' => $page->getId()]);
                }

                return $this->redirectToRoute('admin_page_index');
            } catch (UniqueConstraintViolationException) {
                $this->addFlash('error', 'Podstrona z takim slugiem już istnieje.');
            }
        }

        return $this->render('admin/page/form.html.twig', [
            'form' => $form,
            'page' => $page,
            'tinymce_api_key' => $this->getParameter('tinymce_api_key'),
        ]);
    }
}

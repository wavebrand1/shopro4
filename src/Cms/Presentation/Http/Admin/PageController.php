<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Admin;

use App\Cms\Domain\Entity\Page;
use App\Cms\Application\UrlRedirectManager;
use App\Cms\Application\PageRevisionManager;
use App\Identity\Domain\Entity\AdminUser;
use App\Cms\Infrastructure\Persistence\Doctrine\PageRepository;
use App\Cms\Infrastructure\Persistence\Doctrine\MenuItemRepository;
use App\Cms\Presentation\Form\PageType;
use App\Settings\Application\SettingsProvider;
use App\Language\Application\SystemTranslator;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormError;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/pages')]
#[IsGranted('ROLE_EDITOR')]
final class PageController extends AbstractController
{
    public function __construct(
        #[Autowire(service: 'html_sanitizer.sanitizer.app.page_content')]
        private readonly HtmlSanitizerInterface $htmlSanitizer,
        private readonly SystemTranslator $translator,
        private readonly UrlRedirectManager $redirectManager,
        private readonly EntityManagerInterface $entityManager,
        private readonly PageRevisionManager $revisionManager,
    ) {}

    #[Route('', name: 'admin_page_index', methods: ['GET'])]
    public function index(Request $request, PageRepository $pages, MenuItemRepository $menuItems, SettingsProvider $settings): Response
    {
        $page = max(1, $request->query->getInt('page', 1)); $limit = max(1, min(200, (int) $settings->get('per_page', 20)));
        $search = trim($request->query->getString('q'));
        $status = $request->query->getString('status');
        if (!in_array($status, ['', 'draft', 'scheduled', 'published', 'expired'], true)) $status = '';
        $sort = $request->query->getString('sort', 'updated');
        if (!in_array($sort, ['updated', 'title', 'created'], true)) $sort = 'updated';
        $direction = strtolower($request->query->getString('direction', 'desc'));
        if (!in_array($direction, ['asc', 'desc'], true)) $direction = 'desc';
        $sortFields = ['updated' => 'p.updatedAt', 'title' => 'p.title', 'created' => 'p.createdAt'];
        $query = $pages->createQueryBuilder('p')
            ->andWhere('p.deletedAt IS NULL')
            ->orderBy($sortFields[$sort], strtoupper($direction))
            ->addOrderBy('p.id', strtoupper($direction));
        if ($search !== '') {
            $query->andWhere('LOWER(p.title) LIKE :adminSearch OR LOWER(p.slug) LIKE :adminSearch')
                ->setParameter('adminSearch', '%'.mb_strtolower($search).'%');
        }
        $now = new \DateTimeImmutable();
        match ($status) {
            'draft' => $query->andWhere('p.published = false'),
            'scheduled' => $query->andWhere('p.published = true')->andWhere('p.publishAt > :adminNow')->setParameter('adminNow', $now),
            'published' => $query->andWhere('p.published = true')->andWhere('(p.publishAt IS NULL OR p.publishAt <= :adminNow)')->andWhere('(p.unpublishAt IS NULL OR p.unpublishAt > :adminNow)')->setParameter('adminNow', $now),
            'expired' => $query->andWhere('p.published = true')->andWhere('p.unpublishAt IS NOT NULL')->andWhere('p.unpublishAt <= :adminNow')->setParameter('adminNow', $now),
            default => null,
        };
        $total = (int) (clone $query)->select('COUNT(p.id)')->getQuery()->getSingleScalarResult();
        $listedPages = $query->setFirstResult(($page - 1) * $limit)->setMaxResults($limit)->getQuery()->getResult();
        $menuUsage = $menuItems->usageByPageIds(array_map(static fn (Page $listedPage): int => (int) $listedPage->getId(), $listedPages));
        return $this->render('admin/page/index.html.twig', ['pages' => $listedPages, 'menu_usage' => $menuUsage, 'current_page' => $page, 'last_page' => max(1, (int) ceil($total / $limit)), 'total' => $total, 'search' => $search, 'status_filter' => $status, 'sort' => $sort, 'direction' => $direction, 'query_params' => array_filter(['q' => $search, 'status' => $status, 'sort' => $sort === 'updated' ? '' : $sort, 'direction' => $direction === 'desc' ? '' : $direction], static fn (string $value): bool => $value !== '')]);
    }

    #[Route('/new', name: 'admin_page_new', methods: ['GET', 'POST'])]
    public function new(Request $request, PageRepository $pages): Response
    {
        return $this->handleForm($request, new Page(), $pages, 'page.created');
    }

    #[Route('/bulk', name: 'admin_page_bulk', methods: ['POST'])]
    public function bulk(Request $request, PageRepository $pages, MenuItemRepository $menuItems): Response
    {
        $returnStatus = $request->request->getString('return_status');
        if (!in_array($returnStatus, ['', 'draft', 'scheduled', 'published', 'expired'], true)) $returnStatus = '';
        $returnSort = $request->request->getString('return_sort', 'updated');
        if (!in_array($returnSort, ['updated', 'title', 'created'], true)) $returnSort = 'updated';
        $returnDirection = strtolower($request->request->getString('return_direction', 'desc'));
        if (!in_array($returnDirection, ['asc', 'desc'], true)) $returnDirection = 'desc';
        $redirectParameters = array_filter([
            'q' => trim($request->request->getString('return_q')),
            'status' => $returnStatus,
            'sort' => $returnSort === 'updated' ? '' : $returnSort,
            'direction' => $returnDirection === 'desc' ? '' : $returnDirection,
        ], static fn (string $value): bool => $value !== '');
        if (!$this->isCsrfTokenValid('bulk-pages', $request->request->getString('_token'))) {
            $this->addFlash('error', $this->translator->translate('page.bulk_invalid_token'));
            return $this->redirectToRoute('admin_page_index', $redirectParameters);
        }
        $action = $request->request->getString('bulk_action');
        if (!in_array($action, ['publish', 'draft', 'trash'], true)) {
            $this->addFlash('error', $this->translator->translate('page.bulk_select_action'));
            return $this->redirectToRoute('admin_page_index', $redirectParameters);
        }
        $ids = array_slice(array_values(array_unique(array_filter(array_map(static fn (mixed $id): int => max(0, (int) $id), $request->request->all('pages'))))), 0, 200);
        if ($ids === []) {
            $this->addFlash('error', $this->translator->translate('page.bulk_select_pages'));
            return $this->redirectToRoute('admin_page_index', $redirectParameters);
        }
        $user = $this->getUser();
        $changed = 0;
        $skipped = 0;
        $menuUsage = $action === 'trash' ? $menuItems->usageByPageIds($ids) : [];
        $this->entityManager->getConnection()->beginTransaction();
        try {
            foreach ($pages->findBy(['id' => $ids]) as $page) {
                if ($page->isDeleted()) continue;
                if ($action === 'trash') {
                    if ($page->isSystemPage() || ($menuUsage[$page->getId()] ?? 0) > 0) { ++$skipped; continue; }
                    $page->moveToTrash();
                    $pages->save($page);
                    ++$changed;
                    continue;
                }
                if ($action === 'publish') { $page->setPublished(true); $page->setPublishAt(null); $page->setUnpublishAt(null); }
                else { $page->setPublished(false); }
                $pages->save($page);
                $this->revisionManager->snapshot($page, $user instanceof AdminUser ? $user : null);
                ++$changed;
            }
            $this->entityManager->getConnection()->commit();
        } catch (\Throwable $exception) {
            if ($this->entityManager->getConnection()->isTransactionActive()) $this->entityManager->getConnection()->rollBack();
            throw $exception;
        }
        $message = match ($action) { 'publish' => 'page.bulk_published', 'draft' => 'page.bulk_drafted', default => 'page.bulk_trashed' };
        $this->addFlash('success', sprintf($this->translator->translate($message), $changed));
        if ($skipped > 0) $this->addFlash('error', sprintf($this->translator->translate('page.bulk_trash_skipped'), $skipped));
        return $this->redirectToRoute('admin_page_index', $redirectParameters);
    }

    #[Route('/preview', name: 'admin_page_preview', methods: ['POST'])]
    public function preview(Request $request): Response
    {
        $page = new Page();
        $form = $this->createForm(PageType::class, $page, ['validation_groups' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $page->setBuilderData($this->sanitizeBuilderData($page->getBuilderData()));
            $content = $this->renderView('cms/page/show.html.twig', [
                'page' => $page,
                'source_page' => $page,
                'alternates' => [],
                'preview' => true,
            ]);
            $token = bin2hex(random_bytes(16));
            $previews = $request->getSession()->get('page_previews', []);
            $previews[$token] = $content;
            $request->getSession()->set('page_previews', array_slice($previews, -5, null, true));

            return $this->redirectToRoute('admin_page_preview_show', ['token' => $token]);
        }

        return $this->render('admin/page/form.html.twig', [
            'form' => $form,
            'page' => $page,
        ], new Response(status: Response::HTTP_UNPROCESSABLE_ENTITY));
    }

    #[Route('/preview/{token}', name: 'admin_page_preview_show', requirements: ['token' => '[a-f0-9]{32}'], methods: ['GET'])]
    public function previewShow(string $token, Request $request): Response
    {
        $content = $request->getSession()->get('page_previews', [])[$token] ?? null;
        if (!is_string($content)) {
            throw $this->createNotFoundException($this->translator->translate('page.preview_expired'));
        }

        $response = new Response($content);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }

    #[Route('/{id}/edit', name: 'admin_page_edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function edit(Page $page, Request $request, PageRepository $pages): Response
    {
        if ($page->isDeleted()) return $this->redirectToRoute('admin_page_trash');
        return $this->handleForm($request, $page, $pages, 'page.changes_saved');
    }

    #[Route('/{id}/delete', name: 'admin_page_delete', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function delete(Page $page, Request $request, PageRepository $pages, MenuItemRepository $menuItems): Response
    {
        if ($page->isSystemPage()) {
            $this->addFlash('error', $this->translator->translate('page.system_delete_forbidden'));
            return $this->redirectToRoute('admin_page_index');
        }
        if (($usage = $menuItems->countForPage($page)) > 0) {
            $this->addFlash('error', sprintf($this->translator->translate('page.menu_usage_delete_forbidden'), $usage));
            return $this->redirectToRoute('admin_page_index');
        }
        if ($this->isCsrfTokenValid('delete-page-'.$page->getId(), (string) $request->request->get('_token'))) {
            $page->moveToTrash();
            $pages->save($page);
            $this->addFlash('success', $this->translator->translate('page.moved_to_trash'));
        }

        return $this->redirectToRoute('admin_page_index');
    }

    #[Route('/trash', name: 'admin_page_trash', methods: ['GET'])]
    public function trash(Request $request, PageRepository $pages, SettingsProvider $settings): Response
    {
        $currentPage = max(1, $request->query->getInt('page', 1));
        $limit = max(1, min(200, (int) $settings->get('per_page', 20)));
        $search = trim($request->query->getString('q'));
        $query = $pages->createQueryBuilder('page')->andWhere('page.deletedAt IS NOT NULL')->orderBy('page.deletedAt', 'DESC');
        if ($search !== '') $query->andWhere('LOWER(page.title) LIKE :trashSearch OR LOWER(page.slug) LIKE :trashSearch')->setParameter('trashSearch', '%'.mb_strtolower($search).'%');
        $total = (int) (clone $query)->select('COUNT(page.id)')->getQuery()->getSingleScalarResult();
        $listedPages = $query->setFirstResult(($currentPage - 1) * $limit)->setMaxResults($limit)->getQuery()->getResult();
        return $this->render('admin/page/trash.html.twig', ['pages' => $listedPages, 'search' => $search, 'total' => $total, 'current_page' => $currentPage, 'last_page' => max(1, (int) ceil($total / $limit)), 'query_params' => array_filter(['q' => $search])]);
    }

    #[Route('/trash/bulk-restore', name: 'admin_page_trash_bulk_restore', methods: ['POST'])]
    public function bulkRestore(Request $request, PageRepository $pages): Response
    {
        if (!$this->isCsrfTokenValid('bulk-restore-pages', $request->request->getString('_token'))) {
            $this->addFlash('error', $this->translator->translate('page.bulk_invalid_token'));
            return $this->redirectToRoute('admin_page_trash');
        }
        $ids = array_slice(array_values(array_unique(array_filter(array_map(static fn (mixed $id): int => max(0, (int) $id), $request->request->all('pages'))))), 0, 200);
        if ($ids === []) {
            $this->addFlash('error', $this->translator->translate('page.bulk_select_pages'));
            return $this->redirectToRoute('admin_page_trash');
        }
        $restored = 0;
        foreach ($pages->findBy(['id' => $ids]) as $page) {
            if (!$page->isDeleted()) continue;
            $page->restoreFromTrash();
            $page->setPublished(false);
            $pages->save($page);
            ++$restored;
        }
        $this->addFlash('success', sprintf($this->translator->translate('page.bulk_restored'), $restored));
        return $this->redirectToRoute('admin_page_trash');
    }

    #[Route('/{id}/restore', name: 'admin_page_restore', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function restore(Page $page, Request $request, PageRepository $pages): Response
    {
        if ($page->isDeleted() && $this->isCsrfTokenValid('restore-page-'.$page->getId(), $request->request->getString('_token'))) {
            $page->restoreFromTrash();
            $pages->save($page);
            $this->addFlash('success', $this->translator->translate('page.restored_from_trash'));
        }
        return $this->redirectToRoute('admin_page_trash');
    }

    #[Route('/{id}/destroy', name: 'admin_page_destroy', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function destroy(Page $page, Request $request, PageRepository $pages, MenuItemRepository $menuItems): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        if (($usage = $menuItems->countForPage($page)) > 0) {
            $this->addFlash('error', sprintf($this->translator->translate('page.menu_usage_destroy_forbidden'), $usage));
            return $this->redirectToRoute('admin_page_trash');
        }
        if ($page->isDeleted() && $this->isCsrfTokenValid('destroy-page-'.$page->getId(), $request->request->getString('_token'))) {
            $pages->remove($page);
            $this->addFlash('success', $this->translator->translate('page.deleted_permanently'));
        }
        return $this->redirectToRoute('admin_page_trash');
    }

    #[Route('/{id}/duplicate', name: 'admin_page_duplicate', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function duplicate(Page $page, Request $request, PageRepository $pages): Response
    {
        if ($this->isCsrfTokenValid('duplicate-page-'.$page->getId(), (string) $request->request->get('_token'))) {
            $copy = $page->copyAs('kopia-'.date('YmdHis'));
            $pages->save($copy);
            $this->addFlash('success', $this->translator->translate('page.duplicated'));
            return $this->redirectToRoute('admin_page_edit', ['id' => $copy->getId()]);
        }
        return $this->redirectToRoute('admin_page_index');
    }

    private function handleForm(Request $request, Page $page, PageRepository $pages, string $message): Response
    {
        $previousSlug = $page->getId() !== null ? $page->getSlug() : null;
        $currentLockVersion = $page->getLockVersion();
        if (!$page->usesComponentBuilder()) {
            $legacyContent = $page->getContent();
            $page->setEditorMode('components');
            $page->setBuilderData(json_encode([[
                'id' => 'legacy-section-'.$page->getId(),
                'type' => 'layout_section',
                'data' => ['container' => 'grid', 'widths' => [100], 'columns' => [[[
                    'id' => 'legacy-text-'.$page->getId(),
                    'type' => 'rich_text',
                    'data' => ['content' => $legacyContent],
                ]]]],
            ]], JSON_THROW_ON_ERROR));
        }
        $form = $this->createForm(PageType::class, $page, ['page_version' => $currentLockVersion]);
        $form->handleRequest($request);

        if ($page->getId() !== null && $form->isSubmitted() && (int) $form->get('lockVersion')->getData() !== $currentLockVersion) {
            $form->addError(new FormError($this->translator->translate('page.concurrent_edit')));
        }

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $page->setBuilderData($this->sanitizeBuilderData($page->getBuilderData()));
                $this->entityManager->getConnection()->beginTransaction();
                $pages->save($page);
                if ($previousSlug !== null && $previousSlug !== $page->getSlug()) $this->redirectManager->registerSlugChange($previousSlug, $page->getSlug(), $page->isHomePage());
                $user = $this->getUser();
                $this->revisionManager->snapshot($page, $user instanceof AdminUser ? $user : null);
                $this->entityManager->getConnection()->commit();
                $this->addFlash('success', $this->translator->translate($message));

                if ('stay' === $request->request->get('_save_action')) {
                    return $this->redirectToRoute('admin_page_edit', ['id' => $page->getId()]);
                }

                return $this->redirectToRoute('admin_page_index');
            } catch (OptimisticLockException) {
                if ($this->entityManager->getConnection()->isTransactionActive()) $this->entityManager->getConnection()->rollBack();
                $form->addError(new FormError($this->translator->translate('page.concurrent_edit')));
            } catch (UniqueConstraintViolationException) {
                if ($this->entityManager->getConnection()->isTransactionActive()) $this->entityManager->getConnection()->rollBack();
                $this->addFlash('error', $this->translator->translate('page.slug_exists'));
            } catch (\LogicException $exception) {
                if ($this->entityManager->getConnection()->isTransactionActive()) $this->entityManager->getConnection()->rollBack();
                $this->addFlash('error', $this->translator->translate($exception->getMessage()));
            } catch (\Throwable $exception) {
                if ($this->entityManager->getConnection()->isTransactionActive()) $this->entityManager->getConnection()->rollBack();
                throw $exception;
            }
        }

        return $this->render('admin/page/form.html.twig', [
            'form' => $form,
            'page' => $page,
        ]);
    }

    private function sanitizeBuilderData(string $json): string
    {
        try {
            $project = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return '[]';
        }
        $sanitize = function (array &$components) use (&$sanitize): void {
            foreach ($components as &$component) {
                if (($component['type'] ?? null) === 'rich_text' && isset($component['data']['content'])) {
                    $component['data']['content'] = $this->htmlSanitizer->sanitize((string) $component['data']['content']);
                }

                if (isset($component['data']['columns']) && is_array($component['data']['columns'])) {
                    foreach ($component['data']['columns'] as &$column) {
                        if (is_array($column)) {
                            $sanitize($column);
                        }
                    }
                    unset($column);
                }
            }
            unset($component);
        };

        if (is_array($project)) {
            $sanitize($project);
        }

        return json_encode($project, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

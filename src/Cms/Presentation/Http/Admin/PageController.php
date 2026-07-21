<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Admin;

use App\Cms\Domain\Entity\Page;
use App\Cms\Application\UrlRedirectManager;
use App\Cms\Application\PageRevisionManager;
use App\Identity\Domain\Entity\AdminUser;
use App\Cms\Infrastructure\Persistence\Doctrine\PageRepository;
use App\Cms\Presentation\Form\PageType;
use App\Settings\Application\SettingsProvider;
use App\Language\Application\SystemTranslator;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
    public function index(Request $request, PageRepository $pages, SettingsProvider $settings): Response
    {
        $page = max(1, $request->query->getInt('page', 1)); $limit = max(1, min(200, (int) $settings->get('per_page', 20)));
        $search = trim($request->query->getString('q'));
        $status = $request->query->getString('status');
        if (!in_array($status, ['', 'draft', 'scheduled', 'published', 'expired'], true)) $status = '';
        $query = $pages->createQueryBuilder('p')->orderBy('p.updatedAt', 'DESC');
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
        return $this->render('admin/page/index.html.twig', ['pages' => $query->setFirstResult(($page - 1) * $limit)->setMaxResults($limit)->getQuery()->getResult(), 'current_page' => $page, 'last_page' => max(1, (int) ceil($total / $limit)), 'total' => $total, 'search' => $search, 'status_filter' => $status, 'query_params' => array_filter(['q' => $search, 'status' => $status], static fn (string $value): bool => $value !== '')]);
    }

    #[Route('/new', name: 'admin_page_new', methods: ['GET', 'POST'])]
    public function new(Request $request, PageRepository $pages): Response
    {
        return $this->handleForm($request, new Page(), $pages, 'page.created');
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
        return $this->handleForm($request, $page, $pages, 'page.changes_saved');
    }

    #[Route('/{id}/delete', name: 'admin_page_delete', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function delete(Page $page, Request $request, PageRepository $pages): Response
    {
        if ($page->isSystemPage()) {
            $this->addFlash('error', $this->translator->translate('page.system_delete_forbidden'));
            return $this->redirectToRoute('admin_page_index');
        }
        if ($this->isCsrfTokenValid('delete-page-'.$page->getId(), (string) $request->request->get('_token'))) {
            $pages->remove($page);
            $this->addFlash('success', $this->translator->translate('page.deleted'));
        }

        return $this->redirectToRoute('admin_page_index');
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
        $form = $this->createForm(PageType::class, $page);
        $form->handleRequest($request);

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

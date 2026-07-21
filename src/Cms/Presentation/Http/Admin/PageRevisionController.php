<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Admin;

use App\Cms\Application\PageRevisionManager;
use App\Cms\Application\UrlRedirectManager;
use App\Cms\Domain\Entity\Page;
use App\Cms\Domain\Entity\PageRevision;
use App\Cms\Infrastructure\Persistence\Doctrine\PageRepository;
use App\Cms\Infrastructure\Persistence\Doctrine\PageRevisionRepository;
use App\Identity\Domain\Entity\AdminUser;
use App\Language\Application\SystemTranslator;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/pages/{id}/revisions', requirements: ['id' => '\d+'])]
#[IsGranted('ROLE_EDITOR')]
final class PageRevisionController extends AbstractController
{
    public function __construct(private readonly SystemTranslator $translator) {}

    #[Route('', name: 'admin_page_revision_index', methods: ['GET'])]
    public function index(Page $page, PageRevisionRepository $repository, PageRevisionManager $manager): Response
    {
        if ($page->isDeleted()) return $this->redirectToRoute('admin_page_trash');
        $revisions = $repository->forPage($page);
        $rows = [];
        foreach ($revisions as $index => $revision) {
            $rows[] = ['revision' => $revision, 'changes' => $manager->changes($revision, $revisions[$index + 1] ?? null)];
        }

        return $this->render('admin/page/revisions.html.twig', ['page' => $page, 'rows' => $rows]);
    }

    #[Route('/{revisionId}', name: 'admin_page_revision_show', requirements: ['revisionId' => '\d+'], methods: ['GET'])]
    public function show(Page $page, int $revisionId, PageRevisionRepository $repository): Response
    {
        if ($page->isDeleted()) return $this->redirectToRoute('admin_page_trash');
        $revision = $this->revisionForPage($page, $revisionId, $repository);
        $previous = $repository->findOneBy(['page' => $page, 'version' => $revision->getVersion() - 1]);
        $fields = [
            'title' => 'page.title', 'slug' => 'page.slug', 'caption' => 'page.caption',
            'published' => 'page.published', 'publishAt' => 'page.publish_at', 'unpublishAt' => 'page.unpublish_at',
            'access' => 'page.access', 'seoTitle' => 'page.seo_title', 'description' => 'page.meta_description',
            'canonical' => 'page.canonical', 'follow' => 'page.follow', 'content' => 'revision.text_content',
            'builderData' => 'revision.builder_content',
        ];
        $comparison = [];
        foreach ($fields as $field => $label) {
            $currentValue = $revision->getData()[$field] ?? null;
            $previousValue = $previous?->getData()[$field] ?? null;
            $comparison[] = [
                'label' => $label,
                'current' => $this->displayValue($field, $currentValue),
                'previous' => $previous ? $this->displayValue($field, $previousValue) : null,
                'changed' => !$previous || $currentValue !== $previousValue,
            ];
        }

        return $this->render('admin/page/revision_show.html.twig', [
            'page' => $page, 'revision' => $revision, 'previous' => $previous, 'comparison' => $comparison,
        ]);
    }

    #[Route('/{revisionId}/restore', name: 'admin_page_revision_restore', requirements: ['revisionId' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function restore(Page $page, int $revisionId, Request $request, PageRevisionRepository $repository, PageRevisionManager $manager, PageRepository $pages, UrlRedirectManager $redirects, EntityManagerInterface $em): Response
    {
        if ($page->isDeleted()) return $this->redirectToRoute('admin_page_trash');
        $revision = $this->revisionForPage($page, $revisionId, $repository);
        if (!$this->isCsrfTokenValid('restore-revision-'.$revisionId, (string) $request->request->get('_token'))) {
            return $this->redirectToRoute('admin_page_revision_index', ['id' => $page->getId()]);
        }
        $slugOwner = $pages->findOneBy(['slug' => $revision->getSlug()]);
        if ($slugOwner && $slugOwner->getId() !== $page->getId()) {
            $this->addFlash('error', $this->translator->translate('revision.slug_conflict'));
            return $this->redirectToRoute('admin_page_revision_index', ['id' => $page->getId()]);
        }
        $oldSlug = $page->getSlug();
        $em->getConnection()->beginTransaction();
        try {
            $manager->restore($page, $revision);
            $pages->save($page);
            if ($oldSlug !== $page->getSlug()) $redirects->registerSlugChange($oldSlug, $page->getSlug(), $page->isHomePage());
            $user = $this->getUser();
            $manager->snapshot($page, $user instanceof AdminUser ? $user : null);
            $em->getConnection()->commit();
            $this->addFlash('success', $this->translator->translate('revision.restored'));
        } catch (UniqueConstraintViolationException) {
            if ($em->getConnection()->isTransactionActive()) $em->getConnection()->rollBack();
            $this->addFlash('error', $this->translator->translate('revision.slug_conflict'));
            return $this->redirectToRoute('admin_page_revision_index', ['id' => $page->getId()]);
        } catch (\Throwable $exception) {
            if ($em->getConnection()->isTransactionActive()) $em->getConnection()->rollBack();
            throw $exception;
        }

        return $this->redirectToRoute('admin_page_edit', ['id' => $page->getId()]);
    }

    private function revisionForPage(Page $page, int $revisionId, PageRevisionRepository $repository): PageRevision
    {
        $revision = $repository->find($revisionId);
        if (!$revision || $revision->getPage()->getId() !== $page->getId()) throw $this->createNotFoundException();
        return $revision;
    }

    private function displayValue(string $field, mixed $value): string
    {
        if (is_bool($value)) return $this->translator->translate($value ? 'common.yes' : 'common.no');
        if ($value === null || $value === '') return '—';
        if ($field === 'content') return mb_strimwidth(trim(strip_tags((string) $value)), 0, 240, '…');
        if ($field === 'builderData') return sprintf($this->translator->translate('revision.builder_size'), mb_strlen((string) $value));
        return (string) $value;
    }
}

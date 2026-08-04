<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http;

use App\Cms\Application\PageAccess;
use App\Cms\Application\SystemPageRouteResolver;
use App\Cms\Application\PublicContentResolver;
use App\Cms\Domain\Entity\Page;
use App\Cms\Domain\Entity\PageTranslation;
use App\Cms\Infrastructure\Persistence\Doctrine\PageRepository;
use App\Language\Domain\Entity\Language;
use App\Language\Application\LocalizedPageUrlGenerator;
use App\Language\Application\SystemTranslator;
use App\Identity\Domain\Entity\SiteUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[\App\Module\Application\RequiresModule('cms')]
final class PublicPageController extends AbstractController
{
    public function __construct(
        private readonly SystemTranslator $translator,
        private readonly SystemPageRouteResolver $systemPageRoutes,
        private readonly PublicContentResolver $publicContent,
    )
    {
    }

    #[Route('/{_locale}/{slug}', name: 'cms_page_show_localized', requirements: ['_locale' => '[a-z]{2}', 'slug' => '[a-z0-9-]+'], methods: ['GET'], priority: -90)]
    public function localized(string $_locale, string $slug, EntityManagerInterface $em, PageRepository $pages, Request $request, PageAccess $access): Response
    {
        $language = $em->getRepository(Language::class)->findOneBy(['code' => $_locale, 'active' => true]);
        if ($language?->isDefaultLanguage()) {
            $basePage = $pages->findPublishedBySlug($slug);
            if (!$basePage) return $this->publicContent->resolve($slug,$request)??throw $this->createNotFoundException($this->translator->translate('page.public_not_found'));
            return $this->redirectToRoute('cms_page_show', ['slug' => $basePage->getSlug()], Response::HTTP_FOUND);
        }
        $translation = $language ? $em->getRepository(PageTranslation::class)->findOneBy(['language' => $language, 'slug' => $slug]) : null;
        if (!$translation || (!$translation->isPublished() && !$this->isGranted('ROLE_ADMIN'))) return $this->publicContent->resolve($slug,$request,$_locale)??throw $this->createNotFoundException($this->translator->translate('page.public_translation_not_found'));
        $page = $translation->getPage();
        if (!$page->isPubliclyAvailable()) throw $this->createNotFoundException($this->translator->translate('page.public_unpublished'));
        if ($page->isAdminOnly() && !$this->isGranted('ROLE_ADMIN')) throw $this->createNotFoundException($this->translator->translate('page.public_not_found'));
        if ($target = $this->systemPageRoutes->resolve($page, $language)) {
            return $this->redirectToRoute($target['route'], $target['parameters']);
        }
        if ($denied = $this->guard($page, $request, $access)) return $denied;

        return $this->freshPageResponse($this->render('cms/page/show.html.twig', ['page' => $translation, 'source_page' => $page, 'alternates' => $pages->findPublishedActiveTranslations($page)]));
    }

    #[Route('/{slug}', name: 'cms_page_show', requirements: ['slug' => '[a-z0-9-]+'], methods: ['GET'], priority: -100)]
    public function __invoke(string $slug, PageRepository $pages, Request $request, LocalizedPageUrlGenerator $localizedUrls, PageAccess $access): Response
    {
        $page = $pages->findPublishedBySlug($slug);
        if ($page === null) {
            return $this->publicContent->resolve($slug,$request)??throw $this->createNotFoundException($this->translator->translate('page.public_unpublished'));
        }
        if ($page->isAdminOnly() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException($this->translator->translate('page.public_not_found'));
        }
        $language = $request->attributes->get('_shopro_language');
        if ($target = $this->systemPageRoutes->resolve($page, $language instanceof Language ? $language : null)) {
            return $this->redirectToRoute($target['route'], $target['parameters']);
        }
        if ($denied = $this->guard($page, $request, $access)) return $denied;
        if($language instanceof Language&&!$language->isDefaultLanguage()){
            $localizedUrl=$localizedUrls->page($page,$language);
            $baseUrl=$this->generateUrl('cms_page_show',['slug'=>$page->getSlug()]);
            if($localizedUrl!==$baseUrl)return $this->redirect($localizedUrl);
        }

        return $this->freshPageResponse($this->render('cms/page/show.html.twig', ['page' => $page, 'source_page' => $page, 'alternates' => $pages->findPublishedActiveTranslations($page)]));
    }

    private function guard(Page $page, Request $request, PageAccess $access): ?Response
    {
        $user = $this->getUser();
        $siteUser = $user instanceof SiteUser ? $user : null;
        if ($access->isAllowed($page, $siteUser)) return null;
        if ($siteUser === null) {
            $request->getSession()->set('_security.frontend.target_path', $request->getUri());
            return $this->redirectToRoute('site_login');
        }
        throw $this->createAccessDeniedException($this->translator->translate('site_auth.membership_required'));
    }

    private function freshPageResponse(Response $response): Response
    {
        $response->headers->set('Cache-Control', 'no-cache, private, must-revalidate');
        return $response;
    }
}

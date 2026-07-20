<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http;

use App\Cms\Application\PageAccess;
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

final class PublicPageController extends AbstractController
{
    public function __construct(private readonly SystemTranslator $translator)
    {
    }

    #[Route('/{_locale}/{slug}', name: 'cms_page_show_localized', requirements: ['_locale' => '[a-z]{2}', 'slug' => '[a-z0-9-]+'], methods: ['GET'], priority: -90)]
    public function localized(string $_locale, string $slug, EntityManagerInterface $em, Request $request, PageAccess $access): Response
    {
        $language = $em->getRepository(Language::class)->findOneBy(['code' => $_locale, 'active' => true]);
        if ($language?->isDefaultLanguage()) {
            $basePage = $em->getRepository(\App\Cms\Domain\Entity\Page::class)->findOneBy(['slug' => $slug, 'published' => true]);
            if (!$basePage) throw $this->createNotFoundException($this->translator->translate('page.public_not_found'));
            return $this->redirectToRoute('cms_page_show', ['slug' => $basePage->getSlug()], Response::HTTP_FOUND);
        }
        $translation = $language ? $em->getRepository(PageTranslation::class)->findOneBy(['language' => $language, 'slug' => $slug]) : null;
        if (!$translation || (!$translation->isPublished() && !$this->isGranted('ROLE_ADMIN'))) throw $this->createNotFoundException($this->translator->translate('page.public_translation_not_found'));
        $page = $translation->getPage();
        if ($page->isAdminOnly() && !$this->isGranted('ROLE_ADMIN')) throw $this->createNotFoundException($this->translator->translate('page.public_not_found'));
        if ($denied = $this->guard($page, $request, $access)) return $denied;

        return $this->render('cms/page/show.html.twig', ['page' => $translation, 'source_page' => $page, 'alternates' => $em->getRepository(PageTranslation::class)->findBy(['page' => $page, 'published' => true])]);
    }

    #[Route('/{slug}', name: 'cms_page_show', requirements: ['slug' => '[a-z0-9-]+'], methods: ['GET'], priority: -100)]
    public function __invoke(string $slug, PageRepository $pages, EntityManagerInterface $em, Request $request, LocalizedPageUrlGenerator $localizedUrls, PageAccess $access): Response
    {
        $page = $pages->findPublishedBySlug($slug);
        if ($page === null) {
            throw $this->createNotFoundException($this->translator->translate('page.public_unpublished'));
        }
        if ($page->isAdminOnly() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException($this->translator->translate('page.public_not_found'));
        }
        if ($denied = $this->guard($page, $request, $access)) return $denied;
        $language=$request->attributes->get('_shopro_language');
        if($language instanceof Language&&!$language->isDefaultLanguage()){
            $localizedUrl=$localizedUrls->page($page,$language);
            $baseUrl=$this->generateUrl('cms_page_show',['slug'=>$page->getSlug()]);
            if($localizedUrl!==$baseUrl)return $this->redirect($localizedUrl);
        }

        return $this->render('cms/page/show.html.twig', ['page' => $page, 'source_page' => $page, 'alternates' => $em->getRepository(PageTranslation::class)->findBy(['page' => $page, 'published' => true])]);
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
}

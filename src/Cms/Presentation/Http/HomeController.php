<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http;

use App\Cms\Application\PageAccess;
use App\Cms\Infrastructure\Persistence\Doctrine\PageRepository;
use App\Identity\Domain\Entity\SiteUser;
use App\Language\Application\LocalizedPageUrlGenerator;
use App\Language\Domain\Entity\Language;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function __invoke(PageRepository $pages, Request $request, LocalizedPageUrlGenerator $localizedUrls, PageAccess $access): Response
    {
        if (null !== ($page = $pages->findPublishedHomePage()) && !$page->isAdminOnly()) {
            $user = $this->getUser();
            $siteUser = $user instanceof SiteUser ? $user : null;
            if (!$access->isAllowed($page, $siteUser)) {
                if ($siteUser === null) {
                    $request->getSession()->set('_security.frontend.target_path', $request->getUri());
                    return $this->redirectToRoute('site_login');
                }
                throw $this->createAccessDeniedException();
            }
            $language=$request->attributes->get('_shopro_language');
            if($language instanceof Language&&!$language->isDefaultLanguage()){
                $localizedUrl=$localizedUrls->page($page,$language);
                if($localizedUrl!==$this->generateUrl('app_home'))return $this->redirect($localizedUrl);
            }
            return $this->render('cms/page/show.html.twig', [
                'page' => $page,
                'source_page' => $page,
                'alternates' => $pages->findPublishedActiveTranslations($page),
            ]);
        }
        return $this->render('cms/home.html.twig');
    }
}

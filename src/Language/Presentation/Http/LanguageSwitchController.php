<?php
declare(strict_types=1);
namespace App\Language\Presentation\Http;

use App\Cms\Domain\Entity\Page;
use App\Cms\Application\PageAccess;
use App\Identity\Domain\Entity\SiteUser;
use App\Language\Application\LocalizedPageUrlGenerator;
use App\Language\Application\SystemTranslator;
use App\Language\Domain\Entity\Language;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[\App\Module\Application\RequiresModule('language')]
final class LanguageSwitchController extends AbstractController
{
 public function __construct(private readonly SystemTranslator $translator){}
 #[Route('/admin/language/{code}',name:'admin_language_switch',requirements:['code'=>'[a-z]{2}'],methods:['GET'])]
 #[IsGranted('ROLE_ADMIN')]
 public function admin(string $code,Request $request,EntityManagerInterface $em):Response
 {
  $language=$em->getRepository(Language::class)->findOneBy(['code'=>mb_strtolower($code),'active'=>true]);
  if(!$language)throw $this->createNotFoundException($this->translator->translate('language.not_found'));
  $request->getSession()->set('shopro_language',$language->getCode());
  $return=(string)$request->query->get('return','');
  if(!$this->isSafeAdminReturn($return))$return=$this->generateUrl('admin_dashboard');
  $response=$this->redirect($return);
  $response->headers->set('Cache-Control','no-store, private');
  return $response;
 }

 #[Route('/language/{code}',name:'site_language_switch',requirements:['code'=>'[a-z]{2}'],methods:['GET'])]
 public function __invoke(string $code,Request $request,EntityManagerInterface $em,LocalizedPageUrlGenerator $urls,PageAccess $access):Response
 {
  $language=$em->getRepository(Language::class)->findOneBy(['code'=>mb_strtolower($code),'active'=>true]);
  if(!$language)throw $this->createNotFoundException($this->translator->translate('language.not_found'));
  $request->getSession()->set('shopro_language',$language->getCode());
  $pageId=$request->query->getInt('page');
  $page=$pageId>0?$em->find(Page::class,$pageId):null;
  $user=$this->getUser();
  if($page&&(!$page->isPubliclyAvailable()||$page->isAdminOnly()||!$access->isAllowed($page,$user instanceof SiteUser?$user:null)))$page=null;
  $response=$this->redirect($page?$urls->page($page,$language):$this->generateUrl('app_home'));
  $response->headers->set('Cache-Control','no-store, private');
  return $response;
 }

 private function isSafeAdminReturn(string $return):bool
 {
  if($return===''||preg_match('/[\x00-\x20\\\\]/u',$return))return false;
  $parts=parse_url($return);
  if($parts===false||isset($parts['scheme'])||isset($parts['host'])||isset($parts['user'])||isset($parts['pass']))return false;
  $path=(string)($parts['path']??'');
  return $path==='/admin'||str_starts_with($path,'/admin/');
 }
}

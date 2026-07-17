<?php
declare(strict_types=1);
namespace App\Language\Presentation\Http;

use App\Cms\Domain\Entity\Page;
use App\Language\Application\LocalizedPageUrlGenerator;
use App\Language\Domain\Entity\Language;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class LanguageSwitchController extends AbstractController
{
 #[Route('/admin/language/{code}',name:'admin_language_switch',requirements:['code'=>'[a-z]{2}'],methods:['GET'])]
 #[IsGranted('ROLE_ADMIN')]
 public function admin(string $code,Request $request,EntityManagerInterface $em):Response
 {
  $language=$em->getRepository(Language::class)->findOneBy(['code'=>mb_strtolower($code),'active'=>true]);
  if(!$language)throw $this->createNotFoundException('Język nie istnieje.');
  $request->getSession()->set('shopro_language',$language->getCode());
  $return=(string)$request->query->get('return','');
  if(!str_starts_with($return,'/admin')||str_starts_with($return,'//'))$return=$this->generateUrl('admin_dashboard');
  $response=$this->redirect($return);
  $response->headers->set('Cache-Control','no-store, private');
  return $response;
 }

 #[Route('/language/{code}',name:'site_language_switch',requirements:['code'=>'[a-z]{2}'],methods:['GET'])]
 public function __invoke(string $code,Request $request,EntityManagerInterface $em,LocalizedPageUrlGenerator $urls):Response
 {
  $language=$em->getRepository(Language::class)->findOneBy(['code'=>mb_strtolower($code),'active'=>true]);
  if(!$language)throw $this->createNotFoundException('Język nie istnieje.');
  $request->getSession()->set('shopro_language',$language->getCode());
  $pageId=$request->query->getInt('page');
  $page=$pageId>0?$em->find(Page::class,$pageId):null;
  $response=$this->redirect($page?$urls->page($page,$language):$this->generateUrl('app_home'));
  $response->headers->set('Cache-Control','no-store, private');
  return $response;
 }
}

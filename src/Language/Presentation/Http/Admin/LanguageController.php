<?php
declare(strict_types=1);
namespace App\Language\Presentation\Http\Admin;

use App\Language\Domain\Entity\Language;
use App\Language\Domain\Entity\Translation;
use App\Language\Presentation\Form\LanguageType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/configuration/languages')]
#[IsGranted('ROLE_ADMIN')]
final class LanguageController extends AbstractController
{
 #[Route('',name:'admin_language_index',methods:['GET'])]
 public function index(EntityManagerInterface $em):Response{return $this->render('admin/language/index.html.twig',['languages'=>$em->getRepository(Language::class)->findBy([],['name'=>'ASC'])]);}
 #[Route('/new',name:'admin_language_new',methods:['GET','POST'])]
 public function new(Request $r,EntityManagerInterface $em):Response{return $this->form(new Language(),$r,$em);}
 #[Route('/{id}/edit',name:'admin_language_edit',requirements:['id'=>'\d+'],methods:['GET','POST'])]
 public function edit(Language $language,Request $r,EntityManagerInterface $em):Response{return $this->form($language,$r,$em);}
 #[Route('/{id}/delete',name:'admin_language_delete',requirements:['id'=>'\d+'],methods:['POST'])]
 public function delete(Language $language,Request $r,EntityManagerInterface $em):Response{
  if($this->isCsrfTokenValid('delete-language-'.$language->getId(),(string)$r->request->get('_token'))){
   if($em->getRepository(Language::class)->count([])<=1)$this->addFlash('error','Nie można usunąć ostatniego języka.');
   else{$em->remove($language);$em->flush();$this->addFlash('success','Język został usunięty.');}
  }return $this->redirectToRoute('admin_language_index');
 }
 #[Route('/{id}/phrases',name:'admin_language_phrases',requirements:['id'=>'\d+'],methods:['GET','POST'])]
 public function phrases(Language $language,Request $r,EntityManagerInterface $em):Response{
  if($r->isMethod('POST')&&$this->isCsrfTokenValid('language-phrases-'.$language->getId(),(string)$r->request->get('_token'))){
   $key=trim((string)$r->request->get('key'));$value=(string)$r->request->get('value');
   if(!preg_match('/^[a-zA-Z0-9_.-]{1,190}$/',$key))$this->addFlash('error','Klucz może zawierać litery, cyfry, kropki, myślniki i podkreślenia.');
   else{$item=$em->getRepository(Translation::class)->findOneBy(['language'=>$language,'key'=>$key])??new Translation($language,$key);$item->setValue($value);$em->persist($item);$em->flush();$this->addFlash('success','Tłumaczenie zostało zapisane.');}
   return $this->redirectToRoute('admin_language_phrases',['id'=>$language->getId(),'q'=>$r->query->get('q')]);
  }
  $q=trim((string)$r->query->get('q'));$qb=$em->getRepository(Translation::class)->createQueryBuilder('t')->andWhere('t.language=:language')->setParameter('language',$language)->orderBy('t.key','ASC');
  if($q!=='')$qb->andWhere('t.key LIKE :q OR t.value LIKE :q')->setParameter('q','%'.$q.'%');
  return $this->render('admin/language/phrases.html.twig',['language'=>$language,'phrases'=>$qb->getQuery()->getResult(),'query'=>$q]);
 }
 private function form(Language $language,Request $r,EntityManagerInterface $em):Response{
  $form=$this->createForm(LanguageType::class,$language);$form->handleRequest($r);
  if($form->isSubmitted()&&$form->isValid()){$em->persist($language);$em->flush();$this->addFlash('success','Język został zapisany.');return $this->redirectToRoute('admin_language_index');}
  return $this->render('admin/language/form.html.twig',['form'=>$form,'language'=>$language]);
 }
}

<?php
declare(strict_types=1);
namespace App\Cms\Presentation\Http\Admin;
use App\Cms\Application\PageBuilderSanitizer;use App\Cms\Domain\Entity\Page;use App\Cms\Domain\Entity\PageTranslation;use App\Cms\Presentation\Form\PageTranslationType;use App\Language\Domain\Entity\Language;use App\Language\Application\SystemTranslator;use Doctrine\ORM\EntityManagerInterface;use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;use Symfony\Component\HttpFoundation\Request;use Symfony\Component\HttpFoundation\Response;use Symfony\Component\Routing\Attribute\Route;use Symfony\Component\Security\Http\Attribute\IsGranted;
#[Route('/admin/pages/{id}/translations')]
#[IsGranted('ROLE_EDITOR')]
#[\App\Module\Application\RequiresModule('cms')]
final class PageTranslationController extends AbstractController
{
 public function __construct(private readonly PageBuilderSanitizer $builderSanitizer,private readonly ?SystemTranslator $translator=null){}
 #[Route('',name:'admin_page_translation_index',requirements:['id'=>'\d+'],methods:['GET'])]
 public function index(Page $page,EntityManagerInterface $em):Response{$translations=[];foreach($em->getRepository(PageTranslation::class)->findBy(['page'=>$page]) as $t)$translations[$t->getLanguage()->getId()]=$t;return $this->render('admin/page/translations.html.twig',['page'=>$page,'languages'=>$em->getRepository(Language::class)->findBy(['active'=>true,'defaultLanguage'=>false],['name'=>'ASC']),'translations'=>$translations]);}
 #[Route('/{languageId}',name:'admin_page_translation_edit',requirements:['id'=>'\d+','languageId'=>'\d+'],methods:['GET','POST'])]
 public function edit(Page $page,int $languageId,Request $request,EntityManagerInterface $em):Response
 {
  $language=$em->find(Language::class,$languageId);
  if(!$language)throw $this->createNotFoundException($this->translator->translate('language.not_found'));
  if($language->isDefaultLanguage()){$this->addFlash('success',$this->translator->translate('page.base_language_edit'));return $this->redirectToRoute('admin_page_edit',['id'=>$page->getId()]);}
  $translation=$em->getRepository(PageTranslation::class)->findOneBy(['page'=>$page,'language'=>$language])??new PageTranslation($page,$language);
  $form=$this->createForm(PageTranslationType::class,$translation);
  $form->handleRequest($request);
  if($form->isSubmitted()&&$form->isValid()){
   $translation->setBuilderData($this->builderSanitizer->sanitize($translation->getBuilderData()));
   $this->addFlash('success',$this->translator->translate('page.translation_saved'));
   $em->persist($translation);$em->flush();
   return $this->redirectToRoute('admin_page_translation_edit',['id'=>$page->getId(),'languageId'=>$language->getId()]);
  }
  return $this->render('admin/page/translation_form.html.twig',['form'=>$form,'page'=>$page,'translation'=>$translation,'language'=>$language]);
 }
 #[Route('/{languageId}/copy-base-template',name:'admin_page_translation_copy_base',requirements:['id'=>'\d+','languageId'=>'\d+'],methods:['POST'])]
 public function copyBaseTemplate(Page $page,int $languageId,Request $request,EntityManagerInterface $em):Response
 {
  $language=$em->find(Language::class,$languageId);
  if(!$language||$language->isDefaultLanguage())throw $this->createNotFoundException($this->translator->translate('page.language_version_missing'));
  if(!$this->isCsrfTokenValid('copy-page-template-'.$page->getId().'-'.$languageId,(string)$request->request->get('_token')))throw $this->createAccessDeniedException($this->translator->translate('page.invalid_form_token'));
  $translation=$em->getRepository(PageTranslation::class)->findOneBy(['page'=>$page,'language'=>$language])??new PageTranslation($page,$language);
  $translation->setContent($page->getContent());
  $translation->setBuilderData($page->getBuilderData());
  $translation->setBuilderCss($page->getBuilderCss());
  $em->persist($translation);$em->flush();
  $this->addFlash('success',$this->translator->translate('page.base_template_applied'));
  return $this->redirectToRoute('admin_page_translation_edit',['id'=>$page->getId(),'languageId'=>$languageId]);
 }
}

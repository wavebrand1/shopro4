<?php
declare(strict_types=1);
namespace App\Cms\Presentation\Http\Admin;
use App\Cms\Domain\Entity\MenuItem;use App\Cms\Domain\Entity\MenuItemTranslation;use App\Cms\Presentation\Form\MenuItemTranslationType;use App\Language\Domain\Entity\Language;use Doctrine\ORM\EntityManagerInterface;use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;use Symfony\Component\HttpFoundation\Request;use Symfony\Component\HttpFoundation\Response;use Symfony\Component\Routing\Attribute\Route;use Symfony\Component\Security\Http\Attribute\IsGranted;
#[Route('/admin/menu/{id}/translations')]
#[IsGranted('ROLE_ADMIN')]
final class MenuTranslationController extends AbstractController
{
 #[Route('',name:'admin_menu_translation_index',requirements:['id'=>'\d+'],methods:['GET'])]
 public function index(MenuItem $item,EntityManagerInterface $em):Response{$translations=[];foreach($em->getRepository(MenuItemTranslation::class)->findBy(['menuItem'=>$item]) as $translation)$translations[$translation->getLanguage()->getId()]=$translation;return $this->render('admin/menu/translations.html.twig',['item'=>$item,'languages'=>$em->getRepository(Language::class)->findBy(['active'=>true,'defaultLanguage'=>false],['name'=>'ASC']),'translations'=>$translations]);}
 #[Route('/{languageId}',name:'admin_menu_translation_edit',requirements:['id'=>'\d+','languageId'=>'\d+'],methods:['GET','POST'])]
 public function edit(MenuItem $item,int $languageId,Request $request,EntityManagerInterface $em):Response{$language=$em->find(Language::class,$languageId);if(!$language||$language->isDefaultLanguage())throw $this->createNotFoundException('Wersja językowa nie istnieje.');$translation=$em->getRepository(MenuItemTranslation::class)->findOneBy(['menuItem'=>$item,'language'=>$language])??new MenuItemTranslation($item,$language);$form=$this->createForm(MenuItemTranslationType::class,$translation);$form->handleRequest($request);if($form->isSubmitted()&&$form->isValid()){$em->persist($translation);$em->flush();$this->addFlash('success','Tłumaczenie pozycji menu zostało zapisane.');return $this->redirectToRoute('admin_menu_translation_index',['id'=>$item->getId()]);}return $this->render('admin/menu/translation_form.html.twig',['item'=>$item,'language'=>$language,'form'=>$form]);}
}

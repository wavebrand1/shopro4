<?php
declare(strict_types=1);
namespace App\Mail\Presentation\Http\Admin;
use App\Mail\Domain\Entity\EmailTemplate;
use App\Mail\Infrastructure\Persistence\Doctrine\EmailTemplateRepository;
use App\Mail\Presentation\Form\EmailTemplateType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
#[Route('/admin/configuration/email-templates')]
#[IsGranted('ROLE_ADMIN')]
final class EmailTemplateController extends AbstractController {
 #[Route('',name:'admin_email_template_index',methods:['GET'])] public function index(EmailTemplateRepository $r):Response{return $this->render('admin/email_template/index.html.twig',['templates'=>$r->findBy([],['name'=>'ASC'])]);}
 #[Route('/new',name:'admin_email_template_new',methods:['GET','POST'])] public function new(Request $q,EmailTemplateRepository $r):Response{return $this->form($q,new EmailTemplate(),$r);}
 #[Route('/{id}/edit',name:'admin_email_template_edit',requirements:['id'=>'\\d+'],methods:['GET','POST'])] public function edit(EmailTemplate $template,Request $q,EmailTemplateRepository $r):Response{return $this->form($q,$template,$r);}
 private function form(Request $q,EmailTemplate $template,EmailTemplateRepository $r):Response{$f=$this->createForm(EmailTemplateType::class,$template);$f->handleRequest($q);if($f->isSubmitted()&&$f->isValid()){$r->save($template);$this->addFlash('success','Szablon wiadomości został zapisany.');return $this->redirectToRoute('admin_email_template_index');}return $this->render('admin/email_template/form.html.twig',['form'=>$f,'template'=>$template]);}
}

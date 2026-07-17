<?php
declare(strict_types=1);
namespace App\Newsletter\Presentation\Http\Admin;

use App\Identity\Domain\Entity\AdminUser;
use App\Newsletter\Application\Message\SendNewsletterDelivery;
use App\Newsletter\Domain\Entity\NewsletterCampaign;
use App\Newsletter\Domain\Entity\NewsletterDelivery;
use App\Newsletter\Presentation\Form\NewsletterCampaignType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/newsletter')]
#[IsGranted('ROLE_ADMIN')]
final class NewsletterController extends AbstractController
{
    #[Route('', name:'admin_newsletter_index', methods:['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $campaigns=$em->getRepository(NewsletterCampaign::class)->findBy([],['id'=>'DESC']); $counts=[];
        foreach($campaigns as $campaign) $counts[$campaign->getId()]=$em->getRepository(NewsletterDelivery::class)->count(['campaign'=>$campaign]);
        return $this->render('admin/newsletter/index.html.twig',['campaigns'=>$campaigns,'delivery_counts'=>$counts,'deliveries'=>$em->getRepository(NewsletterDelivery::class)->findBy([],['id'=>'DESC'],100)]);
    }

    #[Route('/new', name:'admin_newsletter_new', methods:['GET','POST'])]
    public function new(Request $request,EntityManagerInterface $em): Response
    {
        $campaign=new NewsletterCampaign(); $form=$this->createForm(NewsletterCampaignType::class,$campaign); $form->handleRequest($request);
        if($form->isSubmitted()&&$form->isValid()){ $em->persist($campaign);$em->flush();$this->addFlash('success','Kampania została zapisana jako szkic.');return $this->redirectToRoute('admin_newsletter_index'); }
        return $this->render('admin/newsletter/form.html.twig',['form'=>$form]);
    }

    #[Route('/{id}/queue', name:'admin_newsletter_queue', requirements:['id'=>'\d+'], methods:['POST'])]
    public function queue(NewsletterCampaign $campaign,Request $request,EntityManagerInterface $em,MessageBusInterface $bus): Response
    {
        if(!$this->isCsrfTokenValid('queue-newsletter-'.$campaign->getId(),(string)$request->request->get('_token'))||$em->getRepository(NewsletterDelivery::class)->count(['campaign'=>$campaign])>0)return $this->redirectToRoute('admin_newsletter_index');
        $emails=$this->recipientEmails($campaign,$em);
        foreach($emails as $email){$delivery=new NewsletterDelivery($campaign,$email);$em->persist($delivery);$em->flush();$bus->dispatch(new SendNewsletterDelivery((int)$delivery->getId()));}
        count($emails)===0?$campaign->markWithoutRecipients():$campaign->markQueued();$em->flush();
        $this->addFlash(count($emails)===0?'error':'success',count($emails)===0?'Nie wybrano żadnego prawidłowego odbiorcy.':sprintf('Zakolejkowano %d wiadomości.',count($emails)));
        return $this->redirectToRoute('admin_newsletter_index');
    }

    #[Route('/{id}/retry', name:'admin_newsletter_retry', requirements:['id'=>'\d+'], methods:['POST'])]
    public function retry(NewsletterCampaign $campaign,Request $request,EntityManagerInterface $em,MessageBusInterface $bus): Response
    {
        if(!$this->isCsrfTokenValid('retry-newsletter-'.$campaign->getId(),(string)$request->request->get('_token')))return $this->redirectToRoute('admin_newsletter_index');
        $failed=$em->getRepository(NewsletterDelivery::class)->findBy(['campaign'=>$campaign,'status'=>'failed']);
        foreach($failed as $delivery){$delivery->markQueued();$bus->dispatch(new SendNewsletterDelivery((int)$delivery->getId()));}
        if($failed){$campaign->markQueued();$em->flush();$this->addFlash('success',sprintf('Ponowiono %d nieudanych wiadomości.',count($failed)));}
        else $this->addFlash('error','Ta kampania nie ma nieudanych wiadomości do ponowienia.');
        return $this->redirectToRoute('admin_newsletter_index');
    }

    /** @return list<string> */
    private function recipientEmails(NewsletterCampaign $campaign,EntityManagerInterface $em): array
    {
        $emails=$campaign->getCustomEmails();
        if($campaign->isIncludeSubscribers())foreach($em->getRepository(AdminUser::class)->findBy(['active'=>true,'newsletter'=>true]) as $user)$emails[]=$user->getEmail();
        if($campaign->getSelectedUserIds())foreach($em->getRepository(AdminUser::class)->findBy(['id'=>$campaign->getSelectedUserIds()]) as $user)$emails[]=$user->getEmail();
        $emails=array_map(static fn(string $email):string=>mb_strtolower(trim($email)),$emails);
        return array_values(array_unique(array_filter($emails,static fn(string $email):bool=>filter_var($email,FILTER_VALIDATE_EMAIL)!==false)));
    }
}

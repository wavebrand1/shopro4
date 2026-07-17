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
    #[Route('', name: 'admin_newsletter_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        return $this->render('admin/newsletter/index.html.twig', [
            'campaigns' => $em->getRepository(NewsletterCampaign::class)->findBy([], ['id' => 'DESC']),
            'deliveries' => $em->getRepository(NewsletterDelivery::class)->findBy([], ['id' => 'DESC'], 100),
        ]);
    }

    #[Route('/new', name: 'admin_newsletter_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $campaign = new NewsletterCampaign(); $form = $this->createForm(NewsletterCampaignType::class, $campaign); $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) { $em->persist($campaign); $em->flush(); $this->addFlash('success', 'Kampania została zapisana jako szkic.'); return $this->redirectToRoute('admin_newsletter_index'); }
        return $this->render('admin/newsletter/form.html.twig', ['form' => $form]);
    }

    #[Route('/{id}/queue', name: 'admin_newsletter_queue', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function queue(NewsletterCampaign $campaign, Request $request, EntityManagerInterface $em, MessageBusInterface $bus): Response
    {
        if (!$this->isCsrfTokenValid('queue-newsletter-'.$campaign->getId(), (string) $request->request->get('_token')) || $campaign->getStatus() !== 'draft') return $this->redirectToRoute('admin_newsletter_index');
        $recipients = $em->getRepository(AdminUser::class)->findBy(['active' => true, 'newsletter' => true]);
        foreach ($recipients as $recipient) { $delivery = new NewsletterDelivery($campaign, $recipient->getEmail()); $em->persist($delivery); $em->flush(); $bus->dispatch(new SendNewsletterDelivery((int) $delivery->getId())); }
        $campaign->markQueued(); $em->flush();
        $this->addFlash('success', sprintf('Zakolejkowano %d wiadomości.', count($recipients)));
        return $this->redirectToRoute('admin_newsletter_index');
    }
}

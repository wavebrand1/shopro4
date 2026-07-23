<?php

declare(strict_types=1);

namespace App\Newsletter\Presentation\Http\Admin;

use App\Identity\Domain\Entity\AdminUser;
use App\Language\Application\SystemTranslator;
use App\Mail\Infrastructure\Persistence\Doctrine\EmailTemplateRepository;
use App\Newsletter\Application\Message\SendNewsletterDelivery;
use App\Newsletter\Application\RecipientCsvImporter;
use App\Newsletter\Application\CampaignTestMailer;
use App\Newsletter\Application\FailedNewsletterMessageCleaner;
use App\Mail\Application\EmailLayoutRenderer;
use App\Module\Application\RequiresModule;
use App\Newsletter\Domain\Entity\NewsletterCampaign;
use App\Newsletter\Domain\Entity\NewsletterDelivery;
use App\Newsletter\Presentation\Form\NewsletterCampaignType;
use App\Settings\Application\SettingsProvider;
use App\Shared\Infrastructure\Messenger\QueueHealthInspector;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/newsletter')]
#[IsGranted('ROLE_ADMIN')]
#[RequiresModule('newsletter')]
final class NewsletterController extends AbstractController
{
    public function __construct(private readonly SystemTranslator $translator) {}

    #[Route('', name: 'admin_newsletter_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em, SettingsProvider $settings, QueueHealthInspector $queueHealth, FailedNewsletterMessageCleaner $failedMessages): Response
    {
        $limit = max(1, min(200, (int) $settings->get('per_page', 20)));
        $campaignTotal = $em->getRepository(NewsletterCampaign::class)->count([]);
        $campaignLastPage = max(1, (int) ceil($campaignTotal / $limit));
        $campaignPage = min($campaignLastPage, max(1, $request->query->getInt('campaign_page', 1)));
        $campaigns = $em->getRepository(NewsletterCampaign::class)->findBy(
            [],
            ['id' => 'DESC'],
            $limit,
            ($campaignPage - 1) * $limit,
        );
        $counts = [];
        foreach ($campaigns as $campaign) {
            $counts[$campaign->getId()] = $em->getRepository(NewsletterDelivery::class)->count(['campaign' => $campaign]);
        }

        $deliveryTotal = $em->getRepository(NewsletterDelivery::class)->count([]);
        $deliveryLastPage = max(1, (int) ceil($deliveryTotal / $limit));
        $deliveryPage = min($deliveryLastPage, max(1, $request->query->getInt('delivery_page', 1)));

        try {
            $failedEntries = $failedMessages->entries();
        } catch (\Throwable) {
            $failedEntries = [];
        }

        return $this->render('admin/newsletter/index.html.twig', [
            'campaigns' => $campaigns,
            'delivery_counts' => $counts,
            'campaign_current_page' => $campaignPage,
            'campaign_last_page' => $campaignLastPage,
            'deliveries' => $em->getRepository(NewsletterDelivery::class)->findBy(
                [],
                ['id' => 'DESC'],
                $limit,
                ($deliveryPage - 1) * $limit,
            ),
            'delivery_current_page' => $deliveryPage,
            'delivery_last_page' => $deliveryLastPage,
            'queue_health' => $queueHealth->inspect(),
            'failed_messages' => $failedEntries,
        ]);
    }

    #[Route('/queue/reconcile', name: 'admin_newsletter_queue_reconcile', methods: ['POST'])]
    public function reconcileQueue(Request $request, FailedNewsletterMessageCleaner $cleaner): Response
    {
        if (!$this->isCsrfTokenValid('newsletter-queue-reconcile', (string) $request->request->get('_token'))) {
            $request->attributes->set('_shopro_queue_outcome', 'invalid_csrf');
            $this->addFlash('error', $this->translator->translate('newsletter.queue_reconcile_invalid'));

            return $this->redirectToRoute('admin_newsletter_index');
        }

        try {
            $removed = $cleaner->removeResolved();
            $request->attributes->set('_shopro_queue_outcome', 'applied');
            $request->attributes->set('_shopro_queue_removed_count', $removed);
            $this->addFlash('success', sprintf($this->translator->translate('newsletter.queue_reconciled'), $removed));
        } catch (\Throwable) {
            $request->attributes->set('_shopro_queue_outcome', 'failed');
            $this->addFlash('error', $this->translator->translate('newsletter.queue_reconcile_failed'));
        }

        return $this->redirectToRoute('admin_newsletter_index');
    }

    #[Route('/queue/failed/{id}/retry', name: 'admin_newsletter_queue_failed_retry', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function retryFailedMessage(string $id, Request $request, FailedNewsletterMessageCleaner $cleaner, MessageBusInterface $bus): Response
    {
        if (!$this->isCsrfTokenValid('newsletter-failed-retry-'.$id, (string) $request->request->get('_token'))) {
            $request->attributes->set('_shopro_queue_outcome', 'invalid_csrf');
            $this->addFlash('error', $this->translator->translate('newsletter.queue_reconcile_invalid'));
        } else {
            try {
                $retried = $cleaner->retry($id, $bus);
                $request->attributes->set('_shopro_queue_outcome', $retried ? 'applied' : 'not_found');
                $this->addFlash($retried ? 'success' : 'error', $this->translator->translate($retried ? 'newsletter.failed_retried' : 'newsletter.failed_missing'));
            } catch (\Throwable) {
                $request->attributes->set('_shopro_queue_outcome', 'failed');
                $this->addFlash('error', $this->translator->translate('newsletter.failed_retry_error'));
            }
        }

        return $this->redirectToRoute('admin_newsletter_index');
    }

    #[Route('/queue/failed/{id}/remove', name: 'admin_newsletter_queue_failed_remove', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function removeFailedMessage(string $id, Request $request, FailedNewsletterMessageCleaner $cleaner): Response
    {
        if (!$this->isCsrfTokenValid('newsletter-failed-remove-'.$id, (string) $request->request->get('_token'))) {
            $request->attributes->set('_shopro_queue_outcome', 'invalid_csrf');
            $this->addFlash('error', $this->translator->translate('newsletter.queue_reconcile_invalid'));
        } else {
            try {
                $removed = $cleaner->removeById($id);
                $request->attributes->set('_shopro_queue_outcome', $removed ? 'applied' : 'not_found');
                $this->addFlash($removed ? 'success' : 'error', $this->translator->translate($removed ? 'newsletter.failed_removed' : 'newsletter.failed_missing'));
            } catch (\Throwable) {
                $request->attributes->set('_shopro_queue_outcome', 'failed');
                $this->addFlash('error', $this->translator->translate('newsletter.failed_remove_error'));
            }
        }

        return $this->redirectToRoute('admin_newsletter_index');
    }

    #[Route('/new', name: 'admin_newsletter_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, RecipientCsvImporter $csvImporter, EmailTemplateRepository $emailTemplates): Response
    {
        $campaign = new NewsletterCampaign();
        $form = $this->createForm(NewsletterCampaignType::class, $campaign);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->applyRecipientFile($form->get('recipientFile')->getData(), $campaign, $csvImporter)) return $this->renderForm($form, $campaign, $emailTemplates);
            $em->persist($campaign);
            $em->flush();
            $this->addFlash('success', $this->translator->translate('newsletter.draft_saved'));

            return $this->redirectToRoute('admin_newsletter_index');
        }

        return $this->renderForm($form, $campaign, $emailTemplates);
    }

    #[Route('/{id}', name: 'admin_newsletter_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(NewsletterCampaign $campaign, Request $request, EntityManagerInterface $em, SettingsProvider $settings): Response
    {
        $counts = ['queued' => 0, 'sent' => 0, 'failed' => 0];
        $countRows = $em->createQueryBuilder()
            ->select('delivery.status AS status, COUNT(delivery.id) AS total')
            ->from(NewsletterDelivery::class, 'delivery')
            ->where('delivery.campaign = :campaign')
            ->setParameter('campaign', $campaign)
            ->groupBy('delivery.status')
            ->getQuery()
            ->getArrayResult();
        foreach ($countRows as $row) {
            $counts[(string) $row['status']] = (int) $row['total'];
        }
        $deliveryTotal = array_sum($counts);
        $limit = max(1, min(200, (int) $settings->get('per_page', 20)));
        $lastPage = max(1, (int) ceil($deliveryTotal / $limit));
        $page = min($lastPage, max(1, $request->query->getInt('page', 1)));
        $deliveries = $em->getRepository(NewsletterDelivery::class)->findBy(
            ['campaign' => $campaign],
            ['id' => 'DESC'],
            $limit,
            ($page - 1) * $limit,
        );

        return $this->render('admin/newsletter/show.html.twig', [
            'campaign' => $campaign,
            'deliveries' => $deliveries,
            'delivery_total' => $deliveryTotal,
            'counts' => $counts,
            'current_page' => $page,
            'last_page' => $lastPage,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_newsletter_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(NewsletterCampaign $campaign, Request $request, EntityManagerInterface $em, RecipientCsvImporter $csvImporter, EmailTemplateRepository $emailTemplates): Response
    {
        $form = $this->createForm(NewsletterCampaignType::class, $campaign);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->applyRecipientFile($form->get('recipientFile')->getData(), $campaign, $csvImporter)) return $this->renderForm($form, $campaign, $emailTemplates);
            $em->flush();
            $this->addFlash('success', $this->translator->translate('newsletter.changes_saved'));

            return $this->redirectToRoute('admin_newsletter_show', ['id' => $campaign->getId()]);
        }

        return $this->renderForm($form, $campaign, $emailTemplates);
    }

    #[Route('/{id}/preview', name: 'admin_newsletter_preview', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function preview(NewsletterCampaign $campaign, EmailLayoutRenderer $layout): Response
    {
        return new Response($layout->render($campaign->getContent(), $campaign->getSubject()), headers: [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Security-Policy' => "default-src 'none'; img-src http: https: data:; style-src 'unsafe-inline'; base-uri 'none'; form-action 'none'; frame-ancestors 'self'",
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }

    #[Route('/{id}/test', name: 'admin_newsletter_test', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function sendTest(NewsletterCampaign $campaign, Request $request, CampaignTestMailer $mailer): Response
    {
        if (!$this->isCsrfTokenValid('test-newsletter-'.$campaign->getId(), (string) $request->request->get('_token'))) return $this->redirectToRoute('admin_newsletter_show', ['id' => $campaign->getId()]);
        $recipient = mb_strtolower(trim((string) $request->request->get('recipient')));
        if (filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
            $this->addFlash('error', $this->translator->translate('newsletter.test_invalid_email'));
            return $this->redirectToRoute('admin_newsletter_show', ['id' => $campaign->getId()]);
        }
        try {
            $mailer->send($campaign, $recipient);
            $this->addFlash('success', sprintf($this->translator->translate('newsletter.test_sent'), $recipient));
        } catch (\Throwable) {
            $this->addFlash('error', $this->translator->translate('newsletter.test_failed'));
        }
        return $this->redirectToRoute('admin_newsletter_show', ['id' => $campaign->getId()]);
    }

    #[Route('/{id}/queue', name: 'admin_newsletter_queue', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function queue(NewsletterCampaign $campaign, Request $request, EntityManagerInterface $em, MessageBusInterface $bus): Response
    {
        if (!$this->isCsrfTokenValid('queue-newsletter-'.$campaign->getId(), (string) $request->request->get('_token'))
            || $em->getRepository(NewsletterDelivery::class)->count(['campaign' => $campaign]) > 0) {
            return $this->redirectToRoute('admin_newsletter_index');
        }

        $count = $this->enqueue($campaign, $em, $bus);
        $this->addFlash($count === 0 ? 'error' : 'success', $count === 0
            ? $this->translator->translate('newsletter.no_valid_recipients')
            : sprintf($this->translator->translate('newsletter.queued_count'), $count));

        return $this->redirectToRoute('admin_newsletter_index');
    }

    #[Route('/{id}/retry', name: 'admin_newsletter_retry', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function retry(NewsletterCampaign $campaign, Request $request, EntityManagerInterface $em, MessageBusInterface $bus): Response
    {
        if (!$this->isCsrfTokenValid('retry-newsletter-'.$campaign->getId(), (string) $request->request->get('_token'))) {
            return $this->redirectToRoute('admin_newsletter_index');
        }
        $failed = $em->getRepository(NewsletterDelivery::class)->findBy(['campaign' => $campaign, 'status' => 'failed']);
        foreach ($failed as $delivery) {
            $delivery->markQueued();
            $bus->dispatch(new SendNewsletterDelivery((int) $delivery->getId()));
        }
        if ($failed) {
            $campaign->markQueued();
            $em->flush();
            $this->addFlash('success', sprintf($this->translator->translate('newsletter.retried_count'), count($failed)));
        } else {
            $this->addFlash('error', $this->translator->translate('newsletter.no_failed'));
        }

        return $this->redirectToRoute('admin_newsletter_show', ['id' => $campaign->getId()]);
    }

    #[Route('/{id}/resend', name: 'admin_newsletter_resend', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function resend(NewsletterCampaign $campaign, Request $request, EntityManagerInterface $em, MessageBusInterface $bus): Response
    {
        if (!$this->isCsrfTokenValid('resend-newsletter-'.$campaign->getId(), (string) $request->request->get('_token'))) {
            return $this->redirectToRoute('admin_newsletter_show', ['id' => $campaign->getId()]);
        }

        $repeated = new NewsletterCampaign();
        $repeated->setSubject($campaign->getSubject());
        $repeated->setContent($campaign->getContent());
        $repeated->setIncludeSubscribers($campaign->isIncludeSubscribers());
        $repeated->setSelectedUserIds($campaign->getSelectedUserIds());
        $repeated->setCustomEmails($campaign->getCustomEmails());
        $em->persist($repeated);
        $em->flush();

        $count = $this->enqueue($repeated, $em, $bus);
        $this->addFlash($count === 0 ? 'error' : 'success', $count === 0
            ? $this->translator->translate('newsletter.no_resend_recipients')
            : sprintf($this->translator->translate('newsletter.resend_queued_count'), $count));

        return $this->redirectToRoute('admin_newsletter_show', ['id' => $repeated->getId()]);
    }

    private function enqueue(NewsletterCampaign $campaign, EntityManagerInterface $em, MessageBusInterface $bus): int
    {
        $emails = $this->recipientEmails($campaign, $em);
        foreach ($emails as $email) {
            $delivery = new NewsletterDelivery($campaign, $email);
            $em->persist($delivery);
            $em->flush();
            $bus->dispatch(new SendNewsletterDelivery((int) $delivery->getId()));
        }
        count($emails) === 0 ? $campaign->markWithoutRecipients() : $campaign->markQueued();
        $em->flush();

        return count($emails);
    }

    /** @return list<string> */
    private function recipientEmails(NewsletterCampaign $campaign, EntityManagerInterface $em): array
    {
        $emails = $campaign->getCustomEmails();
        if ($campaign->isIncludeSubscribers()) {
            foreach ($em->getRepository(AdminUser::class)->findBy(['active' => true, 'newsletter' => true]) as $user) {
                $emails[] = $user->getEmail();
            }
        }
        if ($campaign->getSelectedUserIds()) {
            foreach ($em->getRepository(AdminUser::class)->findBy(['id' => $campaign->getSelectedUserIds()]) as $user) {
                $emails[] = $user->getEmail();
            }
        }
        $emails = array_map(static fn (string $email): string => mb_strtolower(trim($email)), $emails);

        return array_values(array_unique(array_filter($emails, static fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)));
    }

    private function applyRecipientFile(mixed $file, NewsletterCampaign $campaign, RecipientCsvImporter $importer): bool
    {
        if (!$file instanceof UploadedFile) return true;
        try {
            $imported = $importer->import($file->getPathname());
            $campaign->setCustomEmails([...$campaign->getCustomEmails(), ...$imported]);
            $this->addFlash('success', sprintf($this->translator->translate('newsletter.recipient_csv_imported'), count($imported)));
            return true;
        } catch (\Throwable) {
            $this->addFlash('error', $this->translator->translate('newsletter.recipient_csv_error'));
            return false;
        }
    }

    private function renderForm(mixed $form, NewsletterCampaign $campaign, EmailTemplateRepository $templates): Response
    {
        return $this->render('admin/newsletter/form.html.twig', [
            'form' => $form,
            'campaign' => $campaign,
            'email_templates' => $templates->findBy([], ['name' => 'ASC']),
        ]);
    }
}

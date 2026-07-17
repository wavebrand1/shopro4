<?php

declare(strict_types=1);

namespace App\Newsletter\Application\MessageHandler;

use App\Mail\Application\EmailLayoutRenderer;
use App\Newsletter\Application\DynamicMailerFactory;
use App\Newsletter\Application\Message\SendNewsletterDelivery;
use App\Newsletter\Application\UnsubscribeToken;
use App\Newsletter\Domain\Entity\NewsletterDelivery;
use App\Settings\Application\SettingsProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
final class SendNewsletterDeliveryHandler
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly DynamicMailerFactory $mailers, private readonly SettingsProvider $settings, private readonly UnsubscribeToken $tokens, private readonly EmailLayoutRenderer $layout) {}
    public function __invoke(SendNewsletterDelivery $message): void
    {
        $delivery = $this->em->find(NewsletterDelivery::class, $message->deliveryId);
        if (!$delivery || $delivery->getStatus() === 'sent') return;
        try {
            $token = $this->tokens->create($delivery->getRecipient());
            $unsubscribeUrl = rtrim((string) $this->settings->get('site_url'), '/').'/newsletter/unsubscribe/'.rawurlencode($token);
            $content = $delivery->getCampaign()->getContent().'<hr><p style="font-size:12px;color:#667085">Nie chcesz otrzymywać wiadomości? <a href="'.htmlspecialchars($unsubscribeUrl, ENT_QUOTES).'">Wypisz się</a>.</p>';
            $content = $this->layout->render($content, $delivery->getCampaign()->getSubject());
            $plainContent = $this->createPlainText($delivery->getCampaign()->getContent(), $unsubscribeUrl);
            $fromAddress = trim((string) ($this->settings->get('mail_from_address') ?: $this->settings->get('site_email')));
            if ($fromAddress === '') {
                throw new \RuntimeException('Skonfiguruj adres nadawcy wiadomości e-mail.');
            }
            $email = (new Email())
                ->from(sprintf('%s <%s>', $this->settings->get('mail_from_name', 'Shopro'), $fromAddress))
                ->to($delivery->getRecipient())
                ->subject($delivery->getCampaign()->getSubject())
                ->text($plainContent)
                ->html($content);
            $email->getHeaders()->addTextHeader('List-Unsubscribe', '<'.$unsubscribeUrl.'>');
            $email->getHeaders()->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
            if ($replyTo = $this->settings->get('mail_reply_to')) $email->replyTo($replyTo);
            $this->mailers->create()->send($email);
            $delivery->markSent();
            $this->em->flush();
            $this->updateCampaignStatus($delivery);
        } catch (\Throwable $exception) {
            $delivery->markFailed($exception->getMessage());
            $delivery->getCampaign()->markFailed();
            $this->em->flush();
            throw $exception;
        }
        $this->em->flush();
    }

    private function updateCampaignStatus(NewsletterDelivery $delivery): void
    {
        $campaign = $delivery->getCampaign();
        $queued = (int) $this->em->createQuery('SELECT COUNT(d.id) FROM App\\Newsletter\\Domain\\Entity\\NewsletterDelivery d WHERE d.campaign = :campaign AND d.status = :status')->setParameter('campaign', $campaign)->setParameter('status', 'queued')->getSingleScalarResult();
        if ($queued > 0) return;
        $failed = (int) $this->em->createQuery('SELECT COUNT(d.id) FROM App\\Newsletter\\Domain\\Entity\\NewsletterDelivery d WHERE d.campaign = :campaign AND d.status = :status')->setParameter('campaign', $campaign)->setParameter('status', 'failed')->getSingleScalarResult();
        $failed > 0 ? $campaign->markFailed() : $campaign->markCompleted();
    }

    private function createPlainText(string $html, string $unsubscribeUrl): string
    {
        $text = preg_replace('/<\s*br\s*\/?>/i', "\n", $html) ?? $html;
        $text = preg_replace('/<\/(p|div|h[1-6]|li|tr)>/i', "\n", $text) ?? $text;
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/\h*\R\h*/u', "\n", $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim($text)."\n\n---\nNie chcesz otrzymywać wiadomości? Wypisz się:\n".$unsubscribeUrl;
    }
}

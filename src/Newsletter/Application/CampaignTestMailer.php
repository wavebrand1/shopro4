<?php

declare(strict_types=1);

namespace App\Newsletter\Application;

use App\Mail\Application\EmailLayoutRenderer;
use App\Newsletter\Domain\Entity\NewsletterCampaign;
use App\Settings\Application\SettingsProvider;
use Symfony\Component\Mime\Email;

final class CampaignTestMailer
{
    public function __construct(
        private readonly DynamicMailerFactory $mailers,
        private readonly SettingsProvider $settings,
        private readonly EmailLayoutRenderer $layout,
    ) {}

    public function send(NewsletterCampaign $campaign, string $recipient): void
    {
        $fromAddress = trim((string) ($this->settings->get('mail_from_address') ?: $this->settings->get('site_email')));
        if ($fromAddress === '') throw new \RuntimeException('Skonfiguruj adres nadawcy wiadomości e-mail.');
        $subject = '[TEST] '.$campaign->getSubject();
        $email = (new Email())
            ->from(sprintf('%s <%s>', $this->settings->get('mail_from_name', 'Shopro'), $fromAddress))
            ->to($recipient)
            ->subject($subject)
            ->text($this->plainText($campaign->getContent()))
            ->html($this->layout->render($campaign->getContent(), $campaign->getSubject()));
        $email->getHeaders()->addTextHeader('X-Shopro-Message-Type', 'newsletter-test');
        if ($replyTo = $this->settings->get('mail_reply_to')) $email->replyTo($replyTo);
        $this->mailers->create()->send($email);
    }

    private function plainText(string $html): string
    {
        $text = preg_replace('/<\s*br\s*\/?>/i', "\n", $html) ?? $html;
        $text = preg_replace('/<\/(p|div|h[1-6]|li|tr)>/i', "\n", $text) ?? $text;
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim(preg_replace('/\n{3,}/', "\n\n", $text) ?? $text);
    }
}

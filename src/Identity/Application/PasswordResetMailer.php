<?php

declare(strict_types=1);

namespace App\Identity\Application;

use App\Identity\Domain\Entity\AdminUser;
use App\Mail\Application\EmailLayoutRenderer;
use App\Mail\Infrastructure\Persistence\Doctrine\EmailTemplateRepository;
use App\Newsletter\Application\DynamicMailerFactory;
use App\Settings\Application\SettingsProvider;
use Symfony\Component\Mime\Email;

final class PasswordResetMailer
{
    public function __construct(
        private readonly EmailTemplateRepository $templates,
        private readonly EmailLayoutRenderer $layout,
        private readonly DynamicMailerFactory $mailers,
        private readonly SettingsProvider $settings,
    ) {}

    public function send(AdminUser $user, string $resetUrl): void
    {
        $template = $this->templates->findOneBy(['code' => 'user_password_reminder']);
        if ($template === null) throw new \RuntimeException('Brak szablonu user_password_reminder.');
        $variables = ['[SITE_NAME]' => (string) $this->settings->get('site_name', 'Shopro'), '[USERNAME]' => $user->getUsername(), '[LINK]' => $resetUrl];
        $subject = strtr($template->getSubject(), $variables);
        $content = strtr($template->getContent(), $variables);
        $fromAddress = trim((string) ($this->settings->get('mail_from_address') ?: $this->settings->get('site_email')));
        if ($fromAddress === '') throw new \RuntimeException('Skonfiguruj adres nadawcy wiadomości e-mail.');

        $email = (new Email())
            ->from(sprintf('%s <%s>', $this->settings->get('mail_from_name', 'Shopro'), $fromAddress))
            ->to($user->getEmail())
            ->subject($subject)
            ->text(trim(html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8'))."\n\n".$resetUrl)
            ->html($this->layout->render($content, $subject));
        if ($replyTo = $this->settings->get('mail_reply_to')) $email->replyTo($replyTo);
        $this->mailers->create()->send($email);
    }
}

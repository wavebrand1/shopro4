<?php

declare(strict_types=1);

namespace App\Identity\Application;

use App\Identity\Domain\Entity\SiteUser;
use App\Mail\Application\EmailLayoutRenderer;
use App\Mail\Infrastructure\Persistence\Doctrine\EmailTemplateRepository;
use App\Newsletter\Application\DynamicMailerFactory;
use App\Settings\Application\SettingsProvider;
use Symfony\Component\Mime\Email;

final class SiteRegistrationMailer
{
    public function __construct(private readonly EmailTemplateRepository $templates, private readonly EmailLayoutRenderer $layout, private readonly DynamicMailerFactory $mailers, private readonly SettingsProvider $settings) {}
    public function sendActivation(SiteUser $user, string $url): void
    {
        $template = $this->templates->findOneBy(['code' => 'user_activate_account']);
        if (!$template) throw new \RuntimeException('Brak szablonu user_activate_account.');
        $variables = ['[SITE_NAME]' => (string) $this->settings->get('site_name', 'Shopro'), '[USERNAME]' => $user->getUsername(), '[EMAIL]' => $user->getEmail(), '[LINK]' => $url];
        $subject = strtr($template->getSubject(), $variables); $content = strtr($template->getContent(), $variables);
        $from = trim((string) ($this->settings->get('mail_from_address') ?: $this->settings->get('site_email')));
        if ($from === '') throw new \RuntimeException('Brak adresu nadawcy.');
        $email = (new Email())->from(sprintf('%s <%s>', $this->settings->get('mail_from_name', 'Shopro'), $from))->to($user->getEmail())->subject($subject)->text(trim(strip_tags($content))."\n\n".$url)->html($this->layout->render($content, $subject));
        if ($replyTo = $this->settings->get('mail_reply_to')) $email->replyTo($replyTo);
        $this->mailers->create()->send($email);
    }
}

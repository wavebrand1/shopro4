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
        $this->sendTemplate('user_activate_account', $user->getEmail(), $this->variables($user, $url));
    }
    public function sendWelcome(SiteUser $user, string $url): void
    {
        $this->sendTemplate('user_thans_for_registration', $user->getEmail(), $this->variables($user, $url));
    }
    public function sendAdminActivated(SiteUser $user, string $url): void
    {
        $this->sendTemplate('user_admin_activate_your_account', $user->getEmail(), $this->variables($user, $url));
    }
    public function notifyAdministrator(SiteUser $user, string $ip, string $url): void
    {
        $recipient = trim((string) $this->settings->get('site_email'));
        if ($recipient === '') throw new \RuntimeException('Brak adresu administratora.');
        $variables = $this->variables($user, $url) + ['[IP]' => $ip];
        $this->sendTemplate($user->isActive() ? 'admin_new_user' : 'admin_accept_new_user', $recipient, $variables);
    }
    /** @return array<string,string> */
    private function variables(SiteUser $user, string $url): array
    {
        return ['[SITE_NAME]' => (string) $this->settings->get('site_name', 'Shopro'), '[NAME]' => $user->getUsername(), '[USERNAME]' => $user->getUsername(), '[EMAIL]' => $user->getEmail(), '[LINK]' => $url, '[URL]' => $url];
    }
    /** @param array<string,string> $variables */
    private function sendTemplate(string $code, string $recipient, array $variables): void
    {
        $template = $this->templates->findOneBy(['code' => $code]);
        if (!$template) throw new \RuntimeException('Brak szablonu '.$code.'.');
        $subject = strtr($template->getSubject(), $variables); $content = strtr($template->getContent(), $variables);
        $from = trim((string) ($this->settings->get('mail_from_address') ?: $this->settings->get('site_email')));
        if ($from === '') throw new \RuntimeException('Brak adresu nadawcy.');
        $plain = trim(html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if (($variables['[LINK]'] ?? '') !== '') $plain .= "\n\n".$variables['[LINK]'];
        $email = (new Email())->from(sprintf('%s <%s>', $this->settings->get('mail_from_name', 'Shopro'), $from))->to($recipient)->subject($subject)->text($plain)->html($this->layout->render($content, $subject));
        if ($replyTo = $this->settings->get('mail_reply_to')) $email->replyTo($replyTo);
        $this->mailers->create()->send($email);
    }
}

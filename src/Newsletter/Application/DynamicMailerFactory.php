<?php

declare(strict_types=1);

namespace App\Newsletter\Application;

use App\Settings\Application\SettingsProvider;
use App\Settings\Application\SensitiveDataCipher;
use App\Settings\Infrastructure\Persistence\Doctrine\SystemSettingsRepository;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;

final class DynamicMailerFactory
{
    public function __construct(private readonly SettingsProvider $settings, private readonly SystemSettingsRepository $repository, private readonly SensitiveDataCipher $cipher) {}
    public function create(): MailerInterface
    {
        $host = (string) $this->settings->get('smtp_host');
        if ($host === '') throw new \RuntimeException('Skonfiguruj host SMTP w konfiguracji systemu.');
        $scheme = $this->settings->get('smtp_encryption') === 'smtps' ? 'smtps' : 'smtp';
        $user = rawurlencode((string) $this->settings->get('smtp_user'));
        $password = rawurlencode($this->cipher->decrypt($this->repository->get()->getSmtpPassword()));
        $auth = $user !== '' ? $user.':'.$password.'@' : '';
        $query = $this->settings->get('smtp_encryption') === 'none' ? '?auto_tls=false' : '';
        return new Mailer(Transport::fromDsn(sprintf('%s://%s%s:%d%s', $scheme, $auth, $host, (int) $this->settings->get('smtp_port', 587), $query)));
    }
}

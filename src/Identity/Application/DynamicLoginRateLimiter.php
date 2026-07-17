<?php

declare(strict_types=1);

namespace App\Identity\Application;

use App\Settings\Application\SettingsProvider;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\RateLimiter\AbstractRequestRateLimiter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\Policy\FixedWindowLimiter;
use Symfony\Component\RateLimiter\Storage\CacheStorage;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

/** Uses the limits configured by the administrator and protects both account and IP. */
final class DynamicLoginRateLimiter extends AbstractRequestRateLimiter
{
    public function __construct(
        private readonly SettingsProvider $settings,
        private readonly CacheItemPoolInterface $pool,
        #[\SensitiveParameter] private readonly string $kernelSecret,
    ) {}

    protected function getLimiters(Request $request): array
    {
        $attempts = max(1, min(100, (int) $this->settings->get('login_attempts', 5)));
        $seconds = max(10, min(86400, (int) $this->settings->get('flood_seconds', 60)));
        $interval = new \DateInterval(sprintf('PT%dS', $seconds));
        $storage = new CacheStorage($this->pool);
        $ip = $request->getClientIp() ?? 'unknown';
        $username = mb_strtolower((string) $request->attributes->get(SecurityRequestAttributes::LAST_USERNAME, ''), 'UTF-8');

        return [
            new FixedWindowLimiter('login-global-'.$this->hash($ip), $attempts * 5, $interval, $storage),
            new FixedWindowLimiter('login-local-'.$this->hash($username.'|'.$ip), $attempts, $interval, $storage),
        ];
    }

    private function hash(string $value): string
    {
        return substr(hash_hmac('sha256', $value, $this->kernelSecret), 0, 24);
    }
}

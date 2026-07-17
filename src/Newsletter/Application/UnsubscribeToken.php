<?php

declare(strict_types=1);

namespace App\Newsletter\Application;

final class UnsubscribeToken
{
    public function __construct(private readonly string $kernelSecret) {}
    public function create(string $email): string
    {
        $payload = mb_strtolower($email).'|'.time();
        return rtrim(strtr(base64_encode($payload.'|'.hash_hmac('sha256', $payload, $this->kernelSecret)), '+/', '-_'), '=');
    }
    public function verify(string $token, int $maxAge = 31536000): ?string
    {
        $encoded = strtr($token, '-_', '+/');
        $decoded = base64_decode($encoded.str_repeat('=', (4 - strlen($encoded) % 4) % 4), true);
        if (!$decoded) return null;
        $parts = explode('|', $decoded);
        if (count($parts) !== 3 || !ctype_digit($parts[1]) || (int) $parts[1] < time() - $maxAge) return null;
        $payload = $parts[0].'|'.$parts[1];
        return hash_equals(hash_hmac('sha256', $payload, $this->kernelSecret), $parts[2]) ? $parts[0] : null;
    }
}

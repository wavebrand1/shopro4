<?php

declare(strict_types=1);

namespace App\Newsletter\Application;

final class SafeDeliveryError
{
    private const MAX_LENGTH = 500;

    public function sanitize(string $message): string
    {
        $message = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/u', ' ', $message) ?? '';
        $message = preg_replace(
            '#([a-z][a-z0-9+.-]*://)[^@\s/:]+:[^@\s/]+@#iu',
            '$1[redacted]@',
            $message,
        ) ?? $message;
        $message = preg_replace(
            '/\b(password|passwd|pwd|token|api[_ -]?key|secret)(\s*[:=]\s*)([^\s;,]+)/iu',
            '$1$2[redacted]',
            $message,
        ) ?? $message;
        $message = trim(preg_replace('/\s+/u', ' ', $message) ?? '');

        return mb_substr($message !== '' ? $message : 'Unknown delivery error.', 0, self::MAX_LENGTH);
    }
}

<?php

declare(strict_types=1);

namespace App\Audit\Application;

final class AuditLogDataPresenter
{
    private const ALLOWED_KEYS = [
        'route',
        'method',
        'operation',
        'path',
        'item',
        'module',
        'requested_state',
        'outcome',
        'reason',
        'message_id',
        'removed_count',
    ];

    /** @param array<string, mixed> $data
     *  @return array<string, string>
     */
    public static function present(array $data): array
    {
        $result = [];
        foreach (self::ALLOWED_KEYS as $key) {
            $value = $data[$key] ?? null;
            if (!is_scalar($value) || is_bool($value)) continue;
            $value = trim((string) $value);
            if ($value === '') continue;
            $result[$key] = mb_substr($value, 0, 255);
        }

        return $result;
    }
}

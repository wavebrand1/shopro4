<?php

declare(strict_types=1);

namespace App\Audit\Application;

final class AdminAuditOperation
{
    public static function normalize(mixed $value): ?string
    {
        if (!is_string($value)) return null;
        $value = mb_strtolower(trim($value));

        return preg_match('/^[a-z0-9_.:-]{1,60}$/D', $value) === 1 ? $value : null;
    }

    public static function action(string $route, ?string $operation): string
    {
        return mb_substr($route.($operation !== null ? '.'.$operation : ''), 0, 120);
    }

    public static function isImportant(string $route, ?string $operation): bool
    {
        if ($operation !== null && in_array($operation, ['delete', 'clear', 'revoke', 'restore', 'reset'], true)) return true;

        return str_contains($route, 'delete') || str_contains($route, 'clear') || str_contains($route, 'revoke') || str_contains($route, 'restore');
    }
}

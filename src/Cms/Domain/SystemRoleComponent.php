<?php

declare(strict_types=1);

namespace App\Cms\Domain;

final class SystemRoleComponent
{
    public static function count(string $builderData): int
    {
        try {
            $data = json_decode($builderData, true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return 0;
        }

        return is_array($data) ? self::countInNode($data) : 0;
    }

    private static function countInNode(array $node): int
    {
        $count = ($node['type'] ?? null) === 'system_role' ? 1 : 0;
        foreach ($node as $value) {
            if (is_array($value)) $count += self::countInNode($value);
        }

        return $count;
    }
}

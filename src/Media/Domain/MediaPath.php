<?php

declare(strict_types=1);

namespace App\Media\Domain;

final class MediaPath
{
    public static function isSafePublicUploadUrl(string $url): bool
    {
        if (!str_starts_with($url, '/uploads/') || str_starts_with($url, '//')) return false;
        if (preg_match('/[\x00-\x20]/u', $url) || str_contains($url, '\\') || str_contains($url, '?') || str_contains($url, '#')) return false;
        if (preg_match('/%(?![0-9A-Fa-f]{2})/', $url)) return false;

        $decoded = rawurldecode($url);
        if (!str_starts_with($decoded, '/uploads/') || str_contains($decoded, "\0") || str_contains($decoded, '\\')) return false;
        foreach (explode('/', substr($decoded, 9)) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') return false;
        }

        return true;
    }
}

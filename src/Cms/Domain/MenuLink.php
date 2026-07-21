<?php

declare(strict_types=1);

namespace App\Cms\Domain;

final class MenuLink
{
    public static function isSafe(string $url): bool
    {
        if ($url === '' || preg_match('/[\x00-\x20\\\\]/u', $url)) return false;
        if (str_starts_with($url, '/')) return !str_starts_with($url, '//');
        if (str_starts_with($url, '#')) return 1 === preg_match('/^#[A-Za-z][A-Za-z0-9_.:~-]*$/', $url);
        if (str_starts_with(mb_strtolower($url), 'mailto:')) return false !== filter_var(substr($url, 7), FILTER_VALIDATE_EMAIL);
        if (str_starts_with(mb_strtolower($url), 'tel:')) return 1 === preg_match('/^tel:\+?[0-9][0-9(). -]{5,24}$/i', $url);
        if (false === filter_var($url, FILTER_VALIDATE_URL)) return false;

        return in_array(mb_strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
    }
}

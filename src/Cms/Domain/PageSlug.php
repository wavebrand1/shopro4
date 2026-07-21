<?php

declare(strict_types=1);

namespace App\Cms\Domain;

final class PageSlug
{
    /** @var list<string> */
    private const RESERVED = [
        'account',
        'activate',
        'activation',
        'admin',
        'api',
        'health',
        'language',
        'login',
        'logout',
        'newsletter',
        'password',
        'register',
        'search',
    ];

    private function __construct()
    {
    }

    public static function isReserved(string $slug): bool
    {
        return in_array(mb_strtolower(trim($slug)), self::RESERVED, true);
    }
}

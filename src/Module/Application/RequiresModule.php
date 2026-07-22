<?php

declare(strict_types=1);

namespace App\Module\Application;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final readonly class RequiresModule
{
    public function __construct(public string $code)
    {
        if (!preg_match('/^[a-z][a-z0-9_-]{1,79}$/', $code)) {
            throw new \InvalidArgumentException('Invalid required Shopro module code: '.$code);
        }
    }
}

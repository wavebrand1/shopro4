<?php

declare(strict_types=1);

namespace App\Module\Presentation\Http;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final readonly class RequiresModule
{
    public function __construct(public string $code) {}
}

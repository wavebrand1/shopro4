<?php

declare(strict_types=1);

namespace App\Module\Application;

final readonly class ModuleLifecycleDecision
{
    private function __construct(public bool $allowed, public ?string $reason = null) {}

    public static function allow(): self { return new self(true); }
    public static function deny(string $reason): self { return new self(false, $reason); }
}

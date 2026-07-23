<?php

declare(strict_types=1);

namespace App\Module\Application;

final readonly class ModuleIntegrityReport
{
    /**
     * @param list<array{code: string, reason: string, actual?: string, expected?: string}> $issues
     * @param array<string, string> $orphaned Map of module code to retained version.
     */
    public function __construct(public array $issues, public array $orphaned) {}

    public function isHealthy(): bool
    {
        return $this->issues === [];
    }
}

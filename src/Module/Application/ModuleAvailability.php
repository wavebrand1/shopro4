<?php

declare(strict_types=1);

namespace App\Module\Application;

interface ModuleAvailability
{
    public function isEnabled(string $code): bool;
}

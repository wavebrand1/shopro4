<?php

declare(strict_types=1);

namespace App\Module\Application;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('shopro.module_activity_probe')]
interface ModuleActivityProbe
{
    public function moduleCode(): string;
    /** @return list<string> */
    public function blockingReasons(): array;
}

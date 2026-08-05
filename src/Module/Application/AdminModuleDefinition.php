<?php

declare(strict_types=1);

namespace App\Module\Application;

/** Optional contract for modules exposing an administration entry point. */
interface AdminModuleDefinition extends ModuleDefinition
{
    public function adminRoute(): string;
    public function adminRoutePrefix(): string;
}

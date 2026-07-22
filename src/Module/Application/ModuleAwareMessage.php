<?php

declare(strict_types=1);

namespace App\Module\Application;

interface ModuleAwareMessage
{
    public function moduleCode(): string;
}

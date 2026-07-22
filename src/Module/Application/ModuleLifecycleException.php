<?php

declare(strict_types=1);

namespace App\Module\Application;

final class ModuleLifecycleException extends \LogicException
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct($reason);
    }
}

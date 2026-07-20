<?php

declare(strict_types=1);

namespace App\Module\Application;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class ModuleRegistry
{
    /** @var array<string, ModuleDefinition> */
    private array $modules = [];

    public function __construct(#[AutowireIterator('shopro.module')] iterable $modules)
    {
        foreach ($modules as $module) {
            if (isset($this->modules[$module->code()])) throw new \LogicException('Duplicate Shopro module code: '.$module->code());
            $this->modules[$module->code()] = $module;
        }
        ksort($this->modules);
    }

    /** @return array<string, ModuleDefinition> */
    public function all(): array { return $this->modules; }
    public function get(string $code): ?ModuleDefinition { return $this->modules[$code] ?? null; }
}

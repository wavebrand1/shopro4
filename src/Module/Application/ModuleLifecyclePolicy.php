<?php

declare(strict_types=1);

namespace App\Module\Application;

use App\Module\Domain\Entity\InstalledModule;

final readonly class ModuleLifecyclePolicy
{
    public function __construct(private ModuleRegistry $registry) {}

    /** @param array<string, InstalledModule> $installed
     *  @param list<string> $activityReasons
     */
    public function canDisable(string $code, array $installed, array $activityReasons = []): ModuleLifecycleDecision
    {
        $definition = $this->registry->get($code);
        if ($definition === null || !isset($installed[$code])) return ModuleLifecycleDecision::deny('module.lifecycle.not_installed');
        if (!$installed[$code]->isEnabled()) return ModuleLifecycleDecision::allow();
        if ($definition->required()) return ModuleLifecycleDecision::deny('module.lifecycle.required');

        foreach ($this->registry->all() as $dependentCode => $dependent) {
            if (!in_array($code, $dependent->dependencies(), true)) continue;
            if (($installed[$dependentCode] ?? null)?->isEnabled()) return ModuleLifecycleDecision::deny('module.lifecycle.active_dependent');
        }
        if ($activityReasons !== []) return ModuleLifecycleDecision::deny('module.lifecycle.active_work');

        return ModuleLifecycleDecision::allow();
    }

    /** @param array<string, InstalledModule> $installed */
    public function canEnable(string $code, array $installed): ModuleLifecycleDecision
    {
        $definition = $this->registry->get($code);
        if ($definition === null || !isset($installed[$code])) return ModuleLifecycleDecision::deny('module.lifecycle.not_installed');
        if ($installed[$code]->isEnabled()) return ModuleLifecycleDecision::allow();

        foreach ($definition->dependencies() as $dependency) {
            if (!(($installed[$dependency] ?? null)?->isEnabled())) return ModuleLifecycleDecision::deny('module.lifecycle.inactive_dependency');
        }

        return ModuleLifecycleDecision::allow();
    }
}

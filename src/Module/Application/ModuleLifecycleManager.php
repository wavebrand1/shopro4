<?php

declare(strict_types=1);

namespace App\Module\Application;

use App\Module\Infrastructure\Persistence\Doctrine\InstalledModuleRepository;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class ModuleLifecycleManager
{
    /** @var array<string, ModuleActivityProbe> */
    private array $probes;

    public function __construct(
        private ModuleLifecyclePolicy $policy,
        private InstalledModuleRepository $repository,
        #[AutowireIterator('shopro.module_activity_probe')] iterable $probes,
    ) {
        $indexed = [];
        foreach ($probes as $probe) {
            if (isset($indexed[$probe->moduleCode()])) throw new \LogicException('Duplicate module activity probe: '.$probe->moduleCode());
            $indexed[$probe->moduleCode()] = $probe;
        }
        $this->probes = $indexed;
    }

    public function disable(string $code): void
    {
        $denialReason = $this->repository->withLockedRegistry(function (array $installed) use ($code): ?string {
            $decision = $this->policy->canDisable($code, $installed, ($this->probes[$code] ?? null)?->blockingReasons() ?? []);
            if (!$decision->allowed) return $decision->reason ?? 'module.lifecycle.denied';
            $installed[$code]->disable();
            return null;
        });
        if ($denialReason !== null) throw new ModuleLifecycleException($denialReason);
    }

    public function enable(string $code): void
    {
        $denialReason = $this->repository->withLockedRegistry(function (array $installed) use ($code): ?string {
            $decision = $this->policy->canEnable($code, $installed);
            if (!$decision->allowed) return $decision->reason ?? 'module.lifecycle.denied';
            $installed[$code]->enable();
            return null;
        });
        if ($denialReason !== null) throw new ModuleLifecycleException($denialReason);
    }
}

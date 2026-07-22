<?php

declare(strict_types=1);

namespace App\Module\Application;

use App\Module\Infrastructure\Persistence\Doctrine\InstalledModuleRepository;

final readonly class ModuleRuntime implements ModuleAvailability
{
    public function __construct(private ModuleRegistry $registry, private InstalledModuleRepository $repository) {}

    public function isEnabled(string $code): bool
    {
        return $this->isEnabledWithDependencies($code, []);
    }

    /** @param list<string> $checking */
    private function isEnabledWithDependencies(string $code, array $checking): bool
    {
        if (in_array($code, $checking, true)) return false;

        $definition = $this->registry->get($code);
        if ($definition === null) return false;
        $state = $this->repository->find($code);
        if (!($state?->isEnabled() ?? $definition->required())) return false;

        $checking[] = $code;
        foreach ($definition->dependencies() as $dependency) {
            if (!$this->isEnabledWithDependencies($dependency, $checking)) return false;
        }

        return true;
    }
}

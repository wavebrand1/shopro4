<?php

declare(strict_types=1);

namespace App\Module\Application;

use App\Module\Infrastructure\Persistence\Doctrine\InstalledModuleRepository;

final readonly class ModuleRuntime implements ModuleAvailability
{
    public function __construct(private ModuleRegistry $registry, private InstalledModuleRepository $repository) {}

    public function isEnabled(string $code): bool
    {
        $definition = $this->registry->get($code);
        if ($definition === null) return false;
        $state = $this->repository->find($code);

        return $state?->isEnabled() ?? $definition->required();
    }
}

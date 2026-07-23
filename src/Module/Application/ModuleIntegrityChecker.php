<?php

declare(strict_types=1);

namespace App\Module\Application;

use App\Module\Infrastructure\Persistence\Doctrine\InstalledModuleRepository;

final readonly class ModuleIntegrityChecker
{
    public function __construct(
        private ModuleRegistry $registry,
        private InstalledModuleRepository $repository,
        private ModuleAvailability $runtime,
    ) {}

    public function check(): ModuleIntegrityReport
    {
        $installed = $this->repository->indexed();
        $issues = [];

        foreach ($this->registry->all() as $code => $definition) {
            $state = $installed[$code] ?? null;
            if ($state === null) {
                $issues[] = ['code' => $code, 'reason' => 'missing'];
                continue;
            }
            if ($state->getVersion() !== $definition->version()) {
                $issues[] = [
                    'code' => $code,
                    'reason' => 'version_mismatch',
                    'actual' => $state->getVersion(),
                    'expected' => $definition->version(),
                ];
                continue;
            }
            if ($definition->required() && !$state->isEnabled()) {
                $issues[] = ['code' => $code, 'reason' => 'required_disabled'];
                continue;
            }
            if ($state->isEnabled() && !$this->runtime->isEnabled($code)) {
                $issues[] = ['code' => $code, 'reason' => 'dependency_unavailable'];
            }
        }

        $orphaned = [];
        foreach (array_diff_key($installed, $this->registry->all()) as $code => $state) {
            $orphaned[$code] = $state->getVersion();
        }

        return new ModuleIntegrityReport($issues, $orphaned);
    }
}

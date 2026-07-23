<?php

declare(strict_types=1);

namespace App\Module\Application\Command;

use App\Module\Application\ModuleAvailability;
use App\Module\Application\ModuleRegistry;
use App\Module\Infrastructure\Persistence\Doctrine\InstalledModuleRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:modules:verify', description: 'Sprawdza spójność wersji, stanów i zależności modułów po wdrożeniu.')]
final class VerifyModulesCommand extends Command
{
    public function __construct(
        private readonly ModuleRegistry $registry,
        private readonly InstalledModuleRepository $repository,
        private readonly ModuleAvailability $runtime,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $installed = $this->repository->indexed();
        $invalid = 0;

        foreach ($this->registry->all() as $code => $definition) {
            $state = $installed[$code] ?? null;
            if ($state === null) {
                $output->writeln(sprintf('<error>%s: installation record is missing</error>', $code));
                ++$invalid;
                continue;
            }
            if ($state->getVersion() !== $definition->version()) {
                $output->writeln(sprintf('<error>%s: synchronized version %s, code provides %s</error>', $code, $state->getVersion(), $definition->version()));
                ++$invalid;
                continue;
            }
            if ($definition->required() && !$state->isEnabled()) {
                $output->writeln(sprintf('<error>%s: required module is disabled</error>', $code));
                ++$invalid;
                continue;
            }
            if ($state->isEnabled() && !$this->runtime->isEnabled($code)) {
                $output->writeln(sprintf('<error>%s: enabled module has an unavailable dependency</error>', $code));
                ++$invalid;
                continue;
            }

            $output->writeln(sprintf('<info>%s</info> %s', $code, $state->isEnabled() ? 'enabled' : 'disabled (optional)'));
        }

        foreach (array_diff_key($installed, $this->registry->all()) as $code => $state) {
            $output->writeln(sprintf('<comment>%s: orphaned record %s retained</comment>', $code, $state->getVersion()));
        }

        if ($invalid > 0) {
            $output->writeln(sprintf('<error>Module verification failed: %d problem(s).</error>', $invalid));
            return Command::FAILURE;
        }

        $output->writeln('<info>Module registry is consistent.</info>');
        return Command::SUCCESS;
    }
}

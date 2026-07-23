<?php

declare(strict_types=1);

namespace App\Module\Application\Command;

use App\Module\Application\ModuleRegistry;
use App\Module\Infrastructure\Persistence\Doctrine\InstalledModuleRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:modules:sync', description: 'Synchronizuje rejestr modułów dostarczanych przez kod ze stanem instalacji.')]
final class SyncModulesCommand extends Command
{
    public function __construct(private readonly ModuleRegistry $registry, private readonly InstalledModuleRepository $repository) { parent::__construct(); }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $versions = [];
        foreach ($this->registry->all() as $definition) {
            $versions[$definition->code()] = $definition->version();
        }

        $synchronized = $this->repository->synchronizeAll($versions);
        foreach ($synchronized as $module) {
            $output->writeln(sprintf('<info>%s</info> %s', $module->getCode(), $module->getVersion()));
        }

        return Command::SUCCESS;
    }
}

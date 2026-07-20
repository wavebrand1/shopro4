<?php

declare(strict_types=1);

namespace App\Module\Application\Command;

use App\Module\Application\ModuleRegistry;
use App\Module\Domain\Entity\InstalledModule;
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
        $installed = $this->repository->indexed();
        foreach ($this->registry->all() as $definition) {
            $module = $installed[$definition->code()] ?? new InstalledModule($definition->code(), $definition->version());
            $module->synchronize($definition->version());
            $this->repository->save($module);
            $output->writeln(sprintf('<info>%s</info> %s', $definition->code(), $definition->version()));
        }
        return Command::SUCCESS;
    }
}

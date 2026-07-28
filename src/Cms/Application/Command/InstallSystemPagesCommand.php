<?php

declare(strict_types=1);

namespace App\Cms\Application\Command;

use App\Cms\Application\SystemPageInstaller;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:pages:install-system', description: 'Creates missing CMS pages required by system roles.')]
final class InstallSystemPagesCommand extends Command
{
    public function __construct(private readonly SystemPageInstaller $installer)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = $this->installer->install();
        (new SymfonyStyle($input, $output))->success(sprintf(
            'System pages synchronized: %d created, %d existing, %d translations created.',
            $result['created'],
            $result['existing'],
            $result['translations'],
        ));

        return Command::SUCCESS;
    }
}

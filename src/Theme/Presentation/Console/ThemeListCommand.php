<?php

declare(strict_types=1);

namespace App\Theme\Presentation\Console;

use App\Theme\Application\ThemeRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('shopro:theme:list', 'Lists themes installed in the current Shopro runtime.')]
final class ThemeListCommand extends Command
{
    public function __construct(private readonly ThemeRegistry $themes) { parent::__construct(); }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $rows = [];
        foreach ($this->themes->all() as $theme) {
            $rows[] = [$theme->code, $theme->name, $theme->version, $theme->front ? 'yes' : 'no', $theme->admin ? 'yes' : 'no', implode(', ', array_keys($theme->variants)), $theme->system ? 'core' : 'package'];
        }
        $io->table(['Code', 'Name', 'Version', 'Front', 'Admin', 'Variants', 'Source'], $rows);

        return Command::SUCCESS;
    }
}

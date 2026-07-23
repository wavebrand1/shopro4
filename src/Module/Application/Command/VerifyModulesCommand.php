<?php

declare(strict_types=1);

namespace App\Module\Application\Command;

use App\Module\Application\ModuleIntegrityChecker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:modules:verify', description: 'Sprawdza spójność wersji, stanów i zależności modułów po wdrożeniu.')]
final class VerifyModulesCommand extends Command
{
    public function __construct(
        private readonly ModuleIntegrityChecker $integrity,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $report = $this->integrity->check();
        foreach ($report->issues as $issue) {
            $message = match ($issue['reason']) {
                'missing' => sprintf('%s: installation record is missing', $issue['code']),
                'version_mismatch' => sprintf('%s: synchronized version %s, code provides %s', $issue['code'], $issue['actual'], $issue['expected']),
                'required_disabled' => sprintf('%s: required module is disabled', $issue['code']),
                default => sprintf('%s: enabled module has an unavailable dependency', $issue['code']),
            };
            $output->writeln('<error>'.$message.'</error>');
        }

        foreach ($report->orphaned as $code => $version) {
            $output->writeln(sprintf('<comment>%s: orphaned record %s retained</comment>', $code, $version));
        }

        if (!$report->isHealthy()) {
            $output->writeln(sprintf('<error>Module verification failed: %d problem(s).</error>', count($report->issues)));
            return Command::FAILURE;
        }

        $output->writeln('<info>Module registry is consistent.</info>');
        return Command::SUCCESS;
    }
}

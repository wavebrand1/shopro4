<?php

declare(strict_types=1);

namespace App\Shared\Presentation\Console;

use App\Shared\Infrastructure\Messenger\QueueHealthInspector;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:queue:verify', description: 'Verifies message storage and the newsletter worker heartbeat.')]
final class VerifyQueueHealthCommand extends Command
{
    public function __construct(private readonly QueueHealthInspector $queueHealth)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $report = $this->queueHealth->inspect();
        $io->definitionList(
            ['State' => $report->state],
            ['Worker' => $report->workerState],
            ['Pending messages' => (string) $report->pending],
            ['Failed messages' => (string) $report->failed],
        );

        if ($report->blocksReadiness()) {
            $io->error('The message queue blocks release readiness.');
            return Command::FAILURE;
        }

        if ($report->failed > 0) {
            $io->warning('Failed messages require review, but resolved deliveries do not block the release.');
        }

        $io->success('The message queue is ready.');
        return Command::SUCCESS;
    }
}

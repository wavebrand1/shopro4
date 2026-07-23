<?php

declare(strict_types=1);

namespace App\Newsletter\Presentation\Console;

use App\Newsletter\Application\FailedNewsletterMessageCleaner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:newsletter:reconcile-failed', description: 'Removes resolved newsletter deliveries from the Messenger failure transport.')]
final class ReconcileFailedNewsletterMessagesCommand extends Command
{
    public function __construct(private readonly FailedNewsletterMessageCleaner $cleaner)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $removed = $this->cleaner->removeResolved();
        } catch (\Throwable $exception) {
            $output->writeln('<error>Nie można uzgodnić kolejki failed: '.htmlspecialchars($exception->getMessage(), ENT_QUOTES).'</error>');

            return Command::FAILURE;
        }

        $output->writeln(sprintf('<info>Usunięto rozwiązanych wiadomości z kolejki failed: %d.</info>', $removed));

        return Command::SUCCESS;
    }
}

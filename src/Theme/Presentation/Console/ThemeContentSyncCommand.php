<?php

declare(strict_types=1);

namespace App\Theme\Presentation\Console;

use App\Settings\Application\SettingsProvider;
use App\Theme\Application\ThemeContentSynchronizer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

#[AsCommand('app:themes:sync-content', 'Synchronizes upgradeable Page Builder content supplied by the active theme.')]
final class ThemeContentSyncCommand extends Command
{
    /** @param iterable<ThemeContentSynchronizer> $synchronizers */
    public function __construct(
        #[AutowireIterator('shopro.theme_content_synchronizer')] private readonly iterable $synchronizers,
        private readonly SettingsProvider $settings,
    ) { parent::__construct(); }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $theme = (string) $this->settings->get('theme', 'modernize');
        $updated = 0;
        foreach ($this->synchronizers as $synchronizer) {
            if ($synchronizer->themeCode() === $theme) $updated += $synchronizer->synchronize();
        }

        $output->writeln(sprintf('Theme content synchronized: %d page(s) updated.', $updated));

        return Command::SUCCESS;
    }
}

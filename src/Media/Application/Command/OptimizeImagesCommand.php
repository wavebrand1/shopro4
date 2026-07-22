<?php

declare(strict_types=1);

namespace App\Media\Application\Command;

use App\Media\Application\ResponsiveImageOptimizer;
use App\Module\Application\RequiresModule;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(name: 'app:images:optimize', description: 'Generuje responsywne warianty AVIF/WebP obrazów z public/uploads.')]
#[RequiresModule('media')]
final class OptimizeImagesCommand extends Command
{
    public function __construct(private readonly ResponsiveImageOptimizer $optimizer, private readonly KernelInterface $kernel) { parent::__construct(); }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $directory = $this->kernel->getProjectDir().'/public/uploads';
        if (!is_dir($directory)) { $io->note('Katalog public/uploads jeszcze nie istnieje.'); return Command::SUCCESS; }
        $finder = (new Finder())->files()->in($directory)->name('/\.(jpe?g|png|webp)$/i')->notName('/\.\d+\.(webp|avif)$/i');
        $files = 0; $variants = 0;
        foreach ($finder as $file) { ++$files; $variants += count($this->optimizer->optimize($file->getRealPath())); }
        $io->success(sprintf('Przetworzono %d obrazów, wygenerowano %d wariantów.', $files, $variants));
        return Command::SUCCESS;
    }
}

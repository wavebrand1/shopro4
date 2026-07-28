<?php

declare(strict_types=1);

namespace App\Shared\Presentation\Console;

use App\Cms\Infrastructure\Persistence\Doctrine\PageRepository;
use App\Identity\Infrastructure\Persistence\Doctrine\AdminUserRepository;
use App\Language\Domain\Entity\Language;
use App\Mail\Application\SystemEmailTemplateCatalog;
use App\Mail\Domain\Entity\EmailTemplate;
use App\Settings\Application\SettingsProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:release:verify', description: 'Verifies data and configuration required to publish Shopro.')]
final class VerifyReleaseReadinessCommand extends Command
{
    public function __construct(
        private readonly AdminUserRepository $administrators,
        private readonly PageRepository $pages,
        private readonly EntityManagerInterface $entityManager,
        private readonly SettingsProvider $settings,
        private readonly SystemEmailTemplateCatalog $emailTemplates,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('strict', null, InputOption::VALUE_NONE, 'Treat configuration warnings as release blockers.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $errors = [];
        $warnings = [];

        if ($this->administrators->countActiveAdministrators() < 1) {
            $errors[] = 'No active panel administrator exists.';
        }

        $languages = $this->entityManager->getRepository(Language::class);
        if ($languages->count(['active' => true, 'defaultLanguage' => true]) !== 1) {
            $errors[] = 'Exactly one active default language is required.';
        }

        if ($this->pages->findPublishedHomePage() === null) {
            $errors[] = 'No published homepage is assigned.';
        }

        if ($this->pages->findPublishedErrorPage() === null) {
            $warnings[] = 'No published custom 404 page is assigned; the built-in fallback will be used.';
        }

        $templateRepository = $this->entityManager->getRepository(EmailTemplate::class);
        $missingTemplates = [];
        foreach ($this->emailTemplates->codes() as $code) {
            if ($templateRepository->count(['code' => $code]) === 0) {
                $missingTemplates[] = $code;
            }
        }
        if ($missingTemplates !== []) {
            $errors[] = 'Missing system email templates: '.implode(', ', $missingTemplates).'.';
        }

        foreach ([
            'site_url' => 'Public site URL is not configured.',
            'site_email' => 'Site administrator email is not configured.',
            'mail_from_address' => 'Email sender address is not configured.',
            'smtp_host' => 'SMTP host is not configured.',
        ] as $key => $message) {
            if (trim((string) $this->settings->get($key, '')) === '') {
                $warnings[] = $message;
            }
        }

        foreach ($warnings as $warning) {
            $io->warning($warning);
        }
        foreach ($errors as $error) {
            $io->error($error);
        }

        if ($errors !== [] || ($input->getOption('strict') && $warnings !== [])) {
            $io->error(sprintf(
                'Release verification failed with %d error(s) and %d warning(s).',
                count($errors),
                count($warnings),
            ));

            return Command::FAILURE;
        }

        $io->success(sprintf('Release data is ready (%d warning(s)).', count($warnings)));

        return Command::SUCCESS;
    }
}

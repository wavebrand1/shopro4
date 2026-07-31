<?php

declare(strict_types=1);

namespace App\Installer\Presentation\Console;

use App\Identity\Domain\Entity\AdminUser;
use App\Settings\Infrastructure\Persistence\Doctrine\SystemSettingsRepository;
use App\Settings\Application\SettingsProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'shopro:install', description: 'Instaluje Shopro bez instalatora webowego (bez proc_open).')]
final class ShoproInstallCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SystemSettingsRepository $settingsRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
        $this->addArgument('site-name', InputArgument::REQUIRED, 'Nazwa witryny')
            ->addArgument('site-url', InputArgument::REQUIRED, 'Adres witryny')
            ->addArgument('admin-email', InputArgument::REQUIRED, 'E-mail administratora')
            ->addArgument('admin-username', InputArgument::REQUIRED, 'Login administratora')
            ->addArgument('admin-password', InputArgument::REQUIRED, 'Hasło administratora');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (is_file(dirname(__DIR__, 4).'/var/install.lock')) {
            $output->writeln('<error>Shopro jest już zainstalowane (var/install.lock).</error>');
            return Command::FAILURE;
        }

        $commands = ['doctrine:migrations:migrate', 'app:translations:sync', 'app:modules:sync', 'app:modules:verify', 'app:pages:install-system', 'assets:install', 'importmap:install', 'asset-map:compile', 'cache:clear'];
        foreach ($commands as $name) {
            $output->writeln('<info>Uruchamiam '.$name.'...</info>');
            $command = $this->getApplication()?->find($name);
            if (!$command) { $output->writeln('<error>Brak polecenia '.$name.'.</error>'); return Command::FAILURE; }
            $status = $command->run(new \Symfony\Component\Console\Input\ArrayInput(['command' => $name, '--no-interaction' => true]), $output);
            if ($status !== Command::SUCCESS) return $status;
        }

        $username = strtolower(trim((string) $input->getArgument('admin-username')));
        $email = strtolower(trim((string) $input->getArgument('admin-email')));
        $admin = $this->em->getRepository(AdminUser::class)->findOneBy(['username' => $username]) ?? new AdminUser($email, $username);
        $admin->setEmail($email);
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setActive(true);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, (string) $input->getArgument('admin-password')));
        $this->em->persist($admin);
        $settings = $this->settingsRepository->get();
        $config = array_replace(SettingsProvider::defaults(), [
            'site_name' => trim((string) $input->getArgument('site-name')),
            'site_url' => rtrim(trim((string) $input->getArgument('site-url')), '/'),
            'site_email' => $email,
            'language' => 'pl', 'locale' => 'pl_PL', 'show_language' => true,
        ]);
        $settings->setConfiguration($config);
        $this->em->persist($settings);
        $this->em->flush();

        $lock = dirname(__DIR__, 4).'/var/install.lock';
        file_put_contents($lock, json_encode(['installed_at' => date(DATE_ATOM), 'php' => PHP_VERSION, 'site_url' => $config['site_url']], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL, LOCK_EX);
        $output->writeln('<info>Instalacja zakończona. Login administratora: '.$username.'</info>');
        return Command::SUCCESS;
    }
}

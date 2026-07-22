<?php

declare(strict_types=1);

namespace App\Identity\Application\Command;

use App\Identity\Domain\Entity\AdminUser;
use App\Identity\Infrastructure\Persistence\Doctrine\AdminUserRepository;
use App\Module\Application\RequiresModule;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-admin', description: 'Tworzy konto administratora Shopro.')]
#[RequiresModule('identity')]
final class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly AdminUserRepository $users,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'Adres e-mail administratora');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = mb_strtolower(trim((string) $input->getArgument('email')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $output->writeln('<error>Podaj prawidłowy adres e-mail.</error>');
            return Command::INVALID;
        }
        if ($this->users->findOneBy(['email' => $email]) !== null) {
            $output->writeln('<error>Konto z tym adresem już istnieje.</error>');
            return Command::FAILURE;
        }

        $question = (new Question('Hasło: '))->setHidden(true)->setHiddenFallback(false);
        $password = (string) $this->getHelper('question')->ask($input, $output, $question);
        if (mb_strlen($password) < 12) {
            $output->writeln('<error>Hasło musi mieć co najmniej 12 znaków.</error>');
            return Command::INVALID;
        }

        $user = new AdminUser($email);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $this->users->save($user);
        $output->writeln('<info>Konto administratora zostało utworzone.</info>');

        return Command::SUCCESS;
    }
}

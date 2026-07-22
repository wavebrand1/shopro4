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

#[AsCommand(name: 'app:reset-user-password', description: 'Ustawia nowe hasło użytkownika wskazanego loginem lub adresem e-mail.')]
#[RequiresModule('identity')]
final class ResetUserPasswordCommand extends Command
{
    public function __construct(
        private readonly AdminUserRepository $users,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('identifier', InputArgument::REQUIRED, 'Login lub adres e-mail');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user = $this->users->loadUserByIdentifier((string) $input->getArgument('identifier'));
        if (!$user instanceof AdminUser) {
            $output->writeln('<error>Nie znaleziono aktywnego użytkownika.</error>');
            return Command::FAILURE;
        }

        $question = (new Question('Nowe hasło: '))->setHidden(true)->setHiddenFallback(false);
        $password = (string) $this->getHelper('question')->ask($input, $output, $question);
        if (mb_strlen($password) < 12) {
            $output->writeln('<error>Hasło musi mieć co najmniej 12 znaków.</error>');
            return Command::INVALID;
        }

        $user->setPassword($this->hasher->hashPassword($user, $password));
        $this->users->save($user);
        $output->writeln(sprintf('<info>Hasło użytkownika "%s" zostało zmienione.</info>', $user->getUsername()));

        return Command::SUCCESS;
    }
}

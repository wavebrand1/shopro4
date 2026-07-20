<?php

declare(strict_types=1);

namespace App\Backup\Application\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

#[AsCommand(name: 'app:backup:database', description: 'Tworzy skompresowany zrzut bazy MariaDB/MySQL bez ujawniania hasła w argumentach procesu.')]
final class BackupDatabaseCommand extends Command
{
    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('output', InputArgument::REQUIRED, 'Ścieżka docelowego pliku .sql.gz');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $target = (string) $input->getArgument('output');
        $directory = dirname($target);
        if ((!is_dir($directory) && !mkdir($directory, 0700, true)) || !is_writable($directory)) {
            $output->writeln('<error>Nie można zapisać kopii w katalogu docelowym.</error>');
            return Command::FAILURE;
        }

        $params = $this->connection->getParams();
        $database = (string) ($params['dbname'] ?? '');
        if ($database === '' || !str_contains((string) ($params['driver'] ?? ''), 'mysql')) {
            $output->writeln('<error>Automatyczny zrzut obsługuje skonfigurowaną bazę MariaDB/MySQL.</error>');
            return Command::FAILURE;
        }

        $finder = new ExecutableFinder();
        $executable = $finder->find('mariadb-dump') ?? $finder->find('mysqldump');
        if ($executable === null) {
            $output->writeln('<error>Nie znaleziono programu mariadb-dump ani mysqldump.</error>');
            return Command::FAILURE;
        }

        $command = [$executable, '--single-transaction', '--quick', '--skip-lock-tables', '--no-tablespaces', '--triggers', '--default-character-set=utf8mb4'];
        if (($params['host'] ?? '') !== '') array_push($command, '--host', (string) $params['host']);
        if (($params['port'] ?? '') !== '') array_push($command, '--port', (string) $params['port']);
        if (($params['unix_socket'] ?? '') !== '') array_push($command, '--socket', (string) $params['unix_socket']);
        if (($params['user'] ?? '') !== '') array_push($command, '--user', (string) $params['user']);
        $command[] = $database;

        $stream = gzopen($target, 'wb9');
        if ($stream === false) {
            $output->writeln('<error>Nie można utworzyć pliku zrzutu.</error>');
            return Command::FAILURE;
        }

        $stderr = '';
        try {
            $process = new Process($command, null, ['MYSQL_PWD' => (string) ($params['password'] ?? '')]);
            $process->setTimeout(300);
            $process->run(static function (string $type, string $data) use ($stream, &$stderr): void {
                if ($type === Process::OUT) gzwrite($stream, $data); else $stderr .= $data;
            });
        } finally {
            gzclose($stream);
        }

        if (!$process->isSuccessful() || !is_file($target) || filesize($target) === 0) {
            @unlink($target);
            $output->writeln('<error>Zrzut bazy nie powiódł się: '.htmlspecialchars(trim($stderr)).'</error>');
            return Command::FAILURE;
        }

        chmod($target, 0600);
        $output->writeln(sprintf('<info>Utworzono zrzut bazy (%s B).</info>', number_format((int) filesize($target), 0, ',', ' ')));
        return Command::SUCCESS;
    }
}

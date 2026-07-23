<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messenger;

use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class QueueHealthInspector
{
    public function __construct(
        private Connection $connection,
        #[Autowire('%kernel.project_dir%/var/queue-worker-heartbeat.json')]
        private string $heartbeatFile,
    ) {
    }

    public function inspect(): QueueHealthReport
    {
        $counts = [];
        $storageAvailable = true;
        try {
            foreach ($this->connection->fetchAllAssociative('SELECT queue_name, COUNT(*) AS message_count FROM messenger_messages GROUP BY queue_name') as $row) {
                $counts[(string) $row['queue_name']] = (int) $row['message_count'];
            }
        } catch (\Throwable) {
            $storageAvailable = false;
        }

        $heartbeat = null;
        if (is_file($this->heartbeatFile) && is_readable($this->heartbeatFile)) {
            try {
                $decoded = json_decode((string) file_get_contents($this->heartbeatFile), true, 8, JSON_THROW_ON_ERROR);
                $heartbeat = \is_array($decoded) ? $decoded : null;
            } catch (\Throwable) {
                $heartbeat = null;
            }
        }

        return QueueHealthReport::evaluate(
            $counts['async'] ?? 0,
            $counts['failed'] ?? 0,
            $storageAvailable,
            $heartbeat,
            new \DateTimeImmutable(),
        );
    }
}

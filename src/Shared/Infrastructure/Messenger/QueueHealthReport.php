<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messenger;

final readonly class QueueHealthReport
{
    public function __construct(
        public string $state,
        public string $workerState,
        public int $pending,
        public int $failed,
        public ?\DateTimeImmutable $lastRunAt,
        public ?int $exitCode,
        public bool $storageAvailable,
    ) {
    }

    public function blocksReadiness(): bool
    {
        return !$this->storageAvailable
            || ($this->pending > 0 && \in_array($this->workerState, ['missing', 'stale', 'error'], true));
    }

    public static function evaluate(
        int $pending,
        int $failed,
        bool $storageAvailable,
        ?array $heartbeat,
        \DateTimeImmutable $now,
        int $staleAfterSeconds = 180,
    ): self {
        $lastRunAt = null;
        $exitCode = null;
        $workerState = 'missing';

        if (\is_array($heartbeat)) {
            $exitCode = isset($heartbeat['exit_code']) && is_numeric($heartbeat['exit_code']) ? (int) $heartbeat['exit_code'] : null;
            try {
                $lastRunAt = isset($heartbeat['checked_at']) ? new \DateTimeImmutable((string) $heartbeat['checked_at']) : null;
            } catch (\Throwable) {
                $lastRunAt = null;
            }

            $recordedState = (string) ($heartbeat['status'] ?? '');
            if (\in_array($recordedState, ['running', 'success', 'error'], true) && $lastRunAt !== null) {
                $workerState = $recordedState;
            }
        }

        if ($lastRunAt !== null && $now->getTimestamp() - $lastRunAt->getTimestamp() > $staleAfterSeconds) {
            $workerState = 'stale';
        }

        $state = 'healthy';
        if (!$storageAvailable || ($pending > 0 && \in_array($workerState, ['missing', 'stale', 'error'], true))) {
            $state = 'error';
        } elseif ($failed > 0) {
            $state = 'warning';
        }

        return new self($state, $workerState, $pending, $failed, $lastRunAt, $exitCode, $storageAvailable);
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared;

use App\Shared\Infrastructure\Messenger\QueueHealthReport;
use PHPUnit\Framework\TestCase;

final class QueueHealthReportTest extends TestCase
{
    private \DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->now = new \DateTimeImmutable('2026-07-23T12:00:00+00:00');
    }

    public function testFreshSuccessfulWorkerIsHealthy(): void
    {
        $report = QueueHealthReport::evaluate(2, 0, true, [
            'status' => 'success', 'checked_at' => '2026-07-23T11:59:00+00:00', 'exit_code' => 0,
        ], $this->now);

        self::assertSame('healthy', $report->state);
        self::assertSame('success', $report->workerState);
    }

    public function testPendingMessagesAndStaleWorkerAreAnError(): void
    {
        $report = QueueHealthReport::evaluate(4, 0, true, [
            'status' => 'success', 'checked_at' => '2026-07-23T11:50:00+00:00', 'exit_code' => 0,
        ], $this->now);

        self::assertSame('error', $report->state);
        self::assertSame('stale', $report->workerState);
    }

    public function testFailedMessagesProduceWarningWhileWorkerIsHealthy(): void
    {
        $report = QueueHealthReport::evaluate(0, 3, true, [
            'status' => 'running', 'checked_at' => '2026-07-23T11:59:59+00:00', 'exit_code' => 0,
        ], $this->now);

        self::assertSame('warning', $report->state);
        self::assertSame(3, $report->failed);
    }

    public function testUnavailableStorageIsAlwaysAnError(): void
    {
        $report = QueueHealthReport::evaluate(0, 0, false, null, $this->now);

        self::assertSame('error', $report->state);
        self::assertFalse($report->storageAvailable);
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Unit\Audit;

use App\Audit\Application\AuditLogFilters;
use PHPUnit\Framework\TestCase;

final class AuditLogFiltersTest extends TestCase
{
    public function testNormalizesValidFilters(): void
    {
        $filters = AuditLogFilters::fromArray([
            'from' => '2026-07-20', 'to' => '2026-07-01', 'type' => 'site_user',
            'important' => '1', 'q' => '  login_failure  ',
        ]);

        self::assertSame('2026-07-01', $filters->from);
        self::assertSame('2026-07-20', $filters->to);
        self::assertSame('site_user', $filters->type);
        self::assertTrue($filters->important);
        self::assertSame('login_failure', $filters->query);
    }

    public function testRejectsInvalidValuesWithoutThrowing(): void
    {
        $filters = AuditLogFilters::fromArray([
            'from' => '2026-02-31', 'to' => 'not-a-date', 'type' => 'unknown',
            'important' => 'maybe', 'q' => '   ',
        ]);

        self::assertSame(['from' => '', 'to' => '', 'type' => '', 'important' => '', 'q' => ''], $filters->toQuery());
    }

    public function testLimitsSearchLength(): void
    {
        self::assertSame(100, mb_strlen(AuditLogFilters::fromArray(['q' => str_repeat('x', 150)])->query ?? ''));
    }
}

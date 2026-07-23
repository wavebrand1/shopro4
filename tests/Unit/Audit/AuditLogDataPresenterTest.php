<?php

declare(strict_types=1);

namespace App\Tests\Unit\Audit;

use App\Audit\Application\AuditLogDataPresenter;
use PHPUnit\Framework\TestCase;

final class AuditLogDataPresenterTest extends TestCase
{
    public function testOnlyPresentsExplicitlyAllowedScalarMetadata(): void
    {
        $presented = AuditLogDataPresenter::present([
            'route' => ' admin_page_edit ',
            'method' => 'POST',
            'operation' => 'save',
            'path' => '/images',
            'item' => 'photo.webp',
            'module' => 'newsletter',
            'requested_state' => 'disabled',
            'outcome' => 'denied',
            'reason' => 'module.lifecycle.active_work',
            'password' => 'secret',
            'token' => 'unsafe',
            'content' => ['large' => 'payload'],
        ]);

        self::assertSame([
            'route' => 'admin_page_edit',
            'method' => 'POST',
            'operation' => 'save',
            'path' => '/images',
            'item' => 'photo.webp',
            'module' => 'newsletter',
            'requested_state' => 'disabled',
            'outcome' => 'denied',
            'reason' => 'module.lifecycle.active_work',
        ], $presented);
    }

    public function testSkipsEmptyAndBooleanValuesAndLimitsLength(): void
    {
        $presented = AuditLogDataPresenter::present(['route' => str_repeat('x', 300), 'method' => '', 'operation' => true]);

        self::assertSame(255, mb_strlen($presented['route']));
        self::assertArrayNotHasKey('method', $presented);
        self::assertArrayNotHasKey('operation', $presented);
    }
}

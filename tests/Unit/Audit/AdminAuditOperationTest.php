<?php

declare(strict_types=1);

namespace App\Tests\Unit\Audit;

use App\Audit\Application\AdminAuditOperation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AdminAuditOperationTest extends TestCase
{
    #[DataProvider('operations')]
    public function testNormalizesOnlySafeOperationNames(mixed $value, ?string $expected): void
    {
        self::assertSame($expected, AdminAuditOperation::normalize($value));
    }

    public static function operations(): iterable
    {
        yield [' Upload ', 'upload'];
        yield ['delete', 'delete'];
        yield ['rename-file', 'rename-file'];
        yield ['<script>', null];
        yield [str_repeat('a', 61), null];
        yield [[], null];
    }

    public function testMarksDestructiveActionsAsImportant(): void
    {
        self::assertTrue(AdminAuditOperation::isImportant('admin_file_manager_index', 'delete'));
        self::assertTrue(AdminAuditOperation::isImportant('admin_page_revision_restore', null));
        self::assertTrue(AdminAuditOperation::isImportant('admin_module_disable', null));
        self::assertTrue(AdminAuditOperation::isImportant('admin_module_enable', null));
        self::assertFalse(AdminAuditOperation::isImportant('admin_file_manager_index', 'upload'));
        self::assertSame('admin_file_manager_index.rename', AdminAuditOperation::action('admin_file_manager_index', 'rename'));
        self::assertSame(120, mb_strlen(AdminAuditOperation::action(str_repeat('x', 140), null)));
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Unit\Cms;

use App\Cms\Domain\SystemRoleComponent;
use PHPUnit\Framework\TestCase;

final class SystemRoleComponentTest extends TestCase
{
    public function testItCountsNestedRoleComponents(): void
    {
        $builderData = json_encode([[
            'type' => 'layout_section',
            'data' => ['columns' => [[
                ['type' => 'system_role', 'data' => []],
                ['type' => 'rich_text', 'data' => ['content' => 'x']],
            ], [
                ['type' => 'system_role', 'data' => []],
            ]]],
        ]], JSON_THROW_ON_ERROR);

        self::assertSame(2, SystemRoleComponent::count($builderData));
        self::assertSame(0, SystemRoleComponent::count('invalid'));
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use PHPUnit\Framework\TestCase;

final class PageBuilderAsyncFieldContractTest extends TestCase
{
    public function testBuilderSupportsRemoteSingleAndMultipleFields(): void
    {
        $script = file_get_contents(dirname(__DIR__, 2).'/assets/component-builder.js');
        self::assertNotFalse($script);
        self::assertStringContainsString("definition.type==='async_select'", $script);
        self::assertStringContainsString("definition.type==='async_multiselect'", $script);
        self::assertStringContainsString('data-search-url=', $script);
        self::assertStringContainsString("['multiselect','async_multiselect']", $script);
    }
}

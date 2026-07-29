<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Cms\Application\PageBuilder\CmsPageBuilderComponentProvider;
use App\Cms\Application\PageBuilder\PageBuilderComponentDefinition;
use App\Theme\Infrastructure\ThemeComponentCatalog;
use PHPUnit\Framework\TestCase;

final class PageBuilderComponentCatalogTest extends TestCase
{
    public function testCoreProviderExposesOnlyThemeIndependentComponents(): void
    {
        $components = iterator_to_array((new CmsPageBuilderComponentProvider())->components());
        $types = array_map(static fn (PageBuilderComponentDefinition $component): string => $component->type, $components);

        self::assertSame($types, array_values(array_unique($types)));
        self::assertContains('rich_text', $types);
        self::assertContains('system_role', $types);
        self::assertContains('image', $types);
        self::assertContains('layout_section', $types);
        self::assertFalse($components[array_search('layout_section', $types, true)]->library);
        foreach ($components as $component) self::assertSame('cms', $component->moduleCode);
    }

    public function testBuiltInThemesProvideTheExistingVisualComponentCatalog(): void
    {
        $components = iterator_to_array(ThemeComponentCatalog::components());
        $types = array_map(static fn (PageBuilderComponentDefinition $component): string => $component->type, $components);

        self::assertSame($types, array_values(array_unique($types)));
        self::assertSame('homepage', $components[0]->type);
        self::assertTrue($components[0]->preset);
        self::assertContains('hero', $types);
        self::assertContains('feature_cards', $types);
        foreach ($components as $component) self::assertNull($component->moduleCode);
    }

    public function testDefinitionRejectsInvalidTechnicalType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new PageBuilderComponentDefinition('Bad type', 'cms', 'label', 'help', 'X');
    }
}

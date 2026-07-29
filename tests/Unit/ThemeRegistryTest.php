<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Theme\Application\ThemeDefinition;
use App\Theme\Application\ThemeProvider;
use App\Theme\Application\ThemeRegistry;
use App\Theme\Infrastructure\CoreThemeProvider;
use PHPUnit\Framework\TestCase;

final class ThemeRegistryTest extends TestCase
{
    public function testCoreThemesAreAvailableThroughTheSameRegistryAsExtensions(): void
    {
        $registry = new ThemeRegistry([new CoreThemeProvider()]);

        self::assertSame('modernize', $registry->frontChoices()['Shopro Modernize']);
        self::assertSame('classic', $registry->frontChoices()['Shopro Classic']);
        self::assertSame('modernize', $registry->adminChoices()['Shopro Modernize']);
        self::assertSame('compact', $registry->adminChoices()['Shopro 4.0 Compact']);
        self::assertSame('Niebieski', array_key_first($registry->variants('modernize')));
        self::assertSame('Orange', array_key_last($registry->variants('modernize', 'en')));
    }

    public function testExtensionThemeCanBeRegisteredAlongsideTheBuiltInTheme(): void
    {
        $provider = new class implements ThemeProvider {
            public function themes(): iterable
            {
                yield new ThemeDefinition('client_demo', 'Client Demo', '1.0.0', ['light' => ['pl' => 'Jasny', 'en' => 'Light']]);
            }
        };
        $registry = new ThemeRegistry([new CoreThemeProvider(), $provider]);

        self::assertSame('client_demo', $registry->frontChoices()['Client Demo']);
        self::assertSame(['Light' => 'light'], $registry->variants('client_demo', 'en'));
    }

    public function testDefinitionAcceptsOnlySafePublicThemeAssetPaths(): void
    {
        $theme = new ThemeDefinition('client_assets', 'Client assets', '1.0.0', ['light' => ['pl' => 'Jasny', 'en' => 'Light']], frontStylesheet: '/bundles/client/theme.css', frontJavascript: '/bundles/client/theme.js');
        self::assertSame('/bundles/client/theme.css', $theme->frontStylesheet);

        $this->expectException(\InvalidArgumentException::class);
        new ThemeDefinition('client_bad', 'Bad', '1.0.0', ['light' => ['pl' => 'Jasny', 'en' => 'Light']], frontStylesheet: '../theme.css');
    }
}

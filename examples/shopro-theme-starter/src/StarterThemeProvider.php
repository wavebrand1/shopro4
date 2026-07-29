<?php

declare(strict_types=1);

namespace Shopro\Theme\Starter;

use App\Theme\Application\ThemeDefinition;
use App\Theme\Application\ThemeProvider;

final class StarterThemeProvider implements ThemeProvider
{
    public function themes(): iterable
    {
        yield new ThemeDefinition(
            code: 'client_starter',
            name: 'Skórka klienta — starter',
            version: '1.0.0',
            variants: [
                'light' => ['pl' => 'Jasny', 'en' => 'Light'],
                'dark' => ['pl' => 'Ciemny', 'en' => 'Dark'],
            ],
            frontStylesheet: '/bundles/shoprothemestarter/theme.css',
            frontJavascript: '/bundles/shoprothemestarter/theme.js',
        );
    }
}

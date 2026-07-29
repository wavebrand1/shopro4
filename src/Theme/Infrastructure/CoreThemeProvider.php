<?php

declare(strict_types=1);

namespace App\Theme\Infrastructure;

use App\Theme\Application\ThemeDefinition;
use App\Theme\Application\ThemeProvider;

/** Themes supplied with core; external themes use exactly the same API. */
final class CoreThemeProvider implements ThemeProvider
{
    public function themes(): iterable
    {
        $variants = [
            'blue' => ['pl' => 'Niebieski', 'en' => 'Blue'],
            'violet' => ['pl' => 'Fioletowy', 'en' => 'Violet'],
            'emerald' => ['pl' => 'Zielony', 'en' => 'Green'],
            'orange' => ['pl' => 'Pomarańczowy', 'en' => 'Orange'],
        ];
        yield new ThemeDefinition('modernize', 'Shopro Modernize', '4.0.0', $variants, front: true, admin: true, system: true);
        yield new ThemeDefinition('classic', 'Shopro Classic', '4.0.0', $variants, front: true, system: true);
        yield new ThemeDefinition('compact', 'Shopro 4.0 Compact', '4.0.0', $variants, front: false, admin: true, system: true);
    }
}

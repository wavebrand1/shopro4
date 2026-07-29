<?php

declare(strict_types=1);

namespace App\Theme\Infrastructure;

use App\Cms\Application\PageBuilder\PageBuilderComponentDefinition;
use App\Cms\Application\PageBuilder\ThemePageBuilderComponentProvider;

/** Components supplied by the built-in Modernize front theme. */
final class ModernizePageBuilderComponentProvider implements ThemePageBuilderComponentProvider
{
    public function themeCode(): string { return 'modernize'; }
    public function components(): iterable { yield from ThemeComponentCatalog::components(); }
}

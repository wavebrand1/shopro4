<?php

declare(strict_types=1);

namespace App\Theme\Infrastructure;

use App\Cms\Application\PageBuilder\ThemePageBuilderComponentProvider;

/** Compatibility catalog for existing pages when Classic is selected. */
final class ClassicPageBuilderComponentProvider implements ThemePageBuilderComponentProvider
{
    public function themeCode(): string { return 'classic'; }
    public function components(): iterable { yield from ThemeComponentCatalog::components(); }
}

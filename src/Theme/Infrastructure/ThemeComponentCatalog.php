<?php

declare(strict_types=1);

namespace App\Theme\Infrastructure;

use App\Cms\Application\PageBuilder\PageBuilderComponentDefinition;

/** Shared compatibility catalog for the themes bundled with Shopro. */
final class ThemeComponentCatalog
{
    /** @return iterable<PageBuilderComponentDefinition> */
    public static function components(): iterable
    {
        yield new PageBuilderComponentDefinition('homepage', null, 'builder.full_homepage', 'builder.full_homepage_help', 'H', true);
        yield new PageBuilderComponentDefinition('feature_cards', null, 'builder.feature_cards', 'builder.feature_cards_help', 'F');
        yield new PageBuilderComponentDefinition('hero', null, 'Hero', 'builder.hero_help', 'H');
        yield new PageBuilderComponentDefinition('logo_bar', null, 'builder.logo_bar', 'builder.logo_bar_help', 'L');
        yield new PageBuilderComponentDefinition('process', null, 'builder.process', 'builder.process_help', '1');
        yield new PageBuilderComponentDefinition('audience', null, 'builder.audience', 'builder.audience_help', 'A');
        yield new PageBuilderComponentDefinition('cta', null, 'builder.cta', 'builder.cta_help', '>');
    }
}

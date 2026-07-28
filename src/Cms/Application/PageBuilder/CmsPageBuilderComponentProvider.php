<?php

declare(strict_types=1);

namespace App\Cms\Application\PageBuilder;

final class CmsPageBuilderComponentProvider implements PageBuilderComponentProvider
{
    public function components(): iterable
    {
        yield new PageBuilderComponentDefinition('homepage', 'cms', 'builder.full_homepage', 'builder.full_homepage_help', '⌂', true);
        yield new PageBuilderComponentDefinition('feature_cards', 'cms', 'builder.feature_cards', 'builder.feature_cards_help', '▦');
        yield new PageBuilderComponentDefinition('hero', 'cms', 'Hero', 'builder.hero_help', 'H');
        yield new PageBuilderComponentDefinition('logo_bar', 'cms', 'builder.logo_bar', 'builder.logo_bar_help', 'L');
        yield new PageBuilderComponentDefinition('process', 'cms', 'builder.process', 'builder.process_help', '1');
        yield new PageBuilderComponentDefinition('audience', 'cms', 'builder.audience', 'builder.audience_help', 'A');
        yield new PageBuilderComponentDefinition('cta', 'cms', 'builder.cta', 'builder.cta_help', '→');
        yield new PageBuilderComponentDefinition('rich_text', 'cms', 'builder.rich_text', 'builder.rich_text_help', 'T');
        yield new PageBuilderComponentDefinition('system_role', 'cms', 'builder.system_role', 'builder.system_role_help', 'R');
        yield new PageBuilderComponentDefinition('image', 'cms', 'builder.image', 'builder.image_help', '▧');
        yield new PageBuilderComponentDefinition('layout_section', 'cms', 'builder.add_section', 'builder.add_section_help', '§', library: false);
    }
}

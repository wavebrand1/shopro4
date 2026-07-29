<?php

declare(strict_types=1);

namespace App\Cms\Application\PageBuilder;

/** Components which are independent of any visual skin. */
final class CmsPageBuilderComponentProvider implements PageBuilderComponentProvider
{
    public function components(): iterable
    {
        yield new PageBuilderComponentDefinition('rich_text', 'cms', 'builder.rich_text', 'builder.rich_text_help', 'T', htmlFields: ['content']);
        yield new PageBuilderComponentDefinition('system_role', 'cms', 'builder.system_role', 'builder.system_role_help', 'R');
        yield new PageBuilderComponentDefinition('image', 'cms', 'builder.image', 'builder.image_help', 'I');
        yield new PageBuilderComponentDefinition('layout_section', 'cms', 'builder.add_section', 'builder.add_section_help', 'S', library: false);
    }
}

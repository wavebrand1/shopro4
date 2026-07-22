<?php

declare(strict_types=1);

namespace App\Cms\Application\PageBuilder;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('shopro.page_builder_component_provider')]
interface PageBuilderComponentProvider
{
    /** @return iterable<PageBuilderComponentDefinition> */
    public function components(): iterable;
}

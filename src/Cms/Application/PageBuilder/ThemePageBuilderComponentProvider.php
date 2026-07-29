<?php

declare(strict_types=1);

namespace App\Cms\Application\PageBuilder;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/** Extension point for components supplied by an installed theme package. */
#[AutoconfigureTag('shopro.theme_page_builder_component_provider')]
interface ThemePageBuilderComponentProvider
{
    public function themeCode(): string;

    /** @return iterable<PageBuilderComponentDefinition> */
    public function components(): iterable;
}

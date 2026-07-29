<?php

declare(strict_types=1);

namespace App\Theme\Application;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/** Implement this interface in a separately installed theme package. */
#[AutoconfigureTag('shopro.theme_provider')]
interface ThemeProvider
{
    /** @return iterable<ThemeDefinition> */
    public function themes(): iterable;
}

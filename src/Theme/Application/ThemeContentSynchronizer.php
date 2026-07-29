<?php

declare(strict_types=1);

namespace App\Theme\Application;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Lets an installable theme safely upgrade previously created page-builder
 * content after the theme itself gains a new required component.
 */
#[AutoconfigureTag('shopro.theme_content_synchronizer')]
interface ThemeContentSynchronizer
{
    public function themeCode(): string;

    /** Returns the number of updated pages. Must be safe to run repeatedly. */
    public function synchronize(): int;
}

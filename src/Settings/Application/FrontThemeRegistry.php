<?php

declare(strict_types=1);

namespace App\Settings\Application;

use App\Theme\Application\ThemeRegistry;

/**
 * Backwards-compatible facade for settings forms.
 * Theme definitions now come from tagged providers, including Composer themes.
 */
final class FrontThemeRegistry
{
    public function __construct(private readonly ThemeRegistry $themes) {}

    /** @return array<string, string> label => identifier */
    public function frontChoices(string $locale = 'pl'): array
    {
        return $this->themes->frontChoices($locale);
    }

    /** @return array<string, string> */
    public function frontVariantChoices(string $locale = 'pl'): array
    {
        return $this->allVariants($locale, true);
    }

    /** @return array<string, string> */
    public function adminChoices(string $locale = 'pl'): array
    {
        return $this->themes->adminChoices($locale);
    }

    /** @return array<string, string> */
    public function adminVariantChoices(string $locale = 'pl'): array
    {
        return $this->allVariants($locale, false);
    }

    /** @return array<string, string> */
    private function allVariants(string $locale, bool $front): array
    {
        $choices = [];
        foreach ($this->themes->all() as $theme) {
            if (($front && !$theme->front) || (!$front && !$theme->admin)) continue;
            foreach ($theme->variantChoices($locale) as $label => $code) $choices[$label] ??= $code;
        }

        return $choices;
    }
}

<?php

declare(strict_types=1);

namespace App\Settings\Application;

final class FrontThemeRegistry
{
    /** @return array<string, string> label => identifier */
    public function frontChoices(string $locale = 'pl'): array
    {
        return [
            'Shopro Modernize' => 'modernize',
            'Shopro Classic' => 'classic',
        ];
    }

    /** @return array<string, string> */
    public function frontVariantChoices(string $locale = 'pl'): array
    {
        return [
            ($locale === 'en' ? 'Blue' : 'Niebieski') => 'blue',
            ($locale === 'en' ? 'Violet' : 'Fioletowy') => 'violet',
            ($locale === 'en' ? 'Green' : 'Zielony') => 'emerald',
            ($locale === 'en' ? 'Orange' : 'Pomarańczowy') => 'orange',
        ];
    }

    /** @return array<string, string> */
    public function adminChoices(string $locale = 'pl'): array
    {
        return [
            'Shopro 4.0 Modernize' => 'modernize',
            'Shopro 4.0 Compact' => 'compact',
        ];
    }

    /** @return array<string, string> */
    public function adminVariantChoices(string $locale = 'pl'): array { return $this->frontVariantChoices($locale); }
}

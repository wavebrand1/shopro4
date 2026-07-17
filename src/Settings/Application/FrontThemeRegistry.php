<?php

declare(strict_types=1);

namespace App\Settings\Application;

final class FrontThemeRegistry
{
    /** @return array<string, string> label => identifier */
    public function frontChoices(): array
    {
        return [
            'Shopro Modernize' => 'modernize',
            'Shopro Classic' => 'classic',
        ];
    }

    /** @return array<string, string> */
    public function frontVariantChoices(): array
    {
        return [
            'Niebieski' => 'blue',
            'Fioletowy' => 'violet',
            'Zielony' => 'emerald',
            'Pomarańczowy' => 'orange',
        ];
    }

    /** @return array<string, string> */
    public function adminChoices(): array
    {
        return [
            'Shopro 4.0 Modernize' => 'modernize',
            'Shopro 4.0 Compact' => 'compact',
        ];
    }

    /** @return array<string, string> */
    public function adminVariantChoices(): array { return $this->frontVariantChoices(); }
}

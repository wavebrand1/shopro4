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
            'Domyślny (zgodność wsteczna)' => 'default',
        ];
    }

    /** @return array<string, string> */
    public function frontVariantChoices(): array
    {
        return [
            'Domyślny' => 'default',
        ];
    }

    /** @return array<string, string> */
    public function adminChoices(): array
    {
        return [
            'Shopro 4.0 Modernize' => 'modernize',
            'Domyślny (zgodność wsteczna)' => 'default',
        ];
    }
}

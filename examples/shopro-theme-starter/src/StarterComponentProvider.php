<?php

declare(strict_types=1);

namespace Shopro\Theme\Starter;

use App\Cms\Application\PageBuilder\PageBuilderComponentDefinition;
use App\Cms\Application\PageBuilder\ThemePageBuilderComponentProvider;

final class StarterComponentProvider implements ThemePageBuilderComponentProvider
{
    public function themeCode(): string { return 'client_starter'; }

    public function components(): iterable
    {
        yield new PageBuilderComponentDefinition(
            type: 'client_banner', moduleCode: null, label: 'Baner klienta',
            help: 'Przykładowy komponent dostarczany poza Shopro Core.', icon: 'B',
            template: '@ShoproThemeStarter/block/client_banner.html.twig', htmlFields: ['content'],
        );
    }
}

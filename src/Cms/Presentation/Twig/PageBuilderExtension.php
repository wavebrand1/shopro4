<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Twig;

use App\Cms\Application\PageBuilder\PageBuilderComponentRegistry;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class PageBuilderExtension extends AbstractExtension
{
    public function __construct(private readonly PageBuilderComponentRegistry $components) {}
    public function getFunctions(): array
    {
        return [
            new TwigFunction('shopro_builder_components', $this->components->enabled(...)),
            new TwigFunction('shopro_builder_component_groups', $this->components->grouped(...)),
            new TwigFunction('shopro_builder_component_enabled', $this->components->isRenderableEnabled(...)),
            new TwigFunction('shopro_builder_component_template', $this->components->template(...)),
            new TwigFunction('shopro_builder_editor_javascripts', $this->components->editorJavascripts(...)),
            new TwigFunction('shopro_builder_stylesheets', $this->components->stylesheets(...)),
        ];
    }
}

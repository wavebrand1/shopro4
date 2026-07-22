<?php

declare(strict_types=1);

namespace App\Module\Presentation\Twig;

use App\Module\Application\ModuleRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class ModuleExtension extends AbstractExtension
{
    public function __construct(private readonly ModuleRuntime $runtime) {}
    public function getFunctions(): array { return [new TwigFunction('shopro_module_enabled', $this->runtime->isEnabled(...))]; }
}

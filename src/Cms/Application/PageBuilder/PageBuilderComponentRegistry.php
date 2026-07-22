<?php

declare(strict_types=1);

namespace App\Cms\Application\PageBuilder;

use App\Module\Application\ModuleRegistry;
use App\Module\Application\ModuleRuntime;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class PageBuilderComponentRegistry
{
    /** @var array<string, PageBuilderComponentDefinition> */
    private array $components = [];

    public function __construct(
        #[AutowireIterator('shopro.page_builder_component_provider')] iterable $providers,
        ModuleRegistry $modules,
        private readonly ModuleRuntime $runtime,
    ) {
        foreach ($providers as $provider) {
            foreach ($provider->components() as $component) {
                if ($modules->get($component->moduleCode) === null) throw new \LogicException(sprintf('Page Builder component "%s" belongs to unknown module "%s".', $component->type, $component->moduleCode));
                if (isset($this->components[$component->type])) throw new \LogicException('Duplicate Page Builder component type: '.$component->type);
                $this->components[$component->type] = $component;
            }
        }
    }

    /** @return list<PageBuilderComponentDefinition> */
    public function enabled(): array
    {
        return array_values(array_filter($this->components, fn (PageBuilderComponentDefinition $component): bool => $this->runtime->isEnabled($component->moduleCode)));
    }

    /** @return array<string, PageBuilderComponentDefinition> */
    public function all(): array { return $this->components; }
}

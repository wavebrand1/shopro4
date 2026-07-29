<?php

declare(strict_types=1);

namespace App\Cms\Application\PageBuilder;

use App\Module\Application\ModuleRegistry;
use App\Module\Application\ModuleRuntime;
use App\Theme\Application\ThemeRegistry;
use App\Settings\Application\SettingsProvider;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class PageBuilderComponentRegistry
{
    /** @var array<string, PageBuilderComponentDefinition> */
    private array $components = [];
    /** @var array<string, string> component type => theme code */
    private array $themeComponents = [];

    public function __construct(
        #[AutowireIterator('shopro.page_builder_component_provider')] iterable $providers,
        #[AutowireIterator('shopro.theme_page_builder_component_provider')] iterable $themeProviders,
        ModuleRegistry $modules,
        ThemeRegistry $themes,
        private readonly SettingsProvider $settings,
        private readonly ModuleRuntime $runtime,
    ) {
        foreach ($providers as $provider) {
            foreach ($provider->components() as $component) {
                if ($component->moduleCode === null || $modules->get($component->moduleCode) === null) throw new \LogicException(sprintf('Page Builder component "%s" belongs to unknown module "%s".', $component->type, $component->moduleCode ?? 'none'));
                if (isset($this->components[$component->type])) throw new \LogicException('Duplicate Page Builder component type: '.$component->type);
                $this->components[$component->type] = $component;
            }
        }
        foreach ($themeProviders as $provider) {
            if ($themes->get($provider->themeCode()) === null) {
                throw new \LogicException(sprintf('Page Builder theme provider belongs to unknown theme "%s".', $provider->themeCode()));
            }
            foreach ($provider->components() as $component) {
                if ($component->moduleCode !== null && $modules->get($component->moduleCode) === null) {
                    throw new \LogicException(sprintf('Theme component "%s" belongs to unknown optional module "%s".', $component->type, $component->moduleCode));
                }
                if (isset($this->components[$component->type])) {
                    throw new \LogicException('Duplicate Page Builder component type: '.$component->type);
                }
                $this->components[$component->type] = $component;
                $this->themeComponents[$component->type] = $provider->themeCode();
            }
        }
    }

    /** @return list<PageBuilderComponentDefinition> */
    public function enabled(): array
    {
        return array_values(array_filter($this->components, fn (PageBuilderComponentDefinition $component): bool => $component->library && $this->isAvailable($component)));
    }

    public function isRenderableEnabled(string $type): bool
    {
        $component = $this->components[$type] ?? null;

        return $component !== null && !$component->preset && $this->isAvailable($component);
    }

    /** @return array<string, PageBuilderComponentDefinition> */
    public function all(): array { return $this->components; }

    private function isAvailable(PageBuilderComponentDefinition $component): bool
    {
        $theme = $this->themeComponents[$component->type] ?? null;
        if ($theme !== null && $theme !== (string) $this->settings->get('theme', 'modernize')) return false;

        return $component->moduleCode === null || $this->runtime->isEnabled($component->moduleCode);
    }
}

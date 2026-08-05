<?php

declare(strict_types=1);

namespace App\Cms\Application\PageBuilder;

use App\Module\Application\ModuleRegistry;
use App\Module\Application\ModuleRuntime;
use App\Settings\Application\SettingsProvider;
use App\Theme\Application\ThemeRegistry;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class PageBuilderComponentRegistry
{
    /** @var array<string, PageBuilderComponentDefinition> */
    private array $coreComponents = [];
    /** @var array<string, array<string, PageBuilderComponentDefinition>> */
    private array $themeComponents = [];
    /** @var array<string, string> */
    private array $moduleNames = [];

    public function __construct(
        #[AutowireIterator('shopro.page_builder_component_provider')] iterable $providers,
        #[AutowireIterator('shopro.theme_page_builder_component_provider')] iterable $themeProviders,
        ModuleRegistry $modules,
        ThemeRegistry $themes,
        private readonly SettingsProvider $settings,
        private readonly ModuleRuntime $runtime,
    ) {
        foreach ($modules->all() as $code => $module) $this->moduleNames[$code] = $module->name();
        foreach ($providers as $provider) foreach ($provider->components() as $component) {
            if ($component->moduleCode === null || $modules->get($component->moduleCode) === null) throw new \LogicException(sprintf('Page Builder component "%s" belongs to unknown module "%s".', $component->type, $component->moduleCode ?? 'none'));
            if (isset($this->coreComponents[$component->type])) throw new \LogicException('Duplicate core Page Builder component type: '.$component->type);
            $this->coreComponents[$component->type] = $component;
        }
        foreach ($themeProviders as $provider) {
            $theme = $provider->themeCode();
            if ($themes->get($theme) === null) throw new \LogicException(sprintf('Page Builder theme provider belongs to unknown theme "%s".', $theme));
            foreach ($provider->components() as $component) {
                if ($component->moduleCode !== null && $modules->get($component->moduleCode) === null) throw new \LogicException(sprintf('Theme component "%s" belongs to unknown optional module "%s".', $component->type, $component->moduleCode));
                if (isset($this->themeComponents[$theme][$component->type])) throw new \LogicException(sprintf('Duplicate Page Builder component type "%s" in theme "%s".', $component->type, $theme));
                $this->themeComponents[$theme][$component->type] = $component;
            }
        }
    }

    /** @return list<PageBuilderComponentDefinition> */
    public function enabled(?string $context = null): array { return array_values(array_filter($this->activeComponents(), fn (PageBuilderComponentDefinition $component): bool => $component->library && $this->isAvailable($component) && $this->supportsContext($component, $context))); }

    /** @return array<string, list<PageBuilderComponentDefinition>> */
    public function grouped(string $context): array
    {
        $groups = [];
        foreach ($this->enabled($context) as $component) $groups[$this->groupFor($component)][] = $component;
        return $groups;
    }

    public function isRenderableEnabled(string $type): bool
    {
        $component = $this->resolveRenderable($type);
        return $component !== null && !$component->preset && $this->isAvailable($component);
    }

    /** @return array<string, PageBuilderComponentDefinition> */
    public function all(): array { return $this->activeComponents(); }

    public function template(string $type): ?string
    {
        $component = $this->resolveRenderable($type);
        return $component !== null && $this->isAvailable($component) ? $component->template : null;
    }

    /** @return list<string> */
    public function htmlFields(string $type): array { return $this->resolve($type)?->htmlFields ?? []; }

    /** @return list<string> */
    public function editorJavascripts(): array
    {
        $scripts = [];
        foreach ($this->enabled() as $component) if ($component->editorJavascript !== null) $scripts[$component->editorJavascript] = true;
        return array_keys($scripts);
    }
    /** @return list<string> */
    public function stylesheets(): array
    {
        $styles = [];
        foreach ($this->enabled() as $component) if ($component->stylesheet !== null) $styles[$component->stylesheet] = true;
        return array_keys($styles);
    }

    /** @return array<string, PageBuilderComponentDefinition> */
    private function activeComponents(): array { return array_replace($this->coreComponents, $this->themeComponents[$this->activeTheme()] ?? []); }
    private function resolve(string $type): ?PageBuilderComponentDefinition { return ($this->themeComponents[$this->activeTheme()][$type] ?? null) ?? ($this->coreComponents[$type] ?? null); }
    private function resolveRenderable(string $type): ?PageBuilderComponentDefinition
    {
        $component = $this->resolve($type);
        if ($component !== null) return $component;
        foreach ($this->themeComponents as $components) if (isset($components[$type])) return $components[$type];

        return null;
    }
    private function activeTheme(): string { return (string) $this->settings->get('theme', 'modernize'); }
    private function isAvailable(PageBuilderComponentDefinition $component): bool { return $component->moduleCode === null || $this->runtime->isEnabled($component->moduleCode); }
    private function supportsContext(PageBuilderComponentDefinition $component, ?string $context): bool
    {
        if ($context === null || in_array('*', $component->contexts, true) || in_array($context, $component->contexts, true)) return true;
        foreach ($component->contexts as $allowed) if (str_ends_with($allowed, '.*') && str_starts_with($context, substr($allowed, 0, -1))) return true;
        return false;
    }
    private function groupFor(PageBuilderComponentDefinition $component): string
    {
        if ($component->group !== 'builder.group.builtin') return $component->group;
        if (($this->themeComponents[$this->activeTheme()][$component->type] ?? null) === $component) return 'builder.group.theme';
        if ($component->moduleCode !== null && $component->moduleCode !== 'cms') return 'Komponenty '.($this->moduleNames[$component->moduleCode] ?? ucfirst($component->moduleCode));
        return $component->group;
    }
}

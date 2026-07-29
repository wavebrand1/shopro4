<?php

declare(strict_types=1);

namespace App\Theme\Application;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class ThemeRegistry
{
    /** @var array<string, ThemeDefinition> */
    private array $themes = [];

    public function __construct(#[AutowireIterator('shopro.theme_provider')] iterable $providers)
    {
        foreach ($providers as $provider) foreach ($provider->themes() as $theme) {
            if (isset($this->themes[$theme->code])) throw new \LogicException('Duplicate Shopro theme code: '.$theme->code);
            $this->themes[$theme->code] = $theme;
        }
        if (!isset($this->themes['modernize'])) throw new \LogicException('The built-in "modernize" theme must be available.');
    }

    public function get(string $code): ?ThemeDefinition { return $this->themes[$code] ?? null; }
    public function require(string $code): ThemeDefinition { return $this->get($code) ?? throw new \InvalidArgumentException('Unknown Shopro theme: '.$code); }

    /** @return array<string, string> */
    public function frontChoices(string $locale = 'pl'): array { return $this->choicesFor(static fn (ThemeDefinition $theme): bool => $theme->front); }
    /** @return array<string, string> */
    public function adminChoices(string $locale = 'pl'): array { return $this->choicesFor(static fn (ThemeDefinition $theme): bool => $theme->admin); }
    /** @return array<string, string> */
    public function variants(string $themeCode, string $locale = 'pl'): array { return $this->require($themeCode)->variantChoices($locale); }
    /** @return list<ThemeDefinition> */
    public function all(): array { return array_values($this->themes); }

    /** @param callable(ThemeDefinition): bool $filter @return array<string, string> */
    private function choicesFor(callable $filter): array
    {
        $choices = [];
        foreach ($this->themes as $theme) if ($filter($theme)) $choices[$theme->name] = $theme->code;
        return $choices;
    }
}

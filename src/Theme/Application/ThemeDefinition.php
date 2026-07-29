<?php

declare(strict_types=1);

namespace App\Theme\Application;

/** Immutable manifest of an installed Shopro theme. */
final readonly class ThemeDefinition
{
    /** @param array<string, array{pl: string, en: string}> $variants */
    public function __construct(
        public string $code,
        public string $name,
        public string $version,
        public array $variants,
        public bool $front = true,
        public bool $admin = false,
        public bool $system = false,
        public ?string $frontStylesheet = null,
        public ?string $frontJavascript = null,
        public ?string $builderJavascript = null,
    ) {
        if (!preg_match('/^[a-z][a-z0-9_-]{1,79}$/', $code)) throw new \InvalidArgumentException('Invalid theme code: '.$code);
        if ($name === '' || $version === '') throw new \InvalidArgumentException('Theme name and version cannot be empty.');
        if (!$front && !$admin) throw new \InvalidArgumentException('A theme must support the front end, administration panel, or both.');
        if ($variants === []) throw new \InvalidArgumentException('A theme must expose at least one variant.');
        foreach ([$frontStylesheet, $frontJavascript, $builderJavascript] as $asset) {
            if ($asset !== null && (!str_starts_with($asset, '/') || str_contains($asset, '..'))) throw new \InvalidArgumentException('Theme asset paths must be absolute public paths.');
        }
        foreach ($variants as $variantCode => $labels) {
            if (!preg_match('/^[a-z][a-z0-9_-]{1,79}$/', $variantCode) || !isset($labels['pl'], $labels['en'])) throw new \InvalidArgumentException('Invalid theme variant definition.');
        }
    }

    /** @return array<string, string> */
    public function variantChoices(string $locale): array
    {
        $language = str_starts_with($locale, 'en') ? 'en' : 'pl';
        $choices = [];
        foreach ($this->variants as $code => $labels) $choices[$labels[$language]] = $code;

        return $choices;
    }
}

<?php

declare(strict_types=1);

namespace App\Theme\Presentation\Twig;

use App\Settings\Application\SettingsProvider;
use App\Theme\Application\ThemeDefinition;
use App\Theme\Application\ThemeRegistry;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class ThemeExtension extends AbstractExtension
{
    public function __construct(private readonly ThemeRegistry $themes, private readonly SettingsProvider $settings) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('shopro_active_front_theme', $this->activeFrontTheme(...)),
            new TwigFunction('shopro_active_front_layout', $this->activeFrontLayout(...)),
            new TwigFunction('shopro_theme_setting', $this->themeSetting(...)),
        ];
    }

    public function activeFrontTheme(): ThemeDefinition
    {
        $code = (string) $this->settings->get('theme', 'modernize');
        $theme = $this->themes->get($code);

        return $theme !== null && $theme->front ? $theme : $this->themes->require('modernize');
    }

    public function activeFrontLayout(): string
    {
        return $this->activeFrontTheme()->frontLayoutTemplate ?? 'cms/layout_base.html.twig';
    }

    public function themeSetting(string $key, mixed $default = null): mixed
    {
        return $this->settings->get('theme_settings.'.$this->activeFrontTheme()->code.'.'.$key, $default);
    }
}

<?php

declare(strict_types=1);

namespace App\Settings\Presentation\Twig;

use App\Settings\Application\SettingsProvider;
use App\Module\Application\ModuleAvailability;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class SettingsExtension extends AbstractExtension
{
    public function __construct(private readonly SettingsProvider $settings, private readonly ModuleAvailability $modules) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('shopro_setting', $this->setting(...)),
            new TwigFunction('shopro_date', $this->formatDate(...)),
        ];
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        if (!$this->modules->isEnabled('settings')) {
            return SettingsProvider::defaults()[$key] ?? $default;
        }

        return $this->settings->get($key, $default);
    }

    public function formatDate(\DateTimeInterface $date, string $kind = 'short'): string
    {
        $setting = match ($kind) { 'long' => 'date_long', 'time' => 'time_format', default => 'date_short' };
        $format = (string) $this->setting($setting);
        $phpFormat = strtr($format, ['%A' => 'l', '%a' => 'D', '%B' => 'F', '%b' => 'M', '%d' => 'd', '%e' => 'j', '%m' => 'm', '%Y' => 'Y', '%y' => 'y', '%H' => 'H', '%I' => 'h', '%M' => 'i', '%p' => 'A', '%P' => 'a', '%k' => 'G']);
        $copy = \DateTimeImmutable::createFromInterface($date)->setTimezone(new \DateTimeZone((string) $this->setting('timezone', 'Europe/Warsaw')));
        return $copy->format($phpFormat);
    }
}

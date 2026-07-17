<?php

declare(strict_types=1);

namespace App\Settings\Presentation\Twig;

use App\Settings\Application\SettingsProvider;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class SettingsExtension extends AbstractExtension
{
    public function __construct(private readonly SettingsProvider $settings) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('shopro_setting', $this->settings->get(...)),
            new TwigFunction('shopro_date', $this->formatDate(...)),
        ];
    }

    public function formatDate(\DateTimeInterface $date, string $kind = 'short'): string
    {
        $setting = match ($kind) { 'long' => 'date_long', 'time' => 'time_format', default => 'date_short' };
        $format = (string) $this->settings->get($setting);
        $phpFormat = strtr($format, ['%A' => 'l', '%a' => 'D', '%B' => 'F', '%b' => 'M', '%d' => 'd', '%e' => 'j', '%m' => 'm', '%Y' => 'Y', '%y' => 'y', '%H' => 'H', '%I' => 'h', '%M' => 'i', '%p' => 'A', '%P' => 'a', '%k' => 'G']);
        $copy = \DateTimeImmutable::createFromInterface($date)->setTimezone(new \DateTimeZone((string) $this->settings->get('timezone', 'Europe/Warsaw')));
        return $copy->format($phpFormat);
    }
}

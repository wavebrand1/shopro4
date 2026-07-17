<?php

declare(strict_types=1);

namespace App\Settings\Application;

use App\Settings\Infrastructure\Persistence\Doctrine\SystemSettingsRepository;

final class SettingsProvider
{
    /** @var array<string, mixed>|null */
    private ?array $configuration = null;

    public function __construct(private readonly SystemSettingsRepository $repository) {}

    /** @return array<string, mixed> */
    public function all(): array
    {
        return $this->configuration ??= array_replace(self::defaults(), $this->repository->get()->getConfiguration());
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    /** @return array<string, mixed> */
    public static function defaults(): array
    {
        return [
            'site_name' => 'Shopro 4.0', 'company' => '', 'site_url' => '', 'site_dir' => '', 'site_email' => '',
            'theme' => 'modernize', 'theme_variant' => 'blue', 'admin_theme' => 'modernize', 'admin_theme_variant' => 'blue',
            'locale' => 'pl_PL', 'timezone' => 'Europe/Warsaw', 'language' => 'pl', 'date_short' => '%e-%m-%Y', 'date_long' => '%d %B %Y %H:%M', 'time_format' => '%H:%M', 'week_start' => 1,
            'show_login' => true, 'show_search' => true, 'show_breadcrumbs' => true, 'show_language' => false, 'eu_cookie' => true,
            'maintenance' => false, 'maintenance_date' => '', 'maintenance_time' => '', 'maintenance_message' => 'Wykonujemy prace techniczne. Wracamy wkrótce.',
            'image_formats' => ['avif', 'webp'], 'image_widths' => '320,640,960,1280,1600', 'image_quality' => 82, 'image_lazy_loading' => true,
            'per_page' => 20, 'currency' => 'PLN', 'currency_symbol' => 'zł', 'thousands_separator' => ' ', 'decimal_separator' => ',',
            'registration_allowed' => false, 'registration_verify' => true, 'registration_auto_verify' => false, 'notify_admin' => true,
            'user_limit' => 0, 'login_attempts' => 5, 'flood_seconds' => 60, 'logging' => true, 'alert_email_template' => 0,
            'facebook' => '', 'instagram' => '', 'twitter' => '', 'pinterest' => '', 'linkedin' => '', 'youtube' => '', 'tiktok' => '',
            'meta_keywords' => '', 'meta_description' => '', 'analytics_measurement_id' => '', 'analytics_consent_required' => true,
            'smtp_host' => '', 'smtp_user' => '', 'smtp_port' => 587, 'smtp_encryption' => 'tls', 'mail_from_address' => '', 'mail_from_name' => 'Shopro', 'mail_reply_to' => '',
        ];
    }
}

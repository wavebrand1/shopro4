<?php

declare(strict_types=1);

namespace App\Media\Application;

use App\Settings\Application\SettingsProvider;

final class ResponsiveImageOptimizer
{
    public function __construct(private readonly SettingsProvider $settings) {}

    /** @return list<string> generated paths */
    public function optimize(string $source): array
    {
        if (self::isVariant($source)) return [];
        if (!function_exists('imagecreatetruecolor')) return [];
        $info = @getimagesize($source);
        if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) return [];
        // GD decodes the complete source into memory. A compressed 5 MB photo
        // may easily require more than 150 MB after decoding, which must never
        // turn an upload or a deployment into a fatal error on shared hosting.
        if (!self::fitsMemoryBudget((int) $info[0], (int) $info[1])) return [];
        $image = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($source),
            IMAGETYPE_PNG => @imagecreatefrompng($source),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($source) : false,
        };
        if (!$image) return [];
        imagealphablending($image, true); imagesavealpha($image, true);
        $widths = array_values(array_unique(array_filter(array_map('intval', explode(',', (string) $this->settings->get('image_widths', '320,640,960,1280,1600'))), static fn (int $width): bool => $width > 0 && $width <= 3840)));
        sort($widths);
        $formats = (array) $this->settings->get('image_formats', ['avif', 'webp']);
        $quality = (int) $this->settings->get('image_quality', 82);
        self::removeVariants($source);
        $generated = [];
        foreach ($widths as $width) {
            if ($width > $info[0]) continue;
            $height = max(1, (int) round($info[1] * $width / $info[0]));
            $resized = imagecreatetruecolor($width, $height);
            imagealphablending($resized, false); imagesavealpha($resized, true);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $width, $height, $info[0], $info[1]);
            $base = preg_replace('/\.[^.]+$/', '', $source).'.'.$width;
            if (in_array('webp', $formats, true) && function_exists('imagewebp') && imagewebp($resized, $path = $base.'.webp', $quality)) $generated[] = $path;
            if (in_array('avif', $formats, true) && function_exists('imageavif') && imageavif($resized, $path = $base.'.avif', $quality)) $generated[] = $path;
            imagedestroy($resized);
        }
        imagedestroy($image);
        return $generated;
    }

    public static function isVariant(string $path): bool
    {
        return preg_match('/\.\d+\.(?:webp|avif)$/i', basename($path)) === 1;
    }

    private static function fitsMemoryBudget(int $width, int $height): bool
    {
        if ($width < 1 || $height < 1) return false;
        $limit = self::memoryLimitBytes();
        if ($limit === null) return true;

        // GD keeps both the decoded source and a target bitmap in memory. For
        // PNG files its real allocation can be substantially higher than the
        // nominal RGBA 4 bytes/pixel. Be deliberately conservative: skipping
        // one oversized source is always preferable to terminating deployment
        // (or an upload) with an out-of-memory fatal error.
        $estimated = $width * $height * 16;

        return memory_get_usage(true) + $estimated + 48 * 1024 * 1024 < $limit;
    }

    private static function memoryLimitBytes(): ?int
    {
        $raw = trim((string) ini_get('memory_limit'));
        if ($raw === '' || $raw === '-1') return null;
        if (!preg_match('/^(\d+)([KMG]?)$/i', $raw, $matches)) return null;
        $value = (int) $matches[1];
        $multiplier = match (strtoupper($matches[2])) {
            'G' => 1024 * 1024 * 1024,
            'M' => 1024 * 1024,
            'K' => 1024,
            default => 1,
        };

        return $value * $multiplier;
    }

    /** @return list<string> */
    public static function variants(string $source): array
    {
        if (self::isVariant($source)) return [];
        $base = preg_replace('/\.[^.]+$/', '', $source);
        if (!is_string($base)) return [];
        $paths = glob($base.'.*.*') ?: [];
        $expectedName = preg_quote(basename($base), '/');

        return array_values(array_filter($paths, static fn (string $path): bool => is_file($path) && preg_match('/^'.$expectedName.'\.\d+\.(?:webp|avif)$/i', basename($path)) === 1));
    }

    public static function removeVariants(string $source): void
    {
        foreach (self::variants($source) as $variant) @unlink($variant);
    }
}

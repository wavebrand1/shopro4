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

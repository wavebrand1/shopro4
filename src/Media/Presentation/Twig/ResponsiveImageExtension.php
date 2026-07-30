<?php

declare(strict_types=1);

namespace App\Media\Presentation\Twig;

use App\Media\Domain\MediaPath;
use App\Settings\Application\SettingsProvider;
use App\Module\Application\ModuleAvailability;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFunction;

final class ResponsiveImageExtension extends AbstractExtension
{
    public function __construct(#[Autowire('%kernel.project_dir%')] private readonly string $projectDir, private readonly SettingsProvider $settings, private readonly ModuleAvailability $modules) {}
    public function getFunctions(): array { return [new TwigFunction('shopro_picture', $this->picture(...), ['is_safe' => ['html']])]; }
    public function picture(string $src, string $alt = '', ?int $width = null, ?int $height = null, bool $eager = false, string $sizes = '100vw'): Markup
    {
        if (!MediaPath::isSafePublicUploadUrl($src)) return new Markup('', 'UTF-8');

        // The original upload must always remain usable. Responsive variants
        // are an optimisation generated asynchronously/by deployment scripts;
        // their absence may never make the public image disappear.
        $fallbackLoading = $eager ? ' loading="eager" fetchpriority="high" decoding="async"' : ' loading="lazy" decoding="async"';
        $fallback = new Markup(
            '<picture><img src="'.htmlspecialchars($src, ENT_QUOTES | ENT_SUBSTITUTE).'" alt="'.htmlspecialchars($alt, ENT_QUOTES | ENT_SUBSTITUTE).'"'.$fallbackLoading.'></picture>',
            'UTF-8',
        );

        if (!$this->modules->isEnabled('media')) return $fallback;
        $urlPath = $src;
        $decodedPath = rawurldecode($urlPath);
        if (str_contains($decodedPath, "\0")) return new Markup('', 'UTF-8');
        $source = realpath($this->projectDir.'/public'.$decodedPath);
        $uploads = realpath($this->projectDir.'/public/uploads');
        $normalizedSource = $source ? str_replace('\\', '/', $source) : '';
        $normalizedUploads = $uploads ? rtrim(str_replace('\\', '/', $uploads), '/') : '';
        if (!$source || !$uploads || !MediaPath::isSupportedImageFile($source) || !str_starts_with($normalizedSource, $normalizedUploads.'/')) return $fallback;
        $settingsEnabled = $this->modules->isEnabled('settings');
        $widths = array_filter(array_map('intval', explode(',', (string) ($settingsEnabled ? $this->settings->get('image_widths') : SettingsProvider::defaults()['image_widths']))));
        $baseUrl = preg_replace('/\.[^.]+$/', '', $urlPath); $baseFile = preg_replace('/\.[^.]+$/', '', $source);
        $sources = '';
        foreach (['avif' => 'image/avif', 'webp' => 'image/webp'] as $format => $mime) {
            $set = [];
            foreach ($widths as $candidate) if (is_file($baseFile.'.'.$candidate.'.'.$format)) $set[] = htmlspecialchars($baseUrl.'.'.$candidate.'.'.$format, ENT_QUOTES).' '.$candidate.'w';
            if ($set) $sources .= '<source type="'.$mime.'" srcset="'.implode(', ', $set).'" sizes="'.htmlspecialchars($sizes, ENT_QUOTES | ENT_SUBSTITUTE).'">';
        }
        if (!$width || !$height) {
            $imageSize = @getimagesize($source);
            if ($imageSize) { $width = $imageSize[0]; $height = $imageSize[1]; }
        }
        $dimensions = ($width && $height) ? ' width="'.$width.'" height="'.$height.'"' : '';
        $lazy = (bool) ($settingsEnabled ? $this->settings->get('image_lazy_loading', true) : SettingsProvider::defaults()['image_lazy_loading']);
        $loading = $eager ? ' loading="eager" fetchpriority="high" decoding="async"' : ($lazy ? ' loading="lazy" decoding="async"' : ' decoding="async"');
        return new Markup('<picture>'.$sources.'<img src="'.htmlspecialchars($src, ENT_QUOTES).'" alt="'.htmlspecialchars($alt, ENT_QUOTES).'"'.$dimensions.$loading.'></picture>', 'UTF-8');
    }
}

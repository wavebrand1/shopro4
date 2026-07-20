<?php

declare(strict_types=1);

namespace App\Media\Presentation\Twig;

use App\Settings\Application\SettingsProvider;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFunction;

final class ResponsiveImageExtension extends AbstractExtension
{
    public function __construct(#[Autowire('%kernel.project_dir%')] private readonly string $projectDir, private readonly SettingsProvider $settings) {}
    public function getFunctions(): array { return [new TwigFunction('shopro_picture', $this->picture(...), ['is_safe' => ['html']])]; }
    public function picture(string $src, string $alt = '', ?int $width = null, ?int $height = null, bool $eager = false): Markup
    {
        $urlPath = parse_url($src, PHP_URL_PATH);
        if (!is_string($urlPath) || !str_starts_with($urlPath, '/uploads/')) return new Markup('', 'UTF-8');
        $decodedPath = rawurldecode($urlPath);
        if (str_contains($decodedPath, "\0")) return new Markup('', 'UTF-8');
        $source = realpath($this->projectDir.'/public'.$decodedPath);
        $uploads = realpath($this->projectDir.'/public/uploads');
        $normalizedSource = $source ? str_replace('\\', '/', $source) : '';
        $normalizedUploads = $uploads ? rtrim(str_replace('\\', '/', $uploads), '/') : '';
        if (!$source || !$uploads || !is_file($source) || !str_starts_with($normalizedSource, $normalizedUploads.'/')) return new Markup('', 'UTF-8');
        $widths = array_filter(array_map('intval', explode(',', (string) $this->settings->get('image_widths'))));
        $baseUrl = preg_replace('/\.[^.]+$/', '', $urlPath); $baseFile = preg_replace('/\.[^.]+$/', '', $source);
        $sources = '';
        foreach (['avif' => 'image/avif', 'webp' => 'image/webp'] as $format => $mime) {
            $set = [];
            foreach ($widths as $candidate) if (is_file($baseFile.'.'.$candidate.'.'.$format)) $set[] = htmlspecialchars($baseUrl.'.'.$candidate.'.'.$format, ENT_QUOTES).' '.$candidate.'w';
            if ($set) $sources .= '<source type="'.$mime.'" srcset="'.implode(', ', $set).'" sizes="100vw">';
        }
        if (!$width || !$height) {
            $imageSize = @getimagesize($source);
            if ($imageSize) { $width = $imageSize[0]; $height = $imageSize[1]; }
        }
        $dimensions = ($width && $height) ? ' width="'.$width.'" height="'.$height.'"' : '';
        $lazy = (bool) $this->settings->get('image_lazy_loading', true);
        $loading = $eager ? ' loading="eager" fetchpriority="high" decoding="async"' : ($lazy ? ' loading="lazy" decoding="async"' : ' decoding="async"');
        return new Markup('<picture>'.$sources.'<img src="'.htmlspecialchars($src, ENT_QUOTES).'" alt="'.htmlspecialchars($alt, ENT_QUOTES).'"'.$dimensions.$loading.'></picture>', 'UTF-8');
    }
}

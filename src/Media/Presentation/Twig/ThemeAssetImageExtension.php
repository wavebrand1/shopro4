<?php

declare(strict_types=1);

namespace App\Media\Presentation\Twig;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFunction;

/**
 * Renders an optimized image distributed by an installed theme bundle.
 *
 * Theme assets are copied to public/bundles during deployment. The matching
 * command produces sibling .{width}.webp/.avif files, while this helper keeps
 * an original-image fallback for a newly installed theme or an interrupted
 * deploy.
 */
final class ThemeAssetImageExtension extends AbstractExtension
{
    public function __construct(#[Autowire('%kernel.project_dir%')] private readonly string $projectDir) {}

    public function getFunctions(): array
    {
        return [new TwigFunction('shopro_theme_picture', $this->picture(...), ['is_safe' => ['html']])];
    }

    public function picture(string $src, string $alt = '', string $class = '', bool $eager = false, string $sizes = '100vw'): Markup
    {
        $decoded = rawurldecode($src);
        // `asset()` goes through AssetMapper in development and returns an
        // `/assets/bundles/...` URL. Installed bundle assets use `/bundles/...`
        // in production. Both locations are public theme assets.
        $isBundleAsset = str_starts_with($decoded, '/bundles/') || str_starts_with($decoded, '/assets/bundles/');
        if (!$isBundleAsset || str_contains($decoded, "\0") || str_contains($decoded, '\\') || str_contains($decoded, '?') || str_contains($decoded, '#')) {
            return new Markup('', 'UTF-8');
        }

        $classAttribute = $class !== '' ? ' class="'.htmlspecialchars($class, ENT_QUOTES | ENT_SUBSTITUTE).'"' : '';
        $loading = $eager ? ' loading="eager" fetchpriority="high"' : ' loading="lazy"';
        $fallback = new Markup('<picture><img'.$classAttribute.' src="'.htmlspecialchars($src, ENT_QUOTES | ENT_SUBSTITUTE).'" alt="'.htmlspecialchars($alt, ENT_QUOTES | ENT_SUBSTITUTE).'"'.$loading.' decoding="async"></picture>', 'UTF-8');

        $source = realpath($this->projectDir.'/public'.$decoded);
        $roots = array_filter([
            realpath($this->projectDir.'/public/bundles'),
            realpath($this->projectDir.'/public/assets/bundles'),
        ]);
        $normalizedSource = $source ? str_replace('\\', '/', $source) : '';
        $isAllowedSource = false;
        foreach ($roots as $root) {
            $normalizedRoot = rtrim(str_replace('\\', '/', $root), '/');
            if (str_starts_with($normalizedSource, $normalizedRoot.'/')) {
                $isAllowedSource = true;
                break;
            }
        }
        if (!$source || !$isAllowedSource || !is_file($source)) {
            return $fallback;
        }

        $size = @getimagesize($source);
        $baseFile = preg_replace('/\.[^.]+$/', '', $source);
        $baseUrl = preg_replace('/\.[^.]+$/', '', $src);
        if (!is_string($baseFile) || !is_string($baseUrl)) return new Markup('', 'UTF-8');

        $sources = '';
        foreach (['avif' => 'image/avif', 'webp' => 'image/webp'] as $format => $mime) {
            $variants = glob($baseFile.'.*.'.$format) ?: [];
            $set = [];
            foreach ($variants as $variant) {
                if (preg_match('/\.(\d+)\.'.preg_quote($format, '/').'$/i', $variant, $match) === 1) {
                    $set[(int) $match[1]] = htmlspecialchars($baseUrl.'.'.$match[1].'.'.$format, ENT_QUOTES | ENT_SUBSTITUTE);
                }
            }
            ksort($set);
            if ($set) {
                $entries = [];
                foreach ($set as $width => $url) $entries[] = $url.' '.$width.'w';
                $sources .= '<source type="'.$mime.'" srcset="'.implode(', ', $entries).'" sizes="'.htmlspecialchars($sizes, ENT_QUOTES | ENT_SUBSTITUTE).'">';
            }
        }

        $dimensions = $size ? ' width="'.$size[0].'" height="'.$size[1].'"' : '';
        return new Markup('<picture>'.$sources.'<img'.$classAttribute.' src="'.htmlspecialchars($src, ENT_QUOTES | ENT_SUBSTITUTE).'" alt="'.htmlspecialchars($alt, ENT_QUOTES | ENT_SUBSTITUTE).'"'.$dimensions.$loading.' decoding="async"></picture>', 'UTF-8');
    }
}

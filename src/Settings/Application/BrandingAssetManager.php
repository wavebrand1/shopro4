<?php

declare(strict_types=1);

namespace App\Settings\Application;

use Symfony\Component\HttpFoundation\File\UploadedFile;

final class BrandingAssetManager
{
    private const EXTENSIONS = [
        'logo' => ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp', 'image/svg+xml' => 'svg'],
        'favicon' => ['image/png' => 'png', 'image/webp' => 'webp', 'image/x-icon' => 'ico', 'image/vnd.microsoft.icon' => 'ico'],
        'social' => ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'],
    ];

    public function __construct(private readonly string $projectDir) {}

    public function store(UploadedFile $file, string $type): string
    {
        $extension = self::EXTENSIONS[$type][$file->getMimeType() ?? ''] ?? null;
        if ($extension === null) throw new \InvalidArgumentException('Nieobsługiwany format pliku graficznego.');

        $directory = $this->projectDir.'/public/uploads/branding';
        // The uploads directory is intentionally outside version control. It may
        // therefore be absent after a fresh deployment or restoring an older
        // backup. Create it for every accepted file type, not only SVGs.
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('Nie można utworzyć katalogu na pliki identyfikacji wizualnej.');
        }

        $filename = sprintf('%s-%s.%s', $type, bin2hex(random_bytes(8)), $extension);
        if ($extension === 'svg') {
            $svg = $this->sanitizeSvg((string) file_get_contents($file->getPathname()));
            if (file_put_contents($directory.'/'.$filename, $svg, LOCK_EX) === false) throw new \RuntimeException('Nie można zapisać logo.');
        } else {
            $file->move($directory, $filename);
        }

        return '/uploads/branding/'.$filename;
    }

    public function remove(?string $publicPath): void
    {
        if (!$publicPath || !str_starts_with($publicPath, '/uploads/branding/')) return;
        $path = $this->projectDir.'/public/uploads/branding/'.basename($publicPath);
        if (is_file($path)) @unlink($path);
    }

    /**
     * Returns an uploaded branding path only while its file is available.
     *
     * Deployments and restored backups can leave an old path in the database
     * while the matching upload is no longer present. Rendering that stale URL
     * breaks the header and favicon. In that situation the caller receives its
     * bundled fallback instead.
     */
    public function pathOrFallback(?string $publicPath, string $fallback): string
    {
        if (!is_string($publicPath) || $publicPath === '') return $fallback;
        if (!str_starts_with($publicPath, '/uploads/branding/')) return $publicPath;

        $path = $this->projectDir.'/public/uploads/branding/'.basename($publicPath);

        return is_file($path) ? $publicPath : $fallback;
    }

    private function sanitizeSvg(string $source): string
    {
        $previous = libxml_use_internal_errors(true);
        $document = new \DOMDocument();
        $loaded = $document->loadXML($source, LIBXML_NONET | LIBXML_NOBLANKS);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (!$loaded || $document->documentElement?->localName !== 'svg') throw new \InvalidArgumentException('Plik nie jest prawidłowym obrazem SVG.');

        $xpath = new \DOMXPath($document);
        foreach (['script', 'foreignObject', 'iframe', 'object', 'embed', 'audio', 'video'] as $name) {
            foreach (iterator_to_array($xpath->query('//*[local-name()="'.$name.'"]') ?: []) as $node) $node->parentNode?->removeChild($node);
        }
        foreach (iterator_to_array($xpath->query('//@*') ?: []) as $attribute) {
            $name = strtolower($attribute->nodeName);
            $value = trim($attribute->nodeValue ?? '');
            if (str_starts_with($name, 'on') || (($name === 'href' || str_ends_with($name, ':href')) && $value !== '' && !str_starts_with($value, '#'))) {
                $attribute->ownerElement?->removeAttributeNode($attribute);
            }
        }

        return (string) $document->saveXML($document->documentElement);
    }
}

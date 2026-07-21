<?php
declare(strict_types=1);

namespace App\Media\Application;

use Symfony\Component\HttpFoundation\File\UploadedFile;

final class AdminFileManager
{
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg', 'image/png', 'image/webp', 'image/avif', 'image/gif',
        'application/pdf', 'text/plain', 'text/csv', 'application/zip',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];
    private const MIME_EXTENSIONS = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/webp' => ['webp'],
        'image/avif' => ['avif'],
        'image/gif' => ['gif'],
        'application/pdf' => ['pdf'],
        'text/plain' => ['', 'txt', 'csv'],
        'text/csv' => ['csv'],
        'application/zip' => ['zip'],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['xlsx'],
    ];
    private const ALLOWED_EXTENSIONS = ['', 'jpg', 'jpeg', 'png', 'webp', 'avif', 'gif', 'pdf', 'txt', 'csv', 'zip', 'docx', 'xlsx'];

    public function __construct(private readonly string $projectDir, private readonly ?ResponsiveImageOptimizer $imageOptimizer = null) {}

    /** @return array{path:string,parent:?string,directories:list<array<string,mixed>>,files:list<array<string,mixed>>} */
    public function listing(string $relativePath): array
    {
        $path = $this->normalize($relativePath);
        $directory = $this->resolve($path, true);
        $directories = $files = [];
        foreach (new \DirectoryIterator($directory) as $item) {
            if ($item->isDot() || $item->isLink()) continue;
            $entry = [
                'name' => $item->getFilename(),
                'path' => ltrim($path.'/'.$item->getFilename(), '/'),
                'modified' => $item->getMTime(),
            ];
            if ($item->isDir()) $directories[] = $entry;
            elseif ($item->isFile()) {
                if (ResponsiveImageOptimizer::isVariant($item->getPathname())) continue;
                $entry['size'] = $item->getSize();
                $entry['image'] = str_starts_with((string) mime_content_type($item->getPathname()), 'image/');
                $entry['url'] = '/uploads/'.str_replace('%2F', '/', rawurlencode($entry['path']));
                $files[] = $entry;
            }
        }
        usort($directories, static fn (array $a, array $b): int => strnatcasecmp($a['name'], $b['name']));
        usort($files, static fn (array $a, array $b): int => strnatcasecmp($a['name'], $b['name']));

        return ['path' => $path, 'parent' => $path === '' ? null : (dirname($path) === '.' ? '' : dirname($path)), 'directories' => $directories, 'files' => $files];
    }

    public function createDirectory(string $path, string $name): void
    {
        $target = $this->resolveChild($path, $name);
        if (file_exists($target) || !mkdir($target, 0775, true)) throw new \RuntimeException('media.exists_or_create_failed');
    }

    public function createFile(string $path, string $name): void
    {
        $target = $this->resolveChild($path, $name);
        if (file_exists($target) || file_put_contents($target, '', LOCK_EX) === false) throw new \RuntimeException('media.exists_or_create_failed');
    }

    /** @param list<UploadedFile> $files */
    public function upload(string $path, array $files): FileUploadResult
    {
        $directory = $this->resolve($this->normalize($path), true);
        $uploaded = 0;
        $rejections = [];
        foreach ($files as $file) {
            $mimeType = (string) $file->getMimeType();
            $extension = strtolower($file->getClientOriginalExtension());
            $reason = match (true) {
                !$file->isValid() => 'invalid',
                $file->getSize() > 20 * 1024 * 1024 => 'size',
                !in_array($mimeType, self::ALLOWED_MIME_TYPES, true) => 'type',
                !self::matchesMimeType($mimeType, $extension) => 'mismatch',
                default => null,
            };
            if ($reason !== null) {
                $rejections[$reason] = ($rejections[$reason] ?? 0) + 1;
                continue;
            }
            try {
                $name = $this->safeName($file->getClientOriginalName());
            } catch (\InvalidArgumentException) {
                $rejections['name'] = ($rejections['name'] ?? 0) + 1;
                continue;
            }
            if (file_exists($directory.'/'.$name)) $name = pathinfo($name, PATHINFO_FILENAME).'-'.bin2hex(random_bytes(4)).($file->getClientOriginalExtension() ? '.'.strtolower($file->getClientOriginalExtension()) : '');
            $stored = $file->move($directory, $name);
            if ($this->imageOptimizer && str_starts_with((string) $stored->getMimeType(), 'image/')) $this->imageOptimizer->optimize($stored->getPathname());
            ++$uploaded;
        }

        return new FileUploadResult($uploaded, $rejections);
    }

    public function rename(string $path, string $newName): void
    {
        $source = $this->resolve($this->normalize($path));
        $safeName = $this->safeName($newName);
        if (is_file($source)) {
            $mimeType = (string) mime_content_type($source);
            if (isset(self::MIME_EXTENSIONS[$mimeType]) && !self::matchesMimeType($mimeType, strtolower(pathinfo($safeName, PATHINFO_EXTENSION)))) throw new \InvalidArgumentException('media.extension_mismatch');
        }
        $target = dirname($source).'/'.$safeName;
        if (file_exists($target) || !rename($source, $target)) throw new \RuntimeException('media.rename_failed');
        if (is_file($target)) {
            ResponsiveImageOptimizer::removeVariants($source);
            if ($this->imageOptimizer && str_starts_with((string) mime_content_type($target), 'image/')) $this->imageOptimizer->optimize($target);
        }
    }

    public function delete(string $path): void
    {
        $target = $this->resolve($this->normalize($path));
        if (is_dir($target)) {
            $this->removeTechnicalFiles($target);
            $iterator = new \FilesystemIterator($target);
            if ($iterator->valid() || !rmdir($target)) throw new \RuntimeException('media.directory_not_empty');
        } elseif (!unlink($target)) throw new \RuntimeException('media.delete_failed');
        else ResponsiveImageOptimizer::removeVariants($target);
    }

    private function resolveChild(string $path, string $name): string
    {
        return $this->resolve($this->normalize($path), true).'/'.$this->safeName($name);
    }

    private function resolve(string $path, bool $directory = false): string
    {
        $root = $this->root();
        $target = $root.($path === '' ? '' : '/'.$path);
        $real = realpath($target);
        if ($real === false || ($directory && !is_dir($real)) || (!str_starts_with(str_replace('\\', '/', $real).'/', str_replace('\\', '/', $root).'/'))) throw new \InvalidArgumentException('media.invalid_path');

        return $real;
    }

    private function root(): string
    {
        $root = $this->projectDir.'/public/uploads';
        if (!is_dir($root) && !mkdir($root, 0775, true) && !is_dir($root)) throw new \RuntimeException('media.root_failed');

        return (string) realpath($root);
    }

    private function normalize(string $path): string
    {
        $path = trim(str_replace('\\', '/', rawurldecode($path)), '/');
        if ($path === '') return '';
        foreach (explode('/', $path) as $part) if ($part === '' || $part === '.' || $part === '..') throw new \InvalidArgumentException('media.invalid_path');

        return $path;
    }

    private function safeName(string $name): string
    {
        $name = trim(basename(str_replace('\\', '/', $name)));
        if ($name === '' || $name === '.' || $name === '..' || preg_match('/[\\x00-\\x1f<>:"|?*]/u', $name)) throw new \InvalidArgumentException('media.invalid_name');
        if (!in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), self::ALLOWED_EXTENSIONS, true)) throw new \InvalidArgumentException('media.invalid_name');

        return $name;
    }

    private static function matchesMimeType(string $mimeType, string $extension): bool
    {
        return in_array($extension, self::MIME_EXTENSIONS[$mimeType] ?? [], true);
    }

    private function removeTechnicalFiles(string $directory): void
    {
        foreach (new \DirectoryIterator($directory) as $item) {
            if ($item->isFile() && !$item->isLink() && ResponsiveImageOptimizer::isVariant($item->getPathname())) @unlink($item->getPathname());
        }
    }
}

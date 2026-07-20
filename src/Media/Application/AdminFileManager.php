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
    private const ALLOWED_EXTENSIONS = ['', 'jpg', 'jpeg', 'png', 'webp', 'avif', 'gif', 'pdf', 'txt', 'csv', 'zip', 'docx', 'xlsx'];

    public function __construct(private readonly string $projectDir) {}

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
    public function upload(string $path, array $files): int
    {
        $directory = $this->resolve($this->normalize($path), true);
        $uploaded = 0;
        foreach ($files as $file) {
            if (!$file->isValid() || !in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES, true) || $file->getSize() > 20 * 1024 * 1024) continue;
            $name = $this->safeName($file->getClientOriginalName());
            if (file_exists($directory.'/'.$name)) $name = pathinfo($name, PATHINFO_FILENAME).'-'.bin2hex(random_bytes(4)).($file->getClientOriginalExtension() ? '.'.strtolower($file->getClientOriginalExtension()) : '');
            $file->move($directory, $name);
            ++$uploaded;
        }

        return $uploaded;
    }

    public function rename(string $path, string $newName): void
    {
        $source = $this->resolve($this->normalize($path));
        $target = dirname($source).'/'.$this->safeName($newName);
        if (file_exists($target) || !rename($source, $target)) throw new \RuntimeException('media.rename_failed');
    }

    public function delete(string $path): void
    {
        $target = $this->resolve($this->normalize($path));
        if (is_dir($target)) {
            $iterator = new \FilesystemIterator($target);
            if ($iterator->valid() || !rmdir($target)) throw new \RuntimeException('media.directory_not_empty');
        } elseif (!unlink($target)) throw new \RuntimeException('media.delete_failed');
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
}

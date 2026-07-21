<?php

declare(strict_types=1);

namespace App\Tests\Unit\Media;

use App\Media\Domain\MediaPath;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MediaPathTest extends TestCase
{
    #[DataProvider('paths')]
    public function testRecognizesSafePublicUploadUrls(string $url, bool $expected): void
    {
        self::assertSame($expected, MediaPath::isSafePublicUploadUrl($url));
    }

    public static function paths(): iterable
    {
        yield ['/uploads/photo.webp', true];
        yield ['/uploads/gallery/My%20photo.svg', true];
        yield ['https://example.com/uploads/photo.webp', false];
        yield ['//example.com/uploads/photo.webp', false];
        yield ['/uploads/../.env', false];
        yield ['/uploads/%2e%2e/.env', false];
        yield ['/uploads/folder//photo.webp', false];
        yield ['/uploads/photo.webp?version=1', false];
        yield ['/uploads/photo%ZZ.webp', false];
    }

    public function testAcceptsOnlyFilesWithSupportedImageContent(): void
    {
        $directory = sys_get_temp_dir().'/shopro-media-path-'.bin2hex(random_bytes(6));
        self::assertTrue(mkdir($directory));
        $svg = $directory.'/vector.svg';
        $document = $directory.'/document.jpg';
        file_put_contents($svg, '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"></svg>');
        file_put_contents($document, "%PDF-1.4\nnot an image");

        try {
            self::assertTrue(MediaPath::isSupportedImageFile($svg));
            self::assertFalse(MediaPath::isSupportedImageFile($document));
            self::assertFalse(MediaPath::isSupportedImageFile($directory.'/missing.png'));
        } finally {
            unlink($svg);
            unlink($document);
            rmdir($directory);
        }
    }
}

<?php
declare(strict_types=1);

namespace App\Tests\Unit;

use App\Media\Application\AdminFileManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class AdminFileManagerTest extends TestCase
{
    private string $project;
    private AdminFileManager $manager;

    protected function setUp(): void
    {
        $this->project = sys_get_temp_dir().'/shopro-file-manager-'.bin2hex(random_bytes(6));
        mkdir($this->project.'/public/uploads', 0775, true);
        $this->manager = new AdminFileManager($this->project);
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->project);
    }

    public function testItManagesItemsInsideUploads(): void
    {
        $this->manager->createDirectory('', 'documents');
        $this->manager->createFile('documents', 'readme.txt');
        $listing = $this->manager->listing('documents');
        self::assertSame('readme.txt', $listing['files'][0]['name']);
        self::assertSame('', $listing['parent']);
        self::assertSame(0, $listing['directory_count']);
        self::assertSame(1, $listing['file_count']);
        self::assertSame(0, $listing['total_size']);
        self::assertSame(0, $listing['image_count']);
        self::assertSame(0, $listing['image_size']);

        $this->manager->rename('documents/readme.txt', 'manual.txt');
        self::assertSame('manual.txt', $this->manager->listing('documents')['files'][0]['name']);
        $this->manager->delete('documents/manual.txt');
        $this->manager->delete('documents');
        self::assertSame([], $this->manager->listing('')['directories']);
    }

    public function testItRejectsTraversalAndExecutableExtensions(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->manager->listing('../');
    }

    public function testItRejectsExecutableFileCreation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->manager->createFile('', 'shell.php');
    }

    public function testItHidesAndMaintainsResponsiveImageVariants(): void
    {
        file_put_contents($this->project.'/public/uploads/photo.jpg', base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAf/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAF//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABBQJ//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAwEBPwF//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAgEBPwF//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQAGPwJ//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPyF//9k=', true));
        file_put_contents($this->project.'/public/uploads/photo.320.webp', 'variant');
        file_put_contents($this->project.'/public/uploads/photo.640.avif', 'variant');

        self::assertSame(['photo.jpg'], array_column($this->manager->listing('')['files'], 'name'));

        $this->manager->rename('photo.jpg', 'renamed.jpg');
        self::assertFileExists($this->project.'/public/uploads/renamed.jpg');
        self::assertFileDoesNotExist($this->project.'/public/uploads/photo.320.webp');
        self::assertFileDoesNotExist($this->project.'/public/uploads/photo.640.avif');

        file_put_contents($this->project.'/public/uploads/renamed.320.webp', 'variant');
        $this->manager->delete('renamed.jpg');
        self::assertFileDoesNotExist($this->project.'/public/uploads/renamed.320.webp');
    }

    public function testItCanDeleteDirectoryContainingOnlyHiddenTechnicalVariants(): void
    {
        mkdir($this->project.'/public/uploads/gallery');
        file_put_contents($this->project.'/public/uploads/gallery/removed.320.webp', 'variant');

        self::assertSame([], $this->manager->listing('gallery')['files']);
        $this->manager->delete('gallery');
        self::assertDirectoryDoesNotExist($this->project.'/public/uploads/gallery');
    }

    public function testItRejectsUploadedFileWhoseExtensionDoesNotMatchItsMimeType(): void
    {
        $path = $this->project.'/document-source.pdf';
        file_put_contents($path, "%PDF-1.4\ncontent");
        $file = new UploadedFile($path, 'fake-photo.jpg', null, null, true);

        $result = $this->manager->upload('', [$file]);
        self::assertSame(0, $result->uploaded);
        self::assertSame(1, $result->rejected());
        self::assertSame(['mismatch' => 1], $result->rejections);
        self::assertSame([], $this->manager->listing('')['files']);
    }

    public function testItUploadsValidFilesAndReportsRejectedFilesFromTheSameBatch(): void
    {
        $validPath = $this->project.'/valid.pdf';
        $invalidPath = $this->project.'/invalid.pdf';
        file_put_contents($validPath, "%PDF-1.4\nvalid");
        file_put_contents($invalidPath, "%PDF-1.4\ninvalid");
        $valid = new UploadedFile($validPath, 'document.pdf', null, null, true);
        $invalid = new UploadedFile($invalidPath, 'document.jpg', null, null, true);

        $result = $this->manager->upload('', [$valid, $invalid]);

        self::assertSame(1, $result->uploaded);
        self::assertSame(['mismatch' => 1], $result->rejections);
        self::assertSame(['document.pdf'], array_column($this->manager->listing('')['files'], 'name'));
        self::assertSame((int) filesize($this->project.'/public/uploads/document.pdf'), $this->manager->listing('')['total_size']);
        self::assertSame(0, $this->manager->listing('')['image_count']);
    }

    public function testItRejectsRenamingFileToExtensionIncompatibleWithItsMimeType(): void
    {
        file_put_contents($this->project.'/public/uploads/document.pdf', "%PDF-1.4\ncontent");

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('media.extension_mismatch');
        $this->manager->rename('document.pdf', 'document.jpg');
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) return;
        $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($items as $item) $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        rmdir($path);
    }
}

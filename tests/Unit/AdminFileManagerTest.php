<?php
declare(strict_types=1);

namespace App\Tests\Unit;

use App\Media\Application\AdminFileManager;
use PHPUnit\Framework\TestCase;

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

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) return;
        $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($items as $item) $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        rmdir($path);
    }
}

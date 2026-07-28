<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class DoctrineEntityProxyCompatibilityTest extends TestCase
{
    public function testDoctrineEntitiesAreNotFinalSoLazyGhostsCanBeGenerated(): void
    {
        $sourceDirectory = dirname(__DIR__, 2).'/src';
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDirectory, \FilesystemIterator::SKIP_DOTS),
        );
        $finalEntities = [];

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') continue;

            $source = (string) file_get_contents($file->getPathname());
            if (!str_contains($source, '#[ORM\\Entity')) continue;
            if (preg_match('/\bfinal\s+class\s+([A-Za-z_][A-Za-z0-9_]*)/', $source, $match) === 1) {
                $finalEntities[] = $match[1].' ('.$file->getPathname().')';
            }
        }

        self::assertSame(
            [],
            $finalEntities,
            'Encje Doctrine nie mogą być final, ponieważ Symfony/Doctrine generuje dla nich lazy ghosts.',
        );
    }
}

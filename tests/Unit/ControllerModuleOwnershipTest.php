<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Module\Application\RequiresModule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ControllerModuleOwnershipTest extends TestCase
{
    private const FOUNDATION = [
        'App\\Audit\\Presentation\\Http\\Admin\\AuditLogController',
        'App\\Identity\\Presentation\\Http\\LoginController',
        'App\\Module\\Presentation\\Http\\Admin\\ModuleController',
        'App\\Shared\\Presentation\\Http\\HealthController',
    ];

    #[DataProvider('controllerClasses')]
    public function testEveryBusinessControllerDeclaresItsModule(string $class): void
    {
        $attributes = (new \ReflectionClass($class))->getAttributes(RequiresModule::class);
        if (in_array($class, self::FOUNDATION, true)) {
            self::assertSame([], $attributes, $class.' is a deliberately module-independent foundation controller.');

            return;
        }

        self::assertCount(1, $attributes, $class.' must declare exactly one owning Shopro module.');
        $expected = match (true) {
            str_starts_with($class, 'App\\Cms\\') => 'cms',
            str_starts_with($class, 'App\\Identity\\') => 'identity',
            str_starts_with($class, 'App\\Language\\') => 'language',
            str_starts_with($class, 'App\\Media\\') => 'media',
            str_starts_with($class, 'App\\Newsletter\\') => 'newsletter',
            str_starts_with($class, 'App\\Settings\\'), str_starts_with($class, 'App\\Mail\\') => 'settings',
            default => throw new \LogicException('Controller has no module ownership rule: '.$class),
        };
        self::assertSame($expected, $attributes[0]->newInstance()->code);
    }

    /** @return iterable<string, array{string}> */
    public static function controllerClasses(): iterable
    {
        $source = dirname(__DIR__, 2).'/src';
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source));
        foreach ($files as $file) {
            if (!$file->isFile() || !str_ends_with($file->getFilename(), 'Controller.php')) continue;
            $relative = substr($file->getPathname(), strlen($source) + 1, -4);
            $class = 'App\\'.str_replace([DIRECTORY_SEPARATOR, '/'], '\\', $relative);
            yield $class => [$class];
        }
    }
}

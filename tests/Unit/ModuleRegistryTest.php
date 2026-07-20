<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Module\Application\ModuleDefinition;
use App\Module\Application\ModuleRegistry;
use PHPUnit\Framework\TestCase;

final class ModuleRegistryTest extends TestCase
{
    public function testItIndexesValidDefinitionsInStableOrder(): void
    {
        $registry = new ModuleRegistry([$this->definition('zeta'), $this->definition('alpha', ['zeta'])]);
        self::assertSame(['alpha', 'zeta'], array_keys($registry->all()));
        self::assertSame('alpha', $registry->get('alpha')?->code());
    }

    public function testItRejectsMissingDependencies(): void
    {
        $this->expectExceptionMessage('requires missing module');
        new ModuleRegistry([$this->definition('example', ['missing'])]);
    }

    public function testItRejectsDependencyCycles(): void
    {
        $this->expectExceptionMessage('Cyclic Shopro module dependency');
        new ModuleRegistry([$this->definition('first', ['second']), $this->definition('second', ['first'])]);
    }

    public function testItRejectsDuplicateCodes(): void
    {
        $this->expectExceptionMessage('Duplicate Shopro module code');
        new ModuleRegistry([$this->definition('example'), $this->definition('example')]);
    }

    /** @param list<string> $dependencies */
    private function definition(string $code, array $dependencies = []): ModuleDefinition
    {
        return new class($code, $dependencies) implements ModuleDefinition {
            /** @param list<string> $dependencies */
            public function __construct(private readonly string $moduleCode, private readonly array $moduleDependencies) {}
            public function code(): string { return $this->moduleCode; }
            public function name(): string { return 'module.test'; }
            public function description(): string { return 'module.test_help'; }
            public function version(): string { return '1.0.0'; }
            public function category(): string { return 'test'; }
            public function required(): bool { return false; }
            public function dependencies(): array { return $this->moduleDependencies; }
        };
    }
}

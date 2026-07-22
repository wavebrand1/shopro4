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

    public function testItAcceptsCompatibleDependencyVersion(): void
    {
        $registry = new ModuleRegistry([
            $this->definition('foundation', [], [], '4.2.1'),
            $this->definition('extension', ['foundation'], ['foundation' => '^4.0']),
        ]);

        self::assertSame('extension', $registry->get('extension')?->code());
    }

    public function testItRejectsIncompatibleDependencyVersion(): void
    {
        $this->expectExceptionMessage('requires "foundation" version "^4.0"');
        new ModuleRegistry([
            $this->definition('foundation', [], [], '5.0.0'),
            $this->definition('extension', ['foundation'], ['foundation' => '^4.0']),
        ]);
    }

    public function testItRejectsMissingDependencyConstraint(): void
    {
        $this->expectExceptionMessage('must define one version constraint for every dependency');
        new ModuleRegistry([$this->definition('foundation'), $this->definition('extension', ['foundation'], [])]);
    }

    public function testItRejectsInvalidDependencyConstraint(): void
    {
        $this->expectExceptionMessage('Invalid version constraint');
        new ModuleRegistry([
            $this->definition('foundation'),
            $this->definition('extension', ['foundation'], ['foundation' => 'not a constraint']),
        ]);
    }

    /** @param list<string> $dependencies */
    private function definition(string $code, array $dependencies = [], ?array $constraints = null, string $version = '1.0.0'): ModuleDefinition
    {
        $constraints ??= array_fill_keys($dependencies, '*');

        return new class($code, $dependencies, $constraints, $version) implements ModuleDefinition {
            /** @param list<string> $dependencies */
            public function __construct(private readonly string $moduleCode, private readonly array $moduleDependencies, private readonly array $constraints, private readonly string $moduleVersion) {}
            public function code(): string { return $this->moduleCode; }
            public function name(): string { return 'module.test'; }
            public function description(): string { return 'module.test_help'; }
            public function version(): string { return $this->moduleVersion; }
            public function category(): string { return 'test'; }
            public function required(): bool { return false; }
            public function dependencies(): array { return $this->moduleDependencies; }
            public function dependencyVersions(): array { return $this->constraints; }
        };
    }
}

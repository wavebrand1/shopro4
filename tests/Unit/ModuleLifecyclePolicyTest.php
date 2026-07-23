<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Module\Application\ModuleDefinition;
use App\Module\Application\ModuleLifecyclePolicy;
use App\Module\Application\ModuleRegistry;
use App\Module\Domain\Entity\InstalledModule;
use PHPUnit\Framework\TestCase;

final class ModuleLifecyclePolicyTest extends TestCase
{
    public function testRequiredModuleCannotBeDisabled(): void
    {
        $policy = new ModuleLifecyclePolicy(new ModuleRegistry([$this->definition('core', true)]));

        $decision = $policy->canDisable('core', ['core' => new InstalledModule('core', '1.0.0')]);

        self::assertFalse($decision->allowed);
        self::assertSame('module.lifecycle.required', $decision->reason);
    }

    public function testModuleUsedByEnabledDependentCannotBeDisabled(): void
    {
        $policy = new ModuleLifecyclePolicy(new ModuleRegistry([
            $this->definition('foundation'), $this->definition('feature', false, ['foundation']),
        ]));
        $installed = ['foundation' => new InstalledModule('foundation', '1.0.0'), 'feature' => new InstalledModule('feature', '1.0.0')];

        self::assertSame('module.lifecycle.active_dependent', $policy->canDisable('foundation', $installed)->reason);
        $installed['feature']->disable();
        self::assertTrue($policy->canDisable('foundation', $installed)->allowed);
    }

    public function testActiveBackgroundWorkBlocksDisable(): void
    {
        $policy = new ModuleLifecyclePolicy(new ModuleRegistry([$this->definition('feature')]));
        $installed = ['feature' => new InstalledModule('feature', '1.0.0')];

        self::assertSame('module.lifecycle.active_work', $policy->canDisable('feature', $installed, ['newsletter queue'])->reason);
    }

    public function testAllDependenciesMustBeEnabledBeforeModuleCanBeEnabled(): void
    {
        $policy = new ModuleLifecyclePolicy(new ModuleRegistry([
            $this->definition('foundation'), $this->definition('feature', false, ['foundation']),
        ]));
        $foundation = new InstalledModule('foundation', '1.0.0');
        $feature = new InstalledModule('feature', '1.0.0');
        $foundation->disable();
        $feature->disable();
        $installed = ['foundation' => $foundation, 'feature' => $feature];

        self::assertSame('module.lifecycle.inactive_dependency', $policy->canEnable('feature', $installed)->reason);
        $foundation->enable();
        self::assertTrue($policy->canEnable('feature', $installed)->allowed);
    }

    public function testModuleMustBeSynchronizedBeforeItCanBeEnabled(): void
    {
        $policy = new ModuleLifecyclePolicy(new ModuleRegistry([$this->definition('feature')]));
        $feature = new InstalledModule('feature', '0.9.0');
        $feature->disable();

        $decision = $policy->canEnable('feature', ['feature' => $feature]);

        self::assertFalse($decision->allowed);
        self::assertSame('module.lifecycle.unsynchronized', $decision->reason);
    }

    public function testDependencyMustBeSynchronizedBeforeModuleCanBeEnabled(): void
    {
        $policy = new ModuleLifecyclePolicy(new ModuleRegistry([
            $this->definition('foundation'), $this->definition('feature', false, ['foundation']),
        ]));
        $foundation = new InstalledModule('foundation', '0.9.0');
        $feature = new InstalledModule('feature', '1.0.0');
        $feature->disable();

        $decision = $policy->canEnable('feature', ['foundation' => $foundation, 'feature' => $feature]);

        self::assertFalse($decision->allowed);
        self::assertSame('module.lifecycle.unsynchronized_dependency', $decision->reason);
    }

    public function testEntireDependencyChainMustBeAvailableBeforeModuleCanBeEnabled(): void
    {
        $policy = new ModuleLifecyclePolicy(new ModuleRegistry([
            $this->definition('foundation'),
            $this->definition('bridge', false, ['foundation']),
            $this->definition('feature', false, ['bridge']),
        ]));
        $foundation = new InstalledModule('foundation', '1.0.0');
        $bridge = new InstalledModule('bridge', '1.0.0');
        $feature = new InstalledModule('feature', '1.0.0');
        $foundation->disable();
        $feature->disable();
        $installed = ['foundation' => $foundation, 'bridge' => $bridge, 'feature' => $feature];

        $decision = $policy->canEnable('feature', $installed);

        self::assertFalse($decision->allowed);
        self::assertSame('module.lifecycle.inactive_dependency', $decision->reason);
        $foundation->enable();
        self::assertTrue($policy->canEnable('feature', $installed)->allowed);
    }

    /** @param list<string> $dependencies */
    private function definition(string $code, bool $required = false, array $dependencies = []): ModuleDefinition
    {
        return new class($code, $required, $dependencies) implements ModuleDefinition {
            public function __construct(private string $moduleCode, private bool $system, private array $requires) {}
            public function code(): string { return $this->moduleCode; }
            public function name(): string { return 'module.test'; }
            public function description(): string { return 'module.test_help'; }
            public function version(): string { return '1.0.0'; }
            public function category(): string { return 'test'; }
            public function required(): bool { return $this->system; }
            public function dependencies(): array { return $this->requires; }
            public function dependencyVersions(): array { return array_fill_keys($this->requires, '^1.0'); }
        };
    }
}

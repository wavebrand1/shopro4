<?php

declare(strict_types=1);

namespace App\Module\Application;

use Composer\Semver\Semver;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class ModuleRegistry
{
    /** @var array<string, ModuleDefinition> */
    private array $modules = [];

    public function __construct(#[AutowireIterator('shopro.module')] iterable $modules)
    {
        foreach ($modules as $module) {
            if (isset($this->modules[$module->code()])) throw new \LogicException('Duplicate Shopro module code: '.$module->code());
            $this->modules[$module->code()] = $module;
        }
        ksort($this->modules);
        $this->validate();
    }

    /** @return array<string, ModuleDefinition> */
    public function all(): array { return $this->modules; }
    public function get(string $code): ?ModuleDefinition { return $this->modules[$code] ?? null; }

    private function validate(): void
    {
        foreach ($this->modules as $code => $module) {
            if (!preg_match('/^[a-z][a-z0-9_-]{1,79}$/', $code)) throw new \LogicException('Invalid Shopro module code: '.$code);
            if (!preg_match('/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $module->version())) throw new \LogicException('Invalid version of Shopro module: '.$code);
            foreach ($module->dependencies() as $dependency) {
                if (!isset($this->modules[$dependency])) throw new \LogicException(sprintf('Shopro module "%s" requires missing module "%s".', $code, $dependency));
                if ($dependency === $code) throw new \LogicException('Shopro module cannot depend on itself: '.$code);
            }
            $constraints = $module->dependencyVersions();
            $dependencies = $module->dependencies();
            if (array_diff(array_keys($constraints), $dependencies) !== [] || array_diff($dependencies, array_keys($constraints)) !== []) {
                throw new \LogicException(sprintf('Shopro module "%s" must define one version constraint for every dependency.', $code));
            }
            foreach ($constraints as $dependency => $constraint) {
                if (!is_string($constraint) || trim($constraint) === '') throw new \LogicException(sprintf('Invalid version constraint for Shopro module "%s" dependency "%s".', $code, $dependency));
                try {
                    $compatible = Semver::satisfies($this->modules[$dependency]->version(), $constraint);
                } catch (\UnexpectedValueException $exception) {
                    throw new \LogicException(sprintf('Invalid version constraint "%s" for Shopro module "%s" dependency "%s".', $constraint, $code, $dependency), previous: $exception);
                }
                if (!$compatible) {
                    throw new \LogicException(sprintf('Shopro module "%s" requires "%s" version "%s", installed definition provides "%s".', $code, $dependency, $constraint, $this->modules[$dependency]->version()));
                }
            }
        }

        $visiting = [];
        $visited = [];
        $visit = function (string $code) use (&$visit, &$visiting, &$visited): void {
            if (isset($visited[$code])) return;
            if (isset($visiting[$code])) throw new \LogicException('Cyclic Shopro module dependency detected at: '.$code);
            $visiting[$code] = true;
            foreach ($this->modules[$code]->dependencies() as $dependency) $visit($dependency);
            unset($visiting[$code]);
            $visited[$code] = true;
        };
        foreach (array_keys($this->modules) as $code) $visit($code);
    }
}

<?php

declare(strict_types=1);

namespace App\Module\Application;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('shopro.module')]
interface ModuleDefinition
{
    public function code(): string;
    public function name(): string;
    public function description(): string;
    public function version(): string;
    public function category(): string;
    public function required(): bool;
    /** @return list<string> */
    public function dependencies(): array;
    /** @return array<string, string> Map of dependency code to Composer version constraint. */
    public function dependencyVersions(): array;
}

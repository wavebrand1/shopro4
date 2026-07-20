<?php
declare(strict_types=1);
namespace App\Identity\Infrastructure\Module;
use App\Module\Application\ModuleDefinition;
final class IdentityModule implements ModuleDefinition
{
    public function code(): string { return 'identity'; } public function name(): string { return 'module.identity'; }
    public function description(): string { return 'module.identity_help'; } public function version(): string { return '4.0.0'; }
    public function category(): string { return 'system'; } public function required(): bool { return true; }
}

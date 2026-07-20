<?php
declare(strict_types=1);
namespace App\Settings\Infrastructure\Module;
use App\Module\Application\ModuleDefinition;
final class SettingsModule implements ModuleDefinition
{
    public function code(): string { return 'settings'; } public function name(): string { return 'module.settings'; }
    public function description(): string { return 'module.settings_help'; } public function version(): string { return '4.0.0'; }
    public function category(): string { return 'system'; } public function required(): bool { return true; }
    public function dependencies(): array { return []; }
}

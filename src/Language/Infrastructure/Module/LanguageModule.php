<?php
declare(strict_types=1);
namespace App\Language\Infrastructure\Module;
use App\Module\Application\ModuleDefinition;
final class LanguageModule implements ModuleDefinition
{
    public function code(): string { return 'language'; } public function name(): string { return 'module.language'; }
    public function description(): string { return 'module.language_help'; } public function version(): string { return '4.0.0'; }
    public function category(): string { return 'content'; } public function required(): bool { return true; }
    public function dependencies(): array { return ['cms']; }
    public function dependencyVersions(): array { return ['cms' => '^4.0']; }
}

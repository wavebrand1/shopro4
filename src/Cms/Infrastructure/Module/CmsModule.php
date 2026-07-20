<?php
declare(strict_types=1);
namespace App\Cms\Infrastructure\Module;
use App\Module\Application\ModuleDefinition;
final class CmsModule implements ModuleDefinition
{
    public function code(): string { return 'cms'; } public function name(): string { return 'module.cms'; }
    public function description(): string { return 'module.cms_help'; } public function version(): string { return '4.0.0'; }
    public function category(): string { return 'content'; } public function required(): bool { return true; }
    public function dependencies(): array { return ['identity', 'media', 'settings']; }
}

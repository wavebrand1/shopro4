<?php
declare(strict_types=1);
namespace App\Media\Infrastructure\Module;
use App\Module\Application\ModuleDefinition;
final class MediaModule implements ModuleDefinition
{
    public function code(): string { return 'media'; } public function name(): string { return 'module.media'; }
    public function description(): string { return 'module.media_help'; } public function version(): string { return '4.0.0'; }
    public function category(): string { return 'content'; } public function required(): bool { return true; }
}

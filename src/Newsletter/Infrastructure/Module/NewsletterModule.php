<?php
declare(strict_types=1);
namespace App\Newsletter\Infrastructure\Module;
use App\Module\Application\ModuleDefinition;
final class NewsletterModule implements ModuleDefinition
{
    public function code(): string { return 'newsletter'; } public function name(): string { return 'module.newsletter'; }
    public function description(): string { return 'module.newsletter_help'; } public function version(): string { return '4.0.0'; }
    public function category(): string { return 'communication'; } public function required(): bool { return true; }
}

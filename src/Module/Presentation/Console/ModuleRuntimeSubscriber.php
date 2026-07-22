<?php

declare(strict_types=1);

namespace App\Module\Presentation\Console;

use App\Module\Application\ModuleAvailability;
use App\Module\Application\RequiresModule;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: 'console.command')]
final readonly class ModuleRuntimeSubscriber
{
    public function __construct(private ModuleAvailability $modules) {}

    public function __invoke(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();
        if ($command === null) return;

        $attribute = (new \ReflectionClass($command))->getAttributes(RequiresModule::class)[0] ?? null;
        if ($attribute === null) return;

        $requirement = $attribute->newInstance();
        if ($this->modules->isEnabled($requirement->code)) return;

        $event->getOutput()->writeln(sprintf(
            '<error>Polecenie jest niedostępne, ponieważ moduł Shopro "%s" jest wyłączony.</error>',
            $requirement->code,
        ));
        $event->disableCommand();
    }
}

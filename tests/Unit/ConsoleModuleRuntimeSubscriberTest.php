<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Module\Application\ModuleAvailability;
use App\Module\Application\RequiresModule;
use App\Module\Presentation\Console\ModuleRuntimeSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

final class ConsoleModuleRuntimeSubscriberTest extends TestCase
{
    public function testItAllowsCommandOfEnabledModule(): void
    {
        $event = $this->event();
        (new ModuleRuntimeSubscriber($this->availability(true)))($event);

        self::assertTrue($event->commandShouldRun());
        self::assertSame('', $event->getOutput()->fetch());
    }

    public function testItDisablesCommandAndExplainsInactiveModule(): void
    {
        $event = $this->event();
        (new ModuleRuntimeSubscriber($this->availability(false)))($event);

        self::assertFalse($event->commandShouldRun());
        self::assertStringContainsString('moduł Shopro "media" jest wyłączony', $event->getOutput()->fetch());
    }

    private function event(): ConsoleCommandEvent
    {
        return new ConsoleCommandEvent(new ProtectedMediaCommand(), new ArrayInput([]), new BufferedOutput());
    }

    private function availability(bool $enabled): ModuleAvailability
    {
        return new class($enabled) implements ModuleAvailability {
            public function __construct(private readonly bool $enabled) {}
            public function isEnabled(string $code): bool { return $this->enabled && $code === 'media'; }
        };
    }
}

#[RequiresModule('media')]
final class ProtectedMediaCommand extends Command {}

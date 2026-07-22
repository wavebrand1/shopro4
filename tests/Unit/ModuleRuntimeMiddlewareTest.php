<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Module\Application\ModuleAvailability;
use App\Module\Application\ModuleAwareMessage;
use App\Module\Infrastructure\Messenger\ModuleRuntimeMiddleware;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\RecoverableMessageHandlingException;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final class ModuleRuntimeMiddlewareTest extends TestCase
{
    public function testEnabledModuleMessageContinuesThroughBus(): void
    {
        $called = false;
        $middleware = new ModuleRuntimeMiddleware($this->availability(true));
        $envelope = new Envelope($this->message());

        $result = $middleware->handle($envelope, $this->stack($called));

        self::assertSame($envelope, $result);
        self::assertTrue($called);
    }

    public function testDisabledModuleMessageIsKeptForRetry(): void
    {
        $called = false;
        $middleware = new ModuleRuntimeMiddleware($this->availability(false));

        $this->expectException(RecoverableMessageHandlingException::class);
        $this->expectExceptionMessage('module "newsletter" is disabled');
        try {
            $middleware->handle(new Envelope($this->message()), $this->stack($called));
        } finally {
            self::assertFalse($called);
        }
    }

    private function availability(bool $enabled): ModuleAvailability
    {
        return new class($enabled) implements ModuleAvailability {
            public function __construct(private readonly bool $enabled) {}
            public function isEnabled(string $code): bool { return $this->enabled && $code === 'newsletter'; }
        };
    }

    private function message(): ModuleAwareMessage
    {
        return new class implements ModuleAwareMessage {
            public function moduleCode(): string { return 'newsletter'; }
        };
    }

    private function stack(bool &$called): StackInterface
    {
        return new class($called) implements StackInterface {
            public function __construct(private bool &$called) {}
            public function next(): MiddlewareInterface
            {
                return new class($this->called) implements MiddlewareInterface {
                    public function __construct(private bool &$called) {}
                    public function handle(Envelope $envelope, StackInterface $stack): Envelope { $this->called = true; return $envelope; }
                };
            }
        };
    }
}

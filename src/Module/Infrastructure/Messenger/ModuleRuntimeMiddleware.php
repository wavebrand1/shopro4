<?php

declare(strict_types=1);

namespace App\Module\Infrastructure\Messenger;

use App\Module\Application\ModuleAvailability;
use App\Module\Application\ModuleAwareMessage;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\RecoverableMessageHandlingException;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final readonly class ModuleRuntimeMiddleware implements MiddlewareInterface
{
    public function __construct(private ModuleAvailability $modules) {}

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();
        if ($message instanceof ModuleAwareMessage && !$this->modules->isEnabled($message->moduleCode())) {
            throw new RecoverableMessageHandlingException(sprintf(
                'Message %s is waiting because Shopro module "%s" is disabled.',
                $message::class,
                $message->moduleCode(),
            ));
        }

        return $stack->next()->handle($envelope, $stack);
    }
}

<?php

declare(strict_types=1);

namespace App\Newsletter\Application;

use App\Newsletter\Application\Message\SendNewsletterDelivery;
use App\Newsletter\Domain\Entity\NewsletterDelivery;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Transport\Receiver\ListableReceiverInterface;

final readonly class FailedNewsletterMessageCleaner
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        #[Autowire(service: 'messenger.transport.failed')]
        private ListableReceiverInterface $failedTransport,
    ) {
    }

    public function removeForDelivery(int $deliveryId): int
    {
        return $this->remove(static fn (SendNewsletterDelivery $message): bool => $message->deliveryId === $deliveryId);
    }

    public function removeResolved(): int
    {
        return $this->remove(function (SendNewsletterDelivery $message): bool {
            $delivery = $this->entityManager->find(NewsletterDelivery::class, $message->deliveryId);

            return $delivery === null || $delivery->getStatus() === 'sent';
        });
    }

    /** @param callable(SendNewsletterDelivery): bool $shouldRemove */
    private function remove(callable $shouldRemove): int
    {
        $removed = 0;
        foreach ($this->failedTransport->all() as $envelope) {
            $message = $envelope->getMessage();
            if (!$message instanceof SendNewsletterDelivery || !$shouldRemove($message)) {
                continue;
            }
            $this->failedTransport->reject($envelope);
            ++$removed;
        }

        return $removed;
    }
}

<?php

declare(strict_types=1);

namespace App\Newsletter\Application;

use App\Newsletter\Application\Message\SendNewsletterDelivery;
use App\Newsletter\Domain\Entity\NewsletterDelivery;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Transport\Receiver\ListableReceiverInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\ErrorDetailsStamp;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;
use Symfony\Component\Messenger\Stamp\TransportMessageIdStamp;

final readonly class FailedNewsletterMessageCleaner
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        #[Autowire(service: 'messenger.transport.failed')]
        private ListableReceiverInterface $failedTransport,
        private SafeDeliveryError $safeError,
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

    /** @return list<array{id: string, type: string, error: string, failedAt: ?\DateTimeInterface, retries: int}> */
    public function entries(int $limit = 50): array
    {
        $entries = [];
        foreach ($this->failedTransport->all($limit) as $envelope) {
            $id = $envelope->last(TransportMessageIdStamp::class)?->getId();
            if ($id === null) {
                continue;
            }
            $message = $envelope->getMessage();
            $error = $envelope->last(ErrorDetailsStamp::class);
            $redelivery = $envelope->last(RedeliveryStamp::class);
            $entries[] = [
                'id' => (string) $id,
                'type' => (new \ReflectionClass($message))->getShortName(),
                'error' => $error === null ? '' : $this->safeError->sanitize($error->getExceptionMessage()),
                'failedAt' => $redelivery?->getRedeliveredAt(),
                'retries' => $redelivery?->getRetryCount() ?? 0,
            ];
        }

        return $entries;
    }

    public function retry(string $id, MessageBusInterface $bus): bool
    {
        $envelope = $this->failedTransport->find($id);
        if ($envelope === null) {
            return false;
        }
        $bus->dispatch($envelope->getMessage());
        $this->failedTransport->reject($envelope);

        return true;
    }

    public function removeById(string $id): bool
    {
        $envelope = $this->failedTransport->find($id);
        if ($envelope === null) {
            return false;
        }
        $this->failedTransport->reject($envelope);

        return true;
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

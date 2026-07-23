<?php

declare(strict_types=1);

namespace App\Tests\Unit\Newsletter;

use App\Newsletter\Application\FailedNewsletterMessageCleaner;
use App\Newsletter\Application\Message\SendNewsletterDelivery;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\ErrorDetailsStamp;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;
use Symfony\Component\Messenger\Stamp\TransportMessageIdStamp;
use Symfony\Component\Messenger\Transport\Receiver\ListableReceiverInterface;

final class FailedNewsletterMessageCleanerTest extends TestCase
{
    public function testItRemovesOnlyFailedEnvelopeForSelectedDelivery(): void
    {
        $matching = new Envelope(new SendNewsletterDelivery(12));
        $otherNewsletter = new Envelope(new SendNewsletterDelivery(13));
        $otherMessage = new Envelope(new \stdClass());
        $receiver = new InMemoryFailedReceiver([$matching, $otherNewsletter, $otherMessage]);
        $cleaner = new FailedNewsletterMessageCleaner(
            $this->createStub(EntityManagerInterface::class),
            $receiver,
        );

        self::assertSame(1, $cleaner->removeForDelivery(12));
        self::assertSame([$matching], $receiver->rejected);
    }

    public function testItListsTechnicalFailureDetails(): void
    {
        $failedAt = new \DateTimeImmutable('2026-07-23T12:00:00+00:00');
        $envelope = (new Envelope(new SendNewsletterDelivery(12)))
            ->with(new TransportMessageIdStamp(77))
            ->with(new ErrorDetailsStamp(\RuntimeException::class, 0, 'SMTP unavailable'))
            ->with(new RedeliveryStamp(3, $failedAt));
        $cleaner = new FailedNewsletterMessageCleaner(
            $this->createStub(EntityManagerInterface::class),
            new InMemoryFailedReceiver([$envelope]),
        );

        self::assertSame([[
            'id' => '77',
            'type' => 'SendNewsletterDelivery',
            'error' => 'SMTP unavailable',
            'failedAt' => $failedAt,
            'retries' => 3,
        ]], $cleaner->entries());
    }

    public function testItQueuesSelectedFailureBeforeRemovingOriginalEnvelope(): void
    {
        $message = new SendNewsletterDelivery(12);
        $envelope = (new Envelope($message))->with(new TransportMessageIdStamp(77));
        $receiver = new InMemoryFailedReceiver([$envelope]);
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())->method('dispatch')->with($message)->willReturn(new Envelope($message));
        $cleaner = new FailedNewsletterMessageCleaner($this->createStub(EntityManagerInterface::class), $receiver);

        self::assertTrue($cleaner->retry('77', $bus));
        self::assertSame([$envelope], $receiver->rejected);
    }
}

final class InMemoryFailedReceiver implements ListableReceiverInterface
{
    /** @var list<Envelope> */
    public array $rejected = [];

    /** @param list<Envelope> $envelopes */
    public function __construct(private readonly array $envelopes)
    {
    }

    public function all(?int $limit = null): iterable
    {
        return $limit === null ? $this->envelopes : \array_slice($this->envelopes, 0, $limit);
    }

    public function find(mixed $id): ?Envelope
    {
        foreach ($this->envelopes as $envelope) {
            if ((string) $envelope->last(TransportMessageIdStamp::class)?->getId() === (string) $id) {
                return $envelope;
            }
        }

        return null;
    }

    public function get(): iterable
    {
        return [];
    }

    public function ack(Envelope $envelope): void
    {
    }

    public function reject(Envelope $envelope): void
    {
        $this->rejected[] = $envelope;
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Unit\Newsletter;

use App\Newsletter\Application\FailedNewsletterMessageCleaner;
use App\Newsletter\Application\Message\SendNewsletterDelivery;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
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

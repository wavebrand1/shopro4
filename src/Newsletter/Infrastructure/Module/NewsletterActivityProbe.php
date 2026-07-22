<?php

declare(strict_types=1);

namespace App\Newsletter\Infrastructure\Module;

use App\Module\Application\ModuleActivityProbe;
use App\Newsletter\Domain\Entity\NewsletterDelivery;
use Doctrine\ORM\EntityManagerInterface;

final readonly class NewsletterActivityProbe implements ModuleActivityProbe
{
    public function __construct(private EntityManagerInterface $entityManager) {}
    public function moduleCode(): string { return 'newsletter'; }

    public function blockingReasons(): array
    {
        $queued = (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(delivery.id)')
            ->from(NewsletterDelivery::class, 'delivery')
            ->where('delivery.status = :status')
            ->setParameter('status', 'queued')
            ->getQuery()->getSingleScalarResult();

        return $queued > 0 ? ['module.lifecycle.newsletter_queue'] : [];
    }
}

<?php
declare(strict_types=1);

namespace App\Audit\Infrastructure\Persistence\Doctrine;

use App\Audit\Domain\Entity\AuditLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class AuditLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, AuditLog::class); }
    public function save(AuditLog $log): void { $this->getEntityManager()->persist($log); $this->getEntityManager()->flush(); }

    /** @return array{items:list<AuditLog>,total:int} */
    public function filtered(?string $from, ?string $to, ?string $type, int $page, int $limit): array
    {
        $builder = $this->createQueryBuilder('log');
        if ($from) $builder->andWhere('log.createdAt >= :from')->setParameter('from', new \DateTimeImmutable($from.' 00:00:00'));
        if ($to) $builder->andWhere('log.createdAt <= :to')->setParameter('to', new \DateTimeImmutable($to.' 23:59:59'));
        if ($type && in_array($type, ['system', 'admin', 'user'], true)) $builder->andWhere('log.type = :type')->setParameter('type', $type);
        $count = (clone $builder)->select('COUNT(log.id)')->getQuery()->getSingleScalarResult();
        $items = $builder->orderBy('log.createdAt', 'DESC')->setFirstResult(($page - 1) * $limit)->setMaxResults($limit)->getQuery()->getResult();

        return ['items' => $items, 'total' => (int) $count];
    }

    public function clear(): void { $this->createQueryBuilder('log')->delete()->getQuery()->execute(); }
}

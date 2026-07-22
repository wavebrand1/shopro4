<?php
declare(strict_types=1);

namespace App\Audit\Infrastructure\Persistence\Doctrine;

use App\Audit\Application\AuditLogFilters;
use App\Audit\Domain\Entity\AuditLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class AuditLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, AuditLog::class); }
    public function save(AuditLog $log): void { $this->getEntityManager()->persist($log); $this->getEntityManager()->flush(); }

    /** @return array{items:list<AuditLog>,total:int} */
    public function filtered(AuditLogFilters $filters, int $page, int $limit): array
    {
        $builder = $this->createQueryBuilder('log');
        if ($filters->from !== null) $builder->andWhere('log.createdAt >= :from')->setParameter('from', new \DateTimeImmutable($filters->from.' 00:00:00'));
        if ($filters->to !== null) $builder->andWhere('log.createdAt <= :to')->setParameter('to', new \DateTimeImmutable($filters->to.' 23:59:59'));
        if ($filters->type !== null) $builder->andWhere('log.type = :type')->setParameter('type', $filters->type);
        if ($filters->important !== null) $builder->andWhere('log.important = :important')->setParameter('important', $filters->important);
        if ($filters->query !== null) {
            $query = '%'.addcslashes(mb_strtolower($filters->query), '%_\\').'%';
            $builder
                ->andWhere("LOWER(log.action) LIKE :query ESCAPE '\\\\' OR LOWER(log.message) LIKE :query ESCAPE '\\\\' OR LOWER(log.username) LIKE :query ESCAPE '\\\\' OR LOWER(log.ipAddress) LIKE :query ESCAPE '\\\\'")
                ->setParameter('query', $query);
        }
        $count = (clone $builder)->select('COUNT(log.id)')->getQuery()->getSingleScalarResult();
        $items = $builder->orderBy('log.createdAt', 'DESC')->setFirstResult(($page - 1) * $limit)->setMaxResults($limit)->getQuery()->getResult();

        return ['items' => $items, 'total' => (int) $count];
    }

    public function clear(): void { $this->createQueryBuilder('log')->delete()->getQuery()->execute(); }
}

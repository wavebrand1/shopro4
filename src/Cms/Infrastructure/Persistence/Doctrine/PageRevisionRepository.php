<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence\Doctrine;

use App\Cms\Domain\Entity\Page;
use App\Cms\Domain\Entity\PageRevision;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<PageRevision> */
final class PageRevisionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, PageRevision::class); }
    public function nextVersion(Page $page): int { return 1 + (int) $this->createQueryBuilder('r')->select('MAX(r.version)')->where('r.page = :page')->setParameter('page', $page)->getQuery()->getSingleScalarResult(); }
    /** @return list<PageRevision> */ public function forPage(Page $page): array { return $this->findBy(['page' => $page], ['version' => 'DESC']); }
    public function save(PageRevision $revision): void { $this->getEntityManager()->persist($revision); $this->getEntityManager()->flush(); }
}

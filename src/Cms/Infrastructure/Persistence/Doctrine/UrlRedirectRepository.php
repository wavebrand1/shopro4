<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence\Doctrine;

use App\Cms\Domain\Entity\UrlRedirect;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<UrlRedirect> */
final class UrlRedirectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, UrlRedirect::class); }
    public function findActive(string $path): ?UrlRedirect { return $this->findOneBy(['sourcePath' => $path, 'active' => true]); }
    public function save(UrlRedirect $redirect): void { $this->getEntityManager()->persist($redirect); $this->getEntityManager()->flush(); }
    public function remove(UrlRedirect $redirect): void { $this->getEntityManager()->remove($redirect); $this->getEntityManager()->flush(); }
}

<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Persistence\Doctrine;

use App\Identity\Domain\Entity\SiteUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/** @extends ServiceEntityRepository<SiteUser> */
final class SiteUserRepository extends ServiceEntityRepository implements UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, SiteUser::class); }
    public function save(SiteUser $user): void { $this->getEntityManager()->persist($user); $this->getEntityManager()->flush(); }
    public function loadUserByIdentifier(string $identifier): ?UserInterface
    {
        return $this->createQueryBuilder('user')
            ->andWhere('LOWER(user.username) = :identifier OR LOWER(user.email) = :identifier')
            ->andWhere('user.active = true')
            ->setParameter('identifier', mb_strtolower(trim($identifier)))
            ->getQuery()->getOneOrNullResult();
    }
}

<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Persistence\Doctrine;

use App\Identity\Domain\Entity\AdminUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/** @extends ServiceEntityRepository<AdminUser> */
final class AdminUserRepository extends ServiceEntityRepository implements UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdminUser::class);
    }

    public function save(AdminUser $user): void
    {
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function countActiveAdministrators(): int
    {
        return count(array_filter($this->findBy(['active' => true]), static fn (AdminUser $user): bool => $user->isAdministrator()));
    }

    public function loadUserByIdentifier(string $identifier): ?UserInterface
    {
        $identifier = mb_strtolower(trim($identifier));

        return $this->createQueryBuilder('u')
            ->andWhere('LOWER(u.username) = :identifier OR LOWER(u.email) = :identifier')
            ->andWhere('u.active = true')
            ->setParameter('identifier', $identifier)
            ->getQuery()->getOneOrNullResult();
    }

    public function remove(AdminUser $user): void
    {
        $this->getEntityManager()->remove($user);
        $this->getEntityManager()->flush();
    }
}

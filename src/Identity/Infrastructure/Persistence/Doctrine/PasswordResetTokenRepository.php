<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Persistence\Doctrine;

use App\Identity\Domain\Entity\AdminUser;
use App\Identity\Domain\Entity\PasswordResetToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<PasswordResetToken> */
final class PasswordResetTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, PasswordResetToken::class); }

    public function replaceForUser(AdminUser $user, PasswordResetToken $token): void
    {
        $this->createQueryBuilder('t')->delete()->where('t.user = :user')->setParameter('user', $user)->getQuery()->execute();
        $this->getEntityManager()->persist($token);
        $this->getEntityManager()->flush();
    }

    public function findUsable(string $rawToken): ?PasswordResetToken
    {
        $token = $this->findOneBy(['tokenHash' => hash('sha256', $rawToken)]);
        return $token?->isUsable() ? $token : null;
    }

    public function save(PasswordResetToken $token): void
    {
        $this->getEntityManager()->persist($token);
        $this->getEntityManager()->flush();
    }
}

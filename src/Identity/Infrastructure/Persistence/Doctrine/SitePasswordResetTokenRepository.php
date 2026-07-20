<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Persistence\Doctrine;

use App\Identity\Domain\Entity\SitePasswordResetToken;
use App\Identity\Domain\Entity\SiteUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<SitePasswordResetToken> */
final class SitePasswordResetTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, SitePasswordResetToken::class); }

    public function replaceForUser(SiteUser $user, SitePasswordResetToken $token): void
    {
        $this->createQueryBuilder('t')->delete()->where('t.user = :user')->setParameter('user', $user)->getQuery()->execute();
        $this->getEntityManager()->persist($token);
        $this->getEntityManager()->flush();
    }

    public function findUsable(string $rawToken): ?SitePasswordResetToken
    {
        $token = $this->findOneBy(['tokenHash' => hash('sha256', $rawToken)]);
        return $token?->isUsable() ? $token : null;
    }

    public function save(SitePasswordResetToken $token): void
    {
        $this->getEntityManager()->persist($token);
        $this->getEntityManager()->flush();
    }
}

<?php

declare(strict_types=1);

namespace App\Module\Infrastructure\Persistence\Doctrine;

use App\Module\Domain\Entity\InstalledModule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

final class InstalledModuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, InstalledModule::class); }
    /** @return array<string, InstalledModule> */
    public function indexed(): array
    {
        return $this->loadIndexed(false);
    }
    public function save(InstalledModule $module): void { $this->getEntityManager()->persist($module); $this->getEntityManager()->flush(); }

    /**
     * Serializes registry state transitions with deployments. MariaDB and
     * PostgreSQL use row locks; SQLite tests rely on their enclosing transaction.
     *
     * @template T
     * @param callable(array<string, InstalledModule>): T $operation
     * @return T
     */
    public function withLockedRegistry(callable $operation): mixed
    {
        return $this->getEntityManager()->wrapInTransaction(
            fn (): mixed => $operation($this->loadIndexed(true)),
        );
    }

    /**
     * @param array<string, array{version: string, enabledByDefault: bool}> $definitions
     * @return array<string, InstalledModule>
     */
    public function synchronizeAll(array $definitions): array
    {
        return $this->getEntityManager()->wrapInTransaction(function (EntityManagerInterface $entityManager) use ($definitions): array {
            $installed = $this->loadIndexed(true);
            $synchronized = [];

            foreach ($definitions as $code => $definition) {
                $module = $installed[$code] ?? new InstalledModule($code, $definition['version']);
                if (!isset($installed[$code]) && !$definition['enabledByDefault']) $module->disable();
                $module->synchronize($definition['version']);
                $entityManager->persist($module);
                $synchronized[$code] = $module;
            }

            return $synchronized;
        });
    }

    /** @return array<string, InstalledModule> */
    private function loadIndexed(bool $lock): array
    {
        $query = $this->createQueryBuilder('module')->orderBy('module.code', 'ASC')->getQuery();
        if ($lock && !$this->getEntityManager()->getConnection()->getDatabasePlatform() instanceof SQLitePlatform) {
            $query->setLockMode(LockMode::PESSIMISTIC_WRITE);
        }

        $result = [];
        foreach ($query->getResult() as $module) $result[$module->getCode()] = $module;

        return $result;
    }
}

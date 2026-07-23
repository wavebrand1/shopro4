<?php

declare(strict_types=1);

namespace App\Module\Infrastructure\Persistence\Doctrine;

use App\Module\Domain\Entity\InstalledModule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

final class InstalledModuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, InstalledModule::class); }
    /** @return array<string, InstalledModule> */
    public function indexed(): array
    {
        $result = [];
        foreach ($this->findAll() as $module) $result[$module->getCode()] = $module;
        return $result;
    }
    public function save(InstalledModule $module): void { $this->getEntityManager()->persist($module); $this->getEntityManager()->flush(); }

    /**
     * @param array<string, array{version: string, enabledByDefault: bool}> $definitions
     * @return array<string, InstalledModule>
     */
    public function synchronizeAll(array $definitions): array
    {
        return $this->getEntityManager()->wrapInTransaction(function (EntityManagerInterface $entityManager) use ($definitions): array {
            $installed = $this->indexed();
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
}

<?php

declare(strict_types=1);

namespace App\Module\Infrastructure\Persistence\Doctrine;

use App\Module\Domain\Entity\InstalledModule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
}

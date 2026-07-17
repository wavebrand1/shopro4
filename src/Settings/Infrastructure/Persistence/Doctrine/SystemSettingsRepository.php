<?php

declare(strict_types=1);

namespace App\Settings\Infrastructure\Persistence\Doctrine;

use App\Settings\Domain\Entity\SystemSettings;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class SystemSettingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, SystemSettings::class); }

    public function get(): SystemSettings
    {
        if (!$this->isStorageAvailable()) {
            return new SystemSettings();
        }

        return $this->find(1) ?? new SystemSettings();
    }

    public function isStorageAvailable(): bool
    {
        return $this->getEntityManager()->getConnection()->createSchemaManager()->tablesExist(['system_settings']);
    }

    public function save(SystemSettings $settings): void
    {
        if (!$this->isStorageAvailable()) {
            throw new \LogicException('Tabela system_settings nie istnieje. Wdrożenie wymaga wykonania migracji Doctrine.');
        }

        $this->getEntityManager()->persist($settings);
        $this->getEntityManager()->flush();
    }
}

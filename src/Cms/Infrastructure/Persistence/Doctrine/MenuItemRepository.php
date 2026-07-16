<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence\Doctrine;

use App\Cms\Domain\Entity\MenuItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<MenuItem> */
final class MenuItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MenuItem::class);
    }

    /** @return list<MenuItem> */
    public function findActiveByPlace(int $place): array
    {
        return $this->findBy(['active' => true, 'place' => $place], ['parent' => 'ASC', 'position' => 'ASC', 'id' => 'ASC']);
    }

    /** @return list<MenuItem> */
    public function findAllForAdministration(): array
    {
        return $this->findBy([], ['place' => 'DESC', 'parent' => 'ASC', 'position' => 'ASC', 'id' => 'ASC']);
    }

    public function save(MenuItem $item): void
    {
        if ($item->isHomePage()) {
            foreach ($this->findBy(['homePage' => true]) as $homeItem) {
                if ($homeItem !== $item) {
                    $homeItem->setHomePage(false);
                }
            }
        }

        $this->getEntityManager()->persist($item);
        $this->getEntityManager()->flush();
    }

    public function remove(MenuItem $item): void
    {
        $this->getEntityManager()->remove($item);
        $this->getEntityManager()->flush();
    }
}

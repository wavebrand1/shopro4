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

    public function nextPosition(?MenuItem $parent, int $place): int
    {
        $builder = $this->createQueryBuilder('item')
            ->select('MAX(item.position)')
            ->andWhere('item.place = :place')->setParameter('place', $place);
        if ($parent) $builder->andWhere('item.parent = :parent')->setParameter('parent', $parent);
        else $builder->andWhere('item.parent IS NULL');

        return ((int) $builder->getQuery()->getSingleScalarResult()) + 10;
    }

    /** @param list<int> $orderedIds */
    public function reorderSiblings(array $orderedIds): void
    {
        if ($orderedIds === [] || count($orderedIds) !== count(array_unique($orderedIds))) {
            throw new \InvalidArgumentException('Nieprawidłowa lista pozycji menu.');
        }
        /** @var list<MenuItem> $items */
        $items = $this->findBy(['id' => $orderedIds]);
        if (count($items) !== count($orderedIds)) throw new \InvalidArgumentException('Nie znaleziono wszystkich pozycji menu.');

        $byId = [];
        foreach ($items as $item) $byId[$item->getId()] = $item;
        $first = $byId[$orderedIds[0]];
        $parentId = $first->getParent()?->getId();
        $place = $first->getPlace();
        $siblings = $this->findBy(['parent' => $first->getParent(), 'place' => $place]);
        $siblingIds = array_map(static fn (MenuItem $item): int => (int) $item->getId(), $siblings);
        $submittedIds = $orderedIds;
        sort($siblingIds); sort($submittedIds);
        if ($siblingIds !== $submittedIds) throw new \InvalidArgumentException('Lista nie zawiera wszystkich elementów tej grupy menu.');

        foreach ($orderedIds as $index => $id) {
            $item = $byId[$id];
            if ($item->getPlace() !== $place || $item->getParent()?->getId() !== $parentId) {
                throw new \InvalidArgumentException('Pozycje nie należą do tej samej grupy menu.');
            }
            $item->setPosition(($index + 1) * 10);
        }
        $this->getEntityManager()->flush();
    }

    public function remove(MenuItem $item): void
    {
        $this->getEntityManager()->remove($item);
        $this->getEntityManager()->flush();
    }
}

<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence\Doctrine;

use App\Cms\Domain\Entity\Page;
use App\Cms\Domain\Entity\PageTranslation;
use App\Language\Domain\Entity\Language;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Page> */
final class PageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Page::class);
    }

    /** @return list<Page> */
    public function findAllForAdministration(): array
    {
        return $this->createQueryBuilder('page')
            ->orderBy('page.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findPublishedBySlug(string $slug): ?Page
    {
        $query = $this->createQueryBuilder('page')->andWhere('page.slug = :slug')->setParameter('slug', $slug);
        return $this->applyPublicationWindow($query)->getQuery()->getOneOrNullResult();
    }

    public function findPublishedHomePage(): ?Page
    {
        $query = $this->createQueryBuilder('page')->andWhere('page.homePage = true');
        return $this->applyPublicationWindow($query)->getQuery()->getOneOrNullResult();
    }

    public function findPublishedErrorPage(): ?Page
    {
        $query = $this->createQueryBuilder('page')
            ->andWhere('page.errorPage = true')->andWhere('page.access = :access')->andWhere('page.adminOnly = false')
            ->setParameter('access', 'Public');
        return $this->applyPublicationWindow($query)->getQuery()->getOneOrNullResult();
    }

    /** @return list<Page> */
    public function findPublicForSitemap(): array
    {
        $query = $this->createQueryBuilder('page')
            ->andWhere('page.access = :access')
            ->andWhere('page.adminOnly = :adminOnly')
            ->setParameter('access', 'Public')
            ->setParameter('adminOnly', false)
            ->orderBy('page.updatedAt', 'DESC');
        return $this->applyPublicationWindow($query)->getQuery()->getResult();
    }

    /** @return list<Page> */
    public function searchPublic(string $query, int $limit, int $offset): array
    {
        $needle = '%'.self::escapeLike(mb_strtolower($query)).'%';

        $builder = $this->createQueryBuilder('page')
            ->andWhere('page.access = :access')
            ->andWhere('page.adminOnly = false')
            ->andWhere('page.errorPage = false')
            ->andWhere('page.searchPage = false')
            ->andWhere('(LOWER(page.title) LIKE :query ESCAPE \'!\' OR LOWER(page.caption) LIKE :query ESCAPE \'!\' OR LOWER(page.description) LIKE :query ESCAPE \'!\' OR LOWER(page.content) LIKE :query ESCAPE \'!\' OR LOWER(page.builderData) LIKE :query ESCAPE \'!\')')
            ->setParameter('access', 'Public')
            ->setParameter('query', $needle)
            ->orderBy('page.title', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);
        return $this->applyPublicationWindow($builder)->getQuery()->getResult();
    }

    public function countPublicSearch(string $query): int
    {
        $needle = '%'.self::escapeLike(mb_strtolower($query)).'%';

        $builder = $this->createQueryBuilder('page')
            ->select('COUNT(page.id)')
            ->andWhere('page.access = :access')->andWhere('page.adminOnly = false')
            ->andWhere('page.errorPage = false')->andWhere('page.searchPage = false')
            ->andWhere('(LOWER(page.title) LIKE :query ESCAPE \'!\' OR LOWER(page.caption) LIKE :query ESCAPE \'!\' OR LOWER(page.description) LIKE :query ESCAPE \'!\' OR LOWER(page.content) LIKE :query ESCAPE \'!\' OR LOWER(page.builderData) LIKE :query ESCAPE \'!\')')
            ->setParameter('access', 'Public')->setParameter('query', $needle);
        return (int) $this->applyPublicationWindow($builder)->getQuery()->getSingleScalarResult();
    }

    /** @return list<PageTranslation> */
    public function searchPublicTranslations(Language $language, string $query, int $limit, int $offset): array
    {
        $needle = '%'.self::escapeLike(mb_strtolower($query)).'%';

        $builder = $this->getEntityManager()->createQueryBuilder()
            ->select('translation')->from(PageTranslation::class, 'translation')->join('translation.page', 'page')
            ->andWhere('translation.language = :language')->andWhere('translation.published = true')
            ->andWhere('page.access = :access')->andWhere('page.adminOnly = false')
            ->andWhere('page.errorPage = false')->andWhere('page.searchPage = false')
            ->andWhere('(LOWER(translation.title) LIKE :query ESCAPE \'!\' OR LOWER(translation.caption) LIKE :query ESCAPE \'!\' OR LOWER(translation.description) LIKE :query ESCAPE \'!\' OR LOWER(translation.content) LIKE :query ESCAPE \'!\' OR LOWER(translation.builderData) LIKE :query ESCAPE \'!\')')
            ->setParameter('language', $language)->setParameter('access', 'Public')->setParameter('query', $needle)
            ->orderBy('translation.title', 'ASC')->setFirstResult($offset)->setMaxResults($limit);
        return $this->applyPublicationWindow($builder)->getQuery()->getResult();
    }

    public function countPublicTranslationSearch(Language $language, string $query): int
    {
        $needle = '%'.self::escapeLike(mb_strtolower($query)).'%';

        $builder = $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(translation.id)')->from(PageTranslation::class, 'translation')->join('translation.page', 'page')
            ->andWhere('translation.language = :language')->andWhere('translation.published = true')
            ->andWhere('page.access = :access')->andWhere('page.adminOnly = false')
            ->andWhere('page.errorPage = false')->andWhere('page.searchPage = false')
            ->andWhere('(LOWER(translation.title) LIKE :query ESCAPE \'!\' OR LOWER(translation.caption) LIKE :query ESCAPE \'!\' OR LOWER(translation.description) LIKE :query ESCAPE \'!\' OR LOWER(translation.content) LIKE :query ESCAPE \'!\' OR LOWER(translation.builderData) LIKE :query ESCAPE \'!\')')
            ->setParameter('language', $language)->setParameter('access', 'Public')->setParameter('query', $needle);
        return (int) $this->applyPublicationWindow($builder)->getQuery()->getSingleScalarResult();
    }

    public function countPubliclyAvailable(): int
    {
        $builder = $this->createQueryBuilder('page')->select('COUNT(page.id)');
        return (int) $this->applyPublicationWindow($builder)->getQuery()->getSingleScalarResult();
    }

    private function applyPublicationWindow(QueryBuilder $builder, string $alias = 'page'): QueryBuilder
    {
        return $builder
            ->andWhere($alias.'.published = true')
            ->andWhere('('.$alias.'.publishAt IS NULL OR '.$alias.'.publishAt <= :publicationNow)')
            ->andWhere('('.$alias.'.unpublishAt IS NULL OR '.$alias.'.unpublishAt > :publicationNow)')
            ->setParameter('publicationNow', new \DateTimeImmutable());
    }

    private static function escapeLike(string $value): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value);
    }

    public function save(Page $page): void
    {
        $roles = [
            'homePage' => $page->isHomePage(), 'errorPage' => $page->isErrorPage(),
            'loginPage' => $page->isLoginPage(), 'activationPage' => $page->isActivationPage(),
            'accountPage' => $page->isAccountPage(), 'registrationPage' => $page->isRegistrationPage(),
            'searchPage' => $page->isSearchPage(), 'sitemapPage' => $page->isSitemapPage(),
            'profilePage' => $page->isProfilePage(), 'termsPage' => $page->isTermsPage(),
        ];
        foreach ($roles as $field => $enabled) {
            if (!$enabled) { continue; }
            $query = $this->createQueryBuilder('other')->update()->set('other.'.$field, ':disabled')->setParameter('disabled', false);
            if (null !== $page->getId()) { $query->where('other.id != :id')->setParameter('id', $page->getId()); }
            $query->getQuery()->execute();
        }
        $this->getEntityManager()->persist($page);
        $this->getEntityManager()->flush();
    }

    public function remove(Page $page): void
    {
        $this->getEntityManager()->remove($page);
        $this->getEntityManager()->flush();
    }
}

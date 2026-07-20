<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence\Doctrine;

use App\Cms\Domain\Entity\Page;
use App\Cms\Domain\Entity\PageTranslation;
use App\Language\Domain\Entity\Language;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
        return $this->findOneBy(['slug' => $slug, 'published' => true]);
    }

    public function findPublishedHomePage(): ?Page
    {
        return $this->findOneBy(['homePage' => true, 'published' => true]);
    }

    /** @return list<Page> */
    public function findPublicForSitemap(): array
    {
        return $this->createQueryBuilder('page')
            ->andWhere('page.published = :published')
            ->andWhere('page.access = :access')
            ->andWhere('page.adminOnly = :adminOnly')
            ->setParameter('published', true)
            ->setParameter('access', 'Public')
            ->setParameter('adminOnly', false)
            ->orderBy('page.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<Page> */
    public function searchPublic(string $query, int $limit, int $offset): array
    {
        $needle = '%'.self::escapeLike(mb_strtolower($query)).'%';

        return $this->createQueryBuilder('page')
            ->andWhere('page.published = true')
            ->andWhere('page.access = :access')
            ->andWhere('page.adminOnly = false')
            ->andWhere('page.errorPage = false')
            ->andWhere('page.searchPage = false')
            ->andWhere('(LOWER(page.title) LIKE :query ESCAPE \'!\' OR LOWER(page.caption) LIKE :query ESCAPE \'!\' OR LOWER(page.description) LIKE :query ESCAPE \'!\' OR LOWER(page.content) LIKE :query ESCAPE \'!\' OR LOWER(page.builderData) LIKE :query ESCAPE \'!\')')
            ->setParameter('access', 'Public')
            ->setParameter('query', $needle)
            ->orderBy('page.title', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()->getResult();
    }

    public function countPublicSearch(string $query): int
    {
        $needle = '%'.self::escapeLike(mb_strtolower($query)).'%';

        return (int) $this->createQueryBuilder('page')
            ->select('COUNT(page.id)')
            ->andWhere('page.published = true')->andWhere('page.access = :access')->andWhere('page.adminOnly = false')
            ->andWhere('page.errorPage = false')->andWhere('page.searchPage = false')
            ->andWhere('(LOWER(page.title) LIKE :query ESCAPE \'!\' OR LOWER(page.caption) LIKE :query ESCAPE \'!\' OR LOWER(page.description) LIKE :query ESCAPE \'!\' OR LOWER(page.content) LIKE :query ESCAPE \'!\' OR LOWER(page.builderData) LIKE :query ESCAPE \'!\')')
            ->setParameter('access', 'Public')->setParameter('query', $needle)
            ->getQuery()->getSingleScalarResult();
    }

    /** @return list<PageTranslation> */
    public function searchPublicTranslations(Language $language, string $query, int $limit, int $offset): array
    {
        $needle = '%'.self::escapeLike(mb_strtolower($query)).'%';

        return $this->getEntityManager()->createQueryBuilder()
            ->select('translation')->from(PageTranslation::class, 'translation')->join('translation.page', 'page')
            ->andWhere('translation.language = :language')->andWhere('translation.published = true')
            ->andWhere('page.published = true')->andWhere('page.access = :access')->andWhere('page.adminOnly = false')
            ->andWhere('page.errorPage = false')->andWhere('page.searchPage = false')
            ->andWhere('(LOWER(translation.title) LIKE :query ESCAPE \'!\' OR LOWER(translation.caption) LIKE :query ESCAPE \'!\' OR LOWER(translation.description) LIKE :query ESCAPE \'!\' OR LOWER(translation.content) LIKE :query ESCAPE \'!\' OR LOWER(translation.builderData) LIKE :query ESCAPE \'!\')')
            ->setParameter('language', $language)->setParameter('access', 'Public')->setParameter('query', $needle)
            ->orderBy('translation.title', 'ASC')->setFirstResult($offset)->setMaxResults($limit)
            ->getQuery()->getResult();
    }

    public function countPublicTranslationSearch(Language $language, string $query): int
    {
        $needle = '%'.self::escapeLike(mb_strtolower($query)).'%';

        return (int) $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(translation.id)')->from(PageTranslation::class, 'translation')->join('translation.page', 'page')
            ->andWhere('translation.language = :language')->andWhere('translation.published = true')
            ->andWhere('page.published = true')->andWhere('page.access = :access')->andWhere('page.adminOnly = false')
            ->andWhere('page.errorPage = false')->andWhere('page.searchPage = false')
            ->andWhere('(LOWER(translation.title) LIKE :query ESCAPE \'!\' OR LOWER(translation.caption) LIKE :query ESCAPE \'!\' OR LOWER(translation.description) LIKE :query ESCAPE \'!\' OR LOWER(translation.content) LIKE :query ESCAPE \'!\' OR LOWER(translation.builderData) LIKE :query ESCAPE \'!\')')
            ->setParameter('language', $language)->setParameter('access', 'Public')->setParameter('query', $needle)
            ->getQuery()->getSingleScalarResult();
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

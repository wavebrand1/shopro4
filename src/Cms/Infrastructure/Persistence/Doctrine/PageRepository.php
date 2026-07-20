<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence\Doctrine;

use App\Cms\Domain\Entity\Page;
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

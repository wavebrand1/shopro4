<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Cms\Application\SystemPageInstaller;
use App\Cms\Domain\Entity\Page;
use App\Cms\Domain\Entity\PageTranslation;
use App\Language\Domain\Entity\Language;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class SystemPageInstallerTest extends KernelTestCase
{
    public function testItCreatesMissingSystemPagesAndIsIdempotent(): void
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $schema = new SchemaTool($entityManager);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schema->dropSchema($metadata);
        $schema->createSchema($metadata);

        $polish = new Language();
        $polish->setName('Polski');
        $polish->setCode('pl');
        $polish->setDefaultLanguage(true);
        $polish->setActive(true);
        $entityManager->persist($polish);

        $english = new Language();
        $english->setName('English');
        $english->setCode('en');
        $english->setDefaultLanguage(false);
        $english->setActive(true);
        $entityManager->persist($english);

        $homepage = new Page();
        $homepage->setTitle('Istniejąca strona główna');
        $homepage->setSlug('start');
        $homepage->setPublished(true);
        $homepage->setHomePage(true);
        $entityManager->persist($homepage);
        $entityManager->flush();

        $installer = self::getContainer()->get(SystemPageInstaller::class);
        self::assertSame(
            ['created' => 10, 'translations' => 11, 'existing' => 1],
            $installer->install(),
        );

        $pages = $entityManager->getRepository(Page::class);
        self::assertSame(11, $pages->count([]));
        self::assertSame($homepage->getId(), $pages->findOneBy(['homePage' => true])?->getId());
        foreach ([
            'errorPage', 'adminOnly', 'loginPage', 'activationPage', 'accountPage',
            'registrationPage', 'searchPage', 'sitemapPage', 'profilePage', 'termsPage',
        ] as $role) {
            self::assertSame(1, $pages->count([$role => true]), $role);
        }
        self::assertSame(11, $entityManager->getRepository(PageTranslation::class)->count(['language' => $english]));

        self::assertSame(
            ['created' => 0, 'translations' => 0, 'existing' => 11],
            $installer->install(),
        );
        self::assertSame(11, $pages->count([]));
    }
}

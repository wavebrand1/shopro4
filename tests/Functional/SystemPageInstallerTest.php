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
use Symfony\Contracts\Translation\TranslatorInterface;

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
        foreach (['loginPage', 'activationPage', 'accountPage', 'registrationPage', 'searchPage', 'sitemapPage', 'profilePage'] as $role) {
            $systemPage = $pages->findOneBy([$role => true]);
            self::assertInstanceOf(Page::class, $systemPage);
            self::assertSame(1, substr_count($systemPage->getBuilderData(), '"type":"system_role"'), $role);
            $translation = $entityManager->getRepository(PageTranslation::class)->findOneBy(['page' => $systemPage, 'language' => $english]);
            self::assertInstanceOf(PageTranslation::class, $translation);
            self::assertSame(1, substr_count($translation->getBuilderData(), '"type":"system_role"'), $role.' translation');
        }
        self::assertStringContainsString('Wróć na stronę główną', $pages->findOneBy(['errorPage' => true])->getBuilderData());
        self::assertStringContainsString('Reklamacje i odpowiedzialność', $pages->findOneBy(['termsPage' => true])->getBuilderData());
        self::assertStringNotContainsString('"type":"rich_text"', $pages->findOneBy(['loginPage' => true])->getBuilderData());

        $englishTerms = $entityManager->getRepository(PageTranslation::class)->findOneBy([
            'page' => $pages->findOneBy(['termsPage' => true]),
            'language' => $english,
        ]);
        self::assertInstanceOf(PageTranslation::class, $englishTerms);
        self::assertStringContainsString('Complaints and liability', $englishTerms->getBuilderData());

        $terms = $pages->findOneBy(['termsPage' => true]);
        self::assertInstanceOf(Page::class, $terms);
        $terms->setBuilderData('[{"id":"custom","type":"layout_section","data":{"container":"full","widths":[100],"columns":[[{"id":"custom-text","type":"rich_text","data":{"content":"<p>Treść administratora</p>"}}]]}}]');
        $errorPage = $pages->findOneBy(['errorPage' => true]);
        self::assertInstanceOf(Page::class, $errorPage);
        $legacyHeading = 'Nie znaleziono strony';
        $legacyLead = 'Strona, której szukasz, nie istnieje lub została przeniesiona.';
        $errorPage->setBuilderData(json_encode([[
            'id' => 'system-section-'.substr(hash('sha256', $legacyHeading), 0, 12),
            'type' => 'layout_section',
            'data' => ['container' => 'grid', 'widths' => [100], 'columns' => [[[
                'id' => 'system-content-'.substr(hash('sha256', $legacyLead), 0, 12),
                'type' => 'rich_text',
                'data' => ['content' => '<h1>'.$legacyHeading.'</h1><p>'.$legacyLead.'</p>'],
            ]]]],
        ]], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $entityManager->flush();

        self::assertSame(
            ['created' => 0, 'translations' => 0, 'existing' => 11],
            $installer->install(),
        );
        self::assertSame(11, $pages->count([]));
        self::assertSame(1, substr_count($pages->findOneBy(['registrationPage' => true])->getBuilderData(), '"type":"system_role"'));
        self::assertStringContainsString('Treść administratora', $terms->getBuilderData());
        self::assertStringContainsString('Wróć na stronę główną', $errorPage->getBuilderData());

        $translator = self::getContainer()->get(TranslatorInterface::class);
        self::assertSame(
            'Ta strona systemowa musi zawierać dokładnie jeden komponent roli strony.',
            $translator->trans('validation.page.system_role_required', domain: 'validators', locale: 'pl'),
        );
        self::assertSame(
            'The page role component only works on a page with an assigned functional role. Assign a page role or remove this component.',
            $translator->trans('validation.page.system_role_forbidden', domain: 'validators', locale: 'en'),
        );
    }
}

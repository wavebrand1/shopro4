<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Cms\Application\SystemPageInstaller;
use App\Cms\Domain\Entity\Page;
use App\Cms\Domain\Entity\PageTranslation;
use App\Language\Domain\Entity\Language;
use App\Module\Application\ModuleRegistry;
use App\Module\Infrastructure\Persistence\Doctrine\InstalledModuleRepository;
use App\Settings\Infrastructure\Persistence\Doctrine\SystemSettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SystemPageRoutingTest extends WebTestCase
{
    public function testAssignedPagesOpenTheirSystemFunctions(): void
    {
        $client = self::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schema = new SchemaTool($entityManager);
        $schema->dropSchema($metadata);
        $schema->createSchema($metadata);

        $definitions = [];
        foreach (self::getContainer()->get(ModuleRegistry::class)->all() as $definition) {
            $definitions[$definition->code()] = [
                'version' => $definition->version(),
                'enabledByDefault' => true,
            ];
        }
        self::getContainer()->get(InstalledModuleRepository::class)->synchronizeAll($definitions);

        $polish = new Language();
        $polish->setName('Polski');
        $polish->setCode('pl');
        $polish->setDefaultLanguage(true);
        $polish->setActive(true);
        $entityManager->persist($polish);

        $english = new Language();
        $english->setName('English');
        $english->setCode('en');
        $english->setActive(true);
        $entityManager->persist($english);
        $entityManager->flush();

        self::getContainer()->get(SystemPageInstaller::class)->install();
        $settings = self::getContainer()->get(SystemSettingsRepository::class)->get();
        $configuration = $settings->getConfiguration();
        $configuration['registration_allowed'] = true;
        $settings->setConfiguration($configuration);
        self::getContainer()->get(SystemSettingsRepository::class)->save($settings);

        $client->request('GET', '/logowanie');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.builder-system-role form');
        self::assertSelectorExists('a[href="/aktywacja-konta"]');
        self::assertSelectorExists('a[href="/rejestracja"]');

        $client->request('GET', '/rejestracja');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.builder-system-role form[name="site_registration"]');

        $client->request('GET', '/register');
        self::assertResponseRedirects('/rejestracja', 308);

        $client->request('GET', '/strona-glowna');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Witamy na naszej stronie');

        $client->request('GET', '/mapa-witryny');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Mapa witryny');
        self::assertSelectorExists('.builder-system-role .site-sitemap');
        self::assertSelectorExists('.site-sitemap__list a');

        $client->request('GET', '/aktywacja-konta');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.builder-system-role form');

        $client->request('GET', '/wyszukiwanie?q=witamy');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.builder-system-role .site-search-form');

        foreach ([
            '/login' => '/logowanie',
            '/activation/resend' => '/aktywacja-konta',
            '/account' => '/moje-konto',
            '/search?q=test' => '/wyszukiwanie?q=test',
            '/site-map' => '/mapa-witryny',
            '/account/profile' => '/profil-uzytkownika',
        ] as $source => $target) {
            $client->request('GET', $source);
            self::assertResponseRedirects($target, 308, $source);
        }

        $client->request('GET', '/regulamin');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Regulamin serwisu');

        $client->request('GET', '/strefa-administratora');
        self::assertResponseStatusCodeSame(404);

        $client->request('GET', '/en/sign-in');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.builder-system-role form');

        $client->request('GET', '/en/site-search');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.builder-system-role .site-search-form');

        $client->request('GET', '/en/sitemap');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.builder-system-role .site-sitemap');

        $searchPage = $entityManager->getRepository(Page::class)->findOneBy(['searchPage' => true]);
        self::assertInstanceOf(Page::class, $searchPage);
        $searchPage->setSlug('szukaj-w-witrynie');
        $searchTranslation = $entityManager->getRepository(PageTranslation::class)->findOneBy([
            'page' => $searchPage,
            'language' => $english,
        ]);
        self::assertInstanceOf(PageTranslation::class, $searchTranslation);
        $searchTranslation->setSlug('find-on-site');
        $entityManager->flush();

        $client->request('GET', '/?lang=pl');
        $client->request('GET', '/szukaj-w-witrynie?q=witamy');
        self::assertResponseIsSuccessful();
        self::assertSame('/szukaj-w-witrynie', $client->getCrawler()->filter('.site-search-form')->attr('action'));

        $client->request('GET', '/en/find-on-site');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form[action="/en/find-on-site"]');

        $client->request('GET', '/search?q=test');
        self::assertResponseRedirects('/en/find-on-site?q=test', 308);

        $registrationPage = $entityManager->getRepository(Page::class)->findOneBy(['registrationPage' => true]);
        self::assertInstanceOf(Page::class, $registrationPage);
        $registrationPage->setSlug('zaloz-konto');
        $entityManager->flush();

        $client->request('GET', '/?lang=pl');
        $client->request('GET', '/rejestracja');
        self::assertResponseRedirects('/zaloz-konto', 308);

        $client->request('GET', '/zaloz-konto');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form[name="site_registration"]');

        $client->request('POST', '/zaloz-konto', ['site_registration' => []]);
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form[name="site_registration"]');
    }
}

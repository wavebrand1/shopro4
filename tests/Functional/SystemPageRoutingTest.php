<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Cms\Application\SystemPageInstaller;
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

        foreach ([
            '/logowanie' => '/login',
            '/aktywacja-konta' => '/activation/resend',
            '/moje-konto' => '/account',
            '/wyszukiwanie' => '/search',
            '/mapa-witryny' => '/site-map',
            '/profil-uzytkownika' => '/account/profile',
        ] as $source => $target) {
            $client->request('GET', $source);
            self::assertResponseRedirects($target, message: $source);
        }

        $client->request('GET', '/login');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.builder-system-role form');

        $client->request('GET', '/rejestracja');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.builder-system-role form[name="site_registration"]');

        $client->request('GET', '/register');
        self::assertResponseRedirects('/rejestracja', 308);

        $client->request('GET', '/strona-glowna');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Witamy na naszej stronie');

        $client->request('GET', '/site-map');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Mapa witryny');
        self::assertSelectorExists('.builder-system-role .site-sitemap');
        self::assertSelectorExists('.site-sitemap__list a');

        $client->request('GET', '/activation/resend');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.builder-system-role form');

        $client->request('GET', '/search?q=witamy');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.builder-system-role .site-search-form');

        $client->request('GET', '/regulamin');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Regulamin serwisu');

        $client->request('GET', '/strefa-administratora');
        self::assertResponseStatusCodeSame(404);

        foreach ([
            '/en/sign-in' => '/login',
            '/en/site-search' => '/en/search',
            '/en/sitemap' => '/en/site-map',
        ] as $source => $target) {
            $client->request('GET', $source);
            self::assertResponseRedirects($target, message: $source);
        }

        $client->request('GET', '/en/search');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.builder-system-role .site-search-form');
    }
}

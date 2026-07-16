<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Cms\Infrastructure\Persistence\Doctrine\PageRepository;
use App\Identity\Domain\Entity\AdminUser;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AdminCmsTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    public function testAdministratorCanLogInAndPublishPage(): void
    {
        $user = new AdminUser('admin@example.test');
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $user->setPassword($hasher->hashPassword($user, 'very-secure-password'));
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->request('GET', '/admin');
        self::assertResponseRedirects('/admin/login');

        $this->client->followRedirect();
        $this->client->submitForm('Zaloguj się', [
            '_username' => 'admin@example.test',
            '_password' => 'very-secure-password',
        ]);
        self::assertResponseRedirects('/admin');

        $this->client->followRedirect();
        self::assertSelectorTextContains('h1', 'Dzień dobry');

        $this->client->request('GET', '/admin/pages/new');
        self::assertSelectorNotExists('.modern-nav a[href="/admin/pages/new"]');
        $this->client->submitForm('Zapisz', [
            'page[title]' => 'O nas',
            'page[slug]' => 'o-nas',
            'page[content]' => 'Pierwsza treść Shopro 4.0.',
            'page[published]' => true,
        ]);
        self::assertResponseRedirects('/admin/pages');

        $this->client->request('GET', '/o-nas');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'O nas');
        self::assertSelectorTextContains('.public-article__body', 'Pierwsza treść Shopro 4.0.');

        $page = self::getContainer()->get(PageRepository::class)->findPublishedBySlug('o-nas');
        self::assertNotNull($page);

        $this->client->request('GET', '/admin/pages/'.$page->getId().'/edit');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('textarea[name="page[caption]"]');
        self::assertSelectorExists('input[name="page[homePage]"]');
        self::assertSelectorExists('textarea[name="page[javascript]"]');
        self::assertSelectorExists('[data-component-builder]');
        self::assertSelectorExists('select[name="page[editorMode]"]');
        self::assertSelectorExists('input[name="page[builderData]"]');
        self::assertSelectorExists('input[name="page[builderCss]"]');

        $this->client->request('GET', '/admin/pages');
        self::assertSelectorExists('form[action="/admin/pages/'.$page->getId().'/duplicate"]');

        $this->client->request('GET', '/admin/menu/new');
        $this->client->submitForm('Zapisz pozycję', [
            'menu_item[name]' => 'O nas',
            'menu_item[caption]' => 'Poznaj naszą firmę',
            'menu_item[contentType]' => 'page',
            'menu_item[page]' => (string) $page->getId(),
            'menu_item[target]' => '_self',
            'menu_item[position]' => 10,
            'menu_item[place]' => 1,
            'menu_item[active]' => true,
        ]);
        self::assertResponseRedirects('/admin/menu');

        $this->client->request('GET', '/admin/menu/new');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('select[name="menu_item[parent]"] option');

        $this->client->request('GET', '/');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.site-nav', 'O nas');
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Cms\Domain\Entity\MenuItem;
use App\Cms\Infrastructure\Persistence\Doctrine\MenuItemRepository;
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
        $this->client->submitForm('Zapisz podstronę', [
            'page[title]' => 'O nas',
            'page[slug]' => 'o-nas',
            'page[content]' => 'Pierwsza treść Shopro 4.0.',
            'page[builderData]' => '[{"id":"section","type":"layout_section","data":{"container":"grid","widths":[100],"columns":[[{"id":"text","type":"rich_text","data":{"content":"<p>Pierwsza treść Shopro 4.0.</p><script>alert(1)</script>"}}]]}}]',
            'page[published]' => true,
        ]);
        self::assertResponseRedirects('/admin/pages');

        $this->client->request('GET', '/o-nas');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.public-article__body', 'Pierwsza treść Shopro 4.0.');
        self::assertStringNotContainsString('<script>alert(1)</script>', (string) $this->client->getResponse()->getContent());

        $page = self::getContainer()->get(PageRepository::class)->findPublishedBySlug('o-nas');
        self::assertNotNull($page);

        $this->client->request('GET', '/admin/pages/'.$page->getId().'/edit');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('textarea[name="page[caption]"]');
        self::assertSelectorExists('input[name="page[homePage]"]');
        self::assertSelectorExists('textarea[name="page[javascript]"]');
        self::assertSelectorExists('[data-component-builder]');
        self::assertSelectorExists('input[name="page[editorMode]"]');
        self::assertSelectorExists('input[name="page[builderData]"]');
        self::assertSelectorExists('input[name="page[builderCss]"]');
        self::assertSelectorExists('.ui-field input[name="page[title]"]');
        self::assertSelectorExists('.ui-choice input[name="page[published]"]');

        $this->client->submitForm('Zapisz i kontynuuj edycję', [
            'page[title]' => 'O nas — edycja',
        ]);
        self::assertResponseRedirects('/admin/pages/'.$page->getId().'/edit');

        $page = self::getContainer()->get(PageRepository::class)->find($page->getId());
        self::assertNotNull($page);
        $page->setEditorMode('components');
        $page->setBuilderData(json_encode([[
            'id' => 'hero-test',
            'type' => 'hero',
            'data' => [
                'badge' => 'Komponent testowy',
                'heading' => 'Strona z komponentów',
                'highlight' => 'działa poprawnie',
                'text' => 'Kontrolowana treść strony.',
            ],
        ]], JSON_THROW_ON_ERROR));
        self::getContainer()->get(PageRepository::class)->save($page);
        $this->client->request('GET', '/o-nas');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.site-hero h1', 'działa poprawnie');

        $this->client->request('GET', '/admin/pages');
        self::assertSelectorExists('form[action="/admin/pages/'.$page->getId().'/duplicate"]');

        $this->client->request('GET', '/admin/menu/new');
        $this->client->submitForm('Zapisz pozycję', [
            'menu_item[name]' => 'O nas',
            'menu_item[caption]' => 'Poznaj naszą firmę',
            'menu_item[contentType]' => 'page',
            'menu_item[page]' => (string) $page->getId(),
            'menu_item[target]' => '_self',
            'menu_item[place]' => 1,
            'menu_item[active]' => true,
        ]);
        self::assertResponseRedirects('/admin/menu');

        $this->client->request('GET', '/admin/menu/new');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('select[name="menu_item[parent]"] option');
        self::assertSelectorNotExists('input[name="menu_item[position]"]');

        $menuPage = $this->client->request('GET', '/admin/menu');
        self::assertSelectorExists('[data-menu-sort-group] [data-menu-row]');
        self::assertSelectorExists('[data-menu-sort][data-move-url="/admin/menu/move"]');
        self::assertSelectorExists('[data-menu-nest-target]');
        $sortContainer = $menuPage->filter('[data-menu-sort]');
        $this->client->request('POST', '/admin/menu/reorder', [
            '_token' => $sortContainer->attr('data-reorder-token'),
            'items' => [(string) $menuPage->filter('[data-menu-row]')->attr('data-menu-id')],
        ]);
        self::assertResponseIsSuccessful();

        $menuRepository = self::getContainer()->get(MenuItemRepository::class);
        $headerItem = $menuRepository->findOneBy(['name' => 'O nas']);
        self::assertNotNull($headerItem);
        $headerParent = new MenuItem();
        $headerParent->setName('Firma');
        $headerParent->setContentType(MenuItem::TYPE_PLACEHOLDER);
        $headerParent->setPlace(MenuItem::PLACE_HEADER);
        $headerParent->setPosition(10);
        $menuRepository->save($headerParent);
        $headerItem->setParent($headerParent);
        $headerItem->setPosition(10);
        $menuRepository->save($headerItem);

        $footerParent = new MenuItem();
        $footerParent->setName('Informacje');
        $footerParent->setContentType(MenuItem::TYPE_PLACEHOLDER);
        $footerParent->setPlace(MenuItem::PLACE_FOOTER);
        $footerParent->setPosition(10);
        $menuRepository->save($footerParent);
        $headerItemId = (int) $headerItem->getId();
        $headerParentId = (int) $headerParent->getId();
        $footerParentId = (int) $footerParent->getId();

        $menuPage = $this->client->request('GET', '/admin/menu');
        self::assertSelectorExists('[data-menu-sort-group][data-menu-place="1"]');
        self::assertSelectorExists('[data-menu-row][data-menu-depth="1"]');
        $sortContainer = $menuPage->filter('[data-menu-sort]');
        $this->client->request('POST', '/admin/menu/move', [
            '_token' => $sortContainer->attr('data-reorder-token'),
            'item' => (string) $headerItemId,
            'parent' => (string) $footerParentId,
            'place' => MenuItem::PLACE_FOOTER,
        ]);
        self::assertResponseIsSuccessful();
        $menuRepository = self::getContainer()->get(MenuItemRepository::class);
        $movedItem = $menuRepository->find($headerItemId);
        self::assertNotNull($movedItem);
        self::assertSame($footerParentId, $movedItem->getParent()?->getId());
        self::assertSame(MenuItem::PLACE_FOOTER, $movedItem->getPlace());

        $this->client->request('POST', '/admin/menu/move', [
            '_token' => $sortContainer->attr('data-reorder-token'),
            'item' => (string) $footerParentId,
            'parent' => (string) $headerItemId,
            'place' => MenuItem::PLACE_FOOTER,
        ]);
        self::assertResponseStatusCodeSame(422);
        $menuRepository = self::getContainer()->get(MenuItemRepository::class);
        $menuRepository->move(
            $menuRepository->find($headerItemId),
            $menuRepository->find($headerParentId),
            MenuItem::PLACE_HEADER,
        );

        $settingsPage = $this->client->request('GET', '/admin/configuration/system');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.ui-field input[name="system_settings[site_name]"]');
        self::assertSelectorExists('select[name="system_settings[theme]"]');
        self::assertSelectorExists('select[name="system_settings[date_short]"]');
        self::assertSelectorExists('select[name="system_settings[timezone]"]');
        self::assertSelectorCount(2, 'input[type="radio"][name="system_settings[show_login]"]');
        self::assertSelectorCount(2, 'input[type="radio"][name="system_settings[maintenance]"]');
        self::assertSelectorExists('input[name="system_settings[small_image_width]"]');
        self::assertSelectorExists('select[name="system_settings[alert_email_template]"]');
        self::assertSelectorExists('select[name="system_settings[api_auth_module]"]');
        self::assertSelectorCount(2, 'input[type="radio"][name="system_settings[smtp_ssl]"]');
        $this->client->submit($settingsPage->selectButton('Zapisz konfigurację')->form(), [
            'system_settings[site_name]' => 'Shopro test',
            'system_settings[theme]' => 'modernize',
        ]);
        self::assertResponseRedirects('/admin/configuration/system');

        $this->client->request('GET', '/');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.site-nav', 'O nas');
    }

    public function testAdministratorCanLogInWithUsername(): void
    {
        $user = new AdminUser('owner@example.test', 'administrator');
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $user->setPassword($hasher->hashPassword($user, 'very-secure-password'));
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->request('GET', '/admin/login');
        $form = $this->client->getCrawler()->filter('form')->form([
            '_username' => 'administrator',
            '_password' => 'very-secure-password',
        ]);
        $this->client->submit($form);

        self::assertResponseRedirects('/admin');
    }
}

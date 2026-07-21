<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Cms\Domain\Entity\MenuItem;
use App\Audit\Domain\Entity\AuditLog;
use App\Cms\Domain\Entity\MenuItemTranslation;
use App\Cms\Domain\Entity\PageTranslation;
use App\Cms\Domain\Entity\Page;
use App\Cms\Domain\Entity\UrlRedirect;
use App\Cms\Domain\Entity\PageRevision;
use App\Cms\Application\UrlRedirectManager;
use App\Cms\Infrastructure\Persistence\Doctrine\MenuItemRepository;
use App\Cms\Infrastructure\Persistence\Doctrine\PageRepository;
use App\Identity\Domain\Entity\AdminUser;
use App\Identity\Domain\Entity\Membership;
use App\Identity\Domain\Entity\SiteUser;
use App\Identity\Application\PasswordResetManager;
use App\Identity\Application\SitePasswordResetManager;
use App\Identity\Infrastructure\Persistence\Doctrine\AdminUserRepository;
use App\Newsletter\Application\UnsubscribeToken;
use App\Newsletter\Domain\Entity\NewsletterCampaign;
use App\Module\Domain\Entity\InstalledModule;
use App\Language\Domain\Entity\Language;
use App\Settings\Infrastructure\Persistence\Doctrine\SystemSettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
        self::assertResponseHasHeader('Cache-Control');
        self::assertStringContainsString('no-store', (string) $this->client->getResponse()->headers->get('Cache-Control'));

        $this->client->followRedirect();
        $this->client->submitForm('Zaloguj się', [
            '_username' => 'admin@example.test',
            '_password' => 'very-secure-password',
            '_remember_me' => '1',
        ]);
        self::assertResponseRedirects('/admin');
        self::assertNotNull($this->client->getCookieJar()->get('SHOPRO_ADMIN_REMEMBER', '/admin'));

        $this->client->followRedirect();
        self::assertSelectorTextContains('h1', 'Dzień dobry');
        self::assertSelectorCount(3, '.modern-nav-group');
        self::assertSelectorExists('.modern-nav-group summary');
        self::assertSelectorExists('.modern-nav-group a[href="/admin/site-users"]');
        self::assertSelectorExists('.modern-nav-group a[href="/admin/users"]');
        self::assertSelectorExists('.modern-nav-group a[href="/admin/memberships"]');
        $loggedInUser = self::getContainer()->get(AdminUserRepository::class)->find($user->getId());
        self::assertNotNull($loggedInUser?->getLastLoginAt());
        self::assertCount(1, $this->entityManager->getRepository(AuditLog::class)->findBy(['action' => 'login_success']));

        $this->client->request('GET', '/admin/pages/new');
        self::assertSelectorNotExists('.modern-nav a[href="/admin/pages/new"]');
        $this->client->submitForm('Zapisz podstronę', [
            'page[title]' => 'O nas',
            'page[slug]' => '',
            'page[content]' => '',
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

        $this->client->request('GET', '/admin/pages?q=O%20nas&status=published');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.admin-list-filters input[name="q"][value="O nas"]');
        self::assertSelectorExists('.admin-list-filters option[value="published"][selected]');
        self::assertSelectorTextContains('.admin-table tbody', 'O nas');

        $this->client->request('GET', '/admin/pages?q=nieistniejaca');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.admin-table tbody', 'Nie znaleziono podstron spełniających kryteria');

        $this->client->request('GET', '/admin/pages');
        self::assertSelectorExists('[data-page-select][value="'.$page->getId().'"]');
        self::assertSelectorExists('[data-page-select-all]');
        $bulkToken = (string) $this->client->getCrawler()->filter('#page-bulk-form input[name="_token"]')->attr('value');
        $this->client->request('POST', '/admin/pages/bulk', ['_token' => $bulkToken, 'bulk_action' => 'draft', 'pages' => [$page->getId()]]);
        self::assertResponseRedirects('/admin/pages');
        $page = self::getContainer()->get(PageRepository::class)->find($page->getId());
        self::assertFalse($page?->isPublished());

        $this->client->request('GET', '/admin/pages');
        $bulkToken = (string) $this->client->getCrawler()->filter('#page-bulk-form input[name="_token"]')->attr('value');
        $this->client->request('POST', '/admin/pages/bulk', ['_token' => $bulkToken, 'bulk_action' => 'publish', 'pages' => [$page?->getId()]]);
        self::assertResponseRedirects('/admin/pages');
        $page = self::getContainer()->get(PageRepository::class)->find($page?->getId());
        self::assertTrue($page?->isPubliclyAvailable());

        $editPage = $this->client->request('GET', '/admin/pages/'.$page->getId().'/edit');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('textarea[name="page[caption]"]');
        self::assertSelectorExists('input[name="page[homePage]"]');
        self::assertSelectorExists('textarea[name="page[javascript]"]');
        self::assertSelectorExists('[data-component-builder]');
        self::assertSelectorExists('[data-add-component="image"]');
        self::assertSelectorExists('input[name="page[editorMode]"]');
        self::assertSelectorExists('input[name="page[builderData]"]');
        self::assertSelectorExists('input[name="page[builderCss]"]');
        self::assertSelectorExists('.ui-field input[name="page[title]"]');
        self::assertSelectorExists('.ui-choice input[name="page[published]"]');
        self::assertSelectorExists('input[name="page[publishAt]"][type="datetime-local"]');
        self::assertSelectorExists('input[name="page[unpublishAt]"][type="datetime-local"]');
        self::assertSelectorExists('button[data-preview-submit][formtarget="_blank"]');

        $previewForm = $editPage->filter('button[data-preview-submit]')->form();
        $previewForm['page[title]'] = 'Niezapisany podgląd';
        $previewForm['page[builderData]'] = '[{"id":"preview","type":"layout_section","data":{"container":"grid","widths":[100],"columns":[[{"id":"preview-text","type":"rich_text","data":{"content":"<p>Treść tylko w podglądzie.</p><script>alert(1)</script>"}}]]}}]';
        $this->client->submit($previewForm);
        self::assertResponseRedirects();
        $previewUrl = (string) $this->client->getResponse()->headers->get('Location');
        self::assertMatchesRegularExpression('#^/admin/pages/preview/[a-f0-9]{32}$#', $previewUrl);
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('X-Robots-Tag', 'noindex, nofollow');
        self::assertSelectorTextContains('.page-preview-banner', 'Tryb podglądu');
        self::assertSelectorTextContains('.public-article__body', 'Treść tylko w podglądzie.');
        self::assertStringNotContainsString('<script>alert(1)</script>', (string) $this->client->getResponse()->getContent());
        self::assertSame('O nas', self::getContainer()->get(PageRepository::class)->find($page->getId())?->getTitle());
        $this->client->request('GET', $previewUrl);
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.page-preview-banner', 'Tryb podglądu');

        $this->client->request('GET', '/admin/pages/'.$page->getId().'/edit');

        $this->client->submitForm('Zapisz i kontynuuj edycję', [
            'page[title]' => 'O nas — edycja',
        ]);
        self::assertResponseRedirects('/admin/pages/'.$page->getId().'/edit');

        $page = self::getContainer()->get(PageRepository::class)->find($page->getId());
        self::assertNotNull($page);
        $uploadsDirectory = dirname(__DIR__, 2).'/public/uploads';
        if (!is_dir($uploadsDirectory)) mkdir($uploadsDirectory, 0775, true);
        $testImage = $uploadsDirectory.'/test image.svg';
        $testVariant = $uploadsDirectory.'/test image.320.webp';
        file_put_contents($testImage, '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="18"><rect width="32" height="18" fill="#5d87ff"/></svg>');
        file_put_contents($testVariant, 'responsive-test-variant');
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
        ], [
            'id' => 'image-section-test',
            'type' => 'layout_section',
            'data' => [
                'container' => 'grid',
                'widths' => [70, 30],
                'columns' => [[], [[
                    'id' => 'image-test',
                    'type' => 'image',
                    'data' => [
                        'src' => '/uploads/test%20image.svg',
                        'alt' => 'Alternatywny opis',
                        'caption' => 'Podpis obrazu testowego',
                        'ratio' => '16/9',
                        'fit' => 'cover',
                        'loading' => 'lazy',
                    ],
                ]]],
            ],
        ]], JSON_THROW_ON_ERROR));
        self::getContainer()->get(PageRepository::class)->save($page);
        $this->client->request('GET', '/o-nas');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.site-hero h1', 'działa poprawnie');
        self::assertSelectorTextContains('.builder-image figcaption', 'Podpis obrazu testowego');
        self::assertSelectorExists('.builder-image img[src="/uploads/test%20image.svg"][alt="Alternatywny opis"]');
        self::assertSelectorExists('.builder-image source[srcset="/uploads/test%20image.320.webp 320w"][sizes="(max-width: 760px) calc(100vw - 30px), (max-width: 1220px) 30vw, 354px"]');
        unlink($testImage);
        unlink($testVariant);

        $page = self::getContainer()->get(PageRepository::class)->find($page->getId());
        self::assertNotNull($page);
        $page->setHomePage(true);
        self::getContainer()->get(PageRepository::class)->save($page);
        $this->client->request('GET', '/');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('link[rel="alternate"][hreflang="x-default"]');
        self::assertSelectorExists('link[rel="canonical"][href="http://localhost/"]');
        self::assertSelectorExists('meta[property="og:title"][content="O nas — edycja"]');
        self::assertSelectorExists('meta[property="og:url"][content="http://localhost/"]');
        self::assertSelectorExists('meta[name="twitter:card"][content="summary"]');
        self::assertSelectorExists('script[type="application/ld+json"]');
        self::assertStringContainsString('"@type":"WebPage"', (string) $this->client->getResponse()->getContent());

        $this->client->request('GET', '/sitemap.xml');
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/xml; charset=UTF-8');
        self::assertStringContainsString('<loc>http://localhost/</loc>', (string) $this->client->getResponse()->getContent());
        self::assertStringNotContainsString('/admin', (string) $this->client->getResponse()->getContent());

        $this->client->request('GET', '/robots.txt');
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'text/plain; charset=UTF-8');
        self::assertStringContainsString("Disallow: /admin", (string) $this->client->getResponse()->getContent());
        self::assertStringContainsString('Sitemap: http://localhost/sitemap.xml', (string) $this->client->getResponse()->getContent());

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

        $page = self::getContainer()->get(PageRepository::class)->find($page->getId());
        self::assertNotNull($page);
        $polish = new Language();
        $polish->setName('Polski');
        $polish->setCode('pl');
        $polish->setDefaultLanguage(true);
        $english = new Language();
        $english->setName('English');
        $english->setCode('en');
        $translation = new PageTranslation($page, $english);
        $translation->setTitle('About us');
        $translation->setSlug('about-us');
        $translation->setBuilderData('[]');
        $translation->setPublished(false);
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $managedHeaderItem = $entityManager->find(MenuItem::class, $headerItemId);
        self::assertNotNull($managedHeaderItem);
        $menuTranslation = new MenuItemTranslation($managedHeaderItem, $english);
        $menuTranslation->setName('About us');
        $menuTranslation->setCaption('Learn more about our company');
        $entityManager->persist($polish);
        $entityManager->persist($english);
        $entityManager->persist($translation);
        $entityManager->persist($menuTranslation);
        $entityManager->flush();

        $this->client->request('GET', '/admin/pages/'.$page->getId().'/translations/'.$english->getId());
        self::assertResponseIsSuccessful();
        $this->client->submitForm('Zastosuj szablon języka głównego');
        self::assertResponseRedirects('/admin/pages/'.$page->getId().'/translations/'.$english->getId());
        $copiedTranslation = self::getContainer()->get(EntityManagerInterface::class)->getRepository(PageTranslation::class)->find($translation->getId());
        self::assertNotNull($copiedTranslation);
        self::assertSame($page->getBuilderData(), $copiedTranslation->getBuilderData());

        $this->client->request('GET', '/admin/menu/'.$headerItemId.'/translations/'.$english->getId());
        self::assertResponseIsSuccessful();
        $this->client->submitForm('Zapisz tłumaczenie', [
            'menu_item_translation[name]' => 'About our company',
        ]);
        self::assertResponseRedirects('/admin/menu/'.$headerItemId.'/translations');

        $settingsRepository = self::getContainer()->get(SystemSettingsRepository::class);
        $systemSettings = $settingsRepository->get();
        $languageConfiguration = $systemSettings->getConfiguration();
        $languageConfiguration['show_language'] = true;
        $systemSettings->setConfiguration($languageConfiguration);
        $settingsRepository->save($systemSettings);
        $publishedTranslation = self::getContainer()->get(EntityManagerInterface::class)->getRepository(PageTranslation::class)->find($translation->getId());
        self::assertNotNull($publishedTranslation);
        $publishedTranslation->setPublished(true);
        self::getContainer()->get(EntityManagerInterface::class)->flush();

        $this->client->request('GET', '/language/en?page='.$page->getId());
        self::assertResponseRedirects('/en/about-us');
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.site-language-picker a.is-active[href^="/language/en"]');
        self::assertSelectorExists('.site-nav a[href="/en/about-us"]');
        self::assertSelectorTextContains('.site-nav', 'About our company');
        self::assertSelectorTextContains('.preview-title strong', 'Good morning');
        self::assertSelectorTextContains('.preview-stats', 'Published');
        self::assertSelectorTextContains('.preview-chart', 'Last 7 days');

        $this->client->request('GET', '/en/page-that-does-not-exist');
        self::assertResponseStatusCodeSame(404);
        self::assertSelectorTextContains('h1', 'This page is not here');
        self::assertStringNotContainsString('The page translation does not exist or is not published.', (string) $this->client->getResponse()->getContent());

        $this->client->request('GET', '/language/zz');
        self::assertResponseStatusCodeSame(404);
        self::assertSelectorTextContains('h1', 'This page is not here');
        self::assertStringNotContainsString('The language does not exist.', (string) $this->client->getResponse()->getContent());

        $this->client->request('GET', '/admin');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('html[lang="en"]');
        self::assertSelectorTextContains('.modern-page-heading h1', 'Good morning');
        self::assertSelectorTextContains('.modern-nav', 'CONTENT MANAGEMENT');
        self::assertSelectorCount(8, '.modern-module-card');
        self::assertSelectorExists('.modern-module-card[href="/admin/site-users"]');
        self::assertSelectorTextContains('.modern-dashboard-modules', 'Manage the entire system');
        self::assertSelectorExists('.admin-language-picker a[href^="/admin/language/en"]');

        $this->client->request('GET', '/admin/language/en?return=%2Fadmin%2Fpages');
        self::assertResponseRedirects('/admin/pages');

        $this->client->request('GET', '/admin/pages');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.modern-page-heading h1', 'Pages');
        self::assertSelectorTextContains('.modern-table-card', 'Page list');

        $this->client->request('GET', '/admin/pages/'.$page->getId().'/edit');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.modern-page-heading h1', 'Edit page');
        self::assertSelectorTextContains('.component-builder__library', 'Available components');
        self::assertSelectorTextContains('label[for="page_access"]', 'Access');
        self::assertSelectorTextContains('select[name="page[access]"] option[value="Registered"]', 'Signed-in users');

        $this->client->request('GET', '/admin/pages/'.$page->getId().'/translations');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.modern-page-heading', 'Language versions are linked');

        $this->client->request('GET', '/admin/pages/'.$page->getId().'/translations/'.$english->getId());
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.modern-page-heading h1', 'Translation:');
        self::assertSelectorTextContains('label[for="page_translation_published"]', 'Published translation');

        $englishMenuPage = $this->client->request('GET', '/admin/menu');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.modern-page-heading', 'Drag items by the handle');
        self::assertSelectorTextContains('.menu-sort-table thead', 'Location');
        self::assertSelectorTextContains('.menu-group-heading', 'Header menu');
        self::assertSelectorExists('[data-menu-sort][data-saving="Saving…"]');
        self::assertSelectorExists('[data-menu-sort][data-order-saved="The menu order has been saved."]');

        $this->client->request('POST', '/admin/menu/reorder', ['_token' => 'invalid', 'items' => []]);
        self::assertResponseStatusCodeSame(403);
        self::assertStringContainsString('Invalid security token.', (string) $this->client->getResponse()->getContent());

        $this->client->request('POST', '/admin/menu/reorder', [
            '_token' => $englishMenuPage->filter('[data-menu-sort]')->attr('data-reorder-token'),
            'items' => [],
        ]);
        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('The menu item list is invalid.', (string) $this->client->getResponse()->getContent());

        $this->client->request('GET', '/admin/menu/'.$headerItemId.'/edit');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.modern-page-heading h1', 'Edit menu item');
        self::assertSelectorTextContains('select[name="menu_item[contentType]"] option[value="web"]', 'External link');

        $this->client->request('GET', '/admin/users');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.modern-page-heading h1', 'Users');
        self::assertSelectorTextContains('.admin-table thead', 'Email address');
        self::assertSelectorTextContains('.admin-table thead', 'Last login');

        $this->client->request('GET', '/admin/users/'.$user->getId().'/edit');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.modern-page-heading h1', 'Edit user');
        self::assertSelectorTextContains('label[for="admin_user_assignedRoles_0"]', 'Administrator');
        self::assertSelectorTextContains('label[for="admin_user_apiScopes_0"]', 'Read data');
        $this->client->submitForm('Save user', ['admin_user[active]' => false]);
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.ui-field__errors', 'You cannot deactivate the currently signed-in account.');
        self::assertTrue(self::getContainer()->get(AdminUserRepository::class)->find($user->getId())?->isActive());

        $validator = self::getContainer()->get(ValidatorInterface::class);
        $duplicateUser = new AdminUser($user->getEmail(), $user->getUsername());
        $userViolations = $validator->validate($duplicateUser);
        self::assertStringContainsString('An account with this email address already exists.', (string) $userViolations);
        self::assertStringContainsString('An account with this username already exists.', (string) $userViolations);

        $invalidPage = new \App\Cms\Domain\Entity\Page();
        $invalidPage->setTitle('Invalid URL');
        $invalidPage->setSlug('Invalid URL');
        self::assertStringContainsString('The slug may contain lowercase letters, numbers and hyphens.', (string) $validator->validate($invalidPage));

        $invalidMenuItem = new MenuItem();
        $invalidMenuItem->setName('Missing page');
        $invalidMenuItem->setContentType(MenuItem::TYPE_PAGE);
        self::assertStringContainsString('Select a page for this menu item.', (string) $validator->validate($invalidMenuItem));

        $this->client->request('GET', '/admin/configuration/languages');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.modern-page-heading h1', 'Language management');
        self::assertSelectorTextContains('.admin-table thead', 'Subdomain');

        $this->client->request('GET', '/admin/configuration/languages/'.$english->getId().'/edit');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.modern-page-heading h1', 'Edit language');
        self::assertSelectorTextContains('label[for="language_decimalSeparator"]', 'Decimal separator');

        $this->client->request('GET', '/admin/configuration/languages/'.$english->getId().'/phrases');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.modern-page-heading h1', 'Translations: English');
        self::assertSelectorExists('input[placeholder="Search key or content"]');

        $this->client->request('GET', '/admin/newsletter');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.modern-page-heading', 'delivery queue');
        self::assertSelectorTextContains('.modern-table-card + .modern-table-card', 'Delivery history');

        $this->client->request('GET', '/admin/newsletter/new');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.modern-page-heading h1', 'New campaign');
        self::assertSelectorTextContains('label[for="newsletter_campaign_customEmails"]', 'Additional email addresses');
        self::assertSelectorTextContains('label[for="newsletter_campaign_recipientFile"]', 'Import recipients from CSV');
        self::assertSelectorExists('select[data-newsletter-template]');
        self::assertSelectorExists('button[data-newsletter-template-load][disabled]');

        $orphanedModule = new InstalledModule('legacy-extension', '2.0.0');
        self::getContainer()->get(EntityManagerInterface::class)->persist($orphanedModule);
        self::getContainer()->get(EntityManagerInterface::class)->flush();
        $moduleSync = new CommandTester((new Application(self::$kernel))->find('app:modules:sync'));
        self::assertSame(0, $moduleSync->execute([]));

        $this->client->request('GET', '/admin/modules');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.modern-page-heading h1', 'System modules');
        self::assertSelectorCount(6, 'section.modern-table-card:first-of-type .admin-table tbody tr');
        self::assertSelectorTextContains('.admin-table', 'CMS and Page Builder');
        self::assertSelectorCount(6, '.status--published');
        self::assertSelectorTextContains('.modern-table-card + .modern-table-card', 'Orphaned records');
        self::assertSelectorTextContains('.modern-table-card + .modern-table-card', 'legacy-extension');

        $this->client->request('GET', '/admin/memberships/new');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.modern-page-heading h1', 'New membership');
        $this->client->submitForm('Save', [
            'membership[title]' => 'Business customers',
            'membership[description]' => 'Access to materials for business customers.',
            'membership[active]' => true,
        ]);
        self::assertResponseRedirects('/admin/memberships');
        $this->client->followRedirect();
        self::assertSelectorTextContains('.admin-table', 'Business customers');
        self::assertSelectorTextContains('.admin-table', 'Active');

        $currentEntityManager = self::getContainer()->get(EntityManagerInterface::class);
        $membership = $currentEntityManager->getRepository(Membership::class)->findOneBy(['title' => 'Business customers']);
        self::assertNotNull($membership);
        $membershipPage = self::getContainer()->get(PageRepository::class)->find($page->getId());
        self::assertNotNull($membershipPage);
        $membershipPage->setAccess('Membership');
        self::assertStringContainsString('Select at least one membership', (string) $validator->validate($membershipPage));
        $membershipPage->addMembership($membership);
        self::assertStringNotContainsString('Select at least one membership', (string) $validator->validate($membershipPage));
        $currentEntityManager->flush();
        $currentEntityManager->clear();
        $membershipPage = self::getContainer()->get(PageRepository::class)->find($page->getId());
        self::assertNotNull($membershipPage);
        self::assertSame('Business customers', $membershipPage->getMemberships()->first()?->getTitle());

        $this->client->request('GET', '/admin/pages/'.$page->getId().'/edit');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('select[name="page[memberships][]"][multiple]');
        self::assertSelectorExists('select[name="page[memberships][]"] option[value="'.$membership->getId().'"]');

        $this->client->request('GET', '/admin/site-users/new');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.modern-page-heading h1', 'New website user');
        self::assertSelectorExists('select[name="site_user[memberships][]"][multiple]');
        $this->client->submitForm('Save user', [
            'site_user[username]' => 'customer',
            'site_user[email]' => 'customer@example.test',
            'site_user[plainPassword]' => 'very-secure-password',
            'site_user[active]' => true,
            'site_user[memberships]' => [],
        ]);
        self::assertResponseRedirects('/admin/site-users');
        $this->client->followRedirect();
        self::assertSelectorTextContains('.admin-table', 'customer@example.test');

        $this->client->request('GET', '/o-nas');
        self::assertResponseRedirects('/login');
        $this->client->followRedirect();
        self::assertSelectorTextContains('h1', 'Sign in to the website');
        $this->client->submitForm('Sign in', ['_username' => 'customer', '_password' => 'very-secure-password', '_remember_me' => '1']);
        self::assertResponseRedirects('http://localhost/o-nas');
        self::assertNotNull($this->client->getCookieJar()->get('SHOPRO_SITE_REMEMBER'));
        $this->client->followRedirect();
        self::assertResponseStatusCodeSame(403);

        $siteUserManager = self::getContainer()->get(EntityManagerInterface::class);
        $loggedSiteUser = $siteUserManager->getRepository(SiteUser::class)->findOneBy(['username' => 'customer']);
        $allowedMembership = $siteUserManager->getRepository(Membership::class)->findOneBy(['title' => 'Business customers']);
        self::assertNotNull($loggedSiteUser);
        self::assertNotNull($allowedMembership);
        $loggedSiteUser->addMembership($allowedMembership);
        $siteUserManager->flush();
        $this->client->request('GET', '/o-nas');
        self::assertResponseRedirects('/en/about-us');
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Strona z komponentów', (string) $this->client->getResponse()->getContent());

        $this->client->request('GET', '/account');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.site-account-details', 'customer@example.test');
        self::assertSelectorTextContains('.site-membership-list', 'Business customers');
        self::assertSelectorExists('a[href="/account/profile"]');
        self::assertSelectorExists('a[href="/account/password"]');

        $this->client->request('GET', '/account/profile');
        self::assertResponseIsSuccessful();
        $profileForm = $this->client->getCrawler()->filter('form[name="site_profile"]')->form();
        $this->client->submit($profileForm, [
            'site_profile[username]' => 'customer',
            'site_profile[email]' => 'customer-updated@example.test',
        ]);
        self::assertResponseRedirects('/account/profile');
        $updatedSiteUser = self::getContainer()->get(EntityManagerInterface::class)->getRepository(SiteUser::class)->findOneBy(['username' => 'customer']);
        self::assertSame('customer-updated@example.test', $updatedSiteUser?->getEmail());

        $this->client->request('GET', '/account/password');
        self::assertResponseIsSuccessful();
        $passwordForm = $this->client->getCrawler()->filter('form[name="site_password_change"]')->form();
        $this->client->submit($passwordForm, [
            'site_password_change[currentPassword]' => 'very-secure-password',
            'site_password_change[newPassword][first]' => 'customer-changed-password',
            'site_password_change[newPassword][second]' => 'customer-changed-password',
        ]);
        self::assertResponseRedirects('/account/password');
        $passwordChangedUser = self::getContainer()->get(EntityManagerInterface::class)->getRepository(SiteUser::class)->findOneBy(['username' => 'customer']);
        self::assertNotNull($passwordChangedUser);
        self::assertTrue($hasher->isPasswordValid($passwordChangedUser, 'customer-changed-password'));

        $this->client->request('GET', '/register');
        self::assertResponseStatusCodeSame(404);

        $registrationSettings = self::getContainer()->get(SystemSettingsRepository::class)->get();
        $registrationConfiguration = $registrationSettings->getConfiguration();
        $registrationConfiguration['registration_allowed'] = true;
        $registrationConfiguration['registration_verify'] = false;
        $registrationConfiguration['registration_auto_verify'] = true;
        $registrationSettings->setConfiguration($registrationConfiguration);
        self::getContainer()->get(SystemSettingsRepository::class)->save($registrationSettings);

        $this->client->request('GET', '/register');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form[name="site_registration"]');
        $registrationForm = $this->client->getCrawler()->filter('form[name="site_registration"]')->form();
        $this->client->submit($registrationForm, [
            'site_registration[username]' => 'newcustomer',
            'site_registration[email]' => 'newcustomer@example.test',
            'site_registration[plainPassword][first]' => 'another-secure-password',
            'site_registration[plainPassword][second]' => 'another-secure-password',
            'site_registration[termsAccepted]' => true,
        ]);
        self::assertResponseRedirects('/login');
        $registeredUser = self::getContainer()->get(EntityManagerInterface::class)->getRepository(SiteUser::class)->findOneBy(['username' => 'newcustomer']);
        self::assertNotNull($registeredUser);
        self::assertTrue($registeredUser->isActive());
        self::assertTrue($hasher->isPasswordValid($registeredUser, 'another-secure-password'));

        $siteResetToken = self::getContainer()->get(SitePasswordResetManager::class)->create($registeredUser);
        $this->client->request('GET', '/password/reset/'.$siteResetToken);
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('input[name="password_confirmation"]');
        $siteResetForm = $this->client->getCrawler()->filter('form.modern-login-form')->form();
        $this->client->submit($siteResetForm, [
            'password' => 'changed-secure-password',
            'password_confirmation' => 'changed-secure-password',
        ]);
        self::assertResponseRedirects('/login');
        $resetUser = self::getContainer()->get(EntityManagerInterface::class)->getRepository(SiteUser::class)->findOneBy(['username' => 'newcustomer']);
        self::assertNotNull($resetUser);
        self::assertTrue($hasher->isPasswordValid($resetUser, 'changed-secure-password'));
        $this->client->request('GET', '/password/reset/'.$siteResetToken);
        self::assertResponseStatusCodeSame(410);

        $pendingUser = new SiteUser('pending@example.test', 'pending');
        $activationToken = $pendingUser->issueActivationToken();
        self::assertFalse($pendingUser->isActive());
        self::assertFalse($pendingUser->activateWithToken(str_repeat('0', 64)));
        self::assertTrue($pendingUser->activateWithToken($activationToken));
        self::assertTrue($pendingUser->isActive());
        self::assertFalse($pendingUser->activateWithToken($activationToken));

        $resendUser = new SiteUser('waiting@example.test', 'waiting');
        $resendUser->setPassword($hasher->hashPassword($resendUser, 'waiting-secure-password'));
        $originalActivationToken = $resendUser->issueActivationToken();
        $resendManager = self::getContainer()->get(EntityManagerInterface::class);
        $resendManager->persist($resendUser);
        $resendManager->flush();
        $this->client->request('GET', '/activation/resend');
        self::assertResponseIsSuccessful();
        $resendForm = $this->client->getCrawler()->filter('form.modern-login-form')->form();
        $this->client->submit($resendForm, ['identifier' => 'waiting@example.test']);
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.flash--success');
        $this->client->request('GET', '/activate/'.$originalActivationToken);
        self::assertResponseRedirects('/login');
        $this->client->request('GET', '/activate/'.$originalActivationToken);
        self::assertResponseStatusCodeSame(410);
        self::assertSelectorExists('a[href="/activation/resend"]');

        $campaign = new NewsletterCampaign();
        $campaign->setSubject('Testowa kampania');
        $campaign->setContent('<h1>Treść kampanii</h1><p>Wiadomość testowa.</p>');
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($campaign);
        $entityManager->flush();

        $campaignPage = $this->client->request('GET', '/admin/newsletter/'.$campaign->getId());
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('a[href="/admin/newsletter/'.$campaign->getId().'/preview"][target="_blank"]');
        self::assertSelectorExists('form[action="/admin/newsletter/'.$campaign->getId().'/test"] input[type="email"]');

        $this->client->request('GET', '/admin/newsletter/'.$campaign->getId().'/preview');
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('X-Robots-Tag', 'noindex, nofollow');
        self::assertStringContainsString('Treść kampanii', (string) $this->client->getResponse()->getContent());

        $testForm = $campaignPage->filter('form[action="/admin/newsletter/'.$campaign->getId().'/test"]')->form();
        $this->client->submit($testForm, ['recipient' => 'niepoprawny-adres']);
        self::assertResponseRedirects('/admin/newsletter/'.$campaign->getId());
        $this->client->followRedirect();
        self::assertSelectorExists('.flash--error');

        $this->client->request('GET', '/admin/configuration/email-templates');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.modern-page-heading h1', 'Email message templates');
        self::assertSelectorTextContains('.admin-table thead', 'Subject');

        $this->client->request('GET', '/admin/configuration/email-templates/new');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.modern-page-heading h1', 'New template');
        self::assertSelectorTextContains('label[for="email_template_code"]', 'Event / technical code');
        self::assertSelectorTextContains('select[name="email_template[code]"] option[value="user_activate_account"]', 'User — account activation');

        $this->client->request('GET', '/admin/configuration/files');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.modern-page-heading h1', 'File manager');
        self::assertSelectorTextContains('.file-manager-actions', 'New directory');

        $this->client->request('GET', '/admin/configuration/files?picker=1');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.modern-page-heading h1', 'Select an image');

        $this->client->request('GET', '/admin/configuration/files?path=../');
        self::assertResponseStatusCodeSame(404);

        $this->client->request('GET', '/admin/logs?type=admin&limit=10');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.modern-page-heading h1', 'System logs');
        self::assertSelectorTextContains('.audit-filters', 'Date from');
        self::assertSelectorExists('.admin-table tbody tr');

        $this->client->request('GET', '/admin/configuration/system');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.modern-page-heading h1', 'System configuration');
        self::assertSelectorTextContains('.settings-card', 'Website details and themes');
        self::assertSelectorTextContains('label[for="system_settings_site_name"]', 'Website name');
        self::assertSelectorTextContains('label[for="system_settings_registration_allowed_0"]', 'Yes');
        self::assertSelectorTextContains('select[name="system_settings[theme_variant]"] option[value="orange"]', 'Orange');

        $this->client->request('GET', '/o-nas');
        self::assertResponseRedirects('/en/about-us');

        $this->client->request('GET', '/language/pl?page='.$page->getId());
        self::assertResponseRedirects('/');

        $settingsPage = $this->client->request('GET', '/admin/configuration/system');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.ui-field input[name="system_settings[site_name]"]');
        self::assertSelectorExists('select[name="system_settings[theme]"]');
        self::assertSelectorExists('select[name="system_settings[date_short]"]');
        self::assertSelectorExists('select[name="system_settings[timezone]"]');
        self::assertSelectorCount(2, 'input[type="radio"][name="system_settings[show_login]"]');
        self::assertSelectorCount(2, 'input[type="radio"][name="system_settings[maintenance]"]');
        self::assertSelectorCount(2, 'select[name="system_settings[theme]"] option');
        self::assertSelectorCount(2, 'select[name="system_settings[admin_theme]"] option');
        self::assertSelectorCount(4, 'select[name="system_settings[theme_variant]"] option');
        self::assertSelectorExists('input[name="system_settings[image_widths]"]');
        self::assertSelectorExists('input[name="system_settings[image_formats][]"]');
        self::assertSelectorExists('select[name="system_settings[alert_email_template]"]');
        self::assertSelectorNotExists('input[name="system_settings[tenor_api_key]"]');
        self::assertSelectorNotExists('select[name="system_settings[api_auth_module]"]');
        self::assertSelectorExists('select[name="system_settings[smtp_encryption]"]');
        self::assertSelectorExists('input[name="system_settings[social_image_file]"]');
        self::assertSelectorExists('input[name="system_settings[remove_social_image]"]');
        $this->client->submit($settingsPage->selectButton('Zapisz konfigurację')->form(), [
            'system_settings[site_name]' => 'Shopro test',
            'system_settings[theme]' => 'classic',
            'system_settings[theme_variant]' => 'orange',
            'system_settings[admin_theme]' => 'compact',
        ]);
        self::assertResponseRedirects('/admin/configuration/system');

        $settingsRepository = self::getContainer()->get(SystemSettingsRepository::class);
        $systemSettings = $settingsRepository->get();
        $configuration = $systemSettings->getConfiguration();
        $configuration['social_image'] = '/uploads/branding/social-test.webp';
        $systemSettings->setConfiguration($configuration);
        $settingsRepository->save($systemSettings);

        $this->client->request('GET', '/');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('body.front-theme--classic.theme-variant--orange');
        self::assertSelectorTextContains('.site-nav', 'O nas');
        self::assertSelectorExists('meta[property="og:image"][content="http://localhost/uploads/branding/social-test.webp"]');
        self::assertSelectorExists('meta[name="twitter:card"][content="summary_large_image"]');

        $systemSettings = $settingsRepository->get();
        $configuration = $systemSettings->getConfiguration();
        $configuration['maintenance'] = true;
        $configuration['maintenance_message'] = 'Testowa przerwa techniczna';
        $systemSettings->setConfiguration($configuration);
        $settingsRepository->save($systemSettings);
        $this->client->request('GET', '/');
        self::assertResponseStatusCodeSame(503);
        self::assertSelectorTextContains('main', 'Testowa przerwa techniczna');
        $settingsRepository = self::getContainer()->get(SystemSettingsRepository::class);
        $systemSettings = $settingsRepository->get();
        $configuration = $systemSettings->getConfiguration();
        $configuration['maintenance'] = false;
        $systemSettings->setConfiguration($configuration);
        $settingsRepository->save($systemSettings);

        $this->client->request('GET', '/admin/newsletter');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Newsletter');

        $this->client->request('GET', '/admin/configuration/email-templates');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Szablony wiadomości e-mail');

        $userRepository = self::getContainer()->get(AdminUserRepository::class);
        $apiUser = $userRepository->findOneBy(['email' => 'admin@example.test']);
        self::assertNotNull($apiUser);
        $apiUser->setApiScopes(['read']);
        $apiUser->setNewsletter(true);
        $apiToken = $apiUser->rotateApiToken();
        $userRepository->save($apiUser);
        $this->client->request('GET', '/api/v1/me', server: ['HTTP_AUTHORIZATION' => 'Bearer '.$apiToken]);
        self::assertResponseIsSuccessful();
        $apiResponse = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame('admin', $apiResponse['username']);
        self::assertSame(['read'], $apiResponse['scopes']);
        $userRepository = self::getContainer()->get(AdminUserRepository::class);
        $apiUser = $userRepository->findOneBy(['email' => 'admin@example.test']);
        self::assertNotNull($apiUser);
        $apiUser->setApiScopes([]);
        $userRepository->save($apiUser);
        $this->client->request('GET', '/api/v1/me', server: ['HTTP_AUTHORIZATION' => 'Bearer '.$apiToken]);
        self::assertResponseStatusCodeSame(403);
        self::assertJsonStringEqualsJsonString('{"error":"insufficient_scope","required_scope":"read"}', (string) $this->client->getResponse()->getContent());

        $unsubscribeToken = self::getContainer()->get(UnsubscribeToken::class)->create('admin@example.test');
        $this->client->request('POST', '/newsletter/unsubscribe', ['token' => $unsubscribeToken]);
        self::assertResponseIsSuccessful();
        $apiUser = self::getContainer()->get(AdminUserRepository::class)->findOneBy(['email' => 'admin@example.test']);
        self::assertFalse($apiUser->isNewsletter());
    }

    public function testScheduledPublicationWindowControlsPublicAvailability(): void
    {
        $page = new Page();
        $page->setTitle('Zaplanowana strona');
        $page->setSlug('zaplanowana-strona');
        $page->setPublished(true);
        $page->setPublishAt(new \DateTimeImmutable('+1 day'));
        $this->entityManager->persist($page);
        $this->entityManager->flush();

        self::assertSame('scheduled', $page->getPublicationStatus());
        self::assertFalse($page->isPubliclyAvailable());
        $this->client->request('GET', '/zaplanowana-strona');
        self::assertResponseStatusCodeSame(404);

        $page = self::getContainer()->get(PageRepository::class)->find($page->getId());
        self::assertNotNull($page);
        $page->setPublishAt(new \DateTimeImmutable('-1 hour'));
        self::getContainer()->get(EntityManagerInterface::class)->flush();
        self::assertSame('published', $page->getPublicationStatus());
        $this->client->request('GET', '/zaplanowana-strona');
        self::assertResponseIsSuccessful();

        $page = self::getContainer()->get(PageRepository::class)->find($page->getId());
        self::assertNotNull($page);
        $page->setUnpublishAt(new \DateTimeImmutable('-1 minute'));
        self::getContainer()->get(EntityManagerInterface::class)->flush();
        self::assertSame('expired', $page->getPublicationStatus());
        $this->client->request('GET', '/zaplanowana-strona');
        self::assertResponseStatusCodeSame(404);
    }

    public function testBulkTrashSkipsSystemPagesAndPagesUsedByMenu(): void
    {
        $admin = new AdminUser('bulk-trash@example.test', 'bulk-trash');
        $regular = new Page(); $regular->setTitle('Zwykła strona'); $regular->setSlug('zwykla-strona');
        $linked = new Page(); $linked->setTitle('Strona w menu'); $linked->setSlug('strona-w-menu');
        $system = new Page(); $system->setTitle('Strona systemowa'); $system->setSlug('strona-systemowa'); $system->setHomePage(true);
        $menuItem = new MenuItem(); $menuItem->setName('Chroniony link'); $menuItem->setPage($linked);
        foreach ([$admin, $regular, $linked, $system, $menuItem] as $entity) $this->entityManager->persist($entity);
        $this->entityManager->flush();
        self::assertSame(1, self::getContainer()->get(MenuItemRepository::class)->countForPage($linked));
        self::assertSame(1, self::getContainer()->get(MenuItemRepository::class)->usageByPageIds([(int) $linked->getId()])[$linked->getId()] ?? 0);
        $this->client->loginUser($admin, 'admin');

        $this->client->request('GET', '/admin/pages');
        self::assertSelectorExists('option[value="trash"]');
        $token = (string) $this->client->getCrawler()->filter('#page-bulk-form input[name="_token"]')->attr('value');
        $this->client->request('POST', '/admin/pages/bulk', [
            '_token' => $token, 'bulk_action' => 'trash',
            'pages' => [$regular->getId(), $linked->getId(), $system->getId()],
        ]);
        self::assertResponseRedirects('/admin/pages');
        $this->client->followRedirect();
        self::assertSelectorTextContains('.flash--success', '1 podstron');
        self::assertSelectorTextContains('.flash--error', '2 chronionych');

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();
        self::assertTrue($entityManager->find(Page::class, $regular->getId())?->isDeleted());
        self::assertFalse($entityManager->find(Page::class, $linked->getId())?->isDeleted());
        self::assertFalse($entityManager->find(Page::class, $system->getId())?->isDeleted());
    }

    public function testDeletedPageCanBeRestoredFromTrashAndPermanentlyRemoved(): void
    {
        $admin = new AdminUser('trash-admin@example.test', 'trash-admin');
        $page = new Page();
        $page->setTitle('Strona do kosza');
        $page->setSlug('strona-do-kosza');
        $page->setPublished(true);
        $this->entityManager->persist($admin);
        $this->entityManager->persist($page);
        $this->entityManager->flush();
        $pageId = $page->getId();
        $this->client->loginUser($admin, 'admin');

        $menuItem = new MenuItem();
        $menuItem->setName('Link do usuwanej strony');
        $menuItem->setPage($page);
        $this->entityManager->persist($menuItem);
        $this->entityManager->flush();

        $this->client->request('GET', '/admin/pages');
        self::assertSelectorTextContains('.admin-table tbody', 'menu: 1');
        $deleteToken = (string) $this->client->getCrawler()->filter('form[action="/admin/pages/'.$pageId.'/delete"] input[name="_token"]')->attr('value');
        $this->client->request('POST', '/admin/pages/'.$pageId.'/delete', ['_token' => $deleteToken]);
        self::assertResponseRedirects('/admin/pages');
        self::assertFalse(self::getContainer()->get(PageRepository::class)->find($pageId)?->isDeleted());
        $this->client->followRedirect();
        self::assertSelectorTextContains('.flash--error', 'pozycji menu');
        $storedMenuItem = self::getContainer()->get(MenuItemRepository::class)->find($menuItem->getId());
        self::assertNotNull($storedMenuItem);
        self::getContainer()->get(MenuItemRepository::class)->remove($storedMenuItem);

        $this->client->request('GET', '/admin/pages');
        self::assertResponseIsSuccessful();
        $deleteToken = (string) $this->client->getCrawler()->filter('form[action="/admin/pages/'.$pageId.'/delete"] input[name="_token"]')->attr('value');
        $this->client->request('POST', '/admin/pages/'.$pageId.'/delete', ['_token' => $deleteToken]);
        self::assertResponseRedirects('/admin/pages');
        $this->client->request('GET', '/strona-do-kosza');
        self::assertResponseStatusCodeSame(404);
        $this->client->request('GET', '/admin/menu/new');
        self::assertSelectorNotExists('select[name="menu_item[page]"] option[value="'.$pageId.'"]');

        $this->client->request('GET', '/admin/pages/trash');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.admin-table tbody', 'Strona do kosza');
        $restoreToken = (string) $this->client->getCrawler()->filter('form[action="/admin/pages/'.$pageId.'/restore"] input[name="_token"]')->attr('value');
        $this->client->request('POST', '/admin/pages/'.$pageId.'/restore', ['_token' => $restoreToken]);
        self::assertResponseRedirects('/admin/pages/trash');
        $page = self::getContainer()->get(PageRepository::class)->find($pageId);
        self::assertFalse($page?->isDeleted());
        self::assertFalse($page?->isPublished());

        $page?->moveToTrash();
        self::getContainer()->get(EntityManagerInterface::class)->flush();
        $this->client->request('GET', '/admin/pages/trash');
        $destroyToken = (string) $this->client->getCrawler()->filter('form[action="/admin/pages/'.$pageId.'/destroy"] input[name="_token"]')->attr('value');
        $this->client->request('POST', '/admin/pages/'.$pageId.'/destroy', ['_token' => $destroyToken]);
        self::assertResponseRedirects('/admin/pages/trash');
        self::assertNull(self::getContainer()->get(PageRepository::class)->find($pageId));
    }

    public function testMultiplePagesCanBeRestoredFromTrashAsDrafts(): void
    {
        $admin = new AdminUser('bulk-restore@example.test', 'bulk-restore');
        $first = new Page(); $first->setTitle('Pierwsza usunięta'); $first->setSlug('pierwsza-usunieta'); $first->setPublished(true); $first->moveToTrash();
        $second = new Page(); $second->setTitle('Druga usunięta'); $second->setSlug('druga-usunieta'); $second->setPublished(true); $second->moveToTrash();
        foreach ([$admin, $first, $second] as $entity) $this->entityManager->persist($entity);
        $this->entityManager->flush();
        $this->client->loginUser($admin, 'admin');

        $this->client->request('GET', '/admin/pages/trash');
        self::assertResponseIsSuccessful();
        self::assertSelectorCount(2, '[data-page-select]');
        self::assertSelectorExists('button[form="trash-bulk-form"]');
        $this->client->request('GET', '/admin/pages/trash?q=Pierwsza');
        self::assertResponseIsSuccessful();
        self::assertSelectorCount(1, '[data-page-select]');
        self::assertSelectorTextContains('.admin-table tbody', 'Pierwsza usunięta');
        self::assertSelectorTextNotContains('.admin-table tbody', 'Druga usunięta');
        $this->client->request('GET', '/admin/pages/trash');
        $token = (string) $this->client->getCrawler()->filter('#trash-bulk-form input[name="_token"]')->attr('value');
        $this->client->request('POST', '/admin/pages/trash/bulk-restore', ['_token' => $token, 'pages' => [$first->getId(), $second->getId()]]);
        self::assertResponseRedirects('/admin/pages/trash');
        $this->client->followRedirect();
        self::assertSelectorTextContains('.flash--success', '2 podstron');

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();
        foreach ([$first->getId(), $second->getId()] as $id) {
            $restored = $entityManager->find(Page::class, $id);
            self::assertFalse($restored?->isDeleted());
            self::assertFalse($restored?->isPublished());
        }
    }

    public function testAdministratorCanLogInWithUsername(): void
    {
        $user = new AdminUser('owner@example.test', 'administrator');
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $user->setPassword($hasher->hashPassword($user, 'very-secure-password'));
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->request('GET', '/admin/login');
        $this->client->submitForm('Zaloguj się', [
            '_username' => 'administrator',
            '_password' => 'invalid-password',
        ]);
        self::assertResponseRedirects('/admin/login');
        self::assertCount(1, $this->entityManager->getRepository(AuditLog::class)->findBy(['action' => 'login_failure']));

        $this->client->request('GET', '/admin/login');
        $form = $this->client->getCrawler()->filter('form')->form([
            '_username' => 'administrator',
            '_password' => 'very-secure-password',
        ]);
        $this->client->submit($form);

        self::assertResponseRedirects('/admin');
    }

    public function testEditorCanManageContentButCannotOpenAdministratorSettings(): void
    {
        $user = new AdminUser('editor@example.test', 'editor');
        $user->setAssignedRoles(['ROLE_EDITOR']);
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $user->setPassword($hasher->hashPassword($user, 'very-secure-password'));
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->request('GET', '/admin/login');
        $this->client->submitForm('Zaloguj się', [
            '_username' => 'editor',
            '_password' => 'very-secure-password',
        ]);
        self::assertResponseRedirects('/admin');

        $this->client->request('GET', '/admin');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.modern-user__data', 'Redaktor');
        self::assertSelectorExists('.modern-nav a[href="/admin/pages"]');
        self::assertSelectorExists('.modern-nav a[href="/admin/configuration/files"]');
        self::assertSelectorNotExists('.modern-nav a[href="/admin/users"]');
        self::assertSelectorNotExists('.modern-nav-group');
        self::assertSelectorCount(2, '.modern-module-card');

        $this->client->request('GET', '/admin/pages');
        self::assertResponseIsSuccessful();
        $this->client->request('GET', '/admin/configuration/files');
        self::assertResponseIsSuccessful();
        $this->client->request('GET', '/admin/users');
        self::assertResponseStatusCodeSame(403);
        $this->client->request('GET', '/admin/configuration/system');
        self::assertResponseStatusCodeSame(403);

        $this->client->request('GET', '/admin/modules');
        self::assertResponseStatusCodeSame(403);

        $this->client->request('GET', '/admin/memberships');
        self::assertResponseStatusCodeSame(403);
    }

    public function testAdministratorCanResetPasswordWithOneTimeToken(): void
    {
        $user = new AdminUser('reset@example.test', 'reset-user');
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $user->setPassword($hasher->hashPassword($user, 'old-secure-password'));
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->request('GET', '/admin/password/forgot');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Odzyskiwanie hasła');
        $this->client->submitForm('Wyślij link do zmiany hasła', ['identifier' => 'unknown@example.test']);
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.flash--success', 'Jeśli aktywne konto istnieje');

        $managedUser = self::getContainer()->get(AdminUserRepository::class)->find($user->getId());
        self::assertNotNull($managedUser);
        $token = self::getContainer()->get(PasswordResetManager::class)->create($managedUser);
        $this->client->request('GET', '/admin/password/reset/'.$token);
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Ustaw nowe hasło');
        $this->client->submitForm('Zapisz nowe hasło', [
            'password' => 'new-very-secure-password',
            'password_confirmation' => 'new-very-secure-password',
        ]);
        self::assertResponseRedirects('/admin/login');

        $updatedUser = self::getContainer()->get(AdminUserRepository::class)->find($user->getId());
        self::assertNotNull($updatedUser);
        self::assertTrue($hasher->isPasswordValid($updatedUser, 'new-very-secure-password'));

        $this->client->request('GET', '/admin/password/reset/'.$token);
        self::assertResponseStatusCodeSame(410);
        self::assertSelectorTextContains('.flash--error', 'Link jest nieprawidłowy');
    }

    public function testPublicSearchUsesLocalizedContentAndHidesRestrictedPages(): void
    {
        $polish = new Language();
        $polish->setName('Polski'); $polish->setCode('pl'); $polish->setDefaultLanguage(true);
        $english = new Language();
        $english->setName('English'); $english->setCode('en');
        $this->entityManager->persist($polish); $this->entityManager->persist($english);

        $public = new Page();
        $public->setTitle('Oferta wdrożeniowa'); $public->setSlug('oferta'); $public->setCaption('Nowoczesne wdrożenia Symfony'); $public->setPublished(true);
        $restricted = new Page();
        $restricted->setTitle('Poufna oferta'); $restricted->setSlug('poufna-oferta'); $restricted->setContent('Nowoczesne wdrożenia tylko dla klientów'); $restricted->setPublished(true); $restricted->setAccess('Registered');
        $translation = new PageTranslation($public, $english);
        $translation->setTitle('Implementation services'); $translation->setSlug('implementation-services'); $translation->setContent('Modern Symfony implementation'); $translation->setPublished(true);
        $this->entityManager->persist($public); $this->entityManager->persist($restricted); $this->entityManager->persist($translation);
        $this->entityManager->flush();

        $this->client->request('GET', '/search?q=wdrożenia');
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('X-Robots-Tag', 'noindex, follow');
        self::assertSelectorTextContains('.site-search-summary', '1');
        self::assertSelectorExists('.site-search-results a[href="/oferta"]');
        self::assertSelectorTextNotContains('.site-search-results', 'Poufna oferta');

        $this->client->request('GET', '/en/search?q=implementation');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.site-search-results a[href="/en/implementation-services"]');
        self::assertSelectorExists('.site-search-link[href="/en/search"]');

        $this->client->request('GET', '/search?q=%25%25');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.site-search-summary', '0');
    }

    public function testPublicNotFoundUsesConfiguredPageAndLocalizedTranslation(): void
    {
        $polish = new Language();
        $polish->setName('Polski'); $polish->setCode('pl'); $polish->setDefaultLanguage(true);
        $english = new Language();
        $english->setName('English'); $english->setCode('en');
        $errorPage = new Page();
        $errorPage->setTitle('Nie znaleziono strony'); $errorPage->setSlug('blad-404'); $errorPage->setContent('<p>Sprawdź wpisany adres.</p>');
        $errorPage->setEditorMode('rich_text'); $errorPage->setPublished(true); $errorPage->setErrorPage(true);
        $translation = new PageTranslation($errorPage, $english);
        $translation->setTitle('Page not found'); $translation->setSlug('page-not-found'); $translation->setBuilderData('[{"id":"section","type":"layout_section","data":{"container":"grid","widths":[100],"columns":[[{"id":"text","type":"rich_text","data":{"content":"<p>Check the requested address.</p>"}}]]}}]'); $translation->setPublished(true);
        $this->entityManager->persist($polish); $this->entityManager->persist($english); $this->entityManager->persist($errorPage); $this->entityManager->persist($translation);
        $this->entityManager->flush();

        $this->client->request('GET', '/adres-ktory-nie-istnieje');
        self::assertResponseStatusCodeSame(404);
        self::assertResponseHeaderSame('X-Robots-Tag', 'noindex, nofollow');
        self::assertSelectorTextContains('h1', 'Nie znaleziono strony');
        self::assertSelectorTextContains('.public-article__body', 'Sprawdź wpisany adres.');

        $this->client->request('GET', '/en/address-that-does-not-exist');
        self::assertResponseStatusCodeSame(404);
        self::assertSelectorTextContains('.builder-rich-text', 'Check the requested address.');

        $this->client->request('GET', '/admin/address-that-does-not-exist');
        self::assertResponseStatusCodeSame(404);
        self::assertSelectorNotExists('.public-page-hero');
    }

    public function testLegacyAndManagedUrlsRedirectSafely(): void
    {
        $page = new Page();
        $page->setTitle('Oferta'); $page->setSlug('oferta'); $page->setPublished(true);
        $redirect = new UrlRedirect();
        $redirect->setSourcePath('/stara-oferta'); $redirect->setTargetPath('/oferta?source=legacy');
        $this->entityManager->persist($page); $this->entityManager->persist($redirect); $this->entityManager->flush();

        $this->client->request('GET', '/strona/oferta');
        self::assertResponseRedirects('/oferta', 301);
        $this->client->request('GET', '/content.php?url=oferta');
        self::assertResponseRedirects('/oferta', 301);
        $this->client->request('GET', '/index.php?url=oferta');
        self::assertResponseRedirects('/oferta', 301);

        $this->client->request('GET', '/stara-oferta');
        self::assertResponseRedirects('/oferta?source=legacy', 301);
        $this->entityManager->clear();
        $stored = $this->entityManager->getRepository(UrlRedirect::class)->find($redirect->getId());
        self::assertSame(1, $stored?->getHits());
        self::assertNotNull($stored?->getLastUsedAt());

        $unsafe = new UrlRedirect(); $unsafe->setSourcePath('/unsafe'); $unsafe->setTargetPath('/\\evil.example/path');
        $errors = self::getContainer()->get(ValidatorInterface::class)->validate($unsafe);
        self::assertGreaterThan(0, $errors->count());
    }

    public function testChangingPageSlugCreatesAndFlattensPermanentRedirects(): void
    {
        $admin = new AdminUser('slug-admin@example.test', 'slug-admin');
        $admin->setPassword(self::getContainer()->get(UserPasswordHasherInterface::class)->hashPassword($admin, 'very-secure-password'));
        $page = new Page(); $page->setTitle('Stary adres'); $page->setSlug('stary-adres'); $page->setPublished(true);
        $this->entityManager->persist($admin); $this->entityManager->persist($page); $this->entityManager->flush();
        $this->client->loginUser($admin, 'admin');

        $this->client->request('GET', '/admin/pages/'.$page->getId().'/edit');
        $this->client->submitForm('Zapisz i kontynuuj edycję', ['page[title]' => 'Nowy adres', 'page[slug]' => 'nowy-adres']);
        self::assertResponseRedirects('/admin/pages/'.$page->getId().'/edit');
        $this->client->request('GET', '/stary-adres');
        self::assertResponseRedirects('/nowy-adres', 301);

        $this->client->request('GET', '/admin/pages/'.$page->getId().'/edit');
        $this->client->submitForm('Zapisz i kontynuuj edycję', ['page[slug]' => 'aktualny-adres']);
        self::assertResponseRedirects('/admin/pages/'.$page->getId().'/edit');
        $redirects = $this->entityManager->getRepository(UrlRedirect::class);
        self::assertSame('/aktualny-adres', $redirects->findOneBy(['sourcePath' => '/stary-adres'])?->getTargetPath());
        self::assertSame('/aktualny-adres', $redirects->findOneBy(['sourcePath' => '/nowy-adres'])?->getTargetPath());

        // A previously used slug can become the page URL again. Its old
        // redirect must no longer intercept the real page or create a loop.
        $this->client->request('GET', '/admin/pages/'.$page->getId().'/edit');
        self::assertResponseIsSuccessful();
        $form = $this->client->getCrawler()->filter('form[name="page"]')->form();
        $this->client->submit($form, ['page[slug]' => 'stary-adres']);
        self::assertResponseRedirects('/admin/pages');
        $this->entityManager->clear();
        self::assertSame('stary-adres', $this->entityManager->find(Page::class, $page->getId())?->getSlug());
        self::assertFalse($this->entityManager->getRepository(UrlRedirect::class)->findOneBy(['sourcePath' => '/stary-adres'])?->isActive());
        self::assertSame('/stary-adres', $this->entityManager->getRepository(UrlRedirect::class)->findOneBy(['sourcePath' => '/aktualny-adres'])?->getTargetPath());

        $manager = self::getContainer()->get(UrlRedirectManager::class);
        $chainEnd = new UrlRedirect(); $chainEnd->setSourcePath('/chain-middle'); $chainEnd->setTargetPath('/chain-end');
        $this->entityManager->persist($chainEnd); $this->entityManager->flush();
        $chainStart = new UrlRedirect(); $chainStart->setSourcePath('/chain-start'); $chainStart->setTargetPath('/chain-middle');
        $manager->prepare($chainStart);
        self::assertSame('/chain-end', $chainStart->getTargetPath());
        $this->entityManager->persist($chainStart); $this->entityManager->flush();
        $loop = new UrlRedirect(); $loop->setSourcePath('/chain-end'); $loop->setTargetPath('/chain-start');
        $this->expectException(\LogicException::class);
        $manager->prepare($loop);
    }

    public function testStalePageEditCannotOverwriteAnotherOperatorsChanges(): void
    {
        $admin = new AdminUser('concurrency-admin@example.test', 'concurrency-admin');
        $admin->setPassword(self::getContainer()->get(UserPasswordHasherInterface::class)->hashPassword($admin, 'very-secure-password'));
        $page = new Page();
        $page->setTitle('Pierwotny tytuł');
        $page->setSlug('edycja-rownolegla');
        $this->entityManager->persist($admin);
        $this->entityManager->persist($page);
        $this->entityManager->flush();
        $pageId = $page->getId();
        $this->client->loginUser($admin, 'admin');

        $crawler = $this->client->request('GET', '/admin/pages/'.$pageId.'/edit');
        self::assertResponseIsSuccessful();
        $form = $crawler->filter('form[name="page"]')->form();
        self::assertSame('1', $form['page[lockVersion]']->getValue());

        // A second operator saves the page after this form has already been opened.
        self::getContainer()->get(EntityManagerInterface::class)->getConnection()->executeStatement(
            'UPDATE cms_page SET title = ?, lock_version = lock_version + 1 WHERE id = ?',
            ['Zmiana drugiego operatora', $pageId],
        );

        $this->client->submit($form, ['page[title]' => 'Nadpisana zmiana']);
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorExists('.page-form-error');

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();
        self::assertSame('Zmiana drugiego operatora', $entityManager->find(Page::class, $pageId)?->getTitle());
    }

    public function testPageHistoryCapturesChangesAndRestoresCompleteRevision(): void
    {
        $admin = new AdminUser('history-admin@example.test', 'history-admin');
        $admin->setPassword(self::getContainer()->get(UserPasswordHasherInterface::class)->hashPassword($admin, 'very-secure-password'));
        $page = new Page(); $page->setTitle('Wersja startowa'); $page->setSlug('historia'); $page->setPublished(true);
        $this->entityManager->persist($admin); $this->entityManager->persist($page); $this->entityManager->flush();
        $this->client->loginUser($admin, 'admin');

        $this->client->request('GET', '/admin/pages/'.$page->getId().'/edit');
        $this->client->submitForm('Zapisz i kontynuuj edycję', ['page[title]' => 'Wersja pierwsza', 'page[caption]' => 'Pierwszy opis']);
        self::assertResponseRedirects('/admin/pages/'.$page->getId().'/edit');
        $first = $this->entityManager->getRepository(PageRevision::class)->findOneBy(['page' => $page, 'version' => 1]);
        self::assertNotNull($first);
        self::assertSame('history-admin', $first->getCreatedBy());

        $this->client->request('GET', '/admin/pages/'.$page->getId().'/edit');
        $this->client->submitForm('Zapisz i kontynuuj edycję', ['page[title]' => 'Wersja druga', 'page[caption]' => 'Drugi opis', 'page[published]' => false]);
        self::assertResponseRedirects('/admin/pages/'.$page->getId().'/edit');
        $history = $this->client->request('GET', '/admin/pages/'.$page->getId().'/revisions');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('table', 'Wersja druga');
        self::assertSelectorTextContains('.revision-tags', 'Treść i układ');
        self::assertSelectorExists('a[href="/admin/pages/'.$page->getId().'/revisions/'.$first->getId().'"]');
        $restoreForm = $history->filter('form[action$="/'.$first->getId().'/restore"]')->form();
        $this->client->request('GET', '/admin/pages/'.$page->getId().'/revisions/'.$first->getId());
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', '#1');
        self::assertSelectorExists('.revision-comparison__changed');
        $this->client->submit($restoreForm);
        self::assertResponseRedirects('/admin/pages/'.$page->getId().'/edit');

        $this->entityManager->clear();
        $restored = $this->entityManager->find(Page::class, $page->getId());
        self::assertSame('Wersja pierwsza', $restored?->getTitle());
        self::assertSame('Pierwszy opis', $restored?->getCaption());
        self::assertTrue($restored?->isPublished());
        self::assertCount(3, $this->entityManager->getRepository(PageRevision::class)->findBy(['page' => $restored]));
    }

    public function testPageListCanBeSortedAndRejectsUnknownSortFields(): void
    {
        $admin = new AdminUser('sorting-admin@example.test', 'sorting-admin');
        $admin->setPassword(self::getContainer()->get(UserPasswordHasherInterface::class)->hashPassword($admin, 'very-secure-password'));
        $zulu = new Page(); $zulu->setTitle('Zulu'); $zulu->setSlug('zulu');
        $alfa = new Page(); $alfa->setTitle('Alfa'); $alfa->setSlug('alfa');
        $this->entityManager->persist($admin);
        $this->entityManager->persist($zulu);
        $this->entityManager->persist($alfa);
        $this->entityManager->flush();
        $this->client->loginUser($admin, 'admin');

        $crawler = $this->client->request('GET', '/admin/pages?sort=title&direction=asc');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('select[name="sort"] option[value="title"][selected]');
        self::assertSelectorExists('select[name="direction"] option[value="asc"][selected]');
        self::assertSame(['Alfa', 'Zulu'], $crawler->filter('.page-cell strong')->each(static fn ($node): string => $node->text()));
        self::assertSelectorExists('#page-bulk-form input[name="return_sort"][value="title"]');
        self::assertSelectorExists('#page-bulk-form input[name="return_direction"][value="asc"]');

        $this->client->request('GET', '/admin/pages?sort=DROP_TABLE&direction=sideways');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('select[name="sort"] option[value="updated"][selected]');
        self::assertSelectorExists('select[name="direction"] option[value="desc"][selected]');
    }
}

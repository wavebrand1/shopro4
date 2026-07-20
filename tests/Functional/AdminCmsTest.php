<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Cms\Domain\Entity\MenuItem;
use App\Cms\Domain\Entity\MenuItemTranslation;
use App\Cms\Domain\Entity\PageTranslation;
use App\Cms\Infrastructure\Persistence\Doctrine\MenuItemRepository;
use App\Cms\Infrastructure\Persistence\Doctrine\PageRepository;
use App\Identity\Domain\Entity\AdminUser;
use App\Identity\Infrastructure\Persistence\Doctrine\AdminUserRepository;
use App\Newsletter\Application\UnsubscribeToken;
use App\Language\Domain\Entity\Language;
use App\Settings\Infrastructure\Persistence\Doctrine\SystemSettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
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
        self::assertStringContainsString('The page translation does not exist or is not published.', (string) $this->client->getResponse()->getContent());

        $this->client->request('GET', '/language/zz');
        self::assertResponseStatusCodeSame(404);
        self::assertStringContainsString('The language does not exist.', (string) $this->client->getResponse()->getContent());

        $this->client->request('GET', '/admin');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('html[lang="en"]');
        self::assertSelectorTextContains('.modern-page-heading h1', 'Good morning');
        self::assertSelectorTextContains('.modern-nav', 'CONTENT MANAGEMENT');
        self::assertSelectorCount(6, '.modern-module-card');
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

        $this->client->request('GET', '/admin/users/'.$user->getId().'/edit');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.modern-page-heading h1', 'Edit user');
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

<?php

declare(strict_types=1);

namespace App\Cms\Application;

use App\Cms\Domain\Entity\Page;
use App\Cms\Domain\Entity\PageTranslation;
use App\Cms\Infrastructure\Persistence\Doctrine\PageRepository;
use App\Language\Domain\Entity\Language;
use Doctrine\ORM\EntityManagerInterface;

final class SystemPageInstaller
{
    private const FUNCTIONAL_ROLES = [
        'loginPage', 'activationPage', 'accountPage', 'registrationPage',
        'searchPage', 'sitemapPage', 'profilePage',
    ];

    /**
     * @var array<string, array{
     *     title: string,
     *     slug: string,
     *     heading: string,
     *     lead: string,
     *     title_en: string,
     *     slug_en: string,
     *     heading_en: string,
     *     lead_en: string,
     *     setter: string
     * }>
     */
    private const DEFINITIONS = [
        'homePage' => [
            'title' => 'Strona główna', 'slug' => 'strona-glowna',
            'heading' => 'Witamy na naszej stronie', 'lead' => 'To jest domyślna strona główna. Możesz zbudować ją w edytorze podstron.',
            'title_en' => 'Homepage', 'slug_en' => 'home',
            'heading_en' => 'Welcome to our website', 'lead_en' => 'This is the default homepage. You can rebuild it in the page editor.',
            'setter' => 'setHomePage',
        ],
        'errorPage' => [
            'title' => 'Nie znaleziono strony', 'slug' => '404',
            'heading' => 'Nie znaleziono strony', 'lead' => 'Strona, której szukasz, nie istnieje lub została przeniesiona.',
            'title_en' => 'Page not found', 'slug_en' => 'page-not-found',
            'heading_en' => 'Page not found', 'lead_en' => 'The page you are looking for does not exist or has been moved.',
            'setter' => 'setErrorPage',
        ],
        'adminOnly' => [
            'title' => 'Strefa administratora', 'slug' => 'strefa-administratora',
            'heading' => 'Strefa administratora', 'lead' => 'Ta podstrona jest dostępna wyłącznie dla administratorów.',
            'title_en' => 'Administrator area', 'slug_en' => 'administrator-area',
            'heading_en' => 'Administrator area', 'lead_en' => 'This page is available to administrators only.',
            'setter' => 'setAdminOnly',
        ],
        'loginPage' => [
            'title' => 'Logowanie', 'slug' => 'logowanie',
            'heading' => 'Zaloguj się', 'lead' => 'Zaloguj się, aby uzyskać dostęp do swojego konta.',
            'title_en' => 'Sign in', 'slug_en' => 'sign-in',
            'heading_en' => 'Sign in', 'lead_en' => 'Sign in to access your account.',
            'setter' => 'setLoginPage',
        ],
        'activationPage' => [
            'title' => 'Aktywacja konta', 'slug' => 'aktywacja-konta',
            'heading' => 'Aktywacja konta', 'lead' => 'Tutaj użytkownik otrzymuje informację o wyniku aktywacji konta.',
            'title_en' => 'Account activation', 'slug_en' => 'account-activation',
            'heading_en' => 'Account activation', 'lead_en' => 'This page displays the result of account activation.',
            'setter' => 'setActivationPage',
        ],
        'accountPage' => [
            'title' => 'Moje konto', 'slug' => 'moje-konto',
            'heading' => 'Moje konto', 'lead' => 'Zarządzaj danymi i ustawieniami swojego konta.',
            'title_en' => 'My account', 'slug_en' => 'my-account',
            'heading_en' => 'My account', 'lead_en' => 'Manage your account details and settings.',
            'setter' => 'setAccountPage',
        ],
        'registrationPage' => [
            'title' => 'Rejestracja', 'slug' => 'rejestracja',
            'heading' => 'Utwórz konto', 'lead' => 'Załóż konto, aby korzystać z funkcji dostępnych dla użytkowników.',
            'title_en' => 'Registration', 'slug_en' => 'registration',
            'heading_en' => 'Create an account', 'lead_en' => 'Create an account to access features available to registered users.',
            'setter' => 'setRegistrationPage',
        ],
        'searchPage' => [
            'title' => 'Wyszukiwanie', 'slug' => 'wyszukiwanie',
            'heading' => 'Wyszukiwanie', 'lead' => 'Znajdź interesującą Cię treść w witrynie.',
            'title_en' => 'Search', 'slug_en' => 'site-search',
            'heading_en' => 'Search', 'lead_en' => 'Find the content you need on this website.',
            'setter' => 'setSearchPage',
        ],
        'sitemapPage' => [
            'title' => 'Mapa witryny', 'slug' => 'mapa-witryny',
            'heading' => 'Mapa witryny', 'lead' => 'Lista najważniejszych stron dostępnych w witrynie.',
            'title_en' => 'Sitemap', 'slug_en' => 'sitemap',
            'heading_en' => 'Sitemap', 'lead_en' => 'A list of the most important pages available on this website.',
            'setter' => 'setSitemapPage',
        ],
        'profilePage' => [
            'title' => 'Profil użytkownika', 'slug' => 'profil-uzytkownika',
            'heading' => 'Twój profil', 'lead' => 'Uzupełnij i aktualizuj informacje widoczne w Twoim profilu.',
            'title_en' => 'User profile', 'slug_en' => 'user-profile',
            'heading_en' => 'Your profile', 'lead_en' => 'Review and update the information in your profile.',
            'setter' => 'setProfilePage',
        ],
        'termsPage' => [
            'title' => 'Regulamin', 'slug' => 'regulamin',
            'heading' => 'Regulamin serwisu', 'lead' => 'Uzupełnij treść regulaminu przed publicznym uruchomieniem witryny.',
            'title_en' => 'Terms and conditions', 'slug_en' => 'terms-and-conditions',
            'heading_en' => 'Terms and conditions', 'lead_en' => 'Complete the terms and conditions before publishing the website.',
            'setter' => 'setTermsPage',
        ],
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PageRepository $pages,
    ) {
    }

    /** @return array{created: int, translations: int, existing: int} */
    public function install(): array
    {
        $created = 0;
        $existing = 0;
        $installedPages = [];

        foreach (self::DEFINITIONS as $role => $definition) {
            $page = $this->pages->findOneBy([$role => true, 'deletedAt' => null]);
            if ($page instanceof Page) {
                $this->upgradeFactoryContent($page, $role, $definition['heading'], $definition['lead']);
                $this->ensureSystemRoleComponent($page, $role);
                ++$existing;
                $installedPages[$role] = $page;
                continue;
            }

            $page = new Page();
            $page->setTitle($definition['title']);
            $page->setSlug($this->availableBaseSlug($definition['slug']));
            $page->setPublished(true);
            $page->setDescription($definition['lead']);
            $page->setBuilderData($this->builderData($definition['heading'], $definition['lead'], $role));
            $page->{$definition['setter']}(true);
            $this->entityManager->persist($page);
            $installedPages[$role] = $page;
            ++$created;
        }
        $this->entityManager->flush();

        $translations = $this->installTranslations($installedPages);
        $this->entityManager->flush();

        return ['created' => $created, 'translations' => $translations, 'existing' => $existing];
    }

    /** @param array<string, Page> $pages */
    private function installTranslations(array $pages): int
    {
        $created = 0;
        $languages = $this->entityManager->getRepository(Language::class)->findBy([
            'active' => true,
            'defaultLanguage' => false,
        ]);

        foreach ($languages as $language) {
            foreach ($pages as $role => $page) {
                $definition = self::DEFINITIONS[$role];
                $existingTranslation = $this->entityManager->getRepository(PageTranslation::class)->findOneBy([
                    'page' => $page,
                    'language' => $language,
                ]);
                if ($existingTranslation instanceof PageTranslation) {
                    $heading = $language->getCode() === 'en' ? $definition['heading_en'] : $definition['heading'];
                    $lead = $language->getCode() === 'en' ? $definition['lead_en'] : $definition['lead'];
                    $this->upgradeFactoryContent($existingTranslation, $role, $heading, $lead, $language->getCode());
                    $this->ensureSystemRoleComponent($existingTranslation, $role);
                    continue;
                }

                $translation = new PageTranslation($page, $language);
                if ($language->getCode() === 'en') {
                    $translation->setTitle($definition['title_en']);
                    $translation->setSlug($this->availableTranslationSlug($language, $definition['slug_en']));
                    $translation->setDescription($definition['lead_en']);
                    $translation->setBuilderData($this->builderData($definition['heading_en'], $definition['lead_en'], $role, 'en'));
                }
                $translation->setPublished(true);
                $this->entityManager->persist($translation);
                ++$created;
            }
        }

        return $created;
    }

    private function availableBaseSlug(string $preferred): string
    {
        $candidate = $preferred;
        $suffix = 2;
        while ($this->pages->count(['slug' => $candidate]) > 0) {
            $candidate = $preferred.'-'.$suffix++;
        }

        return $candidate;
    }

    private function availableTranslationSlug(Language $language, string $preferred): string
    {
        $repository = $this->entityManager->getRepository(PageTranslation::class);
        $candidate = $preferred;
        $suffix = 2;
        while ($repository->count(['language' => $language, 'slug' => $candidate]) > 0) {
            $candidate = $preferred.'-'.$suffix++;
        }

        return $candidate;
    }

    private function builderData(string $heading, string $lead, string $role, string $locale = 'pl'): string
    {
        if (in_array($role, self::FUNCTIONAL_ROLES, true)) {
            return $this->section([[
                'id' => 'system-role-'.$role,
                'type' => 'system_role',
                'data' => [],
            ]], 'full', 'system-role-section-'.$role);
        }

        $content = match ($role) {
            'homePage' => $this->homepageContent($heading, $lead, $locale),
            'errorPage' => $this->errorContent($heading, $lead, $locale),
            'adminOnly' => $this->adminContent($heading, $lead, $locale),
            'termsPage' => $this->termsContent($heading, $lead, $locale),
            default => $this->introContent($heading, $lead, $locale),
        };

        return $this->section([[
            'id' => 'system-content-'.substr(hash('sha256', $role.$lead), 0, 12),
            'type' => 'rich_text',
            'data' => ['content' => $content],
        ]], 'full', 'system-section-'.$role);
    }

    /**
     * This reproduces the layout installed before the richer system-page
     * defaults were introduced. It is used only to recognise untouched
     * factory content and must remain stable.
     */
    private function legacyBuilderData(string $heading, string $lead, string $role): string
    {
        $components = [[
            'id' => 'system-content-'.substr(hash('sha256', $lead), 0, 12),
            'type' => 'rich_text',
            'data' => ['content' => sprintf(
                '<h1>%s</h1><p>%s</p>',
                $this->escape($heading),
                $this->escape($lead),
            )],
        ]];
        if (in_array($role, self::FUNCTIONAL_ROLES, true)) {
            $components[] = ['id' => 'system-role-'.$role, 'type' => 'system_role', 'data' => []];
        }

        return json_encode([[
            'id' => 'system-section-'.substr(hash('sha256', $heading), 0, 12),
            'type' => 'layout_section',
            'data' => [
                'container' => 'grid',
                'widths' => [100],
                'columns' => [$components],
            ],
        ]], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function upgradeFactoryContent(
        Page|PageTranslation $page,
        string $role,
        string $heading,
        string $lead,
        string $locale = 'pl',
    ): void {
        if ($page->getBuilderData() !== $this->legacyBuilderData($heading, $lead, $role)) {
            return;
        }

        $page->setBuilderData($this->builderData($heading, $lead, $role, $locale));
    }

    /** @param list<array<string, mixed>> $components */
    private function section(array $components, string $container, string $id): string
    {
        return json_encode([[
            'id' => $id,
            'type' => 'layout_section',
            'data' => [
                'container' => $container,
                'widths' => [100],
                'columns' => [$components],
            ],
        ]], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function homepageContent(string $heading, string $lead, string $locale): string
    {
        $english = $locale === 'en';
        return sprintf(
            '<section class="system-page-default system-page-default--home">'
            .'<span class="site-kicker">%s</span><h1>%s</h1><p>%s</p>'
            .'<a class="site-button" href="#about-us">%s <span>→</span></a>'
            .'</section>',
            $english ? 'Welcome' : 'Witamy',
            $this->escape($heading),
            $this->escape($lead),
            $english ? 'Discover our offer' : 'Poznaj naszą ofertę',
        );
    }

    private function errorContent(string $heading, string $lead, string $locale): string
    {
        $english = $locale === 'en';
        return sprintf(
            '<section class="system-page-default system-page-default--error">'
            .'<div class="system-page-default__code">404</div><span class="site-kicker">%s</span>'
            .'<h1>%s</h1><p>%s</p><a class="site-button" href="/">← %s</a>'
            .'</section>',
            $english ? 'Page not found' : 'Nie znaleziono strony',
            $this->escape($heading),
            $this->escape($lead),
            $english ? 'Back to homepage' : 'Wróć na stronę główną',
        );
    }

    private function adminContent(string $heading, string $lead, string $locale): string
    {
        $english = $locale === 'en';
        return sprintf(
            '<section class="system-page-default system-page-default--admin">'
            .'<span class="site-kicker">%s</span><h1>%s</h1><p>%s</p>'
            .'<div class="system-page-default__notice"><strong>%s</strong><span>%s</span></div>'
            .'</section>',
            $english ? 'Protected access' : 'Dostęp chroniony',
            $this->escape($heading),
            $this->escape($lead),
            $english ? 'Secure area' : 'Bezpieczna przestrzeń',
            $english
                ? 'This page is visible only after signing in to an operator account.'
                : 'Zawartość tej strony jest widoczna wyłącznie po zalogowaniu na konto operatora.',
        );
    }

    private function termsContent(string $heading, string $lead, string $locale): string
    {
        if ($locale === 'en') {
            return sprintf(
                '<article class="system-page-default system-page-default--document">'
                .'<span class="site-kicker">Website document</span><h1>%s</h1><p class="system-page-default__lead">%s</p>'
                .'<div class="system-page-default__warning"><strong>Before publishing</strong><span>Add your company details and adapt this content to the services provided through the website.</span></div>'
                .'<h2>1. General information</h2><p>These terms define the rules for using the website, user rights and obligations, and the provision of electronic services.</p>'
                .'<h2>2. Service provider and contact</h2><p>Enter the full company name, registered address, registration details and the e-mail address intended for user contact.</p>'
                .'<h2>3. Website usage rules</h2><p>Describe technical requirements, account creation rules, prohibited activities and conditions for using available functions.</p>'
                .'<h2>4. User accounts</h2><p>Define registration, activation and account deletion procedures, responsibility for credentials and available user permissions.</p>'
                .'<h2>5. Complaints and liability</h2><p>Explain how complaints can be filed, response times and the service provider’s liability rules.</p>'
                .'<h2>6. Personal data</h2><p>Refer to the current privacy policy and information about cookies.</p>'
                .'<h2>7. Final provisions</h2><p>State the effective date, how changes will be communicated and the governing law.</p>'
                .'</article>',
                $this->escape($heading),
                $this->escape($lead),
            );
        }

        return sprintf(
            '<article class="system-page-default system-page-default--document">'
            .'<span class="site-kicker">Dokument serwisu</span><h1>%s</h1><p class="system-page-default__lead">%s</p>'
            .'<div class="system-page-default__warning"><strong>Przed publikacją</strong>'
            .'<span>Uzupełnij dane firmy i dostosuj poniższą treść do usług świadczonych w witrynie.</span></div>'
            .'<h2>1. Informacje ogólne</h2><p>Regulamin określa zasady korzystania z serwisu, prawa i obowiązki użytkowników oraz zasady świadczenia usług drogą elektroniczną.</p>'
            .'<h2>2. Usługodawca i kontakt</h2><p>Wpisz pełną nazwę firmy, adres siedziby, dane rejestrowe oraz adres e-mail przeznaczony do kontaktu z użytkownikami.</p>'
            .'<h2>3. Zasady korzystania z serwisu</h2><p>Opisz wymagania techniczne, zasady zakładania konta, niedozwolone działania i warunki korzystania z udostępnionych funkcji.</p>'
            .'<h2>4. Konta użytkowników</h2><p>Określ sposób rejestracji, aktywacji i usuwania kont, odpowiedzialność za dane dostępowe oraz dostępne uprawnienia użytkownika.</p>'
            .'<h2>5. Reklamacje i odpowiedzialność</h2><p>Podaj sposób i termin składania reklamacji, przewidywany czas odpowiedzi oraz zasady odpowiedzialności usługodawcy.</p>'
            .'<h2>6. Dane osobowe</h2><p>Zamieść odwołanie do aktualnej polityki prywatności i informacji o plikach cookies.</p>'
            .'<h2>7. Postanowienia końcowe</h2><p>Wskaż datę obowiązywania regulaminu, zasady informowania o zmianach oraz prawo właściwe.</p>'
            .'</article>',
            $this->escape($heading),
            $this->escape($lead),
        );
    }

    private function introContent(string $heading, string $lead, string $locale): string
    {
        return sprintf(
            '<section class="system-page-default"><span class="site-kicker">%s</span>'
            .'<h1>%s</h1><p>%s</p></section>',
            $locale === 'en' ? 'Information' : 'Informacje',
            $this->escape($heading),
            $this->escape($lead),
        );
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function ensureSystemRoleComponent(Page|PageTranslation $page, string $role): void
    {
        if (!in_array($role, self::FUNCTIONAL_ROLES, true)) return;

        try {
            $blocks = json_decode($page->getBuilderData(), true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return;
        }
        if (!is_array($blocks) || $this->containsSystemRoleComponent($blocks)) return;

        $component = ['id' => 'system-role-'.$role, 'type' => 'system_role', 'data' => []];
        foreach ($blocks as &$block) {
            if (($block['type'] ?? null) !== 'layout_section' || !isset($block['data']['columns'][0]) || !is_array($block['data']['columns'][0])) continue;
            $block['data']['columns'][0][] = $component;
            $page->setBuilderData(json_encode($blocks, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return;
        }
        unset($block);

        $blocks[] = [
            'id' => 'system-role-section-'.$role,
            'type' => 'layout_section',
            'data' => ['container' => 'grid', 'widths' => [100], 'columns' => [[$component]]],
        ];
        $page->setBuilderData(json_encode($blocks, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function containsSystemRoleComponent(array $node): bool
    {
        if (($node['type'] ?? null) === 'system_role') return true;
        foreach ($node as $value) {
            if (is_array($value) && $this->containsSystemRoleComponent($value)) return true;
        }
        return false;
    }
}

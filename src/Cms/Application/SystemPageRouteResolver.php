<?php

declare(strict_types=1);

namespace App\Cms\Application;

use App\Cms\Domain\Entity\Page;
use App\Language\Domain\Entity\Language;

final class SystemPageRouteResolver
{
    /** @return array{route: string, parameters: array<string, string>}|null */
    public function resolve(Page $page, ?Language $language = null): ?array
    {
        if ($page->isLoginPage()) {
            return ['route' => 'site_login', 'parameters' => []];
        }
        if ($page->isActivationPage()) {
            return ['route' => 'site_activation_resend', 'parameters' => []];
        }
        if ($page->isAccountPage()) {
            return ['route' => 'site_account', 'parameters' => []];
        }
        if ($page->isRegistrationPage()) {
            return ['route' => 'site_register', 'parameters' => []];
        }
        if ($page->isSearchPage()) {
            return $language !== null && !$language->isDefaultLanguage()
                ? ['route' => 'cms_search_localized', 'parameters' => ['_locale' => $language->getCode()]]
                : ['route' => 'cms_search', 'parameters' => []];
        }
        if ($page->isSitemapPage()) {
            return $language !== null && !$language->isDefaultLanguage()
                ? ['route' => 'cms_sitemap_page_localized', 'parameters' => ['_locale' => $language->getCode()]]
                : ['route' => 'cms_sitemap_page', 'parameters' => []];
        }
        if ($page->isProfilePage()) {
            return ['route' => 'site_account_profile', 'parameters' => []];
        }

        // The 404 page is rendered by PublicErrorSubscriber. Terms pages and
        // administrator-only pages use their editable CMS content directly.
        return null;
    }
}

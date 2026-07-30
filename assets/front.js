/*
 * Minimalny punkt wejścia dla witryny publicznej.
 *
 * Panel administracyjny nadal korzysta z app.js (Turbo, Stimulus i zachowania
 * formularzy). Dzięki temu odwiedzający stronę nie pobierają kodu PA.
 */

const siteMenu = document.querySelector('[data-site-menu]');
siteMenu?.addEventListener('click', () => document.body.classList.toggle('site-menu-is-open'));

document.querySelectorAll('#site-navigation a').forEach((link) => {
    link.addEventListener('click', () => document.body.classList.remove('site-menu-is-open'));
});

const initializeCookieConsent = () => {
    const banner = document.querySelector('[data-cookie-consent]');
    if (!banner) return;

    const key = 'shopro_cookie_consent';
    const legacyKey = 'shopro_analytics_consent';
    const manageButton = document.querySelector('[data-cookie-manage]');
    const preferences = banner.querySelector('[data-cookie-preferences]');
    const requiresAnalyticsConsent = banner.dataset.analyticsConsentRequired !== 'false';
    const hideBanner = () => {
        banner.hidden = true;
        banner.setAttribute('aria-hidden', 'true');
    };
    const showBanner = () => {
        banner.hidden = false;
        banner.setAttribute('aria-hidden', 'false');
    };

    const readCookie = () => {
        const value = document.cookie.split('; ').find((item) => item.startsWith(`${key}=`))?.split('=').slice(1).join('=');
        if (!value) return null;
        try { return JSON.parse(decodeURIComponent(value)); } catch (_) { return null; }
    };
    const normalizeConsent = (value) => value && typeof value === 'object' ? {
        version: Number(value.version || 1),
        necessary: true,
        preferences: Boolean(value.preferences),
        analytics: Boolean(value.analytics),
        marketing: Boolean(value.marketing),
    } : null;
    const readConsent = () => {
        try {
            const current = normalizeConsent(JSON.parse(localStorage.getItem(key) || 'null')) || normalizeConsent(readCookie());
            if (current) return current;
            const legacy = localStorage.getItem(legacyKey);
            if (legacy) return { version: 1, necessary: true, preferences: false, analytics: legacy === 'granted', marketing: false };
        } catch (_) { return normalizeConsent(readCookie()); }
        return null;
    };
    const storeConsent = (selection) => {
        const consent = { version: 1, savedAt: new Date().toISOString(), necessary: true, preferences: Boolean(selection.preferences), analytics: Boolean(selection.analytics), marketing: Boolean(selection.marketing) };
        const serialized = JSON.stringify(consent);
        try { localStorage.setItem(key, serialized); localStorage.removeItem(legacyKey); } catch (_) { /* Cookie remains available. */ }
        document.cookie = `${key}=${encodeURIComponent(serialized)}; Max-Age=31536000; Path=/; SameSite=Lax${location.protocol === 'https:' ? '; Secure' : ''}`;
        return consent;
    };
    const loadAnalytics = (consent) => {
        const id = banner.dataset.measurementId;
        if (!id || !consent.analytics || window.shoproAnalyticsLoaded) return;

        window.shoproAnalyticsLoaded = true;
        const script = document.createElement('script');
        script.async = true;
        script.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(id)}`;
        document.head.append(script);

        window.dataLayer = window.dataLayer || [];
        window.gtag = function () { window.dataLayer.push(arguments); };
        window.gtag('js', new Date());
        window.gtag('consent', 'default', {
            analytics_storage: 'granted',
            ad_storage: 'denied',
            ad_user_data: 'denied',
            ad_personalization: 'denied',
        });
        window.gtag('config', id, { anonymize_ip: true });
    };
    const activateCategoryScripts = (consent) => {
        document.querySelectorAll('script[type="text/plain"][data-cookie-category]').forEach((placeholder) => {
            const category = placeholder.dataset.cookieCategory;
            if (!consent[category] || placeholder.dataset.cookieActivated === 'true') return;
            const script = document.createElement('script');
            [...placeholder.attributes].forEach((attribute) => {
                if (!['type', 'data-cookie-category'].includes(attribute.name)) script.setAttribute(attribute.name, attribute.value);
            });
            script.text = placeholder.text;
            placeholder.dataset.cookieActivated = 'true';
            placeholder.replaceWith(script);
        });
    };
    const applyConsent = (consent) => {
        loadAnalytics(consent);
        activateCategoryScripts(consent);
        hideBanner();
        preferences.hidden = true;
        manageButton.hidden = false;
    };
    const setControls = (consent) => {
        banner.querySelectorAll('[data-cookie-category]').forEach((input) => { input.checked = Boolean(consent?.[input.dataset.cookieCategory]); });
    };

    const consent = readConsent();
    if (consent) applyConsent(consent);
    else {
        showBanner();
        if (!requiresAnalyticsConsent) loadAnalytics({ analytics: true });
    }

    banner.querySelector('[data-cookie-accept]')?.addEventListener('click', () => {
        applyConsent(storeConsent({ preferences: true, analytics: true, marketing: true }));
    });
    banner.querySelector('[data-cookie-reject]')?.addEventListener('click', () => {
        applyConsent(storeConsent({ preferences: false, analytics: false, marketing: false }));
    });
    banner.querySelector('[data-cookie-settings]')?.addEventListener('click', () => { preferences.hidden = false; });
    banner.querySelector('[data-cookie-save]')?.addEventListener('click', () => {
        const selection = {};
        banner.querySelectorAll('[data-cookie-category]').forEach((input) => { selection[input.dataset.cookieCategory] = input.checked; });
        applyConsent(storeConsent(selection));
    });
    manageButton?.addEventListener('click', () => { setControls(readConsent()); preferences.hidden = false; showBanner(); manageButton.hidden = true; });
};

initializeCookieConsent();

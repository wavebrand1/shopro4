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

    const key = 'shopro_analytics_consent';
    const loadAnalytics = () => {
        const id = banner.dataset.measurementId;
        if (!id || window.shoproAnalyticsLoaded) return;

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

    const consent = localStorage.getItem(key);
    if (consent === 'granted') loadAnalytics();
    if (!consent) banner.hidden = false;

    banner.querySelector('[data-cookie-accept]')?.addEventListener('click', () => {
        localStorage.setItem(key, 'granted');
        banner.hidden = true;
        loadAnalytics();
    });
    banner.querySelector('[data-cookie-reject]')?.addEventListener('click', () => {
        localStorage.setItem(key, 'denied');
        banner.hidden = true;
    });
};

initializeCookieConsent();

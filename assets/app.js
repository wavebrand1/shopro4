import './stimulus_bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */

const sidebar = document.querySelector('#admin-sidebar');
const openSidebar = document.querySelector('[data-sidebar-open]');
const closeSidebar = document.querySelectorAll('[data-sidebar-close]');

openSidebar?.addEventListener('click', () => document.body.classList.add('sidebar-is-open'));
closeSidebar.forEach((element) => element.addEventListener('click', () => document.body.classList.remove('sidebar-is-open')));

const siteMenu = document.querySelector('[data-site-menu]');
siteMenu?.addEventListener('click', () => document.body.classList.toggle('site-menu-is-open'));
document.querySelectorAll('#site-navigation a').forEach((link) => link.addEventListener('click', () => document.body.classList.remove('site-menu-is-open')));

const initializeSystemSettings = () => {
    const form = document.querySelector('form[name="system_settings"]');
    if (!form || form.dataset.behaviourReady === 'true') return;
    form.dataset.behaviourReady = 'true';

    const fields = (names) => names.map((name) => form.querySelector(`[name="system_settings[${name}]"]`)?.closest('.settings-field')).filter(Boolean);
    const maintenanceFields = fields(['maintenance_date', 'maintenance_time', 'maintenance_message']);
    const sendmailFields = fields(['sendmail_path']);
    const smtpFields = fields(['smtp_host', 'smtp_user', 'smtp_password', 'smtp_port', 'smtp_ssl']);
    const selectedRadio = (name) => form.querySelector(`[name="system_settings[${name}]"]:checked`)?.value;
    const toggle = (elements, visible) => elements.forEach((element) => { element.hidden = !visible; });

    const refresh = () => {
        toggle(maintenanceFields, selectedRadio('maintenance') === '1');
        const mailer = form.querySelector('[name="system_settings[mailer]"]')?.value;
        toggle(sendmailFields, mailer === 'SMAIL');
        toggle(smtpFields, mailer === 'SMTP');
    };

    form.addEventListener('change', refresh);
    refresh();
};

document.addEventListener('turbo:load', initializeSystemSettings);
initializeSystemSettings();

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');

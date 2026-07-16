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

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');

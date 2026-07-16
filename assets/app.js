import './stimulus_bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';

// Cache key for the CMS styles imported by this AssetMapper entrypoint.
export const assetVersion = '2026-07-16-cms-1';

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');

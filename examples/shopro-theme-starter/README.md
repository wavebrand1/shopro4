# Shopro theme starter

This directory is a template, not a theme loaded by the Shopro Core repository.
Copy it to a new private Git repository before developing a client website.

1. Change the Composer package name, PHP namespace, bundle class and theme code.
2. Implement `ThemeProvider` with the available variants and public CSS/JS paths.
3. Add the package to the Shopro application with Composer.
4. Register the bundle in the host application's `config/bundles.php`:

```php
Shopro\Theme\Client\ClientThemeBundle::class => ['all' => true],
```

5. Run `php bin/console assets:install public` and clear production cache.
6. Check `php bin/console shopro:theme:list`, then choose the skin in the System
   configuration screen.

Page Builder components will be registered in this package through
`ThemePageBuilderComponentProvider`. Keep Twig templates, CSS, JavaScript and
component definitions in the package; do not add customer implementation files
to Shopro Core.

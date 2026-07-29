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

## Page Builder contract

`StarterComponentProvider` is the server-side definition of a block. It declares
the technical type, labels, fields, defaults, the Twig template and the fields
that contain HTML and must be sanitised. The corresponding browser definition is
in `public/page-builder.js` and is loaded through `builderJavascript` in the
theme definition. Its field names must match the server-side definition.

This split is intentional: Shopro Core owns page data and security, whereas a
skin owns the client-specific appearance and its available blocks. A skin may
replace the visual component type used by another skin; Shopro resolves that
component according to the currently selected front theme.

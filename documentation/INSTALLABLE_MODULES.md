# Instalowane moduły Shopro

## Zasada

Moduł biznesowy jest prywatnym pakietem Composer typu `symfony-bundle` i ma
własne repozytorium Git. Kod modułu nie należy do Core. Pakiet dostarcza co
najmniej `ModuleDefinition`, konfigurację usług, mapowania Doctrine oraz migracje.

Źródła modułów na lokalnym środowisku mogą znajdować się w `modules/`. Katalog
jest ignorowany przez repozytorium Core, dzięki czemu każde jego poddrzewo może
być osobnym repozytorium Git.

## Manifest development

Lokalny plik `modules/installed.json`:

```json
{
  "modules": [
    {
      "package": "wavebrand1/shopro4-mod-blog",
      "path": "modules/shopro4_mod_blog",
      "constraint": "*@dev"
    }
  ]
}
```

`bin/deploy-dev` i `bin/deploy-prod` uruchamiają `bin/sync-installed-modules`
przed właściwym `composer install`. Skrypt tworzy repozytorium Composer typu
`path`, dodaje pakiet do wymagań aplikacji i pozwala Symfony Flex zarejestrować
bundle. Następnie standardowe wdrożenie wykonuje migracje, `app:modules:sync`
oraz `app:modules:verify`.

## Serwer produkcyjny

Manifest i źródła klienta powinny znajdować się poza katalogiem `httpdocs`, aby
aktualizacja Core ich nie zastępowała. Domyślna lokalizacja manifestu to:

```text
../shopro-modules/installed.json
```

Ścieżki pakietów w manifeście są interpretowane względem katalogu aplikacji.
Inną lokalizację można wskazać zmienną `SHOPRO_MODULE_MANIFEST`.

Przykład serwerowy:

```json
{
  "modules": [
    {
      "package": "wavebrand1/shopro4-mod-blog",
      "path": "../shopro-modules/shopro4_mod_blog",
      "constraint": "*@dev"
    }
  ]
}
```

Usunięcie wpisu z manifestu nie usuwa tabel ani danych modułu. Deinstalacja
danych musi być osobną, wyraźnie potwierdzoną operacją.

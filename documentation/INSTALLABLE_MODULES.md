# Instalowane moduły Shopro

## Zasada

Moduł biznesowy jest prywatnym pakietem Composer typu `symfony-bundle` i ma
własne repozytorium Git. Kod modułu nie należy do Core. Pakiet dostarcza co
najmniej `ModuleDefinition`, konfigurację usług, mapowania Doctrine oraz migracje.

Core nie wymaga pakietów modułów w `composer.json` i nie rejestruje ich bundle'i
bezpośrednio. Czysta instalacja Shopro nie potrzebuje repozytoriów Bloga,
Portfolio ani Sklepu. Wybór modułów dla środowiska odbywa się przez manifest;
skrypt synchronizacji dodaje wskazane pakiety do Composera i rejestruje ich bundle'e.

Źródła modułów znajdują się w katalogu `modules` położonym obok katalogu
aplikacji `symfony`. Każdy moduł jest osobnym repozytorium Git; wewnątrz Core
nie przechowujemy kopii ich kodu.

## Manifest development

Lokalny plik `../modules/installed.json` (patrząc z katalogu `symfony`):

```json
{
  "modules": [
    {
      "package": "wavebrand1/shopro4-mod-blog",
      "path": "../modules/shopro4_mod_blog",
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

Repozytorium dev ma także śledzony `config/modules.dev.json`. Jeśli klient nie
ma własnego manifestu, wdrożenie dev automatycznie klonuje brakujący moduł albo
wykonuje `fetch` i wyłącznie bezpieczny fast-forward do wskazanego `ref`. Lokalne
zmiany lub inny adres `origin` zatrzymują wdrożenie zamiast nadpisywać kod.
Manifest dev nie jest używany, gdy `SHOPRO_DEPLOY_ENV=prod`.

## Serwer produkcyjny

Manifest i źródła klienta powinny znajdować się poza katalogiem `httpdocs`, aby
aktualizacja Core ich nie zastępowała. Domyślna lokalizacja manifestu to:

```text
../shopro-modules/installed.json
```

Ścieżki pakietów w manifeście są interpretowane względem katalogu aplikacji.
Inną lokalizację można wskazać zmienną `SHOPRO_MODULE_MANIFEST`.

### Przykład dla Fotograf Trzciana

Fotograf potrzebuje modułu sklepowego. Blog i portfolio nie są potrzebne, więc
manifest produkcyjny zawiera tylko Sklep:

```json
{
  "modules": [
    {
      "package": "wavebrand1/shopro4-mod-shop",
      "path": "../shopro-modules/shopro4_mod_shop",
      "constraint": "*@dev",
      "repository": "git@github-shopro-shop:wavebrand1/shopro4_mod_shop.git",
      "ref": "main"
    }
  ]
}
```

Repozytorium modułu Sklep wymaga osobnego klucza wdrożeniowego GitHub z prawem
tylko do odczytu. Jeśli manifest nie zawiera wpisów, aplikacja uruchamia się bez
modułów i nie pobiera żadnych repozytoriów modułów.

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

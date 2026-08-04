# Skórki Shopro

## Zasada

Shopro Core jest niezależny od realizacji dla konkretnego klienta. Skórka opisuje
wygląd frontu, warianty kolorystyczne, zasoby oraz komponenty Page Buildera.
Obecna skórka **Shopro Modernize** pozostaje dostarczana razem z systemem i jest
ustawiona jako domyślna. Korzysta jednak z tego samego rejestru co skórki
instalowane później.

## Instalacja skórki

Skórka jest prywatnym pakietem Composer zawierającym Symfony Bundle. Pakiet
rejestruje `ThemeProvider` i — jeżeli ma własne bloki —
`ThemePageBuilderComponentProvider`. Instalacja na serwerze wygląda następująco:

```bash
composer require wavebrand/shopro-theme-nazwa-klienta
APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear
```

W Plesk wykonujemy to podczas wdrożenia/release, nigdy przez upload kodu w PA.
Po instalacji skórka pojawia się w **Konfiguracja systemu → Szablon strony**.

## Kontrakt pakietu

Provider zwraca `ThemeDefinition`: techniczny kod, nazwę, wersję, warianty z
etykietami PL/EN oraz obsługę frontu i/lub panelu administracyjnego. Dane strony,
treści bloków i wybór wariantu pozostają w bazie Shopro.

Jeżeli skórka ma własny nagłówek lub stopkę, deklaruje także
`frontLayoutTemplate`. Jej layout rozszerza `cms/layout_base.html.twig` i
nadpisuje bloki `site_header` oraz `site_footer`; dzięki temu treść systemowych
podstron, menu, logowanie, języki i wszystkie mechanizmy Core nadal działają.

Przed odinstalowaniem skórki trzeba przełączyć witrynę na inną skórkę albo
zmigrować strony korzystające z jej bloków. Nie usuwamy kodu Modernize w trakcie
tej zmiany — chroni to strony działające na wersji 4.0.

## Komponenty Page Buildera

Komponent skórki składa się z dwóch zgodnych definicji:

- PHP: `ThemePageBuilderComponentProvider` zwraca
  `PageBuilderComponentDefinition`. Definicja podaje techniczny typ, pola
  formularza, wartości domyślne, Twig `template` i opcjonalne `htmlFields`.
- JavaScript: plik wskazany przez `ThemeDefinition::$builderJavascript` ustawia
  `window.ShoproThemeComponents`. To definicja biblioteki oraz formularza
  widocznego w Page Builderze.

Nazwy pól w obu definicjach muszą być identyczne. Pola podane w `htmlFields` są
czyszczone przez HTML Sanitizer przed zapisem, dlatego do HTML nie należy używać
zwykłego pola tekstowego bez zadeklarowania go w tym wykazie.

Core dostarcza wyłącznie bloki niezależne od wyglądu: tekst, obraz, sekcję
układu i miejsce na treść roli systemowej. Obecny katalog wizualny Modernize
(hero, karty, CTA itd.) jest rejestrowany jako katalog tej skórki. Inna skórka
może dostarczyć własny zestaw lub własną implementację tego samego typu.

Starter do nowej skórki znajduje się w `examples/shopro-theme-starter`. Należy
skopiować go do osobnego prywatnego repozytorium klienta, zmienić namespace,
nazwę pakietu, kod skórki i bundle.
# Skórki klienta przy wdrożeniach Plesk

Core i skórki klienta są wdrażane niezależnie. Wdrożenie Git przez Plesk zastępuje pliki śledzone przez repozytorium Core, w tym `composer.json`; dlatego skórka klienta musi być zadeklarowana w zewnętrznym manifeście poza `httpdocs`.

Dla witryny, której aplikacja znajduje się w `~/httpdocs`, należy utworzyć `~/shopro-themes/installed.json`:

```json
{
  "themes": [
    {
      "package": "vendor/shopro-theme-example",
      "path": "../shopro-themes/example",
      "constraint": "*@dev"
    }
  ]
}
```

`bin/deploy-dev` i `bin/deploy-prod` odczytują ten manifest przed instalacją Composera. Odtwarzają repozytorium typu `path`, dołączają każdy wymieniony pakiet i pozwalają Symfony Flex zarejestrować jego bundle. Manifest oraz źródła skórki przetrwają więc aktualizację Core. Ścieżka `path` jest liczona względem `httpdocs`; należy użyć katalogu poza `httpdocs`, dostępnego do odczytu dla użytkownika hostingu.

Na środowisku dev domyślna Atena jest wyjątkiem bootstrapowym: ponieważ Core
wymaga jej pakietu już w `composer.json`, `bin/sync-installed-themes` klonuje lub
aktualizuje repozytorium do `themes/shopro-theme-atena-fit` przed pierwszym
wywołaniem Composera. Produkcja nadal wymaga jawnego, wersjonowanego źródła.

# Shopro Component Builder

Edytor stron nie zapisuje swobodnego HTML ani CSS. Programista przygotowuje komponent,
jego formularz administracyjny i szablon Twig, a redaktor tworzy instancje komponentu,
zmienia tylko dozwolone pola oraz ustala ich kolejność.

## Tryby strony

- rich_text — treść tekstowa w kolumnie cms_page.content; po ustawieniu
  TINYMCE_API_KEY formularz uruchamia TinyMCE 8 z Tiny Cloud,
- components — kontrolowane komponenty zapisane jako JSON w
  cms_page.builder_data.

Istniejące strony otrzymują tryb rich_text, dlatego ich treść pozostaje bez zmian.
Kolumna builder_css pozostaje w bazie wyłącznie dla zgodności z pierwszym prototypem
i nie jest wykonywana na stronie.

## Format danych

Każdy element ma stabilne id, identyfikator type oraz obiekt data. Typ
feature_cards zawiera ustawienia sekcji i tablicę items. Każda karta przechowuje:

- id,
- title i text,
- url i buttonLabel,
- icon i color.

Feature cards jest pierwszym komponentem referencyjnym. Obsługuje powielanie,
usuwanie i zmianę kolejności kart, 2–4 kolumny, nagłówek sekcji oraz pola każdej karty.
Publiczny HTML pochodzi wyłącznie z szablonu
templates/cms/block/feature_cards.html.twig.

## Dodawanie komponentów dla klienta

Nowy typ wymaga:

1. definicji formularza i wartości początkowych w assets/component-builder.js,
2. szablonu w templates/cms/block/,
3. jawnego dopuszczenia typu w templates/cms/page/show.html.twig,
4. stylów publicznych i administracyjnych,
5. testu renderowania.

Nie należy zapisywać w JSON haseł, sekretów ani wykonywalnego JavaScriptu.
Adresy linków komponentu są renderowane tylko dla dozwolonych protokołów.

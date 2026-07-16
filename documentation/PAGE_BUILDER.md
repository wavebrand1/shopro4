# Shopro Page Builder

Nowy edytor wizualny wykorzystuje GrapesJS 0.23.2 jako silnik komponentów, canvasu,
drag-and-drop i urządzeń. Integracja znajduje się w `assets/page-builder.js`.
Pakiety vendor importmapy są instalowane podczas wdrożenia przez `bin/deploy-dev` przed
kompilacją AssetMapper.

## Model zapisu

- `cms_page.builder_data` — projekt GrapesJS w JSON; źródło kolejnych edycji,
- `cms_page.content` — wygenerowany HTML i format zgodności z legacy,
- `cms_page.builder_css` — CSS wygenerowany dla danej podstrony.

Strona bez `builder_data` jest otwierana przez import istniejącego `content`. Dzięki temu
dotychczasowe podstrony działają bez masowej migracji i mogą być przenoszone stopniowo.

## Pierwszy zestaw bloków

- sekcja,
- układ dwóch kolumn,
- tekst,
- obraz,
- przycisk,
- separator.

Kolejne typy komponentów Shopro (produkt, plugin, galeria, plik i formularz) powinny
odwoływać się do identyfikatorów encji/API, a nie zapisywać wyrenderowany fragment modułu
w danych projektu.

## Bezpieczeństwo

HTML i CSS może zapisywać wyłącznie administrator. Przed udostępnieniem edycji innym
rolom należy dodać politykę dozwolonych komponentów/atrybutów i sanitizację wyniku.

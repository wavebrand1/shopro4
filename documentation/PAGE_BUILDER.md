# Shopro Component Builder

Edytor stron nie zapisuje swobodnego HTML ani CSS. Programista przygotowuje komponent,
jego formularz administracyjny i szablon Twig, a redaktor tworzy instancje komponentu,
zmienia tylko dozwolone pola oraz ustala ich kolejność.

## Model edycji

Wszystkie strony korzystają z jednego buildera komponentów zapisanego jako JSON w
cms_page.builder_data. Komponent Edytor tekstu korzysta z lokalnego Quill 2 i obsługuje zwykłe podstrony i treści
blogowe. Nowa podstrona otrzymuje automatycznie sekcję pełnej szerokości z tym
komponentem. Dawna treść rich_text jest przy pierwszej edycji opakowywana w taki sam
komponent, dlatego istniejące strony zachowują zawartość.

Quill jest instalowany lokalnie przez AssetMapper, nie wymaga konta, klucza API ani
połączenia z usługą chmurową.

Preset strony głównej tworzy osobną, pełnoszeroką sekcję 100% dla każdego
komponentu. Takie sekcje są podczas renderowania przezroczyste, ponieważ Hero,
Pasek marek, Karty funkcji, Jak to działa, Dla kogo i CTA posiadają własne
wewnętrzne kontenery i gridy odpowiadające wzorcowej stronie głównej. Wrapper
sekcji jest renderowany tylko dla układów, w których ma znaczenie: sekcji w
gridzie strony albo sekcji zawierających więcej niż jedną kolumnę.
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

## Komponenty strony głównej

Builder udostępnia kompletny zestaw sekcji aktualnej strony startowej:

- Hero — nagłówek, opis, dwa przyciski, informacje zaufania i podpisy podglądu,
- Pasek marek — opis i powtarzalne nazwy marek,
- Karty funkcji — nagłówek sekcji i powtarzalne karty,
- Jak to działa — tekst, podpis ilustracji i powtarzalne kroki,
- Dla kogo — nagłówek i powtarzalne grupy odbiorców,
- Wezwanie do działania — treść i dwa linki końcowe.

Biblioteka zawiera również ogólny komponent Obraz. Redaktor wybiera plik bezpośrednio
z Menedżera plików, uzupełnia tekst alternatywny i opcjonalny podpis oraz ustawia
proporcje, dopasowanie i priorytet ładowania. Wybór pliku odbywa się przez bezpieczny
komunikat same-origin do okna edytora. Obrazy przesłane przez Menedżer plików są od
razu optymalizowane, a front renderuje dostępne warianty AVIF/WebP przez
`shopro_picture()` z wymiarami, lazy loadingiem i asynchronicznym dekodowaniem.
Wartość `sizes` jest wyliczana z udziału kolumny: na urządzeniach mobilnych obraz
zajmuje szerokość ekranu, a na desktopie przeglądarka uwzględnia procent kolumny
i maksymalną szerokość grida 1180 px. Redaktor nadal wybiera jeden obraz źródłowy;
właściwy plik z `srcset` wybiera przeglądarka. Wygenerowane techniczne warianty
rozmiarów są ukryte w Menedżerze plików. Zmiana nazwy obrazu
usuwa stare warianty i generuje je pod nową nazwą, a usunięcie oryginału sprząta
również wszystkie odpowiadające mu pliki AVIF/WebP.

Przycisk „Cała strona główna” tworzy wszystkie powyższe sekcje w prawidłowej
kolejności i wypełnia je treścią odpowiadającą bazowemu szablonowi.

## Dodawanie komponentów dla klienta

Nowy typ wymaga:

1. definicji formularza i wartości początkowych w assets/component-builder.js,
2. szablonu w templates/cms/block/,
3. jawnego dopuszczenia typu w templates/cms/page/show.html.twig,
4. stylów publicznych i administracyjnych,
5. testu renderowania.

Nie należy zapisywać w JSON haseł, sekretów ani wykonywalnego JavaScriptu.
Adresy linków komponentu są renderowane tylko dla dozwolonych protokołów.

## Sekcje i kolumny

Najwyższym elementem projektu jest layout_section. Sekcja ma ustawienie szerokości:
ograniczenie do grida witryny albo pełna szerokość ekranu. W sekcji można dynamicznie
dodawać i usuwać kolumny, a każda kolumna ma edytowalny udział szerokości.

Komponent jest dodawany do aktualnie zaznaczonej kolumny. Kolumny zawierające
komponenty trzeba najpierw opróżnić, zanim będzie można je usunąć. Sekcje i komponenty można
przesuwać, usuwać oraz zwijać. Starsze płaskie projekty są podczas otwarcia
automatycznie opakowywane w osobne sekcje pełnej szerokości.

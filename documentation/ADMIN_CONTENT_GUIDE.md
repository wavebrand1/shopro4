# Obsługa treści w panelu Shopro 4.0

Ten dokument opisuje codzienną pracę redaktora i administratora z podstronami CMS.
Techniczny model danych i zgodność ze starym Shopro opisuje `CMS_PAGES.md`, a budowę
sekcji i komponentów — `PAGE_BUILDER.md`.

## Lista podstron

Zakładka **Zarządzanie treścią → Podstrony** pokazuje aktywne rekordy CMS. Nad tabelą
można jednocześnie:

1. wyszukać fragment tytułu albo adresu;
2. wybrać status: szkic, zaplanowana, opublikowana lub wygasła;
3. wybrać dostęp: publiczny, zalogowani, członkostwo albo tylko administrator;
4. wybrać pole i kierunek sortowania.

Przycisk **Wyczyść** przywraca domyślny widok: ostatnio aktualizowane strony jako
pierwsze. Liczba rekordów na stronie wynika z pola **Paginacja** w konfiguracji.

## Tworzenie i zapisywanie

Nowa podstrona otrzymuje sekcję z komponentem tekstowym. Pusty slug jest generowany
z tytułu przy zapisie. Dostępne są dwie akcje:

- **Zapisz podstronę** — zapisuje i wraca do listy;
- **Zapisz i kontynuuj edycję** — zapisuje i pozostawia formularz otwarty.

Podgląd otwiera niezapisaną wersję w nowej karcie. Odświeżenie podglądu jest możliwe
przez jego jednorazowy adres, bez ponownego wysłania formularza.

## Publikacja i dostęp

Checkbox publikacji udostępnia stronę natychmiast, jeżeli nie ustawiono dat. Pola
**Publikuj od** i **Publikuj do** ograniczają przedział widoczności. Poziomy dostępu:

- **Publiczny** — bez logowania;
- **Zalogowani** — dla aktywnego konta użytkownika witryny;
- **Członkostwo** — dla użytkownika należącego do co najmniej jednej przypisanej grupy;
- **Tylko administrator** — strona techniczna dostępna zgodnie z rolą systemową.

Dla dostępu członkowskiego trzeba wskazać co najmniej jedno członkostwo.

## Operacje zbiorcze

Checkbox w nagłówku tabeli zaznacza rekordy widoczne na bieżącej stronie. Po
zaznaczeniu można je opublikować, przenieść do szkiców albo do kosza. Filtry,
sortowanie i numer strony pozostają zachowane po operacji.

Strony systemowe oraz używane w menu są chronione. System wykonuje operację dla
pozostałych rekordów i informuje, ile pozycji pominął.

## Kosz

Przycisk **Kosz** otwiera usunięte podstrony. Redaktor może przywrócić podstronę;
wraca ona zawsze jako szkic. Administrator może dodatkowo usunąć ją trwale.

Trwałe usunięcie kasuje również tłumaczenia i historię i jest nieodwracalne. Zarówno
przywracanie, jak i trwałe usuwanie obsługuje zaznaczenie wielu pozycji.

## Historia zmian

Akcja **Historia** przy podstronie pokazuje zapisane rewizje, autora i zakres zmian.
Można otworzyć porównanie oraz odtworzyć wybraną wersję. Odtworzenie samo tworzy nową
rewizję, więc zachowuje ślad operacji.

Jeżeli formularz był otwarty, a inny operator w międzyczasie zapisał tę samą stronę,
Shopro zatrzyma starszy zapis. Należy wtedy odświeżyć stronę i ponownie nanieść zmianę
na aktualnej wersji.

## Tłumaczenia

Język bazowy edytuje się w głównym formularzu. Akcja **Tłumaczenia** prowadzi do
wersji dodatkowych. Można skopiować układ komponentów z języka bazowego, a następnie
przetłumaczyć treść, tytuł, SEO i slug. Publiczny adres wersji językowej ma format
`/{locale}/{przetłumaczony-slug}`.

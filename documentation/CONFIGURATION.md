# Konfiguracja Shopro 4.0

Formularz został odwzorowany na podstawie `stare/Wersja_2_00/Develop/admin/config.php` oraz zapisu ustawień w `stare/Wersja_2_00/Develop/lib/class_core.php::processConfig()`. Zachowano istniejące klucze tam, gdzie ich znaczenie nadal jest aktualne, ale sposób wykonania dostosowano do Symfony 7.4.

## Ustawienia wykonywane przez system

- `theme`, `theme_variant`, `admin_theme`, `admin_theme_variant` wybierają niezależnie szablon frontu, panelu oraz ich wariant kolorystyczny. Rejestr dostępnych opcji znajduje się w `src/Settings/Application/FrontThemeRegistry.php`.
- formaty daty, czasu, locale i strefa czasowa są obsługiwane przez `src/Settings/Presentation/Twig/SettingsExtension.php`;
- widoczność logowania, wyszukiwarki, breadcrumbs, języka i zgody cookies jest respektowana przez szablony frontu;
- tryb konserwacji zwraca publicznie HTTP 503, pozostawiając dostęp do panelu i wypisu z newslettera;
- `per_page` steruje paginacją list panelu;
- limity prób logowania i czas blokady są egzekwowane dla pary login+IP oraz globalnie dla IP;
- SMTP jest pojedynczym transportem, a hasło jest szyfrowane przy użyciu `APP_SECRET`;
- klucze API są generowane per użytkownik, zapisywane wyłącznie jako SHA-256 i mają osobne zakresy uprawnień.
- zakresy tokenów API są egzekwowane przez każdy endpoint; odczyt `/api/v1/me` wymaga zakresu `read`;
- aktualnie zalogowane oraz ostatnie aktywne konto administratora nie może zostać wyłączone.
- odpowiedzi mają globalnie `nosniff`, ochronę przed osadzaniem w obcej ramce,
  bezpieczną politykę referrera i wyłączone zbędne API przeglądarki; HTTPS otrzymuje HSTS;
- panel administracyjny i API używają `Cache-Control: private, no-store`, aby dane
  uwierzytelnione nie pozostawały w pamięci współdzielonych proxy ani przeglądarki.

## Role panelu administracyjnego

- `ROLE_ADMIN` ma pełny dostęp, w tym do użytkowników, konfiguracji, języków,
  newslettera, szablonów e-mail i logów;
- `ROLE_EDITOR` zarządza treścią, menu, tłumaczeniami podstron i plikami;
- administrator dziedziczy uprawnienia redaktora, a migracja zachowuje rolę
  administratora wszystkim istniejącym kontom;
- nie można zdegradować siebie ani ostatniego aktywnego administratora.
- konta przechowują datę utworzenia i ostatniego poprawnego logowania; udane oraz
  nieudane próby logowania trafiają do dziennika audytowego wraz z adresem IP.
- odzyskiwanie hasła używa jednorazowego tokenu ważnego przez godzinę; w bazie
  zapisywany jest tylko SHA-256 tokenu, a formularz nie ujawnia istnienia konta.

## Obrazy

Stałe rozmiary miniatur ze starego systemu zastąpił zestaw responsywnych szerokości. `app:images:optimize` generuje AVIF/WebP, a funkcja Twig `shopro_picture()` tworzy `picture/srcset`, dopisuje naturalne `width` i `height`, `decoding=async`, lazy loading poza pierwszym ekranem i `fetchpriority=high` dla obrazu LCP. Polecenie jest częścią `bin/deploy-dev`.

## E-mail i newsletter

Kampania tworzy osobną dostawę dla każdego zapisanego odbiorcy i przekazuje ją do Symfony Messenger. Historia przechowuje status, czas wysłania i błąd; nieudane wiadomości mają retry i failed transport. Każdy newsletter zawiera podpisany, roczny link wypisu oraz nagłówki `List-Unsubscribe` i `List-Unsubscribe-Post`.

Odbiorców można wskazać z kont, wpisać ręcznie albo zaimportować z CSV do 2 MB.
Importer rozpoznaje przecinek, średnik i tabulator, wyszukuje adresy we wszystkich
kolumnach, normalizuje wielkość liter, usuwa duplikaty i przyjmuje maksymalnie
10 000 unikalnych poprawnych adresów.

## Decyzje modułowe

- waluta pozostaje przejściowo w konfiguracji dla zgodności, ale docelowo należy do ustawień języka;
- adresy social media są globalnym źródłem danych, natomiast widżety i komponenty social media powinny być dostarczane przez opcjonalny moduł Page Buildera;
- rejestracja dotyczy przyszłych kont użytkowników witryny. Nie jest łączona z kontami administratorów, ponieważ nadanie publicznie rejestrowanemu kontu `ROLE_ADMIN` byłoby błędem bezpieczeństwa;
- dowolny JavaScript analityczny i globalny klucz API ze starego systemu zostały celowo usunięte. System przyjmuje bezpieczny identyfikator GA4 i uruchamia go zgodnie z wyborem zgody użytkownika.

## Tłumaczenia interfejsu

Systemowy katalog fraz znajduje się w `src/Language/Application/SystemTranslationCatalog.php`. Każda nowa etykieta, akcja lub wiadomość interfejsu musi od razu otrzymać polską i angielską wersję w tym katalogu oraz być wyświetlana w Twig przez `shopro_trans('klucz')`.

Polecenie `app:translations:sync`, uruchamiane automatycznie przez `bin/deploy-dev`, zakłada brakujące języki Polski i English oraz dopisuje brakujące frazy. Nie nadpisuje tłumaczeń zmodyfikowanych ręcznie w panelu administracyjnym. Test `SystemTranslationCatalogTest` pilnuje, aby żadna fraza systemowa nie została dodana bez obu wersji językowych.

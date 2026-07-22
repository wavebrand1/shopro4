# Architektura modułów Shopro 4.0

## Rozszerzenia Page Buildera

Provider oznaczony tagiem `shopro.page_builder_component_provider` deklaruje
komponenty należące do danego modułu. Centralny rejestr sprawdza unikalność ich
typów oraz obecność właściciela w `ModuleRegistry`. Biblioteka edytora strony
bazowej i tłumaczenia korzysta z jednej listy filtrowanej przez `ModuleRuntime`.
Nieaktywność modułu ukrywa możliwość dodawania nowych instancji, ale nie kasuje
wcześniej zapisanych bloków ani ich konfiguracji.

## Rejestr dostarczany przez kod

Każdy obszar funkcjonalny implementuje `ModuleDefinition`. Definicja ma stabilny
kod, wersję, kategorię, nazwę i opis interfejsu oraz informację, czy moduł jest
wymagany. Symfony automatycznie oznacza implementacje tagiem `shopro.module`,
a `ModuleRegistry` odrzuca zduplikowane lub nieprawidłowe kody, błędne wersje,
brakujące zależności, niezgodne wymagania SemVer i cykle zależności podczas
budowania kontenera. `dependencyVersions()` musi wskazać ograniczenie w składni
Composer dla każdej pozycji zwracanej przez `dependencies()`, np. `^4.0`.

Aktualny fundament rejestruje wyłącznie działające obszary: CMS, użytkowników,
języki, media, newsletter i konfigurację. Są to moduły systemowe. Nie można ich
wyłączyć z panelu, ponieważ inne działające funkcje mają do nich zależności.

## Stan instalacji

Tabela `installed_module` przechowuje kod, zainstalowaną wersję, stan aktywności
oraz daty instalacji i ostatniej synchronizacji. Po migracjach `bin/deploy-dev`
wykonuje:

```bash
php bin/console app:modules:sync
```

Polecenie dopisuje nowe definicje i aktualizuje ich wersje. Nie usuwa rekordów,
których nie ma w bieżącym katalogu, dzięki czemu samo wycofanie kodu nie kasuje
informacji ani danych klienta.

Administrator widzi stan pod adresem `/admin/modules`. Editor nie ma dostępu do
rejestru instalacji.

`ModuleLifecyclePolicy` stanowi obowiązkową bramkę przyszłych operacji włączania
i wyłączania. Blokuje wyłączenie modułu systemowego, zależności używanej przez
aktywny moduł oraz modułu wykonującego pracę w tle. Ponowne włączenie wymaga
aktywnych wszystkich zależności. Samo przełączenie stanu aktualizuje znacznik
czasu, ale nie usuwa tabel ani danych modułu.
Wszystkie zmiany stanu wykonuje `ModuleLifecycleManager`. Rozszerzalne czujniki
`ModuleActivityProbe` zgłaszają pracę w tle; pierwszy czujnik sprawdza oczekujące
dostarczenia newslettera. Operacje PA używają POST, CSRF i trafiają do audytu,
a wyłączenie jest oznaczane jako zdarzenie ważne.
Kontrolery modułu oznacza się atrybutem `#[RequiresModule('kod')]`. Subskrybent
runtime zwraca 404 dla wyłączonego modułu, dzięki czemu jego endpointów nie można
wywołać ręcznie. Twig udostępnia `shopro_module_enabled()`, używane do ukrywania
odnośników PA i przyszłych komponentów Page Buildera. Brak wpisu instalacji jest
bezpieczny podczas pierwszego wdrożenia: moduł systemowy pozostaje dostępny,
natomiast opcjonalny pozostaje wyłączony do synchronizacji.

## Kontrakt dla przyszłych modułów opcjonalnych

Moduł opcjonalny powinien otrzymać własny katalog domenowy w `src`, definicję
`ModuleDefinition`, migracje, tłumaczenia PL/EN, testy oraz jawnie opisane
zależności od innych modułów. Instalacja i aktualizacja schematu muszą odbywać
się migracjami. Wyłączenie modułu może ukryć trasy i zadania, ale nie może usuwać
jego tabel ani danych. Deinstalacja danych będzie osobną, potwierdzaną operacją,
nigdy skutkiem zwykłego wyłączenia lub braku klasy w kodzie.

Przed udostępnieniem przełączników dla modułów opcjonalnych trzeba dodać:

1. blokadę wyłączenia podczas działania kolejek i zadań cyklicznych,
2. kontrolę tras, menu PA, komponentów Page Buildera i konsumentów zdarzeń,
3. historię operacji w audycie administratora,
4. procedurę ponownego włączenia bez utraty konfiguracji.

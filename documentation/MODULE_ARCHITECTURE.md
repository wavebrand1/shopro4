# Architektura modułów Shopro 4.0

## Rejestr dostarczany przez kod

Każdy obszar funkcjonalny implementuje `ModuleDefinition`. Definicja ma stabilny
kod, wersję, kategorię, nazwę i opis interfejsu oraz informację, czy moduł jest
wymagany. Symfony automatycznie oznacza implementacje tagiem `shopro.module`,
a `ModuleRegistry` odrzuca zduplikowane lub nieprawidłowe kody, błędne wersje,
brakujące zależności i cykle zależności podczas budowania kontenera.

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

## Kontrakt dla przyszłych modułów opcjonalnych

Moduł opcjonalny powinien otrzymać własny katalog domenowy w `src`, definicję
`ModuleDefinition`, migracje, tłumaczenia PL/EN, testy oraz jawnie opisane
zależności od innych modułów. Instalacja i aktualizacja schematu muszą odbywać
się migracjami. Wyłączenie modułu może ukryć trasy i zadania, ale nie może usuwać
jego tabel ani danych. Deinstalacja danych będzie osobną, potwierdzaną operacją,
nigdy skutkiem zwykłego wyłączenia lub braku klasy w kodzie.

Przed udostępnieniem przełączników dla modułów opcjonalnych trzeba dodać:

1. reguły zgodności wersji zależności (istnienie i cykle są już walidowane),
2. blokadę wyłączenia podczas działania kolejek i zadań cyklicznych,
3. kontrolę tras, menu PA, komponentów Page Buildera i konsumentów zdarzeń,
4. historię operacji w audycie administratora,
5. procedurę ponownego włączenia bez utraty konfiguracji.

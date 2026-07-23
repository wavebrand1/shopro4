# Architektura modułów Shopro 4.0

## Rozszerzenia Page Buildera

Provider oznaczony tagiem `shopro.page_builder_component_provider` deklaruje
komponenty należące do danego modułu. Centralny rejestr sprawdza unikalność ich
typów oraz obecność właściciela w `ModuleRegistry`. Biblioteka edytora strony
bazowej i tłumaczenia korzysta z jednej listy filtrowanej przez `ModuleRuntime`.
Nieaktywność modułu ukrywa możliwość dodawania nowych instancji, ale nie kasuje
wcześniej zapisanych bloków ani ich konfiguracji. Wspólny renderer bloków również
sprawdza właściciela przed wykonaniem komponentu; dotyczy to bloków głównych,
zagnieżdżonych kolumn, podglądu i strony błędu.

Procesy asynchroniczne korzystają z analogicznej bramki. Wiadomość należąca do
modułu implementuje `ModuleAwareMessage`, a `ModuleRuntimeMiddleware` sprawdza jej
właściciela zarówno podczas wysłania na magistralę, jak i obsługi przez workera.
Jeżeli moduł jest nieaktywny, handler nie zostaje uruchomiony, a Messenger zachowuje
wiadomość do ponowienia. Newsletter deklaruje ten kontrakt dla każdego dostarczenia.
Normalny panel nadal nie pozwala wyłączyć modułu z oczekującymi zadaniami; middleware
jest drugą warstwą ochrony przed wykonaniem kodu po zmianie stanu poza tym procesem.

Polecenia konsolowe modułów są oznaczane tym samym atrybutem `RequiresModule` co
kontrolery HTTP. Subskrybent `console.command` zatrzymuje polecenie przed metodą
`execute()`, wypisuje kod nieaktywnego modułu i zwraca standardowy kod Symfony dla
pominiętej komendy. Ochroną są objęte obecnie synchronizacja języków, optymalizacja
obrazów oraz administracyjne komendy kont. `app:modules:sync` i kopia bazy są
celowo niezależne od modułów, aby zachować narzędzia wdrożeniowe i ratunkowe.

Listenery uruchamiane automatycznie nie mogą omijać tych bramek. Subskrybenci
konserwacji, kontekstu języka, przekierowań CMS, publicznej strony 404 i historii
logowania sprawdzają `ModuleAvailability` przed pierwszym zapytaniem lub skutkiem
ubocznym. Wyłączenie właściciela oznacza więc brak działania listenera. Audyt
administracyjny i nagłówki bezpieczeństwa pozostają niezależnym fundamentem i są
wykonywane bez względu na aktywność modułów.

Warstwa HTTP ma jawne pokrycie właścicielami. Wszystkie kontrolery CMS, kont
witryny i panelu, języków, mediów, konfiguracji oraz szablonów e-mail używają
klasowego `RequiresModule`. Bramka działa przed wywołaniem akcji, więc ręczne
wejście pod znany URL kończy się odpowiedzią 404 bez uruchomienia kontrolera.
Poza modułami pozostają celowo: logowanie administratora, rejestr modułów,
health-check i audyt. Zapewnia to ścieżkę diagnostyki oraz odzyskania systemu bez
udostępniania wyłączonych funkcji biznesowych.

Pulpit i nawigacja PA także respektują runtime. Kontroler pulpitu nie odpytuje
repozytoriów użytkowników, języków, newslettera ani szablonów e-mail, gdy ich moduł
jest nieaktywny. Odpowiadające karty są wtedy pomijane. Boczne menu ukrywa grupy i
linki poszczególnych modułów, dzięki czemu interfejs nie prowadzi do tras kończących
się kontrolowanym 404. Dane i tabele nadal pozostają nietknięte.

Globalne funkcje Twig również stanowią granicę modułu. `shopro_menu()` zwraca pustą
nawigację bez odpytywania CMS, funkcje językowe nie pobierają encji języków, a
`shopro_setting()` korzysta ze statycznych wartości domyślnych, jeżeli właściciel
jest nieaktywny. Tłumaczenia interfejsu fundamentu pozostają dostępne z katalogu
wbudowanego w kod. Renderer obrazów nie czyta plików ani konfiguracji po wyłączeniu
modułu Media. Dzięki temu szablony logów, rejestru modułów i innych ekranów
ratunkowych mogą być renderowane bez pośredniego uruchamiania usług biznesowych.

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

Synchronizacja całego rejestru odbywa się w jednej transakcji Doctrine i kończy
jednym zatwierdzeniem. Błąd dowolnego wpisu wycofuje wszystkie zmiany, a komunikaty
o zsynchronizowanych wersjach są wypisywane dopiero po powodzeniu transakcji. Nie
może więc powstać pozornie poprawny, częściowo zaktualizowany rejestr modułów.
Nowy moduł opcjonalny otrzymuje przy pierwszej synchronizacji stan wyłączony i musi
zostać świadomie uruchomiony w PA; kolejne synchronizacje zachowują wybrany stan.

Po synchronizacji `bin/deploy-dev` uruchamia `app:modules:verify`. Komenda sprawdza
obecność rekordów, zgodność wersji, aktywność modułów wymaganych oraz dostępność
łańcuchów zależności. Osierocone rekordy raportuje jako informację i zachowuje.
Niespójność zwraca kod błędu, dlatego wdrożenie Pleska nie zostanie oznaczone jako
udane, dopóki rejestr nie odpowiada faktycznie uruchamianemu kodowi.

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

Runtime sprawdza nie tylko stan żądanego modułu, ale rekurencyjnie także wszystkie
jego zależności. Moduł z aktywnym rekordem, którego zależność została wyłączona z
pominięciem `ModuleLifecycleManager` (np. ręczną zmianą w bazie), jest traktowany
jak nieaktywny. Jest to zachowanie fail-closed: kod biznesowy nie startuje, a rekordy
i dane obu modułów pozostają zachowane do naprawienia konfiguracji.

Ta sama bramka porównuje wersję rekordu instalacji z wersją kodu oraz sprawdza
ograniczenia SemVer wszystkich zależności. Częściowe wdrożenie albo pominięcie
`app:modules:sync` nie uruchomi więc modułu na niezgodnym schemacie lub API. Rejestr
PA pokazuje wtedy status „Wymaga synchronizacji”, zamiast mylącego „Włączony”.

Przed udostępnieniem przełączników dla modułów opcjonalnych trzeba dodać:

1. blokadę wyłączenia podczas działania kolejek i zadań cyklicznych,
2. kontrolę tras, menu PA, komponentów Page Buildera i konsumentów zdarzeń,
3. historię operacji w audycie administratora,
4. procedurę ponownego włączenia bez utraty konfiguracji.

# Instalator Shopro 4.0

Shopro można wdrożyć na nowy serwer bez ręcznego wykonywania migracji, tworzenia
administratora i uruchamiania komend synchronizacyjnych. Paczka instalacyjna
zawiera zależności produkcyjne oraz skompilowane zasoby.

## Przygotowanie paczki

### Utworzenie paczki w GitHub Actions

1. Zaloguj się do GitHub i otwórz repozytorium
   `wavebrand1/shopro4`.
2. W górnym menu repozytorium wybierz **Actions**.
3. Z listy po lewej stronie wybierz workflow
   **Build installation package**.
4. Kliknij **Run workflow**.
5. Pozostaw gałąź `main`, a w polu wersji wpisz numer wydania, np. `4.0.0`.
6. Ponownie kliknij zielony przycisk **Run workflow**.
7. Odśwież listę i otwórz rozpoczęte wykonanie. Zaczekaj, aż zadanie
   `package` otrzyma zielony status.
8. Na dole strony, w sekcji **Artifacts**, kliknij
   `shopro-installation-package`.

GitHub pobierze na komputer plik `shopro-installation-package.zip`. Jest to
opakowanie artefaktu. Po jego rozpakowaniu znajduje się w nim właściwa paczka
systemu, np. `shopro-4.0.0.zip`. Na serwer należy wysłać właśnie plik
`shopro-4.0.0.zip`, a następnie rozpakować jego zawartość.

Artefakt jest przechowywany przez 30 dni. Paczka powstaje również automatycznie
po wysłaniu tagu zaczynającego się od `v`, np. `v4.0.0`.

### Wysłanie paczki na serwer przez Plesk

1. Zaloguj się do Pleska serwera docelowego.
2. Otwórz **Domeny**, wybierz domenę i przejdź do **Menedżera plików**.
3. Otwórz katalog aplikacji, zwykle `httpdocs`.
4. Jeżeli jest to nowa domena, usuń wyłącznie domyślny plik
   `index.html` utworzony przez Plesk.
5. Kliknij **Prześlij**, wybierz `shopro-4.0.0.zip` i poczekaj na zakończenie
   wysyłania.
6. Zaznacz przesłany plik, wybierz **Wyodrębnij pliki** i jako miejsce
   docelowe wskaż bieżący katalog `httpdocs`.
7. Sprawdź, czy bezpośrednio w `httpdocs` znajdują się m.in. katalogi
   `bin`, `config`, `public`, `src`, `templates`, `vendor` oraz plik
   `composer.json`. Nie mogą znajdować się w dodatkowym podkatalogu
   `shopro-4.0.0`.
8. Po prawidłowym rozpakowaniu usuń z serwera przesłany plik ZIP.
9. W ustawieniach hostingu domeny ustaw **Document root** na
   `httpdocs/public`.
10. Ustaw PHP 8.2 lub nowsze, włącz HTTPS i przejdź do
    `https://twoja-domena.pl/install`.

Gotowa paczka ma domyślnie ustawione `APP_ENV=prod`, ponieważ nie zawiera
narzędzi deweloperskich takich jak Symfony `DebugBundle`. Nie ustawiaj w Plesku
zmiennej środowiskowej `APP_ENV=dev`. Zmienne serwera mają pierwszeństwo przed
plikami `.env` i wymusiłyby uruchomienie nieobecnych pakietów deweloperskich.

Nie rozpakowuj na serwerze zewnętrznego pliku
`shopro-installation-package.zip` bez sprawdzenia jego zawartości. Zawiera on
jeszcze właściwy plik ZIP, a nie bezpośrednio pliki Shopro.

### Alternatywnie: wysłanie przez SFTP

1. Rozpakuj na komputerze `shopro-installation-package.zip`.
2. Połącz się z serwerem w programie obsługującym SFTP, np. WinSCP lub
   FileZilla.
3. Przejdź do katalogu domeny `httpdocs`.
4. Prześlij `shopro-4.0.0.zip`.
5. Rozpakuj go w Plesku albo w terminalu SSH:

```bash
cd ~/httpdocs
unzip shopro-4.0.0.zip
rm shopro-4.0.0.zip
```

Polecenie `rm` wykonuj dopiero po sprawdzeniu, że archiwum zostało poprawnie
rozpakowane i w katalogu znajdują się pliki Shopro.

Lokalnie paczkę można zbudować poleceniem:

```bash
bash bin/build-release-package 4.0.0
```

Gotowy plik znajduje się w `dist/shopro-4.0.0.zip`.

## Instalacja na nowym serwerze

1. Utwórz pustą bazę MySQL 8 lub MariaDB 10.6+ i jej użytkownika.
2. Wgraj i rozpakuj zawartość ZIP-a w katalogu aplikacji.
3. Ustaw document root domeny na katalog `public`, np. `/httpdocs/public`.
4. Włącz HTTPS i otwórz `https://twoja-domena.pl/install`.
5. Przejdź przez kontrolę serwera, konfigurację bazy, witryny, administratora
   i opcjonalną konfigurację SMTP.

Instalator:

- testuje połączenie przed zapisaniem danych;
- tworzy `.env.local` z losowym 256-bitowym `APP_SECRET`;
- wykonuje wszystkie migracje;
- synchronizuje języki, tłumaczenia i rejestr modułów;
- tworzy wymagane strony systemowe i szablony wiadomości;
- tworzy pierwszego administratora;
- zapisuje konfigurację witryny i szyfruje hasło SMTP;
- instaluje i kompiluje zasoby;
- optymalizuje obrazy;
- próbuje dodać worker kolejki do crona.

Jeśli hosting nie pozwala zarządzać crontabem, ekran końcowy pokazuje gotowe
polecenie do dodania w Plesku jako zadanie wykonywane co minutę.

## Zabezpieczenie instalatora

Po sukcesie powstaje `var/install.lock`. Każda kolejna próba wejścia na
`/install` zwraca 404. Plik zawiera tylko datę instalacji, wersję PHP i publiczny
adres witryny — bez haseł i sekretów.

Ponownej instalacji nie należy uruchamiać na działającej witrynie. Samo usunięcie
blokady nie czyści bazy. Do odtworzenia systemu należy używać kopii bezpieczeństwa
i osobnej, pustej bazy.

## Wymagania

- PHP 8.2 lub nowszy;
- PDO MySQL i OpenSSL;
- MySQL 8 albo MariaDB 10.6+;
- zapis do `.env.local`, `var` i `public/uploads`;
- document root wskazujący na `public`;
- Intl oraz Imagick/GD są zalecane.

Composer nie jest wymagany na serwerze, jeżeli używana jest gotowa paczka ZIP.

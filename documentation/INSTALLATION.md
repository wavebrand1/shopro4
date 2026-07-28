# Instalator Shopro 4.0

Shopro można wdrożyć na nowy serwer bez ręcznego wykonywania migracji, tworzenia
administratora i uruchamiania komend synchronizacyjnych. Paczka instalacyjna
zawiera zależności produkcyjne oraz skompilowane zasoby.

## Przygotowanie paczki

W GitHub Actions wybierz workflow **Build installation package**, użyj
**Run workflow** i podaj numer wersji. Po zakończeniu pobierz artefakt
`shopro-installation-package`.

Paczka powstaje również automatycznie po wysłaniu tagu `v*`. Lokalnie można ją
zbudować poleceniem:

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

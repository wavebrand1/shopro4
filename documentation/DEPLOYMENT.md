# Wdrożenie środowiska developerskiego

## Przepływ

```text
push do GitHub/main
→ webhook GitHub
→ integracja Git w Plesku pobiera origin/main
→ automatyczne wdrożenie do /httpdocs
→ skrypt bin/deploy-dev
→ zależności, migracje, assety i cache
```

Document root domeny `shopro4.orangestudio.pl` wskazuje na `/httpdocs/public`.
Repozytorium jest prywatne, a Plesk pobiera je przez dedykowany deploy key
z uprawnieniem tylko do odczytu.

## Polecenie wykonywane przez Plesk po wdrożeniu

W polu dodatkowych działań wdrożeniowych jest skonfigurowane jedno polecenie:

```bash
bash bin/deploy-dev
```

Skrypt wykonuje kolejno:

1. instalację zależności Composer,
2. kopię bezpieczeństwa, jeżeli oczekują nowe migracje,
3. migracje i walidację mapowania bazy danych,
4. synchronizację tłumaczeń oraz modułów,
5. kontrolę spójności wersji, stanów i zależności modułów,
6. instalację i kompilację zasobów przez AssetMapper,
7. optymalizację responsywnych obrazów i czyszczenie cache,
8. instalację crona kolejki oraz próbę obsłużenia oczekujących wiadomości.

Kontrolę modułów można uruchomić również ręcznie:

```bash
APP_ENV=dev APP_DEBUG=1 php bin/console app:modules:verify
```

Kod wyjścia `1` oznacza brak synchronizacji, wyłączony moduł wymagany albo
niedostępną zależność. Osierocony rekord nie blokuje wdrożenia, ponieważ jego dane
muszą pozostać dostępne do diagnostyki i ewentualnego przywrócenia kodu modułu.

Skrypt generuje również responsywne warianty AVIF/WebP plików znajdujących się
w `public/uploads`. Nowe integracje przesyłania obrazów powinny po zapisie pliku
wywoływać `ResponsiveImageOptimizer`; polecenie `app:images:optimize` pozostaje
bezpiecznym mechanizmem uzupełniającym dla plików wgranych wcześniej.

## Kolejka wiadomości

Newsletter korzysta z trwałej kolejki Doctrine. W Plesku należy dodać zadanie
cykliczne uruchamiane co minutę:

```bash
cd /var/www/vhosts/shopro4.orangestudio.pl/httpdocs && APP_ENV=dev APP_DEBUG=1 /opt/plesk/php/8.3/bin/php bin/console messenger:consume async --time-limit=50 --limit=100 --memory-limit=128M
```

Nie należy uruchamiać kilku nakładających się zadań. Messenger ponawia
przejściowe błędy trzy razy, a niedostarczone zadania przenosi do kolejki
`failed`. Stan każdej wiadomości jest równolegle widoczny w historii newslettera.

Skrypt `bin/run-queue-worker` zabezpiecza wykonanie blokadą `flock` i po każdym
uruchomieniu zapisuje atomowo heartbeat do `var/queue-worker-heartbeat.json`.
Ekran newslettera w panelu administracyjnym pokazuje ostatnią aktywność workera
oraz liczbę wiadomości w kolejkach `async` i `failed`. Heartbeat starszy niż
trzy minuty jest uznawany za nieaktualny. Oczekujące wiadomości wraz z
nieaktywnym workerem oznaczają błąd wymagający sprawdzenia zadania cyklicznego
i pliku `var/log/messenger-cron.log`.
Po obsłużeniu ponownej wysyłki system usuwa odpowiadający jej stary wpis z
transportu `failed`. Wdrożenie dodatkowo uruchamia
`app:newsletter:reconcile-failed`, które usuwa wpisy dotyczące dostarczeń już
oznaczonych jako wysłane, bez naruszania nadal nierozwiązanych błędów.
Tę samą operację administrator może uruchomić przyciskiem `Uzgodnij kolejkę`
na ekranie Newslettera; akcja jest zabezpieczona tokenem CSRF.
Nadal nierozwiązane wpisy są wyświetlane poniżej stanu kolejki wraz z typem
wiadomości, przyczyną oraz liczbą prób. Administrator może pojedynczy wpis
ponowić albo świadomie usunąć. Obie operacje wymagają tokenu CSRF, a usunięcie
jest dodatkowo potwierdzane w interfejsie.
Uzgodnienie, ponowienie i usunięcie wiadomości są zapisywane jako ważne
zdarzenia w Logach wraz z operatorem, wynikiem i identyfikatorem technicznym.

Cache jest czyszczony po kompilacji, aby Symfony odczytało najnowszy manifest
assetów i nie podawało przeglądarce poprzednich adresów CSS.

## Kontrola

- GitHub Actions wykonuje walidację Composera, kontenera i testy.
- Plesk pokazuje ostatni pobrany commit i wynik wdrożenia.
- Niepowodzenie któregokolwiek polecenia przerywa skrypt z kodem błędu.
- `/health` sprawdza, czy proces aplikacji odpowiada, bez zależności od bazy.
- `/health/ready` sprawdza bazę, spójność rejestru modułów i kolejkę; zwraca
  HTTP 503, gdy magazyn kolejki jest niedostępny albo wiadomości oczekują,
  a worker nie ma aktualnego heartbeat. Sam brak heartbeat przy pustej kolejce
  nie uruchamia alarmu i nie blokuje ruchu.
- Cała domena developerska jest chroniona uwierzytelnianiem HTTP w Plesku.
- Sekrety oraz `.env.local` pozostają poza repozytorium.

## Automatyczna kopia przed migracją

Jeśli są dostępne nowe migracje, `bin/deploy-dev` przed ich wykonaniem uruchamia
`bin/create-backup`. Wdrożenie bez zmian schematu nie powiela dużych archiwów.
Kopia trafia poza katalog publiczny do `var/backups/shopro-RRRRMMDD-GGMMSS.tar.gz`
i zawiera skompresowany zrzut MariaDB oraz `public/uploads`. Obok archiwum
zapisywana jest suma SHA-256. Jeśli `mariadb-dump`/`mysqldump` jest niedostępny
albo zrzut się nie powiedzie, wdrożenie zostaje przerwane przed zmianą schematu.

Domyślna retencja wynosi 14 dni. Zmienna `SHOPRO_BACKUP_RETENTION_DAYS` ustawia
inną liczbę dni, a `SHOPRO_BACKUP_DIR` pozwala wskazać katalog na osobnym
woluminie. Backup Pleska powinien dodatkowo kopiować ten katalog poza serwer.

Ręczne utworzenie kopii:

```bash
cd /var/www/vhosts/shopro4.orangestudio.pl/httpdocs
APP_ENV=dev APP_DEBUG=1 bash bin/create-backup
```

Przywracanie należy najpierw przećwiczyć na osobnej bazie: zweryfikować plik
`.sha256`, rozpakować archiwum, zaimportować `database.sql.gz` i odtworzyć
`public/uploads`. Bieżącej bazy nie wolno nadpisywać bez dodatkowej kopii.

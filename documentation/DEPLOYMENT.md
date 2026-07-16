# Wdrożenie środowiska developerskiego

## Przepływ

```text
push do GitHub/main
→ webhook GitHub
→ integracja Git w Plesku pobiera origin/main
→ automatyczne wdrożenie do /httpdocs
→ composer install
→ cache:clear w środowisku dev
```

Document root domeny `shopro4.orangestudio.pl` wskazuje na `/httpdocs/public`. Repozytorium jest prywatne, a Plesk pobiera je przez dedykowany deploy key z uprawnieniem tylko do odczytu.

## Polecenia wykonywane przez Plesk po wdrożeniu

```bash
cd /var/www/vhosts/shopro4.orangestudio.pl/httpdocs
composer install --prefer-dist --no-interaction --no-progress
APP_ENV=dev APP_DEBUG=1 php bin/console cache:clear
APP_ENV=dev APP_DEBUG=1 php bin/console assets:install public
APP_ENV=dev APP_DEBUG=1 php bin/console asset-map:compile
```

## Kontrola

- GitHub Actions wykonuje walidację Composera, kontenera i testy.
- Plesk pokazuje ostatni pobrany commit i wynik wdrożenia.
- AssetMapper kompiluje CSS/JS po każdym wdrożeniu, dlatego zmiany zasobów nie wymagają ręcznych poleceń na serwerze.
- Środowisko jest developerskie i przed rozpoczęciem pracy z danymi musi zostać zabezpieczone przed dostępem publicznym.
- Cała domena jest chroniona uwierzytelnianiem HTTP skonfigurowanym w Plesku.
- Sekrety i konfiguracja serwera pozostają poza repozytorium.

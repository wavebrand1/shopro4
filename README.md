# Shopro 4.0

Nowa wersja Shopro oparta na Symfony 7.4 LTS.

## Wymagania

- PHP 8.2 lub nowszy (lokalnie projekt jest uruchamiany na PHP 8.3)
- Composer 2
- MySQL/MariaDB — docelowa konfiguracja zostanie ustalona po inwentaryzacji bazy legacy

## Instalacja lokalna

```bash
composer install
php bin/console about
php bin/phpunit
```

Sekretów nie zapisujemy w `.env` ani w repozytorium. Lokalne wartości należy umieszczać w `.env.local`, a produkcyjne w konfiguracji hostingu lub w sekretach procesu wdrożenia.

## Struktura wdrożenia

Cała aplikacja jest umieszczana poza publicznym katalogiem WWW. Document root domeny musi wskazywać na katalog `public/` projektu, np.:

```text
/home/shopro4/app/            <- REMOTE_PATH
/home/shopro4/app/public/     <- document root domeny
```

Przed pierwszym wdrożeniem trzeba potwierdzić na serwerze:

- wersję PHP i wymagane rozszerzenia,
- zdalny katalog aplikacji,
- document root domeny,
- możliwość uruchamiania Composera/komend Symfony albo konieczność wysyłania `vendor/`,
- sposób utrzymania katalogów współdzielonych (`.env.local`, `var/`, uploady).

## CI i wdrożenia

`.github/workflows/ci.yml` sprawdza aplikację przy pushach i pull requestach. Szablon `.github/workflows/deploy-sftp.yml.example` jest celowo nieaktywny do czasu potwierdzenia układu hostingu. Hasło SFTP nie może znaleźć się w plikach projektu; należy je dodać jako sekret GitHub.

Dokumentacja analizy Shopro Legacy znajduje się obecnie w nadrzędnym katalogu `documentation/` i zostanie włączona do repozytorium po ustaleniu docelowej organizacji dokumentacji.

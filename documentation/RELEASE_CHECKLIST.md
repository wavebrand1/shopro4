# Shopro 4.0 — checklista pierwszego wydania

## Zakres wersji podstawowej

Pierwsze wydanie obejmuje stabilny fundament CMS:

- logowanie operatorów panelu i zarządzanie uprawnieniami;
- podstrony, tłumaczenia, rewizje, podgląd i publikację;
- Page Builder, treść formatowaną i bibliotekę mediów;
- menu z hierarchią i tłumaczeniami;
- języki, przekierowania URL i podstawowe SEO;
- użytkowników witryny, rejestrację i odzyskiwanie hasła;
- konfigurację systemu, szablony e-mail oraz newsletter z kolejką;
- logi, kopie przed migracją, diagnostykę i endpointy zdrowia.

Moduły biznesowe mogą być instalowane później. Brak modułu nie oznacza, że jego
kod lub dane ze starego Shopro można usunąć.

## Automatyczna bramka jakości

Przed wysłaniem zmian uruchom lokalnie:

```bash
bash bin/release-check --ci
```

Kontrola obejmuje metadane i aktualne podatności zależności Composer,
konfigurację Symfony, Twig, skrypty wdrożeniowe, JavaScript i pełny zestaw
testów.

`bin/deploy-dev` automatycznie uruchamia po wdrożeniu:

```bash
bash bin/release-check --runtime
```

Weryfikowane są migracje, mapowanie Doctrine, rejestr modułów, skompilowane
zasoby oraz prawa zapisu do katalogów roboczych. Błąd przerywa wdrożenie i musi
zostać naprawiony przed uznaniem wersji za gotową.

Plesk na środowisku developerskim nadal używa:

```bash
bash bin/deploy-dev
```

Po podjęciu decyzji o publicznym wydaniu akcję wdrożeniową w Plesku należy
zmienić na:

```bash
bash bin/deploy-prod
```

Wariant produkcyjny instaluje zależności bez pakietów developerskich, buduje
zoptymalizowany autoloader i uruchamia zarówno aplikację, jak i kolejkę w
`APP_ENV=prod` z wyłączonym debugowaniem.

## Konfiguracja serwera

Przed publicznym udostępnieniem:

1. Dokument root domeny wskazuje na `/httpdocs/public`.
2. HTTPS działa i wymusza bezpieczne połączenie.
3. `.env.local` zawiera prawidłowe `APP_SECRET`, `DATABASE_URL`, `MAILER_DSN`
   oraz adres aplikacji; plik nie znajduje się w Git.
4. Zadanie cykliczne kolejki jest aktywne, a panel newslettera potwierdza
   aktualną pracę workera.
5. Kopia bazy powstaje przed migracją, a procedura odtworzenia została
   przetestowana.
6. Dla publicznej wersji ustawiono `APP_ENV=prod` i `APP_DEBUG=0`.
7. Dopiero po spełnieniu punktu 6 można usunąć ochronę hasłem Pleska.

Kontrolę wymuszającą tryb publiczny można wykonać:

```bash
SHOPRO_PUBLIC_RELEASE=1 APP_ENV=prod APP_DEBUG=0 bash bin/release-check --runtime
```

## Test ręczny po wdrożeniu

Sprawdź kolejno:

1. `/health` odpowiada kodem 200, a `/health/ready` nie zgłasza blokady.
2. Strona główna i istniejąca podstrona działają w języku bazowym i dodatkowym.
3. Nieistniejący adres pokazuje stronę 404 bez debugera Symfony.
4. Operator może się zalogować, wylogować i odzyskać hasło.
5. Można utworzyć szkic, zapisać i kontynuować edycję, użyć podglądu,
   opublikować podstronę i przywrócić rewizję.
6. Obraz z biblioteki mediów wyświetla się na froncie w wariantach responsywnych.
7. Zmiana kolejności i hierarchii menu jest widoczna na froncie.
8. Testowy e-mail oraz kampania do jednego adresu trafiają do kolejki i mają
   zapis dostarczenia.
9. Formularze krytyczne odrzucają błędny CSRF i nie ujawniają danych dostępowych.

## Wycofanie nieudanego wydania

1. Włącz ochronę hasłem lub tryb konserwacji.
2. Zachowaj log wdrożenia i `var/log`.
3. Przywróć poprzedni, oznaczony commit aplikacji.
4. Odtwórz bazę z kopii utworzonej przed migracją, jeżeli migracja zmieniła
   schemat lub dane w sposób niezgodny ze starą wersją.
5. Wyczyść cache, skompiluj zasoby i uruchom `release-check --runtime`.
6. Wykonaj skrócony test ręczny przed ponownym otwarciem witryny.

Szczegóły techniczne wdrożenia znajdują się w
[DEPLOYMENT.md](DEPLOYMENT.md).

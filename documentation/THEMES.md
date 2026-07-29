# Skórki Shopro

## Zasada

Shopro Core jest niezależny od realizacji dla konkretnego klienta. Skórka opisuje
wygląd frontu, warianty kolorystyczne, zasoby oraz komponenty Page Buildera.
Obecna skórka **Shopro Modernize** pozostaje dostarczana razem z systemem i jest
ustawiona jako domyślna. Korzysta jednak z tego samego rejestru co skórki
instalowane później.

## Instalacja skóry

Skórka jest prywatnym pakietem Composer zawierającym Symfony Bundle. Pakiet
rejestruje `ThemeProvider` i – jeżeli ma własne bloki –
`ThemePageBuilderComponentProvider`. Instalacja na serwerze wygląda następująco:

```bash
composer require wavebrand/shopro-theme-nazwa-klienta
APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear
```

W Plesk wykonujemy to podczas wdrożenia/release, nigdy przez upload kodu w PA.
Po instalacji skórka pojawia się w **Konfiguracja systemu → Szablon strony**.

## Kontrakt pakietu

Provider zwraca `ThemeDefinition`: techniczny kod, nazwę, wersję, warianty z
etykietami PL/EN oraz obsługę frontu i/lub panelu administracyjnego. Dane strony,
treści bloków i wybór wariantu pozostają w bazie Shopro.

Przed odinstalowaniem skóry trzeba przełączyć witrynę na inną skórkę albo
zmigrować strony korzystające z jej bloków. Nie usuwamy kodu Modernize w trakcie
tej zmiany – chroni to strony działające na wersji 4.0.

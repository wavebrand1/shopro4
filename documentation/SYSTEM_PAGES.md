# Strony systemowe

Polecenie `app:pages:install-system` uzupełnia brakujące strony przypisane do ról
systemowych:

- stronę główną;
- stronę błędu 404;
- strefę administratora;
- stronę logowania;
- stronę aktywacji konta;
- stronę konta;
- stronę rejestracji;
- stronę wyszukiwania;
- mapę witryny;
- stronę profilu;
- regulamin.

Synchronizacja jest idempotentna i może być uruchamiana przy każdym wdrożeniu.
Nie nadpisuje istniejących stron ani treści przygotowanych przez administratora
i tworzy wyłącznie brakujące przypisania. Jeśli domyślny slug jest zajęty, system
wybiera kolejny wolny adres zamiast zmieniać istniejącą stronę.

Nowe strony otrzymują opublikowaną, bezpieczną treść startową w języku bazowym.
Dla aktywnych języków dodatkowych tworzone są wpisy tłumaczeń; katalog angielski
zawiera gotowe angielskie tytuły, treści i slugi. Treści można później zmieniać
w zwykłym edytorze podstron.

`bin/deploy-dev` uruchamia synchronizację po migracjach, synchronizacji tłumaczeń
i rejestru modułów, a przed końcową kontrolą gotowości. `bin/deploy-prod`
korzysta z tego samego procesu wdrożenia.

## Powiązanie z funkcjami

Publiczne wejście na podstronę przypisaną do roli uruchamia właściwy proces:

- strona główna jest wyświetlana pod `/` oraz pod swoim edytowalnym slugiem;
- logowanie prowadzi do bezpiecznego formularza `/login`;
- aktywacja prowadzi do formularza ponownej wysyłki linku aktywacyjnego;
- konto i profil prowadzą do chronionych funkcji użytkownika;
- rejestracja działa bezpośrednio pod końcowym adresem `/rejestracja`
  i uwzględnia konfigurację systemu; historyczny `/register` jedynie przekierowuje
  na polski adres;
- wyszukiwanie prowadzi do wyszukiwarki i zachowuje aktywny język;
- mapa witryny prowadzi do responsywnej mapy HTML; `/sitemap.xml` pozostaje
  techniczną mapą dla robotów;
- regulamin pozostaje zwykłą edytowalną podstroną;
- 404 jest używana przez globalną obsługę nieistniejących adresów;
- strona administratora zachowuje ochronę i dla gościa odpowiada jak brak strony.

Adresy tłumaczeń, np. `/en/sign-in`, uruchamiają tę samą funkcję w aktualnie
wybranym języku. Techniczne adresy procesów pozostają stabilne, dzięki czemu
formularze logowania, zabezpieczenia CSRF i firewall Symfony nie zależą od slugu
ustawionego przez administratora.

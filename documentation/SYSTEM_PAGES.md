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

## Komponent roli strony

Funkcjonalna część strony systemowej jest osadzana w Page Builderze przez komponent
`system_role`. Administrator może umieścić go w dowolnej sekcji i kolumnie, a wokół
niego dodawać zwykły tekst, obrazy oraz pozostałe komponenty. Sam komponent nie ma
konfiguracji: na stronie rejestracji zawsze renderuje bezpieczny formularz
rejestracji, a nie funkcję wybraną ręcznie przez użytkownika.
Komponent jest wymagany, nieusuwalny i może wystąpić dokładnie jeden raz.
Ograniczenie jest egzekwowane zarówno w interfejsie Page Buildera, jak i podczas
walidacji formularza po stronie serwera.

Polecenie instalacyjne dopisuje brakujący komponent do funkcjonalnych stron
systemowych i ich tłumaczeń. Operacja nie zmienia istniejących komponentów ani
treści i nie tworzy duplikatu przy kolejnych wdrożeniach. Obecnie bezpośrednie
renderowanie w układzie Page Buildera obsługuje logowanie i rejestrację; kolejne
role korzystają z tego samego kontraktu komponentu podczas podłączania ich procesów.

Adresy tłumaczeń, np. `/en/sign-in`, uruchamiają tę samą funkcję w aktualnie
wybranym języku. Techniczne adresy procesów pozostają stabilne, dzięki czemu
formularze logowania, zabezpieczenia CSRF i firewall Symfony nie zależą od slugu
ustawionego przez administratora.

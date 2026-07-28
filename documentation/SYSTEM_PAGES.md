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

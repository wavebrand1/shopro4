# Zgodność panelu administracyjnego z Shopro Legacy

Dokument jest kontraktem migracji wskazanych ekranów. Kod Legacy pozostaje
źródłem zachowania i nie jest traktowany jako zbędny.

## Użytkownicy

Źródła: `stare/Wersja_2_00/Develop/admin/users.php`, obsługa żądań w
`admin/controller.php` (`processUser`, wyszukiwanie i `deleteUser`), logika w
`lib/class_user.php`, tabela `users` w `setup/sql/structure.sql`.

Wymagana zgodność: lista i paginacja, wyszukiwanie tekstowe, filtrowanie datami,
dodawanie, edycja i usuwanie, login, hasło, imię, nazwisko, e-mail, telefon,
avatar, informacje, notatki, newsletter, członkostwo, status `y/n/b/t`, poziom
użytkownika `9/8/1`, indywidualny dostęp i szablony uprawnień. Lista pokazuje
użytkownika, e-mail, członkostwo, datę rejestracji, ostatnie logowanie i status.

Shopro 4.0 mapuje poziomy dostępu Legacy na role panelu. Administrator ma pełny
dostęp, a Redaktor zarządza podstronami, menu, ich tłumaczeniami oraz plikami.
Użytkownicy, konfiguracja, języki, newsletter, poczta i logi wymagają roli
Administratora.

## Konfiguracja systemu

Źródła: `admin/config.php`, `admin/controller.php` (`processConfig`),
`lib/class_core.php::processConfig()`, tabela `settings` w
`setup/sql/structure.sql`.

Grupy ustawień: dane witryny; szablony; data, czas, strefa, locale, pierwszy
dzień tygodnia i język; widoczność logowania, wyszukiwarki, breadcrumbs,
przełącznika języka i cookie; maintenance wraz z datą, godziną i komunikatem;
wymiary i jakość obrazów; paginacja i waluta; rejestracja i weryfikacja kont;
limity, próby logowania, flood i logowanie zdarzeń; social media; analytics,
meta i Tenor; uwierzytelnianie API; PHP mail/sendmail/SMTP wraz z SSL.

## Szablony wiadomości e-mail

Źródła: `admin/templates.php`, `admin/controller.php` (`processTemplate`,
`deleteTemplate`), `lib/class_membership.php::processEmailTemplate()`, tabela
`email_templates`.

Wymagana zgodność: lista, dodawanie, edycja i usuwanie; nazwa, temat, typ
`mailer/news`, treść HTML oraz pomoc opisująca zmienne szablonu. Szablony
systemowe są identyfikowane przez `typeid` i nie mogą utracić tej roli.

## Newsletter

Źródła: `admin/newsletter.php`, `admin/newsletter/views/mailings.php`,
`mailing_details.php`, `queue.php`, `send.php`,
`admin/newsletter/newsletter_sender.php`, `lib/class_newsletter.php`.

Wymagana zgodność: zakładki wysyłek, kolejki i nowej wysyłki; wybór szablonu i
grupy adresatów (wszyscy, zapisani na newsletter, członkostwo, pojedynczy
adresat/import CSV), podgląd i personalizacja zmiennymi, zapis wysyłki,
kolejkowanie, status postępu, szczegóły wysłanych/błędnych wiadomości oraz
bezpieczne wznowienie kolejki.

## Zarządzanie językami

Źródła: `admin/language.php`, `admin/controller.php` (`addLanguage`,
`updateLanguage`, `deleteLanguage`, `loadLanguage`, `getLangWord`), tabela
`language` i pliki `lang/*/lang.xml`.

Wymagana zgodność: lista, dodawanie, edycja i usuwanie języka; nazwa,
dwuliterowy kod/flagę, kierunek LTR/RTL, autora i subdomenę; edytor fraz z
wyszukiwaniem, przełączaniem języka oraz zapisem pojedynczej frazy.

## Menedżer plików

Źródła: `admin/filemanager.php`, `admin/controller.php` (`doFM`: `newFolder`,
`newFile`, `rename`, `deleteFile`, `deleteDir`, `doUpload`), `lib/class_fm.php`.

Wymagana zgodność: breadcrumbs, przechodzenie po katalogach, licznik plików i
katalogów, utworzenie katalogu i pustego pliku, wielokrotny upload, dozwolone
MIME, lista z typem/miniaturą/rozmiarem/datą, podgląd obrazu, zmiana nazwy oraz
usuwanie. Każda operacja musi pozostać wewnątrz skonfigurowanego katalogu
uploadów; Legacy już jawnie blokuje `../` i `..\\` w `admin/filemanager.php`.
Nowa implementacja dodatkowo wiąże wykryty MIME z dozwolonym rozszerzeniem przy
uploadzie i zmianie nazwy. Plik nie może więc zostać opublikowany jako inny typ
wyłącznie przez zmianę jego rozszerzenia.
Wieloplikowy upload zwraca osobno liczbę zapisanych i odrzuconych plików. Panel
pokazuje też przyczynę odrzucenia, a błąd jednego elementu nie wycofuje poprawnych
plików z tej samej paczki.
Widok pokazuje liczbę katalogów, liczbę plików oraz ich łączny rozmiar dla
aktualnie otwartego katalogu. Warianty responsywne WebP/AVIF pozostają techniczne,
więc nie zawyżają tych liczników.

## Logi

Źródła: `admin/logs.php`, `admin/controller.php` (`deleteLogs`),
`lib/class_security.php::writeLog()`, tabela `log`.

Wymagana zgodność: lista, filtrowanie zakresem dat, wybór liczby rekordów,
paginacja i czyszczenie całości. Rekord przechowuje czas, użytkownika, IP, typ
`system/admin/user`, dane/ikonę, komunikat i ważność. Operacje administracyjne
nowej wersji muszą dopisywać audyt, a treść przy wyświetlaniu pozostaje
sanityzowana.
Audyt rozróżnia operacje wykonywane przez wspólne kontrolery, np. `upload`,
`mkdir`, `rename` i `delete` menedżera plików. Dla plików zapisuje wyłącznie
ścieżkę operacji, nigdy zawartość uploadu. Usuwanie, czyszczenie, cofanie
uprawnień i przywracanie rewizji są oznaczane jako zdarzenia ważne.
Lista rozróżnia zdarzenia operatorów i użytkowników witryny. Można ją filtrować
po typie, ważności i poprawnym zakresie dat oraz przeszukiwać po zdarzeniu,
komunikacie, użytkowniku albo adresie IP. Nieprawidłowa data jest ignorowana,
zamiast powodować błąd panelu.
Każdy wpis ma stronę szczegółów. Pokazuje ona tylko jawnie dozwolone dane
techniczne (`route`, `method`, `operation`, `path`, `item`); inne pola, w tym
potencjalne hasła, tokeny i zawartość formularzy, nie są renderowane.

## Zasady implementacji Symfony

- wszystkie trasy znajdują się pod `/admin` i wymagają `ROLE_ADMIN`;
- operacje zmieniające dane używają POST oraz CSRF;
- hasła są hashowane PasswordHasherem, hasła SMTP nie są ponownie pokazywane;
- wysyłka newslettera działa kolejką, nie w żądaniu HTTP;
- pliki są walidowane po MIME i ścieżce kanonicznej;
- konfiguracja i operacje CRUD zapisują wpis audytowy;
- migracje zachowują pola potrzebne do późniejszego importu danych Legacy.
# Członkostwa

Shopro 4.0 odwzorowuje pola bazowego rekordu Legacy `memberships`: nazwę, opis
i aktywność. Lista oraz dodawanie i edycja są dostępne administratorowi w
`/admin/memberships`. Relacje z podstronami i kontami frontowymi są znormalizowane
w tabelach łączących. Kontami klientów zarządza osobna sekcja
`/admin/site-users`; obsługuje wyszukiwanie, aktywność, bezpieczną zmianę hasła oraz
przypisywanie wielu grup. Operatorzy PA pozostają oddzieleni w `/admin/users`.
Kasowanie grupy nadal jest zablokowane, aby nie tworzyć osieroconych uprawnień.
Źródła Legacy:
`setup/sql/structure.sql:93`, `lib/class_membership.php:31`,
`lib/class_membership.php:57`, `admin/memberships.php`.

Rejestracja kont witryny pod `/register` respektuje ustawienia konfiguracji:
włączenie rejestracji, limit użytkowników oraz ręczną lub automatyczną aktywację.
Przy wymaganej weryfikacji system wysyła responsywny szablon
`user_activate_account`. W bazie przechowywany jest wyłącznie skrót SHA-256
jednorazowego tokenu, który wygasa po 24 godzinach. Nieudana wysyłka wiadomości
nie aktywuje konta i jest jawnie komunikowana użytkownikowi.

Konta witryny mają osobne odzyskiwanie hasła pod `/password/forgot` i nie
korzystają z tokenów operatorów panelu. Odpowiedź formularza nie ujawnia, czy
login lub e-mail istnieje. Link jest jednorazowy, ważny przez godzinę, a w bazie
zapisywany jest wyłącznie jego skrót SHA-256. Po zmianie hasła wykorzystany token
zwraca HTTP 410 i nie może zostać użyty ponownie.

Zalogowany użytkownik witryny ma samoobsługowy panel `/account`. Może sprawdzić
swoje aktywne członkostwa, zmienić unikalny login i adres e-mail oraz ustawić
nowe hasło po potwierdzeniu obecnego. Zmiana danych i hasła jest audytowana, a
trasy konta wymagają `ROLE_SITE_USER` i nie są dostępne operatorom PA wyłącznie
z racji ich uprawnień administracyjnych.

Nieaktywny użytkownik może poprosić o ponowną wiadomość pod
`/activation/resend`. Formularz zawsze zwraca ten sam komunikat, niezależnie od
istnienia i stanu konta. Poprzedni token jest zastępowany dopiero po skutecznym
przekazaniu nowej wiadomości do transportu pocztowego. Endpoint ma limit trzech
prób na 15 minut dla kombinacji adresu IP i identyfikatora konta.

Szablony systemowe są powiązane z cyklem konta: `user_activate_account` wysyła
link weryfikacyjny, `user_thans_for_registration` potwierdza aktywację,
`admin_new_user` lub `admin_accept_new_user` informuje administratora zgodnie z
ustawieniem `notify_admin`, a `user_admin_activate_your_account` informuje o
ręcznej aktywacji w PA. Awarie wiadomości informacyjnych nie wycofują poprawnie
zapisanej rejestracji lub aktywacji.

Opcjonalne „Zapamiętaj mnie” działa niezależnie dla operatorów PA i użytkowników
witryny. Podpisane ciastka mają osobne nazwy, ważność 30 dni, `HttpOnly`,
`SameSite=Lax` i automatyczne wymaganie HTTPS. Cookie operatora jest dodatkowo
ograniczone ścieżką `/admin`, dzięki czemu nie jest wysyłane do frontu.

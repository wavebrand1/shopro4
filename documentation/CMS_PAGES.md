# Podstrony CMS — zgodność z Shopro Legacy

Źródłem kontraktu podstron są `stare/Wersja_2_00/Develop/admin/pages.php`, metoda
`Content::processPage()` w `stare/Wersja_2_00/Develop/lib/class_content.php` oraz tabela
`pages` w `stare/Wersja_2_00/Develop/setup/sql/structure.sql`.

## Zaimplementowany kontrakt

- tytuł, slug, podpis/zajawka, aktywność i treść HTML;
- tytuł SEO, opis, słowa kluczowe, dodatkowe meta, canonical i follow;
- dostęp `Public`, `Registered` i `Membership`; dla poziomu `Membership` administrator
  przypisuje jedną lub kilka dozwolonych grup, a zapis bez grupy jest blokowany;
- role: strona główna, 404, logowanie, aktywacja, konto, rejestracja, wyszukiwanie,
  mapa strony, profil i regulamin;
- rola „tylko administrator”, kod JavaScript strony, duplikowanie i usuwanie;
- pojedynczość ról systemowych oraz blokada usuwania strony systemowej;
- renderowanie wskazanej strony głównej i metadanych na froncie.

Obraz podstrony w formularzu legacy jest zakomentowany. Pola modułu, danych modułu,
motywu pozostają zależnościami kolejnych etapów migracji i nie są pozorowane
bez implementacji odpowiadających im modułów.

Przypisania członkostw są przechowywane relacyjnie w `cms_page_membership`, zamiast
listy identyfikatorów w pojedynczym polu używanej przez Legacy. Zapewnia to integralność
danych i zachowuje możliwość wyboru wielu grup.

Konta użytkowników witryny są oddzielone od operatorów panelu administracyjnego
(`site_user` i `admin_user`) oraz używają osobnych zapór i sesji Symfony. Dostęp
`Registered` wymaga aktywnego konta frontowego, natomiast `Membership` dodatkowo
wymaga co najmniej jednej aktywnej grupy wspólnej dla konta i podstrony. Próba wejścia
bez sesji zachowuje adres docelowy i prowadzi do `/login`; zalogowane konto bez
wymaganej grupy otrzymuje odpowiedź 403.

Implementacja: `src/Cms/Domain/Entity/Page.php`,
`src/Cms/Presentation/Form/PageType.php`,
`src/Cms/Presentation/Http/Admin/PageController.php`,
`src/Cms/Infrastructure/Persistence/Doctrine/PageRepository.php` oraz
`templates/admin/page/`.

## Cykl życia podstrony

Podstrona może być szkicem, oczekiwać na publikację, być opublikowana albo wygasła.
Status nie jest przechowywany jako niezależny tekst: wynika z pól `published`,
`publishAt` oraz `unpublishAt`. Dzięki temu publikowanie i wygaszanie działa na
podstawie aktualnego czasu bez osobnego zadania cron. Reguły wylicza
`Page::getPublicationStatus()`, a publiczne zapytania ogranicza
`PageRepository::applyPublicationWindow()`.

Lista `/admin/pages` obsługuje:

- wyszukiwanie po tytule i slugu;
- filtrowanie według statusu publikacji oraz dostępu;
- sortowanie według tytułu, daty utworzenia i ostatniej aktualizacji;
- paginację określoną przez `per_page` z konfiguracji systemu;
- zbiorcze publikowanie, przenoszenie do szkiców i kosza.

Parametry filtrów, sortowania i numer strony są zachowywane po operacji zbiorczej.
Pola sortowania i filtrów korzystają z białych list w `PageController`; wartości z URL
nie są używane bezpośrednio jako fragment DQL. Numer większy od liczby stron jest
ograniczany do ostatniej istniejącej strony.

## Kosz i ochrona danych

Usunięcie w panelu jest operacją miękką: `Page::moveToTrash()` ustawia `deletedAt`,
a publiczne repozytorium natychmiast wyklucza rekord. Kosz `/admin/pages/trash`
pozwala wyszukiwać, przywracać jako szkic oraz — administratorowi — usuwać trwale.
Przywracanie i trwałe usuwanie działają pojedynczo i zbiorczo.

Strona systemowa ani strona używana przez pozycję menu nie może zostać przeniesiona
do kosza lub trwale usunięta. Operacja zbiorcza pomija rekordy chronione i pokazuje
liczbę pominiętych pozycji. Zależności menu sprawdza
`MenuItemRepository::usageByPageIds()`. Tłumaczenia i rewizje mają klucze obce
`ON DELETE CASCADE`, dlatego po świadomym trwałym usunięciu nie pozostają osierocone
rekordy.

## Historia, równoczesna edycja i przekierowania

Każdy poprawny zapis tworzy migawkę w `cms_page_revision`. Historia podstrony pozwala
porównać wersje i odtworzyć komplet danych strony. Implementację stanowią
`PageRevisionManager`, `PageRevisionController` oraz `templates/admin/page/revision/`.

Pole `lockVersion` korzysta z optymistycznej blokady Doctrine. Jeżeli drugi operator
zapisze stronę po otwarciu formularza, starszy formularz otrzyma błąd konfliktu i nie
nadpisze nowszej treści.

Zmiana slugu tworzy przekierowanie ze starego adresu. `UrlRedirectManager` blokuje
pętle, skraca łańcuchy do końcowego celu i pozwala bezpiecznie cofnąć zmianę przez
odtworzenie wcześniejszej rewizji.

## Testy kontraktu

Scenariusze funkcjonalne znajdują się w `tests/Functional/AdminCmsTest.php`. Obejmują
między innymi publikację, podgląd, automatyczny slug, dostęp, filtrowanie, sortowanie,
paginację, kosz, ochronę powiązań menu, historię i konflikt równoczesnej edycji.

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

Flaga `adminOnly` ma pierwszeństwo przed rolą strony i poziomem dostępu. Treść takiej
podstrony nie jest renderowana przez zwykły adres, wersję językową ani `/`, nawet gdy
rekord ma jednocześnie rolę strony głównej. Nie trafia również do wyszukiwarki,
sitemapy i obsługi historycznych adresów `/strona/slug`.

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

Pozycje menu prowadzące do podstron podlegają temu samemu oknu publikacji. Szkic,
strona oczekująca na publikację, wygasła lub znajdująca się w koszu nie jest pokazywana
w menu frontu, nawet jeżeli sama pozycja menu pozostaje aktywna. Link pojawia się i
znika na podstawie aktualnego czasu, bez zadania cron.

Własne linki menu i ich tłumaczenia są walidowane przed zapisem. Dozwolone są adresy
HTTP/HTTPS, ścieżki wewnętrzne zaczynające się pojedynczym `/`, kotwice oraz poprawne
odnośniki `mailto:` i `tel:`. Protokoły wykonywalne (np. `javascript:`), adresy `//`,
backslashe, spacje i znaki sterujące są blokowane przed trafieniem do `href`.

`sitemap.xml` korzysta z tego samego okna publikacji i zawiera wyłącznie publiczne
podstrony. Strona obsługująca błąd 404 oraz techniczna strona wyszukiwarki są
wykluczone z mapy również wtedy, gdy mają opublikowaną treść lub tłumaczenia.
Adresy `hreflang` na podstronie, stronie głównej i w mapie witryny pochodzą z jednego
zapytania: uwzględniają wyłącznie opublikowane tłumaczenia aktywnych języków innych niż
język bazowy. Wyłączenie języka usuwa więc jego adresy ze wszystkich sygnałów SEO.

Przełącznik języka zachowuje bieżącą podstronę tylko wtedy, gdy jest ona aktualnie
opublikowana i dostępna dla użytkownika. Nie ujawnia adresów szkiców, stron wygasłych,
administracyjnych ani treści wymagających innego członkostwa. Parametr powrotu
przełącznika języka PA akceptuje wyłącznie lokalne ścieżki `/admin` i `/admin/...`;
adresy absolutne, `//`, backslashe i podobne warianty są odrzucane.

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

Wyszukiwanie zabezpiecza operatory `LIKE`: znaki `%`, `_` oraz znak ucieczki są
traktowane jako zwykła treść wpisana przez operatora. Ta sama reguła obowiązuje na
liście aktywnych podstron i w koszu.

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

Przestrzeń panelu jest rozpoznawana po dokładnym prefiksie `/admin/` (oraz samym
`/admin`), a nie po samym początku napisu. Publiczne slugi takie jak
`/administrator-poradnik` pozostają dzięki temu zwykłymi podstronami i nie są
przechwytywane przez firewall, obsługę błędów, konserwację ani audyt panelu.

Dokładne slugi zajęte przez kontrolery systemowe (między innymi `admin`, `api`,
`login`, `register`, `account`, `search`, `password`, `language`, `newsletter`
i `health`) są odrzucane przed zapisem strony bazowej oraz tłumaczenia. Dłuższe
slugi zaczynające się od tych słów, np. `administrator-poradnik`, są dozwolone.

Odtworzenie jest blokowane, jeżeli historyczny slug zajmuje inna podstrona — także
znajdująca się w koszu — i pokazuje czytelny komunikat zamiast błędu bazy. Role
systemowe są przywracane przez `PageRepository::save()`, które atomowo odbiera daną
rolę poprzedniej podstronie, dzięki czemu nadal istnieje najwyżej jedna strona główna,
404, logowania lub inna strona techniczna danego rodzaju.

Historia strony znajdującej się w koszu jest tylko zachowanym elementem jej danych i
nie może zostać użyta do zmiany rekordu poza standardowym przywróceniem z kosza.
Trwałe usunięcie strony technicznej jest dodatkowo blokowane po stronie kontrolera,
niezależnie od tego, w jaki sposób rekord trafił do kosza.

## Duplikowanie

Duplikowanie tworzy zawsze nieopublikowany szkic bez harmonogramu, canonical i ról
systemowych. Treść, komponenty, SEO, poziom dostępu i przypisane członkostwa są
kopiowane. Slug otrzymuje przewidywalny sufiks `-kopia`, a kolejne kopie `-kopia-2`,
`-kopia-3` itd.; generator uwzględnia również slugi zajęte przez rekordy w koszu.

## Testy kontraktu

Scenariusze funkcjonalne znajdują się w `tests/Functional/AdminCmsTest.php`. Obejmują
między innymi publikację, podgląd, automatyczny slug, dostęp, filtrowanie, sortowanie,
paginację, kosz, ochronę powiązań menu, historię i konflikt równoczesnej edycji.

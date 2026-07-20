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

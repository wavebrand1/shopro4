# SEO techniczne

Shopro generuje automatyczny adres canonical dla każdej opublikowanej podstrony.
Wartość wpisana ręcznie w ustawieniach podstrony ma pierwszeństwo. Wersje językowe
otrzymują własny canonical oraz wzajemne odnośniki `hreflang`; wersja bazowa jest
oznaczona także jako `x-default`.

`/sitemap.xml` zawiera tylko aktywne, opublikowane strony z dostępem publicznym.
Do każdego dokumentu dołączane są opublikowane tłumaczenia w aktywnych językach.
Data `lastmod` pochodzi z daty ostatniej zmiany strony.

`/robots.txt` zezwala na indeksowanie witryny, blokuje panel `/admin` i wskazuje
mapę XML. Po włączeniu trybu konserwacji automatycznie blokuje całą witrynę.
Obie odpowiedzi są buforowane publicznie przez 15 minut.

Każda zwykła odpowiedź strony zawiera również Open Graph i Twitter Card, dzięki
czemu link ma spójny tytuł oraz opis po udostępnieniu. Dane JSON-LD opisują stronę,
witrynę i organizację w jednym grafie `@graph`. Nazwa witryny, firmy oraz logo są
pobierane z Konfiguracji systemu, a tytuł i opis z ustawień bieżącej podstrony.
Podgląd niezapisanej strony nie emituje canonical, danych social ani JSON-LD.

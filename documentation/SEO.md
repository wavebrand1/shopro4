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

# SEO techniczne

Shopro generuje automatyczny adres canonical dla każdej opublikowanej podstrony.
Wartość wpisana ręcznie w ustawieniach podstrony ma pierwszeństwo. Wersje językowe
otrzymują własny canonical oraz wzajemne odnośniki `hreflang`; wersja bazowa jest
oznaczona także jako `x-default`.
Ręczny canonical musi być pełnym adresem HTTP lub HTTPS (najlepiej HTTPS). Formularz
odrzuca ścieżki względne, inne protokoły i nieprawidłowe adresy, aby ten sam poprawny
URL mógł zostać użyty w canonical, Open Graph oraz danych JSON-LD.

`/sitemap.xml` zawiera tylko aktywne, opublikowane strony z dostępem publicznym.
Do każdego dokumentu dołączane są opublikowane tłumaczenia w aktywnych językach.
Data `lastmod` pochodzi z daty ostatniej zmiany strony.

`/robots.txt` zezwala na indeksowanie witryny, blokuje panel `/admin` i wskazuje
mapę XML. Po włączeniu trybu konserwacji automatycznie blokuje całą witrynę.
Obie odpowiedzi mają politykę `no-store, must-revalidate`. Dzięki temu zmiana trybu
konserwacji, publikacji albo jej harmonogramu jest widoczna dla robotów od następnego
żądania i nie pozostaje przez kilkanaście minut w cache przeglądarki, proxy lub CDN.

Każda zwykła odpowiedź strony zawiera również Open Graph i Twitter Card, dzięki
czemu link ma spójny tytuł oraz opis po udostępnieniu. Dane JSON-LD opisują stronę,
witrynę i organizację w jednym grafie `@graph`. Nazwa witryny, firmy oraz logo są
pobierane z Konfiguracji systemu, a tytuł i opis z ustawień bieżącej podstrony.
Podgląd niezapisanej strony nie emituje canonical, danych social ani JSON-LD.
Opcjonalną grafikę kart można przesłać w grupie „SEO i analityka” w Konfiguracji
systemu. Akceptowane są PNG, JPG i WebP (zalecane 1200 × 630 px). Bez grafiki
system świadomie generuje kartę tekstową `summary`, zamiast używać niepasującego logo.

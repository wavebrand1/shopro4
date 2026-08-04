# Moduł Blog — zakres zgodności z Shopro Legacy

## Cel

Moduł `blog` jest osobno instalowanym pakietem Composer
`wavebrand/shopro-blog-module`. Ma odtworzyć zachowania paczki Shopro Legacy
„Moduł Bloga 2.0”, ale używać architektury Shopro 4.0, Doctrine, tłumaczeń encji, wspólnego modułu
Media i bramek `ModuleRuntime`. Stary model danych jest źródłem wymagań oraz danych
do migracji, a nie wzorem implementacji.

## Wymagany zakres funkcjonalny

- Artykuły: tytuł, slug, skrót, treść, autor, status, publikacja i wygaśnięcie,
  SEO, obraz wyróżniający, załączniki, odsłony oraz wariant prezentacji.
- Wielojęzyczność artykułów i kategorii przez osobne encje tłumaczeń.
- Hierarchiczne kategorie z kolejnością, aktywnością, ukryciem, SEO i własną
  liczbą wpisów na stronę; artykuł ma kategorię główną i może należeć do wielu.
- Tagi oraz publiczne listy według tagu, autora, kategorii i miesiąca archiwum.
- Widoki publiczne: indeks, kategoria, artykuł, wyszukiwanie, tag, autor,
  archiwum, RSS i wpisy sitemap.
- Trzy historyczne poziomy widoczności oraz ograniczenie treści do wybranych
  membershipów; wartości legacy muszą dać się jednoznacznie migrować.
- Obraz i załączniki jako relacje do zasobów modułu Media.
- Komentarze gości i użytkowników: odpowiedzi, moderacja, paginacja, CAPTCHA,
  wymagane pola, blacklistowanie słów i powiadomienia.
- Konfigurowane per artykuł: autor, data, komentarze, oceny, polubienia i
  udostępnianie społecznościowe.
- Odporne na wielokrotne głosowanie oceny i polubienia.
- Komponenty Page Buildera: kategorie, archiwum, ostatnie oraz popularne wpisy.
- Panel administracyjny: CRUD artykułów, drzewo kategorii, moderacja komentarzy,
  konfiguracja i podgląd; osobne uprawnienia do treści, kategorii, konfiguracji
  i podglądu.
- SEO: canonical, meta, Open Graph, JSON-LD BlogPosting/NewsArticle, breadcrumbs,
  sitemap i przekierowania ze starych adresów `/{slug}`.
- Opcjonalne integracje: Newsletter i alerty; punkt rozszerzenia dla przyszłego
  modułu Shop bez twardej zależności.

## Reguły modułu Shopro 4.0

- Kod modułu: `blog`, wersjonowany przez `ModuleDefinition`.
- Kod biznesowy nie należy do `App\\` ani rdzenia Shopro; pakiet dostarcza bundle,
  konfigurację usług, mapowania Doctrine, migracje, trasy, szablony i tłumaczenia.
- Instalacja kodu odbywa się przez Composer. Dopiero potem `app:modules:sync`
  tworzy wyłączony wpis instalacji, który administrator może włączyć.
- Moduł jest opcjonalny i po pierwszej synchronizacji pozostaje wyłączony.
- Zależności wymagane: `cms`, `identity`, `language`, `media`, `settings`.
- Wszystkie kontrolery, komendy, listenery, wiadomości i komponenty są chronione
  bramką modułu.
- Wyłączenie modułu nie usuwa ani nie modyfikuje danych.
- Migracje schematu są wykonywane wyłącznie przez Doctrine Migrations.
- Integracja z Newsletterem nie może uniemożliwiać publikacji, gdy Newsletter
  jest wyłączony.

## Etapy implementacji

1. Definicja modułu, encje artykułów, tłumaczeń, kategorii i tagów oraz migracja.
2. Administracyjny CRUD artykułów i kategorii wraz z uprawnieniami.
3. Publiczny indeks, kategoria i pojedynczy artykuł z publikacją czasową i SEO.
4. Media, załączniki, tagi, autorzy, archiwum, wyszukiwanie, RSS i sitemap.
5. Komentarze, moderacja, oceny, polubienia i udostępnianie.
6. Komponenty Page Buildera, Newsletter, alerty i migrator danych legacy.

## Niespójności legacy, których nie wolno kopiować

- Nieistniejąca metoda obsługi artykułów dodawanych przez użytkowników.
- Tagi nowych artykułów zapisywane dopiero po późniejszej edycji.
- Wyszukiwanie FULLTEXT bez gwarantowanego indeksu.
- Identyfikatory załączników zapisane jako tekstowa lista.
- Niejednoznaczna relacja kategorii głównej i dodatkowych.
- Brak ochrony ocen i polubień przed ponownym głosem.
- RSS omijający część ograniczeń dostępu.
- Sitemap używająca bieżącej daty zamiast daty modyfikacji.
- Osobne kolumny językowe i skopiowane szablony dla każdego motywu.

# Komponenty formularzy panelu administracyjnego

Wspólny wygląd pól formularzy jest zdefiniowany przez:

- `templates/admin/form_theme.html.twig` — struktura pól Symfony, komunikaty pomocy i błędów;
- `assets/styles/app.css` — wygląd inputów, textarea, selectów, checkboxów i radio;
- `assets/admin-form-components.js` — interaktywne komponenty formularzy.

## SearchableSelect

Komponent zastępuje natywny select kontrolką opartą na zachowaniu `wb_chosen` ze starego
Shopro, ale dopasowaną do szablonu Shopro 4. W zamkniętym polu pokazuje maksymalnie dwie
wybrane wartości i licznik pozostałych. Dropdown zawiera wyszukiwarkę, listę zaznaczonych
elementów z możliwością ich usunięcia oraz listę dostępnych pozycji.

W panelu administracyjnym komponent jest automatycznie stosowany do wszystkich selectów
(również dodanych dynamicznie). Atrybut `data-native-select` pozwala świadomie pozostawić
kontrolkę natywną. Obsługuje wybór pojedynczy i wielokrotny. Może filtrować opcje istniejące
w HTML albo korzystać z wyszukiwania asynchronicznego, dzięki czemu nie pobiera całej kolekcji.

Selecty stanowiące część edytora tekstu Quill są zawsze pomijane i zachowują oryginalny
wygląd oraz zachowanie dostarczane przez edytor.

Select korzystający z endpointu wymaga atrybutów:

```html
<select
    multiple
    data-searchable-select
    data-search-url="/admin/example/search"
    data-search-placeholder="Szukaj..."
    data-search-empty="Brak wyników"
    data-search-more="Pokaż więcej"
></select>
```

Opcjonalny `data-display-count` określa liczbę wartości widocznych w zamkniętym polu
(domyślnie 2). Bez `data-search-url` komponent filtruje opcje już obecne w elemencie `select`.

Endpoint zwraca:

```json
{
  "results": [{"id": 1, "text": "Nazwa pozycji"}],
  "more": false
}
```

Dropdown jest pozycjonowany absolutnie, więc nie zmienia wysokości formularza. Oryginalny
select pozostaje źródłem wartości wysyłanej do Symfony.

# Komponenty formularzy panelu administracyjnego

Wspólny wygląd pól formularzy jest zdefiniowany przez:

- `templates/admin/form_theme.html.twig` — struktura pól Symfony, komunikaty pomocy i błędów;
- `assets/styles/app.css` — wygląd inputów, textarea, selectów, checkboxów, radio i pól wyboru;
- `assets/admin-form-components.js` — interaktywne komponenty formularzy.

## SearchableSelect

Komponent zastępuje natywny select kontrolką z wyszukiwaniem asynchronicznym. Obsługuje wybór
pojedynczy i wielokrotny oraz nie pobiera całej kolekcji przy otwieraniu formularza.

Select wymaga atrybutów:

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

Endpoint zwraca:

```json
{
  "results": [{"id": 1, "text": "Nazwa pozycji"}],
  "more": false
}
```

Dropdown jest pozycjonowany absolutnie, więc nie zmienia wysokości formularza. Oryginalny select
pozostaje źródłem wartości wysyłanej do Symfony.

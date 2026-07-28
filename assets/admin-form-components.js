const SEARCHABLE_SELECT_SELECTOR = 'select[data-searchable-select]';

const initializeSearchableSelect = (select) => {
    if (select.dataset.componentReady === 'true') return;
    select.dataset.componentReady = 'true';
    select.classList.add('ui-native-control--enhanced');

    const component = document.createElement('div');
    component.className = 'ui-searchable-select';
    component.innerHTML = `
        <div class="ui-searchable-select__control">
            <div class="ui-searchable-select__selected" data-selected></div>
            <input class="ui-searchable-select__search" type="search" autocomplete="off"
                   role="combobox" aria-autocomplete="list" aria-haspopup="listbox" aria-expanded="false">
            <span class="ui-searchable-select__arrow" aria-hidden="true"></span>
        </div>
        <div class="ui-searchable-select__dropdown" role="listbox" data-dropdown hidden></div>`;
    select.after(component);

    const control = component.querySelector('.ui-searchable-select__control');
    const selected = component.querySelector('[data-selected]');
    const input = component.querySelector('input');
    const dropdown = component.querySelector('[data-dropdown]');
    const multiple = select.multiple;
    const optionByValue = (value) => [...select.options].find((option) => option.value === String(value));
    let controller = null;
    let timer = null;
    let page = 1;
    let currentQuery = '';

    input.placeholder = select.dataset.searchPlaceholder || '';
    dropdown.id = `${select.id || `searchable-select-${crypto.randomUUID()}`}-dropdown`;
    input.setAttribute('aria-controls', dropdown.id);

    const close = () => {
        dropdown.hidden = true;
        component.classList.remove('is-open');
        input.setAttribute('aria-expanded', 'false');
    };
    const open = () => {
        dropdown.hidden = false;
        component.classList.add('is-open');
        input.setAttribute('aria-expanded', 'true');
    };
    const notifyChange = () => select.dispatchEvent(new Event('change', { bubbles: true }));

    const renderSelected = () => {
        selected.replaceChildren();
        [...select.selectedOptions].forEach((option) => {
            const chip = document.createElement('span');
            chip.className = 'ui-searchable-select__chip';
            chip.append(document.createTextNode(option.text));
            if (multiple) {
                const remove = document.createElement('button');
                remove.type = 'button';
                remove.setAttribute('aria-label', `${select.dataset.removeLabel || 'Usuń'} ${option.text}`);
                remove.textContent = '×';
                remove.addEventListener('click', (event) => {
                    event.stopPropagation();
                    option.selected = false;
                    if (!option.defaultSelected) option.remove();
                    renderSelected();
                    notifyChange();
                });
                chip.append(remove);
            }
            selected.append(chip);
        });
    };

    const choose = (item) => {
        if (!multiple) [...select.options].forEach((option) => { option.selected = false; });
        let option = optionByValue(item.id);
        if (!option) {
            option = new Option(item.text, String(item.id), true, true);
            select.add(option);
        }
        option.selected = true;
        renderSelected();
        notifyChange();
        input.value = '';
        currentQuery = '';
        close();
    };

    const createOption = (item) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'ui-searchable-select__option';
        button.setAttribute('role', 'option');
        button.textContent = item.text;
        button.addEventListener('click', () => choose(item));
        return button;
    };

    const load = async (append = false) => {
        controller?.abort();
        controller = new AbortController();
        const url = new URL(select.dataset.searchUrl, window.location.origin);
        url.searchParams.set('q', currentQuery);
        url.searchParams.set('page', String(page));
        try {
            const response = await fetch(url, {
                signal: controller.signal,
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const payload = await response.json();
            if (!append) dropdown.replaceChildren();
            payload.results
                .filter((item) => !optionByValue(item.id)?.selected)
                .forEach((item) => dropdown.append(createOption(item)));
            if (payload.more) {
                const more = document.createElement('button');
                more.type = 'button';
                more.className = 'ui-searchable-select__option ui-searchable-select__more';
                more.textContent = select.dataset.searchMore || 'Pokaż więcej';
                more.addEventListener('click', () => {
                    page += 1;
                    load(true);
                });
                dropdown.append(more);
            }
            if (!dropdown.children.length) {
                const empty = document.createElement('p');
                empty.className = 'ui-searchable-select__empty';
                empty.textContent = select.dataset.searchEmpty || 'Brak wyników';
                dropdown.append(empty);
            }
            open();
        } catch (error) {
            if (error.name !== 'AbortError') close();
        }
    };

    input.addEventListener('input', () => {
        clearTimeout(timer);
        currentQuery = input.value.trim();
        page = 1;
        timer = setTimeout(() => load(), 250);
    });
    input.addEventListener('focus', () => {
        currentQuery = input.value.trim();
        page = 1;
        load();
    });
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            close();
            input.blur();
        }
    });
    control.addEventListener('click', (event) => {
        if (!event.target.closest('button')) input.focus();
    });
    document.addEventListener('click', (event) => {
        if (!component.contains(event.target)) close();
    });
    renderSelected();
};

export const initializeAdminFormComponents = () => {
    document.querySelectorAll(SEARCHABLE_SELECT_SELECTOR).forEach(initializeSearchableSelect);
};

document.addEventListener('turbo:load', initializeAdminFormComponents);
initializeAdminFormComponents();

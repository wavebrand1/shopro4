const SEARCHABLE_SELECT_SELECTOR = [
    '.admin-body select:not([data-native-select]):not(.picker__select--month):not(.picker__select--year):not(.flatpickr-monthDropdown-months)',
    'select[data-searchable-select]',
    'select[data-newsletter-user-picker]',
].join(', ');

const initializeSearchableSelect = (select) => {
    if (select.dataset.componentReady === 'true') return;

    select.dataset.componentReady = 'true';
    select.dataset.pickerReady = 'true';
    select.hidden = true;
    select.style.setProperty('display', 'none', 'important');
    select.classList.add('ui-native-control--enhanced');
    if (select.required) {
        select.required = false;
        select.dataset.wasRequired = 'true';
    }
    if (select.nextElementSibling?.classList.contains('async-user-picker')) select.nextElementSibling.remove();

    const multiple = select.multiple;
    const displayCount = Math.max(1, Number.parseInt(select.dataset.displayCount || '2', 10) || 2);
    const component = document.createElement('div');
    component.className = 'ui-searchable-select';
    component.classList.toggle('ui-searchable-select--multiple', multiple);
    component.classList.toggle('ui-searchable-select--single', !multiple);
    component.innerHTML = `
        <button class="ui-searchable-select__control" type="button" aria-haspopup="listbox" aria-expanded="false">
            <span class="ui-searchable-select__summary" data-summary></span>
            <span class="ui-searchable-select__arrow" aria-hidden="true"></span>
        </button>
        <div class="ui-searchable-select__dropdown" data-dropdown hidden>
            <div class="ui-searchable-select__search-wrap">
                <span class="ui-searchable-select__search-icon" aria-hidden="true"></span>
                <input class="ui-searchable-select__search" type="search" autocomplete="off">
            </div>
            <div class="ui-searchable-select__chosen" data-chosen hidden></div>
            <div class="ui-searchable-select__options" role="listbox" data-options></div>
        </div>`;
    select.after(component);

    const control = component.querySelector('.ui-searchable-select__control');
    const summary = component.querySelector('[data-summary]');
    const input = component.querySelector('input');
    const dropdown = component.querySelector('[data-dropdown]');
    const chosen = component.querySelector('[data-chosen]');
    const options = component.querySelector('[data-options]');
    const optionByValue = (value) => [...select.options].find((option) => option.value === String(value));
    let controller = null;
    let timer = null;
    let page = 1;
    let currentQuery = '';

    dropdown.id = `${select.id || `searchable-select-${crypto.randomUUID()}`}-dropdown`;
    input.placeholder = select.dataset.searchPlaceholder || 'Szukaj';
    input.setAttribute('aria-label', input.placeholder);
    control.setAttribute('aria-controls', dropdown.id);
    if (select.dataset.wasRequired === 'true') control.setAttribute('aria-required', 'true');
    control.disabled = select.disabled;
    options.setAttribute('aria-multiselectable', multiple ? 'true' : 'false');

    const close = () => {
        dropdown.hidden = true;
        component.classList.remove('is-open');
        control.setAttribute('aria-expanded', 'false');
    };
    const open = () => {
        dropdown.hidden = false;
        component.classList.add('is-open');
        control.setAttribute('aria-expanded', 'true');
        window.requestAnimationFrame(() => input.focus());
    };
    const notifyChange = () => select.dispatchEvent(new Event('change', { bubbles: true }));

    const renderSummary = () => {
        summary.replaceChildren();
        const selectedOptions = [...select.selectedOptions].filter((option) => option.value !== '');
        if (selectedOptions.length === 0) {
            const placeholder = document.createElement('span');
            placeholder.className = 'ui-searchable-select__placeholder';
            placeholder.textContent = select.dataset.placeholder || select.getAttribute('placeholder') || 'Wybierz';
            summary.append(placeholder);
            return;
        }
        selectedOptions.slice(0, displayCount).forEach((option) => {
            const value = document.createElement('span');
            value.className = multiple ? 'ui-searchable-select__value' : 'ui-searchable-select__single-value';
            value.textContent = option.text;
            summary.append(value);
        });
        if (selectedOptions.length > displayCount) {
            const overflow = document.createElement('span');
            overflow.className = 'ui-searchable-select__count';
            overflow.textContent = `+${selectedOptions.length - displayCount}`;
            summary.append(overflow);
        }
    };

    const renderChosen = () => {
        chosen.replaceChildren();
        if (!multiple) {
            chosen.hidden = true;
            return;
        }
        const selectedOptions = [...select.selectedOptions].filter((option) => option.value !== '');
        chosen.hidden = selectedOptions.length === 0;
        selectedOptions.forEach((option) => {
            const row = document.createElement('div');
            row.className = 'ui-searchable-select__chosen-row';
            const label = document.createElement('span');
            label.textContent = option.text;
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'ui-searchable-select__remove';
            remove.setAttribute('aria-label', `${select.dataset.removeLabel || 'Usuń'} ${option.text}`);
            remove.textContent = '×';
            remove.addEventListener('click', () => {
                option.selected = false;
                renderSummary();
                renderChosen();
                notifyChange();
                if (!dropdown.hidden) load();
            });
            row.append(label, remove);
            chosen.append(row);
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
        renderSummary();
        renderChosen();
        notifyChange();
        input.value = '';
        currentQuery = '';
        page = 1;
        if (multiple) load();
        else close();
    };

    const createOption = (item) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'ui-searchable-select__option';
        button.setAttribute('role', 'option');
        button.setAttribute('aria-selected', 'false');
        button.textContent = item.text;
        button.addEventListener('click', () => choose(item));
        return button;
    };

    const staticResults = () => [...select.options]
        .filter((option) => option.value !== '' && !option.selected)
        .filter((option) => option.text.toLocaleLowerCase().includes(currentQuery.toLocaleLowerCase()))
        .map((option) => ({
            id: option.value,
            text: option.text,
            group: option.parentElement instanceof HTMLOptGroupElement ? option.parentElement.label : '',
        }));

    const renderResults = (results, append = false, more = false) => {
        if (!append) options.replaceChildren();
        let lastGroup = null;
        results.filter((item) => !optionByValue(item.id)?.selected).forEach((item) => {
            if (item.group && item.group !== lastGroup) {
                const heading = document.createElement('p');
                heading.className = 'ui-searchable-select__group';
                heading.textContent = item.group;
                options.append(heading);
                lastGroup = item.group;
            }
            options.append(createOption(item));
        });
        if (more) {
            const moreButton = document.createElement('button');
            moreButton.type = 'button';
            moreButton.className = 'ui-searchable-select__option ui-searchable-select__more';
            moreButton.textContent = select.dataset.searchMore || 'Pokaż więcej';
            moreButton.addEventListener('click', () => {
                page += 1;
                load(true);
            });
            options.append(moreButton);
        }
        if (!options.children.length) {
            const empty = document.createElement('p');
            empty.className = 'ui-searchable-select__empty';
            empty.textContent = select.dataset.searchEmpty || 'Brak wyników';
            options.append(empty);
        }
    };

    const load = async (append = false) => {
        if (!select.dataset.searchUrl) {
            renderResults(staticResults(), append);
            return;
        }
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
            renderResults(payload.results || [], append, Boolean(payload.more));
        } catch (error) {
            if (error.name !== 'AbortError') renderResults([], false);
        }
    };

    const show = () => {
        if (select.disabled) return;
        currentQuery = '';
        input.value = '';
        page = 1;
        renderChosen();
        load();
        open();
    };

    control.addEventListener('click', () => dropdown.hidden ? show() : close());
    input.addEventListener('input', () => {
        clearTimeout(timer);
        currentQuery = input.value.trim();
        page = 1;
        timer = setTimeout(() => load(), 250);
    });
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            close();
            control.focus();
        }
    });
    document.addEventListener('click', (event) => {
        if (!component.contains(event.target)) close();
    });
    select.addEventListener('change', () => {
        renderSummary();
        renderChosen();
    });
    renderSummary();
    renderChosen();
};

export const initializeAdminFormComponents = () => {
    document.querySelectorAll(SEARCHABLE_SELECT_SELECTOR).forEach(initializeSearchableSelect);
};

document.addEventListener('turbo:load', initializeAdminFormComponents);
document.addEventListener('DOMContentLoaded', initializeAdminFormComponents);
initializeAdminFormComponents();

const observer = new MutationObserver((mutations) => {
    if (mutations.some((mutation) => [...mutation.addedNodes].some((node) => node.nodeType === Node.ELEMENT_NODE))) {
        initializeAdminFormComponents();
    }
});
if (document.documentElement) observer.observe(document.documentElement, { childList: true, subtree: true });

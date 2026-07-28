const initializeNewsletterRecipientPicker = () => {
    document.querySelectorAll('select[data-newsletter-user-picker]').forEach((select) => {
        if (select.dataset.pickerReady === 'true') return;
        select.dataset.pickerReady = 'true';
        select.hidden = true;

        const picker = document.createElement('div');
        picker.className = 'async-user-picker';
        picker.innerHTML = `
            <div class="async-user-picker__selected" data-selected></div>
            <div class="async-user-picker__search">
                <input type="search" autocomplete="off" role="combobox" aria-expanded="false">
                <div class="async-user-picker__results" data-results hidden></div>
            </div>`;
        select.after(picker);

        const selected = picker.querySelector('[data-selected]');
        const input = picker.querySelector('input');
        const results = picker.querySelector('[data-results]');
        input.placeholder = select.dataset.searchPlaceholder || '';
        let controller = null;
        let timer = null;
        let page = 1;
        let currentQuery = '';

        const optionByValue = (value) => [...select.options].find((option) => option.value === String(value));
        const renderSelected = () => {
            selected.replaceChildren();
            [...select.selectedOptions].forEach((option) => {
                const chip = document.createElement('span');
                chip.className = 'async-user-picker__chip';
                chip.append(document.createTextNode(option.text));
                const remove = document.createElement('button');
                remove.type = 'button';
                remove.setAttribute('aria-label', `Usuń ${option.text}`);
                remove.textContent = '×';
                remove.addEventListener('click', () => {
                    option.remove();
                    renderSelected();
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                });
                chip.append(remove);
                selected.append(chip);
            });
        };

        const addUser = (user) => {
            let option = optionByValue(user.id);
            if (!option) {
                option = new Option(user.text, String(user.id), true, true);
                select.add(option);
            }
            option.selected = true;
            renderSelected();
            select.dispatchEvent(new Event('change', { bubbles: true }));
            input.value = '';
            results.hidden = true;
            input.setAttribute('aria-expanded', 'false');
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
                if (!response.ok) throw new Error();
                const payload = await response.json();
                if (!append) results.replaceChildren();
                const available = payload.results.filter((user) => !optionByValue(user.id)?.selected);
                available.forEach((user) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.textContent = user.text;
                    button.addEventListener('click', () => addUser(user));
                    results.append(button);
                });
                if (payload.more) {
                    const more = document.createElement('button');
                    more.type = 'button';
                    more.className = 'async-user-picker__more';
                    more.textContent = select.dataset.searchMore;
                    more.addEventListener('click', () => { page += 1; load(true); });
                    results.append(more);
                }
                if (!results.children.length) {
                    const empty = document.createElement('p');
                    empty.textContent = select.dataset.searchEmpty;
                    results.append(empty);
                }
                results.hidden = false;
                input.setAttribute('aria-expanded', 'true');
            } catch (error) {
                if (error.name !== 'AbortError') results.hidden = true;
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
        document.addEventListener('click', (event) => {
            if (!picker.contains(event.target)) {
                results.hidden = true;
                input.setAttribute('aria-expanded', 'false');
            }
        });
        renderSelected();
    });
};

document.addEventListener('turbo:load', initializeNewsletterRecipientPicker);
initializeNewsletterRecipientPicker();

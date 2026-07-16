const root = document.querySelector('[data-component-builder]');

if (root) {
    const projectField = document.getElementById(root.dataset.projectField);
    const modeField = document.getElementById('page_editorMode');
    const form = root.closest('form');
    const list = root.querySelector('[data-builder-list]');
    const empty = root.querySelector('[data-builder-empty]');
    const panels = document.querySelectorAll('[data-editor-panel]');
    let blocks = [];

    const uid = () => globalThis.crypto?.randomUUID?.() ?? 'block-' + Date.now() + '-' + Math.random().toString(16).slice(2);
    const escape = (value = '') => String(value).replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
    const defaultCard = () => ({id: uid(), title: 'Nowa karta', text: 'Wpisz krótki opis.', url: '#', buttonLabel: 'Dowiedz się więcej', icon: 'document', color: 'blue'});
    const defaultFeatureCards = () => ({
        id: uid(), type: 'feature_cards', data: {heading: '', lead: '', columns: 3, items: [
            {...defaultCard(), title: 'Zarządzanie treścią', text: 'Twórz, edytuj i publikuj podstrony w intuicyjnym panelu administracyjnym.', buttonLabel: 'Otwórz panel'},
            {...defaultCard(), title: 'Modułowa architektura', text: 'Uruchamiaj tylko te funkcje, których potrzebuje konkretna organizacja.', icon: 'command', color: 'green'},
            {...defaultCard(), title: 'Bezpieczny rozwój', text: 'Nowoczesny fundament Symfony, kontrola dostępu i przewidywalne wdrożenia.', icon: 'target', color: 'orange'},
        ]},
    });

    try {
        const parsed = JSON.parse(projectField.value || '[]');
        blocks = Array.isArray(parsed) ? parsed.filter(block => block?.id && block?.type && block?.data) : [];
    } catch { blocks = []; }

    const field = (label, key, value, type = 'text', options = []) => {
        if (type === 'textarea') return '<label>' + escape(label) + '<textarea rows="3" data-field="' + key + '">' + escape(value) + '</textarea></label>';
        if (type === 'select') return '<label>' + escape(label) + '<select data-field="' + key + '">' + options.map(option => '<option value="' + escape(option[1]) + '"' + (value === option[1] ? ' selected' : '') + '>' + escape(option[0]) + '</option>').join('') + '</select></label>';
        return '<label>' + escape(label) + '<input type="' + type + '" value="' + escape(value) + '" data-field="' + key + '"></label>';
    };
    const icon = card => card.icon === 'command' ? '⌘' : card.icon === 'target' ? '◎' : card.icon === 'star' ? '☆' : '▤';
    const cardTemplate = (card, index) =>
        '<details class="component-card-editor" data-card-id="' + escape(card.id) + '"' + (index === 0 ? ' open' : '') + '>' +
        '<summary><span class="component-card-editor__icon component-card-editor__icon--' + escape(card.color) + '">' + icon(card) + '</span><span><strong>' + escape(card.title || 'Karta ' + (index + 1)) + '</strong><small>Karta ' + (index + 1) + '</small></span><b>⌄</b></summary>' +
        '<div class="component-card-editor__body"><div class="modern-form-grid">' +
        field('Nagłówek', 'title', card.title) + field('Ikona', 'icon', card.icon, 'select', [['Dokument','document'],['Command','command'],['Cel','target'],['Gwiazda','star']]) +
        '</div>' + field('Treść', 'text', card.text, 'textarea') + '<div class="modern-form-grid">' +
        field('Adres linku', 'url', card.url) + field('Tekst przycisku', 'buttonLabel', card.buttonLabel) + '</div>' +
        field('Kolor ikony', 'color', card.color, 'select', [['Niebieski','blue'],['Zielony','green'],['Pomarańczowy','orange'],['Fioletowy','purple']]) +
        '<div class="component-item-actions"><button type="button" data-move-card="-1">↑ Wyżej</button><button type="button" data-move-card="1">↓ Niżej</button><button class="is-danger" type="button" data-remove-card>Usuń kartę</button></div></div></details>';

    const blockTemplate = block => {
        if (block.type !== 'feature_cards') return '';
        const data = block.data;
        return '<section class="component-block" data-block-id="' + escape(block.id) + '">' +
            '<header class="component-block__header"><span class="component-block__handle">⋮⋮</span><div><strong>Karty funkcji</strong><small>' + (data.items?.length || 0) + ' kart</small></div><div class="component-item-actions"><button type="button" data-move-block="-1">↑</button><button type="button" data-move-block="1">↓</button><button class="is-danger" type="button" data-remove-block>Usuń sekcję</button></div></header>' +
            '<div class="component-block__settings"><div class="modern-form-grid">' + field('Nagłówek sekcji', 'heading', data.heading || '') +
            field('Liczba kolumn', 'columns', String(data.columns || 3), 'select', [['2 kolumny','2'],['3 kolumny','3'],['4 kolumny','4']]) +
            '</div>' + field('Wprowadzenie', 'lead', data.lead || '', 'textarea') + '</div><div class="component-block__items">' +
            (data.items || []).map(cardTemplate).join('') + '</div><button class="component-add-card" type="button" data-add-card><span>+</span> Dodaj kolejną kartę</button></section>';
    };

    const synchronize = () => { projectField.value = JSON.stringify(blocks); };
    const render = () => {
        list.innerHTML = blocks.map(blockTemplate).join('');
        empty.hidden = blocks.length > 0;
        synchronize();
    };
    const showMode = () => panels.forEach(panel => { panel.hidden = panel.dataset.editorPanel !== modeField.value; });

    root.querySelectorAll('[data-add-component]').forEach(button => button.addEventListener('click', () => {
        if (button.dataset.addComponent === 'feature_cards') blocks.push(defaultFeatureCards());
        render();
        list.lastElementChild?.scrollIntoView({behavior: 'smooth', block: 'center'});
    }));

    list.addEventListener('input', event => {
        const blockElement = event.target.closest('[data-block-id]');
        const block = blocks.find(item => item.id === blockElement?.dataset.blockId);
        if (!block || !event.target.dataset.field) return;
        const cardElement = event.target.closest('[data-card-id]');
        const target = cardElement ? block.data.items.find(item => item.id === cardElement.dataset.cardId) : block.data;
        target[event.target.dataset.field] = event.target.dataset.field === 'columns' ? Number(event.target.value) : event.target.value;
        synchronize();
        if (event.target.dataset.field === 'title') cardElement.querySelector('summary strong').textContent = event.target.value || 'Karta';
    });

    list.addEventListener('click', event => {
        const blockElement = event.target.closest('[data-block-id]');
        if (!blockElement) return;
        const blockIndex = blocks.findIndex(item => item.id === blockElement.dataset.blockId);
        const block = blocks[blockIndex];
        const cardElement = event.target.closest('[data-card-id]');
        const cardIndex = cardElement ? block.data.items.findIndex(item => item.id === cardElement.dataset.cardId) : -1;
        if (event.target.closest('[data-add-card]')) block.data.items.push(defaultCard());
        else if (event.target.closest('[data-remove-card]')) block.data.items.splice(cardIndex, 1);
        else if (event.target.closest('[data-remove-block]')) blocks.splice(blockIndex, 1);
        else if (event.target.closest('[data-move-card]')) {
            const next = cardIndex + Number(event.target.closest('[data-move-card]').dataset.moveCard);
            if (next >= 0 && next < block.data.items.length) [block.data.items[cardIndex], block.data.items[next]] = [block.data.items[next], block.data.items[cardIndex]];
        } else if (event.target.closest('[data-move-block]')) {
            const next = blockIndex + Number(event.target.closest('[data-move-block]').dataset.moveBlock);
            if (next >= 0 && next < blocks.length) [blocks[blockIndex], blocks[next]] = [blocks[next], blocks[blockIndex]];
        } else return;
        render();
    });

    modeField.addEventListener('change', showMode);
    form?.addEventListener('submit', synchronize);
    if (globalThis.tinymce) {
        globalThis.tinymce.init({
            selector: '[data-rich-text-editor]',
            language: 'pl',
            height: 560,
            menubar: 'edit view insert format tools table help',
            plugins: 'autolink lists link image table code preview searchreplace wordcount fullscreen',
            toolbar: 'undo redo | blocks | bold italic underline | alignleft aligncenter alignright | bullist numlist | link image table | code preview fullscreen',
            promotion: false,
            branding: false,
        });
    }
    showMode();
    render();
}

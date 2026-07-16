const initializeComponentBuilder = () => {
    const root = document.querySelector('[data-component-builder]');
    if (!root || root.dataset.componentBuilderInitialized === 'true') return;
    root.dataset.componentBuilderInitialized = 'true';
    const projectField = document.getElementById(root.dataset.projectField);
    const modeField = document.getElementById('page_editorMode');
    const form = root.closest('form');
    const list = root.querySelector('[data-builder-list]');
    const empty = root.querySelector('[data-builder-empty]');
    const panels = document.querySelectorAll('[data-editor-panel]');
    let blocks = [];

    const uid = () => globalThis.crypto?.randomUUID?.() ?? 'block-' + Date.now() + '-' + Math.random().toString(16).slice(2);
    const clone = value => JSON.parse(JSON.stringify(value));
    const escape = (value = '') => String(value).replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
    const text = (label, key) => ({label, key, type: 'text'});
    const area = (label, key) => ({label, key, type: 'textarea'});
    const select = (label, key, options) => ({label, key, type: 'select', options});
    const linkFields = [text('Adres linku', 'url'), text('Tekst przycisku', 'buttonLabel')];
    const cardFields = [text('Nagłówek', 'title'), area('Treść', 'text'), ...linkFields, select('Ikona', 'icon', [['Dokument','document'],['Command','command'],['Cel','target'],['Gwiazda','star']]), select('Kolor ikony', 'color', [['Niebieski','blue'],['Zielony','green'],['Pomarańczowy','orange'],['Fioletowy','purple']])];
    const stepFields = [text('Numer', 'number'), text('Nagłówek', 'title'), area('Opis', 'text')];

    const definitions = {
        hero: {
            label: 'Hero', itemLabel: '', fields: [text('Kotwica', 'anchor'), text('Etykieta', 'badge'), text('Pierwsza linia nagłówka', 'heading'), text('Wyróżniona linia', 'highlight'), text('Ostatnia linia', 'headingAfter'), area('Opis', 'text'), text('Główny przycisk', 'primaryLabel'), text('Link głównego przycisku', 'primaryUrl'), text('Drugi przycisk', 'secondaryLabel'), text('Link drugiego przycisku', 'secondaryUrl'), text('Nagłówek zaufania', 'trustTitle'), text('Opis zaufania', 'trustText'), text('Etykieta statystyki', 'floatingLabel'), text('Wartość statystyki', 'floatingValue'), text('Status', 'statusTitle'), text('Opis statusu', 'statusText')],
            defaults: {anchor:'start',badge:'Nowa generacja Shopro',heading:'Jedno miejsce.',highlight:'Pełna kontrola',headingAfter:'nad firmą.',text:'Nowoczesny system do zarządzania treścią, procesami i codzienną pracą zespołu. Elastyczny, modułowy i dopasowany do Twojej organizacji.',primaryLabel:'Poznaj możliwości',primaryUrl:'#mozliwosci',secondaryLabel:'Zobacz jak działa',secondaryUrl:'#jak-dziala',trustTitle:'Rozwijany z myślą o firmach',trustText:'które potrzebują czegoś więcej',floatingLabel:'Aktywne procesy',floatingValue:'+28.4%',statusTitle:'System działa',statusText:'Wszystkie usługi aktywne'}
        },
        logo_bar: {
            label:'Pasek marek', itemLabel:'markę', fields:[area('Tekst nad markami','text')], itemFields:[text('Nazwa','name')],
            defaults:{text:'Elastyczny fundament dla nowoczesnych organizacji',items:[{name:'WAVEBRAND'},{name:'ORANGE STUDIO'},{name:'SHOPRO CMS'},{name:'SYMFONY'},{name:'MODULAR'}]}
        },
        feature_cards: {
            label:'Karty funkcji', itemLabel:'kartę', fields:[text('Kotwica','anchor'),text('Etykieta','kicker'),text('Nagłówek','heading'),text('Wyróżniona linia','highlight'),area('Wprowadzenie','lead'),select('Liczba kolumn','columns',[['2 kolumny','2'],['3 kolumny','3'],['4 kolumny','4']])], itemFields:cardFields,
            defaults:{anchor:'mozliwosci',kicker:'MOŻLIWOŚCI',heading:'Wszystko, czego potrzebujesz.',highlight:'Bez zbędnego chaosu.',lead:'Shopro porządkuje pracę i daje zespołowi narzędzia, które rozwijają się razem z firmą.',columns:3,items:[
                {title:'Zarządzanie treścią',text:'Twórz, edytuj i publikuj podstrony w intuicyjnym panelu administracyjnym.',url:'/admin',buttonLabel:'Otwórz panel',icon:'document',color:'blue'},
                {title:'Modułowa architektura',text:'Uruchamiaj tylko te funkcje, których potrzebuje konkretna organizacja.',url:'#jak-dziala',buttonLabel:'Dowiedz się więcej',icon:'command',color:'green'},
                {title:'Bezpieczny rozwój',text:'Nowoczesny fundament Symfony, kontrola dostępu i przewidywalne wdrożenia.',url:'#jak-dziala',buttonLabel:'Poznaj technologię',icon:'target',color:'orange'}]}
        },
        process: {
            label:'Jak to działa',itemLabel:'krok',fields:[text('Kotwica','anchor'),text('Etykieta','kicker'),text('Nagłówek','heading'),text('Wyróżniona linia','highlight'),area('Opis','text'),text('Etykieta ilustracji','previewKicker'),text('Nagłówek ilustracji','previewTitle'),text('Druga linia ilustracji','previewSubtitle')],itemFields:stepFields,
            defaults:{anchor:'jak-dziala',kicker:'JAK TO DZIAŁA',heading:'Technologia pracuje w tle.',highlight:'Ty skupiasz się na firmie.',text:'Shopro łączy prostotę codziennej obsługi z solidną architekturą, która nie ogranicza kolejnych etapów rozwoju.',previewKicker:'TWÓJ SYSTEM',previewTitle:'Prosty w obsłudze.',previewSubtitle:'Gotowy na rozwój.',items:[{number:'1',title:'Wybierasz potrzebne funkcje',text:'System dopasowuje się do procesów organizacji.'},{number:'2',title:'Zespół pracuje w jednym miejscu',text:'Dane, treści i zadania pozostają uporządkowane.'},{number:'3',title:'Rozbudowujesz bez rewolucji',text:'Nowe moduły dochodzą wraz z rozwojem firmy.'}]}
        },
        audience: {
            label:'Dla kogo',itemLabel:'grupę',fields:[text('Kotwica','anchor'),text('Etykieta','kicker'),text('Nagłówek','heading'),text('Wyróżniona linia','highlight'),area('Opis','text')],itemFields:stepFields,
            defaults:{anchor:'dla-kogo',kicker:'DLA KOGO',heading:'Stworzone dla firm,',highlight:'które chcą działać sprawniej.',text:'Od prostego CMS po kompleksowe środowisko do zarządzania procesami.',items:[{number:'01',title:'Rosnące zespoły',text:'Potrzebują jednego źródła informacji i jasnych zasad pracy.'},{number:'02',title:'Firmy usługowe',text:'Chcą połączyć treści, klientów, pliki i procesy operacyjne.'},{number:'03',title:'Organizacje z własnym procesem',text:'Nie chcą dopasowywać firmy do sztywnego, gotowego systemu.'}]}
        },
        cta: {
            label:'Wezwanie do działania',itemLabel:'',fields:[text('Kotwica','anchor'),text('Etykieta','kicker'),text('Nagłówek','heading'),text('Druga linia nagłówka','headingAfter'),area('Opis','text'),text('Główny przycisk','primaryLabel'),text('Link głównego przycisku','primaryUrl'),text('Drugi przycisk','secondaryLabel'),text('Link drugiego przycisku','secondaryUrl')],
            defaults:{anchor:'kontakt',kicker:'SHOPRO 4.0',heading:'Zbudujmy system,',headingAfter:'który pracuje po Twojemu.',text:'Nowa wersja Shopro powstaje na stabilnym fundamencie i będzie rozwijana etapami.',primaryLabel:'Porozmawiajmy',primaryUrl:'mailto:kontakt@wavebrand.pl',secondaryLabel:'Przejdź do panelu',secondaryUrl:'/admin'}
        }
    };

    const createBlock = type => {
        const data = clone(definitions[type].defaults);
        if (data.items) data.items = data.items.map(item => ({id:uid(),...item}));
        return {id:uid(),type,data};
    };
    const homepagePreset = () => ['hero','logo_bar','feature_cards','process','audience','cta'].map(createBlock);

    try {
        const parsed = JSON.parse(projectField.value || '[]');
        blocks = Array.isArray(parsed) ? parsed.filter(block => definitions[block?.type] && block?.id && block?.data) : [];
    } catch { blocks = []; }

    const field = (definition, value, scope) => {
        const attr = ' data-field="' + definition.key + '" data-scope="' + scope + '"';
        if (definition.type === 'textarea') return '<label>' + escape(definition.label) + '<textarea rows="3"' + attr + '>' + escape(value) + '</textarea></label>';
        if (definition.type === 'select') return '<label>' + escape(definition.label) + '<select' + attr + '>' + definition.options.map(option => '<option value="' + escape(option[1]) + '"' + (String(value) === option[1] ? ' selected' : '') + '>' + escape(option[0]) + '</option>').join('') + '</select></label>';
        return '<label>' + escape(definition.label) + '<input type="text" value="' + escape(value) + '"' + attr + '></label>';
    };
    const fields = (schema, data, scope) => '<div class="component-fields">' + schema.map(definition => field(definition, data[definition.key] ?? '', scope)).join('') + '</div>';
    const itemTemplate = (item, index, definition) => '<details class="component-card-editor" data-item-id="' + escape(item.id) + '"' + (index === 0 ? ' open' : '') + '><summary><span class="component-card-editor__icon component-card-editor__icon--blue">' + (index + 1) + '</span><span><strong>' + escape(item.title || item.name || definition.itemLabel) + '</strong><small>Element ' + (index + 1) + '</small></span><b>⌄</b></summary><div class="component-card-editor__body">' + fields(definition.itemFields, item, 'item') + '<div class="component-item-actions"><button type="button" data-move-item="-1">↑ Wyżej</button><button type="button" data-move-item="1">↓ Niżej</button><button class="is-danger" type="button" data-remove-item>Usuń</button></div></div></details>';
    const blockTemplate = block => {
        const definition = definitions[block.type];
        const items = Array.isArray(block.data.items) ? block.data.items : [];
        return '<section class="component-block" data-block-id="' + escape(block.id) + '"><header class="component-block__header"><span class="component-block__handle">⋮⋮</span><div><strong>' + escape(definition.label) + '</strong><small>' + (definition.itemFields ? items.length + ' elementów' : 'Sekcja') + '</small></div><div class="component-item-actions"><button type="button" data-move-block="-1">↑</button><button type="button" data-move-block="1">↓</button><button class="is-danger" type="button" data-remove-block>Usuń sekcję</button></div></header><div class="component-block__settings">' + fields(definition.fields, block.data, 'block') + '</div>' + (definition.itemFields ? '<div class="component-block__items">' + items.map((item,index) => itemTemplate(item,index,definition)).join('') + '</div><button class="component-add-card" type="button" data-add-item><span>+</span> Dodaj ' + escape(definition.itemLabel) + '</button>' : '') + '</section>';
    };

    const synchronize = () => { projectField.value = JSON.stringify(blocks); };
    const render = () => { list.innerHTML = blocks.map(blockTemplate).join(''); empty.hidden = blocks.length > 0; synchronize(); };
    const showMode = () => panels.forEach(panel => { panel.hidden = panel.dataset.editorPanel !== modeField.value; });

    root.querySelectorAll('[data-add-component]').forEach(button => button.addEventListener('click', () => { blocks.push(createBlock(button.dataset.addComponent)); render(); list.lastElementChild?.scrollIntoView({behavior:'smooth',block:'center'}); }));
    root.querySelector('[data-add-preset]').addEventListener('click', () => {
        if (blocks.length && !globalThis.confirm('Zastąpić aktualny układ kompletną stroną główną?')) return;
        blocks = homepagePreset(); render();
    });
    list.addEventListener('input', event => {
        const block = blocks.find(item => item.id === event.target.closest('[data-block-id]')?.dataset.blockId);
        if (!block || !event.target.dataset.field) return;
        const itemElement = event.target.closest('[data-item-id]');
        const target = itemElement ? block.data.items.find(item => item.id === itemElement.dataset.itemId) : block.data;
        target[event.target.dataset.field] = event.target.dataset.field === 'columns' ? Number(event.target.value) : event.target.value;
        synchronize();
        if (itemElement && ['title','name'].includes(event.target.dataset.field)) itemElement.querySelector('summary strong').textContent = event.target.value || 'Element';
    });
    list.addEventListener('click', event => {
        const blockElement = event.target.closest('[data-block-id]'); if (!blockElement) return;
        const blockIndex = blocks.findIndex(item => item.id === blockElement.dataset.blockId); const block = blocks[blockIndex]; const definition = definitions[block.type];
        const itemElement = event.target.closest('[data-item-id]'); const itemIndex = itemElement ? block.data.items.findIndex(item => item.id === itemElement.dataset.itemId) : -1;
        if (event.target.closest('[data-add-item]')) { const item = {}; definition.itemFields.forEach(field => item[field.key] = ''); block.data.items.push({id:uid(),...item}); }
        else if (event.target.closest('[data-remove-item]')) block.data.items.splice(itemIndex,1);
        else if (event.target.closest('[data-remove-block]')) blocks.splice(blockIndex,1);
        else if (event.target.closest('[data-move-item]')) { const next=itemIndex+Number(event.target.closest('[data-move-item]').dataset.moveItem); if(next>=0&&next<block.data.items.length)[block.data.items[itemIndex],block.data.items[next]]=[block.data.items[next],block.data.items[itemIndex]]; }
        else if (event.target.closest('[data-move-block]')) { const next=blockIndex+Number(event.target.closest('[data-move-block]').dataset.moveBlock); if(next>=0&&next<blocks.length)[blocks[blockIndex],blocks[next]]=[blocks[next],blocks[blockIndex]]; }
        else return; render();
    });

    modeField.addEventListener('change', showMode); form?.addEventListener('submit', synchronize);
    if (globalThis.tinymce) globalThis.tinymce.init({selector:'[data-rich-text-editor]',language:'pl',height:560,menubar:'edit view insert format tools table help',plugins:'autolink lists link image table code preview searchreplace wordcount fullscreen',toolbar:'undo redo | blocks | bold italic underline | alignleft aligncenter alignright | bullist numlist | link image table | code preview fullscreen',promotion:false,branding:false});
    showMode(); render();
};

initializeComponentBuilder();
document.addEventListener('turbo:load', initializeComponentBuilder);
document.addEventListener('turbo:before-cache', () => {
    if (globalThis.tinymce) globalThis.tinymce.remove();
});

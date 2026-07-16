const initializeComponentBuilder = () => {
    const root = document.querySelector('[data-component-builder]');
    if (!root || root.dataset.componentBuilderInitialized === 'true') return;
    root.dataset.componentBuilderInitialized = 'true';
    const projectField = document.getElementById(root.dataset.projectField);
    const form = root.closest('form');
    const list = root.querySelector('[data-builder-list]');
    const empty = root.querySelector('[data-builder-empty]');
    let blocks = [];
    let selectedSlot = null;

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
        },
        rich_text: {
            label:'Tekst TinyMCE',itemLabel:'',fields:[{label:'Treść',key:'content',type:'richtext'}],
            defaults:{content:'<p>Rozpocznij pisanie treści…</p>'}
        }
    };

    const createComponent = type => {
        const data = clone(definitions[type].defaults);
        if (data.items) data.items = data.items.map(item => ({id:uid(),...item}));
        return {id:uid(),type,data};
    };
    const createSection = (layout = 'full', components = []) => ({id:uid(),type:'layout_section',data:{layout,columns:layout === 'full' ? [components] : [components,[]]}});
    const homepagePreset = () => ['hero','logo_bar','feature_cards','process','audience','cta'].map(type => createSection('full',[createComponent(type)]));
    try {
        const parsed = JSON.parse(projectField.value || '[]');
        if (Array.isArray(parsed)) blocks = parsed.map(block => block.type === 'layout_section' ? block : createSection('full',[block]));
    } catch { blocks = []; }
    if (!blocks.length) blocks = [createSection('full',[createComponent('rich_text')])];
    selectedSlot = {sectionId:blocks[0].id,column:0};

    const findComponent = id => {
        for (const section of blocks) for (const column of section.data.columns || []) {
            const component = column.find(item => item.id === id); if (component) return component;
        }
        return null;
    };
    const field = (definition,value,scope) => {
        const attr=' data-field="'+definition.key+'" data-scope="'+scope+'"';
        if(definition.type==='richtext')return '<label class="component-rich-field">'+escape(definition.label)+'<textarea rows="12" data-rich-text-component'+attr+'>'+escape(value)+'</textarea></label>';
        if(definition.type==='textarea')return '<label>'+escape(definition.label)+'<textarea rows="3"'+attr+'>'+escape(value)+'</textarea></label>';
        if(definition.type==='select')return '<label>'+escape(definition.label)+'<select'+attr+'>'+definition.options.map(option=>'<option value="'+escape(option[1])+'"'+(String(value)===option[1]?' selected':'')+'>'+escape(option[0])+'</option>').join('')+'</select></label>';
        return '<label>'+escape(definition.label)+'<input type="text" value="'+escape(value)+'"'+attr+'></label>';
    };
    const fields=(schema,data,scope)=>'<div class="component-fields">'+schema.map(definition=>field(definition,data[definition.key]??'',scope)).join('')+'</div>';
    const itemTemplate=(item,index,definition)=>'<details class="component-card-editor" data-item-id="'+escape(item.id)+'"><summary><span class="component-card-editor__icon component-card-editor__icon--blue">'+(index+1)+'</span><span><strong>'+escape(item.title||item.name||definition.itemLabel)+'</strong><small>Element '+(index+1)+'</small></span><b>⌄</b></summary><div class="component-card-editor__body">'+fields(definition.itemFields,item,'item')+'<div class="component-item-actions"><button type="button" data-move-item="-1">↑</button><button type="button" data-move-item="1">↓</button><button class="is-danger" type="button" data-remove-item>Usuń</button></div></div></details>';
    const componentTemplate=component=>{const definition=definitions[component.type];if(!definition)return '';const items=Array.isArray(component.data.items)?component.data.items:[];return '<details class="component-block" data-component-id="'+escape(component.id)+'"><summary class="component-block__header"><span class="component-block__handle">⋮⋮</span><div><strong>'+escape(definition.label)+'</strong><small>'+items.length+(definition.itemFields?' elementów':'')+'</small></div><div class="component-item-actions"><button type="button" data-move-component="-1">↑</button><button type="button" data-move-component="1">↓</button><button class="is-danger" type="button" data-remove-component>Usuń</button></div><b>⌄</b></summary><div class="component-block__settings">'+fields(definition.fields,component.data,'component')+'</div>'+(definition.itemFields?'<div class="component-block__items">'+items.map((item,index)=>itemTemplate(item,index,definition)).join('')+'</div><button class="component-add-card" type="button" data-add-item>+ Dodaj '+escape(definition.itemLabel)+'</button>':'')+'</details>';};
    const sectionTemplate=(section,index)=>'<details class="builder-section" data-section-id="'+escape(section.id)+'" open><summary class="builder-section__header"><span>Sekcja '+(index+1)+'</span><strong>'+escape(section.data.layout.replace('_',' / '))+'</strong><div class="component-item-actions"><button type="button" data-move-section="-1">↑</button><button type="button" data-move-section="1">↓</button><button class="is-danger" type="button" data-remove-section>Usuń</button></div><b>⌄</b></summary><div class="builder-section__columns builder-section__columns--'+escape(section.data.layout)+'">'+section.data.columns.map((column,columnIndex)=>'<div class="builder-section__column'+(selectedSlot?.sectionId===section.id&&selectedSlot.column===columnIndex?' is-selected':'')+'" data-column="'+columnIndex+'"><small>Kliknij, aby wybrać kolumnę</small>'+column.map(componentTemplate).join('')+'</div>').join('')+'</div></details>';
    const synchronize=()=>{projectField.value=JSON.stringify(blocks);};
    const initializeTiny=()=>{if(!globalThis.tinymce)return;root.querySelectorAll('[data-rich-text-component]').forEach(textarea=>globalThis.tinymce.init({target:textarea,height:360,menubar:false,plugins:'autolink lists link image table code preview',toolbar:'undo redo | blocks | bold italic | bullist numlist | link image table | code',promotion:false,branding:false,setup:editor=>editor.on('change keyup',()=>{const component=findComponent(textarea.closest('[data-component-id]').dataset.componentId);if(component){component.data.content=editor.getContent();synchronize();}})}));};
    const render=()=>{if(globalThis.tinymce)globalThis.tinymce.remove();list.innerHTML=blocks.map(sectionTemplate).join('');empty.hidden=blocks.length>0;synchronize();initializeTiny();};
    const selectedColumn=()=>{const section=blocks.find(item=>item.id===selectedSlot?.sectionId);return section?.data.columns[selectedSlot.column];};

    root.querySelectorAll('[data-add-section]').forEach(button=>button.addEventListener('click',()=>{const section=createSection(button.dataset.addSection);blocks.push(section);selectedSlot={sectionId:section.id,column:0};render();}));
    root.querySelectorAll('[data-add-component]').forEach(button=>button.addEventListener('click',()=>{let column=selectedColumn();if(!column){const section=createSection();blocks.push(section);selectedSlot={sectionId:section.id,column:0};column=section.data.columns[0];}column.push(createComponent(button.dataset.addComponent));render();}));
    root.querySelector('[data-add-preset]').addEventListener('click',()=>{if(blocks.length&&!globalThis.confirm('Zastąpić aktualny układ kompletną stroną główną?'))return;blocks=homepagePreset();selectedSlot={sectionId:blocks[0].id,column:0};render();});
    list.addEventListener('input',event=>{const component=findComponent(event.target.closest('[data-component-id]')?.dataset.componentId);if(!component||!event.target.dataset.field)return;const itemElement=event.target.closest('[data-item-id]');const target=itemElement?component.data.items.find(item=>item.id===itemElement.dataset.itemId):component.data;target[event.target.dataset.field]=event.target.dataset.field==='columns'?Number(event.target.value):event.target.value;synchronize();});
    list.addEventListener('click',event=>{
        const sectionElement=event.target.closest('[data-section-id]');if(!sectionElement)return;const sectionIndex=blocks.findIndex(item=>item.id===sectionElement.dataset.sectionId);const section=blocks[sectionIndex];
        const columnElement=event.target.closest('[data-column]');if(columnElement&&!event.target.closest('button,summary,input,textarea,select,label')){selectedSlot={sectionId:section.id,column:Number(columnElement.dataset.column)};render();return;}
        const componentElement=event.target.closest('[data-component-id]');const component=componentElement?findComponent(componentElement.dataset.componentId):null;const column=component?section.data.columns.find(items=>items.some(item=>item.id===component.id)):null;const componentIndex=column?.findIndex(item=>item.id===component.id)??-1;
        const itemElement=event.target.closest('[data-item-id]');const itemIndex=itemElement&&component?component.data.items.findIndex(item=>item.id===itemElement.dataset.itemId):-1;const definition=component?definitions[component.type]:null;
        if(event.target.closest('[data-remove-section]'))blocks.splice(sectionIndex,1);
        else if(event.target.closest('[data-move-section]')){const next=sectionIndex+Number(event.target.closest('[data-move-section]').dataset.moveSection);if(next>=0&&next<blocks.length)[blocks[sectionIndex],blocks[next]]=[blocks[next],blocks[sectionIndex]];}
        else if(event.target.closest('[data-remove-component]'))column.splice(componentIndex,1);
        else if(event.target.closest('[data-move-component]')){const next=componentIndex+Number(event.target.closest('[data-move-component]').dataset.moveComponent);if(next>=0&&next<column.length)[column[componentIndex],column[next]]=[column[next],column[componentIndex]];}
        else if(event.target.closest('[data-add-item]')){const item={id:uid()};definition.itemFields.forEach(field=>item[field.key]='');component.data.items.push(item);}
        else if(event.target.closest('[data-remove-item]'))component.data.items.splice(itemIndex,1);
        else if(event.target.closest('[data-move-item]')){const next=itemIndex+Number(event.target.closest('[data-move-item]').dataset.moveItem);if(next>=0&&next<component.data.items.length)[component.data.items[itemIndex],component.data.items[next]]=[component.data.items[next],component.data.items[itemIndex]];}
        else return;render();
    });
    form?.addEventListener('submit',()=>{if(globalThis.tinymce)globalThis.tinymce.editors.forEach(editor=>{const component=findComponent(editor.targetElm.closest('[data-component-id]')?.dataset.componentId);if(component)component.data.content=editor.getContent();});synchronize();});
    render();
};

initializeComponentBuilder();
document.addEventListener('turbo:load', initializeComponentBuilder);
document.addEventListener('turbo:before-cache', () => {
    if (globalThis.tinymce) globalThis.tinymce.remove();
});

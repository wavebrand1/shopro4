import Quill from 'quill';
import 'quill/dist/quill.snow.css';

const initializeComponentBuilder = () => {
    const root = document.querySelector('[data-component-builder]');
    if (!root || root.dataset.componentBuilderInitialized === 'true') return;
    root.dataset.componentBuilderInitialized = 'true';
    const projectField = document.getElementById(root.dataset.projectField);
    const form = root.closest('form');
    const list = root.querySelector('[data-builder-list]');
    const empty = root.querySelector('[data-builder-empty]');
    const systemRoleRequired = root.dataset.systemRoleRequired === 'true';
    let blocks = [];
    let selectedSlot = null;
    let draggedComponentId = null;
    let mediaPickerTarget = null;
    let dirty = false;
    let submitting = false;
    const language = document.documentElement.lang || 'pl';
    const t = (pl, en) => language.startsWith('en') ? en : pl;

    const uid = () => globalThis.crypto?.randomUUID?.() ?? 'block-' + Date.now() + '-' + Math.random().toString(16).slice(2);
    const clone = value => JSON.parse(JSON.stringify(value));
    const escape = (value = '') => String(value).replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
    const text = (label, key) => ({label, key, type: 'text'});
    const area = (label, key) => ({label, key, type: 'textarea'});
    const select = (label, key, options) => ({label, key, type: 'select', options});
    const media = (label, key) => ({label, key, type: 'media'});
    const linkFields = [text(t('Adres linku','Link URL'), 'url'), text(t('Tekst przycisku','Button label'), 'buttonLabel')];
    const cardFields = [text(t('Nagłówek','Heading'), 'title'), area(t('Treść','Content'), 'text'), ...linkFields, select(t('Ikona','Icon'), 'icon', [[t('Dokument','Document'),'document'],['Command','command'],[t('Cel','Target'),'target'],[t('Gwiazda','Star'),'star']]), select(t('Kolor ikony','Icon color'), 'color', [[t('Niebieski','Blue'),'blue'],[t('Zielony','Green'),'green'],[t('Pomarańczowy','Orange'),'orange'],[t('Fioletowy','Violet'),'purple']])];
    const stepFields = [text(t('Numer','Number'), 'number'), text(t('Nagłówek','Heading'), 'title'), area(t('Opis','Description'), 'text')];

    const definitions = {
        hero: {
            label: 'Hero', itemLabel: '', fields: [text(t('Kotwica','Anchor'), 'anchor'), text(t('Etykieta','Label'), 'badge'), text(t('Pierwsza linia nagłówka','First heading line'), 'heading'), text(t('Wyróżniona linia','Highlighted line'), 'highlight'), text(t('Ostatnia linia','Last heading line'), 'headingAfter'), area(t('Opis','Description'), 'text'), text(t('Główny przycisk','Primary button'), 'primaryLabel'), text(t('Link głównego przycisku','Primary button URL'), 'primaryUrl'), text(t('Drugi przycisk','Secondary button'), 'secondaryLabel'), text(t('Link drugiego przycisku','Secondary button URL'), 'secondaryUrl'), text(t('Nagłówek zaufania','Trust heading'), 'trustTitle'), text(t('Opis zaufania','Trust description'), 'trustText'), text(t('Etykieta statystyki','Statistic label'), 'floatingLabel'), text(t('Wartość statystyki','Statistic value'), 'floatingValue'), text('Status', 'statusTitle'), text(t('Opis statusu','Status description'), 'statusText')],
            defaults: {anchor:'start',badge:'Nowa generacja Shopro',heading:'Jedno miejsce.',highlight:'Pełna kontrola',headingAfter:'nad firmą.',text:'Nowoczesny system do zarządzania treścią, procesami i codzienną pracą zespołu. Elastyczny, modułowy i dopasowany do Twojej organizacji.',primaryLabel:'Poznaj możliwości',primaryUrl:'#mozliwosci',secondaryLabel:'Zobacz jak działa',secondaryUrl:'#jak-dziala',trustTitle:'Rozwijany z myślą o firmach',trustText:'które potrzebują czegoś więcej',floatingLabel:'Aktywne procesy',floatingValue:'+28.4%',statusTitle:'System działa',statusText:'Wszystkie usługi aktywne'}
        },
        logo_bar: {
            label:t('Pasek marek','Brand bar'), itemLabel:t('markę','brand'), fields:[area(t('Tekst nad markami','Text above brands'),'text')], itemFields:[text(t('Nazwa','Name'),'name')],
            defaults:{text:'Elastyczny fundament dla nowoczesnych organizacji',items:[{name:'WAVEBRAND'},{name:'ORANGE STUDIO'},{name:'SHOPRO CMS'},{name:'SYMFONY'},{name:'MODULAR'}]}
        },
        feature_cards: {
            label:t('Karty funkcji','Feature cards'), itemLabel:t('kartę','card'), fields:[text(t('Kotwica','Anchor'),'anchor'),text(t('Etykieta','Label'),'kicker'),text(t('Nagłówek','Heading'),'heading'),text(t('Wyróżniona linia','Highlighted line'),'highlight'),area(t('Wprowadzenie','Introduction'),'lead'),select(t('Liczba kolumn','Column count'),'columns',[[t('2 kolumny','2 columns'),'2'],[t('3 kolumny','3 columns'),'3'],[t('4 kolumny','4 columns'),'4']])], itemFields:cardFields,
            defaults:{anchor:'mozliwosci',kicker:'MOŻLIWOŚCI',heading:'Wszystko, czego potrzebujesz.',highlight:'Bez zbędnego chaosu.',lead:'Shopro porządkuje pracę i daje zespołowi narzędzia, które rozwijają się razem z firmą.',columns:3,items:[
                {title:'Zarządzanie treścią',text:'Twórz, edytuj i publikuj podstrony w intuicyjnym panelu administracyjnym.',url:'/admin',buttonLabel:'Otwórz panel',icon:'document',color:'blue'},
                {title:'Modułowa architektura',text:'Uruchamiaj tylko te funkcje, których potrzebuje konkretna organizacja.',url:'#jak-dziala',buttonLabel:'Dowiedz się więcej',icon:'command',color:'green'},
                {title:'Bezpieczny rozwój',text:'Nowoczesny fundament Symfony, kontrola dostępu i przewidywalne wdrożenia.',url:'#jak-dziala',buttonLabel:'Poznaj technologię',icon:'target',color:'orange'}]}
        },
        process: {
            label:t('Jak to działa','How it works'),itemLabel:t('krok','step'),fields:[text(t('Kotwica','Anchor'),'anchor'),text(t('Etykieta','Label'),'kicker'),text(t('Nagłówek','Heading'),'heading'),text(t('Wyróżniona linia','Highlighted line'),'highlight'),area(t('Opis','Description'),'text'),text(t('Etykieta ilustracji','Preview label'),'previewKicker'),text(t('Nagłówek ilustracji','Preview heading'),'previewTitle'),text(t('Druga linia ilustracji','Preview second line'),'previewSubtitle')],itemFields:stepFields,
            defaults:{anchor:'jak-dziala',kicker:'JAK TO DZIAŁA',heading:'Technologia pracuje w tle.',highlight:'Ty skupiasz się na firmie.',text:'Shopro łączy prostotę codziennej obsługi z solidną architekturą, która nie ogranicza kolejnych etapów rozwoju.',previewKicker:'TWÓJ SYSTEM',previewTitle:'Prosty w obsłudze.',previewSubtitle:'Gotowy na rozwój.',items:[{number:'1',title:'Wybierasz potrzebne funkcje',text:'System dopasowuje się do procesów organizacji.'},{number:'2',title:'Zespół pracuje w jednym miejscu',text:'Dane, treści i zadania pozostają uporządkowane.'},{number:'3',title:'Rozbudowujesz bez rewolucji',text:'Nowe moduły dochodzą wraz z rozwojem firmy.'}]}
        },
        audience: {
            label:t('Dla kogo','Audience'),itemLabel:t('grupę','group'),fields:[text(t('Kotwica','Anchor'),'anchor'),text(t('Etykieta','Label'),'kicker'),text(t('Nagłówek','Heading'),'heading'),text(t('Wyróżniona linia','Highlighted line'),'highlight'),area(t('Opis','Description'),'text')],itemFields:stepFields,
            defaults:{anchor:'dla-kogo',kicker:'DLA KOGO',heading:'Stworzone dla firm,',highlight:'które chcą działać sprawniej.',text:'Od prostego CMS po kompleksowe środowisko do zarządzania procesami.',items:[{number:'01',title:'Rosnące zespoły',text:'Potrzebują jednego źródła informacji i jasnych zasad pracy.'},{number:'02',title:'Firmy usługowe',text:'Chcą połączyć treści, klientów, pliki i procesy operacyjne.'},{number:'03',title:'Organizacje z własnym procesem',text:'Nie chcą dopasowywać firmy do sztywnego, gotowego systemu.'}]}
        },
        cta: {
            label:t('Wezwanie do działania','Call to action'),itemLabel:'',fields:[text(t('Kotwica','Anchor'),'anchor'),text(t('Etykieta','Label'),'kicker'),text(t('Nagłówek','Heading'),'heading'),text(t('Druga linia nagłówka','Second heading line'),'headingAfter'),area(t('Opis','Description'),'text'),text(t('Główny przycisk','Primary button'),'primaryLabel'),text(t('Link głównego przycisku','Primary button URL'),'primaryUrl'),text(t('Drugi przycisk','Secondary button'),'secondaryLabel'),text(t('Link drugiego przycisku','Secondary button URL'),'secondaryUrl')],
            defaults:{anchor:'kontakt',kicker:'SHOPRO 4.0',heading:'Zbudujmy system,',headingAfter:'który pracuje po Twojemu.',text:'Nowa wersja Shopro powstaje na stabilnym fundamencie i będzie rozwijana etapami.',primaryLabel:'Porozmawiajmy',primaryUrl:'mailto:kontakt@wavebrand.pl',secondaryLabel:'Przejdź do panelu',secondaryUrl:'/admin'}
        },
        rich_text: {
            label:t('Edytor tekstu','Text editor'),itemLabel:'',fields:[{label:t('Treść','Content'),key:'content',type:'richtext'}],
            defaults:{content:'<p>Rozpocznij pisanie treści…</p>'}
        },
        system_role: {
            label:t('Komponent roli strony','Page role component'),itemLabel:'',fields:[],
            defaults:{}
        },
        image: {
            label:t('Obraz','Image'),itemLabel:'',fields:[media(t('Plik obrazu','Image file'),'src'),text(t('Tekst alternatywny','Alternative text'),'alt'),area(t('Podpis','Caption'),'caption'),select(t('Proporcje','Aspect ratio'),'ratio',[[t('Oryginalne','Original'),'auto'],['16:9','16/9'],['4:3','4/3'],['1:1','1/1']]),select(t('Dopasowanie','Fit'),'fit',[[t('Wypełnij','Cover'),'cover'],[t('Pokaż cały obraz','Contain'),'contain']]),select(t('Ładowanie','Loading'),'loading',[[t('Leniwe','Lazy'),'lazy'],[t('Priorytetowe','Priority'),'eager']])],
            defaults:{src:'',alt:'',caption:'',ratio:'auto',fit:'cover',loading:'lazy'}
        },
        ...(globalThis.ShoproThemeComponents ?? {})
    };

    const createComponent = type => {
        const data = clone(definitions[type].defaults);
        if (data.items) data.items = data.items.map(item => ({id:uid(),...item}));
        return {id:uid(),type,data};
    };
    const hydrateComponent = component => {
        const definition = definitions[component?.type];
        if (!definition) return component;
        const storedData = component.data && typeof component.data === 'object' ? component.data : {};
        const hasStoredItems = Array.isArray(storedData.items) && storedData.items.length > 0;
        const data = {...clone(definition.defaults || {}), ...storedData};
        if (typeof definition.normalizeData === 'function') definition.normalizeData(data);
        if (Array.isArray(data.items)) {
            if (!hasStoredItems && Array.isArray(definition.defaults?.items)) data.items = clone(definition.defaults.items);
            data.items = data.items.map(item => {
                const normalized = item && typeof item === 'object' ? item : {};

                return {...normalized, id: normalized.id || uid()};
            });
        }
        component.data = data;
        return component;
    };
    const duplicateComponent = component => {
        const copy = clone(component);
        copy.id = uid();
        if (Array.isArray(copy.data?.items)) copy.data.items = copy.data.items.map(item => ({...item, id:uid()}));
        return copy;
    };
    const createSection = (container = 'grid', components = []) => ({id:uid(),type:'layout_section',data:{container,columns:[components],widths:[100]}});
    const duplicateSection = section => {
        const copy = clone(section);
        copy.id = uid();
        copy.data.columns = copy.data.columns.map(column => column.map(duplicateComponent));
        return copy;
    };
    const normalizeSection = section => {
        if (!Array.isArray(section.data.columns) || !section.data.columns.length) section.data.columns=[[]];
        section.data.columns = section.data.columns.map(column => Array.isArray(column) ? column.map(hydrateComponent) : []);
        if (!section.data.container) section.data.container=section.data.layout === 'full' ? 'full' : 'grid';
        if (!Array.isArray(section.data.widths)) section.data.widths=section.data.layout === '70_30' ? [70,30] : section.data.layout === '50_50' ? [50,50] : section.data.columns.map(()=>Math.round(100/section.data.columns.length));
        delete section.data.layout; return section;
    };
    const defaultHomepagePreset = () => ['hero','logo_bar','feature_cards','process','audience','cta'].map(type => createSection('full',[createComponent(type)]));
    const createPreset = type => {
        if (type === 'homepage') return defaultHomepagePreset();
        const preset = globalThis.ShoproThemePresets?.[type];
        if (!preset || !Array.isArray(preset.sections)) return null;

        return preset.sections.map(section => createSection(
            section.container === 'grid' ? 'grid' : 'full',
            (Array.isArray(section.components) ? section.components : []).filter(componentType => definitions[componentType]).map(createComponent),
        ));
    };
    try {
        const parsed = JSON.parse(projectField.value || '[]');
        if (Array.isArray(parsed)) blocks = parsed.map(block => normalizeSection(block.type === 'layout_section' ? block : createSection('full',[block])));
    } catch { blocks = []; }
    if (!blocks.length) blocks = [createSection('grid',[createComponent('rich_text')])];
    selectedSlot = {sectionId:blocks[0].id,column:0};

    const findComponent = id => {
        for (const section of blocks) for (const column of section.data.columns || []) {
            const component = column.find(item => item.id === id); if (component) return component;
        }
        return null;
    };
    const findComponentLocationByType = type => {
        for (const section of blocks) for (const column of section.data.columns || []) {
            const component = column.find(item => item.type === type); if (component) return {section,column,component};
        }
        return null;
    };
    const sectionHasProtectedSystemRole = section => systemRoleRequired && (section.data.columns || []).some(column => column.some(component => component.type === 'system_role'));
    const findComponentLocation = id => {
        for (const section of blocks) for (let columnIndex=0;columnIndex<section.data.columns.length;columnIndex++) {
            const column=section.data.columns[columnIndex];
            const componentIndex=column.findIndex(item=>item.id===id);
            if(componentIndex>=0)return {section,column,columnIndex,componentIndex};
        }
        return null;
    };
    const field = (definition,value,scope) => {
        const attr=' data-field="'+definition.key+'" data-scope="'+scope+'"';
        if(definition.type==='richtext')return '<div class="component-rich-field"><div class="component-rich-field__heading"><span>'+escape(definition.label)+'</span><button type="button" class="modern-button" data-rich-text-source-toggle>'+t('Edytuj HTML','Edit HTML')+'</button></div><div class="component-quill-editor" data-rich-text-component data-content="'+escape(value)+'"'+attr+'></div><textarea class="component-rich-source" rows="14" data-rich-text-source'+attr+' hidden>'+escape(value)+'</textarea></div>';
        if(definition.type==='textarea')return '<label>'+escape(definition.label)+'<textarea rows="3"'+attr+'>'+escape(value)+'</textarea></label>';
        if(definition.type==='checkbox'){const checked=value===true||value===1||value==='1'||value==='true';return '<label class="component-checkbox-field"><input type="checkbox"'+(checked?' checked':'')+attr+'><span>'+escape(definition.label)+'</span></label>';}
        if(definition.type==='select')return '<label>'+escape(definition.label)+'<select'+attr+'>'+definition.options.map(option=>'<option value="'+escape(option[1])+'"'+(String(value)===option[1]?' selected':'')+'>'+escape(option[0])+'</option>').join('')+'</select></label>';
        if(definition.type==='media')return '<label class="component-media-field">'+escape(definition.label)+'<span><input type="text" value="'+escape(value)+'" placeholder="/uploads/..."'+attr+'><button class="modern-button" type="button" data-media-picker data-media-key="'+escape(definition.key)+'">'+t('Wybierz plik','Select file')+'</button></span></label>';
        return '<label>'+escape(definition.label)+'<input type="text" value="'+escape(value)+'"'+attr+'></label>';
    };
    const fields=(schema,data,scope)=>'<div class="component-fields">'+schema.map(definition=>field(definition,data[definition.key]??'',scope)).join('')+'</div>';
    const itemTemplate=(item,index,definition)=>'<details class="component-card-editor" data-item-id="'+escape(item.id)+'"><summary><span class="component-card-editor__icon component-card-editor__icon--blue">'+(index+1)+'</span><span><strong>'+escape(item.title||item.name||definition.itemLabel)+'</strong><small>'+t('Element ','Item ')+(index+1)+'</small></span><b>⌄</b></summary><div class="component-card-editor__body">'+fields(definition.itemFields,item,'item')+'<div class="component-item-actions"><button type="button" data-move-item="-1">↑</button><button type="button" data-move-item="1">↓</button><button class="is-danger" type="button" data-remove-item>'+t('Usuń','Delete')+'</button></div></div></details>';
    const componentTemplate=component=>{const definition=definitions[component.type];if(!definition)return '';const items=Array.isArray(component.data.items)?component.data.items:[];const protectedRole=systemRoleRequired&&component.type==='system_role';return '<details class="component-block" data-component-id="'+escape(component.id)+'"><summary class="component-block__header"><span class="component-block__handle" draggable="true" title="'+t('Przeciągnij komponent','Drag component')+'">⋮⋮</span><div><strong>'+escape(definition.label)+'</strong><small>'+(protectedRole?t('Wymagany element strony systemowej','Required system page element'):items.length+(definition.itemFields?t(' elementów',' items'):''))+'</small></div><div class="component-item-actions"><button type="button" data-move-component="-1" title="'+t('Przenieś wyżej','Move up')+'">↑</button><button type="button" data-move-component="1" title="'+t('Przenieś niżej','Move down')+'">↓</button>'+(component.type==='system_role'?'':'<button type="button" data-duplicate-component>'+t('Duplikuj','Duplicate')+'</button>')+(protectedRole?'':'<button class="is-danger" type="button" data-remove-component>'+t('Usuń','Delete')+'</button>')+'</div><b>⌄</b></summary><div class="component-block__settings">'+fields(definition.fields,component.data,'component')+'</div>'+(definition.itemFields?'<div class="component-block__items">'+items.map((item,index)=>itemTemplate(item,index,definition)).join('')+'</div><button class="component-add-card" type="button" data-add-item>+ '+t('Dodaj ','Add ')+escape(definition.itemLabel)+'</button>':'')+'</details>';};
    const sectionTemplate = (section, index) => {
        const protectedRole = sectionHasProtectedSystemRole(section);
        const columns = section.data.columns.map((column, columnIndex) => '<div class="builder-section__column'+(selectedSlot?.sectionId===section.id&&selectedSlot.column===columnIndex?' is-selected':'')+'" data-column="'+columnIndex+'"><div class="builder-column-toolbar"><strong>'+t('Kolumna ','Column ')+(columnIndex+1)+'</strong><label>'+t('Szerokość kolumny ','Column width ')+'<span><input type="number" min="1" max="100" value="'+escape(section.data.widths[columnIndex]||1)+'" data-column-width="'+columnIndex+'">%</span></label><button type="button" class="is-danger" data-remove-column="'+columnIndex+'"'+(section.data.columns.length===1?' disabled':'')+'>'+t('Usuń kolumnę','Delete column')+'</button></div>'+column.map(componentTemplate).join('')+'</div>').join('');

        return '<details class="builder-section" data-section-id="'+escape(section.id)+'" open>'+
            '<summary class="builder-section__header"><span><strong>'+t('Sekcja ','Section ')+(index+1)+'</strong><small>'+section.data.columns.length+' '+(section.data.columns.length===1?t('kolumna','column'):t('kolumny','columns'))+'</small></span><div class="component-item-actions"><button type="button" data-move-section="-1" title="'+t('Przenieś wyżej','Move up')+'">↑</button><button type="button" data-move-section="1" title="'+t('Przenieś niżej','Move down')+'">↓</button>'+(protectedRole?'':'<button type="button" data-duplicate-section>'+t('Duplikuj','Duplicate')+'</button><button class="is-danger" type="button" data-remove-section>'+t('Usuń sekcję','Delete section')+'</button>')+'</div><b>⌄</b></summary>'+
            '<div class="builder-section__settings"><div><strong>'+t('Ustawienia sekcji','Section settings')+'</strong><small>'+t('Określ szerokość sekcji i liczbę kolumn.','Set the section width and number of columns.')+'</small></div><label>'+t('Szerokość sekcji','Section width')+'<select data-native-select data-section-field="container"><option value="grid"'+(section.data.container==='grid'?' selected':'')+'>'+t('W gridzie strony','Website grid')+'</option><option value="full"'+(section.data.container==='full'?' selected':'')+'>'+t('Pełna szerokość ekranu','Full screen width')+'</option></select></label><button class="modern-button modern-button--primary" type="button" data-add-column>+ '+t('Dodaj kolumnę','Add column')+'</button></div>'+
            '<div class="builder-section__columns" style="grid-template-columns:'+section.data.widths.map(width=>Math.max(1,Number(width)||1)+'fr').join(' ')+'">'+columns+'</div></details>';
    };
    const synchronize=()=>{projectField.value=JSON.stringify(blocks);};
    const initializeEditors=()=>root.querySelectorAll('[data-rich-text-component]').forEach(element=>{const editor=new Quill(element,{theme:'snow',modules:{toolbar:[[{header:[1,2,3,false]}],['bold','italic','underline','strike'],[{list:'ordered'},{list:'bullet'}],['blockquote','link'],[{align:[]}],['clean']]}});element.__shoproQuill=editor;editor.clipboard.dangerouslyPasteHTML(element.dataset.content||'');editor.on('text-change',()=>{const component=findComponent(element.closest('[data-component-id]').dataset.componentId);if(component){component.data.content=editor.root.innerHTML;const source=element.parentElement.querySelector('[data-rich-text-source]');if(source&&!source.hidden)source.value=component.data.content;markDirty();synchronize();}});});
    const render=()=>{list.innerHTML=blocks.map(sectionTemplate).join('');empty.hidden=blocks.length>0;synchronize();initializeEditors();};
    const selectedColumn=()=>{const section=blocks.find(item=>item.id===selectedSlot?.sectionId);return section?.data.columns[selectedSlot.column];};

    const markDirty=()=>{dirty=true;};
    root.querySelectorAll('[data-add-section]').forEach(button=>button.addEventListener('click',()=>{const section=createSection();blocks.push(section);selectedSlot={sectionId:section.id,column:0};markDirty();render();}));
    root.querySelectorAll('[data-add-component]').forEach(button=>button.addEventListener('click',()=>{
        const type=button.dataset.addComponent;
        if(type==='system_role'&&findComponentLocationByType('system_role')){globalThis.alert(t('Komponent roli strony może wystąpić tylko raz.','The page role component can only be added once.'));return;}
        let column=selectedColumn();if(!column){const section=createSection();blocks.push(section);selectedSlot={sectionId:section.id,column:0};column=section.data.columns[0];}column.push(createComponent(type));markDirty();render();
    }));
    root.querySelectorAll('[data-add-preset]').forEach(button => button.addEventListener('click',()=>{
        const preset = createPreset(button.dataset.addPreset);
        if (!preset?.length) {
            globalThis.alert(t('Ten układ nie jest dostępny dla aktywnej skórki.','This layout is not available for the active theme.'));
            return;
        }
        if(blocks.length&&!globalThis.confirm(t('Zastąpić aktualny układ kompletną stroną główną?','Replace the current layout with the complete homepage?')))return;
        blocks=preset;selectedSlot={sectionId:blocks[0].id,column:0};markDirty();render();
    }));
    const updateField = event=>{const section=blocks.find(item=>item.id===event.target.closest('[data-section-id]')?.dataset.sectionId);if(section&&event.target.dataset.sectionField){section.data[event.target.dataset.sectionField]=event.target.value;markDirty();synchronize();return;}if(section&&event.target.dataset.columnWidth!==undefined){section.data.widths[Number(event.target.dataset.columnWidth)]=Number(event.target.value);markDirty();synchronize();return;}const component=findComponent(event.target.closest('[data-component-id]')?.dataset.componentId);if(!component||!event.target.dataset.field)return;const itemElement=event.target.closest('[data-item-id]');const target=itemElement?component.data.items.find(item=>item.id===itemElement.dataset.itemId):component.data;target[event.target.dataset.field]=event.target.type==='checkbox'?event.target.checked:event.target.dataset.field==='columns'?Number(event.target.value):event.target.value;markDirty();synchronize();};
    list.addEventListener('input',updateField);
    list.addEventListener('change',event=>{if(event.target.matches('select[data-section-field],select[data-field],input[type="checkbox"][data-field]'))updateField(event);});
    const clearComponentDropState=()=>list.querySelectorAll('.is-component-dragging,.is-component-drop-target,.is-component-drop-before,.is-component-drop-after').forEach(element=>element.classList.remove('is-component-dragging','is-component-drop-target','is-component-drop-before','is-component-drop-after'));
    list.addEventListener('dragstart',event=>{const handle=event.target.closest('.component-block__handle[draggable="true"]');if(!handle)return;const componentElement=handle.closest('[data-component-id]');draggedComponentId=componentElement?.dataset.componentId||null;if(!draggedComponentId)return;componentElement.classList.add('is-component-dragging');event.dataTransfer.effectAllowed='move';event.dataTransfer.setData('text/plain',draggedComponentId);});
    list.addEventListener('dragover',event=>{if(!draggedComponentId)return;const columnElement=event.target.closest('[data-column]');if(!columnElement)return;event.preventDefault();event.dataTransfer.dropEffect='move';clearComponentDropState();[...list.querySelectorAll('[data-component-id]')].find(element=>element.dataset.componentId===draggedComponentId)?.classList.add('is-component-dragging');const targetComponent=event.target.closest('[data-component-id]');if(targetComponent&&targetComponent.dataset.componentId!==draggedComponentId){const after=event.clientY>=targetComponent.getBoundingClientRect().top+targetComponent.getBoundingClientRect().height/2;targetComponent.classList.add(after?'is-component-drop-after':'is-component-drop-before');}else columnElement.classList.add('is-component-drop-target');});
    list.addEventListener('drop',event=>{if(!draggedComponentId)return;const columnElement=event.target.closest('[data-column]');const sectionElement=columnElement?.closest('[data-section-id]');if(!columnElement||!sectionElement)return;event.preventDefault();const source=findComponentLocation(draggedComponentId);const targetSection=blocks.find(item=>item.id===sectionElement.dataset.sectionId);const targetColumnIndex=Number(columnElement.dataset.column);const targetColumn=targetSection?.data.columns[targetColumnIndex];if(!source||!targetColumn)return;const targetElement=event.target.closest('[data-component-id]');const targetId=targetElement?.dataset.componentId;if(targetId===draggedComponentId){draggedComponentId=null;clearComponentDropState();return;}const after=targetElement?event.clientY>=targetElement.getBoundingClientRect().top+targetElement.getBoundingClientRect().height/2:false;const [component]=source.column.splice(source.componentIndex,1);let targetIndex=targetId?targetColumn.findIndex(item=>item.id===targetId):-1;if(targetIndex<0)targetIndex=targetColumn.length;else if(after)targetIndex++;targetColumn.splice(targetIndex,0,component);selectedSlot={sectionId:targetSection.id,column:targetColumnIndex};draggedComponentId=null;clearComponentDropState();markDirty();render();});
    list.addEventListener('dragend',()=>{draggedComponentId=null;clearComponentDropState();});
    list.addEventListener('click',event=>{
        const sectionElement=event.target.closest('[data-section-id]');if(!sectionElement)return;const sectionIndex=blocks.findIndex(item=>item.id===sectionElement.dataset.sectionId);const section=blocks[sectionIndex];
        const columnElement=event.target.closest('[data-column]');if(columnElement&&!event.target.closest('button,summary,input,textarea,select,label')){selectedSlot={sectionId:section.id,column:Number(columnElement.dataset.column)};render();return;}
        const componentElement=event.target.closest('[data-component-id]');const component=componentElement?findComponent(componentElement.dataset.componentId):null;const column=component?section.data.columns.find(items=>items.some(item=>item.id===component.id)):null;const componentIndex=column?.findIndex(item=>item.id===component.id)??-1;
        const itemElement=event.target.closest('[data-item-id]');const itemIndex=itemElement&&component?component.data.items.findIndex(item=>item.id===itemElement.dataset.itemId):-1;const definition=component?definitions[component.type]:null;
        if(event.target.closest('[data-rich-text-source-toggle]')&&component){const fieldElement=event.target.closest('.component-rich-field');const editorElement=fieldElement.querySelector('[data-rich-text-component]');const source=fieldElement.querySelector('[data-rich-text-source]');const sourceMode=source.hidden;source.hidden=!sourceMode;editorElement.parentElement.querySelector('.ql-toolbar').hidden=sourceMode;editorElement.hidden=sourceMode;if(sourceMode){source.value=component.data.content||'';event.target.textContent=t('Wróć do edytora','Back to editor');}else{editorElement.__shoproQuill.clipboard.dangerouslyPasteHTML(source.value);event.target.textContent=t('Edytuj HTML','Edit HTML');}return;}
        if(event.target.closest('[data-media-picker]')&&component){mediaPickerTarget={componentId:component.id,key:event.target.closest('[data-media-picker]').dataset.mediaKey};globalThis.open('/admin/configuration/files?picker=1','shopro-media-picker','width=1180,height=760,resizable=yes,scrollbars=yes');return;}
        if(event.target.closest('[data-add-column]')){section.data.columns.push([]);section.data.widths=section.data.columns.map(()=>Math.round(100/section.data.columns.length));selectedSlot={sectionId:section.id,column:section.data.columns.length-1};}
        else if(event.target.closest('[data-remove-column]')){const index=Number(event.target.closest('[data-remove-column]').dataset.removeColumn);if(section.data.columns.length>1&&!section.data.columns[index].length){section.data.columns.splice(index,1);section.data.widths.splice(index,1);selectedSlot={sectionId:section.id,column:0};}else if(section.data.columns[index].length)globalThis.alert(t('Najpierw usuń lub przenieś komponenty z tej kolumny.','Delete or move the components from this column first.'));}
        else if(event.target.closest('[data-remove-section]')){if(sectionHasProtectedSystemRole(section))return;blocks.splice(sectionIndex,1);}
        else if(event.target.closest('[data-duplicate-section]')){if(sectionHasProtectedSystemRole(section))return;const copy=duplicateSection(section);blocks.splice(sectionIndex+1,0,copy);selectedSlot={sectionId:copy.id,column:0};}
        else if(event.target.closest('[data-move-section]')){const next=sectionIndex+Number(event.target.closest('[data-move-section]').dataset.moveSection);if(next>=0&&next<blocks.length)[blocks[sectionIndex],blocks[next]]=[blocks[next],blocks[sectionIndex]];}
        else if(event.target.closest('[data-remove-component]')){if(systemRoleRequired&&component.type==='system_role')return;column.splice(componentIndex,1);}
        else if(event.target.closest('[data-duplicate-component]')){if(component.type==='system_role'){globalThis.alert(t('Komponent roli strony może wystąpić tylko raz.','The page role component can only be added once.'));return;}column.splice(componentIndex+1,0,duplicateComponent(component));}
        else if(event.target.closest('[data-move-component]')){const next=componentIndex+Number(event.target.closest('[data-move-component]').dataset.moveComponent);if(next>=0&&next<column.length)[column[componentIndex],column[next]]=[column[next],column[componentIndex]];}
        else if(event.target.closest('[data-add-item]')){const item={id:uid()};definition.itemFields.forEach(field=>item[field.key]=field.type==='checkbox');component.data.items.push(item);}
        else if(event.target.closest('[data-remove-item]'))component.data.items.splice(itemIndex,1);
        else if(event.target.closest('[data-move-item]')){const next=itemIndex+Number(event.target.closest('[data-move-item]').dataset.moveItem);if(next>=0&&next<component.data.items.length)[component.data.items[itemIndex],component.data.items[next]]=[component.data.items[next],component.data.items[itemIndex]];}
        else return;markDirty();render();
    });
    const receiveMedia=event=>{if(event.origin!==globalThis.location.origin||event.data?.type!=='shopro:media-selected'||!mediaPickerTarget)return;const component=findComponent(mediaPickerTarget.componentId);if(!component)return;component.data[mediaPickerTarget.key]=String(event.data.url||'');mediaPickerTarget=null;markDirty();render();};
    globalThis.addEventListener('message',receiveMedia);
    document.addEventListener('turbo:before-cache',()=>globalThis.removeEventListener('message',receiveMedia),{once:true});
    const leaveMessage=t('Masz niezapisane zmiany. Czy na pewno chcesz opuścić edycję?','You have unsaved changes. Are you sure you want to leave the editor?');
    const beforeUnload=event=>{if(!dirty||submitting)return;event.preventDefault();event.returnValue='';};
    const beforeVisit=event=>{if(!dirty||submitting)return;if(!globalThis.confirm(leaveMessage))event.preventDefault();else{dirty=false;document.removeEventListener('turbo:before-visit',beforeVisit);}};
    globalThis.addEventListener('beforeunload',beforeUnload);
    document.addEventListener('turbo:before-visit',beforeVisit);
    form?.addEventListener('input',markDirty);
    form?.addEventListener('change',markDirty);
    form?.addEventListener('submit',event=>{synchronize();if(event.submitter?.matches('[data-preview-submit]'))return;submitting=true;dirty=false;document.removeEventListener('turbo:before-visit',beforeVisit);});
    render();
};

initializeComponentBuilder();
document.addEventListener('turbo:load', initializeComponentBuilder);

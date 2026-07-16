import grapesjs from 'grapesjs';

const root = document.querySelector('[data-shopro-builder]');

if (root) {
    const contentField = document.getElementById(root.dataset.contentField);
    const projectField = document.getElementById(root.dataset.projectField);
    const cssField = document.getElementById(root.dataset.cssField);
    const form = root.closest('form');

    const editor = grapesjs.init({
        container: root.querySelector('[data-builder-canvas]'),
        height: '680px',
        width: 'auto',
        storageManager: false,
        noticeOnUnload: false,
        fromElement: false,
        blockManager: { appendTo: root.querySelector('[data-builder-blocks]') },
        panels: { defaults: [] },
        deviceManager: { devices: [
            { id: 'Desktop', name: 'Desktop', width: '' },
            { id: 'Tablet', name: 'Tablet', width: '768px', widthMedia: '992px' },
            { id: 'Mobile portrait', name: 'Telefon', width: '375px', widthMedia: '575px' },
        ] },
        canvas: { styles: [root.dataset.styleUrl] },
    });

    editor.Blocks.add('shopro-section', {
        label: 'Sekcja', category: 'Układ',
        content: { type: 'section', classes: ['shopro-content-section'], components: [{ type: 'text', content: '<h2>Nowa sekcja</h2><p>Wpisz treść sekcji.</p>' }] },
    });
    editor.Blocks.add('shopro-columns-2', {
        label: '2 kolumny', category: 'Układ',
        content: '<section class="shopro-content-section"><div class="shopro-builder-columns"><div><h3>Kolumna 1</h3><p>Treść</p></div><div><h3>Kolumna 2</h3><p>Treść</p></div></div></section>',
    });
    editor.Blocks.add('shopro-text', { label: 'Tekst', category: 'Treść', content: '<div class="shopro-rich-text"><h2>Nagłówek</h2><p>Rozpocznij pisanie treści.</p></div>' });
    editor.Blocks.add('shopro-image', { label: 'Obraz', category: 'Treść', content: { type: 'image', attributes: { alt: 'Opis obrazu' }, style: { width: '100%' } }, activate: true });
    editor.Blocks.add('shopro-button', { label: 'Przycisk', category: 'Treść', content: '<a class="site-button" href="#">Przycisk <span>→</span></a>' });
    editor.Blocks.add('shopro-divider', { label: 'Separator', category: 'Treść', content: '<hr class="shopro-divider">' });

    try {
        const project = projectField.value ? JSON.parse(projectField.value) : null;
        if (project && Object.keys(project).length) editor.loadProjectData(project);
        else if (contentField.value.trim()) editor.setComponents(contentField.value);
    } catch (error) {
        console.warn('Nie udało się odczytać projektu buildera. Użyto HTML.', error);
        editor.setComponents(contentField.value);
    }

    document.querySelectorAll('[data-builder-device]').forEach((button) => button.addEventListener('click', () => {
        editor.setDevice(button.dataset.builderDevice);
        document.querySelectorAll('[data-builder-device]').forEach((item) => item.classList.toggle('is-active', item === button));
    }));

    const synchronize = () => {
        projectField.value = JSON.stringify(editor.getProjectData());
        contentField.value = editor.getHtml();
        cssField.value = editor.getCss();
    };
    editor.on('update', synchronize);
    form?.addEventListener('submit', synchronize);
    synchronize();
}

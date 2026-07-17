import Quill from 'quill';
import 'quill/dist/quill.snow.css';

const initializeRichEditors = () => {
    document.querySelectorAll('textarea[data-rich-editor]').forEach((textarea) => {
        if (textarea.dataset.richEditorInitialized === 'true') return;
        textarea.dataset.richEditorInitialized = 'true';
        const wrapper = document.createElement('div');
        wrapper.className = 'email-rich-editor';
        const canvas = document.createElement('div');
        canvas.className = 'email-rich-editor__canvas';
        wrapper.append(canvas);
        textarea.hidden = true;
        textarea.before(wrapper);
        const editor = new Quill(canvas, {
            theme: 'snow',
            placeholder: 'Wpisz treść wiadomości…',
            modules: { toolbar: [[{ header: [1, 2, 3, false] }], ['bold', 'italic', 'underline', 'strike'], [{ color: [] }, { background: [] }], [{ list: 'ordered' }, { list: 'bullet' }], [{ align: [] }], ['blockquote', 'link'], ['clean']] },
        });
        editor.clipboard.dangerouslyPasteHTML(textarea.value || '');
        const synchronize = () => { textarea.value = editor.root.innerHTML; };
        editor.on('text-change', synchronize);
        textarea.form?.addEventListener('submit', synchronize);
    });
};

document.addEventListener('turbo:load', initializeRichEditors);
initializeRichEditors();

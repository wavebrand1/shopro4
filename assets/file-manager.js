const initializeMediaPicker = () => {
    document.querySelectorAll('[data-select-media]').forEach(button => {
        if (button.dataset.mediaPickerInitialized === 'true') return;
        button.dataset.mediaPickerInitialized = 'true';
        button.addEventListener('click', () => {
            if (!globalThis.opener) return;
            globalThis.opener.postMessage({type: 'shopro:media-selected', url: button.dataset.selectMedia}, globalThis.location.origin);
            globalThis.close();
        });
    });
};

initializeMediaPicker();
document.addEventListener('turbo:load', initializeMediaPicker);

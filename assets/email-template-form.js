const initialize = () => document.querySelectorAll('[data-email-template-code]').forEach((select) => {
    if (select.dataset.descriptionInitialized) return;
    select.dataset.descriptionInitialized = 'true';
    const description = document.querySelector('[data-email-template-description]');
    const update = () => { description.textContent = select.options[select.selectedIndex]?.dataset.description || 'Wybierz zdarzenie, aby zobaczyć, kiedy wiadomość jest używana.'; };
    select.addEventListener('change', update); update();
});
document.addEventListener('turbo:load', initialize); initialize();

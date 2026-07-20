const initialize = () => document.querySelectorAll('[data-email-template-code]').forEach((select) => {
    if (select.dataset.descriptionInitialized) return;
    select.dataset.descriptionInitialized = 'true';
    const description = document.querySelector('[data-email-template-description]');
    if (!description) return;
    const update = () => {
        description.textContent = select.options[select.selectedIndex]?.dataset.description || description.dataset.emptyDescription || '';
    };
    select.addEventListener('change', update);
    update();
});

document.addEventListener('turbo:load', initialize);
initialize();

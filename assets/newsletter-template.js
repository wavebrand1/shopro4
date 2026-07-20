const initializeNewsletterTemplate = () => {
    const select = document.querySelector('[data-newsletter-template]');
    const button = document.querySelector('[data-newsletter-template-load]');
    const subject = document.querySelector('[name="newsletter_campaign[subject]"]');
    const content = document.querySelector('[name="newsletter_campaign[content]"]');
    if (!select || !button || !subject || !content || button.dataset.initialized === 'true') return;
    button.dataset.initialized = 'true';
    select.addEventListener('change', () => { button.disabled = select.value === ''; });
    button.addEventListener('click', () => {
        const option = select.selectedOptions[0];
        if (!option?.value) return;
        const currentText = content.shoproRichEditor?.getText().trim() || content.value.replace(/<[^>]*>/g, '').trim();
        if ((subject.value.trim() || currentText) && !window.confirm(button.dataset.confirm || 'Replace the current message?')) return;
        subject.value = option.dataset.subject || '';
        content.value = option.dataset.content || '';
        if (content.shoproRichEditor) content.shoproRichEditor.clipboard.dangerouslyPasteHTML(content.value);
        subject.dispatchEvent(new Event('change', { bubbles: true }));
    });
};

document.addEventListener('turbo:load', initializeNewsletterTemplate);
initializeNewsletterTemplate();

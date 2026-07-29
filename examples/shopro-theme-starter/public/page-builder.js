/*
 * Definitions are merged by Shopro Core before the component builder starts.
 * Keep field descriptors and defaults in the theme package with the Twig block.
 */
window.ShoproThemeComponents = {
    client_banner: {
        label: 'Baner klienta',
        itemLabel: '',
        fields: [
            {label: 'Nadtytuł', key: 'eyebrow', type: 'text'},
            {label: 'Nagłówek', key: 'heading', type: 'text'},
            {label: 'Treść', key: 'content', type: 'richtext'},
            {label: 'Adres linku', key: 'url', type: 'text'},
            {label: 'Tekst przycisku', key: 'buttonLabel', type: 'text'}
        ],
        defaults: {eyebrow: 'Skórka klienta', heading: 'Pierwszy komponent', content: '<p>Treść banera.</p>', url: '#', buttonLabel: 'Dowiedz się więcej'}
    }
};

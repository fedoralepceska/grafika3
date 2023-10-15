import { createI18n } from 'vue-i18n';

const messages = {
    en: {
        // Define your English translations here
        name: 'Name',
        company: 'Company',
        email: 'Email',
        phone: 'Phone',
        client: 'Client',
        addNewClient :'Add new client',
        addClient: 'Add client'
    },
    mk: {
        // Define your Macedonian translations here
        greeting: 'Здраво',
        name: 'Име',
        company: 'Компанија',
        email: 'Е-пошта',
        phone: 'Телефон',
        client: 'Клиент',
        addNewClient :'Додади нов клиент',
        addClient: 'Додади клиент'
    },
};

export const i18n = createI18n({
    locale: 'en', // Set the initial locale
    fallbackLocale: 'en', // Fallback to English if translation is missing
    messages,
});

export default i18n;

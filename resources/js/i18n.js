import { createI18n } from 'vue-i18n';

const messages = {
    en: {
        // Define your English translations here
        name: 'Name',
        company: 'Client name',
        email: 'Email',
        phone: 'Client number',
        client: 'Client',
        clientDetails: 'Client details',
        addNewClient :'Add new client',
        addClient: 'Add client'
    },
    mk: {
        // Define your Macedonian translations here
        greeting: 'Здраво',
        name: 'Име',
        company: 'Име на клиент',
        email: 'Е-пошта',
        phone: 'Телефон',
        client: 'Клиент',
        clientDetails: 'Детали за клиент',
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

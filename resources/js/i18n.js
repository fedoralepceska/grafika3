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
        addClient: 'Add client',
        invoice: 'Invoice',
        invoiceTitle: 'Invoice title',
        createNewInvoice: 'Create new invoice',
        createInvoice: 'Create invoice',
        comment: 'Comment',
        shippingDetails: 'Shipping details',
        startDate: 'Start date',
        endDate: 'End date',
        dragAndDrop: 'Drag and drop files here',
        browse: 'Browse',
        orderInfo: 'Order info',
        orderLines: 'Order lines',
        image: 'Image',
        width: 'Width',
        height: 'Height',
        toast: {
            success: 'Successfully created invoice.',
            error: 'Failed to create invoice.'
        }
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
        addClient: 'Додади клиент',
        invoice: 'Налог',
        invoiceTitle: 'Име на налог',
        createNewInvoice: 'Креирај нов налог',
        createInvoice: 'Креирај налог',
        comment: 'Коментар',
        shippingDetails: 'Детали за испорака',
        startDate: 'Почетен датум',
        endDate: 'Краен датум',
        dragAndDrop: 'Прикачи документи тука',
        browse: 'Пребарај',
        orderInfo: 'Информации за налог',
        orderLines: 'Налог и ставки',
        image: 'Слика',
        width: 'Ширина',
        height: 'Висина',
        toast: {
            success: 'Налогот е успешно креиран.',
            error: 'Настана грешка при креирање на налогот.'
        }
    },
};

export const i18n = createI18n({
    locale: 'en', // Set the initial locale
    fallbackLocale: 'en', // Fallback to English if translation is missing
    messages,
});

export default i18n;

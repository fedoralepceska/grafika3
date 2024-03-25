import { createI18n } from 'vue-i18n';

const messages = {
    en: {
        // Define your English translations here
        name: 'Name',
        company: 'Client name',
        email: 'Email',
        phone: 'Client number',
        client: 'Client',
        allClients: 'All clients',
        clientDetails: 'Client details',
        addNewClient :'Add new client',
        addClient: 'Add client',
        invoice: 'Order',
        invoice2: 'Invoice',
        statement: 'Statement',
        invoiceTitle: 'Order title',
        createNewInvoice: 'Create new order',
        createInvoice: 'Create order',
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
            success: 'Successfully created order.',
            error: 'Failed to create order.'
        },
        material: 'Material',
        smallMaterial: 'Small Material',
        format: 'Format',
        materials: {
            'Material 1': 'Фолија со црна позадина',
            'Material 2': 'Фолија со бела позадина',
            'Material 3': 'Беклајт фолија',
            'Material 4': 'Форекс 3мм пола табла',
            'Material 5': 'Форекс 3мм',
            'Material 6': 'Форекс 5мм',
            'Material 7': 'Беклајт церада 280цм',
            'Material 8': 'Церада 1м 440гр',
            'Material 9': 'Сити лајт хартија',
            'Material 10': 'Аки плац 5мм пола табла',
            'Material 11': 'Плексиглас',
            'Material 12': 'Аки плац',
            'Material 13': 'Микровал картон',
            'Material 14': 'Каст фолија',
            'Material 15': 'Катер фолија во боја',
            'Material 16': 'Меш платно',
            'Material 17': 'Перфорирана',
            'Material 18': 'Фронт лит церада',
            'Material 19': 'Фронт лит церада 3.2мм',
            'Material 20': 'Бек лит церада',
            'Material 21': 'Блу бек хартија',
            'Material 22': 'Транспарентна фолија мат',
            'Material 23': 'Транспарентна фолија сјај',
            'Material 24': 'Вералит 1мм',
            'Material 25': 'Фибер платно',
            'Material 26': 'Пескарена',
            'Material 27': 'Филм фолија',
            'Material 28': 'Платно за знаме',
        },
        MATERIALS:"MATERIALS",
        materialLargeFormat:'Materials',
        materialSmallFormat: "Material - small format",
        syncJobs: 'Sync selected jobs',
        syncAllJobs: 'Sync all jobs',
        materialsSmall: {
            'Material small 1': 'Меморандум А4 (80гр)',
            'Material small 2': 'Офсет 80гр / А4',
            'Material small 3': 'Кунздрукт 300гр / 230x330',
            'Material small 4': 'Кунздрукт 350гр / 330x480',
            'Material small 5': 'Кунздрукт 200гр / 320x440',
            'Material small 6': 'Кунздрукт 300гр / 320x440',
            'Material small 7': 'Кунздрукт 300гр / 350x500',
            'Material small 8': 'Кунздрукт 350гр / 350x500',
            'Material small 9': 'Кунздрукт 150гр / 320x440',
            'Material small 10': 'Кунздрукт 130гр / 350x500',
            'Material small 11': 'Кунздрукт 300гр / 210x420',
            'Material small 12': 'Кунздрукт 130гр / 320x440',
            'Material small 13': 'Кунздрукт 300гр / 230x500',
            'Material small 14': 'Кунздрукт 200гр / 290x550',
            'Material small 15': 'Кунздрукт 300гр / 250x350',
            'Material small 16': 'Кунздрукт 250гр / 320x440',
            'Material small 17': 'Офсет 90гр / 350x500',
            'Material small 18': 'Офсет 90гр / 320x440',
            'Material small 19': 'Муфлон 230x330',
            'Material small 20': 'Муфлон 330x480',
            'Material small 21': 'Муфлон 350x500',
            'Material small 22': 'Пенкало metz',
            'Material small 23': 'Пенкало шнидер',
            'Material small 24': 'Запалки ultra',
            'Material small 25': 'Запали itec luss',
            'Material small 26': 'Молив',
            'Material small 27': 'Хромо 270гр 320x440',
            'Material small 28': 'Хромо 270гр 350x500',
            'Material small 29': 'Коверт American',
            'Material small 30': 'Коверт C5',
            'Material small 31': 'Моден картон 260гр 230x330',
            'Material small 32': 'Беџ',
            'Material small 33': 'Пластична ID картичка',
            'Material small 34': 'Кунздрукт 300гр / 330x480',
        },
        machineC: "Machine CUT",
        machineCut: {
            'Machine cut 1': 'Summa cutter',
            'Machine cut 2': 'AHC',
        },
        machineP: "Machine PRINT",
        machinePrint: {
            'Machine print 1': 'Mimaki UV',
            'Machine print 2': 'Pitney bowe s di380',
            'Machine print 3': 'Mimaki eco slovent',
            'Machine print 4': 'Horex iGen 150',
            'Machine print 5': 'Horex Versant 2100',
            'Machine print 6': 'Durst 320',
            'Machine print 7': 'Scitex XL 1500',
            'Machine print 8': 'Durst p10',
            'Machine print 9': 'Cannon B&W imagePress 1125',
            'Machine print 10': 'HP Injet X476W',
        },
        ACTIONS: 'ACTIONS',
        action: 'Action',
        actions: {
            'Action 1': 'Сечење на зунд',
            'Action 2': 'Лепење на форекс 3мм',
            'Action 3': 'Лепење на форекс 5мм',
            'Action 4': 'Лепење на форекс 10мм',
            'Action 5': 'Ковертирање',
            'Action 6': 'Лепење на лепенка',
            'Action 7': 'Монтажа на наша конструкција',
            'Action 8': 'Катирање',
            'Action 9': 'Нитни на банер',
            'Action 10': 'Подвив на банер и врвка',
            'Action 11': 'Ламинација сјај',
            'Action 12': 'Ламинација мат',
            'Action 13': 'Пластификат папка сјај',
            'Action 14': 'Пластификат папка мат',
            'Action 15': 'Ламинат ролна сјај',
            'Action 16': 'Ламинат ролна мат',
            'Action 17': 'Укоричување',
            'Action 18': 'Штанцање (рачно)',
            'Action 19': 'Селективен лак mimaki',
            'Action 20': 'Сечење рачно',
            'Action 21': 'Сечење машинско',
            'Action 22': 'Трансфер',
            'Action 23': 'Да се остави 5-7цм бело',
            'Action 24': 'Да се остави 15цм бело',
            'Action 25': 'Лепење дуплофан (коцка)',
            'Action 26': 'Лепење дуплофан (цела должина)',
            'Action 27': 'Лепење пластика (wobler)',
            'Action 28': 'Хефтање (книга)',
            'Action 29': 'Бигување',
            'Action 30': 'Ламинат ролна сатин',
            'Action 31': 'Бушење',
            'Action 32': 'Лепење на биндер',
            'Action 33': 'Перфорација',
            'Action 34': 'Врзување врвка',
            'Action 35': 'Штанцање (машинско)',
        },
        Quantity:'Quantity',
        Copies:'Copies',
        Shipping:'Shipping',
        materialDetails: 'Material Details',
        smallFormatDetails: 'Small Format Details',
        smallFormatMaterialDetails: 'Small Format Material Details',
        addNewMaterialSmall: 'Add Small Format Material',
        quantity: 'Quantity',
        addMaterial: 'Add Material',
        addFormat: 'Add Format',
        addSmallMaterial: 'Add Small Material',
        SmallFormatMaterials: 'Small Format Materials',
        listOfSmallFormat: 'List of Small Format Materials',
        pricePerUnit: 'Price per unit',
        addNewMaterialLarge:'Add Large Format Material',
        listOfLargeFormat: 'List of Large Format Materials',
        contact: 'Contact',
        contacts: 'Contacts',
        otherContacts: 'Other contacts',
        listOfSmallMaterials: 'List of small materials',
        listOfAllOrders: 'List Of All Orders',
        listOfAllInvoices: 'List Of All Invoiced Orders',
        listOfNotInvoiced:'List of Uninvoiced Orders',
        ViewAllInvoices: 'View All Orders',
        UninvoicedOrders: 'Uninvoiced orders',
        listOfAllStatements: 'List Of All Statements',
        invoicedOrders: 'Invoiced orders',
        bankStatement:'Bank Statement',
        currentReport:'Current Report',
        invoiceGeneration: 'Invoice Generation',
        invoiceEdit:'Edit Invoice',
        InvoiceDetails: 'Order Details',
        InvoiceReview: 'Order Review',
        totalm: 'Total m',
        shippingTo: 'Shipping To',
        jobPrice: 'Job Price Cost',
        salePrice:'Sale Price',
        production: 'Production',
        dashboard: 'Dashboard',
        actionInfo: 'Action Overview',
        address: 'Address',
        city: "City",
        Nr:'Nr.'
    },
    mk: {
        // Define your Macedonian translations here
        greeting: 'Здраво',
        name: 'Име',
        company: 'Име на клиент',
        email: 'Е-пошта',
        phone: 'Телефон',
        client: 'Клиент',
        allClients: 'Сите клиенти',
        clientDetails: 'Детали за клиент',
        addNewClient :'Додади нов клиент',
        addClient: 'Додади клиент',
        invoice: 'Налог',
        invoice2:'Фактура',
        statement: 'Извод',
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
        },
        material: 'Материјал',
        smallMaterial: 'Мал Материјал',
        format: 'Формат',
        materials: {
            'Material 1': 'Фолија со црна позадина',
            'Material 2': 'Фолија со бела позадина',
            'Material 3': 'Беклајт фолија',
            'Material 4': 'Форекс 3мм пола табла',
            'Material 5': 'Форекс 3мм',
            'Material 6': 'Форекс 5мм',
            'Material 7': 'Беклајт церада 280цм',
            'Material 8': 'Церада 1м 440гр',
            'Material 9': 'Сити лајт хартија',
            'Material 10': 'Аки плац 5мм пола табла',
            'Material 11': 'Плексиглас',
            'Material 12': 'Аки плац',
            'Material 13': 'Микровал картон',
            'Material 14': 'Каст фолија',
            'Material 15': 'Катер фолија во боја',
            'Material 16': 'Меш платно',
            'Material 17': 'Перфорирана',
            'Material 18': 'Фронт лит церада',
            'Material 19': 'Фронт лит церада 3.2мм',
            'Material 20': 'Бек лит церада',
            'Material 21': 'Блу бек хартија',
            'Material 22': 'Транспарентна фолија мат',
            'Material 23': 'Транспарентна фолија сјај',
            'Material 24': 'Вералит 1мм',
            'Material 25': 'Фибер платно',
            'Material 26': 'Пескарена',
            'Material 27': 'Филм фолија',
            'Material 28': 'Платно за знаме',
        },
        MATERIALS:"МАТЕРИЈАЛИ",
        materialLargeFormat:'Материјали',
        materialSmallFormat: "Материјал - мал формат",
        syncJobs: 'Ажурирај',
        syncAllJobs: 'Ажурирај ги сите',
        materialsSmall: {
            'Material small 1': 'Меморандум А4 (80гр)',
            'Material small 2': 'Офсет 80гр / А4',
            'Material small 3': 'Кунздрукт 300гр / 230x330',
            'Material small 4': 'Кунздрукт 350гр / 330x480',
            'Material small 5': 'Кунздрукт 200гр / 320x440',
            'Material small 6': 'Кунздрукт 300гр / 320x440',
            'Material small 7': 'Кунздрукт 300гр / 350x500',
            'Material small 8': 'Кунздрукт 350гр / 350x500',
            'Material small 9': 'Кунздрукт 150гр / 320x440',
            'Material small 10': 'Кунздрукт 130гр / 350x500',
            'Material small 11': 'Кунздрукт 300гр / 210x420',
            'Material small 12': 'Кунздрукт 130гр / 320x440',
            'Material small 13': 'Кунздрукт 300гр / 230x500',
            'Material small 14': 'Кунздрукт 200гр / 290x550',
            'Material small 15': 'Кунздрукт 300гр / 250x350',
            'Material small 16': 'Кунздрукт 250гр / 320x440',
            'Material small 17': 'Офсет 90гр / 350x500',
            'Material small 18': 'Офсет 90гр / 320x440',
            'Material small 19': 'Муфлон 230x330',
            'Material small 20': 'Муфлон 330x480',
            'Material small 21': 'Муфлон 350x500',
            'Material small 22': 'Пенкало metz',
            'Material small 23': 'Пенкало шнидер',
            'Material small 24': 'Запалки ultra',
            'Material small 25': 'Запали itec luss',
            'Material small 26': 'Молив',
            'Material small 27': 'Хромо 270гр 320x440',
            'Material small 28': 'Хромо 270гр 350x500',
            'Material small 29': 'Коверт American',
            'Material small 30': 'Коверт C5',
            'Material small 31': 'Моден картон 260гр 230x330',
            'Material small 32': 'Беџ',
            'Material small 33': 'Пластична ID картичка',
            'Material small 34': 'Кунздрукт 300гр / 330x480',
        },
        machineC: "Машини CUT",
        machineCut: {
            'Machine cut 1': 'Summa cutter',
            'Machine cut 2': 'AHC',
        },
        machineP: "Машини PRINT",
        machinePrint: {
            'Machine print 1': 'Mimaki UV',
            'Machine print 2': 'Pitney bowe s di380',
            'Machine print 3': 'Mimaki eco slovent',
            'Machine print 4': 'Horex iGen 150',
            'Machine print 5': 'Horex Versant 2100',
            'Machine print 6': 'Durst 320',
            'Machine print 7': 'Scitex XL 1500',
            'Machine print 8': 'Durst p10',
            'Machine print 9': 'Cannon B&W imagePress 1125',
            'Machine print 10': 'HP Injet X476W',

        },
        ACTIONS:'ДОРАБОТКИ',
        action: 'Доработка',
        actions: {
            'Action 1': 'Сечење на зунд',
            'Action 2': 'Лепење на форекс 3мм',
            'Action 3': 'Лепење на форекс 5мм',
            'Action 4': 'Лепење на форекс 10мм',
            'Action 5': 'Ковертирање',
            'Action 6': 'Лепење на лепенка',
            'Action 7': 'Монтажа на наша конструкција',
            'Action 8': 'Катирање',
            'Action 9': 'Нитни на банер',
            'Action 10': 'Подвив на банер и врвка',
            'Action 11': 'Ламинација сјај',
            'Action 12': 'Ламинација мат',
            'Action 13': 'Пластификат папка сјај',
            'Action 14': 'Пластификат папка мат',
            'Action 15': 'Ламинат ролна сјај',
            'Action 16': 'Ламинат ролна мат',
            'Action 17': 'Укоричување',
            'Action 18': 'Штанцање (рачно)',
            'Action 19': 'Селективен лак mimaki',
            'Action 20': 'Сечење рачно',
            'Action 21': 'Сечење машинско',
            'Action 22': 'Трансфер',
            'Action 23': 'Да се остави 5-7цм бело',
            'Action 24': 'Да се остави 15цм бело',
            'Action 25': 'Лепење дуплофан (коцка)',
            'Action 26': 'Лепење дуплофан (цела должина)',
            'Action 27': 'Лепење пластика (wobler)',
            'Action 28': 'Хефтање (книга)',
            'Action 29': 'Бигување',
            'Action 30': 'Ламинат ролна сатин',
            'Action 31': 'Бушење',
            'Action 32': 'Лепење на биндер',
            'Action 33': 'Перфорација',
            'Action 34': 'Врзување врвка',
            'Action 35': 'Штанцање (машинско)',
        },
        Quantity:'Количина',
        Copies:'Копии',
        Shipping:'Достава',
        materialDetails: 'Детали за материјал',
        smallFormatDetails: 'Детали за мал формат',
        smallFormatMaterialDetails: 'Детали за мал формат материајал',
        addNewMaterialSmall: 'Додади материјал - мал формат',
        quantity: 'Количина',
        addMaterial: 'Додади материјал',
        addFormat: 'Додади формат',
        addSmallMaterial: 'Додади мал материјал',
        SmallFormatMaterials: 'Материјали - мал формат',
        listOfSmallFormat: 'Листа на материјали - мал формат',
        listOfSmallMaterials: 'Листа на мали материјали',
        pricePerUnit: 'Цена по парче',
        contact: 'Контакт',
        contacts: 'Контакти',
        otherContacts: 'Останати контакти',
        production: 'Продукција',
        dashboard: 'Преглед',
        actionInfo: 'Преглед на акција',
        addNewMaterialLarge:'Додади материјал - голем формат',
        listOfLargeFormat: 'Листа на материјали - голем формат',
        listOfAllOrders: 'Листа на сите нарачки',
        listOfAllInvoices: 'Листа на сите фактурирани нарачки',
        listOfNotInvoiced:'Листа на нефактурирани нарачки',
        listOfAllStatements:'Листа на сите Изводи',
        ViewAllInvoices: 'Погледни ги сите нарачки',
        UninvoicedOrders:'Нефактурирани нарачки',
        invoicedOrders:'Фактурирани нарачки',
        bankStatement: 'Извод банка',
        currentReport:'Тековен извештај',
        invoiceGeneration:'Генерирање на фактура',
        invoiceEdit: 'Уреди фактура',
        InvoiceDetails: 'Детали за нарачка',
        InvoiceReview: 'Преглед на нарачка',
        totalm: 'Вкупно м.',
        shippingTo: 'Достава до',
        jobPrice: 'Цена на продукт',
        salePrice: 'Продажна цена',
        address: 'Адреса',
        city: "Град",
        Nr:'Бр.'
    },
};

export const i18n = createI18n({
    locale: 'en', // Set the initial locale
    fallbackLocale: 'en', // Fallback to English if translation is missing
    messages,
});

export default i18n;

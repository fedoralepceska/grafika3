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
        allArticles: 'All articles',
        allReceipts:'All receipts ',
        allRefinements: 'All refinements',
        articleName:'Article Name',
        clientDetails: 'Client details',
        addNewClient :'Add new client',
        addClient: 'Add client',
        invoice: 'Order',
        invoice2: 'Invoice',
        article: 'Article',
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
        length:'Length',
        weight:'Weight',
        color:'Color',
        Kg:'kg',
        M:'m',
        Pcs:'pcs',
        square_meters: 'm²',
        Unit:'Unit',
        Barcode: 'Barcode',
        Delete:'Delete',
        Edit:'Edit',
        receipt:'Receipt',
        refinements: 'Refinements',
        addNewReceipt:'Add new Receipt',
        receiptDetails:'Receipt Details',
        warehouse:'Warehouse',
        wItems: 'Warehouse articles',
        toast: {
            success: 'Successfully created order.',
            error: 'Failed to create order.'
        },
        material: 'Material',
        materialList: 'List of all Materials',
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
        Qty:'Qty.',
        Copies:'Copies',
        Code:'Code',
        VAT: 'VAT',
        Tax:'Tax',
        Amount: 'Amount',
        Total: 'Total',
        GeneralInfo:'General Information',
        Product: 'Product',
        Service: 'Service',
        pricelist:'Price List',
        price: 'Price',
        pprice: 'Purchase Price',
        fprice: 'Factory Price',
        additionalInfo: 'Additional Information',
        Shipping:'Shipping',
        materialDetails: 'Material Details',
        articleDetails:'Article Details',
        smallFormatDetails: 'Small Format Details',
        smallFormatMaterialDetails: 'Small Format Material Details',
        addNewMaterialSmall: 'Add Small Format Material',
        quantity: 'Quantity',
        addMaterial: 'Add Material',
        addArticle: 'Add Article',
        addFormat: 'Add Format',
        addSmallMaterial: 'Add Small Material',
        add:'Add',
        NewRow:'New Row',
        SmallFormatMaterials: 'Small Format Materials',
        listOfSmallFormat: 'List of Small Format Materials',
        pricePerUnit: 'Price per unit',
        addNewMaterialLarge:'Add Large Format Material',
        addNewArticle:'Add New Article',
        listOfLargeFormat: 'List of Large Format Materials',
        contact: 'Contact',
        contacts: 'Contacts',
        otherContacts: 'Other contacts',
        listOfSmallMaterials: 'List of Small Materials',
        listOfLargeMaterials:'List of Large Materials',
        listOfAllOrders: 'List Of All Orders',
        listOfAllInvoices: 'List Of All Invoiced Orders',
        listOfNotInvoiced:'List of Uninvoiced Orders',
        listOfIncomingInvoices:'List of Incoming Invoices',
        ViewAllInvoices: 'View All Orders',
        UninvoicedOrders: 'Uninvoiced orders',
        listOfAllStatements: 'List Of All Statements',
        invoicedOrders: 'Invoiced orders',
        listofallCardStatements:'List of All Card Statements',
        CardStatements:'Card Statement',
        CCS:'Client Card Statement',
        bankStatement:'Bank Statement',
        currentReport:'Current Report',
        invoiceGeneration: 'Invoice Generation',
        invoiceEdit:'Edit Invoice',
        InvoiceDetails: 'Order Details',
        InvoiceReview: 'Order Review',
        totalm: 'Total m',
        shippingTo: 'Shipping To',
        jobPriceCost: 'Job Price Cost',
        jobPrice: 'Job Price',
        salePrice:'Sale Price',
        production: 'Production',
        dashboard: 'Dashboard',
        actionInfo: 'Action Overview',
        address: 'Address',
        city: "City",
        Nr:'Nr.',
        from: 'From',
        to: 'To',
        owes: 'Owes',
        requests: 'Requests',
        balance: 'Balance',
        totalBalance: 'Total balance',
        date: 'Date',
        number: 'Number',
        document: 'Document',
        incomingInvoice: 'Incoming invoice',
        outcomeInvoice: 'Outcome invoice',
        statementIncome: 'Statement - income',
        statementExpense: 'Statement - expense',
        analyticsInvoice: 'Analytics for Order - User',
        receiptId: 'Receipt ID',
        other: 'Other',
        actionQuantity: 'Action quantity',
        offerClient: 'Offers for clients',
        offer: 'Offer',
        productionTime: 'Production time',
        analytics: 'Analytics',
        articleAnalytics: 'Analytics for Articles',
        selectChartType: 'Select chart type',
        pie: 'Pie chart',
        bar: 'Bar chart',
        line: 'Line chart',
        error: 'Error',
        detailedBreakdown: 'Detailed breakdown',
        orderCount: 'Order count',
        percentage: 'Percentage',
        clientInvoiceAnalytics: 'Client Invoice Analytics',
        clientInvoiceCostsAnalytics: 'Client Invoice Costs Analytics',
        invoiceCostsDen: 'Invoice Costs (ден.)',
        userInvoiceAnalytics: 'User Invoice Analytics',
        userName: 'User name',
        workerAnalytics: 'Worker Analytics',
        workedId: 'Worker ID',
        totalTimeSpent: 'Total time spent',
        invoicesWorkedOn: 'Invoices worked on',
        selectDateRange: 'Select Date Range',
        year: 'Year',
        month: 'Month',
        day: 'Day',
        reset: 'Reset',
        apply: 'Apply'
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
        allArticles: 'Сите артикли',
        allReceipts: 'Сите приемници',
        allRefinements:'Сите доработки',
        articleName: 'Име на артикл',
        clientDetails: 'Детали за клиент',
        addNewClient :'Додади нов клиент',
        addClient: 'Додади клиент',
        invoice: 'Налог',
        invoice2:'Фактура',
        statement: 'Извод',
        article: 'Артикл',
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
        length:'Должина',
        weight:'Тежина',
        color:'Боја',
        Kg:'кг',
        M:'м',
        Pcs:'ком',
        square_meters: 'м²',
        Unit:'Единица Мерка',
        Barcode: 'Бар Код',
        Delete:'Избриши',
        Edit:'Измени',
        receipt:'Приемница',
        refinements:'Доработки',
        addNewReceipt:'Додади нова приемница',
        receiptDetails:'Детали за приемница',
        warehouse: 'Магацин',
        wItems:'Магацински артикли',
        toast: {
            success: 'Налогот е успешно креиран.',
            error: 'Настана грешка при креирање на налогот.'
        },
        material: 'Материјал',
        materialList: 'Листа на сите материјали',
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
        Qty:'Кол.',
        Copies:'Копии',
        Code:'Шифра',
        GeneralInfo: 'Основни Податоци',
        Product: 'Производ',
        Service: 'Услуга',
        pricelist: 'Ценовник',
        price: 'Цена',
        pprice: 'Цена Набавна',
        fprice: 'Цена Фабричка',
        additionalInfo:'Дополнителни информации',
        VAT:'ДДВ',
        Tax: 'Данок',
        Amount:'Износ',
        Total: 'Вкупно',
        Shipping:'Достава',
        materialDetails: 'Детали за материјал',
        articleDetails: 'Детали за артикл',
        smallFormatDetails: 'Детали за мал формат',
        smallFormatMaterialDetails: 'Детали за мал формат материајал',
        addNewMaterialSmall: 'Додади материјал - мал формат',
        quantity: 'Количина',
        addMaterial: 'Додади материјал',
        addArticle: 'Додади артикл',
        addFormat: 'Додади формат',
        addSmallMaterial: 'Додади мал материјал',
        add:'Додади',
        NewRow:'Нов Ред',
        SmallFormatMaterials: 'Материјали - мал формат',
        listOfSmallFormat: 'Листа на материјали - мал формат',
        listOfSmallMaterials: 'Листа на Мали Материјали',
        listOfLargeMaterials:'Листа на Големи Материјали',
        pricePerUnit: 'Цена по парче',
        contact: 'Контакт',
        contacts: 'Контакти',
        otherContacts: 'Останати контакти',
        production: 'Продукција',
        dashboard: 'Преглед',
        actionInfo: 'Преглед на акција',
        addNewMaterialLarge:'Додади материјал - голем формат',
        addNewArticle:'Додади нов артикл',
        listOfLargeFormat: 'Листа на материјали - голем формат',
        listOfAllOrders: 'Листа на сите нарачки',
        listOfAllInvoices: 'Листа на сите фактурирани нарачки',
        listOfNotInvoiced:'Листа на нефактурирани нарачки',
        listOfAllStatements:'Листа на сите Изводи',
        ViewAllInvoices: 'Погледни ги сите нарачки',
        UninvoicedOrders:'Нефактурирани нарачки',
        invoicedOrders:'Фактурирани нарачки',
        listofallCardStatements:'Листа на сите извод картици',
        listOfIncomingInvoices:'Листа на влезни фактури',
        CardStatements:'Извод Карица',
        CCS:'Извод картица на клиент',
        bankStatement: 'Извод банка',
        currentReport:'Тековен извештај',
        invoiceGeneration:'Генерирање на фактура',
        invoiceEdit: 'Уреди фактура',
        InvoiceDetails: 'Детали за нарачка',
        InvoiceReview: 'Преглед на нарачка',
        totalm: 'Вкупно м.',
        shippingTo: 'Достава до',
        jobPriceCost: 'Цена на продукт (потрошено)',
        jobPrice: 'Цена на продукт',
        salePrice: 'Продажна цена',
        address: 'Адреса',
        city: "Град",
        Nr:'Бр.',
        from: 'Од',
        to: 'До',
        owes: 'Должи',
        requests: 'Платил',
        balance: 'Баланс',
        totalBalance: 'Вкупен баланс',
        date: 'Датум',
        number: 'Број',
        document: 'Документ',
        incomingInvoice: 'Влезна фактура',
        outcomeInvoice: 'Излезна фактура',
        statementIncome: 'Извод - уплата',
        statementExpense: 'Извод - исплата',
        analyticsInvoice: 'Аналитика на налози - корисници',
        receiptId: 'Приемница бр.',
        other: 'Друго',
        actionQuantity: 'Количина доработка',
        offerClient: 'Понуди за клиенти',
        offer: 'Понуда',
        productionTime: 'Време на изработка',
        analytics: 'Аналитика',
        articleAnalytics: 'Аналитика на артикли',
        selectChartType: 'Избери тип на график',
        pie: 'Пита - график',
        bar: 'Бар - график',
        line: 'Линија - график',
        error: 'Грешка',
        detailedBreakdown: 'Детален преглед',
        orderCount: 'Број на налози',
        percentage: 'Процент',
        clientInvoiceAnalytics: 'Аналитика на налози - клиенти',
        clientInvoiceCostsAnalytics: 'Аналитика на трошоци на налози - клиенти',
        invoiceCostsDen: 'Трошоци за налог (ден.)',
        userInvoiceAnalytics: 'Аналитика на налози - корисници',
        userName: 'Име на корисник',
        workerAnalytics: 'Аналитика на работници',
        workedId: 'Работник бр.',
        totalTimeSpent: 'Вкупно време потрошено',
        invoicesWorkedOn: 'Број на изработени налози',
        selectDateRange: 'Избери датум',
        year: 'Година',
        month: 'Месец',
        day: 'Ден',
        reset: 'Ресетирај',
        apply: 'Филтрирај'
    },
};

export const i18n = createI18n({
    locale: 'en', // Set the initial locale
    fallbackLocale: 'en', // Fallback to English if translation is missing
    messages,
});

export default i18n;

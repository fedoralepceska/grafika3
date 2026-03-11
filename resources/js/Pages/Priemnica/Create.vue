<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="receipt" subtitle="addNewReceipt" icon="Materials.png" link="receipt"/>
            <div class="dark-gray p-5">
                <div class="form-container p-2 light-gray">
                    <div class="flex gap-2 upper">
                        <div class="border p-2 mb-2 mag">
                            <h2 class="text-white bold">
                                {{$t('warehouse')}}
                            </h2>
                            <div class="px-4 py-1">
                                <select v-model="selectedWarehouseId" @change="updateWarehouseDetails" class="text-gray-700 rounded" style="width: 40vh;">
                                    <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">
                                        {{ warehouse.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="px-4 pb-1">
                                <input type="text" v-model="selectedWarehouse.address" class="text-gray-500 rounded" style="width: 40vh;" readonly>
                            </div>
                            <div class="px-4 pb-1">
                                <input type="text" v-model="selectedWarehouse.phone" class="text-gray-500 rounded" style="width: 40vh;" readonly>
                            </div>
                        </div>
                        <div class="border p-2 mb-2 cl flex gap-16">
                            <div>
                                <h2 class="text-white bold">
                                    {{ $t('client') }}
                                </h2>
                                <div class="px-4 py-1">
                                    <select v-model="selectedClientId" @change="updateClientDetails" class="text-gray-700 rounded" style="width: 72vh;">
                                        <option v-for="client in clients" :key="client.id" :value="client.id">
                                            {{ client.name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="px-4 pb-1 gap-1 flex">
                                    <input v-model="selectedClient.address" type="text" class="text-gray-500 rounded" style="width: 40vh;" readonly>
                                    <input v-model="selectedClient.city" type="text" class="text-gray-500 rounded" style="width: 31.3vh;" readonly>
                                </div>
                                <div class="px-4 pb-1 gap-1 flex">
                                    <input v-model="selectedClientCardStatement.name" type="text" class="text-gray-500 rounded" style="width: 25vh;" readonly>
                                    <input v-model="selectedClientCardStatement.phone" type="text" class="text-gray-500 rounded" style="width: 46.3vh;" readonly>
                                </div>
                            </div>
                            <div>
                                 <h2 class="text-white bold">{{$t('Date')}}</h2>
                                <input type="date" class="text-gray-700 rounded" style="width: 40vh;" v-model="selectedDate">
                            </div>
                        </div>
                    </div>
                    <h2 class="sub-title">
                        {{ $t('receiptDetails') }}
                    </h2>
                    <div class="button-container action-toolbar mb-2 gap-2">
                        <SecondaryButton @click="addRow" type="submit" class="white">{{ $t('NewRow') }}</SecondaryButton>
                        <SecondaryButton @click="deleteRow" class="red" type="submit">
                            {{ $t('Delete') }}<span v-if="selectedRowIndexes.length > 0"> ({{ selectedRowIndexes.length }})</span>
                        </SecondaryButton>
                        <SecondaryButton @click="triggerImportFilePicker" class="teal" type="button">
                            {{ $t('importFromExcel') || 'Import from Excel' }}
                        </SecondaryButton>
                        <input
                            ref="importFileInput"
                            type="file"
                            accept=".xlsx,.xls"
                            class="hidden-import-input"
                            @change="handleImportFileChange"
                        >
                    </div>
                    <div v-if="rows.length > 0" class="row-actions mb-2">
                        <label class="row-actions-label">
                            <input type="checkbox" v-model="selectAllRows" @change="toggleSelectAllRows">
                            {{ $t('selectAllRows') || 'Select all rows' }}
                        </label>
                        <span class="row-actions-count">{{ selectedRowIndexes.length }} / {{ rows.length }}</span>
                    </div>
                    <div v-if="showImportMapping" class="import-mapping-box mb-2">
                        <h3 class="import-mapping-title">{{ $t('mapColumns') || 'Map columns' }}</h3>
                        <div class="import-mapping-grid">
                            <div class="import-map-item">
                                <label>{{ $t('Code') }}</label>
                                <select v-model.number="importMapping.code_col" class="text-gray-700 rounded">
                                    <option :value="null">--</option>
                                    <option v-for="(h, i) in importHeaders" :key="'code_'+i" :value="i">{{ h || ('Column ' + (i + 1)) }}</option>
                                </select>
                            </div>
                            <div class="import-map-item">
                                <label>{{ $t('articleName') }}</label>
                                <select v-model.number="importMapping.name_col" class="text-gray-700 rounded">
                                    <option :value="null">--</option>
                                    <option v-for="(h, i) in importHeaders" :key="'name_'+i" :value="i">{{ h || ('Column ' + (i + 1)) }}</option>
                                </select>
                            </div>
                            <div class="import-map-item">
                                <label>{{ $t('Qty') }} *</label>
                                <select v-model.number="importMapping.quantity_col" class="text-gray-700 rounded">
                                    <option :value="null">--</option>
                                    <option v-for="(h, i) in importHeaders" :key="'qty_'+i" :value="i">{{ h || ('Column ' + (i + 1)) }}</option>
                                </select>
                            </div>
                            <div class="import-map-item">
                                <label>{{ $t('price') }}</label>
                                <select v-model.number="importMapping.price_col" class="text-gray-700 rounded">
                                    <option :value="null">--</option>
                                    <option v-for="(h, i) in importHeaders" :key="'price_'+i" :value="i">{{ h || ('Column ' + (i + 1)) }}</option>
                                </select>
                            </div>
                            <div class="import-map-item">
                                <label>{{ $t('VAT') }}</label>
                                <select v-model.number="importMapping.vat_col" class="text-gray-700 rounded">
                                    <option :value="null">--</option>
                                    <option v-for="(h, i) in importHeaders" :key="'vat_'+i" :value="i">{{ h || ('Column ' + (i + 1)) }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="import-map-actions">
                            <SecondaryButton class="teal" type="button" @click="applyImportMappingPreview">
                                {{ $t('previewImport') || 'Preview import' }}
                            </SecondaryButton>
                        </div>
                    </div>
                    <form @submit.prevent="" class="flex gap-3 justify-center overflow-x-auto">
                        <table class="excel-table">
                            <thead>
                            <tr>
                                <th style="width: 20px"></th>
                                <th style="width: 45px;">{{$t('Nr')}}</th>
                                <th>{{$t('Code')}}<div class="resizer" @mousedown="initResize($event, 2)"></div></th>
                                <th>{{$t('articleName')}}<div class="resizer" @mousedown="initResize($event, 3)"></div></th>
                                <th>{{$t('Qty')}}<div class="resizer" @mousedown="initResize($event, 4)"></div></th>
                                <th>{{$t('price')}} (.ден)<div class="resizer" @mousedown="initResize($event, 5)"></div></th>
                                <th>{{$t('VAT')}}%<div class="resizer" @mousedown="initResize($event, 6)"></div></th>
                                <th>{{$t('price')}} {{$t('VAT')}}<div class="resizer" @mousedown="initResize($event, 7)"></div></th>
                                <th>{{$t('Amount')}}<div class="resizer" @mousedown="initResize($event, 8)"></div></th>
                                <th>{{$t('Tax')}}<div class="resizer" @mousedown="initResize($event, 9)"></div></th>
                                <th>{{$t('Total')}}<div class="resizer" @mousedown="initResize($event, 10)"></div></th>
                                <th>{{$t('importStatus') || 'Import status'}}<div class="resizer" @mousedown="initResize($event, 11)"></div></th>
                                <th>{{$t('comment')}}<div class="resizer" @mousedown="initResize($event, 12)"></div></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="(row, index) in rows" :key="index" :class="importRowClass(row)">
                                <td>
                                    <input type="checkbox" v-model="selectedRowIndexes" :value="index" @change="onRowSelectionChange">
                                </td>
                                <td>{{ index + 1 }}</td>
                                <td>
                                    <ArticleSearchDropdown
                                        v-model="row.code"
                                        :placeholder="$t('Code')"
                                        search-type="code"
                                        @article-selected="(article) => handleArticleSelected(article, index)"
                                    />
                                </td>
                                <td>
                                    <ArticleSearchDropdown
                                        v-model="row.name"
                                        :placeholder="$t('articleName')"
                                        search-type="name"
                                        @article-selected="(article) => handleArticleSelected(article, index)"
                                    />
                                </td>
                                <td><input v-model.number="row.quantity" min="0.00001" step="0.00001" type="number" class="table-input" @input="updateRowValues(index)"></td>
                                <td><input v-model.number="row.purchase_price" min="0.01" step="0.01" type="number" class="table-input" @input="updateRowValues(index)"></td>
                                <td>
                                    <select v-model="row.tax_type" @change="updateRowValues(index)" class="table-input">
                                        <option value="1">18%</option>
                                        <option value="2">5%</option>
                                        <option value="3">10%</option>
                                        <option value="0">0%</option>
                                    </select>
                                </td>
                                <td>{{formatNumber(row.priceWithVAT) }}</td>
                                <td>{{ formatNumber(row.amount) }}</td>
                                <td>{{ formatNumber(row.tax) }}</td>
                                <td>{{ formatNumber(row.total) }}</td>
                                <td>
                                    <span v-if="row.import_action === 'update'" class="import-badge update">
                                        {{ $t('willUpdate') || 'Will update stock' }}
                                    </span>
                                    <span v-else-if="row.import_action === 'dropped'" class="import-badge dropped">
                                        {{ $t('willDrop') || 'Will be dropped' }}
                                    </span>
                                </td>
                                <td><input v-model="row.comment" type="text" class="table-input"></td>
                            </tr>
                            </tbody>
                        </table>
                    </form>
                    <div class="button-container mt-10">
                        <PrimaryButton @click="showCreateConfirm = true" type="button">
                            {{ $t('createReceipt') || 'Create Receipt' }}
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showCreateConfirm" class="confirm-overlay" @click.self="showCreateConfirm = false">
            <div class="confirm-modal">
                <h3 class="confirm-title">{{ $t('confirmCreateReceiptTitle') || 'Create receipt?' }}</h3>
                <p class="confirm-text">{{ $t('confirmCreateReceiptQuestion') || 'Are you sure you want to create this receipt?' }}</p>
                <div class="confirm-actions">
                    <SecondaryButton type="button" class="white" @click="showCreateConfirm = false">
                        {{ $t('cancel') || 'Cancel' }}
                    </SecondaryButton>
                    <PrimaryButton type="button" @click="confirmCreateReceipt">
                        {{ $t('createReceipt') || 'Create Receipt' }}
                    </PrimaryButton>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script>
import axios from 'axios';
import PrimaryButton from "@/Components/buttons/PrimaryButton.vue";
import MainLayout from "@/Layouts/MainLayout.vue";
import { useToast } from "vue-toastification";
import Header from "@/Components/Header.vue";
import SecondaryButton from "@/Components/buttons/SecondaryButton.vue";
import ArticleSearchDropdown from "@/Components/ArticleSearchDropdown.vue";

export default {
    name: 'Create',
    components: {
        SecondaryButton,
        Header,
        MainLayout,
        PrimaryButton,
        ArticleSearchDropdown
    },
    data() {
        return {
            rows: [],
            selectedRowIndexes: [],
            selectAllRows: false,
            isImportingExcel: false,
            importFile: null,
            importHeaders: [],
            showImportMapping: false,
            importMapping: {
                code_col: null,
                name_col: null,
                quantity_col: null,
                price_col: null,
                vat_col: null,
            },
            startX: 0,
            startWidth: 0,
            columnIndex: -1,
            selectedDate: new Date().toISOString().split('T')[0], // Default to today's date

            warehouses: [],
            articles: [],
            selectedWarehouseId: null,
            selectedWarehouse: {
                address: '',
                phone: ''
            },

            clients: [],
            selectedClientId: null,
            selectedClient: {
                address: '',
                city: ''
            },
            selectedClientCardStatement: {
                name: '',
                phone: ''
            },
            showCreateConfirm: false,
        };
    },
    mounted() {
        this.fetchWarehouses();
        this.fetchClients();
        this.fetchArticles();
    },
    methods: {
        //Clients fetching and prefilling
        fetchClients() {
            axios.get('/api/clients') // Adjust the URL to your endpoint
                .then(response => {
                    this.clients = response.data;
                })
                .catch(error => {
                    console.error('Error fetching clients:', error);
                });
        },
        updateClientDetails() {
            const selected = this.clients.find(client => client.id === this.selectedClientId);
            if (selected) {
                this.selectedClient = {
                    address: selected.address,
                    city: selected.city
                };
                if (selected.client_card_statement) {
                    this.selectedClientCardStatement = {
                        name: selected.client_card_statement.name,
                        phone: selected.client_card_statement.phone
                    };
                } else {
                    this.selectedClientCardStatement = {
                        name: '',
                        phone: ''
                    };
                }
            } else {
                this.selectedClient = {
                    address: '',
                    city: ''
                };
                this.selectedClientCardStatement = {
                    name: '',
                    phone: ''
                };
            }
        },

        //Warehouse fetching and prefilling
        fetchWarehouses() {
            axios.get('/api/warehouses')
                .then(response => {
                    this.warehouses = response.data;
                })
                .catch(error => {
                    console.error('Error fetching warehouses:', error);
                });
        },
        async fetchArticles() {
            try {
                let response = await axios.get('/articles'); // Adjust this endpoint to your API route
                this.articles = response.data;
            } catch (error) {
                console.error("Failed to fetch articles:", error);
            }
        },
        updateWarehouseDetails() {
            const selected = this.warehouses.find(warehouse => warehouse.id === this.selectedWarehouseId);
            if (selected) {
                this.selectedWarehouse = {
                    address: selected.address,
                    phone: selected.phone
                };
            } else {
                this.selectedWarehouse = {
                    address: '',
                    phone: ''
                };
            }
        },
        triggerImportFilePicker() {
            this.$refs.importFileInput?.click();
        },
        async handleImportFileChange(event) {
            const file = event.target?.files?.[0];
            if (!file) return;

            const toast = useToast();
            this.isImportingExcel = true;
            try {
                this.importFile = file;
                const form = new FormData();
                form.append('file', file);
                const { data } = await axios.post('/receipt/import/parse', form, {
                    headers: { 'Content-Type': 'multipart/form-data', 'Accept': 'application/json' },
                });
                this.importHeaders = data.headers || [];
                this.showImportMapping = true;
                this.setDefaultImportMapping();
                toast.info(this.$t('mapColumns') || 'Map columns');
            } catch (error) {
                toast.error(error?.response?.data?.message || 'Failed to import preview.');
            } finally {
                this.isImportingExcel = false;
                if (this.$refs.importFileInput) {
                    this.$refs.importFileInput.value = '';
                }
            }
        },
        setDefaultImportMapping() {
            const normalized = this.importHeaders.map(h => String(h || '').toLowerCase().trim());
            const findByAliases = (aliases) => {
                const idx = normalized.findIndex(h => aliases.includes(h));
                return idx >= 0 ? idx : null;
            };

            this.importMapping.code_col = findByAliases(['code', 'article code', 'sifra', 'шифра']);
            this.importMapping.name_col = findByAliases(['name', 'article', 'article name', 'item', 'назив', 'име']);
            this.importMapping.quantity_col = findByAliases(['qty', 'quantity', 'kolicina', 'количина']);
            this.importMapping.price_col = findByAliases(['purchase price', 'pprice', 'price', 'цена']);
            this.importMapping.vat_col = findByAliases(['vat', 'ddv', 'tax', 'данок']);
        },
        async applyImportMappingPreview() {
            const toast = useToast();
            if (!this.importFile) {
                toast.error('Select file first.');
                return;
            }
            if (this.importMapping.quantity_col === null || this.importMapping.quantity_col === undefined || this.importMapping.quantity_col === '') {
                toast.error('Quantity mapping is required.');
                return;
            }
            if (this.importMapping.code_col === null && this.importMapping.name_col === null) {
                toast.error('Map at least Code or Name.');
                return;
            }

            this.isImportingExcel = true;
            try {
                const form = new FormData();
                form.append('file', this.importFile);
                form.append('mapping[quantity_col]', this.importMapping.quantity_col);
                if (this.importMapping.code_col !== null) form.append('mapping[code_col]', this.importMapping.code_col);
                if (this.importMapping.name_col !== null) form.append('mapping[name_col]', this.importMapping.name_col);
                if (this.importMapping.price_col !== null) form.append('mapping[price_col]', this.importMapping.price_col);
                if (this.importMapping.vat_col !== null) form.append('mapping[vat_col]', this.importMapping.vat_col);

                const { data } = await axios.post('/receipt/import/preview', form, {
                    headers: { 'Content-Type': 'multipart/form-data', 'Accept': 'application/json' },
                });

                const importedRows = (data.rows || []).map((row) => ({
                    code: row.code || '',
                    name: row.name || '',
                    quantity: Number(row.quantity) || 1,
                    purchase_price: Number(row.purchase_price) || 0,
                    tax_type: String(row.tax_type ?? '1'),
                    priceWithVAT: 0,
                    amount: 0,
                    tax: 0,
                    total: 0,
                    comment: '',
                    import_action: row.import_action || null,
                    allow_article_create: !!row.allow_article_create,
                }));

                this.rows = importedRows;
                this.selectedRowIndexes = [];
                this.selectAllRows = false;
                this.rows.forEach((_, index) => this.updateRowValues(index));
                this.showImportMapping = false;

                if (this.rows.length === 0) {
                    toast.info(this.$t('noRowsToPreview') || 'No data rows to preview.');
                } else {
                    toast.success((this.$t('previewImport') || 'Preview import') + `: ${this.rows.length}`);
                }
            } catch (error) {
                toast.error(error?.response?.data?.message || 'Failed to import preview.');
            } finally {
                this.isImportingExcel = false;
            }
        },
        importRowClass(row) {
            if (row.import_action === 'create') return 'import-row-create';
            if (row.import_action === 'update') return 'import-row-update';
            if (row.import_action === 'dropped') return 'import-row-dropped';
            return '';
        },
        addRow() {
            this.rows.push({
                code: '',
                name: '',
                quantity: 1,
                purchase_price: 0,
                tax_type: '1',
                priceWithVAT: 0,
                amount: 0,
                tax: 0,
                total: 0,
                comment: '',
                import_action: null,
                allow_article_create: false,
            });
            
            // Calculate initial values for the new row
            const index = this.rows.length - 1;
            this.updateRowValues(index);
            this.onRowSelectionChange();
        },
        deleteRow() {
            if (this.selectedRowIndexes.length > 0) {
                const toDelete = new Set(this.selectedRowIndexes);
                this.rows = this.rows.filter((_, idx) => !toDelete.has(idx));
                this.selectedRowIndexes = [];
                this.selectAllRows = false;
                return;
            }

            if (this.rows.length > 0) {
                this.rows.pop(); // fallback to old behavior if nothing selected
            }
            this.onRowSelectionChange();
        },
        toggleSelectAllRows() {
            if (this.selectAllRows) {
                this.selectedRowIndexes = this.rows.map((_, idx) => idx);
            } else {
                this.selectedRowIndexes = [];
            }
        },
        onRowSelectionChange() {
            this.selectAllRows = this.rows.length > 0 && this.selectedRowIndexes.length === this.rows.length;
        },
        formatNumber(number) {
            const num = Number(number);
            if (isNaN(num)) {
                console.warn('formatNumber received NaN:', number);
                return '0.00';
            }
            return num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        updateRowValues(index) {
            let row = this.rows[index];
            console.log('Calculating VAT for row:', row); // Debug log
            
            // Ensure all values are numbers
            const price = Number(row.purchase_price) || 0;
            const quantity = Number(row.quantity) || 0;
            const vatPercentage = this.taxTypePercentage(row.tax_type);
            
            console.log('Price:', price, 'Quantity:', quantity, 'VAT %:', vatPercentage); // Debug log
            
            // Calculate values with proper number handling
            row.priceWithVAT = price + (price * vatPercentage / 100);
            row.amount = quantity * price;
            row.tax = row.amount * vatPercentage / 100;
            row.total = row.amount + row.tax;
            
            console.log('Calculated values:', {
                priceWithVAT: row.priceWithVAT,
                amount: row.amount,
                tax: row.tax,
                total: row.total
            }); // Debug log
        },
        taxTypePercentage(taxType) {
            console.log('Tax type received:', taxType, 'Type:', typeof taxType); // Debug log
            // Convert to string for comparison since it's coming from varchar
            const type = String(taxType);
            switch (type) {
                case '1':
                    return 18;
                case '2':
                    return 5;
                case '3':
                    return 10;
                default:
                    console.log('No matching tax type found for:', type); // Debug log
                    return 0;
            }
        },
        initResize(event, index) {
            this.startX = event.clientX;
            this.startWidth = event.target.parentElement.offsetWidth;
            this.columnIndex = index;

            document.documentElement.addEventListener('mousemove', this.resizeColumn);
            document.documentElement.addEventListener('mouseup', this.stopResize);
        },
        resizeColumn(event) {
            const diffX = event.clientX - this.startX;
            const newWidth = this.startWidth + diffX;
            const ths = document.querySelectorAll('.excel-table th');

            if (this.columnIndex >= 0 && this.columnIndex < ths.length) {
                ths[this.columnIndex].style.width = `${newWidth}px`;
            }
        },
        stopResize() {
            document.documentElement.removeEventListener('mousemove', this.resizeColumn);
            document.documentElement.removeEventListener('mouseup', this.stopResize);
        },
        addPrimenica() {
            const toast = useToast();
            if (!this.selectedWarehouseId) {
                toast.error('Please select a warehouse.');
                return;
            }
            if (!this.selectedDate) {
                toast.error('Please select a date.');
                return;
            }

            const validRows = this.rows.filter(row => (row.code || row.name) && Number(row.quantity) > 0);
            if (validRows.length === 0) {
                toast.error('Please add at least one valid row with quantity.');
                return;
            }

            const receiptData = [];

            for (const row of validRows) {
                receiptData.push({
                    client_id: this.selectedClientId || null,
                    warehouse: this.selectedWarehouseId,
                    date: this.selectedDate, // Add selected date to the data
                    ...row, // Include all other data from each row
                });
            }
            axios.post('/receipt/create', receiptData)
                .then((response) => {
                    this.dialog = false;
                    toast.success('Receipt created successfully!');

                    this.$inertia.visit('/receipt');
                })

                .catch((error) => {
                    toast.error('Failed to create receipt!');
                });
        },
        confirmCreateReceipt() {
            this.showCreateConfirm = false;
            this.addPrimenica();
        },
        handleArticleSelected(article, index) {
            console.log('Selected article:', article); // Debug log
            this.rows[index] = {
                ...this.rows[index],
                code: article.code,
                name: article.name,
                purchase_price: article.purchase_price,
                tax_type: article.tax_type,
                quantity: 1,
                import_action: 'update',
                allow_article_create: false,
            };
            console.log('Updated row:', this.rows[index]); // Debug log
            this.updateRowValues(index);
            this.addRow();
        },
    },
};
</script>

<style scoped lang="scss">
$background-color: #1a2732;
$gray: #3c4e59;
$dark-gray: #2a3946;
$light-gray: #54606b;
$ultra-light-gray: #77808b;
$white: #ffffff;
$black: #000000;
$hover-color: #4a5a68;
$green: #408a0b;
$red: #9e2c30;

.table-input {
    background-color: transparent;
    border: transparent;
}

.table-input:focus {
    background-color: $ultra-light-gray;
    border:transparent;
}
.upper{
    display: flex;
    justify-content: space-around;
}
.mag {
    width: fit-content;
    border-radius: 3px;
}
.cl {
    border-radius: 3px;
}
.bold {
    font-weight: bolder;
}
fieldset {
    border: 1px solid #ffffff;
    border-radius: 3px;
    width: fit-content;
    padding-right: 35px;
}
legend {
    margin-left: 10px;
    color: white;
}
#taxA {
    width: 120px;
}
#taxA2 {
    width: 80px;
}
.green-text {
    color: $green;
}
.red{
    background-color: $red;
    color: white;
    border: none;
}
.red:hover{
    background-color: darkred;
}
.white:hover{
    background-color: lightgray;
}
.teal{
    background-color: #0d9488;
    color: white;
    border: none;
}
.teal:hover{
    background-color: #0f766e;
}
.hidden-import-input{
    display: none;
}
.import-mapping-box{
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 6px;
    padding: 12px;
    background: rgba(0,0,0,0.12);
}
.import-mapping-title{
    color: #fff;
    font-size: 15px;
    margin-bottom: 10px;
    font-weight: 600;
}
.import-mapping-grid{
    display: grid;
    grid-template-columns: repeat(5, minmax(130px, 1fr));
    gap: 10px;
}
.import-map-item{
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.import-map-item label{
    color: #fff;
    font-size: 12px;
}
.import-map-item select{
    width: 100%;
    border: 1px solid rgba(255,255,255,0.25);
    background: rgba(255,255,255,0.95);
}
.import-map-actions{
    margin-top: 10px;
    display: flex;
    justify-content: flex-end;
}
.header {
    display: flex;
    align-items: center;
}
.dark-gray {
    background-color: $dark-gray;
    justify-content: left;
    align-items: center;
    min-height: 20vh;
}
.light-gray {
    background-color: $light-gray;
}
.client-form {
    width: 100%;
    max-width: 1000px;
    justify-content: left;
    align-items: center;
    min-height: 20vh;
    padding: 5px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}
.sub-title {
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    color: $white;
}
.form-group {
    display: flex;
    justify-content: right;
    align-items: center;
    width: 350px;
    margin-bottom: 10px;
    color: $white;
}
.label {
    flex: 1;
    text-align: left;
    margin-right: 20px;
}
.button-container {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.action-toolbar{
    padding: 8px 10px;
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 6px;
    background: rgba(0,0,0,0.10);
}
.action-toolbar :deep(button){
    min-width: 150px;
    justify-content: center;
}
.row-actions{
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 6px 10px;
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: 6px;
    background: rgba(0,0,0,0.08);
}
.row-actions-label{
    color: #fff;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
}
.row-actions-count{
    color: rgba(255,255,255,0.8);
    font-size: 12px;
    background: rgba(255,255,255,0.12);
    border-radius: 999px;
    padding: 2px 8px;
}
.excel-table input[type="checkbox"]{
    width: 15px;
    height: 15px;
    accent-color: #0d9488;
    cursor: pointer;
}
.excel-table {
    border-collapse: separate;
    border-spacing: 0;
    width: 100%;
    color: $white;
    table-layout: fixed;
    background-color: $background-color;
    border: 1px solid $gray;
    border-radius: 4px;
    position: relative;
    margin-bottom: 1rem;
}

.import-row-create td {
    background: rgba(34, 197, 94, 0.12);
}

.import-row-update td {
    background: rgba(59, 130, 246, 0.12);
}
.import-row-dropped td {
    background: rgba(239, 68, 68, 0.12);
}

.import-badge {
    display: inline-block;
    border-radius: 4px;
    padding: 2px 6px;
    font-size: 11px;
    font-weight: 600;
}
.import-badge.create {
    background: rgba(34, 197, 94, 0.25);
    color: #bbf7d0;
}
.import-badge.update {
    background: rgba(59, 130, 246, 0.25);
    color: #bfdbfe;
}
.import-badge.dropped {
    background: rgba(239, 68, 68, 0.25);
    color: #fecaca;
}

.confirm-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.55);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2000;
    padding: 20px;
}

.confirm-modal {
    width: 100%;
    max-width: 460px;
    background: $dark-gray;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    padding: 18px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
}

.confirm-title {
    color: white;
    margin: 0 0 8px 0;
    font-size: 20px;
    font-weight: 700;
}

.confirm-text {
    color: rgba(255, 255, 255, 0.9);
    margin: 0;
    font-size: 14px;
}

.confirm-actions {
    margin-top: 16px;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

/* Column widths */
.excel-table {
    th:nth-child(1) { width: 40px; } /* Select */
    th:nth-child(2) { width: 50px; } /* Nr */
    th:nth-child(3) { width: 120px; } /* Code */
    th:nth-child(4) { width: 200px; } /* Article Name */
    th:nth-child(5) { width: 80px; } /* Qty */
    th:nth-child(6) { width: 100px; } /* Price */
    th:nth-child(7) { width: 80px; } /* VAT% */
    th:nth-child(8) { width: 100px; } /* Price VAT */
    th:nth-child(9) { width: 100px; } /* Amount */
    th:nth-child(10) { width: 100px; } /* Tax */
    th:nth-child(11) { width: 100px; } /* Total */
    th:nth-child(12) { width: 160px; } /* Import Status */
    th:nth-child(13) { width: 150px; } /* Comment */
}

.excel-table th,
.excel-table td {
    border: 1px solid $gray;
    text-align: center;
    position: relative;
    padding: 10px 8px;
    font-size: 0.9rem;
    transition: background-color 0.2s ease;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.excel-table th {
    background-color: $dark-gray;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.8rem;
    letter-spacing: 0.5px;
    padding-right: 20px; /* Space for resizer */
    user-select: none;
    position: sticky;
    top: 0;
    z-index: 2;
}

.excel-table td {
    background-color: rgba($background-color, 0.7);
    border-bottom: 1px solid $gray;
    border-right: 1px solid $gray;
    height: 42px; /* Fixed height for consistency */
}
.excel-table tbody tr:nth-child(even) td {
    background-color: rgba($background-color, 0.82);
}

/* Specific styling for numeric columns */
.excel-table td:nth-child(n+5):nth-child(-n+11) {
    font-family: 'Consolas', monospace;
    text-align: right;
    padding-right: 12px;
}

/* Hover effects */
.excel-table tbody tr {
    transition: all 0.2s ease;

    &:hover td {
        background-color: rgba($hover-color, 0.3);
    }

    &:nth-child(even) td {
        background-color: rgba($dark-gray, 0.3);
    }
}

/* Resizer styling */
.resizer {
    width: 4px;
    height: 100%;
    position: absolute;
    right: 0;
    top: 0;
    cursor: col-resize;
    user-select: none;
    background-color: $gray;
    opacity: 0;
    transition: opacity 0.2s ease;

    &:hover {
        opacity: 1;
        background-color: $ultra-light-gray;
    }
}

/* Table wrapper styling */
.table-wrapper {
    position: relative;
    overflow-x: auto;
    overflow-y: visible;
    margin: 0;
    padding: 1px;
    border-radius: 4px;
    background: $gray;

    &::-webkit-scrollbar {
        height: 8px;
    }

    &::-webkit-scrollbar-track {
        background: $dark-gray;
        border-radius: 4px;
    }

    &::-webkit-scrollbar-thumb {
        background: $light-gray;
        border-radius: 4px;

        &:hover {
            background: $ultra-light-gray;
        }
    }
}

/* Input styling within table */
.table-input {
    width: 100%;
    height: 100%;
    background-color: transparent;
    border: none;
    color: $white;
    padding: 8px;
    font-size: 0.9rem;
    outline: none;
    transition: background-color 0.2s ease;

    &::placeholder {
        color: $ultra-light-gray;
        opacity: 0.7;
    }

    &:focus {
        background-color: $dark-gray;
    }
}



/* Specific styling for numeric inputs */
input[type="number"].table-input {
    text-align: right;
    padding-right: 12px;
    font-family: 'Consolas', monospace;
}

@media (max-width: 1200px){
    .import-mapping-grid{
        grid-template-columns: repeat(3, minmax(130px, 1fr));
    }
}

@media (max-width: 768px){
    .import-mapping-grid{
        grid-template-columns: repeat(2, minmax(120px, 1fr));
    }
    .action-toolbar :deep(button){
        min-width: 130px;
    }
}
</style>

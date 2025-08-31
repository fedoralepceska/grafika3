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
                    <div class="button-container mb-2 gap-2">
                        <SecondaryButton @click="addRow" type="submit" class="white">{{ $t('NewRow') }}</SecondaryButton>
                        <SecondaryButton @click="deleteRow" class="red" type="submit">{{ $t('Delete') }}</SecondaryButton>
                    </div>
                    <form @submit.prevent="" class="flex gap-3 justify-center overflow-x-auto">
                        <table class="excel-table">
                            <thead>
                            <tr>
                                <th style="width: 20px">#</th>
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
                                <th>{{$t('comment')}}<div class="resizer" @mousedown="initResize($event, 11)"></div></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="(row, index) in rows" :key="index">
                                <td></td>
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
                                <td><input v-model="row.comment" type="text" class="table-input"></td>
                            </tr>
                            </tbody>
                        </table>
                    </form>
                    <div class="button-container mt-10">
                        <PrimaryButton @click="addPrimenica()" type="submit">{{ $t('add') }}</PrimaryButton>
                    </div>
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
            }
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
                comment: ''
            });
            
            // Calculate initial values for the new row
            const index = this.rows.length - 1;
            this.updateRowValues(index);
        },
        deleteRow() {
            if (this.rows.length > 0) {
                this.rows.pop();
            }
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
            const receiptData = [];

            for (const row of this.rows) {
                receiptData.push({
                    client_id: this.selectedClientId,
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
        handleArticleSelected(article, index) {
            console.log('Selected article:', article); // Debug log
            this.rows[index] = {
                ...this.rows[index],
                code: article.code,
                name: article.name,
                purchase_price: article.purchase_price,
                tax_type: article.tax_type,
                quantity: 1,
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
    justify-content: end;
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

/* Column widths */
.excel-table {
    th:nth-child(1) { width: 40px; } /* # */
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
    th:nth-child(12) { width: 150px; } /* Comment */
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
}

.excel-table td {
    background-color: rgba($background-color, 0.7);
    border-bottom: 1px solid $gray;
    border-right: 1px solid $gray;
    height: 42px; /* Fixed height for consistency */
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
</style>

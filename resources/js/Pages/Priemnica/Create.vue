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
                                <input type="date" class="text-gray-700 rounded" style="width: 40vh;">
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
                                    <input v-model="row.code" type="text" class="table-input" @keyup.enter="findArticleByCode(row.code, index)">
                                </td>
                                <td>
                                    <input v-model="row.name" type="text" class="table-input" @keyup.enter="findArticleByName(row.name, index)">
                                </td>
                                <td><input v-model.number="row.quantity" type="number" class="table-input" @input="updateRowValues(index)"></td>
                                <td>{{row.purchase_price}}</td>
                                <td>{{ taxTypePercentage(row.tax_type) }}%</td>
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

export default {
    name: 'Create',
    components: {SecondaryButton, Header, MainLayout, PrimaryButton },
    data() {
        return {
            rows: [],
            startX: 0,
            startWidth: 0,
            columnIndex: -1,

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
                price: 0,
                vat: 0,
                priceWithVAT: 0,
                amount: 0,
                tax: 0,
                total: 0,
                comment: ''
            });
        },
        deleteRow() {
            if (this.rows.length > 0) {
                this.rows.pop();
            }
        },
        formatNumber(number) {
            return Number(number).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        updateRowValues(index) {
            let row = this.rows[index];
            row.priceWithVAT = row.purchase_price + (row.purchase_price * this.taxTypePercentage(row.tax_type) / 100);
            row.amount = row.quantity * row.purchase_price;
            row.tax = row.amount * this.taxTypePercentage(row.tax_type) / 100;
            row.total = row.amount + row.tax;

        },
        taxTypePercentage(taxType) {
            switch (taxType) {
                case 1:
                    return 18;
                case 2:
                    return 5;
                case 3:
                    return 10;
                default:
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
                    ...row, // Include all other data from each row
                });
            }
            axios.post('/receipt/create', receiptData)
                .then((response) => {
                    this.dialog = false;
                    toast.success('Receipt created successfully!');

                    setTimeout(() => {
                        window.location.reload();
                    }, 1000); // Adding a slight delay before reload to ensure the toast message is displayed

                })

                .catch((error) => {
                    toast.error('Failed to create receipt!');
                });
        },
        findArticleByCode(code, index) {
            const toast = useToast();
            if (!code) return; // Handle empty code case

            const foundArticle = this.articles.data.find(article => article.code === code);
            if (foundArticle) {
                this.rows[index] = {
                    ...this.rows[index], // Preserve existing data
                    ...foundArticle, // Override with article data
                };
                this.addRow();
            } else {
                toast.error(`Article ${code} not found!`);
            }
        },
        findArticleByName(name, index) {
            const toast = useToast();
            if (!name) return; // Handle empty name case

            const foundArticle = this.articles.data.find(article => article.name.toLowerCase().includes(name.toLowerCase())); // Case-insensitive search
            if (foundArticle) {
                this.rows[index] = {
                    ...this.rows[index],
                    ...foundArticle,
                };
                this.addRow();
            } else {
                toast.error(`Article ${name} not found!`);
            }
        },
    },
};
</script>

<style scoped lang="scss">
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
    border-collapse: collapse;
    width: 100%;
    color: white;
    table-layout: fixed;
}
.excel-table th,
.excel-table td {
    border: 1px solid #dddddd;
    text-align: center;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
    position: relative;
}
.excel-table th {
    min-width: 50px;
}
.excel-table tr:nth-child(even) {
    background-color: $ultra-light-gray;
}
.resizer {
    width: 5px;
    height: 100%;
    position: absolute;
    right: 0;
    top: 0;
    cursor: col-resize;
    user-select: none;
    background-color: transparent;
}
</style>

<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="receipt" subtitle="allReceipts" icon="Materials.png" link="receipt" />
            <div class="form-container p15">
                <div class="dark-gray p-5 text-white">
                    <div class="form-container p-2 light-gray overflow-x-auto">
                        <h2 class="sub-title">
                            {{ $t('allReceipts') }}
                        </h2>
                        <div class="filters flex gap-10">
                            <!-- Client Filter -->
                            <div class="search-container pb-2">
                                <div class="mr-1 ml-2">Client</div>
                                <div class="ml-2">
                                    <select v-model="filters.client_id" class="rounded text-black">
                                        <option value="All" hidden>Clients</option>
                                        <option value="All">All Clients</option>
                                        <option v-for="client in clients" :key="client.id" :value="client.id">
                                            {{ client.name }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <!-- Warehouse Filter -->
                            <div class="search-container pb-2">
                                <div class="mr-1 ml-2">Warehouse</div>
                                <div class="ml-2">
                                    <select v-model="filters.warehouse_id" class="rounded text-black">
                                        <option value="All" hidden>Warehouses</option>
                                        <option value="All">All Warehouses</option>
                                        <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">
                                            {{ warehouse.name }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <!-- Date Range Filters -->
                            <div class="flex">
                                <div class="search-container pb-2">
                                    <div class="mr-1 ml-2">From Date</div>
                                    <div class="ml-2">
                                        <input type="date" v-model="filters.from_date" class="rounded">
                                    </div>
                                </div>
                                <div class="search-container pb-2">
                                    <div class="mr-1 ml-2">To Date</div>
                                    <div class="ml-2">
                                        <input type="date" v-model="filters.to_date" class="rounded">
                                    </div>
                                </div>
                            </div>
                            <!-- Filter Button -->
                            <div class="buttF gap-3">
                                <button @click="applyFilter" class="btn create-order1">Filter</button>
                            </div>
                        </div>
                        <table class="excel-table">
                            <thead>
                            <tr>
                                <th style="width: 45px;">{{$t('Nr')}}</th>
                                <th style="width: 75px">{{$t('ID')}}</th>
                                <th>{{$t('date')}}<div class="resizer" @mousedown="initResize($event, 1)"></div></th>
                                <th>{{$t('warehouse')}}<div class="resizer" @mousedown="initResize($event, 2)"></div></th>
                                <th>{{$t('client')}}<div class="resizer" @mousedown="initResize($event, 3)"></div></th>
                                <th>{{$t('price')}} (.ден)<div class="resizer" @mousedown="initResize($event, 4)"></div></th>
                                <th>{{$t('comment')}}<div class="resizer" @mousedown="initResize($event, 5)"></div></th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="(receipt, index) in localReceipts" :key="receipt.id">
                                <th>{{index + 1}}</th>
                                <th>{{receipt.id}}</th>
                                <th>{{ new Date(receipt.created_at).toLocaleDateString('en-GB') }}</th>
                                <th>{{receipt.warehouse_name}}</th>
                                <th>{{receipt.client.name}}</th>
                                <th>{{ calculateTotalPrice(receipt.articles) }}</th>
                                <th>{{receipt.comment ? receipt.comment: '/'}}</th>
                                <th>
                                    <div class="centered">
                                        <PriemInfoDialog :priem="receipt" />
                                    </div>
                                </th>
                            </tr>
                            </tbody>
                        </table>
                        <Pagination :pagination="receipts" />
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script>
import MainLayout from "@/Layouts/MainLayout.vue";
import Pagination from "@/Components/Pagination.vue";
import Header from "@/Components/Header.vue";
import PriemInfoDialog from "@/Components/PriemInfoDialog.vue";
import axios from "axios";

export default {
    components: {
        MainLayout,
        Pagination,
        Header,
        PriemInfoDialog
    },
    props: {
        receipts: Array,
        clients: Array,
        warehouses: Array,
    },
    data() {
        return {
            localReceipts: [...this.receipts],
            filters: {
                client_id: 'All',
                warehouse_id: 'All',
                from_date: '',
                to_date: '',
            },
            startX: 0,
            startWidth: 0,
            columnIndex: -1,
        };
    },
    methods: {
        calculateTotalPrice(articles) {
            return articles.reduce((total, article) => {
                const articleTotal = (article.purchase_price || 0) * (article.pivot.quantity || 0);
                return total + articleTotal;
            }, 0);
        },
        applyFilter() {
            axios.get('/receipt', { params: this.filters })
                .then(response => {
                    this.localReceipts = response.data;
                })
                .catch(error => {
                    console.error('Error applying filters:', error);
                });
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
        }
    },
};
</script>

<style scoped lang="scss">
.centered {
    display: flex;
    justify-content: center;
    align-items: center;
}
.filters{
    justify-content: space-between;
}
select{
    width: 240px;
}
.buttF{
    padding-top: 23.5px;
}
.btn {
    padding: 9px 12px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    border-radius: 2px;
}
.create-order1{
    background-color: $blue;
    color: white;
}
.centered {
    display: flex;
    justify-content: center;
    align-items: center;
}
.delete{
    border: none;
    color: white;
    background-color: $red;
}
.delete:hover{
    background-color: darkred;
}
.green-text{
    color: $green;
}
.blue{
    background-color: $blue;
    border: none;
    color: white;
}
.blue:hover{
    background-color: cornflowerblue;
}
.green{
    background-color: $green;
}
.header{
    display: flex;
    align-items: center;
}
.dark-gray {
    background-color: $dark-gray;
    justify-content: left;
    align-items: center;
    min-height: 20vh;
    min-width: 80vh;
}
.light-gray{
    background-color: $light-gray;
}

.client-form {
    width: 100%;
    justify-content: left;
    align-items: center;
    min-height: 20vh;
    padding: 5px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}
.page-title {
    font-size: 24px;
    display: flex;
    align-items: center;
    color: $white;
}
.sub-title{
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    color: $white;
}

.image-icon {
    margin-left: 2px;
    max-width: 40px;
}
.form-group {
    display: flex;
    justify-content: right;
    align-items: center;
    width: 300px;
    margin-bottom: 10px;
    color: $white;
}

.label {
    flex: 1;
    text-align: left;
    margin-right: 20px;
}
.button-container{
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
    padding: 4px;
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
.info {
    border: 2px solid white;
    min-width: 90vh;
    max-width: 100vh;
}
.contact-info {
    display: flex;
    flex-direction: row;
    align-items: center;
}
</style>

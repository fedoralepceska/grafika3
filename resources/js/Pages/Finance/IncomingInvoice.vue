<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="invoice2" subtitle="incomingInvoice" icon="invoice.png" link="incomingInvoice"/>
            <div class="dark-gray p-2 text-white overflow-x-hidden">
                <RedirectTabs :route="$page.url" />
                <div class="form-container p-2">
                    <div class="flex justify-between items-center align-middle">
                        <h2 class="sub-title">
                            {{ $t('listOfIncomingInvoices') }}
                        </h2>
                    <div style="margin-top: -12px;">
                        <AddIncomingInvoiceDialog 
                                    :incomingInvoice="incomingInvoice"
                                    :cost-types="$page.props.costTypes"
                                    :bill-types="$page.props.billTypes"
                                    @invoice-added="handleInvoiceAdded"
                                />
                    </div>
                    </div>
                    <div class="filter-container">
                        <!-- Search Section -->
                        <div class="search-section mb-4">
                            <div class="flex items-center gap-2">
                                <div class="flex-1">
                                    <input 
                                        v-model="searchQuery" 
                                        placeholder="Search by invoice number..." 
                                        class="w-full text-black px-4 py-2 rounded border border-gray-300 focus:outline-none focus:border-blue-500" 
                                        @keyup.enter="searchInvoices"
                                    />
                                </div>
                                <button class="btn create-order1 px-6" @click="searchInvoices">
                                    <i class="fa fa-search mr-2"></i>Search
                                </button>
                            </div>
                        </div>

                        <!-- Filters Section -->
                        <div class="filters-section bg-gray-800 p-4 rounded mb-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
                                <div class="filter-group">
                                    <label class="block text-sm mb-2">Client</label>
                                    <select v-model="filterClient" class="w-full text-black px-2 py-1.5 rounded">
                                        <option value="All">All Clients</option>
                                        <option v-for="client in $page.props.clients" :key="client.id" :value="client.id">
                                            {{ client.name }}
                                        </option>
                                    </select>
                                </div>

                                <div class="filter-group">
                                    <label class="block text-sm mb-2">Warehouse</label>
                                    <select v-model="filterWarehouse" class="w-full text-black px-2 py-1.5 rounded">
                                        <option value="">All Warehouses</option>
                                        <option v-for="warehouse in $page.props.warehouses" :key="warehouse" :value="warehouse">
                                            {{ warehouse }}
                                        </option>
                                    </select>
                                </div>

                                <div class="filter-group">
                                    <label class="block text-sm mb-2">Cost Type</label>
                                    <select v-model="filterCostType" class="w-full text-black px-2 py-1.5 rounded">
                                        <option value="">All Cost Types</option>
                                        <option v-for="type in $page.props.costTypes" :key="type.id" :value="type.id">
                                            {{ type.name }}
                                        </option>
                                    </select>
                                </div>

                                <div class="filter-group">
                                    <label class="block text-sm mb-2">Bill Type</label>
                                    <select v-model="filterBillType" class="w-full text-black px-2 py-1.5 rounded">
                                        <option value="">All Bill Types</option>
                                        <option v-for="type in $page.props.billTypes" :key="type.id" :value="type.id">
                                            {{ type.name }}
                                        </option>
                                    </select>
                                </div>

                                <div class="filter-group">
                                    <label class="block text-sm mb-2">Date Order</label>
                                    <select v-model="sortOrder" class="w-full text-black px-2 py-1.5 rounded">
                                        <option value="desc">Newest to Oldest</option>
                                        <option value="asc">Oldest to Newest</option>
                                    </select>
                                </div>
                            </div>

                            <div class="flex justify-end items-center mt-4">
                                <button @click="applyFilter" class="btn create-order1 px-6">
                                    <i class="fa fa-filter mr-2"></i>Apply Filters
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-if="incomingInvoice.data" class="overflow-x-auto">
                        <div class="border mb-1" v-for="faktura in incomingInvoice.data" :key="faktura.id">
                            <div class="bg-white text-black flex justify-between">
                                <div class="p-2 bold">Invoice #{{faktura.incoming_number}}/{{ new Date(faktura.created_at).toLocaleDateString('en-US', { year: 'numeric' }) }}</div>
                                <div class="flex items-center">
                                    <div class="p-2 bold">{{ new Date(faktura.date).toLocaleDateString('en-US', { year: 'numeric', month: '2-digit', day: '2-digit' }) }}</div>
                                    <EditIncomingInvoiceDialog 
                                        :invoice="faktura"
                                        :cost-types="$page.props.costTypes"
                                        :bill-types="$page.props.billTypes"
                                        :warehouses="$page.props.warehouses"
                                        :clients="$page.props.clients"
                                        @invoice-updated="handleInvoiceUpdated"
                                    />
                                </div>
                            </div>
                            <div class="flex flex-wrap justify-between gap-4 p-2">
                                <div class="info">
                                    <div>Customer</div>
                                    <div class="bold">{{ faktura.client_name || 'N/A' }}</div>
                                </div>
                                <div class="info">
                                    <div>Warehouse</div>
                                    <div class="bold">{{ faktura.warehouse || 'N/A' }}</div>
                                </div>
                                <div class="info">
                                    <div>Amount</div>
                                    <div class="bold">{{ faktura.amount }}</div>
                                </div>
                                <div class="info">
                                    <div>Tax</div>
                                    <div class="bold">{{ faktura.tax }}</div>
                                </div>
                                <div class="info">
                                    <div>Total</div>
                                    <div class="bold">{{ calculateTotal(faktura) }}</div>
                                </div>
                                <div class="info">
                                    <div>Comment</div>
                                    <div class="bold">{{ faktura.comment || 'N/A' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <Pagination :pagination="incomingInvoice"/>
            </div>
        </div>
    </MainLayout>
</template>

<script>
import MainLayout from "@/Layouts/MainLayout.vue";
import Header from "@/Components/Header.vue";
import Pagination from "@/Components/Pagination.vue"
import axios from 'axios';
import {reactive} from "vue";
import OrderJobDetails from "@/Pages/Invoice/OrderJobDetails.vue";
import ViewLockDialog from "@/Components/ViewLockDialog.vue";
import RedirectTabs from "@/Components/RedirectTabs.vue";
import AddIncomingInvoiceDialog from "@/Components/AddIncomingInvoiceDialog.vue";
import EditIncomingInvoiceDialog from "@/Components/EditIncomingInvoiceDialog.vue";

export default {
    components: {Header, MainLayout,Pagination,OrderJobDetails, ViewLockDialog, RedirectTabs,AddIncomingInvoiceDialog, EditIncomingInvoiceDialog },
    props:{
        incomingInvoice: Object,
        costTypes: {
            type: Array,
            required: true
        },
        billTypes: {
            type: Array,
            required: true
        },
        clients: {
            type: Array,
            required: true
        }
    },
    data() {
        return {
            incomingInvoice: [],
            searchQuery: '',
            filterClient: 'All',
            filterWarehouse: '',
            filterCostType: '',
            filterBillType: '',
            sortOrder: 'desc',
            loading: false,
            error: null
        };
    },
    created() {
        this.fetchInvoices();
    },
    methods: {
        handleInvoiceAdded() {
            this.fetchInvoices(); // Refresh the list after adding
        },
        calculateTotal(faktura) {
            const amount = parseFloat(faktura.amount.replace(/,/g, ''));
            const tax = parseFloat(faktura.tax.replace(/,/g, ''));
            return (amount + tax).toFixed(2);
        },
        async fetchInvoices() {
            this.loading = true;
            try {
                const response = await axios.get('/incomingInvoice');
                this.incomingInvoice = response.data;
            } catch (error) {
                this.error = 'Error fetching invoices: ' + error.message;
                console.error('Error fetching invoices:', error);
            } finally {
                this.loading = false;
            }
        },
        toggleSortOrder() {
            this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
            this.applyFilter();
        },
        async applyFilter() {
            try {
                const response = await axios.get('/incomingInvoice', {
                    params: {
                        filterClient: this.filterClient,
                        filterWarehouse: this.filterWarehouse,
                        filterCostType: this.filterCostType,
                        filterBillType: this.filterBillType,
                        sortOrder: this.sortOrder
                    }
                });
                this.incomingInvoice = response.data;
            } catch (error) {
                console.error('Error applying filters:', error);
            }
        },
        async searchInvoices() {
            try {
                const response = await axios.get('/incomingInvoice', {
                    params: {
                        searchQuery: this.searchQuery
                    }
                });
                this.incomingInvoice = response.data;
            } catch (error) {
                console.error('Error searching invoices:', error);
            }
        },
        handleInvoiceUpdated(updatedInvoice) {
            this.fetchInvoices(); // Refresh the list after update
        }
    },
};
</script>
<style scoped lang="scss">
.info {
    flex: 0 1 200px;
    min-width: 0;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.jobInfo{
    align-items: center;
}
.locked{
    display: flex;
    justify-content: center;
}
.img{
    width: 70px;
    height: 70px;
}
select{
    width: 25vh;
    border-radius: 3px;
}
.orange{
    color: $orange;
}
.blue-text{
    color: $blue;
}
.bold{
    font-weight: bold;
}
.green-text{
    color: $green;
}
.blue{
    background-color: $blue;
}
.green{
    background-color: $green;
}
.green:hover{
    background-color: green;
}
.header{
    display: flex;
    align-items: center;
}
.bgJobs{
    background-color: $ultra-light-gray;
}
.dark-gray {
    background-color: $dark-gray;
    justify-content: left;
    align-items: center;
    min-height: 20vh;
    width: 100%;
}
.ultra-light-gray{
    background-color: $ultra-light-gray;
}
.sub-title{
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    color: $white;
}
.filter-container{
    justify-content: space-between;
}
.button-container{
    display: flex;
    justify-content: end;
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
.create-order{
    background-color: $green;
    color: white;
}
.job-details-container {
    max-height: 0;
    opacity: 0;
    overflow: hidden;
    transition: max-height 0.3s ease-in-out, opacity 0.3s ease-in-out;
    will-change: max-height, opacity;
}
.job-details-container.active {
    max-height: 400px;
    opacity: 1;
}
</style>


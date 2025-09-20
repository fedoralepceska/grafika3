<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="invoice2" subtitle="invoicedOrders" icon="invoice.png" link="allInvoices"/>
            <div class="dark-gray p-2 text-white">
                <RedirectTabs :route="$page.url" />
                <div class="form-container p-2 ">
                    <h2 class="sub-title">
                        {{ $t('listOfAllInvoices') }}
                    </h2>
                    <div class="filter-container flex gap-4 pb-10">
                        <div class="search flex gap-2">
                            <input v-model="searchQuery" placeholder="Enter Invoice Id" class="text-black search-input" @keyup.enter="searchInvoices" />
                            <button class="btn create-order1" @click="searchInvoices">Search</button>
                        </div>
                        <div class="flex gap-2 filters-group">
                        <div class="client">
                            <label class="pr-3">Filter Invoices</label>
                            <select v-model="filterClient" class="text-black filter-select" @change="applyFilter">
                                <option value="All" hidden>Clients</option>
                                <option value="All">All Clients</option>
                                <option v-for="client in uniqueClients" :key="client">{{ client.name }}</option>
                            </select>
                        </div>
                        <div class="date">
                            <select v-model="sortOrder" class="text-black filter-select" @change="applyFilter">
                                <option value="desc" hidden>Date</option>
                                <option value="desc">Newest to Oldest</option>
                                <option value="asc">Oldest to Newest</option>
                            </select>
                        </div>
                        </div>
                    </div>
                    <div v-if="loading" class="loading-container">
                        <div class="loading-spinner">
                            <i class="fa fa-spinner fa-spin"></i>
                            <span>Loading invoices...</span>
                        </div>
                    </div>
                    <div v-else-if="displayFakturas && displayFakturas.data && displayFakturas.data.length > 0">
                        <div :class="['border mb-2 invoice-row']" v-for="faktura in displayFakturas.data" :key="faktura.id">
                            <div class="text-black flex justify-between order-info" style="line-height: normal">
                                <div class="p-2 bold" style="font-size: 16px">{{faktura.id}}/{{ new Date(faktura.created_at).toLocaleDateString('en-US', { year: 'numeric' }) }}</div>
                                <div class="flex" style="font-size: 12px">
                                    <button class="flex items-center p-1" @click="viewInvoice(faktura.id)">
                                        <i class="fa fa-eye bg-gray-300 p-2 rounded" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="flex row-columns pl-2 pt-1" style="line-height: initial">
                                <div class="info col-order">
                                    <div>Invoice</div>
                                    <div class="bold">#{{faktura.id}}</div>
                                </div>
                                <div class="info min-w-80 no-wrap col-client">
                                    <div>Customer</div>
                                    <div class="bold ellipsis">{{faktura.invoices[0]?.client.name}}</div>
                                </div>
                                <div class="info col-date">
                                    <div>Comment</div>
                                    <div class="bold truncate">{{faktura.comment}}</div>
                                </div>
                                <div class="info col-user">
                                    <div>Created By</div>
                                    <div class="bold truncate">{{faktura.created_by?.name}}</div>
                                </div>
                                <div class="info col-status">
                                    <div>Invoice for Orders</div>
                                    <div class="bold truncate">{{ faktura.invoices.map(invoice => '#' + invoice.id).join(', ') }}</div>
                                </div>
                                <div class="info col-date">
                                    <div>Date Created</div>
                                    <div class="bold truncate">{{ new Date(faktura.created_at).toLocaleDateString('en-US', { year: 'numeric', month: '2-digit', day: '2-digit' }) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="no-invoices-message">
                        <p>No invoices found matching your criteria.</p>
                    </div>
                </div>
                <Pagination :pagination="displayFakturas"/>
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

export default {
    components: {Header, MainLayout,Pagination,OrderJobDetails, ViewLockDialog, RedirectTabs},
    props:{
        fakturas:Object,
    },
    data() {
        return {
            searchQuery: '',
            filterClient: 'All',
            sortOrder: 'desc',
            uniqueClients:[],
            localInvoices: [],
            localFakturas: null,
            loading: false,
        };
    },
    computed: {
        hasInvoices() {
            return this.localFakturas && this.localFakturas.data && this.localFakturas.data.length > 0;
        },
        displayFakturas() {
            // Use localFakturas if available, otherwise fall back to prop
            return this.localFakturas || this.fakturas;
        }
    },
    mounted() {
        this.fetchUniqueClients()
        // Initialize localFakturas with the prop data if it exists
        if (this.fakturas) {
            this.localFakturas = this.fakturas;
        }
    },
    methods: {
        async applyFilter() {
            try {
                this.loading = true;
                
                const response = await axios.get('/api/allInvoices/filtered', {
                    params: {
                        searchQuery: this.searchQuery,
                        sortOrder: this.sortOrder,
                        client: this.filterClient,
                    },
                });
                
                // Update the fakturas data directly without showing all invoices
                this.localFakturas = response.data;
                
                // Build URL with current filters for browser history
                let redirectUrl = '/allInvoices';
                const params = [];
                
                if (this.searchQuery) {
                    params.push(`searchQuery=${encodeURIComponent(this.searchQuery)}`);
                }
                if (this.sortOrder) {
                    params.push(`sortOrder=${this.sortOrder}`);
                }
                if (this.filterClient && this.filterClient !== 'All') {
                    params.push(`client=${encodeURIComponent(this.filterClient)}`);
                }
                
                if (params.length > 0) {
                    redirectUrl += '?' + params.join('&');
                }

                // Update URL without full page reload
                window.history.pushState({}, '', redirectUrl);
            } catch (error) {
                console.error('Error applying filters:', error);
            } finally {
                this.loading = false;
            }
        },
        async searchInvoices() {
            // Use the same applyFilter method for consistency
            await this.applyFilter();
        },
        async fetchUniqueClients() {
            try {
                const response = await axios.get('/unique-clients');
                this.uniqueClients = response.data;
            } catch (error) {
                console.error(error);
            }
        },
        viewInvoice(id) {
            this.$inertia.visit(`/invoice/${id}`);
        },
    },
};
</script>
<style scoped lang="scss">

.border:nth-child(odd) .order-info {
    background-color: white;
}

.border:nth-child(even) .order-info {
    background-color: rgba(255, 255, 255, 0.65);
}
 
.info {
    min-width: 0;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.filter-container{
    justify-content: space-between;
    flex-wrap: wrap;
}

.filter-container .search {
    flex: 1 1 320px;
}

.filter-container .filters-group {
    flex: 2 1 500px;
    flex-wrap: wrap;
}

.search-input{
    width: 50vh;
    max-width: 100%;
    border-radius: 3px;
}

.filter-select{
    width: 25vh;
    max-width: 100%;
}

select{
    width: 25vh;
    border-radius: 3px;
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

.dark-gray {
    background-color: $dark-gray;
    justify-content: left;
    border-radius: 8px;
    align-items: center;
    min-height: 20vh;
    min-width: 80vh;
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

.min-w-80 {
    min-width: 320px;
    flex-shrink: 0;
    display: block;
}

.row-columns {
    gap: 2rem;
    align-items: center;
    background-color: $background-color;
}

.col-status { margin-left: auto; }

.row-columns > .info div:nth-child(2) {
    white-space: nowrap;
}

.col-order { width: 90px; }
.col-client { width: 320px; }
.col-date { width: 170px; }
.col-user { width: 200px; }
.col-status { width: 180px; }

.truncate {
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.ellipsis {
    width: 100%;
    text-overflow: ellipsis;
    white-space: nowrap;
    overflow: hidden;
    display: inline-block;
    max-width: 100%;
}

.ellipsis-file {
    text-overflow: ellipsis;
    white-space: nowrap;
    overflow: hidden;
    display: inline-block;
    width: 150px;
}

.w-150 {
    width: 150px;
}

.loading-container {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 40px;
    color: white;
}

.loading-spinner {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    
    i {
        font-size: 24px;
        color: $blue;
    }
    
    span {
        font-size: 16px;
        color: white;
    }
}

.no-invoices-message {
    text-align: center;
    padding: 40px;
    color: white;
    
    p {
        font-size: 18px;
        margin: 0;
        opacity: 0.8;
    }
}

/* Improved invoice row styling */
.invoice-row {
    border: 3px solid rgba(255,255,255,0.25);
    border-radius: 8px;
    overflow: hidden;
    background: rgba(255,255,255,0.06);
    box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    transition: box-shadow 0.2s ease, transform 0.2s ease, border-color 0.2s ease;
}

.invoice-row:nth-child(odd) {
    background-color: rgba(255, 255, 255, 0.05);
}

.invoice-row:nth-child(even) {
    background-color: rgba(255, 255, 255, 0.09);
}

.invoice-row .order-info {
    border-bottom: 1px solid rgba(255, 255, 255, 0.12);
}

.invoice-row .row-columns {
    padding-bottom: 8px;
}

/* Status-based row accents for a more lively UI */
.row-completed {
    border-color: rgba(16, 185, 129, 0.35); /* green */
}

.row-progress {
    border-color: rgba(59, 130, 246, 0.35); /* blue */
}

.row-pending {
    border-color: rgba(234, 179, 8, 0.35); /* amber */
}

/* Status pill styling */
.status-pill {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 9999px;
    background-color: rgba(255,255,255,0.08);
    width: fit-content;
}

@media (max-width: 1024px) {
    .filter-container { gap: 12px; }
    .search-input { width: 100%; }
    .filter-select { width: 100%; }
    .filters-group { flex: 1 1 100%; }
    .row-columns { gap: 1rem; }
    .col-client { width: 220px; }
    .col-user { width: 150px; }
    .col-status { width: 140px; }
}

@media (max-width: 768px) {
    .row-columns { gap: 0.75rem; }
    .col-client { width: 180px; }
    .col-user { width: 120px; }
    .col-status { width: 120px; }
}
</style>


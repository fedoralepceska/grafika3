<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="invoice2" subtitle="UninvoicedOrders" icon="invoice.png" link="notInvoiced"/>
            <div class="dark-gray p-2 text-white">
                <RedirectTabs :route="$page.url" />
                <div class="form-container p-2 ">
                    <h2 class="sub-title">
                        {{ $t('listOfNotInvoiced') }}
                    </h2>
                    <div class="filter-container flex gap-4 pb-10">
                        <div class="search flex gap-2">
                            <input v-model="searchQuery" placeholder="Enter order number or order name" class="text-black search-input" @keyup.enter="searchInvoices" />
                            <button class="btn create-order1" @click="searchInvoices">Search</button>
                        </div>
                        <div class="flex gap-2 filters-group">
                        <div class="status">
                            
                            <ClientSelectDropdown 
                                v-model="filterClient"
                                :clients="uniqueClients"
                                @change="applyFilter"
                            />
                        </div>
                        <div class="date">
                            <select v-model="sortOrder" class="text-black filter-select" @change="applyFilter">
                                <option value="desc" hidden>Date</option>
                                <option value="desc">Newest to Oldest</option>
                                <option value="asc">Oldest to Newest</option>
                            </select>
                        </div>
                        </div>
                        <div class="button flex gap-3">
                            <button @click="clearAllSelections" v-if="hasSelectedInvoices || filterClient !== 'All'" class="btn create-order1" >
                                Clear Selection <i class="fa-solid fa-times"></i>
                            </button>
                            <button @click="generateInvoices" class="btn create-order" >
                                Generate Invoice <i class="fa-solid fa-file-invoice-dollar"></i>
                            </button>
                        </div>
                    </div>

                    <div v-if="loading" class="loading-container">
                        <div class="loading-spinner">
                            <i class="fa fa-spinner fa-spin"></i>
                            <span>Loading orders...</span>
                        </div>
                    </div>
                    <div v-else-if="filteredInvoices && filteredInvoices.length > 0">
                        <div :class="['border mb-2 invoice-row', getStatusRowClass(invoice.status)]" v-for="invoice in filteredInvoices" :key="invoice.id">
                            <div class="text-black flex justify-between order-info" style="line-height: normal">
                                <div class="p-2 bold" style="font-size: 16px">{{invoice.invoice_title}}</div>
                                <div class="flex" style="font-size: 12px">
                                    <button class="flex items-center p-1" @click="viewInvoice(invoice.id)">
                                        <i class="fa fa-eye bg-gray-300 p-2 rounded" aria-hidden="true"></i>
                                    </button>
                                    <div class="flex items-center p-1">
                                        <input type="checkbox" :id="`invoice-${invoice.id}`" :checked="selectedInvoices[invoice.id]" @change="toggleInvoiceSelection(invoice, $event)" class="bg-gray-200 p-2 rounded px-3 py-3 border-gray-500">
                                    </div>
                                </div>
                            </div>
                            <div class="flex row-columns pl-2 pt-1" style="line-height: initial">
                                <div class="info col-order">
                                    <div>Order</div>
                                    <div class="bold">#{{invoice.id}}</div>
                                </div>
                                <div class="info min-w-80 no-wrap col-client">
                                    <div>Customer</div>
                                    <div class="bold ellipsis">{{ invoice.client.name }}</div>
                                </div>
                                <div class="info col-date">
                                    <div>End Date</div>
                                    <div class="bold truncate">{{ formatDate(invoice.end_date) }}</div>
                                </div>
                                <div class="info col-user">
                                    <div>Created By</div>
                                    <div class="bold truncate">{{ invoice.user.name }}</div>
                                </div>
                                <div v-if="invoice.LockedNote" class="info locked">
                                    <ViewLockDialog :invoice="invoice"/>
                                </div>
                                <div class="info col-status">
                                    <div>Status</div>
                                    <div :class="[getStatusColorClass(invoice.status), 'bold', 'truncate', 'status-pill']">{{invoice.status}}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <Pagination :pagination="{ data: [], links: invoices?.links || [] }" @pagination-change-page="goToPage"/>
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
import ClientSelectDropdown from "@/Components/ClientSelectDropdown.vue";
import {useToast} from "vue-toastification";

export default {
    components: {Header, MainLayout,Pagination,OrderJobDetails, ViewLockDialog, RedirectTabs, ClientSelectDropdown },
    props:{
        invoices:Object,
    },
    data() {
        return {
            searchQuery: '',
            filterClient: 'All',
            sortOrder: 'desc',
            localInvoices: [],
            uniqueClients:[],
            filteredInvoices: [],
            selectedInvoices:{},
            loading: false,
            perPage: 10,
        };
    },
    mounted() {
        this.localInvoices = this.invoices.data.slice();
        this.fetchUniqueClients()
        this.filteredInvoices = this.invoices.data; // Backend now handles the faktura_id filtering
        // Force initial load with testing per-page value
        this.applyFilter(1)
    },
    computed:{
        hasSelectedInvoices() {
            return Object.values(this.selectedInvoices).some(value => value);
        },
        
    },
    methods: {
        formatDate(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            if (isNaN(d.getTime())) return dateStr;
            const dd = String(d.getDate()).padStart(2, '0');
            const mm = String(d.getMonth() + 1).padStart(2, '0');
            const yyyy = d.getFullYear();
            return `${dd}/${mm}/${yyyy}`;
        },
        getStatusRowClass(status) {
            if (status === 'Completed') return 'row-completed';
            if (status === 'In progress' || status === 'In Progress') return 'row-progress';
            if (status === 'Not started yet') return 'row-pending';
            return '';
        },
        toggleInvoiceSelection(invoice, event) {
            const toast = useToast();
            const isCurrentlySelected = this.selectedInvoices[invoice.id];

            // Check if invoice.client exists
            if (!invoice.client) {
                toast.error('This invoice does not have an associated client.');
                event.target.checked = false; // Revert checkbox state
                return;
            }

            // If unselecting an invoice, remove it and check if we should clear filter
            if (isCurrentlySelected) {
                this.selectedInvoices[invoice.id] = false;
                
                // If no invoices are selected anymore, clear the filter
                const remainingSelected = Object.keys(this.selectedInvoices).filter(id => this.selectedInvoices[id]);
                if (remainingSelected.length === 0 && this.filterClient !== 'All') {
                    this.clearAllSelections();
                }
                return;
            }

            // Get IDs of currently selected invoices
            const selectedInvoiceIds = Object.keys(this.selectedInvoices).filter(id => this.selectedInvoices[id]);

            // If this is the first selection OR same client, auto-filter and select
            if (selectedInvoiceIds.length === 0 || 
                (selectedInvoiceIds.length > 0 && invoice.client && selectedInvoiceIds.some(id => {
                    const existingInvoice = this.filteredInvoices.find(inv => inv.id == id);
                    return existingInvoice && existingInvoice.client && existingInvoice.client.name === invoice.client.name;
                }))) {
                
                // Auto-filter to same client if this is the first selection
                if (selectedInvoiceIds.length === 0) {
                    this.autoFilterByClient(invoice.client.name);
                }
                
                // Select the invoice
                this.selectedInvoices[invoice.id] = true;
                return;
            }

            // Different client - show error
            toast.error('You can only select invoices from the same client.');
            event.target.checked = false; // Revert checkbox state
        },

        getStatusColorClass(status) {
            if (status === "Not started yet") {
                return "orange";
            } else if (status === "In progress") {
                return "blue-text";
            } else if (status === "Completed") {
                return "green-text";
            }
        },
        async applyFilter(page = 1) {
            try {
                this.loading = true;
                
                const response = await axios.get('/api/notInvoiced/filtered', {
                    params: {
                        searchQuery: this.searchQuery,
                        sortOrder: this.sortOrder,
                        client: this.filterClient,
                        page: page,
                    },
                });
                
                // Update the filtered invoices directly without showing all orders
                this.filteredInvoices = response.data.data || response.data;
                // Keep pagination links from backend if sent
                if (response.data && response.data.links) {
                    this.invoices.links = response.data.links;
                }
                
                // Build URL with current filters for browser history
                let redirectUrl = '/notInvoiced';
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
                if (page) {
                    params.push(`page=${page}`);
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
            await this.applyFilter(1);
        },
        goToPage(page){
            this.applyFilter(page);
        },
        async fetchUniqueClients() {
            try {
                const response = await axios.get('/unique-clients');
                this.uniqueClients = response.data;
            } catch (error) {
                console.error(error);
            }
        },
        
        async autoFilterByClient(clientName) {
            // Set the client filter and apply it
            this.filterClient = clientName;
            await this.applyFilter(1);
        },
        viewInvoice(id) {
            this.$inertia.visit(`/orders/${id}`);
        },
        clearAllSelections() {
            this.selectedInvoices = {};
            this.filterClient = 'All';
            this.applyFilter(1);
        },
        
        async generateInvoices() {
            const toast = useToast();
            const selectedIds = Object.entries(this.selectedInvoices)
                .filter(([, isSelected]) => isSelected)
                .map(([id]) => id);

            if (selectedIds.length === 0) {
                toast.error('Please select at least one invoice to generate.');
                return;
            }

            // Redirect to invoice generation page with selected orders
            const queryParams = selectedIds.map(id => `orders[]=${id}`).join('&');
            this.$inertia.visit(`/invoiceGeneration?${queryParams}`);
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
    flex: 2 1 600px;
    flex-wrap: wrap;
}

.search-input{
    width: 50vh;
    max-width: 100%;
    border-radius: 3px;
}

.filter-select{
    width: 30vh;
    min-width: 200px;
    max-width: 100%;
}

.locked{
    display: flex;
    justify-content: center;
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

.dark-gray {
    background-color: $dark-gray;
    justify-content: left;
    border-radius: 8px;
    align-items: center;
    min-height: 20vh;
    min-width: 80vh;
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
.col-client { width: 450px; }
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
    .col-client { width: 300px; }
    .col-user { width: 150px; }
    .col-status { width: 140px; }
}

@media (max-width: 768px) {
    .row-columns { gap: 0.75rem; }
    .col-client { width: 250px; }
    .col-user { width: 120px; }
    .col-status { width: 120px; }
}
</style>


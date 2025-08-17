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
                    <div class="filter-container flex pb-10">
                        <div class="search flex gap-2">
                            <input v-model="searchQuery" placeholder="Enter order number or order name" class="text-black" style="width: 50vh; border-radius: 3px" @keyup.enter="searchInvoices" />
                            <button class="btn create-order1" @click="searchInvoices">Search</button>
                        </div>
                        <div class="flex gap-3">
                        <div class="client">
                            <label class="pr-3">Filter orders</label>
                            <select v-model="filterClient" class="text-black">
                                <option value="All" hidden>Clients</option>
                                <option value="All">All Clients</option>
                                <option v-for="client in uniqueClients" :key="client">{{ client.name }}</option>
                            </select>
                        </div>
                        <div class="date">
                            <select v-model="sortOrder" class="text-black">
                                <option value="desc" hidden>Date</option>
                                <option value="desc">Newest to Oldest</option>
                                <option value="asc">Oldest to Newest</option>
                            </select>
                        </div>
                            <button @click="applyFilter" class="btn create-order1">Filter</button>
                        </div>
                        <div class="button flex gap-4">
                            <button @click="generateInvoices" class="btn create-order" >
                                Generate Invoice <i class="fa-solid fa-file-invoice-dollar"></i>
                            </button>
                        </div>
                    </div>

                    <div v-if="filteredInvoices">
                        <div class="border mb-1" v-for="invoice in filteredInvoices" :key="invoice.id">
                            <div class="bg-white text-black flex justify-between">

                                <div class="p-2 bold">{{invoice.invoice_title}}</div>
                                <div class="flex">
                                    <button class="flex items-center p-1" @click="viewJobs(invoice.id)">
                                        <i v-if="iconStates[invoice.id]" class="fa-solid fa-angles-up bg-gray-300 p-2 rounded" aria-hidden="true"></i>
                                        <i v-else class="fa-solid fa-angles-down bg-gray-300 p-2 rounded" aria-hidden="true"></i>
                                    </button>
                                    <button class="flex items-center p-1" @click="viewInvoice(invoice.id)">
                                        <i class="fa fa-eye bg-gray-300 p-2 rounded" aria-hidden="true"></i>
                                    </button>
                                    <div class="flex items-center p-1">
                                        <input type="checkbox" :id="`invoice-${invoice.id}`" :checked="selectedInvoices[invoice.id]" @change="toggleInvoiceSelection(invoice, $event)" class="bg-gray-200 p-2 rounded px-3 py-3 border-gray-500">
                                    </div>
                                </div>
                            </div>
                            <div class="flex gap-40 p-2">
                                <div class="info">
                                    <div>Order</div>
                                    <div class="bold">#{{invoice.id}}</div>
                                </div>
                                <div class="info">
                                    <div>Customer</div>
                                    <div class="bold">{{ invoice.client.name }}</div>
                                </div>
                                <div class="info">
                                    <div>End Date</div>
                                    <div  class="bold">{{invoice.end_date}}</div>
                                </div>
                                <div class="info">
                                    <div>Created By</div>
                                    <div  class="bold">{{ invoice.user.name }}</div>
                                </div>
                                <div class="info">
                                    <div>Status</div>
                                    <div :class="getStatusColorClass(invoice.status)" class="bold" >{{invoice.status}}</div>
                                </div>
                                <div v-if="invoice.LockedNote" class="info locked">
                                    <ViewLockDialog :invoice="invoice"/>
                                </div>
                            </div>
                            <div v-if="currentInvoiceId" class="job-details-container" :class="{ active: currentInvoiceId === invoice.id }">
                                <div v-if="currentInvoiceId===invoice.id" class="bgJobs text-white p-2 bold">
                                    Jobs for Order #{{invoice.id}} {{invoice.invoice_title}}
                                </div>
                                <div v-if="currentInvoiceId===invoice.id" class="jobInfo border-b" v-for="(job,index) in invoice.jobs">
                                    <div class=" jobInfo flex justify-between gap-3">
                                        <div class="text-white bold p-1">
                                            #{{index+1}} {{job.file}}
                                        </div>
                                        <div class="p-1 img">
                                            <img :src="getImageUrl(job.id)" alt="Job Image" class="jobImg thumbnail"/>
                                        </div>
                                        <div class="p-1">{{$t('Height')}}: <span class="bold">{{job.height.toFixed(2)}}</span> </div>
                                        <div class="p-1">{{$t('Width')}}: <span class="bold">{{job.width.toFixed(2)}}</span> </div>
                                        <div class="p-1">{{$t('Quantity')}}: <span class="bold">{{job.quantity}}</span> </div>
                                        <div class="p-1">{{$t('Copies')}}: <span class="bold">{{job.copies}}</span> </div>
                                    </div>
                                    <div class="ultra-light-gray pt-4">
                                        <OrderJobDetails :job="job" :invoice-id="invoice.id"/>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <Pagination :pagination="invoices"/>
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
import {useToast} from "vue-toastification";

export default {
    components: {Header, MainLayout,Pagination,OrderJobDetails, ViewLockDialog, RedirectTabs },
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
            currentInvoiceId: null,
            selectedInvoices:{},
            iconStates : reactive({}),
        };
    },
    mounted() {
        this.localInvoices = this.invoices.data.slice();
        this.fetchUniqueClients()
        this.invoices.data.forEach(invoice => {
            this.iconStates[invoice.id] = false;
        });
        this.filteredInvoices = this.invoices.data.filter(invoice => !invoice.faktura_id);
    },
    computed:{
        hasSelectedInvoices() {
            return Object.values(this.selectedInvoices).some(value => value);
        },
    },
    methods: {
        toggleInvoiceSelection(invoice, event) {
            const toast = useToast(); // Initialize toast here to use directly

            // Check if invoice.client exists
            if (!invoice.client) {
                toast.error('This invoice does not have an associated client.');
                event.target.checked = false; // Revert checkbox state
                return;
            }

            // Get IDs of currently selected invoices
            const selectedInvoiceIds = Object.keys(this.selectedInvoices).filter(id => this.selectedInvoices[id]);

            if (selectedInvoiceIds.length > 0) {
                // Get the client ID of the first selected invoice
                const firstSelectedInvoice = this.invoices.data.find(inv => inv.id == selectedInvoiceIds[0]);

                // Check if first selected invoice has a client
                if (!firstSelectedInvoice || !firstSelectedInvoice.client) {
                    toast.error('Selected invoice(s) do not have associated clients.');
                    event.target.checked = false; // Revert checkbox state
                    return;
                }

                const firstSelectedClientId = firstSelectedInvoice.client.name;

                // Check if the new invoice's client ID matches the first selected invoice's client ID
                if (invoice.client.name !== firstSelectedClientId) {
                    toast.error('You can only select invoices from the same client.');
                    event.target.checked = false; // Revert checkbox state
                    return;
                }
            }

            // Toggle the selected status of the invoice if validation passes
            this.selectedInvoices[invoice.id] = !this.selectedInvoices[invoice.id];
        },

        getImageUrl(id) {
            const currentInvoice = this.invoices.data.find(invoice => invoice.id === this.currentInvoiceId);
            if (currentInvoice && currentInvoice.jobs) {
                const job = currentInvoice.jobs.find(j => j.id === id);
                return `/storage/uploads/${job.file}`;
            }
        },
        viewJobs(invoiceId) {
            if (this.currentInvoiceId && this.currentInvoiceId !== invoiceId) {
                this.iconStates[this.currentInvoiceId] = false;
                this.currentInvoiceId = null;
            }

            // Toggle the icon state for the clicked invoice
            this.currentInvoiceId = this.currentInvoiceId === invoiceId ? null : invoiceId;
            this.iconStates[invoiceId] = !this.iconStates[invoiceId];
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
        async applyFilter() {
            try {
                const response = await axios.get('/orders', {
                    params: {
                        searchQuery: encodeURIComponent(this.searchQuery),
                        status: this.filterStatus,
                        sortOrder: this.sortOrder,
                        client: this.filterClient,
                    },
                });
                this.localInvoices = response.data;
                let redirectUrl = '/notInvoiced';
                if (this.searchQuery) {
                    redirectUrl += `?searchQuery=${encodeURIComponent(this.searchQuery)}`;
                }
                if (this.filterStatus) {
                    redirectUrl += `${this.searchQuery ? '&' : '?'}status=${this.filterStatus}`;
                }
                if (this.sortOrder) {
                    redirectUrl += `${this.searchQuery || this.filterStatus ? '&' : '?'}sortOrder=${this.sortOrder}`;
                }
                if (this.filterClient) {
                    redirectUrl += `${this.searchQuery || this.filterStatus || this.sortOrder ? '&' : '?'}client=${this.filterClient}`;
                }

                this.$inertia.visit(redirectUrl);
            } catch (error) {
                console.error(error);
            }
        },
        async searchInvoices() {
            try {
                const response = await axios.get(`?searchQuery=${encodeURIComponent(this.searchQuery)}`);
                this.localInvoices = response.data;
                this.$inertia.visit(`/notInvoiced?searchQuery=${this.searchQuery}`);
            } catch (error) {
                console.error(error);
            }
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
            this.$inertia.visit(`/orders/${id}`);
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

            try {
                const response = await axios.post('/outgoing/invoice', { invoiceIds: selectedIds }, {
                    responseType: 'blob',
                });

                const blob = new Blob([response.data], { type: 'application/pdf' });
                const url = window.URL.createObjectURL(blob);
                window.open(url, '_blank');
                this.searchInvoices();
            } catch (error) {
                console.error('Error generating invoices:', error);
                toast.error('An error occurred while generating the invoices. Please try again.');
            }
        },
    },
};
</script>
<style scoped lang="scss">
.info {
    flex: 1;
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
    color: #1ba5e4;
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


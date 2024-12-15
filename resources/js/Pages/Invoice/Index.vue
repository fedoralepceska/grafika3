<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="invoice" subtitle="ViewAllInvoices" icon="List.png" link="orders"/>
            <div class="dark-gray p-2 text-white">
                <div class="form-container p-2 ">
                    <h2 class="sub-title">
                        {{ $t('listOfAllOrders') }}
                    </h2>
                    <div class="filter-container flex gap-4 pb-10">
                        <div class="search flex gap-2">
                            <input v-model="searchQuery" placeholder="Enter order number or order name" class="text-black" style="width: 50vh; border-radius: 3px" @keyup.enter="searchInvoices" />
                            <button class="btn create-order1" @click="searchInvoices">Search</button>
                        </div>
                        <div class="flex gap-2">
                        <div class="status">
                            <label class="pr-3">Filter orders</label>
                            <select v-model="filterStatus" class="text-black" >
                                <option value="All" hidden>Status</option>
                                <option value="All">All</option>
                                <option value="Not started yet">Not started yet</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Completed">Completed</option>
                            </select>
                        </div>
                        <div class="client">
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
                        <div class="button flex gap-3">
                            <button @click="navigateToCreateOrder" class="btn create-order">
                                Create Order <i class="fa fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div v-if="invoices.data">
                        <div class="border mb-2" v-for="invoice in invoices.data" :key="invoice.id">
                            <div class="bg-white text-black flex justify-between" style="line-height: normal">
                                <div class="p-2 bold" style="font-size: 16px">{{invoice.invoice_title}}</div>
                                <div class="flex" style="font-size: 12px">
                                    <button class="flex items-center p-1" @click="viewJobs(invoice.id)">
                                        <i v-if="iconStates[invoice.id]" class="fa-solid fa-angles-up bg-gray-300 p-2 rounded" aria-hidden="true"></i>
                                        <i v-else class="fa-solid fa-angles-down bg-gray-300 p-2 rounded" aria-hidden="true"></i>
                                    </button>
                                    <button class="flex items-center p-1" @click="viewInvoice(invoice.id)">
                                    <i class="fa fa-eye bg-gray-300 p-2 rounded" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="flex gap-40 pl-2 pt-1" style="line-height: initial">
                                <div class="info">
                                    <div>Order</div>
                                    <div class="bold">#{{invoice.id}}</div>
                                </div>
                                <div class="info min-w-80 no-wrap">
                                    <div>Customer</div>
                                    <div class="bold ellipsis">{{ invoice.client.name }}</div>
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
                                    <div v-if="currentInvoiceId===invoice.id" class="bgJobs text-white p-2 bold" style="line-height: normal;">
                                        Jobs for Order #{{invoice.id}} {{invoice.invoice_title}}
                                    </div>
                                    <div v-if="currentInvoiceId===invoice.id" class="jobInfo border-b" v-for="(job,index) in invoice.jobs">
                                            <div class=" jobInfo flex justify-between gap-1">
                                            <div class="text-white bold p-1 ellipsis-file">
                                                #{{index+1}} {{job.file}}
                                            </div>
                                            <div class="p-1 img">
                                                <img :src="getImageUrl(job.id)" alt="Job Image" class="jobImg thumbnail"/>
                                            </div>
                                            <div class="p-1 w-150">{{$t('Height')}}: <span class="bold">{{job.height.toFixed(2)}}</span> </div>
                                            <div class="p-1 w-150">{{$t('Width')}}: <span class="bold">{{job.width.toFixed(2)}}</span> </div>
                                            <div class="p-1 w-150">{{$t('Quantity')}}: <span class="bold">{{job.quantity}}</span> </div>
                                            <div class="p-1 w-150">{{$t('Copies')}}: <span class="bold">{{job.copies}}</span> </div>
                                            </div>
                                        <div class="ultra-light-gray pt-4">
                                        <OrderJobDetails :job="job"/>
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

export default {
    components: {Header, MainLayout,Pagination,OrderJobDetails, ViewLockDialog },
    props:{
      invoices:Object,
    },
    data() {
        return {
            searchQuery: '',
            filterStatus: 'All',
            filterClient: 'All',
            sortOrder: 'desc',
            localInvoices: [],
            uniqueClients:[],
            currentInvoiceId: null,
            iconStates : reactive({}),
        };
    },
    mounted() {
        this.localInvoices = this.invoices.data.slice();
        this.fetchUniqueClients()
        this.invoices.data.forEach(invoice => {
            this.iconStates[invoice.id] = false;
        });
    },
    methods: {
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
                let redirectUrl = '/orders';
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
                this.$inertia.visit(`/orders?searchQuery=${this.searchQuery}`);
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
        navigateToCreateOrder(){
            this.$inertia.visit(`/orders/create`);
        },
    },
};
</script>
<style scoped lang="scss">
.info {

    min-width: 0;
    display: flex;
    flex-direction: column;
    justify-content: center;

}
.filter-container{
    justify-content: space-between;
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
.job-details-container {
    max-height: 0;
    opacity: 0;
    overflow: hidden;
    transition: max-height 0.3s ease-in-out, opacity 0.3s ease-in-out;
    will-change: max-height, opacity;
}

.job-details-container.active {
    max-height: 550px;
    opacity: 1;
}
.min-w-80 {
    min-width: 320px;
    flex-shrink: 0;
    display: block;
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
</style>


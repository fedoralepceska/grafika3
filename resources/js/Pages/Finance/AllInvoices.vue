<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="invoice2" subtitle="invoicedOrders" icon="invoice.png" link="allInvoices"/>
            <div class="dark-gray p-2 text-white">
                <div class="form-container p-2 ">
                    <h2 class="sub-title">
                        {{ $t('listOfAllInvoices') }}
                    </h2>
                    <div class="filter-container flex gap-4 pb-10">
                        <div class="search flex gap-2">
                            <input v-model="searchQuery" placeholder="Enter Invoice Id" class="text-black" style="width: 50vh; border-radius: 3px" @keyup.enter="searchInvoices" />
                            <button class="btn create-order1" @click="searchInvoices">Search</button>
                        </div>
                        <div class="flex gap-3">
                        <div class="client">
                            <label class="pr-3">Filter Invoices</label>
                            <select v-model="filterClient" class="text-black">
                                <option value="All" hidden>Clients</option>
                                <option value="All">All Clients</option>
                                <option v-for="client in uniqueClients" :key="client">{{ client }}</option>
                            </select>
                        </div>
                        <div class="date">
                            <select v-model="sortOrder" class="text-black">
                                <option value="desc" hidden>Date</option>
                                <option value="desc">Newest to Oldest</option>
                                <option value="asc">Oldest to Newest</option>
                            </select>
                        </div>
                        <div class="button flex gap-3">
                            <button @click="applyFilter" class="btn create-order1">Filter</button>
                        </div>
                        </div>
                    </div>
                    <div v-if="fakturas.data">
                        <div class="border mb-1" v-for="faktura in fakturas.data" :key="faktura.id">
                            <div class="bg-white text-black flex justify-between">
                                <div class="p-2 bold">{{faktura.id}}</div>
                                <div class="flex">
                                    <button class="flex items-center p-1" @click="viewInvoice(faktura.id)">
                                        <i class="fa fa-eye bg-gray-300 p-2 rounded" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="flex gap-40 p-2">
                                <div class="info">
                                    <div>Invoice</div>
                                    <div class="bold">#{{faktura.id}}</div>
                                </div>
                                <div class="info">
                                    <div>Customer</div>
                                    <div class="bold">{{faktura.invoices[0].client.name}}</div>
                                </div>
                                <div class="info">
                                    <div>Comment</div>
                                    <div  class="bold">{{faktura.comment}}</div>
                                </div>
                                <div class="info">
                                    <div>Created By</div>
                                    <div  class="bold">{{}}</div>
                                </div>
                                <div class="info">
                                    <div>Invoice for Orders</div>
                                    <div  class="bold">{{ faktura.invoices.map(invoice => '#' + invoice.id).join(', ') }}</div>
                                </div>
                                <div class="info">
                                    <div>Date Created</div>
                                   <div>{{ new Date(faktura.created_at).toLocaleDateString('en-US', { year: 'numeric', month: '2-digit', day: '2-digit' }) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <Pagination :pagination="fakturas"/>
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
        fakturas:Object,
    },
    data() {
        return {
            searchQuery: '',
            filterClient: 'All',
            sortOrder: 'desc',
            uniqueClients:[],
            localInvoices: [],
        };
    },
    mounted() {
        this.fetchUniqueClients()
    },
    methods: {
        async applyFilter() {
            try {
                const response = await axios.get('/allInvoices', {
                    params: {
                        searchQuery: encodeURIComponent(this.searchQuery),
                        sortOrder: this.sortOrder,
                        client: this.filterClient,
                    },
                });
                this.localInvoices = response.data;
                let redirectUrl = '/allInvoices';
                if (this.searchQuery) {
                    redirectUrl += `?searchQuery=${encodeURIComponent(this.searchQuery)}`;
                }
                if (this.sortOrder) {
                    redirectUrl += `${this.searchQuery  ? '&' : '?'}sortOrder=${this.sortOrder}`;
                }
                if (this.filterClient) {
                    redirectUrl += `${this.searchQuery || this.sortOrder ? '&' : '?'}client=${this.filterClient}`;
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
                this.$inertia.visit(`/allInvoices?searchQuery=${this.searchQuery}`);
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
            this.$inertia.visit(`/invoice/${id}`);
        },
    },
};
</script>
<style scoped lang="scss">
.filter-container{
    justify-content: space-between;
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
    max-height: 400px;
    opacity: 1;
}
</style>


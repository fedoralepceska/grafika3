<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="invoice2" subtitle="incomingInvoice" icon="invoice.png" link="incomingInvoice"/>
            <div class="dark-gray p-2 text-white">
                <RedirectTabs :route="$page.url" />
                <div class="form-container p-2 ">
                    <h2 class="sub-title">
                        {{ $t('listOfIncomingInvoices') }}
                    </h2>
                    <div class="filter-container flex pb-10">
                        <div class="search flex gap-2">
                            <input v-model="searchQuery" placeholder="Enter invoice number" class="text-black" style="width: 50vh; border-radius: 3px" @keyup.enter="searchInvoices" />
                            <button class="btn create-order1" @click="searchInvoices">Search</button>
                        </div>
                        <div class="flex gap-3">
                            <div class="client">
                                <label class="pr-3">Filter Incoming Invoices</label>
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
                            <AddIncomingInvoiceDialog :incomingInvoice="incomingInvoice" />
                        </div>
                    </div>

                    <div v-if="incomingInvoice.data">
                        <div class="border mb-1" v-for="faktura in incomingInvoice.data" :key="faktura.id">
                            <div class="bg-white text-black flex justify-between">
                                <div class="p-2 bold">{{faktura.id}}/{{ new Date(faktura.created_at).toLocaleDateString('en-US', { year: 'numeric' }) }}</div>
                            </div>
                            <div class="flex gap-40 p-2">
                                <div class="info">
                                    <div>Invoice</div>
                                    <div class="bold">#{{faktura.id}}</div>
                                </div>
                                <div class="info">
                                    <div>Customer</div>
                                    <div class="bold"></div>
                                </div>
                                <div class="info">
                                    <div>Warehouse</div>
                                    <div  class="bold">{{faktura.warehouse}}</div>
                                </div>
                                <div class="info">
                                    <div>Amount</div>
                                    <div  class="bold">{{faktura.amount}}</div>
                                </div>
                                <div class="info">
                                    <div>Comment</div>
                                    <div  class="bold">{{faktura.comment}}</div>
                                </div>
                                <div class="info">
                                    <div>Date Created</div>
                                    <div>{{ new Date(faktura.created_at).toLocaleDateString('en-US', { year: 'numeric', month: '2-digit', day: '2-digit' }) }}</div>
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

export default {
    components: {Header, MainLayout,Pagination,OrderJobDetails, ViewLockDialog, RedirectTabs,AddIncomingInvoiceDialog },
    props:{
        incomingInvoice:Object,
    },
    data() {
        return {
            searchQuery: '',
            filterClient: 'All',
            sortOrder: 'desc',
            localInvoices: [],
            filteredInvoices: [],
            uniqueClients:[],
            currentInvoiceId: null,
        };
    },
    mounted() {
        this.fetchUniqueClients()
    },
    methods: {
        async applyFilter() {

        },
        async searchInvoices() {

        },
        async fetchUniqueClients() {
            try {
                const response = await axios.get('/unique-clients');
                this.uniqueClients = response.data;
            } catch (error) {
                console.error(error);
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


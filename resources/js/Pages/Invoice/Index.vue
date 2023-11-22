<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="invoice" subtitle="ViewAllInvoices" icon="List.png"/>
            <div class="dark-gray p-2 text-white">
                <div class="form-container p-2 ">
                    <h2 class="sub-title">
                        {{ $t('listOfAllOrders') }}
                    </h2>
                    <div class="filter-container flex gap-4 pb-10">
                        <div class="search">
                            <label class="pr-3">Search orders </label>
                            <input v-model="searchQuery" @input="applyFilter" placeholder="Enter order number or order name" class="text-black" style="width: 50vh; border-radius: 4px" />
                        </div>
                        <div class="status">
                            <label class="pr-3">Filter orders</label>
                            <select v-model="filterStatus" @change="applyFilter" class="text-black" >
                                <option value="All" hidden>Status</option>
                                <option value="All">All</option>
                                <option value="Not started yet">Not started yet</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Completed">Completed</option>
                            </select>
                        </div>
                        <div class="client">
                            <select v-model="filterClient" @change="applyFilter" class="text-black">
                                <option value="All" hidden>Clients</option>
                                <option value="All">All Clients</option>
                                <option v-for="client in uniqueClients" :key="client">{{ client }}</option>
                            </select>
                        </div>
                        <div class="date">
                            <select v-model="sortOrder" @change="applyFilter" class="text-black">
                                <option value="desc" hidden>Date</option>
                                <option value="desc">Newest to Oldest</option>
                                <option value="asc">Oldest to Newest</option>
                            </select>
                        </div>
                        <div class="button">
                            <button @click="navigateToCreateOrder" class="btn create-order">
                                Create Order <i class="fa fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="border mb-1" v-for="invoice in filteredInvoices" :key="invoice.id">
                        <div class="bg-white text-black flex justify-between">
                            <div class="p-2 bold">{{invoice.invoice_title}}</div>
                            <button class="flex items-center p-1" @click="viewInvoice(invoice.id)">
                            <i class="fa fa-eye bg-gray-300 p-2 rounded" aria-hidden="true"></i>
                            </button>
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
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script>
import axios from "axios";
import MainLayout from "@/Layouts/MainLayout.vue";
import Header from "@/Components/Header.vue";

export default {
    components: {Header, MainLayout },
    props: {
        invoices: Array,
    },
    data(){
        return{
            filterStatus:"All",
            filterClient:"All",
            searchQuery: "",
            ortBy: "created_at",
            sortOrder: "desc",
        }
    },
    computed: {
        filteredInvoices() {
            const sortedInvoices = this.invoices.slice().sort((a, b) => {
                const order = this.sortOrder === "asc" ? 1 : -1;
                return order * (new Date(a.created_at) - new Date(b.created_at));
            });

            return sortedInvoices.filter(invoice => {
                const statusCondition = this.filterStatus === "All" || invoice.status === this.filterStatus;
                const clientCondition = this.filterClient === "All" || invoice.client.name === this.filterClient;
                const searchCondition = !this.searchQuery || invoice.id.toString().includes(this.searchQuery) || invoice.invoice_title.toString().includes(this.searchQuery) ;
                return statusCondition && clientCondition && searchCondition;
            });
        },
        uniqueClients() {
            return Array.from(new Set(this.invoices.map(invoice => invoice.client.name)));
        },
    },
    methods: {
        getStatusColorClass(status) {
            if (status === "Not started yet") {
                return "orange";
            } else if (status === "In Progress") {
                return "blue-text";
            } else if (status === "Completed") {
                return "green-text";
            }
        },
        applyFilter() {
            // Optionally, you can fetch filtered data from the server here
        },
        viewInvoice(id) {
            this.$inertia.visit(`/invoices/${id}`);
        },
        navigateToCreateOrder(){
            this.$inertia.visit(`/invoices/create`);
        }
    },
};
</script>
<style scoped lang="scss">
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
.create-order{
    background-color: $blue;
    color: white;
}
</style>


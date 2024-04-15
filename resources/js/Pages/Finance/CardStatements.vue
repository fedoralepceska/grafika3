<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="CardStatements" subtitle="listofallCardStatements" icon="clientCard.png" link="cardStatements"/>
            <div class="dark-gray p-2 text-white">
                <div class="form-container p-2 ">
                    <h2 class="sub-title">
                        {{ $t('listofallCardStatements') }}
                    </h2>
                    <div class="filter-container flex pb-10">
                        <div class="search flex gap-2">
                            <input v-model="searchQuery" placeholder="Enter client name" class="text-black" style="width: 50vh; border-radius: 3px" @keyup.enter="searchCardStatements" />
                            <button class="btn create-order1" @click="searchCardStatements">Search</button>
                        </div>
                        <div class="flex gap-3">
                            <div class="client">
                                <label class="pr-3">Filter Card Statements</label>
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
                            <button @click="applyFilter" class="btn create-order1">Filter</button>
                        </div>

                    </div>

                    <div v-if="localCardStatements">
                        <div class="border mb-1" v-for="card in localCardStatements" >
                            <div class="bg-white text-black flex justify-between">

                                <div class="p-2 bold">{{}}</div>
                                <div class="flex">

                                    <button class="flex items-center p-1" @click="viewCard(card.id)">
                                        <i class="fa fa-eye bg-gray-300 p-2 rounded" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="flex gap-40 p-2">
                                <div class="info">
                                    <div>Client ID</div>
                                    <div class="bold">#{{card?.client_id}}</div>
                                </div>
                                <div class="info">
                                    <div>Name</div>
                                    <div class="bold">{{ card?.name }}</div>
                                </div>
                                <div class="info">
                                    <div>Bank</div>
                                    <div  class="bold">{{ card?.bank }}</div>
                                </div>
                                <div class="info">
                                    <div>Bank Account</div>
                                    <div  class="bold">{{ card?.account }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <Pagination :pagination="clientCards"/>
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
        clientCards:Object,
    },
    data() {
        return {
            searchQuery: '',
            filterClient: 'All',
            sortOrder: 'desc',
            localCardStatements: [],
            uniqueClients:[],
        };
    },
    mounted() {
        this.localCardStatements = this.clientCards.data.slice();
        this.fetchUniqueClients()
    },
    methods: {
        async applyFilter() {
            try {
                const response = await axios.get('/cardStatements', {
                    params: {
                        searchQuery: encodeURIComponent(this.searchQuery),
                        sortOrder: this.sortOrder,
                        client: this.filterClient,
                    },
                });
                this.localCardStatements = response.data;
                let redirectUrl = '/cardStatements';
                if (this.searchQuery) {
                    redirectUrl += `?searchQuery=${encodeURIComponent(this.searchQuery)}`;
                }
                if (this.sortOrder) {
                    redirectUrl += `${this.searchQuery}sortOrder=${this.sortOrder}`;
                }
                if (this.filterClient) {
                    redirectUrl += `${this.searchQuery || this.sortOrder ? '&' : '?'}client=${this.filterClient}`;
                }

                this.$inertia.visit(redirectUrl);
            } catch (error) {
                console.error(error);
            }
        },
        async searchCardStatements() {
            try {
                const response = await axios.get(`?searchQuery=${encodeURIComponent(this.searchQuery)}`);
                this.localCardStatements = response.data;
                this.$inertia.visit(`/cardStatements?searchQuery=${this.searchQuery}`);
            } catch (error) {
                console.error(error);
            }
        },
        async fetchUniqueClients() {
            try {
                const response = await axios.get('/unique-clients');
                this.uniqueClients = response.data;
                console.log(this.uniqueClients)
            } catch (error) {
                console.error(error);
            }
        },
        viewCard(id) {
            this.$inertia.visit(`/cardStatement/${id}`);
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
    justify-items: center;
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


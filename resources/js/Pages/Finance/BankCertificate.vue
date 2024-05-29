<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <div class="flex justify-between">
                <Header title="statement" subtitle="bankStatement" icon="bill.png" link="statements"/>
                <div class="flex pt-4">
                    <div class="flex gap-2 pt-3">
                        <button class="btn"><ViewBanksDialog :bank="bank"/></button>
                        <button class="btn"><AddBankDialog :bank="bank" /></button>
                    </div>
                </div>
            </div>
            <div class="dark-gray p-2 text-white">
                <RedirectTabs :route="$page.url" />
                <div class="form-container p-2 ">
                    <h2 class="sub-title">
                        {{ $t('listOfAllStatements') }}
                    </h2>
                    <div class="filter-container flex gap-4 pb-10">
                        <div class="search flex gap-2">
                            <input v-model="searchQuery" placeholder="Enter Statement Id" class="text-black" style="width: 50vh; border-radius: 3px" @keyup.enter="searchInvoices" />
                            <button class="btn create-order1" @click="searchCertificates">Search</button>
                        </div>
                        <div class="flex gap-3">
                            <div class="client">
                                <label class="pr-3">Filter Statements</label>
                                <select v-model="filterBank" class="text-black">
                                    <option value="All" hidden>Bank Accounts</option>
                                    <option value="All">All Banks</option>
                                    <option v-for="bankAccount in uniqueBanks" :key="bankAccount">{{ bankAccount }}</option>
                                </select>
                            </div>
                            <div class="date">
                                <select v-model="sortOrder" class="text-black">
                                    <option value="desc" hidden>Date</option>
                                    <option value="desc">Newest to Oldest</option>
                                    <option value="asc">Oldest to Newest</option>
                                </select>
                            </div>
                            <div class="button flex gap-5">
                                <div>
                                    <button @click="applyFilter" class="btn create-order1">Filter</button>
                                </div>
                                    <AddCertificateDialog
                                        :certificate="certificate"
                                    />
                            </div>
                        </div>
                    </div>
                    <div v-if="certificates.data">
                        <div class="border mb-1" v-for="certificate in certificates.data" :key="certificate.id">
                            <div class="bg-white text-black flex justify-between">
                                <div class="p-2 bold">{{certificate.id}}/{{ new Date(certificate.created_at).toLocaleDateString('en-US', { year: 'numeric' }) }}</div>
                                <div class="flex">
                                    <button class="flex items-center p-1" @click="viewCertificate(certificate.id)">
                                        <i class="fa fa-eye bg-gray-300 p-2 rounded" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="flex gap-40 p-2">
                                <div class="info">
                                    <div>Statement</div>
                                    <div class="bold">#{{certificate.id}}</div>
                                </div>
                                <div class="info">
                                    <div>Bank</div>
                                    <div class="bold">{{certificate.bank}}</div>
                                </div>
                                <div class="info">
                                    <div>Bank Account</div>
                                    <div  class="bold">{{certificate.bankAccount}}</div>
                                </div>
                                <div class="info">
                                    <div>Created By</div>
                                    <div  class="bold">{{certificate.created_by?.name}}</div>
                                </div>
                                <div class="info">
                                    <div>Date Created</div>
                                    <div>{{ new Date(certificate.created_at).toLocaleDateString('en-US', { year: 'numeric', month: '2-digit', day: '2-digit' }) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <Pagination :pagination="certificates"/>
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
import AddCertificateDialog from "@/Components/AddCertificateDialog.vue";
import RedirectTabs from "@/Components/RedirectTabs.vue";
import AddBankDialog from "@/Components/AddBankDialog.vue";
import ViewBanksDialog from "@/Components/ViewBanksDialog.vue";

export default {
    components: {
        ViewBanksDialog,
        Header, MainLayout,Pagination,OrderJobDetails, ViewLockDialog,AddCertificateDialog, RedirectTabs, AddBankDialog},
    props:{
        certificates:Object,
        bank:Object,
    },
    data() {
        return {
            searchQuery: '',
            filterBank: 'All',
            sortOrder: 'desc',
            uniqueBanks:[],
            localCertificates: [],
        };
    },
    mounted() {
        this.fetchUniqueBanks()
    },
    methods: {
        async applyFilter() {
            try {
                const response = await axios.get('/statements', {
                    params: {
                        searchQuery: encodeURIComponent(this.searchQuery),
                        sortOrder: this.sortOrder,
                        bank: this.filterBank,
                    },
                });
                this.localCertificates = response.data;
                let redirectUrl = '/statements';
                if (this.searchQuery) {
                    redirectUrl += `?searchQuery=${encodeURIComponent(this.searchQuery)}`;
                }
                if (this.sortOrder) {
                    redirectUrl += `${this.searchQuery  ? '&' : '?'}sortOrder=${this.sortOrder}`;
                }
                if (this.filterBank) {
                    redirectUrl += `${this.searchQuery || this.sortOrder ? '&' : '?'}bankAccount=${this.filterBank}`;
                }

                this.$inertia.visit(redirectUrl);
            } catch (error) {
                console.error(error);
            }
        },
        async searchCertificates() {
            try {
                const response = await axios.get(`?searchQuery=${encodeURIComponent(this.searchQuery)}`);
                this.localCertificates = response.data;
                this.$inertia.visit(`/statements?searchQuery=${this.searchQuery}`);
            } catch (error) {
                console.error(error);
            }
        },
        async fetchUniqueBanks() {
            try {
                const response = await axios.get('/unique-banks');
                this.uniqueBanks = response.data;
            } catch (error) {
                console.error(error);
            }
        },
        viewCertificate(id) {
            this.$inertia.visit(`/statements/${id}`);
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


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
                    <div class="filter-container flex items-center gap-4 pb-6 flex-wrap">
                        <div class="search flex gap-2">
                            <input v-model="searchQuery" placeholder="Enter Statement Id" class="search-input" @keyup.enter="searchCertificates" />
                            <button class="btn create-order1" @click="searchCertificates">Search</button>
                        </div>
                        <div class="flex items-center gap-3 flex-wrap">
                            <span class="filter-label">Filters:</span>
                            <select v-model="filterBank" @change="applyFilter" class="filter-select">
                                <option value="All">All Banks</option>
                                <option v-for="bankAccount in uniqueBanks" :key="bankAccount">{{ bankAccount }}</option>
                            </select>
                            <select v-model="filterYear" @change="applyFilter" class="filter-select">
                                <option value="All">All Years</option>
                                <option v-for="year in availableYears" :key="year" :value="year">{{ year }}</option>
                            </select>
                            <select v-model="sortOrder" @change="applyFilter" class="filter-select">
                                <option value="desc">Newest First</option>
                                <option value="asc">Oldest First</option>
                            </select>
                        </div>
                        <div class="ml-auto">
                            <AddCertificateDialog :certificate="certificate" />
                        </div>
                    </div>
                    <div v-if="certificates.data">
                        <div class="border mb-1" v-for="certificate in certificates.data" :key="certificate.id">
                            <div class="bg-white text-black flex justify-between">
                                <div class="p-2 bold">{{certificate.id_per_bank}}/{{ certificate.fiscal_year }}</div>
                                <div class="flex">
                                    <button class="flex items-center p-1" @click="viewCertificate(certificate.id)">
                                        <i class="fa fa-eye bg-gray-300 p-2 rounded" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="flex gap-40 p-2">
                                <div class="info">
                                    <div>Statement</div>
                                    <div class="bold">#{{certificate.id_per_bank}}</div>
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
                                    <div>{{ new Date(certificate.date).toLocaleDateString('en-US', { year: 'numeric', month: '2-digit', day: '2-digit' }) }}</div>
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
            filterYear: 'All',
            sortOrder: 'desc',
            uniqueBanks:[],
            availableYears: [],
            localCertificates: [],
        };
    },
    mounted() {
        this.initFiltersFromUrl();
        this.fetchUniqueBanks();
        this.fetchAvailableYears();
    },
    methods: {
        initFiltersFromUrl() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('bankAccount')) {
                this.filterBank = urlParams.get('bankAccount');
            }
            if (urlParams.has('fiscalYear')) {
                this.filterYear = urlParams.get('fiscalYear');
            }
            if (urlParams.has('sortOrder')) {
                this.sortOrder = urlParams.get('sortOrder');
            }
            if (urlParams.has('searchQuery')) {
                this.searchQuery = urlParams.get('searchQuery');
            }
        },
        async applyFilter() {
            try {
                const response = await axios.get('/statements', {
                    params: {
                        searchQuery: encodeURIComponent(this.searchQuery),
                        sortOrder: this.sortOrder,
                        bank: this.filterBank,
                        fiscalYear: this.filterYear,
                    },
                });
                this.localCertificates = response.data;
                let redirectUrl = '/statements';
                let params = [];
                if (this.searchQuery) {
                    params.push(`searchQuery=${encodeURIComponent(this.searchQuery)}`);
                }
                if (this.sortOrder) {
                    params.push(`sortOrder=${this.sortOrder}`);
                }
                if (this.filterBank && this.filterBank !== 'All') {
                    params.push(`bankAccount=${this.filterBank}`);
                }
                if (this.filterYear && this.filterYear !== 'All') {
                    params.push(`fiscalYear=${this.filterYear}`);
                }
                if (params.length > 0) {
                    redirectUrl += '?' + params.join('&');
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
        async fetchAvailableYears() {
            try {
                const response = await axios.get('/statements/available-years');
                this.availableYears = response.data;
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

.filter-container {
    justify-content: space-between;
    align-items: center;
}

.search-input {
    width: 280px;
    padding: 8px 12px;
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 4px;
    background: white;
    color: #333;
    font-size: 14px;
    &::placeholder {
        color: #999;
    }
}

.filter-label {
    font-size: 14px;
    color: rgba(255, 255, 255, 0.8);
}

.filter-select {
    padding: 8px 12px;
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 4px;
    background: white;
    color: #333;
    font-size: 14px;
    min-width: 130px;
    cursor: pointer;
}

.bold {
    font-weight: bold;
}

.dark-gray {
    background-color: $dark-gray;
    justify-content: left;
    align-items: center;
    min-height: 20vh;
    min-width: 80vh;
}

.sub-title {
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    color: $white;
}

.btn {
    padding: 9px 12px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    border-radius: 4px;
}

.create-order1 {
    background-color: $blue;
    color: white;
}
</style>


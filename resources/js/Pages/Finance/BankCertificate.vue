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
            <RedirectTabs :route="$page.url" />
            <div class="dark-gray p-2 text-white overflow-x-hidden">
                <div class="form-container p-2">
                    <div class="toolbar-panel">
                        <div class="toolbar-inline">
                            <div class="filter-toolbar-bank">
                                <FinanceCompactSearch
                                    v-model="searchQuery"
                                    label="Search statement"
                                    placeholder="ID, bank, account…"
                                    class="bank-field bank-field--search"
                                    @submit="applyFilter(1)"
                                />
                                <div class="filter-col bank-field bank-field--sort">
                                    <label class="toolbar-label">Sort</label>
                                    <select v-model="sortOrder" class="text-black filter-select-compact" @change="applyFilter(1)">
                                        <option value="desc">Newest first</option>
                                        <option value="asc">Oldest first</option>
                                    </select>
                                </div>
                                <div class="filter-col bank-field bank-field--account">
                                    <label class="toolbar-label">Bank account</label>
                                    <select v-model="filterBank" class="text-black filter-select-compact" @change="applyFilter(1)">
                                        <option value="All">All banks</option>
                                        <option v-for="bankAccount in uniqueBanks" :key="bankAccount" :value="bankAccount">
                                            {{ bankAccount }}
                                        </option>
                                    </select>
                                </div>
                                <FinanceDateRangeCompact
                                    class="bank-field bank-field--dates"
                                    :date-from="dateFrom"
                                    :date-to="dateTo"
                                    label="Statement date"
                                    @update:date-from="dateFrom = $event"
                                    @update:date-to="dateTo = $event"
                                    @change="onDateRangeChange"
                                />
                                <FinanceYearMonthSelects
                                    class="bank-field bank-field--ym"
                                    :fiscal-year="fiscalYear"
                                    :month="calendarMonth"
                                    :min-year="yearSelectMin"
                                    :max-year="yearSelectMax"
                                    @update:fiscal-year="fiscalYear = $event"
                                    @update:month="calendarMonth = $event"
                                    @change="applyFilter(1)"
                                />
                            </div>
                            <FinancePeriodPresets
                                class="presets-inline-bank"
                                label=""
                                @preset="onPeriodPreset"
                                @clear-dates="onClearDates"
                            />
                            <div class="toolbar-add-inline">
                                <AddCertificateDialog />
                            </div>
                        </div>
                    </div>

                    <DataTableShell compact variant="grid">
                        <template #header>
                            <tr>
                                <th class="number-column">Nr</th>
                                <th class="statement-column">Statement</th>
                                <th>Bank</th>
                                <th>Bank Account</th>
                                <th>Created By</th>
                                <th>Date Created</th>
                                <th class="actions-column actions-header">Actions</th>
                            </tr>
                        </template>

                        <template v-if="localCertificates && localCertificates.length > 0">
                            <tr v-for="(certificate, index) in localCertificates" :key="certificate.id">
                                <td class="cell-secondary">{{ rowNumber(index) }}</td>
                                <td class="statement-primary-cell">
                                    <div class="cell-primary">#{{ certificate.id_per_bank }}</div>
                                    <div class="cell-secondary">{{ certificate.fiscal_year }}</div>
                                </td>
                                <td><div class="cell-primary">{{ certificate.bank }}</div></td>
                                <td><div class="cell-secondary">{{ certificate.bankAccount }}</div></td>
                                <td><div class="cell-secondary">{{ certificate.created_by?.name || 'N/A' }}</div></td>
                                <td><div class="cell-secondary">{{ formatDateDisplay(certificate.date) }}</div></td>
                                <td class="actions-cell">
                                    <button class="table-action-button" @click="viewCertificate(certificate.id)">
                                        View
                                    </button>
                                </td>
                            </tr>
                        </template>

                        <tr v-else>
                            <td colspan="7" class="empty-cell">
                                No bank statements found matching your criteria.
                            </td>
                        </tr>
                    </DataTableShell>
                </div>
                <Pagination :pagination="paginationState" @pagination-change-page="goToPage" />
            </div>
        </div>
    </MainLayout>
</template>

<script>
import MainLayout from "@/Layouts/MainLayout.vue";
import Header from "@/Components/Header.vue";
import Pagination from "@/Components/Pagination.vue"
import DataTableShell from "@/Components/DataTableShell.vue";
import axios from 'axios';
import AddCertificateDialog from "@/Components/AddCertificateDialog.vue";
import RedirectTabs from "@/Components/RedirectTabs.vue";
import AddBankDialog from "@/Components/AddBankDialog.vue";
import ViewBanksDialog from "@/Components/ViewBanksDialog.vue";
import FinanceCompactSearch from "@/Components/Finance/FinanceCompactSearch.vue";
import FinanceDateRangeCompact from "@/Components/Finance/FinanceDateRangeCompact.vue";
import FinanceYearMonthSelects from "@/Components/Finance/FinanceYearMonthSelects.vue";
import FinancePeriodPresets from "@/Components/Finance/FinancePeriodPresets.vue";
import { normalizeDateRangeFields } from "@/utils/financeFilters";

export default {
    components: {
        ViewBanksDialog,
        Header,
        MainLayout,
        Pagination,
        DataTableShell,
        AddCertificateDialog,
        RedirectTabs,
        AddBankDialog,
        FinanceCompactSearch,
        FinanceDateRangeCompact,
        FinanceYearMonthSelects,
        FinancePeriodPresets,
    },
    props:{
        certificates:Object,
        bank:Object,
    },
    data() {
        return {
            searchQuery: '',
            filterBank: 'All',
            sortOrder: 'desc',
            dateFrom: '',
            dateTo: '',
            fiscalYear: '',
            calendarMonth: '',
            uniqueBanks: [],
            availableYears: [],
            localCertificates: this.certificates?.data || [],
            paginationState: this.certificates || { data: [], links: [] },
            perPage: 20,
        };
    },
    computed: {
        yearSelectMin() {
            if (!this.availableYears || this.availableYears.length === 0) {
                return null;
            }
            return Math.min(...this.availableYears.map((y) => Number(y)));
        },
        yearSelectMax() {
            if (!this.availableYears || this.availableYears.length === 0) {
                return null;
            }
            return Math.max(...this.availableYears.map((y) => Number(y)));
        },
    },
    mounted() {
        this.initFiltersFromUrl();
        this.fetchUniqueBanks();
        this.fetchAvailableYears();
    },
    methods: {
        rowNumber(index) {
            const currentPage = this.paginationState?.current_page || 1;
            const perPage = this.paginationState?.per_page || this.perPage;
            return ((currentPage - 1) * perPage) + index + 1;
        },
        formatDateDisplay(date) {
            if (!date) return 'N/A';
            return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: '2-digit', day: '2-digit' });
        },
        initFiltersFromUrl() {
            const p = new URLSearchParams(window.location.search);
            this.searchQuery = p.get('searchQuery') || '';
            this.sortOrder = p.get('sortOrder') || 'desc';
            this.filterBank = p.get('bankAccount') || 'All';
            const fy = p.get('fiscal_year') || p.get('fiscalYear');
            if (fy !== null && fy !== '' && fy !== 'All') {
                const n = parseInt(fy, 10);
                this.fiscalYear = Number.isNaN(n) ? '' : n;
            } else {
                this.fiscalYear = '';
            }
            const mo = p.get('month');
            if (mo !== null && mo !== '') {
                const m = parseInt(mo, 10);
                this.calendarMonth = Number.isNaN(m) ? '' : m;
            } else {
                this.calendarMonth = '';
            }
            this.dateFrom = p.get('date_from') || '';
            this.dateTo = p.get('date_to') || '';
            const pp = p.get('per_page');
            if (pp) {
                const n = parseInt(pp, 10);
                if (!Number.isNaN(n) && n > 0) {
                    this.perPage = Math.min(200, n);
                }
            }
        },
        buildBankListParams(page) {
            const state = { dateFrom: this.dateFrom, dateTo: this.dateTo };
            normalizeDateRangeFields(state);
            this.dateFrom = state.dateFrom;
            this.dateTo = state.dateTo;

            const params = {
                sortOrder: this.sortOrder,
                per_page: this.perPage,
                page,
            };
            const sq = (this.searchQuery || '').trim();
            if (sq) {
                params.searchQuery = sq;
            }
            if (this.filterBank && this.filterBank !== 'All') {
                params.bankAccount = this.filterBank;
            }
            if (this.fiscalYear !== '' && this.fiscalYear != null) {
                params.fiscal_year = this.fiscalYear;
            }
            if (this.calendarMonth !== '' && this.calendarMonth != null) {
                params.month = this.calendarMonth;
            }
            if (this.dateFrom) {
                params.date_from = this.dateFrom;
            }
            if (this.dateTo) {
                params.date_to = this.dateTo;
            }
            return params;
        },
        pushBankHistory(params) {
            const qs = new URLSearchParams();
            Object.entries(params).forEach(([k, v]) => {
                if (v !== '' && v !== null && v !== undefined) {
                    qs.set(k, String(v));
                }
            });
            const q = qs.toString();
            window.history.pushState({}, '', q ? `/statements?${q}` : '/statements');
        },
        onDateRangeChange() {
            const state = { dateFrom: this.dateFrom, dateTo: this.dateTo };
            normalizeDateRangeFields(state);
            this.dateFrom = state.dateFrom;
            this.dateTo = state.dateTo;
            this.applyFilter(1);
        },
        onPeriodPreset(type) {
            const d = new Date();
            const y = d.getFullYear();
            const m = String(d.getMonth() + 1).padStart(2, '0');
            const today = `${y}-${m}-${String(d.getDate()).padStart(2, '0')}`;
            if (type === 'this_month') {
                this.dateFrom = `${y}-${m}-01`;
                this.dateTo = today;
                this.fiscalYear = '';
                this.calendarMonth = '';
            } else if (type === 'this_year') {
                this.dateFrom = `${y}-01-01`;
                this.dateTo = today;
                this.fiscalYear = '';
                this.calendarMonth = '';
            }
            this.applyFilter(1);
        },
        onClearDates() {
            this.dateFrom = '';
            this.dateTo = '';
            this.applyFilter(1);
        },
        async applyFilter(page = 1) {
            try {
                const params = this.buildBankListParams(page);
                const response = await axios.get('/statements', {
                    params,
                    headers: { Accept: 'application/json' },
                });
                this.localCertificates = response.data.data;
                this.paginationState = response.data;
                this.pushBankHistory(params);
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
        goToPage(page) {
            this.applyFilter(page);
        },
    },
};
</script>
<style scoped lang="scss">
.toolbar-inline {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 10px 12px;
}

.filter-toolbar-bank {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 10px 12px;
    flex: 1 1 auto;
    min-width: 0;
}

.filter-toolbar-bank .bank-field--search {
    flex: 0 1 200px;
    min-width: 160px;
    max-width: 280px;
}

.filter-toolbar-bank .bank-field--sort {
    flex: 0 0 130px;
    min-width: 120px;
}

.filter-toolbar-bank .bank-field--account {
    flex: 0 1 180px;
    min-width: 140px;
}

.filter-toolbar-bank .bank-field--dates {
    flex: 1 1 280px;
    min-width: 260px;
    max-width: 340px;
}

.filter-toolbar-bank .bank-field--ym {
    flex: 0 1 260px;
    min-width: 240px;
}

.presets-inline-bank {
    flex: 0 0 auto;
    align-self: flex-end;
    min-width: 0;
}

.toolbar-add-inline {
    flex: 0 0 auto;
    align-self: flex-end;
    margin-left: auto;
}

@media (min-width: 900px) {
    .presets-inline-bank :deep(.fc-pp__row) {
        flex-wrap: nowrap;
    }
}

@media (max-width: 1100px) {
    .filter-toolbar-bank .bank-field--dates {
        flex-basis: 100%;
        max-width: none;
    }

    .filter-toolbar-bank .bank-field--ym {
        flex-basis: 100%;
    }

    .toolbar-add-inline {
        margin-left: 0;
        width: 100%;
        display: flex;
        justify-content: flex-end;
    }
}

.toolbar-panel {
    margin-bottom: 14px;
    padding: 12px 14px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.04);
}

.toolbar-label {
    display: block;
    margin-bottom: 4px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: rgba(255, 255, 255, 0.7);
}

.filter-col {
    min-width: 0;
}

.filter-select-compact {
    width: 100%;
    min-height: 34px;
    border-radius: 8px;
    padding: 0 8px;
    font-size: 13px;
}

.search-input {
    width: 100%;
    min-height: 40px;
    padding: 8px 12px;
    border: 1px solid rgba(255, 255, 255, 0.16);
    border-radius: 4px;
    background: white;
    color: #333;
    font-size: 14px;
    &::placeholder {
        color: #999;
    }
}

.dark-gray {
    background-color: $dark-gray;
    justify-content: left;
    align-items: center;
    min-height: 20vh;
    border-radius: 8px;
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

.number-column {
    width: 70px;
}

.statement-column {
    width: 160px;
}

.actions-column {
    width: 120px;
}

.actions-header {
    text-align: right;
}

.statement-primary-cell {
    min-width: 140px;
}

.cell-primary {
    font-weight: 700;
    color: $white;
}

.cell-secondary {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.68);
}

.actions-cell {
    text-align: right;
}

.table-action-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 6px 10px;
    border: 1px solid rgba(255, 255, 255, 0.14);
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.06);
    color: $white;
    font-size: 12px;
    font-weight: 700;
    transition: all 0.2s ease;
}

.table-action-button:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.24);
}

.empty-cell {
    padding: 34px 16px !important;
    text-align: center;
    color: rgba(255, 255, 255, 0.7);
}

@media (max-width: 768px) {
    .toolbar-add-inline {
        width: 100%;
        justify-content: flex-start;
    }
}
</style>


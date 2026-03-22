<template>
    <MainLayout>
        <div class="px-4 sm:px-6 lg:px-7">
            <Header title="receipt" subtitle="allReceipts" icon="Materials.png" link="receipt" />
            <div class="form-container p15">
                <RedirectTabs :route="$page.url" />
                <div class="dark-gray p-2 text-white overflow-x-hidden">
                    <div class="content-panel p-4 overflow-x-auto">
                        <div class="page-heading">
                            <h2 class="sub-title">
                                {{ $t('allReceipts') }}
                            </h2>
                            <div class="page-heading-actions">
                                <button @click="$inertia.visit('/receipt/create')" class="btn create-order1 header-action">
                                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                                    <span>Add New Receipt</span>
                                </button>
                                <MaterialImportSummaryDialog class="header-import-summary" />
                            </div>
                        </div>

                        <div class="toolbar-panel">
                            <div class="toolbar-inline">
                                <div class="filter-toolbar-pr">
                                    <FinanceCompactSearch
                                        v-model="searchInput"
                                        label="Search"
                                        placeholder="Receipt #, id…"
                                        class="pr-field pr-field--search"
                                        @submit="onSearchSubmit"
                                    />
                                    <FinanceClientSearchSelect
                                        v-model="clientId"
                                        :clients="clients"
                                        label="Client"
                                        class="pr-field pr-field--client"
                                        @change="applyFilter(1)"
                                    />
                                    <div class="filter-col pr-field pr-field--warehouse">
                                        <label class="toolbar-label">Warehouse</label>
                                        <select v-model="warehouseId" class="text-black filter-select-compact" @change="applyFilter(1)">
                                            <option value="All" hidden>Warehouses</option>
                                            <option value="All">All warehouses</option>
                                            <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">
                                                {{ warehouse.name }}
                                            </option>
                                        </select>
                                    </div>
                                    <FinanceDateRangeCompact
                                        class="pr-field pr-field--dates"
                                        :date-from="dateFrom"
                                        :date-to="dateTo"
                                        label="Created"
                                        @update:date-from="dateFrom = $event"
                                        @update:date-to="dateTo = $event"
                                        @change="onDateRangeChange"
                                    />
                                    <FinanceYearMonthSelects
                                        class="pr-field pr-field--ym"
                                        :fiscal-year="fiscalYear"
                                        :month="calendarMonth"
                                        @update:fiscal-year="fiscalYear = $event"
                                        @update:month="calendarMonth = $event"
                                        @change="applyFilter(1)"
                                    />
                                </div>
                                <FinancePeriodPresets
                                    class="presets-inline-pr"
                                    label=""
                                    @preset="onPeriodPreset"
                                    @clear-dates="onClearDates"
                                />
                            </div>
                        </div>

                        <DataTableShell compact variant="grid">
                            <template #header>
                                <tr>
                                    <th class="number-column">{{$t('Nr')}}</th>
                                    <th class="receipt-column">{{$t('receiptId')}}</th>
                                    <th>{{$t('date')}}</th>
                                    <th>{{$t('warehouse')}}</th>
                                    <th class="customer-column">{{$t('client')}}</th>
                                    <th class="text-right">{{$t('price')}} (.ден)</th>
                                    <th class="text-right">{{$t('price')}} + {{$t('VAT')}} (.ден)</th>
                                    <th>{{$t('comment')}}</th>
                                    <th class="actions-column actions-header">{{$t('ACTIONS')}}</th>
                                </tr>
                            </template>

                            <template v-if="localReceipts && localReceipts.length > 0">
                                <tr v-for="(receipt, index) in localReceipts" :key="receipt.id">
                                    <td class="cell-secondary">{{ rowNumber(index) }}</td>
                                    <td class="receipt-primary-cell">
                                        <div class="cell-primary">#{{ receipt.receipt_number }}/{{ receipt.fiscal_year }}</div>
                                    </td>
                                    <td><div class="cell-secondary">{{ formatDateDisplay(receipt.created_at) }}</div></td>
                                    <td><div class="cell-secondary">{{ receipt.warehouse_name || 'N/A' }}</div></td>
                                    <td class="customer-column">
                                        <FinanceClientNameCell :name="receipt.client?.name || ''" empty-label="/" />
                                    </td>
                                    <td><div class="cell-secondary text-right">{{ formatNumber(calculateTotalPrice(receipt.articles)) }}</div></td>
                                    <td><div class="cell-primary text-right">{{ formatNumber(calculateTotalPriceWithVAT(receipt.articles)) }}</div></td>
                                    <td><div class="cell-secondary comment-cell">{{ receipt.comment ? receipt.comment : '/' }}</div></td>
                                    <td class="actions-cell">
                                        <div class="action-buttons">
                                            <PriemInfoDialog :priem="receipt" />
                                            <ActionDropdown
                                                icon-only
                                                trigger-title="Receipt actions"
                                                :groups="getActionMenuGroups()"
                                                @select="handleActionMenuSelect(receipt, $event)"
                                            />
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <tr v-else>
                                <td colspan="9" class="empty-cell">
                                    No receipts found matching your criteria.
                                </td>
                            </tr>
                        </DataTableShell>

                        <Pagination :pagination="paginationState" @pagination-change-page="goToPage" />
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script>
import MainLayout from "@/Layouts/MainLayout.vue";
import Pagination from "@/Components/Pagination.vue";
import Header from "@/Components/Header.vue";
import DataTableShell from "@/Components/DataTableShell.vue";
import PriemInfoDialog from "@/Components/PriemInfoDialog.vue";
import MaterialImportSummaryDialog from "@/Components/MaterialImportSummaryDialog.vue";
import RedirectTabs from "@/Components/RedirectTabs.vue";
import ActionDropdown from "@/Components/ActionDropdown.vue";
import FinanceCompactSearch from "@/Components/Finance/FinanceCompactSearch.vue";
import FinanceClientSearchSelect from "@/Components/Finance/FinanceClientSearchSelect.vue";
import FinanceDateRangeCompact from "@/Components/Finance/FinanceDateRangeCompact.vue";
import FinanceYearMonthSelects from "@/Components/Finance/FinanceYearMonthSelects.vue";
import FinancePeriodPresets from "@/Components/Finance/FinancePeriodPresets.vue";
import FinanceClientNameCell from "@/Components/Finance/FinanceClientNameCell.vue";
import { normalizeDateRangeFields } from "@/utils/financeFilters";
import axios from "axios";

export default {
    components: {
        MainLayout,
        Pagination,
        Header,
        DataTableShell,
        PriemInfoDialog,
        MaterialImportSummaryDialog,
        RedirectTabs,
        ActionDropdown,
        FinanceCompactSearch,
        FinanceClientSearchSelect,
        FinanceDateRangeCompact,
        FinanceYearMonthSelects,
        FinancePeriodPresets,
    },
    props: {
        receipts: Object,
        clients: Array,
        warehouses: Array,
    },
    data() {
        return {
            localReceipts: this.receipts?.data || [],
            paginationState: this.receipts || { data: [], links: [] },
            perPage: 20,
            searchInput: '',
            searchQuery: '',
            clientId: null,
            warehouseId: 'All',
            dateFrom: '',
            dateTo: '',
            fiscalYear: '',
            calendarMonth: '',
        };
    },
    mounted() {
        this.initFromUrl();
        const page = parseInt(new URLSearchParams(window.location.search).get('page') || '1', 10) || 1;
        this.applyFilter(page);
    },
    methods: {
        initFromUrl() {
            const p = new URLSearchParams(window.location.search);
            this.searchInput = p.get('searchQuery') || '';
            this.searchQuery = this.searchInput;
            const cid = p.get('client_id');
            if (cid && cid !== 'All') {
                const n = parseInt(cid, 10);
                this.clientId = Number.isNaN(n) ? null : n;
            } else {
                this.clientId = null;
            }
            const wid = p.get('warehouse_id');
            if (wid && wid !== '' && wid !== 'All') {
                const wn = parseInt(wid, 10);
                this.warehouseId = Number.isNaN(wn) ? 'All' : wn;
            } else {
                this.warehouseId = 'All';
            }
            this.dateFrom = p.get('from_date') || p.get('date_from') || '';
            this.dateTo = p.get('to_date') || p.get('date_to') || '';
            const fy = p.get('fiscal_year');
            this.fiscalYear = fy !== null && fy !== '' ? parseInt(fy, 10) : '';
            if (Number.isNaN(this.fiscalYear)) {
                this.fiscalYear = '';
            }
            const mo = p.get('month');
            this.calendarMonth = mo !== null && mo !== '' ? parseInt(mo, 10) : '';
            if (Number.isNaN(this.calendarMonth)) {
                this.calendarMonth = '';
            }
            const pp = p.get('per_page');
            if (pp) {
                const n = parseInt(pp, 10);
                if (!Number.isNaN(n) && n > 0) {
                    this.perPage = Math.min(200, n);
                }
            }
        },
        buildReceiptParams(page = 1) {
            const state = { dateFrom: this.dateFrom, dateTo: this.dateTo };
            normalizeDateRangeFields(state);
            this.dateFrom = state.dateFrom;
            this.dateTo = state.dateTo;

            const params = {
                page,
                per_page: this.perPage,
            };
            const sq = (this.searchQuery || '').trim();
            if (sq) {
                params.searchQuery = sq;
            }
            if (this.clientId != null) {
                params.client_id = this.clientId;
            }
            if (this.warehouseId && this.warehouseId !== 'All') {
                params.warehouse_id = this.warehouseId;
            }
            if (this.dateFrom) {
                params.from_date = this.dateFrom;
            }
            if (this.dateTo) {
                params.to_date = this.dateTo;
            }
            if (this.fiscalYear !== '' && this.fiscalYear != null) {
                params.fiscal_year = this.fiscalYear;
            }
            if (this.calendarMonth !== '' && this.calendarMonth != null) {
                params.month = this.calendarMonth;
            }
            return params;
        },
        pushReceiptHistory(params) {
            const qs = new URLSearchParams();
            Object.entries(params).forEach(([k, v]) => {
                if (v !== '' && v !== null && v !== undefined) {
                    qs.set(k, String(v));
                }
            });
            const q = qs.toString();
            window.history.pushState({}, '', q ? `/receipt?${q}` : '/receipt');
        },
        onSearchSubmit() {
            this.searchQuery = (this.searchInput || '').trim();
            this.applyFilter(1);
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
        rowNumber(index) {
            const currentPage = this.paginationState?.current_page || 1;
            const perPage = this.paginationState?.per_page || this.perPage;
            return ((currentPage - 1) * perPage) + index + 1;
        },
        taxTypePercentage(taxType) {
            const type = String(taxType);
            switch (type) {
                case '1':
                    return 18;
                case '2':
                    return 5;
                case '3':
                    return 10;
                default:
                    return 0;
            }
        },
        formatNumber(number) {
            return Number(number).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        formatDateDisplay(date) {
            if (!date) return 'N/A';
            return new Date(date).toLocaleDateString('en-GB');
        },
        calculateTotalPrice(articles) {
            return articles.reduce((total, article) => {
                const articleTotal = (article.purchase_price || 0) * (article.pivot.quantity || 0);
                return total + articleTotal;
            }, 0);
        },
        calculateTotalPriceWithVAT(articles) {
            return articles.reduce((total, article) => {
                const basePrice = (article.purchase_price || 0) * (article.pivot.quantity || 0);
                const vatPercentage = this.taxTypePercentage(article.tax_type);
                const vatAmount = basePrice * (vatPercentage / 100);
                return total + basePrice + vatAmount;
            }, 0);
        },
        applyFilter(page = 1) {
            const params = this.buildReceiptParams(page);
            axios.get('/receipt', {
                params,
                headers: { Accept: 'application/json' },
            })
                .then((response) => {
                    this.localReceipts = response.data.data;
                    this.paginationState = response.data;
                    this.pushReceiptHistory(params);
                })
                .catch((error) => {
                    console.error('Error applying filters:', error);
                });
        },
        goToPage(page) {
            this.applyFilter(page);
        },
        getActionMenuGroups() {
            return [
                {
                    label: 'Receipt Actions',
                    items: [
                        { label: this.$t('Edit'), value: 'edit' },
                    ],
                },
            ];
        },
        handleActionMenuSelect(receipt, item) {
            if (item.value === 'edit') {
                this.editReceipt(receipt.id);
            }
        },
        editReceipt(receiptId) {
            this.$inertia.visit(`/receipt/${receiptId}/edit`);
        }
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

.filter-toolbar-pr {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 10px 12px;
    flex: 1 1 auto;
    min-width: 0;
}

.filter-toolbar-pr .pr-field--search {
    flex: 0 1 180px;
    min-width: 140px;
    max-width: 240px;
}

.filter-toolbar-pr .pr-field--client {
    flex: 0 1 200px;
    min-width: 160px;
}

.filter-toolbar-pr .pr-field--warehouse {
    flex: 0 1 160px;
    min-width: 130px;
}

.filter-toolbar-pr .pr-field--dates {
    flex: 1 1 280px;
    min-width: 260px;
    max-width: 340px;
}

.filter-toolbar-pr .pr-field--ym {
    flex: 0 1 260px;
    min-width: 240px;
}

.presets-inline-pr {
    flex: 0 0 auto;
    align-self: flex-end;
    min-width: 0;
}

@media (min-width: 900px) {
    .presets-inline-pr :deep(.fc-pp__row) {
        flex-wrap: nowrap;
    }
}

@media (max-width: 1100px) {
    .filter-toolbar-pr .pr-field--dates {
        flex-basis: 100%;
        max-width: none;
    }

    .filter-toolbar-pr .pr-field--ym {
        flex-basis: 100%;
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

.filter-select,
select,
input[type="date"] {
    width: 100%;
    max-width: 100%;
    min-height: 40px;
    padding: 8px 10px;
    border-radius: 8px;
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

.dark-gray {
    background-color: $dark-gray;
    justify-content: left;
    align-items: center;
    min-height: 20vh;
    min-width: 0;
    border-radius: 8px;
}
.content-panel {
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.04) 0%, rgba(255, 255, 255, 0.02) 100%);
    border-radius: 10px;
}

.page-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 18px;
}

.page-heading-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
}

.page-heading-actions :deep(.header-import-summary) {
    display: inline-flex;
    align-items: center;
}

.header-action {
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.sub-title{
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 0;
    display: flex;
    align-items: center;
    color: $white;
}

.number-column {
    width: 70px;
}

.customer-column {
    max-width: 280px;
    width: 18%;
}

.receipt-column {
    width: 180px;
}

.actions-column {
    width: 130px;
}

.receipt-primary-cell {
    min-width: 170px;
}

.cell-primary {
    font-weight: 700;
    color: $white;
}

.cell-secondary {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.68);
}

.comment-cell {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-width: 180px;
    white-space: normal;
}

.text-right {
    text-align: right;
}

.actions-cell {
    text-align: right;
}

.action-buttons {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 8px;
}

.empty-cell {
    padding: 34px 16px !important;
    text-align: center;
    color: rgba(255, 255, 255, 0.7);
}

@media (max-width: 1024px) {
    .page-heading {
        flex-direction: column;
        align-items: flex-start;
    }
    .page-heading-actions {
        width: 100%;
        justify-content: flex-start;
    }
}

@media (max-width: 640px) {
    .page-heading-actions {
        flex-direction: column;
        align-items: stretch;
    }
    .page-heading-actions .header-action {
        width: 100%;
        justify-content: center;
        box-sizing: border-box;
    }
    .page-heading-actions :deep(.header-import-summary button) {
        width: 100%;
        justify-content: center;
        box-sizing: border-box;
    }
}

</style>

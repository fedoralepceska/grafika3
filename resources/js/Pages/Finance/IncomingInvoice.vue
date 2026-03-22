<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="invoice2" subtitle="incomingInvoice" icon="invoice.png" link="incomingInvoice" />
            <RedirectTabs :route="$page.url" />
            <div class="dark-gray p-2 text-white min-w-0">
                <div class="form-container p-2 min-w-0">
                    <div class="toolbar-panel">
                        <div class="toolbar-inline">
                            <div class="filter-toolbar-incoming">
                                <FinanceCompactSearch
                                    v-model="searchInput"
                                    label="Invoice #"
                                    placeholder="Incoming #…"
                                    class="incoming-field incoming-field--search"
                                    @submit="onSearchSubmit"
                                />
                                <div class="filter-col incoming-field incoming-field--sort">
                                    <label class="toolbar-label">Order</label>
                                    <select v-model="sortOrder" class="text-black filter-select-compact" @change="fetchList(1)">
                                        <option value="desc">Newest first</option>
                                        <option value="asc">Oldest first</option>
                                    </select>
                                </div>
                                <FinanceClientSearchSelect
                                    v-model="clientId"
                                    :clients="clients"
                                    label="Client"
                                    class="incoming-field incoming-field--client"
                                    @change="fetchList(1)"
                                />
                                <div class="filter-col incoming-field">
                                    <label class="toolbar-label">Warehouse</label>
                                    <select v-model="filterWarehouse" class="text-black filter-select-compact" @change="fetchList(1)">
                                        <option value="">All</option>
                                        <option v-for="warehouse in $page.props.warehouses" :key="warehouse" :value="warehouse">
                                            {{ warehouse }}
                                        </option>
                                    </select>
                                </div>
                                <div class="filter-col incoming-field">
                                    <label class="toolbar-label">Cost type</label>
                                    <select v-model="filterCostType" class="text-black filter-select-compact" @change="fetchList(1)">
                                        <option value="">All</option>
                                        <option v-for="type in $page.props.costTypes" :key="type.id" :value="type.id">
                                            {{ type.name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="filter-col incoming-field">
                                    <label class="toolbar-label">Bill type</label>
                                    <select v-model="filterBillType" class="text-black filter-select-compact" @change="fetchList(1)">
                                        <option value="">All</option>
                                        <option v-for="type in $page.props.billTypes" :key="type.id" :value="type.id">
                                            {{ type.name }}
                                        </option>
                                    </select>
                                </div>
                                <FinanceDateRangeCompact
                                    class="incoming-field incoming-field--dates"
                                    :date-from="dateFrom"
                                    :date-to="dateTo"
                                    label="Created"
                                    @update:date-from="dateFrom = $event"
                                    @update:date-to="dateTo = $event"
                                    @change="onDateRangeChange"
                                />
                                <FinanceYearMonthSelects
                                    class="incoming-field incoming-field--ym"
                                    :fiscal-year="fiscalYear"
                                    :month="calendarMonth"
                                    @update:fiscal-year="fiscalYear = $event"
                                    @update:month="calendarMonth = $event"
                                    @change="fetchList(1)"
                                />
                            </div>
                            <FinancePeriodPresets class="presets-inline" label="" @preset="onPeriodPreset" @clear-dates="onClearDates" />
                        </div>

                        <div class="toolbar-actions">
                            <AddIncomingInvoiceDialog
                                :incoming-invoice="displayIncoming"
                                :cost-types="$page.props.costTypes"
                                :bill-types="$page.props.billTypes"
                                @invoice-added="handleInvoiceAdded"
                            />
                        </div>
                    </div>

                    <div v-if="loading" class="loading-inline">
                        <i class="fa fa-spinner fa-spin" /> Loading…
                    </div>

                    <div v-else class="incoming-table-shell">
                    <DataTableShell compact variant="grid">
                        <template #header>
                            <tr>
                                <th class="invoice-column">Invoice</th>
                                <th class="customer-column">Client</th>
                                <th class="col-wh" title="Warehouse">Warehouse</th>
                                <th class="col-cost" title="Cost type">Cost</th>
                                <th class="col-bill" title="Bill type">Bill</th>
                                <th class="col-num">Amount</th>
                                <th class="col-num">Tax</th>
                                <th class="col-num">Total</th>
                                <th class="col-date">Date</th>
                                <th class="col-comment">Comment</th>
                                <th class="actions-column actions-header">Actions</th>
                            </tr>
                        </template>

                        <template v-if="displayIncoming.data && displayIncoming.data.length > 0">
                            <tr v-for="faktura in displayIncoming.data" :key="faktura.id">
                                <td class="invoice-primary-cell">
                                    <div class="invoice-main">
                                        <div class="cell-primary">#{{ faktura.incoming_number }}</div>
                                        <span v-if="faktura.billing_type === 'фактура'" class="archive-badge">
                                            Archive #{{ faktura.faktura_counter }}
                                        </span>
                                    </div>
                                    <div class="cell-secondary">{{ formatInvoicePeriod(faktura.created_at) }}</div>
                                </td>
                                <td class="customer-column">
                                    <FinanceClientNameCell :name="faktura.client_name || ''" />
                                </td>
                                <td class="col-wh"><div class="cell-secondary incoming-ellipsis" :title="faktura.warehouse || ''">{{ faktura.warehouse || 'N/A' }}</div></td>
                                <td class="col-cost"><div class="cell-secondary incoming-text-wrap" :title="faktura.cost_type || ''">{{ faktura.cost_type || 'N/A' }}</div></td>
                                <td class="col-bill"><div class="cell-secondary incoming-text-wrap" :title="faktura.billing_type || ''">{{ faktura.billing_type || 'N/A' }}</div></td>
                                <td class="col-num"><div class="cell-secondary text-right">{{ faktura.amount }}</div></td>
                                <td class="col-num"><div class="cell-secondary text-right">{{ faktura.tax }}</div></td>
                                <td class="col-num"><div class="cell-primary text-right">{{ calculateTotal(faktura) }}</div></td>
                                <td class="col-date"><div class="cell-secondary incoming-ellipsis">{{ formatFullDate(faktura.date) }}</div></td>
                                <td class="col-comment"><div class="cell-secondary comment-cell" :title="faktura.comment || ''">{{ faktura.comment || 'N/A' }}</div></td>
                                <td class="actions-cell">
                                    <EditIncomingInvoiceDialog
                                        :invoice="faktura"
                                        :cost-types="$page.props.costTypes"
                                        :bill-types="$page.props.billTypes"
                                        :warehouses="$page.props.warehouses"
                                        :clients="$page.props.clients"
                                        @invoice-updated="handleInvoiceUpdated"
                                    />
                                </td>
                            </tr>
                        </template>

                        <tr v-else>
                            <td colspan="11" class="empty-cell">
                                No incoming invoices found matching your criteria.
                            </td>
                        </tr>
                    </DataTableShell>
                    </div>
                </div>
                <Pagination :pagination="displayIncoming" @pagination-change-page="goToPage" />
            </div>
        </div>
    </MainLayout>
</template>

<script>
import MainLayout from '@/Layouts/MainLayout.vue';
import Header from '@/Components/Header.vue';
import Pagination from '@/Components/Pagination.vue';
import DataTableShell from '@/Components/DataTableShell.vue';
import RedirectTabs from '@/Components/RedirectTabs.vue';
import AddIncomingInvoiceDialog from '@/Components/AddIncomingInvoiceDialog.vue';
import EditIncomingInvoiceDialog from '@/Components/EditIncomingInvoiceDialog.vue';
import FinanceCompactSearch from '@/Components/Finance/FinanceCompactSearch.vue';
import FinanceClientSearchSelect from '@/Components/Finance/FinanceClientSearchSelect.vue';
import FinanceDateRangeCompact from '@/Components/Finance/FinanceDateRangeCompact.vue';
import FinanceYearMonthSelects from '@/Components/Finance/FinanceYearMonthSelects.vue';
import FinancePeriodPresets from '@/Components/Finance/FinancePeriodPresets.vue';
import FinanceClientNameCell from '@/Components/Finance/FinanceClientNameCell.vue';
import axios from 'axios';
import { normalizeDateRangeFields } from '@/utils/financeFilters';

export default {
    components: {
        Header,
        MainLayout,
        Pagination,
        DataTableShell,
        RedirectTabs,
        AddIncomingInvoiceDialog,
        EditIncomingInvoiceDialog,
        FinanceCompactSearch,
        FinanceClientSearchSelect,
        FinanceDateRangeCompact,
        FinanceYearMonthSelects,
        FinancePeriodPresets,
        FinanceClientNameCell,
    },
    props: {
        incomingInvoice: Object,
        costTypes: {
            type: Array,
            required: true,
        },
        billTypes: {
            type: Array,
            required: true,
        },
        clients: {
            type: Array,
            required: true,
        },
    },
    data() {
        return {
            searchInput: '',
            searchQuery: '',
            clientId: null,
            filterWarehouse: '',
            filterCostType: '',
            filterBillType: '',
            dateFrom: '',
            dateTo: '',
            fiscalYear: '',
            calendarMonth: '',
            sortOrder: 'desc',
            perPage: 20,
            localIncoming: null,
            loading: false,
        };
    },
    computed: {
        displayIncoming() {
            return this.localIncoming !== null ? this.localIncoming : this.incomingInvoice;
        },
    },
    mounted() {
        this.initFromUrl();
        const page = parseInt(new URLSearchParams(window.location.search).get('page') || '1', 10) || 1;
        this.fetchList(page);
    },
    methods: {
        initFromUrl() {
            const p = new URLSearchParams(window.location.search);
            this.searchInput = p.get('searchQuery') || '';
            this.searchQuery = this.searchInput;
            const cid = p.get('filterClient');
            if (cid && cid !== 'All') {
                const n = parseInt(cid, 10);
                this.clientId = Number.isNaN(n) ? null : n;
            }
            this.filterWarehouse = p.get('filterWarehouse') || '';
            this.filterCostType = p.get('filterCostType') || '';
            this.filterBillType = p.get('filterBillType') || '';
            this.dateFrom = p.get('date_from') || '';
            this.dateTo = p.get('date_to') || '';
            const fy = p.get('fiscal_year');
            this.fiscalYear = fy ? parseInt(fy, 10) : '';
            const mo = p.get('month');
            this.calendarMonth = mo ? parseInt(mo, 10) : '';
            this.sortOrder = p.get('sortOrder') || 'desc';
            const pp = p.get('per_page');
            if (pp) {
                const n = parseInt(pp, 10);
                if (!Number.isNaN(n) && n > 0) {
                    this.perPage = Math.min(200, n);
                }
            }
        },
        buildParams(page) {
            const state = { dateFrom: this.dateFrom, dateTo: this.dateTo };
            normalizeDateRangeFields(state);
            this.dateFrom = state.dateFrom;
            this.dateTo = state.dateTo;

            const params = {
                page,
                per_page: this.perPage,
                sortOrder: this.sortOrder,
            };
            if (this.searchQuery) {
                params.searchQuery = this.searchQuery;
            }
            params.filterClient = this.clientId == null ? 'All' : this.clientId;
            if (this.filterWarehouse) {
                params.filterWarehouse = this.filterWarehouse;
            }
            if (this.filterCostType !== '' && this.filterCostType != null) {
                params.filterCostType = this.filterCostType;
            }
            if (this.filterBillType !== '' && this.filterBillType != null) {
                params.filterBillType = this.filterBillType;
            }
            if (this.dateFrom) {
                params.date_from = this.dateFrom;
            }
            if (this.dateTo) {
                params.date_to = this.dateTo;
            }
            if (this.fiscalYear !== '' && this.fiscalYear != null) {
                params.fiscal_year = this.fiscalYear;
            }
            if (this.calendarMonth !== '' && this.calendarMonth != null) {
                params.month = this.calendarMonth;
            }
            return params;
        },
        buildHistoryQuery(page) {
            const p = this.buildParams(page);
            const parts = [];
            if (p.searchQuery) {
                parts.push(`searchQuery=${encodeURIComponent(p.searchQuery)}`);
            }
            if (p.filterClient && p.filterClient !== 'All') {
                parts.push(`filterClient=${p.filterClient}`);
            }
            if (p.filterWarehouse) {
                parts.push(`filterWarehouse=${encodeURIComponent(p.filterWarehouse)}`);
            }
            if (p.filterCostType !== undefined && p.filterCostType !== '') {
                parts.push(`filterCostType=${encodeURIComponent(p.filterCostType)}`);
            }
            if (p.filterBillType !== undefined && p.filterBillType !== '') {
                parts.push(`filterBillType=${encodeURIComponent(p.filterBillType)}`);
            }
            if (p.date_from) {
                parts.push(`date_from=${p.date_from}`);
            }
            if (p.date_to) {
                parts.push(`date_to=${p.date_to}`);
            }
            if (p.fiscal_year != null && p.fiscal_year !== '') {
                parts.push(`fiscal_year=${p.fiscal_year}`);
            }
            if (p.month != null && p.month !== '') {
                parts.push(`month=${p.month}`);
            }
            parts.push(`sortOrder=${this.sortOrder}`);
            parts.push(`per_page=${this.perPage}`);
            parts.push(`page=${page}`);
            return parts.length ? `?${parts.join('&')}` : '';
        },
        async fetchList(page = 1) {
            try {
                this.loading = true;
                const { data } = await axios.get('/incomingInvoice', {
                    params: this.buildParams(page),
                });
                this.localIncoming = data;
                window.history.pushState({}, '', `/incomingInvoice${this.buildHistoryQuery(page)}`);
            } catch (e) {
                console.error(e);
            } finally {
                this.loading = false;
            }
        },
        goToPage(page) {
            this.fetchList(page);
        },
        onSearchSubmit() {
            this.searchQuery = (this.searchInput || '').trim();
            this.fetchList(1);
        },
        onDateRangeChange() {
            const state = { dateFrom: this.dateFrom, dateTo: this.dateTo };
            normalizeDateRangeFields(state);
            this.dateFrom = state.dateFrom;
            this.dateTo = state.dateTo;
            this.fetchList(1);
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
            this.fetchList(1);
        },
        onClearDates() {
            this.dateFrom = '';
            this.dateTo = '';
            this.fetchList(1);
        },
        handleInvoiceAdded() {
            this.fetchList(1);
        },
        handleInvoiceUpdated() {
            this.fetchList(this.displayIncoming.current_page || 1);
        },
        calculateTotal(faktura) {
            const amount = parseFloat(String(faktura.amount).replace(/,/g, ''));
            const tax = parseFloat(String(faktura.tax).replace(/,/g, ''));
            return (amount + tax).toFixed(2);
        },
        formatInvoicePeriod(date) {
            return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short' });
        },
        formatFullDate(date) {
            if (!date) {
                return 'N/A';
            }
            return new Date(date).toLocaleDateString('en-US', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
            });
        },
    },
};
</script>
<style scoped lang="scss">
.dark-gray {
    background-color: $dark-gray;
    justify-content: left;
    border-radius: 8px;
    align-items: center;
    min-height: 20vh;
    width: 100%;
}

.toolbar-inline {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 10px 12px;
}

/* Flex toolbar: one horizontal flow; min-widths stop date/year controls from stacking */
.filter-toolbar-incoming {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 10px 12px;
    flex: 1 1 auto;
    min-width: 0;
}

.filter-toolbar-incoming .incoming-field--search {
    flex: 0 1 170px;
    min-width: 140px;
    max-width: 220px;
}

.filter-toolbar-incoming .incoming-field--sort {
    flex: 0 0 130px;
    min-width: 120px;
}

.filter-toolbar-incoming .incoming-field--client {
    flex: 0 1 200px;
    min-width: 160px;
}

.filter-toolbar-incoming .filter-col.incoming-field {
    flex: 0 1 130px;
    min-width: 110px;
}

.filter-toolbar-incoming .incoming-field--dates {
    flex: 1 1 280px;
    min-width: 260px;
    max-width: 340px;
}

.filter-toolbar-incoming .incoming-field--ym {
    flex: 0 1 260px;
    min-width: 240px;
}

.presets-inline {
    flex: 0 0 auto;
    align-self: flex-end;
}

@media (max-width: 1100px) {
    .filter-toolbar-incoming .incoming-field--dates {
        flex-basis: 100%;
        max-width: none;
    }

    .filter-toolbar-incoming .incoming-field--ym {
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

.filter-select-compact {
    width: 100%;
    min-height: 34px;
    border-radius: 8px;
    padding: 0 8px;
    font-size: 13px;
}

.filter-col {
    min-width: 0;
}

.toolbar-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.loading-inline {
    padding: 16px;
    text-align: center;
    color: rgba(255, 255, 255, 0.85);
}

/* Fixed layout, balanced widths; Actions column uses same cell styling as DataTableShell (no sticky panel) */
.incoming-table-shell {
    width: 100%;
    min-width: 0;
}

.incoming-table-shell :deep(.data-table) {
    table-layout: fixed;
    width: 100%;
}

.incoming-table-shell :deep(.data-table-head th),
.incoming-table-shell :deep(.data-table-body td) {
    padding-left: 10px;
    padding-right: 10px;
}

/* Single-line headers — avoids “WAREHOUS / E” wrapping */
.incoming-table-shell :deep(.data-table-head th) {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.2;
    vertical-align: bottom;
}

/* Column widths (sum 100%) */
.incoming-table-shell :deep(th.invoice-column),
.incoming-table-shell :deep(td.invoice-primary-cell) {
    width: 8%;
    min-width: 0;
}

.incoming-table-shell :deep(th.customer-column),
.incoming-table-shell :deep(td.customer-column) {
    width: 18%;
    min-width: 0;
}

.incoming-table-shell :deep(th.col-wh),
.incoming-table-shell :deep(td.col-wh) {
    width: 11%;
    min-width: 0;
}

.incoming-table-shell :deep(th.col-cost),
.incoming-table-shell :deep(td.col-cost) {
    width: 9%;
    min-width: 0;
}

.incoming-table-shell :deep(th.col-bill),
.incoming-table-shell :deep(td.col-bill) {
    width: 9%;
    min-width: 0;
}

.incoming-table-shell :deep(th.col-num),
.incoming-table-shell :deep(td.col-num) {
    width: 6%;
    min-width: 0;
}

.incoming-table-shell :deep(th.col-date),
.incoming-table-shell :deep(td.col-date) {
    width: 8%;
    min-width: 0;
}

.incoming-table-shell :deep(th.col-comment),
.incoming-table-shell :deep(td.col-comment) {
    width: 9%;
    min-width: 0;
}

.incoming-table-shell :deep(th.actions-column),
.incoming-table-shell :deep(td.actions-cell) {
    width: 10%;
    min-width: 72px;
    max-width: 104px;
    vertical-align: middle;
}

.incoming-table-shell :deep(.finance-client-name) {
    max-width: 100%;
}

.invoice-primary-cell {
    min-width: 0;
}

.invoice-main {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 2px;
    flex-wrap: wrap;
}

.cell-primary {
    font-weight: 700;
    color: $white;
}

.cell-secondary {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.68);
}

.archive-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 8px;
    border-radius: 999px;
    background: rgba(59, 130, 246, 0.16);
    border: 1px solid rgba(96, 165, 250, 0.36);
    color: $white;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.comment-cell {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-width: 0;
    white-space: normal;
    word-break: break-word;
}

.incoming-ellipsis {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    min-width: 0;
}

/* Cyrillic / longer labels: 2 lines instead of harsh mid-word ellipsis */
.incoming-text-wrap {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    word-break: break-word;
    line-height: 1.35;
    min-width: 0;
}

.actions-cell {
    text-align: right;
}

.text-right {
    text-align: right;
}

.empty-cell {
    padding: 34px 16px !important;
    text-align: center;
    color: rgba(255, 255, 255, 0.7);
}
</style>

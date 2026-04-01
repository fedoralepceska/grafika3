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

                    <div v-else class="incoming-table-block">
                    <div
                        ref="incomingTableScroll"
                        class="incoming-table-shell finance-table-scroll"
                        @scroll.passive="onIncomingTableScroll"
                    >
                    <DataTableShell compact variant="grid">
                        <template #colgroup>
                            <colgroup>
                                <col class="inc-col-inv" />
                                <col class="inc-col-date" />
                                <col class="inc-col-client" />
                                <col class="inc-col-num" />
                                <col class="inc-col-num" />
                                <col class="inc-col-num" />
                                <col class="inc-col-wh" />
                                <col class="inc-col-cost" />
                                <col class="inc-col-bill" />
                                <col class="inc-col-comment" />
                                <col class="inc-col-actions" />
                            </colgroup>
                        </template>
                        <template #header>
                            <tr>
                                <th class="invoice-column">{{ $t('financeTable.invoice') }}</th>
                                <th class="col-date">{{ $t('financeTable.date') }}</th>
                                <th class="customer-column">{{ $t('financeTable.client') }}</th>
                                <th class="col-num">{{ $t('financeTable.amount') }}</th>
                                <th class="col-num">{{ $t('financeTable.tax') }}</th>
                                <th class="col-num">{{ $t('financeTable.total') }}</th>
                                <th class="col-wh" :title="$t('financeTable.warehouse')">{{ $t('financeTable.warehouse') }}</th>
                                <th class="col-cost" :title="$t('financeTable.costType')">{{ $t('financeTable.cost') }}</th>
                                <th class="col-bill" :title="$t('financeTable.billType')">{{ $t('financeTable.bill') }}</th>
                                <th class="col-comment">{{ $t('financeTable.comment') }}</th>
                                <th class="actions-column actions-header">{{ $t('financeTable.actions') }}</th>
                            </tr>
                        </template>

                        <template v-if="displayIncoming.data && displayIncoming.data.length > 0">
                            <tr v-for="faktura in displayIncoming.data" :key="faktura.id">
                                <td
                                    class="invoice-primary-cell"
                                    :class="{ 'invoice-primary-cell--archived': faktura.billing_type === 'фактура' }"
                                    :title="faktura.billing_type === 'фактура' ? `Archive #${faktura.faktura_counter}` : undefined"
                                >
                                    <div class="invoice-primary-stack">
                                        <div class="cell-primary">#{{ faktura.incoming_number }}</div>
                                        <div
                                            v-if="faktura.billing_type === 'фактура'"
                                            class="invoice-archive-reveal"
                                        >
                                            <span class="invoice-archive-badge">Archive #{{ faktura.faktura_counter }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="col-date"><div class="cell-secondary incoming-ellipsis">{{ formatFullDate(faktura.date) }}</div></td>
                                <td class="customer-column">
                                    <FinanceClientNameCell :name="faktura.client_name || ''" />
                                </td>
                                <td class="col-num" :title="String(faktura.amount)">
                                    <div class="cell-secondary text-right finance-money">{{ faktura.amount }}</div>
                                </td>
                                <td class="col-num" :title="String(faktura.tax)">
                                    <div class="cell-secondary text-right finance-money">{{ faktura.tax }}</div>
                                </td>
                                <td class="col-num" :title="String(calculateTotal(faktura))">
                                    <div class="cell-primary text-right finance-money">{{ calculateTotal(faktura) }}</div>
                                </td>
                                <td class="col-wh"><div class="cell-secondary incoming-ellipsis" :title="faktura.warehouse || ''">{{ faktura.warehouse || 'N/A' }}</div></td>
                                <td class="col-cost"><div class="cell-secondary incoming-text-wrap" :title="faktura.cost_type || ''">{{ faktura.cost_type || 'N/A' }}</div></td>
                                <td class="col-bill"><div class="cell-secondary incoming-text-wrap" :title="faktura.billing_type || ''">{{ faktura.billing_type || 'N/A' }}</div></td>
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
                    <div v-if="loadingMore" class="finance-scroll-loading" aria-live="polite">
                        <i class="fa fa-spinner fa-spin" /> Loading more…
                    </div>
                    </div>
                    
                    <div
                        v-if="showIncomingSubtotal"
                        class="incoming-subtotal-sticky"
                        :style="{ paddingInlineEnd: subtotalScrollbarPadPx + 'px' }"
                    >
                        <table class="data-table finance-subtotal-table" title="Totals for all rows matching the current filters">
                            <colgroup>
                                <col class="inc-col-inv" />
                                <col class="inc-col-date" />
                                <col class="inc-col-client" />
                                <col class="inc-col-num" />
                                <col class="inc-col-num" />
                                <col class="inc-col-num" />
                                <col class="inc-col-wh" />
                                <col class="inc-col-cost" />
                                <col class="inc-col-bill" />
                                <col class="inc-col-comment" />
                                <col class="inc-col-actions" />
                            </colgroup>
                            <tbody>
                                <tr class="incoming-subtotal-row">
                                    <td colspan="3" class="incoming-subtotal-label">
                                        <span class="incoming-subtotal-label-text">Subtotal</span>
                                        <span class="incoming-subtotal-hint">{{ incomingSubtotalRowCount }} {{ incomingSubtotalCountLabel }}</span>
                                    </td>
                                    <td class="col-num incoming-subtotal-num" :title="incomingSubtotalTotals.amount">
                                        <div class="incoming-subtotal-value text-right finance-money">{{ incomingSubtotalTotals.amount }}</div>
                                    </td>
                                    <td class="col-num incoming-subtotal-num" :title="incomingSubtotalTotals.tax">
                                        <div class="incoming-subtotal-value text-right finance-money">{{ incomingSubtotalTotals.tax }}</div>
                                    </td>
                                    <td class="col-num incoming-subtotal-num incoming-subtotal-num--total" :title="incomingSubtotalTotals.total">
                                        <div class="incoming-subtotal-value incoming-subtotal-value--emphasis text-right finance-money">{{ incomingSubtotalTotals.total }}</div>
                                    </td>
                                    <td class="col-wh incoming-subtotal-rest">&nbsp;</td>
                                    <td class="col-cost incoming-subtotal-rest">&nbsp;</td>
                                    <td class="col-bill incoming-subtotal-rest">&nbsp;</td>
                                    <td class="col-comment incoming-subtotal-rest">&nbsp;</td>
                                    <td class="actions-cell incoming-subtotal-rest">&nbsp;</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    </div>
                </div>
        </div>
        </div>
    </MainLayout>
</template>

<script>
import MainLayout from '@/Layouts/MainLayout.vue';
import Header from '@/Components/Header.vue';
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
import { normalizeDateRangeFields, monthBoundsLocalISO } from '@/utils/financeFilters';
import { measureVerticalScrollbarWidth } from '@/utils/financeTableScrollbar';

export default {
    components: {
        Header,
        MainLayout,
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
        filter_totals: {
            type: Object,
            default: null,
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
            chunkSize: 10,
            localIncoming: null,
            loading: false,
            loadingMore: false,
            incomingScrollFillDepth: 0,
            subtotalScrollbarPadPx: 0,
            filterTotalsSnapshot: null,
        };
    },
    computed: {
        displayIncoming() {
            return this.localIncoming !== null ? this.localIncoming : this.incomingInvoice;
        },
        effectiveIncomingFilterTotals() {
            if (this.filterTotalsSnapshot && typeof this.filterTotalsSnapshot.row_count === 'number') {
                return this.filterTotalsSnapshot;
            }
            return this.filter_totals;
        },
        incomingSubtotalTotals() {
            const ft = this.effectiveIncomingFilterTotals;
            if (ft && typeof ft.amount === 'string') {
                return { amount: ft.amount, tax: ft.tax, total: ft.total };
            }
            const rows = this.displayIncoming?.data ?? [];
            let amountSum = 0;
            let taxSum = 0;
            let totalSum = 0;
            for (const f of rows) {
                const amount = parseFloat(String(f.amount ?? 0).replace(/,/g, '')) || 0;
                const tax = parseFloat(String(f.tax ?? 0).replace(/,/g, '')) || 0;
                amountSum += amount;
                taxSum += tax;
                totalSum += amount + tax;
            }
            return {
                amount: amountSum.toFixed(2),
                tax: taxSum.toFixed(2),
                total: totalSum.toFixed(2),
            };
        },
        hasIncomingFetchedRows() {
            return (this.displayIncoming?.data?.length ?? 0) > 0;
        },
        showIncomingSubtotal() {
            const n = this.effectiveIncomingFilterTotals?.row_count;
            if (typeof n === 'number') {
                return n > 0;
            }
            return this.hasIncomingFetchedRows;
        },
        incomingSubtotalRowCount() {
            const ft = this.effectiveIncomingFilterTotals;
            if (ft && typeof ft.row_count === 'number') {
                return ft.row_count;
            }
            return this.displayIncoming?.data?.length ?? 0;
        },
        incomingSubtotalCountLabel() {
            const ft = this.effectiveIncomingFilterTotals;
            if (ft && typeof ft.row_count === 'number' && ft.row_count !== 1) {
                return 'invoices';
            }
            if (ft && ft.row_count === 1) {
                return 'invoice';
            }
            return 'fetched';
        },
    },
    watch: {
        loading(val) {
            if (!val) {
                this.$nextTick(() => this.syncIncomingSubtotalScrollbarPad());
            }
        },
    },
    mounted() {
        this.initFromUrl();
        this.filterTotalsSnapshot = this.filter_totals || null;
        this.fetchList(1);
    },
    beforeUnmount() {
        if (this._incomingSubtotalWindowResize) {
            window.removeEventListener('resize', this._incomingSubtotalWindowResize);
        }
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
            const noDateFilter = p.get('no_date_filter') === '1';
            if (!noDateFilter && !this.dateFrom && !this.dateTo) {
                const bounds = monthBoundsLocalISO(new Date());
                this.dateFrom = bounds.dateFrom;
                this.dateTo = bounds.dateTo;
            }
            const fy = p.get('fiscal_year');
            this.fiscalYear = fy ? parseInt(fy, 10) : '';
            const mo = p.get('month');
            this.calendarMonth = mo ? parseInt(mo, 10) : '';
            this.sortOrder = p.get('sortOrder') || 'desc';
        },
        buildParams(page) {
            const state = { dateFrom: this.dateFrom, dateTo: this.dateTo };
            normalizeDateRangeFields(state);
            this.dateFrom = state.dateFrom;
            this.dateTo = state.dateTo;

            const params = {
                page,
                per_page: this.chunkSize,
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
            if (!this.dateFrom && !this.dateTo) {
                params.no_date_filter = 1;
            }
            return params;
        },
        buildHistoryQuery() {
            const p = this.buildParams(1);
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
            if (!this.dateFrom && !this.dateTo) {
                parts.push('no_date_filter=1');
            }
            return parts.length ? `?${parts.join('&')}` : '';
        },
        async fetchList(page = 1, { append = false } = {}) {
            if (!append) {
                this.incomingScrollFillDepth = 0;
            }
            try {
                if (append) {
                    this.loadingMore = true;
                } else {
                    this.loading = true;
                }
                const { data } = await axios.get('/incomingInvoice', {
                    params: this.buildParams(page),
                });
                if (append && this.localIncoming && Array.isArray(this.localIncoming.data)) {
                    this.localIncoming = {
                        ...data,
                        data: [...this.localIncoming.data, ...data.data],
                    };
                } else {
                    this.localIncoming = data;
                }
                if (data.filter_totals) {
                    this.filterTotalsSnapshot = data.filter_totals;
                }
                window.history.replaceState({}, '', `/incomingInvoice${this.buildHistoryQuery()}`);
            } catch (e) {
                console.error(e);
            } finally {
                this.loading = false;
                this.loadingMore = false;
                this.$nextTick(() => {
                    this.maybeFillIncomingScroll();
                    this.syncIncomingSubtotalScrollbarPad();
                });
            }
        },
        syncIncomingSubtotalScrollbarPad() {
            const el = this.$refs.incomingTableScroll;
            if (!el) {
                this.subtotalScrollbarPadPx = 0;
                return;
            }
            this.subtotalScrollbarPadPx = measureVerticalScrollbarWidth(el);
            if (!this._incomingSubtotalWindowResize) {
                this._incomingSubtotalWindowResize = () => this.syncIncomingSubtotalScrollbarPad();
                window.addEventListener('resize', this._incomingSubtotalWindowResize);
            }
        },
        onIncomingTableScroll(e) {
            const el = e.target;
            if (this.loading || this.loadingMore) {
                return;
            }
            const p = this.displayIncoming;
            if (!p || !p.data?.length || p.current_page >= p.last_page) {
                return;
            }
            const threshold = 100;
            if (el.scrollTop + el.clientHeight >= el.scrollHeight - threshold) {
                this.fetchList(p.current_page + 1, { append: true });
            }
        },
        maybeFillIncomingScroll() {
            const el = this.$refs.incomingTableScroll;
            if (!el || this.loading || this.loadingMore) {
                return;
            }
            const p = this.displayIncoming;
            if (!p || p.current_page >= p.last_page) {
                return;
            }
            if (this.incomingScrollFillDepth >= 50) {
                return;
            }
            if (el.scrollHeight <= el.clientHeight + 2) {
                this.incomingScrollFillDepth += 1;
                this.fetchList(p.current_page + 1, { append: true });
            }
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
            this.fetchList(1);
        },
        calculateTotal(faktura) {
            const amount = parseFloat(String(faktura.amount).replace(/,/g, ''));
            const tax = parseFloat(String(faktura.tax).replace(/,/g, ''));
            return (amount + tax).toFixed(2);
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

.incoming-table-block {
    width: 100%;
    min-width: 0;
    position: relative;
}

/* ~10 data rows visible; scroll for more (loads next page near bottom) */
.incoming-table-shell.finance-table-scroll {
    min-height: 0;
    max-height: calc(40px + 10 * 38px + 56px);
    overflow-y: auto;
    overflow-x: auto;
    border-radius: 12px;
}

.incoming-table-shell.finance-table-scroll :deep(.data-table-head th) {
    position: sticky;
    top: 0;
    z-index: 4;
    background: #171d24;
    background-clip: border-box;
    box-shadow: 0 1px 0 rgba(255, 255, 255, 0.12);
}

.finance-scroll-loading {
    padding: 8px 12px;
    text-align: center;
    font-size: 12px;
    color: rgba(255, 255, 255, 0.65);
    background: rgba(8, 15, 26, 0.9);
    border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.incoming-table-shell :deep(.data-table) {
    table-layout: fixed;
    width: 100%;
}

.incoming-table-shell.finance-table-scroll :deep(.data-table-shell) {
    min-width: 0;
    overflow: visible;
}
.incoming-table-shell.finance-table-scroll :deep(.table-scroll) {
    overflow: visible;
    min-width: 0;
}

/* Shared 11-column grid — Invoice, Date, Client, Amount, Tax, Total, Warehouse, Cost, Bill, Comment, Actions (sum 100%) */
.incoming-table-shell col.inc-col-inv,
.incoming-subtotal-sticky col.inc-col-inv {
    width: 7%;
}
.incoming-table-shell col.inc-col-date,
.incoming-subtotal-sticky col.inc-col-date {
    width: 7%;
}
.incoming-table-shell col.inc-col-client,
.incoming-subtotal-sticky col.inc-col-client {
    width: 9%;
}
.incoming-table-shell col.inc-col-num,
.incoming-subtotal-sticky col.inc-col-num {
    width: 11%;
}
.incoming-table-shell col.inc-col-wh,
.incoming-subtotal-sticky col.inc-col-wh {
    width: 8%;
}
.incoming-table-shell col.inc-col-cost,
.incoming-subtotal-sticky col.inc-col-cost {
    width: 7%;
}
.incoming-table-shell col.inc-col-bill,
.incoming-subtotal-sticky col.inc-col-bill {
    width: 7%;
}
.incoming-table-shell col.inc-col-comment,
.incoming-subtotal-sticky col.inc-col-comment {
    width: 11%;
}
.incoming-table-shell col.inc-col-actions,
.incoming-subtotal-sticky col.inc-col-actions {
    width: 11%;
    min-width: 72px;
}

.incoming-table-shell :deep(.data-table-head th),
.incoming-table-shell :deep(.data-table-body td) {
    padding-left: 10px;
    padding-right: 10px;
}

.incoming-subtotal-sticky .finance-subtotal-table td {
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

.incoming-table-shell :deep(th.actions-column),
.incoming-table-shell :deep(td.actions-cell) {
    max-width: 104px;
    vertical-align: middle;
}

/* Amount / Tax / Total — override DataTableShell default th alignment */
.incoming-table-shell :deep(.data-table-head th.col-num),
.incoming-table-shell :deep(.data-table-body td.col-num),
.incoming-subtotal-sticky .finance-subtotal-table td.col-num {
    text-align: right;
    font-variant-numeric: tabular-nums;
    min-width: 11rem;
    white-space: nowrap;
}

.incoming-table-shell .finance-money,
.incoming-subtotal-sticky .finance-money {
    display: block;
    overflow: visible;
    text-overflow: clip;
    white-space: nowrap;
}

.incoming-table-shell :deep(.finance-client-name) {
    max-width: 100%;
}

.invoice-primary-cell {
    min-width: 0;
}

.invoice-primary-stack {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 0;
    min-width: 0;
}

.cell-primary {
    font-weight: 700;
    color: $white;
}

.cell-secondary {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.68);
}

/* Archived invoice: accent rail + archive line only on hover */
.incoming-table-shell :deep(td.invoice-primary-cell--archived) {
    border-left: 3px solid rgba(96, 165, 250, 0.55) !important;
    transition:
        border-left-color 0.2s ease,
        background-color 0.2s ease;
}

.incoming-table-shell :deep(td.invoice-primary-cell--archived:hover) {
    border-left-color: rgba(147, 197, 253, 0.95) !important;
    background-color: rgba(59, 130, 246, 0.08) !important;
}

.invoice-archive-reveal {
    max-height: 0;
    opacity: 0;
    overflow: hidden;
    transition:
        max-height 0.22s ease,
        opacity 0.18s ease,
        margin-top 0.18s ease;
}

.incoming-table-shell :deep(td.invoice-primary-cell--archived:hover .invoice-archive-reveal) {
    max-height: 40px;
    opacity: 1;
    margin-top: 6px;
}

.invoice-archive-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 8px;
    border-radius: 999px;
    background: rgba(59, 130, 246, 0.22);
    border: 1px solid rgba(96, 165, 250, 0.45);
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

/* Subtotal strip — always visible at bottom, positioned outside scroll container */
.incoming-subtotal-sticky {
    position: sticky;
    bottom: 0;
    z-index: 10;
    box-sizing: border-box;
    width: 100%;
    min-width: 0;
    margin-top: -1px;
    border-radius: 0 0 12px 12px;
    overflow-x: auto;
    overflow-y: visible;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-top: none;
    background: linear-gradient(
        180deg,
        rgba(37, 99, 235, 0.24) 0%,
        rgba(15, 23, 42, 0.94) 100%
    );
    box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.3);
}

.incoming-subtotal-sticky .finance-subtotal-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    font-variant-numeric: tabular-nums;
}

.incoming-subtotal-sticky .finance-subtotal-table td {
    padding: 6px 10px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    font-size: 12px;
    line-height: 1.35;
    vertical-align: middle;
}

.incoming-subtotal-sticky tr.incoming-subtotal-row td {
    background: linear-gradient(
        180deg,
        rgba(37, 99, 235, 0.24) 0%,
        rgba(15, 23, 42, 0.94) 100%
    ) !important;
    border-color: rgba(147, 197, 253, 0.22) !important;
    border-top: 2px solid rgba(147, 197, 253, 0.7) !important;
    box-shadow: inset 0 1px 0 rgba(191, 219, 254, 0.14);
    padding-top: 9px !important;
    padding-bottom: 9px !important;
}

.incoming-subtotal-sticky tr.incoming-subtotal-row td.incoming-subtotal-num--total {
    box-shadow:
        inset 0 1px 0 rgba(191, 219, 254, 0.14),
        inset 1px 0 0 rgba(147, 197, 253, 0.4);
}

.incoming-subtotal-sticky .incoming-subtotal-label {
    text-align: left;
    vertical-align: middle;
}

.incoming-subtotal-sticky .incoming-subtotal-label-text {
    display: inline-block;
    margin-right: 8px;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: #e8f0fe;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.35);
}

.incoming-subtotal-sticky .incoming-subtotal-hint {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: lowercase;
    color: rgba(191, 219, 254, 0.95);
    background: rgba(15, 23, 42, 0.55);
    border: 1px solid rgba(96, 165, 250, 0.35);
}

.incoming-subtotal-sticky .incoming-subtotal-value {
    font-size: 13px;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    color: #f1f5f9;
}

.incoming-subtotal-sticky .incoming-subtotal-value--emphasis {
    font-size: 14px;
    font-weight: 800;
    color: #fff;
}
</style>

<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="invoice2" subtitle="invoicedOrders" icon="invoice.png" link="allInvoices" />
            <RedirectTabs :route="$page.url" />
            <div class="dark-gray p-2 text-white min-w-0">
                <div class="form-container p-2 min-w-0">
                    <div class="toolbar-panel">
                        <div class="toolbar-inline">
                            <div class="filter-grid">
                            <FinanceCompactSearch
                                v-model="searchInput"
                                label="Invoice #"
                                placeholder="# or number…"
                                @submit="onSearchSubmit"
                            />
                            <FinanceClientSearchSelect
                                v-model="clientId"
                                :clients="uniqueClients"
                                label="Client"
                                @change="applyFilter(1)"
                            />
                            <FinanceDateRangeCompact
                                :date-from="dateFrom"
                                :date-to="dateTo"
                                label="Date created"
                                @update:date-from="dateFrom = $event"
                                @update:date-to="dateTo = $event"
                                @change="onDateRangeChange"
                            />
                            <FinanceYearMonthSelects
                                :fiscal-year="fiscalYear"
                                :month="calendarMonth"
                                @update:fiscal-year="fiscalYear = $event"
                                @update:month="calendarMonth = $event"
                                @change="applyFilter(1)"
                            />
                            <div class="filter-col filter-col--sort">
                                <label class="toolbar-label">Order</label>
                                <select v-model="sortOrder" class="text-black filter-select-compact" @change="applyFilter(1)">
                                    <option value="desc">Newest first</option>
                                    <option value="asc">Oldest first</option>
                                </select>
                            </div>
                            </div>
                            <FinancePeriodPresets
                                class="presets-inline"
                                label=""
                                @preset="onPeriodPreset"
                                @clear-dates="onClearDates"
                            />
                        </div>
                    </div>

                    <div v-if="loading" class="loading-container">
                        <div class="loading-spinner">
                            <i class="fa fa-spinner fa-spin" />
                            <span>Loading invoices...</span>
                        </div>
                    </div>

                    <div v-else class="all-invoices-table-block">
                    <div
                        ref="allInvoicesTableScroll"
                        class="all-invoices-table-shell finance-table-scroll"
                        @scroll.passive="onAllInvoicesTableScroll"
                    >
                    <DataTableShell compact variant="grid">
                        <template #colgroup>
                            <colgroup>
                                <col class="ai-col-inv" />
                                <col class="ai-col-date" />
                                <col class="ai-col-client" />
                                <col class="ai-col-num" />
                                <col class="ai-col-num" />
                                <col class="ai-col-num" />
                                <col class="ai-col-by" />
                                <col class="ai-col-orders" />
                                <col class="ai-col-comment" />
                                <col class="ai-col-actions" />
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
                                <th class="col-created">{{ $t('financeTable.createdBy') }}</th>
                                <th class="col-orders-h">{{ $t('financeTable.orders') }}</th>
                                <th class="col-comment-h">{{ $t('financeTable.comment') }}</th>
                                <th class="actions-column">{{ $t('financeTable.actions') }}</th>
                            </tr>
                        </template>

                        <template v-if="hasInvoices">
                            <tr
                                v-for="faktura in displayFakturas.data"
                                :key="faktura.id"
                                class="invoice-row"
                                :class="{ 'invoice-row--active': selectedInvoice?.id === faktura.id }"
                                @click="openInvoiceDrawer(faktura)"
                            >
                                <td class="invoice-primary-cell">
                                    <div class="invoice-main">
                                        <div class="invoice-number">#{{ faktura.faktura_number || faktura.id }}</div>
                                        <span v-if="faktura.is_split_invoice" class="split-badge">Split</span>
                                    </div>
                                </td>
                                <td class="col-date-cell">
                                    <div class="cell-secondary">{{ formatFullDate(faktura.created_at) }}</div>
                                </td>
                                <td class="customer-column">
                                    <FinanceClientNameCell :name="getClientName(faktura)" />
                                </td>
                                <td class="col-num" :title="formatFakturaMoney(faktura.amount)">
                                    <div class="cell-secondary text-right finance-money">{{ formatFakturaMoney(faktura.amount) }}</div>
                                </td>
                                <td class="col-num" :title="formatFakturaMoney(faktura.tax)">
                                    <div class="cell-secondary text-right finance-money">{{ formatFakturaMoney(faktura.tax) }}</div>
                                </td>
                                <td class="col-num" :title="formatFakturaTotalCell(faktura)">
                                    <div class="cell-primary text-right finance-money">{{ formatFakturaTotalCell(faktura) }}</div>
                                </td>
                                <td class="col-created-cell">
                                    <div class="cell-secondary">{{ faktura.created_by?.name || 'N/A' }}</div>
                                </td>
                                <td>
                                    <div class="cell-secondary orders-cell">{{ getOrdersDisplay(faktura) }}</div>
                                </td>
                                <td>
                                    <div class="cell-secondary comment-cell">{{ getCommentDisplay(faktura.comment) }}</div>
                                </td>
                                <td class="actions-cell">
                                    <button class="view-button" @click.stop="viewInvoice(faktura.id)">
                                        <i class="fa fa-eye" aria-hidden="true" />
                                        <span>View</span>
                                    </button>
                                </td>
                            </tr>
                        </template>

                        <tr v-else>
                            <td colspan="10" class="empty-cell">
                                No invoices found matching your criteria.
                            </td>
                        </tr>
                    </DataTableShell>
                    <div v-if="loadingMore" class="finance-scroll-loading" aria-live="polite">
                        <i class="fa fa-spinner fa-spin" /> Loading more…
                    </div>
                    </div>

                    <div
                        v-if="showAllInvoicesSubtotal"
                        class="all-invoices-subtotal-sticky"
                        :style="{ paddingInlineEnd: subtotalScrollbarPadPx + 'px' }"
                    >
                        <table class="data-table finance-subtotal-table" title="Totals for all rows matching the current filters">
                            <colgroup>
                                <col class="ai-col-inv" />
                                <col class="ai-col-date" />
                                <col class="ai-col-client" />
                                <col class="ai-col-num" />
                                <col class="ai-col-num" />
                                <col class="ai-col-num" />
                                <col class="ai-col-by" />
                                <col class="ai-col-orders" />
                                <col class="ai-col-comment" />
                                <col class="ai-col-actions" />
                            </colgroup>
                            <tbody>
                                <tr class="all-invoices-subtotal-row">
                                    <td colspan="3" class="all-invoices-subtotal-label">
                                        <span class="all-invoices-subtotal-label-text">Subtotal</span>
                                        <span class="all-invoices-subtotal-hint">{{ allInvoicesSubtotalRowCount }} {{ allInvoicesSubtotalCountLabel }}</span>
                                    </td>
                                    <td class="col-num all-invoices-subtotal-num" :title="allInvoicesSubtotalTotals.amount">
                                        <div class="all-invoices-subtotal-value text-right finance-money">{{ allInvoicesSubtotalTotals.amount }}</div>
                                    </td>
                                    <td class="col-num all-invoices-subtotal-num" :title="allInvoicesSubtotalTotals.tax">
                                        <div class="all-invoices-subtotal-value text-right finance-money">{{ allInvoicesSubtotalTotals.tax }}</div>
                                    </td>
                                    <td class="col-num all-invoices-subtotal-num all-invoices-subtotal-num--total" :title="allInvoicesSubtotalTotals.total">
                                        <div class="all-invoices-subtotal-value all-invoices-subtotal-value--emphasis text-right finance-money">{{ allInvoicesSubtotalTotals.total }}</div>
                                    </td>
                                    <td class="col-created-cell all-invoices-subtotal-rest">&nbsp;</td>
                                    <td class="col-orders-h all-invoices-subtotal-rest">&nbsp;</td>
                                    <td class="col-comment-h all-invoices-subtotal-rest">&nbsp;</td>
                                    <td class="actions-cell all-invoices-subtotal-rest">&nbsp;</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    </div>
                </div>
        </div>
        </div>

        <Teleport to="body">
            <div v-if="invoiceDrawerOpen && selectedInvoice" class="invoice-drawer-portal" role="presentation">
                <div class="invoice-drawer-scrim" aria-hidden="true" @click="closeInvoiceDrawer" />
                <aside
                    class="invoice-drawer-panel"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="invoice-drawer-title"
                    @click.stop
                >
                    <header class="invoice-drawer-head">
                        <div class="invoice-drawer-head-text">
                            <h2 id="invoice-drawer-title" class="invoice-drawer-title">
                                Invoice #{{ selectedInvoice.faktura_number || selectedInvoice.id }}/{{ selectedInvoice.fiscal_year || '—' }}
                            </h2>
                            <p class="invoice-drawer-subtitle">
                                {{ getClientName(selectedInvoice) }}
                            </p>
                        </div>
                        <div class="invoice-drawer-head-actions">
                            <button
                                v-if="selectedInvoice.is_split_invoice"
                                type="button"
                                class="invoice-drawer-chip"
                            >
                                Split invoice
                            </button>
                            <button type="button" class="invoice-drawer-close" aria-label="Close drawer" @click="closeInvoiceDrawer">
                                &times;
                            </button>
                        </div>
                    </header>

                    <div class="invoice-drawer-scroll">
                        <section class="invoice-drawer-meta-grid">
                            <div class="invoice-meta-card">
                                <span class="invoice-meta-label">Created date</span>
                                <span class="invoice-meta-value">{{ formatFullDate(selectedInvoice.created_at) || '—' }}</span>
                            </div>
                            <div class="invoice-meta-card">
                                <span class="invoice-meta-label">Created by</span>
                                <span class="invoice-meta-value">{{ selectedInvoice.created_by?.name || 'N/A' }}</span>
                            </div>
                            <div class="invoice-meta-card">
                                <span class="invoice-meta-label">Amount</span>
                                <span class="invoice-meta-value finance-money">{{ formatFakturaMoney(selectedInvoice.amount) }}</span>
                            </div>
                            <div class="invoice-meta-card">
                                <span class="invoice-meta-label">Tax</span>
                                <span class="invoice-meta-value finance-money">{{ formatFakturaMoney(selectedInvoice.tax) }}</span>
                            </div>
                            <div class="invoice-meta-card invoice-meta-card--emphasis">
                                <span class="invoice-meta-label">Total</span>
                                <span class="invoice-meta-value finance-money">{{ formatFakturaTotalCell(selectedInvoice) }}</span>
                            </div>
                        </section>

                        <section class="invoice-drawer-section">
                            <div class="invoice-drawer-section-head">
                                <h3 class="invoice-drawer-section-title">Orders</h3>
                                <span class="invoice-drawer-section-hint">{{ getInvoiceOrders(selectedInvoice).length }} linked</span>
                            </div>

                            <div v-if="!getInvoiceOrders(selectedInvoice).length" class="invoice-drawer-empty">
                                No linked orders.
                            </div>

                            <article
                                v-for="order in getInvoiceOrders(selectedInvoice)"
                                :key="`drawer-order-${order.id}`"
                                class="invoice-order-card"
                            >
                                <div class="invoice-order-separator">
                                    <span class="separator-line" />
                                    <span class="separator-text">Order {{ order.order_number || order.id }}</span>
                                    <span class="separator-line" />
                                </div>
                                <header class="invoice-order-head">
                                    <div class="invoice-order-title-wrap">
                                        <h4 class="invoice-order-title">{{ order.invoice_title || 'Untitled order' }}</h4>
                                        <p class="invoice-order-subtitle">
                                            {{ formatFullDate(order.start_date) || '—' }} - {{ formatFullDate(order.end_date) || '—' }}
                                        </p>
                                        <p v-if="getOrderMergeGroups(order).length" class="invoice-order-grouping">
                                            {{ getOrderMergeGroups(order).length }} grouped block{{ getOrderMergeGroups(order).length === 1 ? '' : 's' }}
                                        </p>
                                    </div>
                                    <div class="invoice-order-head-right">
                                        <span class="invoice-order-meta">{{ order.client?.name || getClientName(selectedInvoice) }}</span>
                                        <span class="invoice-order-meta">{{ order.user?.name || 'N/A' }}</span>
                                    </div>
                                </header>

                                <div class="invoice-order-jobs">
                                    <div
                                        v-for="group in getOrderGroupedJobs(order)"
                                        :key="`drawer-group-${order.id}-${group.index}`"
                                        class="invoice-merge-group"
                                        :class="group.colorClass"
                                    >
                                        <div class="invoice-merge-group-head">
                                            <span class="invoice-merge-badge">{{ group.label }}</span>
                                            <span class="invoice-merge-stats">
                                                {{ group.jobCount }} jobs · Qty {{ group.totalQty }} · {{ formatArea(group.totalArea) }} m²
                                            </span>
                                        </div>
                                        <div class="invoice-merge-group-body">
                                            <div
                                                v-for="job in group.jobs"
                                                :key="`drawer-group-job-${job.id}`"
                                                class="invoice-job-row invoice-job-row--inside-group"
                                            >
                                                <div class="invoice-job-main">
                                                    <span class="invoice-job-name">{{ job.name || `Job #${job.id}` }}</span>
                                                    <span class="invoice-job-meta">Qty: {{ job.quantity ?? '—' }} {{ job.unit || '' }}</span>
                                                </div>
                                                <div class="invoice-job-side">
                                                    <span class="invoice-job-meta">m²: {{ formatArea(job.computed_total_area_m2) }}</span>
                                                    <span class="invoice-job-meta">VAT: {{ formatVatDisplay(job) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        v-if="getOrderGroupedJobs(order).length && getOrderUngroupedJobs(order).length"
                                        class="invoice-ungrouped-label"
                                    >
                                        Other jobs
                                    </div>

                                    <div
                                        v-for="job in getOrderUngroupedJobs(order)"
                                        :key="`drawer-job-${job.id}`"
                                        class="invoice-job-row"
                                    >
                                        <div class="invoice-job-main">
                                            <span class="invoice-job-name">{{ job.name || `Job #${job.id}` }}</span>
                                            <span class="invoice-job-meta">Qty: {{ job.quantity ?? '—' }} {{ job.unit || '' }}</span>
                                        </div>
                                        <div class="invoice-job-side">
                                            <span class="invoice-job-meta">m²: {{ formatArea(job.computed_total_area_m2) }}</span>
                                            <span class="invoice-job-meta">VAT: {{ formatVatDisplay(job) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        </section>

                        <section class="invoice-drawer-section" v-if="selectedInvoice.comment">
                            <h3 class="invoice-drawer-section-title">Comment</h3>
                            <p class="invoice-drawer-comment">{{ selectedInvoice.comment }}</p>
                        </section>
                    </div>

                    <footer class="invoice-drawer-foot">
                        <button type="button" class="invoice-drawer-btn invoice-drawer-btn--muted" @click="closeInvoiceDrawer">
                            Close
                        </button>
                        <button type="button" class="invoice-drawer-btn invoice-drawer-btn--primary" @click="viewInvoice(selectedInvoice.id)">
                            Open full invoice
                        </button>
                    </footer>
                </aside>
            </div>
        </Teleport>
    </MainLayout>
</template>

<script>
import MainLayout from '@/Layouts/MainLayout.vue';
import Header from '@/Components/Header.vue';
import DataTableShell from '@/Components/DataTableShell.vue';
import RedirectTabs from '@/Components/RedirectTabs.vue';
import FinanceCompactSearch from '@/Components/Finance/FinanceCompactSearch.vue';
import FinanceClientSearchSelect from '@/Components/Finance/FinanceClientSearchSelect.vue';
import FinanceDateRangeCompact from '@/Components/Finance/FinanceDateRangeCompact.vue';
import FinanceYearMonthSelects from '@/Components/Finance/FinanceYearMonthSelects.vue';
import FinancePeriodPresets from '@/Components/Finance/FinancePeriodPresets.vue';
import FinanceClientNameCell from '@/Components/Finance/FinanceClientNameCell.vue';
import axios from 'axios';
import { measureVerticalScrollbarWidth } from '@/utils/financeTableScrollbar';
import { monthBoundsLocalISO, formatDateDdMmYyyy } from '@/utils/financeFilters';

function localISODate(d = new Date()) {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
}

function firstDayOfMonthFromDateString(dateStr) {
    if (!dateStr || dateStr.length < 7) {
        return '';
    }
    return `${dateStr.slice(0, 7)}-01`;
}

export default {
    components: {
        Header,
        MainLayout,
        DataTableShell,
        RedirectTabs,
        FinanceCompactSearch,
        FinanceClientSearchSelect,
        FinanceDateRangeCompact,
        FinanceYearMonthSelects,
        FinancePeriodPresets,
        FinanceClientNameCell,
    },
    props: {
        fakturas: Object,
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
            dateFrom: '',
            dateTo: '',
            fiscalYear: '',
            calendarMonth: '',
            sortOrder: 'desc',
            uniqueClients: [],
            localFakturas: null,
            loading: false,
            loadingMore: false,
            chunkSize: 10,
            allInvoicesScrollFillDepth: 0,
            subtotalScrollbarPadPx: 0,
            filterTotalsSnapshot: null,
            invoiceDrawerOpen: false,
            selectedInvoice: null,
        };
    },
    computed: {
        hasInvoices() {
            return this.localFakturas && this.localFakturas.data && this.localFakturas.data.length > 0;
        },
        displayFakturas() {
            return this.localFakturas || this.fakturas;
        },
        effectiveFilterTotals() {
            if (this.filterTotalsSnapshot && typeof this.filterTotalsSnapshot.row_count === 'number') {
                return this.filterTotalsSnapshot;
            }
            return this.filter_totals;
        },
        allInvoicesSubtotalTotals() {
            const ft = this.effectiveFilterTotals;
            const rows = this.displayFakturas?.data ?? [];
            const rowCount = ft?.row_count;
            /** Every matching invoice is on this page — sum row cells so subtotal matches the table (avoids stale cached filter_totals). */
            const fullFilteredSetLoaded =
                typeof rowCount === 'number' && rowCount > 0 && rows.length === rowCount;

            let amountSum = 0;
            let taxSum = 0;
            for (const f of rows) {
                amountSum += parseFloat(String(f.amount ?? 0).replace(/,/g, '')) || 0;
                taxSum += parseFloat(String(f.tax ?? 0).replace(/,/g, '')) || 0;
            }
            const totalSum = amountSum + taxSum;

            if (fullFilteredSetLoaded) {
                return {
                    amount: this.formatFinanceSubtotal(amountSum),
                    tax: this.formatFinanceSubtotal(taxSum),
                    total: this.formatFinanceSubtotal(totalSum),
                };
            }

            if (ft && typeof ft.amount === 'string') {
                return { amount: ft.amount, tax: ft.tax, total: ft.total };
            }

            return {
                amount: this.formatFinanceSubtotal(amountSum),
                tax: this.formatFinanceSubtotal(taxSum),
                total: this.formatFinanceSubtotal(totalSum),
            };
        },
        hasAllInvoicesFetchedRows() {
            return (this.displayFakturas?.data?.length ?? 0) > 0;
        },
        showAllInvoicesSubtotal() {
            const n = this.effectiveFilterTotals?.row_count;
            if (typeof n === 'number') {
                return n > 0;
            }
            return this.hasAllInvoicesFetchedRows;
        },
        allInvoicesSubtotalRowCount() {
            const ft = this.effectiveFilterTotals;
            if (ft && typeof ft.row_count === 'number') {
                return ft.row_count;
            }
            return this.displayFakturas?.data?.length ?? 0;
        },
        allInvoicesSubtotalCountLabel() {
            const ft = this.effectiveFilterTotals;
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
                this.$nextTick(() => this.syncAllInvoicesSubtotalScrollbarPad());
            }
        },
        invoiceDrawerOpen(open) {
            if (open) {
                document.body.style.overflow = 'hidden';
                window.addEventListener('keydown', this.onInvoiceDrawerEscape, true);
            } else {
                document.body.style.overflow = '';
                window.removeEventListener('keydown', this.onInvoiceDrawerEscape, true);
            }
        },
    },
    mounted() {
        this.fetchUniqueClients();
        this.initFromUrl();
        this.filterTotalsSnapshot = this.filter_totals || null;
        this.applyFilter(1);
    },
    beforeUnmount() {
        document.body.style.overflow = '';
        window.removeEventListener('keydown', this.onInvoiceDrawerEscape, true);
        if (this._allInvoicesSubtotalWindowResize) {
            window.removeEventListener('resize', this._allInvoicesSubtotalWindowResize);
        }
    },
    methods: {
        onInvoiceDrawerEscape(e) {
            if (e.key === 'Escape' && this.invoiceDrawerOpen) {
                this.closeInvoiceDrawer();
            }
        },
        openInvoiceDrawer(faktura) {
            this.selectedInvoice = faktura;
            this.invoiceDrawerOpen = true;
        },
        closeInvoiceDrawer() {
            this.invoiceDrawerOpen = false;
        },
        initFromUrl() {
            const p = new URLSearchParams(window.location.search);
            const sq = p.get('searchQuery') || '';
            this.searchInput = sq;
            this.searchQuery = sq;
            const cid = p.get('client_id');
            this.clientId = cid ? parseInt(cid, 10) : null;
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
        normalizeDates() {
            const today = localISODate();
            if (this.dateFrom && !this.dateTo) {
                this.dateTo = today;
            }
            if (this.dateTo && !this.dateFrom) {
                this.dateFrom = firstDayOfMonthFromDateString(this.dateTo);
            }
        },
        buildRequestParams(page) {
            this.normalizeDates();
            const params = {
                page,
                per_page: this.chunkSize,
                sortOrder: this.sortOrder,
            };
            if (this.searchQuery) {
                params.searchQuery = this.searchQuery;
            }
            if (this.clientId) {
                params.client_id = this.clientId;
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
        buildHistoryQueryString() {
            const parts = [];
            if (this.searchQuery) {
                parts.push(`searchQuery=${encodeURIComponent(this.searchQuery)}`);
            }
            if (this.sortOrder) {
                parts.push(`sortOrder=${this.sortOrder}`);
            }
            if (this.clientId) {
                parts.push(`client_id=${this.clientId}`);
            }
            if (this.dateFrom) {
                parts.push(`date_from=${this.dateFrom}`);
            }
            if (this.dateTo) {
                parts.push(`date_to=${this.dateTo}`);
            }
            if (this.fiscalYear !== '' && this.fiscalYear != null) {
                parts.push(`fiscal_year=${this.fiscalYear}`);
            }
            if (this.calendarMonth !== '' && this.calendarMonth != null) {
                parts.push(`month=${this.calendarMonth}`);
            }
            if (!this.dateFrom && !this.dateTo) {
                parts.push('no_date_filter=1');
            }
            return parts.length ? `?${parts.join('&')}` : '';
        },
        async applyFilter(page = 1, { append = false } = {}) {
            if (!append) {
                this.allInvoicesScrollFillDepth = 0;
            }
            try {
                if (append) {
                    this.loadingMore = true;
                } else {
                    this.loading = true;
                }
                const response = await axios.get('/api/allInvoices/filtered', {
                    params: this.buildRequestParams(page),
                });
                if (append && this.localFakturas && Array.isArray(this.localFakturas.data)) {
                    this.localFakturas = {
                        ...response.data,
                        data: [...this.localFakturas.data, ...response.data.data],
                    };
                } else {
                    this.localFakturas = response.data;
                }
                if (response.data.filter_totals) {
                    this.filterTotalsSnapshot = response.data.filter_totals;
                }
                const redirectUrl = `/allInvoices${this.buildHistoryQueryString()}`;
                window.history.replaceState({}, '', redirectUrl);
            } catch (error) {
                console.error('Error applying filters:', error);
            } finally {
                this.loading = false;
                this.loadingMore = false;
                this.$nextTick(() => {
                    this.maybeFillAllInvoicesScroll();
                    this.syncAllInvoicesSubtotalScrollbarPad();
                });
            }
        },
        syncAllInvoicesSubtotalScrollbarPad() {
            const el = this.$refs.allInvoicesTableScroll;
            if (!el) {
                this.subtotalScrollbarPadPx = 0;
                return;
            }
            this.subtotalScrollbarPadPx = measureVerticalScrollbarWidth(el);
            if (!this._allInvoicesSubtotalWindowResize) {
                this._allInvoicesSubtotalWindowResize = () => this.syncAllInvoicesSubtotalScrollbarPad();
                window.addEventListener('resize', this._allInvoicesSubtotalWindowResize);
            }
        },
        onAllInvoicesTableScroll(e) {
            const el = e.target;
            if (this.loading || this.loadingMore) {
                return;
            }
            const p = this.displayFakturas;
            if (!p || !p.data?.length || p.current_page >= p.last_page) {
                return;
            }
            const threshold = 100;
            if (el.scrollTop + el.clientHeight >= el.scrollHeight - threshold) {
                this.applyFilter(p.current_page + 1, { append: true });
            }
        },
        maybeFillAllInvoicesScroll() {
            const el = this.$refs.allInvoicesTableScroll;
            if (!el || this.loading || this.loadingMore) {
                return;
            }
            const p = this.displayFakturas;
            if (!p || p.current_page >= p.last_page) {
                return;
            }
            if (this.allInvoicesScrollFillDepth >= 50) {
                return;
            }
            if (el.scrollHeight <= el.clientHeight + 2) {
                this.allInvoicesScrollFillDepth += 1;
                this.applyFilter(p.current_page + 1, { append: true });
            }
        },
        onSearchSubmit() {
            this.searchQuery = (this.searchInput || '').trim();
            this.applyFilter(1);
        },
        onDateRangeChange() {
            this.normalizeDates();
            this.applyFilter(1);
        },
        onPeriodPreset(type) {
            const today = localISODate();
            if (type === 'this_month') {
                const d = new Date();
                const y = d.getFullYear();
                const m = String(d.getMonth() + 1).padStart(2, '0');
                this.dateFrom = `${y}-${m}-01`;
                this.dateTo = today;
                this.fiscalYear = '';
                this.calendarMonth = '';
            } else if (type === 'this_year') {
                const y = new Date().getFullYear();
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
        getInvoiceOrders(faktura) {
            if (!faktura || !Array.isArray(faktura.invoices)) {
                return [];
            }
            return faktura.invoices;
        },
        formatArea(value) {
            const n = Number(value);
            if (!Number.isFinite(n)) {
                return '—';
            }
            return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        formatVatDisplay(job) {
            if (!job || !Array.isArray(job.articles) || !job.articles.length) {
                return '—';
            }
            const values = [];
            for (const article of job.articles) {
                if (article == null) {
                    continue;
                }
                const raw = article?.pivot?.taxRate ?? article?.taxRate;
                if (raw !== undefined && raw !== null && raw !== '') {
                    values.push(`${raw}%`);
                }
            }
            if (!values.length) {
                return '—';
            }
            return Array.from(new Set(values)).join(', ');
        },
        getOrderMergeGroups(order) {
            const raw = order?.merge_groups;
            if (Array.isArray(raw)) {
                return raw;
            }
            if (typeof raw === 'string' && raw.trim()) {
                try {
                    const parsed = JSON.parse(raw);
                    return Array.isArray(parsed) ? parsed : [];
                } catch (_) {
                    return [];
                }
            }
            return [];
        },
        getOrderGroupedJobs(order) {
            const jobs = Array.isArray(order?.jobs) ? order.jobs : [];
            if (!jobs.length) {
                return [];
            }

            const jobById = new Map();
            for (const job of jobs) {
                const key = Number(job?.id);
                if (Number.isFinite(key)) {
                    jobById.set(key, job);
                }
            }

            const groups = this.getOrderMergeGroups(order);
            const grouped = [];
            for (let i = 0; i < groups.length; i += 1) {
                const g = groups[i];
                const ids = Array.isArray(g?.job_ids) ? g.job_ids : [];
                const mergedJobs = [];
                let totalQty = 0;
                let totalArea = 0;
                for (const rawId of ids) {
                    const id = Number(rawId);
                    if (!Number.isFinite(id) || !jobById.has(id)) {
                        continue;
                    }
                    const job = jobById.get(id);
                    mergedJobs.push(job);
                    totalQty += Number(job?.quantity) || 0;
                    totalArea += Number(job?.computed_total_area_m2) || 0;
                }
                if (!mergedJobs.length) {
                    continue;
                }
                grouped.push({
                    index: i,
                    label: `Group ${i + 1}`,
                    jobs: mergedJobs,
                    jobCount: mergedJobs.length,
                    totalQty,
                    totalArea,
                    colorClass: `invoice-merge-color-${i % 6}`,
                });
            }
            return grouped;
        },
        getOrderUngroupedJobs(order) {
            const jobs = Array.isArray(order?.jobs) ? order.jobs : [];
            if (!jobs.length) {
                return [];
            }
            const groupedIds = new Set();
            const groups = this.getOrderGroupedJobs(order);
            for (const group of groups) {
                for (const job of group.jobs) {
                    const id = Number(job?.id);
                    if (Number.isFinite(id)) {
                        groupedIds.add(id);
                    }
                }
            }
            return jobs.filter((job) => {
                const id = Number(job?.id);
                if (!Number.isFinite(id)) {
                    return true;
                }
                return !groupedIds.has(id);
            });
        },
        formatFullDate(date) {
            if (!date) {
                return '';
            }
            const s = formatDateDdMmYyyy(date);
            return s === 'N/A' ? '' : s;
        },
        getCommentDisplay(comment) {
            return comment && String(comment).trim() ? comment : 'No comment';
        },
        getClientName(faktura) {
            if (faktura.client && faktura.client.name) {
                return faktura.client.name;
            }
            if (faktura.invoices && faktura.invoices.length > 0) {
                return faktura.invoices[0]?.client?.name || 'Unknown Client';
            }
            if (faktura.is_split_invoice) {
                if (faktura.jobs && faktura.jobs.length > 0) {
                    return faktura.jobs[0]?.client?.name || 'Unknown Client';
                }
                if (faktura.parent_order && faktura.parent_order.client) {
                    return faktura.parent_order.client.name;
                }
            }
            return 'Unknown Client';
        },
        formatFakturaMoney(val) {
            if (val === undefined || val === null || val === '') {
                return '—';
            }
            return val;
        },
        /** Match PHP number_format(..., 2, '.', ',') for subtotal strip */
        formatFinanceSubtotal(n) {
            const num = Number(n);
            if (!Number.isFinite(num)) {
                return '0.00';
            }
            return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        formatFakturaTotalCell(faktura) {
            const raw = faktura.total;
            if (raw === undefined || raw === null || raw === '') {
                return '—';
            }
            return raw;
        },
        getOrdersDisplay(faktura) {
            const ordersMap = new Map();
            if (Array.isArray(faktura.invoices)) {
                faktura.invoices.forEach((inv) => {
                    if (inv && inv.id != null) {
                        ordersMap.set(Number(inv.id), inv.order_number || inv.id);
                    }
                });
            }
            if (faktura.is_split_invoice && faktura.parent_order_id) {
                const orderNum = faktura.parent_order?.order_number || faktura.parent_order_id;
                ordersMap.set(Number(faktura.parent_order_id), orderNum);
            }
            const orderNumbers = Array.from(ordersMap.values());
            return orderNumbers.length ? orderNumbers.map((num) => `#${num}`).join(', ') : 'No Orders';
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

.filter-grid {
    display: grid;
    flex: 1 1 auto;
    min-width: 0;
    grid-template-columns: minmax(140px, 1fr) minmax(160px, 1.1fr) minmax(200px, 1.2fr) minmax(200px, 1.2fr) minmax(120px, 0.7fr);
    gap: 10px 12px;
    align-items: end;
}

.presets-inline {
    flex: 0 0 auto;
    align-self: flex-end;
}

@media (max-width: 1200px) {
    .filter-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 640px) {
    .filter-grid {
        grid-template-columns: 1fr;
    }

    .presets-inline {
        width: 100%;
    }
}

.filter-col--sort {
    min-width: 0;
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

.dark-gray {
    background-color: $dark-gray;
    justify-content: left;
    border-radius: 8px;
    align-items: center;
    min-height: 20vh;
    min-width: 0;
}

.loading-container {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 40px;
    color: white;
}

.loading-spinner {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;

    i {
        font-size: 24px;
        color: $blue;
    }

    span {
        font-size: 16px;
        color: white;
    }
}

/* Column widths come from <colgroup> + .all-invoices-table-shell col.* (keeps subtotal aligned) */
.customer-column {
    max-width: none;
}

.invoice-column,
.col-date,
.col-created,
.col-orders-h,
.col-comment-h {
    min-width: 0;
}

.actions-column {
    min-width: 0;
}

.col-num {
    text-align: right;
}

/* Amount / Tax / Total — room for large figures; nowrap + min-width so values don’t clip */
.all-invoices-table-shell :deep(.data-table-head th.col-num),
.all-invoices-table-shell :deep(.data-table-body td.col-num) {
    text-align: right;
    font-variant-numeric: tabular-nums;
    min-width: 11rem;
    white-space: nowrap;
}

.all-invoices-subtotal-sticky .finance-subtotal-table td.col-num {
    text-align: right;
    font-variant-numeric: tabular-nums;
    min-width: 11rem;
    white-space: nowrap;
}

.all-invoices-table-shell .finance-money {
    display: block;
    overflow: visible;
    text-overflow: clip;
    white-space: nowrap;
}

.text-right {
    text-align: right;
}

.invoice-primary-cell {
    min-width: 0;
}

.invoice-main {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 2px;
}

.invoice-number,
.cell-primary {
    font-weight: 700;
    color: $white;
}

.cell-secondary {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.68);
}

.orders-cell,
.comment-cell {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-width: 0;
    white-space: normal;
}

.split-badge {
    display: inline-flex;
    align-items: center;
    background: rgba(168, 85, 247, 0.18);
    color: white;
    padding: 4px 8px;
    border-radius: 999px;
    font-size: 10px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border: 1px solid rgba(168, 85, 247, 0.55);
}

.actions-cell {
    text-align: right;
}

.view-button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 10px;
    border: 1px solid rgba(59, 130, 246, 0.35);
    border-radius: 8px;
    background: rgba(59, 130, 246, 0.14);
    color: $white;
    font-size: 12px;
    font-weight: 700;
    transition: all 0.2s ease;
}

.view-button:hover {
    background: rgba(59, 130, 246, 0.22);
    border-color: rgba(96, 165, 250, 0.85);
}

.empty-cell {
    padding: 34px 16px !important;
    text-align: center;
    color: rgba(255, 255, 255, 0.7);
}

.all-invoices-table-block {
    width: 100%;
    min-width: 0;
    position: relative;
}

.all-invoices-table-shell {
    width: 100%;
    min-width: 0;
}

/* Same 10 columns as header/body — pairs with subtotal table colgroup */
.all-invoices-table-shell col.ai-col-inv,
.all-invoices-subtotal-sticky col.ai-col-inv {
    width: 10%;
}
.all-invoices-table-shell col.ai-col-date,
.all-invoices-subtotal-sticky col.ai-col-date {
    width: 9%;
}
.all-invoices-table-shell col.ai-col-client,
.all-invoices-subtotal-sticky col.ai-col-client {
    width: 12%;
}
.all-invoices-table-shell col.ai-col-num,
.all-invoices-subtotal-sticky col.ai-col-num {
    width: 11%;
}
.all-invoices-table-shell col.ai-col-by,
.all-invoices-subtotal-sticky col.ai-col-by {
    width: 8%;
}
.all-invoices-table-shell col.ai-col-orders,
.all-invoices-subtotal-sticky col.ai-col-orders {
    width: 8%;
}
.all-invoices-table-shell col.ai-col-comment,
.all-invoices-subtotal-sticky col.ai-col-comment {
    width: 10%;
}
.all-invoices-table-shell col.ai-col-actions,
.all-invoices-subtotal-sticky col.ai-col-actions {
    width: 10%;
}

.all-invoices-table-shell :deep(.data-table-head th),
.all-invoices-table-shell :deep(.data-table-body td) {
    padding-left: 9px;
    padding-right: 9px;
}

.all-invoices-table-shell :deep(.data-table-body tr.invoice-row) {
    cursor: pointer;
    transition: background-color 0.14s ease, box-shadow 0.14s ease;
}

.all-invoices-table-shell :deep(.data-table-body tr.invoice-row--active),
.all-invoices-table-shell :deep(.data-table-body tr.invoice-row--active:nth-child(even)),
.all-invoices-table-shell :deep(.data-table-body tr.invoice-row--active:hover) {
    background: linear-gradient(90deg, rgba(59, 130, 246, 0.16) 0%, rgba(30, 41, 59, 0.88) 100%) !important;
    box-shadow:
        inset 3px 0 0 #60a5fa,
        inset 0 1px 0 rgba(147, 197, 253, 0.22),
        inset 0 -1px 0 rgba(147, 197, 253, 0.16);
}

.all-invoices-subtotal-sticky .finance-subtotal-table td {
    padding-left: 9px;
    padding-right: 9px;
}

.all-invoices-table-shell.finance-table-scroll {
    min-height: 0;
    max-height: calc(40px + 10 * 38px + 56px);
    overflow-y: auto;
    overflow-x: auto;
    border-radius: 12px;
}



.all-invoices-table-shell.finance-table-scroll :deep(.data-table-head th) {
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

.all-invoices-table-shell :deep(.data-table) {
    table-layout: fixed;
    width: 100%;
}

/* Same inner width as subtotal; outer .finance-table-scroll handles overflow. Inner overflow must stay
   visible so thead position:sticky works (DataTableShell’s overflow:hidden would otherwise break it). */
.all-invoices-table-shell.finance-table-scroll :deep(.data-table-shell) {
    min-width: 0;
    overflow: visible;
}
.all-invoices-table-shell.finance-table-scroll :deep(.table-scroll) {
    overflow: visible;
    min-width: 0;
}


.all-invoices-subtotal-sticky {
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

.all-invoices-subtotal-sticky .finance-subtotal-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    font-variant-numeric: tabular-nums;
}


.all-invoices-subtotal-sticky .finance-subtotal-table td {
    padding: 5px 9px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    font-size: 12px;
    line-height: 1.35;
    vertical-align: middle;
}

.all-invoices-subtotal-sticky tr.all-invoices-subtotal-row td {
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

.all-invoices-subtotal-sticky tr.all-invoices-subtotal-row td.all-invoices-subtotal-num--total {
    box-shadow:
        inset 0 1px 0 rgba(191, 219, 254, 0.14),
        inset 1px 0 0 rgba(147, 197, 253, 0.4);
}

.all-invoices-subtotal-sticky .all-invoices-subtotal-label {
    text-align: left;
    vertical-align: middle;
}

.all-invoices-subtotal-sticky .all-invoices-subtotal-label-text {
    display: inline-block;
    margin-right: 8px;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: #e8f0fe;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.35);
}

.all-invoices-subtotal-sticky .all-invoices-subtotal-hint {
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

.all-invoices-subtotal-sticky .all-invoices-subtotal-value {
    font-size: 13px;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    color: #f1f5f9;
}

.all-invoices-subtotal-sticky .all-invoices-subtotal-value--emphasis {
    font-size: 14px;
    font-weight: 800;
    color: #fff;
}

.invoice-drawer-portal {
    position: fixed;
    inset: 0;
    z-index: 1900;
}

.invoice-drawer-scrim {
    position: absolute;
    inset: 0;
    background: rgba(2, 6, 23, 0.62);
    backdrop-filter: blur(1px);
}

.invoice-drawer-panel {
    position: absolute;
    top: 0;
    right: 0;
    height: 100%;
    width: min(940px, 96vw);
    display: flex;
    flex-direction: column;
    background: #0f172a;
    border-left: 1px solid rgba(148, 163, 184, 0.24);
    box-shadow: -18px 0 44px rgba(2, 6, 23, 0.48);
}

.invoice-drawer-head {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    padding: 16px 20px;
    border-bottom: 1px solid rgba(148, 163, 184, 0.2);
    background: rgba(15, 23, 42, 0.96);
}

.invoice-drawer-title {
    margin: 0;
    font-size: 20px;
    font-weight: 800;
    color: #f8fafc;
}

.invoice-drawer-subtitle {
    margin: 4px 0 0;
    color: rgba(226, 232, 240, 0.82);
    font-size: 13px;
}

.invoice-drawer-head-actions {
    display: flex;
    align-items: flex-start;
    gap: 8px;
}

.invoice-drawer-chip {
    border: 1px solid rgba(125, 211, 252, 0.38);
    background: rgba(14, 116, 144, 0.28);
    color: #e0f2fe;
    border-radius: 999px;
    padding: 4px 10px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.invoice-drawer-close {
    border: 1px solid rgba(148, 163, 184, 0.3);
    background: rgba(15, 23, 42, 0.7);
    color: #e2e8f0;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    font-size: 20px;
    line-height: 1;
}

.invoice-drawer-scroll {
    flex: 1;
    overflow: auto;
    padding: 16px 20px 18px;
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.invoice-drawer-meta-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(148px, 1fr));
    gap: 10px;
}

.invoice-meta-card {
    display: flex;
    flex-direction: column;
    gap: 5px;
    padding: 10px 12px;
    border-radius: 10px;
    border: 1px solid rgba(148, 163, 184, 0.18);
    background: rgba(15, 23, 42, 0.72);
}

.invoice-meta-card--emphasis {
    border-color: rgba(96, 165, 250, 0.38);
    background: linear-gradient(180deg, rgba(30, 64, 175, 0.26) 0%, rgba(15, 23, 42, 0.82) 100%);
}

.invoice-meta-label {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: rgba(148, 163, 184, 0.92);
    font-weight: 700;
}

.invoice-meta-value {
    font-size: 14px;
    font-weight: 700;
    color: #f8fafc;
}

.invoice-drawer-section {
    border: 1px solid rgba(148, 163, 184, 0.17);
    background: rgba(15, 23, 42, 0.64);
    border-radius: 12px;
    padding: 12px;
}

.invoice-drawer-section-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
}

.invoice-drawer-section-title {
    margin: 0;
    font-size: 13px;
    font-weight: 800;
    letter-spacing: 0.03em;
    color: #dbeafe;
    text-transform: uppercase;
}

.invoice-drawer-section-hint {
    font-size: 11px;
    color: rgba(191, 219, 254, 0.85);
    background: rgba(30, 58, 138, 0.28);
    border: 1px solid rgba(96, 165, 250, 0.3);
    border-radius: 999px;
    padding: 3px 8px;
}

.invoice-drawer-empty {
    color: rgba(203, 213, 225, 0.75);
    font-size: 13px;
}

.invoice-order-card {
    border: 1px solid rgba(148, 163, 184, 0.15);
    border-radius: 10px;
    background: rgba(2, 6, 23, 0.26);
    padding: 10px;
}

.invoice-order-card + .invoice-order-card {
    margin-top: 10px;
}

.invoice-order-separator {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.invoice-order-separator .separator-line {
    flex: 1;
    height: 1px;
    background: rgba(226, 232, 240, 0.32);
}

.invoice-order-separator .separator-text {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: rgba(226, 232, 240, 0.84);
    font-weight: 700;
}

.invoice-order-head {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 8px;
}

.invoice-order-title {
    margin: 0;
    font-size: 14px;
    font-weight: 800;
    color: #f1f5f9;
}

.invoice-order-subtitle {
    margin: 3px 0 0;
    font-size: 12px;
    color: rgba(203, 213, 225, 0.74);
}

.invoice-order-grouping {
    margin: 4px 0 0;
    font-size: 11px;
    color: rgba(147, 197, 253, 0.88);
}

.invoice-order-head-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 3px;
}

.invoice-order-meta {
    font-size: 11px;
    color: rgba(191, 219, 254, 0.92);
}

.invoice-order-jobs {
    border-top: 1px solid rgba(148, 163, 184, 0.16);
    padding-top: 8px;
}

.invoice-merge-group {
    border: 1px solid rgba(148, 163, 184, 0.24);
    border-radius: 8px;
    margin-bottom: 8px;
    overflow: hidden;
}

.invoice-merge-group-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
    padding: 7px 9px;
}

.invoice-merge-badge {
    background: rgba(78, 205, 196, 0.9);
    color: #06262f;
    padding: 2px 7px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.invoice-merge-stats {
    font-size: 11px;
    font-weight: 700;
    color: rgba(241, 245, 249, 0.9);
}

.invoice-merge-group-body {
    padding: 0 9px 6px;
    background: rgba(2, 6, 23, 0.2);
}

.invoice-job-row--inside-group {
    border-bottom-color: rgba(148, 163, 184, 0.16);
}

.invoice-ungrouped-label {
    margin: 2px 0 4px;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: rgba(148, 163, 184, 0.88);
}

.invoice-merge-color-0 .invoice-merge-group-head {
    background: rgba(49, 130, 206, 0.48);
}

.invoice-merge-color-1 .invoice-merge-group-head {
    background: rgba(81, 168, 177, 0.48);
}

.invoice-merge-color-2 .invoice-merge-group-head {
    background: rgba(163, 106, 3, 0.48);
}

.invoice-merge-color-3 .invoice-merge-group-head {
    background: rgba(162, 185, 58, 0.45);
}

.invoice-merge-color-4 .invoice-merge-group-head {
    background: rgba(158, 44, 48, 0.52);
}

.invoice-merge-color-5 .invoice-merge-group-head {
    background: rgba(129, 201, 80, 0.46);
}

.invoice-job-row {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    padding: 8px 0;
    border-bottom: 1px solid rgba(148, 163, 184, 0.1);
}

.invoice-job-row:last-child {
    border-bottom: none;
    padding-bottom: 2px;
}

.invoice-job-main,
.invoice-job-side {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.invoice-job-side {
    align-items: flex-end;
}

.invoice-job-name {
    color: #f8fafc;
    font-size: 13px;
    font-weight: 700;
}

.invoice-job-meta {
    color: rgba(203, 213, 225, 0.74);
    font-size: 12px;
}

.invoice-drawer-comment {
    margin: 8px 0 0;
    color: rgba(226, 232, 240, 0.88);
    white-space: pre-wrap;
    line-height: 1.45;
    font-size: 13px;
}

.invoice-drawer-foot {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    padding: 12px 20px;
    border-top: 1px solid rgba(148, 163, 184, 0.2);
    background: rgba(15, 23, 42, 0.95);
}

.invoice-drawer-btn {
    border-radius: 9px;
    padding: 7px 12px;
    font-size: 12px;
    font-weight: 700;
    border: 1px solid transparent;
}

.invoice-drawer-btn--muted {
    color: #e2e8f0;
    background: rgba(15, 23, 42, 0.72);
    border-color: rgba(148, 163, 184, 0.34);
}

.invoice-drawer-btn--primary {
    color: #f8fafc;
    background: rgba(37, 99, 235, 0.34);
    border-color: rgba(96, 165, 250, 0.65);
}

@media (max-width: 900px) {
    .invoice-drawer-panel {
        width: 100vw;
    }
}
</style>





<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="invoice2" subtitle="invoicedOrders" icon="invoice.png" link="allInvoices" />
            <RedirectTabs :route="$page.url" />
            <div class="dark-gray p-2 text-white">
                <div class="form-container p-2">
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

                    <DataTableShell v-else compact variant="grid">
                        <template #header>
                            <tr>
                                <th class="invoice-column">Invoice</th>
                                <th class="customer-column">Customer</th>
                                <th>Orders</th>
                                <th>Comment</th>
                                <th>Created By</th>
                                <th>Date Created</th>
                                <th class="actions-column">Actions</th>
                            </tr>
                        </template>

                        <template v-if="hasInvoices">
                            <tr v-for="faktura in displayFakturas.data" :key="faktura.id">
                                <td class="invoice-primary-cell">
                                    <div class="invoice-main">
                                        <div class="invoice-number">#{{ faktura.faktura_number || faktura.id }}</div>
                                        <span v-if="faktura.is_split_invoice" class="split-badge">Split</span>
                                    </div>
                                    <div class="invoice-meta">{{ formatInvoicePeriod(faktura.created_at) }}</div>
                                </td>
                                <td class="customer-column">
                                    <FinanceClientNameCell :name="getClientName(faktura)" />
                                </td>
                                <td>
                                    <div class="cell-secondary orders-cell">{{ getOrdersDisplay(faktura) }}</div>
                                </td>
                                <td>
                                    <div class="cell-secondary comment-cell">{{ getCommentDisplay(faktura.comment) }}</div>
                                </td>
                                <td>
                                    <div class="cell-secondary">{{ faktura.created_by?.name || 'N/A' }}</div>
                                </td>
                                <td>
                                    <div class="cell-secondary">{{ formatFullDate(faktura.created_at) }}</div>
                                </td>
                                <td class="actions-cell">
                                    <button class="view-button" @click="viewInvoice(faktura.id)">
                                        <i class="fa fa-eye" aria-hidden="true" />
                                        <span>View</span>
                                    </button>
                                </td>
                            </tr>
                        </template>

                        <tr v-else>
                            <td colspan="7" class="empty-cell">
                                No invoices found matching your criteria.
                            </td>
                        </tr>
                    </DataTableShell>
                </div>
                <Pagination :pagination="displayFakturas" @pagination-change-page="goToPage" />
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
import FinanceCompactSearch from '@/Components/Finance/FinanceCompactSearch.vue';
import FinanceClientSearchSelect from '@/Components/Finance/FinanceClientSearchSelect.vue';
import FinanceDateRangeCompact from '@/Components/Finance/FinanceDateRangeCompact.vue';
import FinanceYearMonthSelects from '@/Components/Finance/FinanceYearMonthSelects.vue';
import FinancePeriodPresets from '@/Components/Finance/FinancePeriodPresets.vue';
import FinanceClientNameCell from '@/Components/Finance/FinanceClientNameCell.vue';
import axios from 'axios';

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
        Pagination,
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
            perPage: 20,
        };
    },
    computed: {
        hasInvoices() {
            return this.localFakturas && this.localFakturas.data && this.localFakturas.data.length > 0;
        },
        displayFakturas() {
            return this.localFakturas || this.fakturas;
        },
    },
    mounted() {
        this.fetchUniqueClients();
        this.initFromUrl();
        const page = parseInt(new URLSearchParams(window.location.search).get('page') || '1', 10) || 1;
        if (this.fakturas) {
            this.localFakturas = this.fakturas;
        }
        this.applyFilter(page);
    },
    methods: {
        initFromUrl() {
            const p = new URLSearchParams(window.location.search);
            const sq = p.get('searchQuery') || '';
            this.searchInput = sq;
            this.searchQuery = sq;
            const cid = p.get('client_id');
            this.clientId = cid ? parseInt(cid, 10) : null;
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
                per_page: this.perPage,
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
            return params;
        },
        buildHistoryQueryString(page) {
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
            parts.push(`per_page=${this.perPage}`);
            if (page) {
                parts.push(`page=${page}`);
            }
            return parts.length ? `?${parts.join('&')}` : '';
        },
        async applyFilter(page = 1) {
            try {
                this.loading = true;
                const response = await axios.get('/api/allInvoices/filtered', {
                    params: this.buildRequestParams(page),
                });
                this.localFakturas = response.data;
                const redirectUrl = `/allInvoices${this.buildHistoryQueryString(page)}`;
                window.history.replaceState({}, '', redirectUrl);
            } catch (error) {
                console.error('Error applying filters:', error);
            } finally {
                this.loading = false;
            }
        },
        goToPage(page) {
            this.applyFilter(page);
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
        formatInvoicePeriod(date) {
            return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short' });
        },
        formatFullDate(date) {
            return new Date(date).toLocaleDateString('en-US', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
            });
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
    min-width: 80vh;
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

.customer-column {
    max-width: 280px;
    width: 22%;
}

.invoice-column {
    width: 180px;
}

.actions-column {
    width: 120px;
}

.invoice-primary-cell {
    min-width: 180px;
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

.invoice-meta,
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
    min-width: 180px;
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
</style>

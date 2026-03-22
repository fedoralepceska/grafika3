<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <div class="page-shell">
                <div class="flex justify-between">
                    <Header title="CardStatements" subtitle="CCS" icon="clientCard.png" link="cardStatements"/>
                </div>

                <div class="dark-gray p-5 text-white overflow-x-hidden">
                    <div class="statement-hero">
                        <div class="statement-title-block">
                            <div class="statement-kicker">Client Statement</div>
                            <h1 class="statement-title">{{ client?.name || 'Client Statement' }}</h1>
                            <p class="statement-subtitle">{{ cardStatement?.bank || 'No bank' }} · {{ cardStatement?.account || 'No account' }}</p>
                        </div>

                        <div class="page-actions">
                            <CardStatementUpdateDialog :client_id="client?.id" />
                        </div>
                    </div>

                    <div class="summary-grid">
                        <div class="summary-card summary-card--wide">
                            <div class="summary-card-head">
                                <span class="summary-label">Statement Overview</span>
                                <span class="summary-chip">{{ cardStatement?.account || 'N/A' }}</span>
                            </div>

                            <div class="detail-grid">
                                <div class="detail-field">
                                    <span class="detail-label">Client</span>
                                    <div class="detail-value">{{ client?.name || 'N/A' }}</div>
                                </div>
                                <div class="detail-field">
                                    <span class="detail-label">Name</span>
                                    <div class="detail-value">{{ cardStatement?.name || 'N/A' }}</div>
                                </div>
                                <div class="detail-field">
                                    <span class="detail-label">Bank</span>
                                    <div class="detail-value">{{ cardStatement?.bank || 'N/A' }}</div>
                                </div>
                                <div class="detail-field">
                                    <span class="detail-label">Payment Deadline</span>
                                    <div class="detail-value">{{ cardStatement?.payment_deadline || 'N/A' }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="summary-card">
                            <span class="summary-label">Requests</span>
                            <div class="summary-value success-text">{{ formatNumber(summaryRequests) }}</div>
                            <div class="summary-meta">Incoming for current filters</div>
                        </div>

                        <div class="summary-card">
                            <span class="summary-label">Owes</span>
                            <div class="summary-value danger-text">{{ formatNumber(summaryOwes) }}</div>
                            <div class="summary-meta">Outgoing for current filters</div>
                        </div>

                        <div class="summary-card">
                            <span class="summary-label">Balance</span>
                            <div class="summary-value" :class="summaryBalance >= 0 ? 'success-text' : 'danger-text'">
                                {{ formatSignedBalance(summaryBalance) }}
                            </div>
                            <div class="summary-meta">Based on filtered rows</div>
                        </div>
                    </div>

                    <div class="toolbar-panel">
                        <div class="toolbar-inline">
                            <div class="filter-toolbar-ccs">
                                <FinanceCompactSearch
                                    v-model="searchInput"
                                    label="Search rows"
                                    placeholder="Document, #, date, comment…"
                                    class="ccs-field ccs-field--search"
                                    @submit="onSearchSubmit"
                                />
                                <FinanceDateRangeCompact
                                    class="ccs-field ccs-field--dates"
                                    :date-from="dateFrom"
                                    :date-to="dateTo"
                                    label="Period (source data)"
                                    @update:date-from="dateFrom = $event"
                                    @update:date-to="dateTo = $event"
                                    @change="onDateRangeChange"
                                />
                                <FinanceYearMonthSelects
                                    class="ccs-field ccs-field--ym"
                                    :fiscal-year="fiscalYear"
                                    :month="calendarMonth"
                                    @update:fiscal-year="fiscalYear = $event"
                                    @update:month="calendarMonth = $event"
                                    @change="applyFilter"
                                />
                            </div>
                            <FinancePeriodPresets
                                class="presets-inline-ccs"
                                label=""
                                @preset="onPeriodPreset"
                                @clear-dates="onClearDates"
                            />
                        </div>
                    </div>

                    <DataTableShell compact variant="grid" class="statement-table">
                        <template #header>
                            <tr>
                                <th>Date</th>
                                <th>Document</th>
                                <th>Number</th>
                                <th class="text-right">Incoming Invoice</th>
                                <th class="text-right">Outcome Invoice</th>
                                <th class="text-right">Statement Income</th>
                                <th class="text-right">Statement Expense</th>
                                <th>Comment</th>
                            </tr>
                        </template>

                        <template v-if="localTableData?.data?.length">
                            <tr
                                v-for="item in localTableData.data"
                                :key="`${item.number}-${item.document}-${item.date}`"
                                :class="{ 'split-invoice-row': isSplitDocument(item.document) }"
                            >
                                <td><div class="cell-secondary">{{ item?.date || '—' }}</div></td>
                                <td>
                                    <div class="document-cell">
                                        <span class="cell-primary">{{ item?.document || '—' }}</span>
                                        <span v-if="isSplitDocument(item.document)" class="split-badge">Split</span>
                                    </div>
                                </td>
                                <td><div class="cell-secondary">{{ item?.number || '—' }}</div></td>
                                <td class="text-right"><div class="cell-secondary">{{ formatNumber(item?.incoming_invoice) }}</div></td>
                                <td class="text-right"><div class="cell-secondary">{{ formatNumber(item?.output_invoice) }}</div></td>
                                <td class="text-right"><div class="cell-secondary">{{ formatNumber(item?.statement_income) }}</div></td>
                                <td class="text-right"><div class="cell-secondary">{{ formatNumber(item?.statement_expense) }}</div></td>
                                <td><div class="comment-cell">{{ item?.comment || '—' }}</div></td>
                            </tr>
                        </template>

                        <tr v-else>
                            <td colspan="8" class="empty-cell">
                                No client statement entries found for the selected period.
                            </td>
                        </tr>
                    </DataTableShell>

                    <Pagination :pagination="localTableData" @pagination-change-page="fetchTableData"/>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script>
import MainLayout from "@/Layouts/MainLayout.vue";
import axios from "axios";
import Header from "@/Components/Header.vue";
import Pagination from "@/Components/Pagination.vue";
import DataTableShell from "@/Components/DataTableShell.vue";
import CardStatementUpdateDialog from "@/Components/CardStatementUpdateDialog.vue";
import FinanceCompactSearch from "@/Components/Finance/FinanceCompactSearch.vue";
import FinanceDateRangeCompact from "@/Components/Finance/FinanceDateRangeCompact.vue";
import FinanceYearMonthSelects from "@/Components/Finance/FinanceYearMonthSelects.vue";
import FinancePeriodPresets from "@/Components/Finance/FinancePeriodPresets.vue";
import { normalizeDateRangeFields } from "@/utils/financeFilters";

export default {
    components: {
        MainLayout,
        Header,
        Pagination,
        DataTableShell,
        CardStatementUpdateDialog,
        FinanceCompactSearch,
        FinanceDateRangeCompact,
        FinanceYearMonthSelects,
        FinancePeriodPresets,
    },
    props: {
        cardStatement: Object,
        client: Object,
        owes: Number,
        requests: Number,
        balance: Number,
        tableData: Object,
    },
    data() {
        return {
            localTableData: this.tableData || { data: [], links: [] },
            perPage: 20,
            searchInput: '',
            searchQuery: '',
            dateFrom: '',
            dateTo: '',
            fiscalYear: '',
            calendarMonth: '',
            summaryOwes: this.owes,
            summaryRequests: this.requests,
            summaryBalance: this.balance,
        };
    },
    watch: {
        owes(v) {
            this.summaryOwes = v;
        },
        requests(v) {
            this.summaryRequests = v;
        },
        balance(v) {
            this.summaryBalance = v;
        },
    },
    mounted() {
        this.initFiltersFromUrl();
        this.searchInput = this.searchQuery;
        const page = parseInt(new URLSearchParams(window.location.search).get('page') || '1', 10) || 1;
        this.fetchTableData(page);
    },
    methods: {
        initFiltersFromUrl() {
            const p = new URLSearchParams(window.location.search);
            this.searchQuery = p.get('searchQuery') || '';
            this.searchInput = this.searchQuery;
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
        buildCcsParams(page = 1) {
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
        pushCcsHistory(params) {
            const qs = new URLSearchParams();
            Object.entries(params).forEach(([k, v]) => {
                if (v !== '' && v !== null && v !== undefined) {
                    qs.set(k, String(v));
                }
            });
            const q = qs.toString();
            // Use replaceState so filter/pagination URL updates don't add extra history entries
            // (otherwise Back from this page steps through stale URLs before returning to the list).
            window.history.replaceState({}, '', q ? `/cardStatement/${this.cardStatement.id}?${q}` : `/cardStatement/${this.cardStatement.id}`);
        },
        fetchTableData(page = 1) {
            const params = this.buildCcsParams(page);
            axios.get(`/cardStatement/${this.cardStatement.id}`, {
                params,
                headers: { Accept: 'application/json' },
            }).then((response) => {
                const d = response.data;
                const { owes, requests, balance, ...pagination } = d;
                this.localTableData = pagination;
                if (owes !== undefined) {
                    this.summaryOwes = owes;
                }
                if (requests !== undefined) {
                    this.summaryRequests = requests;
                }
                if (balance !== undefined) {
                    this.summaryBalance = balance;
                }
                this.pushCcsHistory(params);
            }).catch((error) => {
                console.error('Error fetching data: ', error);
            });
        },
        onSearchSubmit() {
            this.searchQuery = (this.searchInput || '').trim();
            this.fetchTableData(1);
        },
        onDateRangeChange() {
            const state = { dateFrom: this.dateFrom, dateTo: this.dateTo };
            normalizeDateRangeFields(state);
            this.dateFrom = state.dateFrom;
            this.dateTo = state.dateTo;
            this.fetchTableData(1);
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
            this.fetchTableData(1);
        },
        onClearDates() {
            this.dateFrom = '';
            this.dateTo = '';
            this.fetchTableData(1);
        },
        applyFilter() {
            this.fetchTableData(1);
        },
        isSplitDocument(document) {
            return Boolean(document && document.includes('Split'));
        },
        formatNumber(value) {
            return Number(value || 0).toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        },
        formatSignedBalance(value) {
            if (!value) return this.formatNumber(0);
            const prefix = value > 0 ? '+' : '-';
            return `${prefix}${this.formatNumber(Math.abs(value))}`;
        }
    }
}
</script>

<style scoped lang="scss">
.page-shell {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.statement-hero {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 20px;
}

.statement-title-block {
    min-width: 0;
}

.statement-kicker {
    margin-bottom: 8px;
    color: rgba(255, 255, 255, 0.62);
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}

.statement-title {
    margin: 0;
    font-size: 28px;
    font-weight: 800;
    line-height: 1.15;
    color: $white;
}

.statement-subtitle {
    margin: 8px 0 0;
    color: rgba(255, 255, 255, 0.68);
    font-size: 14px;
}

.page-actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 10px;
}

.page-actions :deep(.d-flex.justify-center.align-center) {
    display: contents;
}

.page-actions :deep(.v-row) {
    margin: 0;
    width: auto;
    flex: 0 0 auto;
}

.page-actions :deep(.bt) {
    margin: 0;
}

.page-actions :deep(.bt button) {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border: 1px solid rgba(255, 255, 255, 0.14);
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.06);
    color: $white;
    transition: all 0.2s ease;
}

.page-actions :deep(.bt button:hover) {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.24);
}

.summary-grid {
    display: grid;
    grid-template-columns: 1.5fr repeat(3, minmax(180px, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}

.summary-card {
    padding: 18px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.04);
}

.summary-card--wide {
    min-width: 0;
}

.summary-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 16px;
}

.summary-label {
    display: block;
    color: rgba(255, 255, 255, 0.64);
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.summary-chip {
    padding: 6px 10px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.08);
    color: rgba(255, 255, 255, 0.82);
    font-size: 11px;
    font-weight: 700;
}

.summary-value {
    margin-top: 10px;
    font-size: 26px;
    font-weight: 800;
    color: $white;
}

.summary-meta {
    margin-top: 8px;
    font-size: 12px;
    color: rgba(255, 255, 255, 0.6);
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(180px, 1fr));
    gap: 14px 18px;
}

.detail-label {
    display: block;
    margin-bottom: 6px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: rgba(255, 255, 255, 0.58);
}

.detail-value {
    color: $white;
    font-size: 14px;
    font-weight: 700;
}

.success-text {
    color: #4ade80;
}

.danger-text {
    color: #f87171;
}

.toolbar-panel {
    margin-bottom: 14px;
    padding: 12px 14px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.04);
}

.toolbar-inline {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 10px 12px;
}

.filter-toolbar-ccs {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 10px 12px;
    flex: 1 1 auto;
    min-width: 0;
}

.filter-toolbar-ccs .ccs-field--search {
    flex: 0 1 220px;
    min-width: 160px;
    max-width: 300px;
}

.filter-toolbar-ccs .ccs-field--dates {
    flex: 1 1 280px;
    min-width: 260px;
    max-width: 360px;
}

.filter-toolbar-ccs .ccs-field--ym {
    flex: 0 1 260px;
    min-width: 240px;
}

.presets-inline-ccs {
    flex: 0 0 auto;
    align-self: flex-end;
    min-width: 0;
}

@media (min-width: 900px) {
    .presets-inline-ccs :deep(.fc-pp__row) {
        flex-wrap: nowrap;
    }
}

@media (max-width: 1100px) {
    .filter-toolbar-ccs .ccs-field--dates {
        flex-basis: 100%;
        max-width: none;
    }

    .filter-toolbar-ccs .ccs-field--ym {
        flex-basis: 100%;
    }
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

.document-cell {
    display: inline-flex;
    align-items: center;
    gap: 8px;
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
    font-size: 12px;
    color: rgba(255, 255, 255, 0.68);
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

.split-invoice-row :deep(td) {
    background: rgba(168, 85, 247, 0.06);
}

.statement-table :deep(.data-table-head th:not(:last-child)),
.statement-table :deep(.data-table-body td:not(:last-child)) {
    border-right: 1px solid rgba(255, 255, 255, 0.14);
}

.empty-cell {
    padding: 34px 16px !important;
    text-align: center;
    color: rgba(255, 255, 255, 0.7);
}

.dark-gray {
    background-color: $dark-gray;
    justify-content: left;
    align-items: center;
    min-height: 20vh;
    width: 100%;
    border-radius: 8px;
}

@media (max-width: 1200px) {
    .summary-grid {
        grid-template-columns: repeat(2, minmax(220px, 1fr));
    }

    .summary-card--wide {
        grid-column: span 2;
    }
}

@media (max-width: 900px) {
    .statement-hero {
        flex-direction: column;
    }

    .page-actions {
        justify-content: flex-start;
    }
}

@media (max-width: 768px) {
    .summary-grid,
    .detail-grid,
    .summary-card--wide {
        grid-column: span 1;
    }
}
</style>

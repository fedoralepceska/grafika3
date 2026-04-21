<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <div class="flex justify-between">
                <Header title="statement" subtitle="bankStatement" icon="bill.png" link="statements"/>
                <div class="flex pt-4">
                    <div class="flex gap-2 pt-3 bank-toolbar-dialogs">
                        <ViewBanksDialog :bank="bank" />
                        <AddBankDialog :bank="bank" />
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

                    <div class="statements-split">
                        <div class="statements-list-col">
                            <div
                                ref="statementsListScroll"
                                class="statements-list-scroll"
                                @scroll.passive="onStatementsListScroll"
                            >
                            <DataTableShell class="statements-list-table" compact variant="grid">
                                <template #colgroup>
                                    <colgroup>
                                        <col class="col-stmt" />
                                        <col class="col-date" />
                                        <col class="col-account" />
                                    </colgroup>
                                </template>
                                <template #header>
                                    <tr>
                                        <th class="statement-column">Statement #</th>
                                        <th class="date-column">Date</th>
                                        <th class="account-column">Bank account</th>
                                    </tr>
                                </template>

                                <template v-if="localCertificates && localCertificates.length > 0">
                                    <tr
                                        v-for="certificate in localCertificates"
                                        :key="certificate.id"
                                        class="statement-row"
                                        :class="{ 'statement-row--selected': selectedStatementId === certificate.id }"
                                        tabindex="0"
                                        @click="openStatementDrawer(certificate)"
                                        @keydown.enter.prevent="openStatementDrawer(certificate)"
                                        @keydown.space.prevent="openStatementDrawer(certificate)"
                                    >
                                        <td class="statement-primary-cell">
                                            <div class="cell-primary">#{{ certificate.id_per_bank }}/{{ certificate.fiscal_year }}</div>
                                        </td>
                                        <td><div class="cell-secondary">{{ formatDateDisplay(certificate.date) }}</div></td>
                                        <td><div class="cell-secondary">{{ certificate.bankAccount }}</div></td>
                                    </tr>
                                </template>

                                <tr v-else>
                                    <td colspan="3" class="empty-cell">
                                        No bank statements found matching your criteria.
                                    </td>
                                </tr>
                            </DataTableShell>
                            <div
                                v-if="loadingMoreStatements"
                                class="statements-scroll-loading"
                                aria-live="polite"
                            >
                                <i class="fa fa-spinner fa-spin" aria-hidden="true" /> Loading more…
                            </div>
                            </div>
                        </div>

                        <aside
                            class="statements-details-aside"
                            :aria-label="drawerRegionLabel"
                            @click.stop
                        >
                            <div class="statements-aside-body">
                                <div v-if="selectedStatementId == null && localCertificates.length" class="statements-aside-idle" />
                                <div v-else-if="selectedStatementId == null && !localCertificates.length" class="statements-aside-idle-text">
                                    No statements in this list.
                                </div>
                                <div v-else-if="drawerLoading" class="statement-drawer-loading">Loading…</div>
                                <div v-else-if="drawerError" class="statement-drawer-error">{{ drawerError }}</div>
                                <template v-else-if="drawerCertificate">
                                    <DataTableShell
                                        compact
                                        variant="grid"
                                        class="statement-table drawer-inline-statement-table statements-nested-shell"
                                    >
                                        <template #header>
                                            <tr>
                                                <th class="number-column">Nr</th>
                                                <th class="client-column">Client</th>
                                                <th class="text-right">Expense</th>
                                                <th class="text-right">Income</th>
                                                <th>Code</th>
                                                <th>Reference</th>
                                                <th>Comment</th>
                                            </tr>
                                        </template>

                                        <template v-if="drawerItems.length">
                                            <tr v-for="(item, index) in drawerItems" :key="item.id">
                                                <td class="number-cell drawer-nr-compact">
                                                    <span class="drawer-nr-line">
                                                        <span class="cell-primary drawer-nr-main">#{{ index + 1 }}</span>
                                                        <span class="cell-secondary drawer-nr-id">{{ item.id }}</span>
                                                    </span>
                                                </td>
                                                <td class="client-cell">
                                                    <div class="cell-primary drawer-cell-tight">{{ getDrawerClientName(item) || 'N/A' }}</div>
                                                </td>
                                                <td class="text-right amount-cell">
                                                    <span class="cell-secondary drawer-cell-tight">{{ formatDrawerNumber(item.expense) }}</span>
                                                </td>
                                                <td class="text-right amount-cell">
                                                    <span class="cell-secondary drawer-cell-tight">{{ formatDrawerNumber(item.income) }}</span>
                                                </td>
                                                <td>
                                                    <span class="cell-secondary drawer-cell-tight">{{ item.code || '—' }}</span>
                                                </td>
                                                <td>
                                                    <span class="cell-secondary drawer-cell-tight">{{ item.reference_to || '—' }}</span>
                                                </td>
                                                <td class="comment-cell">
                                                    <span class="cell-secondary drawer-cell-tight">{{ item.comment || '—' }}</span>
                                                </td>
                                            </tr>
                                        </template>

                                        <tr v-else>
                                            <td colspan="7" class="empty-cell drawer-statement-empty-cell">
                                                No statement transactions added yet.
                                            </td>
                                        </tr>

                                        <template #footer>
                                            <tr>
                                                <td colspan="2">Totals</td>
                                                <td class="text-right">{{ formatDrawerNumber(drawerTotalExpense) }}</td>
                                                <td class="text-right">{{ formatDrawerNumber(drawerTotalIncome) }}</td>
                                                <td colspan="3" class="footer-balance">
                                                    Net {{ formatSignedDrawerBalance(drawerNetBalance) }}
                                                </td>
                                            </tr>
                                        </template>
                                    </DataTableShell>
                                </template>
                            </div>

                            <div v-if="selectedStatementId != null" class="statement-drawer-footer">
                                <button
                                    type="button"
                                    class="statements-aside-btn statements-aside-btn--muted"
                                    @click="closeStatementDrawer"
                                >
                                    Close
                                </button>
                                <button
                                    v-if="drawerCertificate && !drawerLoading"
                                    type="button"
                                    class="statements-aside-btn statements-aside-btn--primary"
                                    @click="viewCertificate(drawerCertificate.id)"
                                >
                                    View full statement
                                </button>
                            </div>
                        </aside>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script>
import MainLayout from "@/Layouts/MainLayout.vue";
import Header from "@/Components/Header.vue";
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
import { normalizeDateRangeFields, formatDateDdMmYyyy } from "@/utils/financeFilters";

export default {
    components: {
        ViewBanksDialog,
        Header,
        MainLayout,
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
            loadingMoreStatements: false,
            statementsScrollFillDepth: 0,
            selectedStatementId: null,
            drawerLoading: false,
            drawerError: null,
            drawerCertificate: null,
            drawerItems: [],
        };
    },
    computed: {
        drawerRegionLabel() {
            if (this.drawerCertificate) {
                return `Statement ${this.drawerCertificate.id_per_bank}/${this.drawerCertificate.fiscal_year}, lines`;
            }
            return 'Statement lines';
        },
        drawerTotalIncome() {
            return this.drawerItems.reduce((sum, item) => sum + (Number(item.income) || 0), 0);
        },
        drawerTotalExpense() {
            return this.drawerItems.reduce((sum, item) => sum + (Number(item.expense) || 0), 0);
        },
        drawerNetBalance() {
            return this.drawerTotalIncome - this.drawerTotalExpense;
        },
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
    watch: {
        selectedStatementId(val) {
            document.removeEventListener('keydown', this.onDrawerEscape);
            if (val != null) {
                document.addEventListener('keydown', this.onDrawerEscape);
            }
        },
    },
    mounted() {
        this.initFiltersFromUrl();
        this.fetchUniqueBanks();
        this.fetchAvailableYears();
    },
    beforeUnmount() {
        document.removeEventListener('keydown', this.onDrawerEscape);
    },
    methods: {
        onDrawerEscape(e) {
            if (e.key === 'Escape') {
                this.closeStatementDrawer();
            }
        },
        openStatementDrawer(certificate) {
            this.selectedStatementId = certificate.id;
            this.drawerLoading = true;
            this.drawerError = null;
            this.drawerCertificate = null;
            this.drawerItems = [];
            const id = certificate.id;
            Promise.all([
                axios.get(`/statements/${id}`, { headers: { Accept: 'application/json' } }),
                axios.get(`/items/${id}`),
            ])
                .then(([certRes, itemsRes]) => {
                    this.drawerCertificate = certRes.data;
                    this.drawerItems = itemsRes.data || [];
                })
                .catch(() => {
                    this.drawerError = 'Could not load this statement.';
                })
                .finally(() => {
                    this.drawerLoading = false;
                });
        },
        closeStatementDrawer() {
            this.selectedStatementId = null;
            this.drawerLoading = false;
            this.drawerError = null;
            this.drawerCertificate = null;
            this.drawerItems = [];
        },
        formatDrawerNumber(value) {
            return Number(value || 0).toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        },
        formatSignedDrawerBalance(value) {
            if (!value) {
                return this.formatDrawerNumber(0);
            }
            const prefix = value > 0 ? '+' : '-';
            return `${prefix}${this.formatDrawerNumber(Math.abs(value))}`;
        },
        getDrawerClientName(item) {
            return item.client?.name || '';
        },
        formatDateDisplay(date) {
            return formatDateDdMmYyyy(date);
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
            window.history.replaceState({}, '', q ? `/statements?${q}` : '/statements');
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
        async applyFilter(page = 1, { append = false } = {}) {
            try {
                if (append) {
                    this.loadingMoreStatements = true;
                } else {
                    this.statementsScrollFillDepth = 0;
                }
                const params = this.buildBankListParams(page);
                const response = await axios.get('/statements', {
                    params,
                    headers: { Accept: 'application/json' },
                });
                const payload = response.data;
                const chunk = payload.data || [];
                if (append) {
                    this.localCertificates = [...(this.localCertificates || []), ...chunk];
                } else {
                    this.localCertificates = chunk;
                }
                this.paginationState = payload;
                if (
                    this.selectedStatementId != null
                    && !this.localCertificates.some((c) => c.id === this.selectedStatementId)
                ) {
                    this.closeStatementDrawer();
                }
                if (!append) {
                    this.pushBankHistory(this.buildBankListParams(1));
                    this.$nextTick(() => {
                        const el = this.$refs.statementsListScroll;
                        if (el) {
                            el.scrollTop = 0;
                        }
                        this.maybeFillStatementsScroll();
                    });
                } else {
                    this.$nextTick(() => this.maybeFillStatementsScroll());
                }
            } catch (error) {
                console.error(error);
            } finally {
                this.loadingMoreStatements = false;
            }
        },
        onStatementsListScroll(e) {
            const el = e.target;
            if (this.loadingMoreStatements) {
                return;
            }
            const p = this.paginationState;
            const cur = p?.current_page ?? 1;
            const last = p?.last_page ?? 1;
            if (cur >= last) {
                return;
            }
            const threshold = 100;
            if (el.scrollTop + el.clientHeight >= el.scrollHeight - threshold) {
                this.applyFilter(cur + 1, { append: true });
            }
        },
        maybeFillStatementsScroll() {
            const el = this.$refs.statementsListScroll;
            if (!el || this.loadingMoreStatements) {
                return;
            }
            const p = this.paginationState;
            if (!p || (p.current_page ?? 1) >= (p.last_page ?? 1)) {
                return;
            }
            if (this.statementsScrollFillDepth >= 50) {
                return;
            }
            if (el.scrollHeight <= el.clientHeight + 2) {
                this.statementsScrollFillDepth += 1;
                this.applyFilter((p.current_page ?? 1) + 1, { append: true });
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
            this.closeStatementDrawer();
            this.$inertia.visit(`/statements/${id}`);
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

.statement-column {
    width: 140px;
}

.date-column {
    width: 110px;
}

.account-column {
    min-width: 160px;
}

.statement-row {
    cursor: pointer;
    transition: background-color 0.15s ease;
}

.statement-row:hover,
.statement-row:focus-visible {
    background: rgba(255, 255, 255, 0.06);
    outline: none;
}

/* Must beat DataTableShell grid `tr:nth-child(even)` / `tr:hover` specificity */
.statements-list-table.data-table-shell--grid.compact :deep(.data-table-body tr.statement-row--selected),
.statements-list-table.data-table-shell--grid.compact :deep(.data-table-body tr.statement-row--selected:nth-child(even)),
.statements-list-table.data-table-shell--grid.compact :deep(.data-table-body tr.statement-row--selected:hover) {
    position: relative;
    z-index: 1;
    background: linear-gradient(90deg, rgba(59, 130, 246, 0.2) 0%, rgba(30, 41, 59, 0.88) 100%) !important;
    box-shadow:
        inset 4px 0 0 0 #60a5fa,
        inset 0 1px 0 rgba(147, 197, 253, 0.3),
        inset 0 -1px 0 rgba(147, 197, 253, 0.22),
        0 0 0 1px rgba(96, 165, 250, 0.35) !important;
    outline: none;
}

.statements-list-table.data-table-shell--grid.compact :deep(.data-table-body tr.statement-row--selected:hover),
.statements-list-table.data-table-shell--grid.compact :deep(.data-table-body tr.statement-row--selected:focus-visible) {
    background: linear-gradient(90deg, rgba(59, 130, 246, 0.24) 0%, rgba(30, 41, 59, 0.9) 100%) !important;
    box-shadow:
        inset 4px 0 0 0 #93c5fd,
        inset 0 1px 0 rgba(191, 219, 254, 0.34),
        inset 0 -1px 0 rgba(191, 219, 254, 0.24),
        0 0 0 1px rgba(147, 197, 253, 0.45) !important;
    outline: none;
}

.statements-list-table :deep(tr.statement-row--selected .cell-primary) {
    color: #ffffff;
    text-shadow: none;
}

.statements-list-table :deep(tr.statement-row--selected .cell-secondary) {
    color: rgba(219, 234, 254, 0.94);
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

.statements-split {
    display: flex;
    align-items: stretch;
    gap: 0;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    overflow: hidden;
    background: rgba(4, 10, 18, 0.92);
    box-shadow: 0 14px 30px rgba(0, 0, 0, 0.14);
}

.statements-list-col {
    flex: 1 1 45%;
    min-width: 0;
    border-right: 1px solid rgba(255, 255, 255, 0.1);
    display: flex;
    flex-direction: column;
    background: rgba(6, 12, 20, 0.75);
    max-height: min(72vh, 640px);
    min-height: 260px;
}

.statements-list-scroll {
    flex: 1 1 auto;
    min-height: 0;
    overflow-x: hidden;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
}

.statements-scroll-loading {
    padding: 10px 12px;
    text-align: center;
    font-size: 12px;
    color: rgba(255, 255, 255, 0.65);
    border-top: 1px solid rgba(255, 255, 255, 0.06);
    background: rgba(0, 0, 0, 0.2);
}

.statements-list-table {
    flex: 1 1 auto;
    border: none !important;
    border-radius: 0 !important;
    box-shadow: none !important;
    background: transparent !important;
}

.statements-list-table :deep(.data-table) {
    table-layout: fixed;
}

.statements-list-table :deep(col.col-stmt) {
    width: 26%;
}

.statements-list-table :deep(col.col-date) {
    width: 22%;
}

.statements-list-table :deep(col.col-account) {
    width: 52%;
}

.statements-list-table.data-table-shell--grid.compact :deep(.data-table-head th) {
    padding: 5px 8px !important;
    line-height: 1.2;
}

.statements-list-table.data-table-shell--grid.compact :deep(.data-table-body td) {
    padding: 4px 8px !important;
    line-height: 1.3;
    vertical-align: middle;
}

.statements-details-aside {
    flex: 1 1 55%;
    min-width: 240px;
    max-width: 58%;
    position: relative;
    display: flex;
    flex-direction: column;
    background: rgba(4, 10, 18, 0.92);
    min-height: 0;
}

.statements-aside-body {
    flex: 1;
    min-height: 0;
    overflow: auto;
    padding: 8px 8px 8px 8px;
}

.statements-aside-idle {
    min-height: 48px;
}

.statements-aside-idle-text {
    margin: 0;
    padding: 14px 12px;
    font-size: 12px;
    color: rgba(255, 255, 255, 0.5);
}

@media (max-width: 960px) {
    .statements-split {
        flex-direction: column;
    }

    .statements-list-col {
        border-right: none;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        width: 100%;
        max-height: min(52vh, 480px);
    }

    .statements-details-aside {
        max-width: none;
        width: 100%;
        max-height: min(65vh, 560px);
    }
}

.drawer-nr-line {
    display: inline-flex;
    align-items: baseline;
    gap: 6px;
    white-space: nowrap;
    line-height: 1.2;
}

.drawer-nr-main {
    font-size: 11px;
}

.drawer-nr-id {
    font-size: 10px;
    opacity: 0.72;
}

.drawer-cell-tight {
    font-size: 11px;
    line-height: 1.25;
}

.statements-nested-shell {
    border: none !important;
    border-radius: 0 !important;
    box-shadow: none !important;
    background: transparent !important;
}

.statement-drawer-loading,
.statement-drawer-error {
    padding: 14px 8px;
    text-align: center;
    color: rgba(255, 255, 255, 0.75);
    font-size: 12px;
}

.statement-drawer-error {
    color: #f87171;
}

/* Same grid styling as Finance/Certificate.vue statement table */
.drawer-inline-statement-table :deep(th.number-column) {
    width: 92px;
}

.drawer-inline-statement-table :deep(th.client-column) {
    min-width: 140px;
}

.drawer-inline-statement-table.data-table-shell--grid.compact :deep(.data-table-head th) {
    padding: 4px 6px !important;
    font-size: 9px !important;
    line-height: 1.15 !important;
}

.drawer-inline-statement-table.data-table-shell--grid.compact :deep(.data-table-body td) {
    padding: 2px 6px !important;
    font-size: 11px !important;
    line-height: 1.22 !important;
    vertical-align: middle !important;
}

.drawer-inline-statement-table.data-table-shell--grid.compact :deep(.data-table-foot td) {
    padding: 2px 6px !important;
    font-size: 11px !important;
    line-height: 1.22 !important;
    vertical-align: middle !important;
}

.drawer-inline-statement-table.statement-table :deep(.data-table-head th:not(:last-child)),
.drawer-inline-statement-table.statement-table :deep(.data-table-body td:not(:last-child)),
.drawer-inline-statement-table.statement-table :deep(.data-table-foot td:not(:last-child)) {
    border-right: 1px solid rgba(255, 255, 255, 0.14);
}

.footer-balance {
    text-align: right;
    color: rgba(255, 255, 255, 0.84);
}

.drawer-statement-empty-cell {
    padding: 10px 8px !important;
    text-align: center;
    color: rgba(255, 255, 255, 0.66);
    font-size: 11px;
}

.statement-drawer-footer {
    flex-shrink: 0;
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    align-items: center;
    gap: 8px;
    padding: 8px 10px 10px;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.statements-aside-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 5px 12px;
    min-height: 0;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.15s ease, border-color 0.15s ease, filter 0.15s ease;
}

.statements-aside-btn--muted {
    border: 1px solid rgba(255, 255, 255, 0.2);
    background: rgba(255, 255, 255, 0.06);
    color: rgba(255, 255, 255, 0.88);
}

.statements-aside-btn--muted:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.28);
}

.statements-aside-btn--primary {
    border: 1px solid transparent;
    background: $blue;
    color: $white;
}

.statements-aside-btn--primary:hover {
    filter: brightness(1.06);
}
</style>


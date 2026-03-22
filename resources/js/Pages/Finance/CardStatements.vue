<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="CardStatements" subtitle="listofallCardStatements" icon="clientCard.png" link="cardStatements"/>
            <RedirectTabs :route="$page.url" />
            <div class="dark-gray p-2 text-white overflow-x-hidden">
                <div class="form-container p-2">
                    <div class="toolbar-panel">
                        <div class="toolbar-inline">
                            <div class="filter-toolbar-cs">
                                <FinanceCompactSearch
                                    v-model="searchInput"
                                    label="Search"
                                    placeholder="Account, bank, name…"
                                    class="cs-field cs-field--search"
                                    @submit="onSearchSubmit"
                                />
                                <div class="filter-col cs-field cs-field--sort">
                                    <label class="toolbar-label">Order</label>
                                    <select v-model="sortOrder" class="text-black filter-select-compact" @change="applyFilter(1)">
                                        <option value="desc">Newest first</option>
                                        <option value="asc">Oldest first</option>
                                    </select>
                                </div>
                                <FinanceClientSearchSelect
                                    v-model="clientId"
                                    :clients="clients"
                                    label="Client"
                                    class="cs-field cs-field--client"
                                    @change="applyFilter(1)"
                                />
                                <FinanceDateRangeCompact
                                    class="cs-field cs-field--dates"
                                    :date-from="dateFrom"
                                    :date-to="dateTo"
                                    label="Created"
                                    @update:date-from="dateFrom = $event"
                                    @update:date-to="dateTo = $event"
                                    @change="onDateRangeChange"
                                />
                                <FinanceYearMonthSelects
                                    class="cs-field cs-field--ym"
                                    :fiscal-year="fiscalYear"
                                    :month="calendarMonth"
                                    @update:fiscal-year="fiscalYear = $event"
                                    @update:month="calendarMonth = $event"
                                    @change="applyFilter(1)"
                                />
                            </div>
                            <FinancePeriodPresets class="presets-inline-cs" label="" @preset="onPeriodPreset" @clear-dates="onClearDates" />
                        </div>
                    </div>

                    <DataTableShell compact variant="grid">
                        <template #header>
                            <tr>
                                <th class="number-column">Nr</th>
                                <th class="client-column">Client</th>
                                <th>Name</th>
                                <th>Bank</th>
                                <th>Bank Account</th>
                                <th class="actions-column actions-header">Actions</th>
                            </tr>
                        </template>

                        <template v-if="localCardStatements && localCardStatements.length">
                            <tr v-for="(card, index) in localCardStatements" :key="card.id">
                                <td class="cell-secondary">{{ rowNumber(index) }}</td>
                                <td class="client-column-wrap">
                                    <FinanceClientNameCell :name="card?.client?.name || ''" />
                                </td>
                                <td><div class="cell-secondary">{{ card?.name || '—' }}</div></td>
                                <td><div class="cell-secondary">{{ card?.bank || '—' }}</div></td>
                                <td><div class="cell-secondary">{{ card?.account || '—' }}</div></td>
                                <td class="actions-cell">
                                    <button class="view-button" @click="viewCard(card.id)">
                                        <i class="fa fa-eye" aria-hidden="true"></i>
                                        <span>View</span>
                                    </button>
                                </td>
                            </tr>
                        </template>

                        <tr v-else>
                            <td colspan="6" class="empty-cell">
                                No client statements found matching your criteria.
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
import RedirectTabs from "@/Components/RedirectTabs.vue";
import FinanceCompactSearch from "@/Components/Finance/FinanceCompactSearch.vue";
import FinanceClientSearchSelect from "@/Components/Finance/FinanceClientSearchSelect.vue";
import FinanceDateRangeCompact from "@/Components/Finance/FinanceDateRangeCompact.vue";
import FinanceYearMonthSelects from "@/Components/Finance/FinanceYearMonthSelects.vue";
import FinancePeriodPresets from "@/Components/Finance/FinancePeriodPresets.vue";
import FinanceClientNameCell from "@/Components/Finance/FinanceClientNameCell.vue";
import { normalizeDateRangeFields } from "@/utils/financeFilters";

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
        clientCards: Object,
        clients: {
            type: Array,
            default: () => [],
        },
    },
    watch: {
        clientCards: {
            handler(value) {
                this.localCardStatements = value?.data || [];
                this.paginationState = value || { data: [], links: [] };
            },
            deep: true,
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
            localCardStatements: this.clientCards?.data || [],
            paginationState: this.clientCards || { data: [], links: [] },
            perPage: 20,
        };
    },
    mounted() {
        this.initFiltersFromUrl();
        const page = parseInt(new URLSearchParams(window.location.search).get('page') || '1', 10) || 1;
        this.applyFilter(page);
    },
    methods: {
        getRequestParams(page = 1) {
            const state = { dateFrom: this.dateFrom, dateTo: this.dateTo };
            normalizeDateRangeFields(state);
            this.dateFrom = state.dateFrom;
            this.dateTo = state.dateTo;
            const params = {
                sortOrder: this.sortOrder || undefined,
                per_page: this.perPage,
                page,
            };
            if (this.searchQuery) {
                params.searchQuery = this.searchQuery;
            }
            if (this.clientId != null) {
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
        getRedirectUrl(page = 1) {
            const p = this.getRequestParams(page);
            const parts = [];
            if (p.searchQuery) {
                parts.push(`searchQuery=${encodeURIComponent(p.searchQuery)}`);
            }
            if (p.sortOrder) {
                parts.push(`sortOrder=${p.sortOrder}`);
            }
            if (p.client_id) {
                parts.push(`client_id=${p.client_id}`);
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
            parts.push(`per_page=${this.perPage}`);
            if (page && page !== 1) {
                parts.push(`page=${page}`);
            }
            return parts.length ? `/cardStatements?${parts.join('&')}` : '/cardStatements';
        },
        initFiltersFromUrl() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('searchQuery')) {
                const sq = urlParams.get('searchQuery');
                this.searchInput = sq;
                this.searchQuery = sq;
            }
            if (urlParams.has('sortOrder')) {
                this.sortOrder = urlParams.get('sortOrder');
            }
            const cid = urlParams.get('client_id') || urlParams.get('client');
            if (cid && cid !== 'All') {
                const list = this.clients || [];
                const matchingClient = list.find((c) => String(c.id) === String(cid));
                if (matchingClient) {
                    this.clientId = matchingClient.id;
                } else {
                    const n = parseInt(cid, 10);
                    this.clientId = Number.isNaN(n) ? null : n;
                }
            } else {
                this.clientId = null;
            }
            this.dateFrom = urlParams.get('date_from') || '';
            this.dateTo = urlParams.get('date_to') || '';
            const fy = urlParams.get('fiscal_year');
            this.fiscalYear = fy !== null && fy !== '' ? parseInt(fy, 10) : '';
            if (Number.isNaN(this.fiscalYear)) {
                this.fiscalYear = '';
            }
            const mo = urlParams.get('month');
            this.calendarMonth = mo !== null && mo !== '' ? parseInt(mo, 10) : '';
            if (Number.isNaN(this.calendarMonth)) {
                this.calendarMonth = '';
            }
            const pp = urlParams.get('per_page');
            if (pp) {
                const n = parseInt(pp, 10);
                if (!Number.isNaN(n) && n > 0) {
                    this.perPage = Math.min(200, n);
                }
            }
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
        async applyFilter(page = 1) {
            try {
                const response = await axios.get('/cardStatements', {
                    params: this.getRequestParams(page),
                    headers: { Accept: 'application/json' },
                });
                this.localCardStatements = response.data.data;
                this.paginationState = response.data;
                window.history.pushState({}, '', this.getRedirectUrl(page));
            } catch (error) {
                console.error(error);
            }
        },
        viewCard(id) {
            this.$inertia.visit(`/cardStatement/${id}`);
        },
        goToPage(page) {
            this.applyFilter(page);
        },
    },
};
</script>
<style scoped lang="scss">
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

.filter-toolbar-cs {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 10px 12px;
    flex: 1 1 auto;
    min-width: 0;
}

.filter-toolbar-cs .cs-field--search {
    flex: 0 1 200px;
    min-width: 160px;
    max-width: 280px;
}

.filter-toolbar-cs .cs-field--sort {
    flex: 0 0 130px;
    min-width: 120px;
}

.filter-toolbar-cs .cs-field--client {
    flex: 0 1 200px;
    min-width: 160px;
}

.filter-toolbar-cs .cs-field--dates {
    flex: 1 1 280px;
    min-width: 260px;
    max-width: 340px;
}

.filter-toolbar-cs .cs-field--ym {
    flex: 0 1 260px;
    min-width: 240px;
}

.presets-inline-cs {
    flex: 0 0 auto;
    align-self: flex-end;
    min-width: 0;
}

@media (min-width: 900px) {
    .presets-inline-cs :deep(.fc-pp__row) {
        flex-wrap: nowrap;
    }
}

@media (max-width: 1100px) {
    .filter-toolbar-cs .cs-field--dates {
        flex-basis: 100%;
        max-width: none;
    }

    .filter-toolbar-cs .cs-field--ym {
        flex-basis: 100%;
    }
}

.filter-col {
    min-width: 0;
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
    color: #111827;
}

.dark-gray {
    background-color: $dark-gray;
    justify-content: left;
    border-radius: 8px;
    align-items: center;
    min-height: 20vh;
    min-width: 0;
}

.btn {
    padding: 9px 12px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    border-radius: 8px;
}

.toolbar-search-button {
    min-width: 110px;
    border: 1px solid rgba(96, 165, 250, 0.45);
    background: $blue;
    color: white;
}

.number-column {
    width: 70px;
}

.client-column {
    min-width: 160px;
    max-width: 280px;
    width: 22%;
}

.client-column-wrap {
    max-width: 280px;
}

.actions-column {
    width: 120px;
}

.actions-header {
    text-align: right;
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

@media (max-width: 1024px) {
    .filters-group {
        justify-content: flex-start;
    }
}

@media (max-width: 768px) {
    .search-row {
        flex-direction: column;
    }

    .search-input,
    .filter-select,
    .filter-field {
        width: 100%;
    }
}
</style>


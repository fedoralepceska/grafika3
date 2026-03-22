<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="invoice2" subtitle="allTradeInvoices" icon="invoice.png" link="trade-invoices" />
            <RedirectTabs :route="$page.url" />
            <div class="dark-gray p-2 text-white overflow-x-hidden">
                <div class="form-container p-2">
                    <div class="toolbar-panel">
                        <div class="toolbar-inline">
                            <div class="filter-toolbar-trade">
                                <FinanceCompactSearch
                                    v-model="searchInput"
                                    label="Invoice #"
                                    placeholder="Number…"
                                    class="trade-field trade-field--search"
                                    @submit="onSearchSubmit"
                                />
                                <div class="filter-col trade-field trade-field--sort">
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
                                    class="trade-field trade-field--client"
                                    @change="applyFilter(1)"
                                />
                                <div class="filter-col trade-field trade-field--warehouse">
                                    <label class="toolbar-label">Warehouse</label>
                                    <select v-model="filters.warehouse_id" class="text-black filter-select-compact" @change="applyFilter(1)">
                                        <option value="All" hidden>Warehouses</option>
                                        <option value="All">All Warehouses</option>
                                        <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">
                                            {{ warehouse.name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="filter-col trade-field trade-field--status">
                                    <label class="toolbar-label">Status</label>
                                    <select v-model="filters.status" class="text-black filter-select-compact" @change="applyFilter(1)">
                                        <option value="All" hidden>Status</option>
                                        <option value="All">All Status</option>
                                        <option value="draft">Draft</option>
                                        <option value="sent">Sent</option>
                                        <option value="paid">Paid</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>
                                <FinanceDateRangeCompact
                                    class="trade-field trade-field--dates"
                                    :date-from="filters.from_date"
                                    :date-to="filters.to_date"
                                    label="Invoice date"
                                    @update:date-from="filters.from_date = $event"
                                    @update:date-to="filters.to_date = $event"
                                    @change="onDateRangeChange"
                                />
                                <FinanceYearMonthSelects
                                    class="trade-field trade-field--ym"
                                    :fiscal-year="fiscalYear"
                                    :month="calendarMonth"
                                    @update:fiscal-year="fiscalYear = $event"
                                    @update:month="calendarMonth = $event"
                                    @change="applyFilter(1)"
                                />
                            </div>
                            <FinancePeriodPresets class="presets-inline-trade" label="" @preset="onPeriodPreset" @clear-dates="onClearDates" />
                        </div>

                        <div class="toolbar-actions">
                            <button @click="createInvoice" class="btn create-order1">Create Invoice</button>
                        </div>
                    </div>

                    <DataTableShell compact variant="grid">
                        <template #header>
                            <tr>
                                <th class="number-column">{{$t('Nr')}}</th>
                                <th class="invoice-column">Invoice</th>
                                <th>{{$t('date')}}</th>
                                <th class="customer-column">{{$t('client')}}</th>
                                <th>{{$t('warehouse')}}</th>
                                <th class="text-right">{{$t('subtotal')}} (.ден)</th>
                                <th class="text-right">{{$t('VAT')}} (%)</th>
                                <th class="text-right">{{$t('total')}} (.ден)</th>
                                <th>Status</th>
                                <th>Created By</th>
                                <th class="actions-column actions-header">{{$t('ACTIONS')}}</th>
                            </tr>
                        </template>

                        <template v-if="localInvoices && localInvoices.length > 0">
                            <tr v-for="(invoice, index) in localInvoices" :key="invoice.id">
                                <td class="cell-secondary">{{ rowNumber(index) }}</td>
                                <td class="invoice-primary-cell">
                                    <div class="cell-primary">#{{ invoice.invoice_number }}</div>
                                </td>
                                <td><div class="cell-secondary">{{ formatDateDisplay(invoice.invoice_date) }}</div></td>
                                <td class="customer-column">
                                    <FinanceClientNameCell :name="invoice.client?.name || ''" />
                                </td>
                                <td><div class="cell-secondary">{{ invoice.warehouse?.name || 'N/A' }}</div></td>
                                <td><div class="cell-secondary text-right">{{ formatNumber(invoice.subtotal) }}</div></td>
                                <td><div class="cell-secondary text-right">{{ formatPercent(computedVatPercent(invoice)) }}</div></td>
                                <td><div class="cell-primary text-right">{{ formatNumber(computedTotal(invoice)) }}</div></td>
                                <td>
                                    <span :class="['status-badge', getStatusBadgeClass(invoice.status)]">
                                        {{ formatStatus(invoice.status) }}
                                    </span>
                                </td>
                                <td><div class="cell-secondary">{{ invoice.created_by?.name || 'N/A' }}</div></td>
                                <td class="actions-cell">
                                    <div class="action-buttons">
                                        <button @click="openViewModal(invoice)" class="table-action-button">{{ $t('View') }}</button>
                                        <ActionDropdown
                                            icon-only
                                            trigger-title="Invoice actions"
                                            :groups="getActionMenuGroups(invoice)"
                                            @select="handleActionMenuSelect(invoice, $event)"
                                        />
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <tr v-else>
                            <td colspan="11" class="empty-cell">
                                No trade invoices found matching your criteria.
                            </td>
                        </tr>
                    </DataTableShell>

                    <Pagination :pagination="paginationState" class="mt-2" @pagination-change-page="goToPage" />
                </div>
            </div>
        </div>

        <!-- View Modal -->
        <div v-if="showViewModal" class="modal-overlay" @click="closeViewModal">
            <div class="modal-content" @click.stop>
                <div class="modal-header">
                    <h3>Invoice #{{ selectedInvoice?.invoice_number }}</h3>
                    <button @click="closeViewModal" class="close-btn">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="info-card">
                            <div class="info-label">Client</div>
                            <div class="info-value">{{ selectedInvoice?.client?.name }}</div>
                        </div>
                        <div class="info-card">
                            <div class="info-label">Warehouse</div>
                            <div class="info-value">{{ selectedInvoice?.warehouse?.name }}</div>
                        </div>
                        <div class="info-card">
                            <div class="info-label">Date</div>
                            <div class="info-value">{{ selectedInvoice?.invoice_date }}</div>
                        </div>
                        <div class="info-card">
                            <div class="info-label">Status</div>
                            <div class="info-value">{{ selectedInvoice?.status }}</div>
                        </div>
                    </div>

                    <table class="modal-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Code</th>
                                <th>Article</th>
                                <th>Qty</th>
                                <th>Unit Price</th>
                                <th>VAT %</th>
                                <th>Amount</th>
                                <th>VAT</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, idx) in selectedInvoice?.items" :key="item.id">
                                <td>{{ idx + 1 }}</td>
                                <td>{{ item.article?.code }}</td>
                                <td>{{ item.article?.name }}</td>
                                <td>{{ formatNumber(item.quantity) }}</td>
                                <td>{{ formatNumber(item.unit_price) }}</td>
                                <td>{{ taxTypePercent(item.tax_type) }}%</td>
                                <td>{{ formatNumber(item.line_total) }}</td>
                                <td>{{ formatNumber(item.vat_amount) }}</td>
                                <td>{{ formatNumber(Number(item.line_total) + Number(item.vat_amount)) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="totals-section">
                        <div class="total-row">
                            <span>Subtotal:</span>
                            <span>{{ formatNumber(selectedInvoice?.subtotal) }}</span>
                        </div>
                        <div class="total-row">
                            <span>VAT:</span>
                            <span>{{ formatNumber(selectedInvoice?.vat_amount) }}</span>
                        </div>
                        <div class="total-row total-final">
                            <span>Total:</span>
                            <span>{{ formatNumber(selectedInvoice?.total_amount) }}</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button @click="downloadPdf(selectedInvoice?.id)" class="btn purple">PDF</button>
                    <button @click="closeViewModal" class="btn gray">Close</button>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <div v-if="showEditModal" class="modal-overlay" @click="closeEditModal">
            <div class="modal-content large" @click.stop>
                <div class="modal-header">
                    <h3>Edit Invoice #{{ editForm.invoice_number }}</h3>
                    <button @click="closeEditModal" class="close-btn">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="grid grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="form-label">Client</label>
                            <select v-model="editForm.client_id" class="form-input">
                                <option v-for="client in clients" :key="client.id" :value="client.id">
                                    {{ client.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Warehouse</label>
                            <select v-model="editForm.warehouse_id" class="form-input" @change="loadAvailableArticles">
                                <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">
                                    {{ warehouse.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Date</label>
                            <input type="date" v-model="editForm.invoice_date" class="form-input">
                        </div>
                    </div>

                    <div class="flex gap-2 mb-4">
                        <button @click="addEditRow" class="btn blue">Add Row</button>
                        <button @click="removeEditRow" class="btn red">Remove Row</button>
                    </div>

                    <table class="modal-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Code</th>
                                <th>Article</th>
                                <th>Qty</th>
                                <th>Unit Price</th>
                                <th>VAT %</th>
                                <th>Amount</th>
                                <th>VAT</th>
                                <th>Total</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, idx) in editForm.items" :key="item._key">
                                <td>{{ idx + 1 }}</td>
                                <td>
                                    <select v-model="item.article_id" @change="onEditArticleChange(idx)" class="table-input">
                                        <option value="">Select</option>
                                        <option v-for="article in availableArticles" :key="article.id" :value="article.id">
                                            {{ article.code }}
                                        </option>
                                    </select>
                                </td>
                                <td>{{ item.article?.name || '' }}</td>
                                <td>
                                    <input type="number" min="0.00001" step="0.00001" v-model.number="item.quantity" 
                                           @input="recalcEditRow(idx)" class="table-input">
                                </td>
                                <td>
                                    <input type="number" min="0.01" step="0.01" v-model.number="item.unit_price" 
                                           @input="recalcEditRow(idx)" class="table-input">
                                </td>
                                <td>
                                    <select v-model.number="item.tax_type" @change="recalcEditRow(idx)" class="table-input">
                                        <option :value="1">18%</option>
                                        <option :value="2">5%</option>
                                        <option :value="3">10%</option>
                                        <option :value="0">0%</option>
                                    </select>
                                </td>
                                <td>{{ formatNumber(item.line_total) }}</td>
                                <td>{{ formatNumber(item.vat_amount) }}</td>
                                <td>{{ formatNumber(Number(item.line_total) + Number(item.vat_amount)) }}</td>
                                <td>
                                    <button @click="removeEditRowAt(idx)" class="btn delete btn-sm">Remove</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="totals-section">
                        <div class="total-row">
                            <span>Subtotal:</span>
                            <span>{{ formatNumber(editTotals.subtotal) }}</span>
                        </div>
                        <div class="total-row">
                            <span>VAT:</span>
                            <span>{{ formatNumber(editTotals.vat) }}</span>
                        </div>
                        <div class="total-row total-final">
                            <span>Total:</span>
                            <span>{{ formatNumber(editTotals.total) }}</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button @click="saveEdit" class="btn green" :disabled="!canSaveEdit">Save</button>
                    <button @click="closeEditModal" class="btn gray">Cancel</button>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script>
import MainLayout from "@/Layouts/MainLayout.vue";
import Pagination from "@/Components/Pagination.vue";
import Header from "@/Components/Header.vue";
import RedirectTabs from "@/Components/RedirectTabs.vue";
import DataTableShell from "@/Components/DataTableShell.vue";
import ActionDropdown from "@/Components/ActionDropdown.vue";
import axios from "axios";
import { useToast } from "vue-toastification";
import FinanceCompactSearch from "@/Components/Finance/FinanceCompactSearch.vue";
import FinanceClientSearchSelect from "@/Components/Finance/FinanceClientSearchSelect.vue";
import FinanceDateRangeCompact from "@/Components/Finance/FinanceDateRangeCompact.vue";
import FinanceYearMonthSelects from "@/Components/Finance/FinanceYearMonthSelects.vue";
import FinancePeriodPresets from "@/Components/Finance/FinancePeriodPresets.vue";
import { normalizeDateRangeFields } from "@/utils/financeFilters";

export default {
    components: {
        MainLayout,
        Pagination,
        Header,
        RedirectTabs,
        DataTableShell,
        ActionDropdown,
        FinanceCompactSearch,
        FinanceClientSearchSelect,
        FinanceDateRangeCompact,
        FinanceYearMonthSelects,
        FinancePeriodPresets,
    },
    props: {
        invoices: Object,
        clients: Array,
        warehouses: Array,
    },
    data() {
        return {
            localInvoices: this.invoices?.data || [],
            paginationState: this.invoices || { data: [], links: [] },
            searchInput: '',
            searchQuery: '',
            clientId: null,
            filters: {
                warehouse_id: 'All',
                status: 'All',
                from_date: '',
                to_date: '',
            },
            fiscalYear: '',
            calendarMonth: '',
            sortOrder: 'desc',
            perPage: 20,
            // Modal states
            showViewModal: false,
            showEditModal: false,
            selectedInvoice: null,
            editForm: {
                id: null,
                invoice_number: '',
                client_id: null,
                warehouse_id: null,
                invoice_date: '',
                items: []
            },
            availableArticles: []
        };
    },
    setup() {
        const toast = useToast();
        return { toast };
    },
    mounted() {
        this.initFromUrl();
        const page = parseInt(new URLSearchParams(window.location.search).get('page') || '1', 10) || 1;
        this.applyFilter(page);
    },
    computed: {
        editTotals() {
            let subtotal = 0, vat = 0;
            this.editForm.items.forEach(item => {
                subtotal += Number(item.line_total || 0);
                vat += Number(item.vat_amount || 0);
            });
            return { subtotal, vat, total: subtotal + vat };
        },
        canSaveEdit() {
            return this.editForm.client_id && 
                   this.editForm.warehouse_id && 
                   this.editForm.items.length > 0 && 
                   this.editForm.items.some(item => item.article_id && item.quantity > 0);
        }
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
            this.filters.warehouse_id = p.get('warehouse_id') || 'All';
            this.filters.status = p.get('status') || 'All';
            this.filters.from_date = p.get('from_date') || p.get('date_from') || '';
            this.filters.to_date = p.get('to_date') || p.get('date_to') || '';
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
            this.sortOrder = p.get('sortOrder') || 'desc';
            const pp = p.get('per_page');
            if (pp) {
                const n = parseInt(pp, 10);
                if (!Number.isNaN(n) && n > 0) {
                    this.perPage = Math.min(200, n);
                }
            }
        },
        buildTradeListParams(page) {
            const state = { dateFrom: this.filters.from_date, dateTo: this.filters.to_date };
            normalizeDateRangeFields(state);
            this.filters.from_date = state.dateFrom;
            this.filters.to_date = state.dateTo;
            const params = {
                page,
                per_page: this.perPage,
                sortOrder: this.sortOrder,
                client_id: this.clientId != null ? this.clientId : 'All',
                warehouse_id: this.filters.warehouse_id,
                status: this.filters.status,
                from_date: this.filters.from_date || undefined,
                to_date: this.filters.to_date || undefined,
            };
            if (this.searchQuery) {
                params.searchQuery = this.searchQuery;
            }
            if (this.fiscalYear !== '' && this.fiscalYear != null) {
                params.fiscal_year = this.fiscalYear;
            }
            if (this.calendarMonth !== '' && this.calendarMonth != null) {
                params.month = this.calendarMonth;
            }
            return params;
        },
        pushTradeHistory(params) {
            const qs = new URLSearchParams();
            Object.entries(params).forEach(([k, v]) => {
                if (v !== '' && v !== null && v !== undefined) {
                    qs.set(k, String(v));
                }
            });
            window.history.pushState({}, '', `/trade-invoices?${qs.toString()}`);
        },
        onSearchSubmit() {
            this.searchQuery = (this.searchInput || '').trim();
            this.applyFilter(1);
        },
        onDateRangeChange() {
            const state = { dateFrom: this.filters.from_date, dateTo: this.filters.to_date };
            normalizeDateRangeFields(state);
            this.filters.from_date = state.dateFrom;
            this.filters.to_date = state.dateTo;
            this.applyFilter(1);
        },
        onPeriodPreset(type) {
            const d = new Date();
            const y = d.getFullYear();
            const m = String(d.getMonth() + 1).padStart(2, '0');
            const today = `${y}-${m}-${String(d.getDate()).padStart(2, '0')}`;
            if (type === 'this_month') {
                this.filters.from_date = `${y}-${m}-01`;
                this.filters.to_date = today;
                this.fiscalYear = '';
                this.calendarMonth = '';
            } else if (type === 'this_year') {
                this.filters.from_date = `${y}-01-01`;
                this.filters.to_date = today;
                this.fiscalYear = '';
                this.calendarMonth = '';
            }
            this.applyFilter(1);
        },
        onClearDates() {
            this.filters.from_date = '';
            this.filters.to_date = '';
            this.applyFilter(1);
        },
        rowNumber(index) {
            const currentPage = this.paginationState?.current_page || 1;
            const perPage = this.paginationState?.per_page || 20;
            return ((currentPage - 1) * perPage) + index + 1;
        },
        computedVatPercent(invoice) {
            if (!invoice.items || !Array.isArray(invoice.items) || invoice.items.length === 0) {
                // If server only provided totals, infer a weighted VAT% if possible
                const subtotal = Number(invoice.subtotal || 0);
                const vatAmt = Number(invoice.vat_amount || 0);
                if (subtotal > 0) {
                    return (vatAmt / subtotal) * 100;
                }
                return 0;
            }
            // Compute weighted VAT% across items
            const taxMap = { 1: 18, 2: 5, 3: 10 };
            let subtotal = 0;
            let vatAmt = 0;
            invoice.items.forEach(item => {
                const line = Number(item.line_total ?? (Number(item.quantity || 0) * Number(item.unit_price || 0)));
                const rate = (taxMap[item.tax_type] ?? 0) / 100;
                subtotal += line;
                vatAmt += line * rate;
            });
            if (subtotal === 0) return 0;
            return (vatAmt / subtotal) * 100;
        },
        computedVat(invoice) {
            // Prefer server totals when items not present
            if (!invoice.items || !Array.isArray(invoice.items) || invoice.items.length === 0) {
                return Number(invoice.vat_amount || 0);
            }
            const taxMap = { 1: 18, 2: 5, 3: 10 };
            let vat = 0;
            invoice.items.forEach(item => {
                const lineTotal = Number(item.line_total ?? (Number(item.quantity || 0) * Number(item.unit_price || 0)));
                const vatRate = taxMap[item.tax_type] ?? 0;
                vat += lineTotal * (vatRate / 100);
            });
            return vat;
        },
        computedTotal(invoice) {
            if (!invoice.items || !Array.isArray(invoice.items) || invoice.items.length === 0) {
                return Number(invoice.total_amount || 0);
            }
            let subtotal = 0;
            invoice.items.forEach(item => {
                subtotal += Number(item.line_total ?? (Number(item.quantity || 0) * Number(item.unit_price || 0)));
            });
            return subtotal + this.computedVat(invoice);
        },
        formatNumber(number) {
            return Number(number).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        formatPercent(num) {
            return `${Number(num).toFixed(2)}%`;
        },
        formatDateDisplay(date) {
            if (!date) return 'N/A';
            return new Date(date).toLocaleDateString('en-GB');
        },
        formatStatus(status) {
            return status.charAt(0).toUpperCase() + status.slice(1);
        },
        getStatusClass(status) {
            const classes = {
                'draft': 'status-draft',
                'sent': 'status-sent',
                'paid': 'status-paid',
                'cancelled': 'status-cancelled'
            };
            return classes[status] || '';
        },
        getStatusBadgeClass(status) {
            const classes = {
                'draft': 'status-draft',
                'sent': 'status-sent',
                'paid': 'status-paid',
                'cancelled': 'status-cancelled'
            };
            return classes[status] || 'status-default';
        },
        getActionMenuGroups(invoice) {
            const items = [
                { label: 'PDF', value: 'pdf' },
            ];

            if (invoice.status === 'draft') {
                items.push({ label: this.$t('Edit'), value: 'edit' });
                items.push({ label: 'Send', value: 'sent' });
            }

            if (invoice.status === 'sent') {
                items.push({ label: 'Mark Paid', value: 'paid' });
            }

            if (['draft', 'sent'].includes(invoice.status)) {
                items.push({ label: 'Cancel', value: 'cancelled', danger: true });
            }

            return items.length ? [{ label: 'Invoice Actions', items }] : [];
        },
        handleActionMenuSelect(invoice, item) {
            if (item.value === 'edit') {
                this.openEditModal(invoice);
                return;
            }

            if (item.value === 'pdf') {
                this.downloadPdf(invoice.id);
                return;
            }

            this.updateStatus(invoice.id, item.value);
        },
        applyFilter(page = 1) {
            const params = this.buildTradeListParams(page);
            axios.get('/trade-invoices', { params })
                .then(response => {
                    this.localInvoices = response.data.data;
                    this.paginationState = response.data;
                    this.pushTradeHistory(params);
                })
                .catch(error => {
                    console.error('Error applying filters:', error);
                    this.toast.error('Error applying filters');
                });
        },
        createInvoice() {
            this.$inertia.visit('/trade-invoices/create');
        },
        goToPage(page) {
            this.applyFilter(page);
        },
        viewInvoice(invoiceId) {
            this.$inertia.visit(`/trade-invoices/${invoiceId}`);
        },
        editInvoice(invoiceId) {
            this.$inertia.visit(`/trade-invoices/${invoiceId}/edit`);
        },
        downloadPdf(invoiceId) {
            window.open(`/trade-invoices/${invoiceId}/pdf`, '_blank');
        },
        updateStatus(invoiceId, status) {
            axios.put(`/trade-invoices/${invoiceId}/status`, { status })
                .then(response => {
                    this.toast.success('Invoice status updated successfully');
                    // Update local data
                    const invoice = this.localInvoices.find(inv => inv.id === invoiceId);
                    if (invoice) {
                        invoice.status = status;
                    }
                })
                .catch(error => {
                    console.error('Error updating status:', error);
                    this.toast.error('Error updating invoice status');
                });
        },
        // Modal methods
        openViewModal(invoice) {
            this.selectedInvoice = invoice;
            this.showViewModal = true;
        },
        closeViewModal() {
            this.showViewModal = false;
            this.selectedInvoice = null;
        },
        openEditModal(invoice) {
            this.editForm = {
                id: invoice.id,
                invoice_number: invoice.invoice_number,
                client_id: invoice.client_id,
                warehouse_id: invoice.warehouse_id,
                invoice_date: this.formatDateForInput(invoice.invoice_date),
                items: (invoice.items || []).map(item => ({
                    ...item,
                    _key: `${item.id || Math.random()}`,
                    article: item.article || null,
                    line_total: Number(item.line_total || 0),
                    vat_amount: Number(item.vat_amount || 0)
                }))
            };
            this.showEditModal = true;
            this.loadAvailableArticles();
        },
        closeEditModal() {
            this.showEditModal = false;
            this.editForm = {
                id: null,
                invoice_number: '',
                client_id: null,
                warehouse_id: null,
                invoice_date: '',
                items: []
            };
            this.availableArticles = [];
        },
        async loadAvailableArticles() {
            if (!this.editForm.warehouse_id) return;
            try {
                const response = await axios.get(`/trade-invoices/${this.editForm.warehouse_id}/available-articles`);
                this.availableArticles = response.data;
            } catch (error) {
                console.error('Error loading available articles:', error);
                this.toast.error('Failed to load available articles');
            }
        },
        addEditRow() {
            this.editForm.items.push({
                _key: Math.random(),
                article_id: '',
                article: null,
                quantity: 1,
                unit_price: 0,
                tax_type: 1,
                line_total: 0,
                vat_amount: 0
            });
        },
        removeEditRow() {
            if (this.editForm.items.length > 0) {
                this.editForm.items.pop();
            }
        },
        removeEditRowAt(index) {
            this.editForm.items.splice(index, 1);
        },
        onEditArticleChange(index) {
            const item = this.editForm.items[index];
            const article = this.availableArticles.find(a => a.id === item.article_id);
            if (article) {
                item.article = { 
                    id: article.id,
                    name: article.name, 
                    code: article.code 
                };
                item.unit_price = article.selling_price || article.purchase_price;
                item.tax_type = article.tax_type;
                this.recalcEditRow(index);
            }
        },
        recalcEditRow(index) {
            const item = this.editForm.items[index];
            const lineTotal = Number(item.quantity || 0) * Number(item.unit_price || 0);
            const vatAmount = lineTotal * (this.taxTypePercent(item.tax_type) / 100);
            item.line_total = lineTotal;
            item.vat_amount = vatAmount;
        },
        taxTypePercent(taxType) {
            const map = { 1: 18, 2: 5, 3: 10 };
            return map[taxType] || 0;
        },
        formatDateForInput(dateString) {
            if (!dateString) return '';
            // Convert date to YYYY-MM-DD format for HTML date input
            const date = new Date(dateString);
            if (isNaN(date.getTime())) return '';
            return date.toISOString().split('T')[0];
        },
        async saveEdit() {
            if (!this.canSaveEdit) {
                this.toast.error('Please fill in all required fields');
                return;
            }

            const payload = {
                client_id: this.editForm.client_id,
                warehouse_id: this.editForm.warehouse_id,
                invoice_date: this.editForm.invoice_date,
                notes: '',
                items: this.editForm.items
                    .filter(item => item.article_id && item.quantity > 0)
                    .map(item => ({
                        article_id: item.article_id,
                        quantity: item.quantity,
                        unit_price: item.unit_price,
                        tax_type: item.tax_type
                    }))
            };

            try {
                await axios.put(`/trade-invoices/${this.editForm.id}`, payload);
                this.toast.success('Invoice updated successfully');
                this.closeEditModal();
                this.applyFilter(this.paginationState?.current_page || 1); // Refresh the list
            } catch (error) {
                console.error('Error updating invoice:', error);
                this.toast.error(error.response?.data?.error || 'Failed to update invoice');
            }
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

/* Flex toolbar: one horizontal flow; min-widths stop date/year controls from stacking */
.filter-toolbar-trade {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 10px 12px;
    flex: 1 1 auto;
    min-width: 0;
}

.filter-toolbar-trade .trade-field--search {
    flex: 0 1 170px;
    min-width: 140px;
    max-width: 220px;
}

.filter-toolbar-trade .trade-field--sort {
    flex: 0 0 130px;
    min-width: 120px;
}

.filter-toolbar-trade .trade-field--client {
    flex: 0 1 200px;
    min-width: 160px;
}

.filter-toolbar-trade .trade-field--warehouse {
    flex: 0 1 140px;
    min-width: 120px;
}

.filter-toolbar-trade .trade-field--status {
    flex: 0 1 130px;
    min-width: 110px;
}

.filter-toolbar-trade .trade-field--dates {
    flex: 1 1 280px;
    min-width: 260px;
    max-width: 340px;
}

.filter-toolbar-trade .trade-field--ym {
    flex: 0 1 260px;
    min-width: 240px;
}

.presets-inline-trade {
    flex: 0 0 auto;
    align-self: flex-end;
}

@media (max-width: 1100px) {
    .filter-toolbar-trade .trade-field--dates {
        flex-basis: 100%;
        max-width: none;
    }

    .filter-toolbar-trade .trade-field--ym {
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

.number-column {
    width: 70px;
}

.customer-column {
    max-width: 280px;
    width: 18%;
}

.invoice-column {
    width: 150px;
}

.actions-column {
    width: 190px;
}

.invoice-primary-cell {
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

.text-right {
    text-align: right;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
}

.actions-cell {
    text-align: right;
}

.actions-header {
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

.filters {
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 15px;
    
    @media (max-width: 768px) {
        flex-direction: column;
        align-items: stretch;
    }
}

.search-container {
    @media (max-width: 768px) {
        width: 100%;
        margin-bottom: 10px;
    }
}

.date-filters {
    @media (max-width: 768px) {
        flex-direction: column;
        width: 100%;
    }
    
    @media (max-width: 480px) {
        gap: 8px;
    }
}

input[type="date"] {
    width: 240px;
    
    @media (max-width: 768px) {
        width: 100%;
    }
    
    @media (max-width: 480px) {
        width: 100%;
        font-size: 14px;
    }
}

select {
    width: 240px;
    
    @media (max-width: 768px) {
        width: 100%;
    }
    
    @media (max-width: 480px) {
        width: 100%;
        font-size: 14px;
    }
}


.btn {
    padding: 9px 12px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    border-radius: 2px;
    font-size: 14px;
    
    @media (max-width: 480px) {
        padding: 8px 10px;
        font-size: 12px;
    }
}

.btn-sm {
    padding: 6px 8px;
    font-size: 12px;
    
    @media (max-width: 480px) {
        padding: 4px 6px;
        font-size: 10px;
    }
}

.action-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    justify-content: flex-end;
    align-items: center;
    
    @media (max-width: 768px) {
        flex-direction: column;
        gap: 2px;
    }
    
    @media (max-width: 480px) {
        gap: 1px;
    }
}

.dropdown {
    position: relative;
}

.dropdown .gray {
    background-color: #6b7280;
    color: white;
}

.dropdown-menu {
    background: #1f2937;
    border: 1px solid #374151;
    min-width: 140px;
    z-index: 10;
    display: flex;
    flex-direction: column;
}

.dropdown-item {
    padding: 8px 10px;
    color: white;
    text-align: left;
    background: transparent;
    border: none;
    cursor: pointer;
}

.dropdown-item:hover {
    background: #374151;
}

.dropdown-item.danger {
    color: #ef4444;
}

.create-order1 {
    background-color: $blue;
    color: white;
}

.delete {
    border: none;
    color: white;
    background-color: $red;
}

.delete:hover {
    background-color: darkred;
}

.green {
    background-color: $green;
    color: white;
}

.green:hover {
    background-color: darkgreen;
}

.blue {
    background-color: $blue;
    border: none;
    color: white;
}

.blue:hover {
    background-color: cornflowerblue;
}

.orange {
    background-color: orange;
    color: white;
}

.orange:hover {
    background-color: darkorange;
}

.purple {
    background-color: #8b5cf6;
    color: white;
}

.purple:hover {
    background-color: #7c3aed;
}

/* Status classes */
.status-draft {
    color: #9ca3af;
    background: rgba(156, 163, 175, 0.14);
    border: 1px solid rgba(156, 163, 175, 0.28);
}

.status-sent {
    color: #f59e0b;
    background: rgba(245, 158, 11, 0.14);
    border: 1px solid rgba(245, 158, 11, 0.28);
}

.status-paid {
    color: #4ade80;
    background: rgba(74, 222, 128, 0.14);
    border: 1px solid rgba(74, 222, 128, 0.28);
}

.status-cancelled {
    color: #ef4444;
    background: rgba(239, 68, 68, 0.14);
    border: 1px solid rgba(239, 68, 68, 0.28);
}

.status-default {
    color: rgba(255, 255, 255, 0.82);
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.14);
}

.dark-gray {
    background-color: $dark-gray;
    justify-content: left;
    align-items: center;
    min-height: 20vh;
    min-width: 80vh;
    border-radius: 8px;
    
    @media (max-width: 768px) {
        min-width: auto;
        padding: 15px;
    }
}

.light-gray {
    background-color: $light-gray;
}

.title-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    
    @media (max-width: 768px) {
        flex-direction: column;
        gap: 15px;
        align-items: stretch;
    }
}

.sub-title {
    font-size: 20px;
    font-weight: bold;
    display: flex;
    align-items: center;
    color: $white;
    margin: 0;
    
    @media (max-width: 768px) {
        font-size: 18px;
        text-align: center;
    }
    
    @media (max-width: 480px) {
        font-size: 16px;
    }
}

/* Responsive table */
.excel-table {
    border-collapse: collapse;
    width: 100%;
    color: white;
    table-layout: fixed;
    
    @media (max-width: 1024px) {
        font-size: 14px;
    }
    
    @media (max-width: 768px) {
        font-size: 12px;
        display: block;
        overflow-x: auto;
        white-space: nowrap;
    }
}

.excel-table th,
.excel-table td {
    border: 1px solid #dddddd;
    padding: 4px;
    text-align: center;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
    position: relative;
    
    @media (max-width: 768px) {
        padding: 6px 4px;
        min-width: 80px;
    }
    
    @media (max-width: 480px) {
        padding: 4px 2px;
        min-width: 70px;
        font-size: 11px;
    }
}

.excel-table th {
    min-width: 50px;
    
    @media (max-width: 768px) {
        min-width: 80px;
    }
    
    @media (max-width: 480px) {
        min-width: 70px;
    }
}

.excel-table tr:nth-child(even) {
    background-color: $ultra-light-gray;
}

.resizer {
    width: 5px;
    height: 100%;
    position: absolute;
    right: 0;
    top: 0;
    cursor: col-resize;
    user-select: none;
    background-color: transparent;
    
    @media (max-width: 768px) {
        display: none; /* Disable resizing on mobile */
    }
}

/* Mobile-specific styles */
@media (max-width: 768px) {
    .toolbar-actions {
        justify-content: flex-start;
    }

    .pl-7 {
        padding-left: 15px;
        padding-right: 15px;
    }
    
    .p15 {
        padding: 10px;
    }
    
    .p-5 {
        padding: 15px;
    }
    
    .p-2 {
        padding: 10px;
    }
    
    .gap-10 {
        gap: 10px;
    }
    
    .gap-3 {
        gap: 8px;
    }
    
    .gap-2 {
        gap: 5px;
    }
}

@media (max-width: 480px) {
    .pl-7 {
        padding-left: 10px;
        padding-right: 10px;
    }
    
    .p15 {
        padding: 8px;
    }
    
    .p-5 {
        padding: 10px;
    }
    
    .p-2 {
        padding: 8px;
    }
    
    .gap-10 {
        gap: 8px;
    }
    
    .gap-3 {
        gap: 6px;
    }
    
    .gap-2 {
        gap: 4px;
    }
    
}

/* Ensure table scrolls horizontally on mobile */
@media (max-width: 768px) {
    .overflow-x-auto {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
}

/* Improve readability on mobile */
@media (max-width: 480px) {
    .excel-table th,
    .excel-table td {
        font-size: 10px;
        line-height: 1.2;
    }
    
    .btn {
        font-size: 11px;
        padding: 6px 8px;
    }
}

/* Modal Styles */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.7);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 10000;
}

.modal-content {
    background: #1f2937;
    border-radius: 8px;
    max-width: 90vw;
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.modal-content.large {
    width: 95vw;
    height: 90vh;
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid #374151;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #111827;
}

.modal-header h3 {
    color: white;
    margin: 0;
    font-size: 18px;
}

.close-btn {
    background: none;
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.close-btn:hover {
    background: #374151;
    border-radius: 4px;
}

.modal-body {
    padding: 20px;
    overflow-y: auto;
    flex: 1;
}

.modal-footer {
    padding: 20px;
    border-top: 1px solid #374151;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    background: #111827;
}

.info-card {
    background: #374151;
    padding: 12px;
    border-radius: 4px;
}

.info-label {
    font-size: 12px;
    color: #9ca3af;
    margin-bottom: 4px;
}

.info-value {
    color: white;
    font-weight: 500;
}

.modal-table {
    width: 100%;
    border-collapse: collapse;
    color: white;
    margin: 20px 0;
}

.modal-table th,
.modal-table td {
    border: 1px solid #374151;
    padding: 8px;
    text-align: center;
}

.modal-table th {
    background: #111827;
    font-weight: 600;
}

.modal-table td {
    background: #1f2937;
}

.totals-section {
    background: #374151;
    padding: 16px;
    border-radius: 4px;
    margin-top: 20px;
}

.total-row {
    display: flex;
    justify-content: space-between;
    padding: 4px 0;
    color: white;
}

.total-final {
    border-top: 2px solid #6b7280;
    padding-top: 8px;
    font-weight: bold;
    font-size: 1.1em;
}

.form-label {
    display: block;
    color: white;
    font-size: 14px;
    margin-bottom: 4px;
}

.form-input {
    width: 100%;
    padding: 8px;
    border: 1px solid #374151;
    border-radius: 4px;
    background: #1f2937;
    color: white;
}

.form-input:focus {
    outline: none;
    border-color: #3b82f6;
}

.table-input {
    width: 100%;
    background: transparent;
    border: 1px solid #374151;
    color: white;
    padding: 4px;
    border-radius: 2px;
}

.table-input:focus {
    outline: none;
    border-color: #3b82f6;
}

.gray {
    background-color: #6b7280;
    color: white;
}

.gray:hover {
    background-color: #4b5563;
}

</style>
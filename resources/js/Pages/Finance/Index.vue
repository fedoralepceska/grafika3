<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="invoice2" subtitle="UninvoicedOrders" icon="invoice.png" link="notInvoiced" />
            <RedirectTabs :route="$page.url" />
            <div class="dark-gray p-2 text-white">
                <div class="form-container p-2">
                    <div class="toolbar-panel">
                        <div class="toolbar-inline">
                            <div class="filter-grid">
                                <FinanceCompactSearch
                                    v-model="searchInput"
                                    label="Search orders"
                                    placeholder="Order #, title, client…"
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

                        <div class="toolbar-actions">
                            <button
                                v-if="hasSelectedInvoices || clientId != null"
                                type="button"
                                class="finance-toolbar-btn finance-toolbar-btn--ghost"
                                @click="clearAllSelections"
                            >
                                Clear Selection
                                <i class="fa-solid fa-times" aria-hidden="true"></i>
                            </button>
                            <button
                                type="button"
                                class="finance-toolbar-btn finance-toolbar-btn--secondary"
                                :disabled="generateEmptyLoading"
                                @click="openGenerateEmptyConfirm"
                            >
                                Generate empty
                                <i class="fa-regular fa-file" aria-hidden="true"></i>
                            </button>
                            <button
                                type="button"
                                class="finance-toolbar-btn finance-toolbar-btn--primary"
                                @click="generateInvoices"
                            >
                                Generate Invoice
                                <i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <div v-if="loading" class="loading-container">
                        <div class="loading-spinner">
                            <i class="fa fa-spinner fa-spin"></i>
                            <span>Loading orders...</span>
                        </div>
                    </div>

                    <DataTableShell v-else compact variant="grid">
                        <template #header>
                            <tr>
                                <th class="select-column">Select</th>
                                <th class="order-column">Order</th>
                                <th>Title</th>
                                <th class="customer-column">Customer</th>
                                <th class="files-column">Files</th>
                                <th>End Date</th>
                                <th>Created By</th>
                                <th>Status</th>
                                <th class="actions-column">Actions</th>
                            </tr>
                        </template>

                        <template v-if="filteredInvoices && filteredInvoices.length > 0">
                            <tr
                                v-for="invoice in filteredInvoices"
                                :key="invoice.id"
                                :class="[
                                    'invoice-table-row',
                                    'invoice-table-row--clickable',
                                    getStatusRowClass(invoice.status),
                                    { 'row-selected': selectedInvoices[invoice.id] },
                                ]"
                                @click="openOrderDrawer(invoice)"
                            >
                                <td class="select-cell" @click.stop>
                                    <button
                                        type="button"
                                        class="select-toggle"
                                        :class="{ selected: selectedInvoices[invoice.id] }"
                                        :aria-pressed="selectedInvoices[invoice.id] ? 'true' : 'false'"
                                        :title="selectedInvoices[invoice.id] ? 'Selected' : 'Select order'"
                                        @click="handleSelectionClick(invoice)"
                                    >
                                        <span class="select-indicator">
                                            <i v-if="selectedInvoices[invoice.id]" class="fa-solid fa-check" aria-hidden="true"></i>
                                        </span>
                                    </button>
                                </td>
                                <td class="order-primary-cell">
                                    <div class="cell-primary">#{{ invoice.order_number }}</div>
                                </td>
                                <td>
                                    <div class="title-cell">
                                        <div class="cell-primary truncate-title">{{ invoice.invoice_title }}</div>
                                        <div v-if="invoice.LockedNote" class="lock-note-trigger" @click.stop>
                                            <ViewLockDialog :invoice="invoice" />
                                        </div>
                                    </div>
                                </td>
                                <td class="customer-column">
                                    <FinanceClientNameCell :name="invoice.client?.name || ''" variant="secondary" />
                                </td>
                                <td class="files-cell" @click.stop>
                                    <div class="thumbnail-section">
                                        <template v-if="invoice.jobs && invoice.jobs.length > 0">
                                            <div class="invoice-thumbnails-container">
                                                <template v-for="(job, jobIndex) in invoice.jobs" :key="job.id">
                                                    <template v-if="hasDisplayableFiles(job)">
                                                        <template v-if="hasMultipleFiles(job)">
                                                            <div class="multiple-thumbnails-row">
                                                                <div
                                                                    v-for="(file, fileIndex) in getJobFiles(job)"
                                                                    :key="`${job.id}-${fileIndex}`"
                                                                    class="file-thumbnail-wrapper"
                                                                    @click="openFileThumbnailModal(job, jobIndex, fileIndex)"
                                                                >
                                                                    <div v-if="getAvailableThumbnails(job.id, fileIndex).length > 0" class="thumbnail-preview-container">
                                                                        <img
                                                                            v-if="shouldAttemptImageLoad(job, fileIndex)"
                                                                            :src="getThumbnailUrl(job.id, fileIndex)"
                                                                            :alt="`File ${fileIndex + 1}`"
                                                                            class="preview-thumbnail-img"
                                                                            @error="handleThumbnailError($event, job, fileIndex)"
                                                                        />
                                                                        <div v-else class="thumbnail-placeholder-icon">
                                                                            <i class="fa fa-file-o"></i>
                                                                        </div>
                                                                        <div v-if="getAvailableThumbnails(job.id, fileIndex).length > 1" class="page-count-indicator">
                                                                            {{ getAvailableThumbnails(job.id, fileIndex).length }}
                                                                        </div>
                                                                        <div class="file-number-indicator">
                                                                            {{ fileIndex + 1 }}
                                                                        </div>
                                                                    </div>
                                                                    <div v-else-if="thumbnailLoading && thumbnailLoading[job.id]" class="thumbnail-loading-indicator">
                                                                        <i class="fa fa-spinner fa-spin"></i>
                                                                    </div>
                                                                    <div v-else class="thumbnail-placeholder-icon">
                                                                        <span class="text-xs">No preview</span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </template>

                                                        <template v-else-if="hasSingleNewFile(job) || isLegacyJob(job)">
                                                            <div class="single-thumbnail-container">
                                                                <div class="file-thumbnail-wrapper single-file" @click="openFileThumbnailModal(job, jobIndex, 0)">
                                                                    <div v-if="getAvailableThumbnails(job.id, 0).length > 0" class="thumbnail-preview-container">
                                                                        <img
                                                                            v-if="shouldAttemptImageLoad(job, 0)"
                                                                            :src="getThumbnailUrl(job.id, 0)"
                                                                            :alt="`Job ${jobIndex + 1} Preview`"
                                                                            class="preview-thumbnail-img single-file-img"
                                                                            @error="handleThumbnailError($event, job, 0)"
                                                                        />
                                                                        <div v-else class="thumbnail-placeholder-icon">
                                                                            <i class="fa fa-file-o"></i>
                                                                        </div>
                                                                        <div v-if="getAvailableThumbnails(job.id, 0).length > 1" class="page-count-indicator">
                                                                            {{ getAvailableThumbnails(job.id, 0).length }}
                                                                        </div>
                                                                    </div>
                                                                    <div v-else-if="thumbnailLoading && thumbnailLoading[job.id]" class="thumbnail-loading-indicator">
                                                                        <i class="fa fa-spinner fa-spin"></i>
                                                                    </div>
                                                                    <div v-else class="thumbnail-placeholder-icon">
                                                                        <span class="text-xs">No preview</span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </template>
                                                    </template>
                                                </template>
                                            </div>
                                        </template>
                                        <div v-else class="no-thumbnails">
                                            <span class="text-xs">No files</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="cell-secondary">{{ formatDate(invoice.end_date) }}</div>
                                </td>
                                <td>
                                    <div class="cell-secondary">{{ invoice.user?.name || 'N/A' }}</div>
                                </td>
                                <td>
                                    <span :class="['status-badge', getStatusBadgeClass(invoice.status)]">
                                        {{ invoice.status }}
                                    </span>
                                </td>
                                <td class="actions-cell" @click.stop>
                                    <button type="button" class="view-button" @click="openOrderInNewTab(invoice.id)">
                                        <i class="fa fa-eye" aria-hidden="true"></i>
                                        <span>View</span>
                                    </button>
                                </td>
                            </tr>
                        </template>

                        <tr v-else>
                            <td colspan="9" class="empty-cell">
                                No orders found matching your criteria.
                            </td>
                        </tr>
                    </DataTableShell>
                </div>
                <Pagination :pagination="paginationState"
                    @pagination-change-page="goToPage" />
            </div>

            <NotInvoicedOrderDrawer v-model="orderDrawerOpen" :invoice="orderDrawerInvoice" />

            <!-- File-Specific Thumbnail Preview Modal -->
            <div v-if="fileModal.show" class="thumbnail-modal-overlay" @click="closeFileModal">
                <div class="thumbnail-modal" @click.stop>
                    <div class="modal-header">
                        <div class="modal-title">
                            {{ fileModal.fileName }} - {{ fileModal.jobName }}
                        </div>
                        <button class="close-btn" @click="closeFileModal">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>

                    <div class="modal-carousel">
                        <!-- Large thumbnail display -->
                        <img v-if="getCurrentFileThumbnail() && !fileModal.hasError" :src="getModalThumbnailUrl()"
                            :alt="`Page ${fileModal.currentIndex + 1}`" class="modal-thumbnail"
                            @error="onThumbnailError" />
                        <div v-else class="modal-no-thumbnail">
                            <i class="fa fa-image"></i>
                            <p>No preview available</p>
                        </div>

                        <!-- Navigation controls -->
                        <button v-if="fileModal.thumbnails.length > 1" @click="previousFileThumbnail()"
                            class="modal-nav-btn prev-btn" :disabled="fileModal.currentIndex === 0">
                            <i class="fa fa-chevron-left"></i>
                        </button>

                        <button v-if="fileModal.thumbnails.length > 1" @click="nextFileThumbnail()"
                            class="modal-nav-btn next-btn"
                            :disabled="fileModal.currentIndex === fileModal.thumbnails.length - 1">
                            <i class="fa fa-chevron-right"></i>
                        </button>

                        <!-- Page indicator -->
                        <div v-if="fileModal.thumbnails.length > 1" class="modal-page-indicator">
                            Page {{ fileModal.currentIndex + 1 }} of {{ fileModal.thumbnails.length }}
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-if="showGenerateEmptyConfirm"
                class="confirm-overlay"
                role="dialog"
                aria-modal="true"
                aria-labelledby="confirm-generate-empty-title"
                @click="cancelGenerateEmptyConfirm"
            >
                <div class="confirm-modal" @click.stop>
                    <h3 id="confirm-generate-empty-title" class="confirm-title">Generate empty invoice?</h3>
                    <p class="confirm-text">
                        This will create a new empty invoice with no selected orders. Are you sure you want to continue?
                    </p>
                    <div class="confirm-actions">
                        <button type="button" class="finance-toolbar-btn finance-toolbar-btn--ghost" @click="cancelGenerateEmptyConfirm">
                            Cancel
                        </button>
                        <button
                            type="button"
                            class="finance-toolbar-btn finance-toolbar-btn--secondary"
                            :disabled="generateEmptyLoading"
                            @click="confirmGenerateEmptyInvoice"
                        >
                            Yes, generate
                        </button>
                    </div>
                </div>
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
import ViewLockDialog from "@/Components/ViewLockDialog.vue";
import RedirectTabs from "@/Components/RedirectTabs.vue";
import FinanceCompactSearch from "@/Components/Finance/FinanceCompactSearch.vue";
import FinanceClientSearchSelect from "@/Components/Finance/FinanceClientSearchSelect.vue";
import FinanceDateRangeCompact from "@/Components/Finance/FinanceDateRangeCompact.vue";
import FinanceYearMonthSelects from "@/Components/Finance/FinanceYearMonthSelects.vue";
import FinancePeriodPresets from "@/Components/Finance/FinancePeriodPresets.vue";
import FinanceClientNameCell from "@/Components/Finance/FinanceClientNameCell.vue";
import NotInvoicedOrderDrawer from '@/Components/Finance/NotInvoicedOrderDrawer.vue';
import { formatDateDdMmYyyy } from '@/utils/financeFilters';
import { useToast } from "vue-toastification";

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
        ViewLockDialog,
        RedirectTabs,
        FinanceCompactSearch,
        FinanceClientSearchSelect,
        FinanceDateRangeCompact,
        FinanceYearMonthSelects,
        FinancePeriodPresets,
        FinanceClientNameCell,
        NotInvoicedOrderDrawer,
    },
    props: {
        invoices: Object,
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
            localInvoices: [],
            paginationState: { data: [], links: [] },
            uniqueClients: [],
            filteredInvoices: [],
            selectedInvoices: {},
            loading: false,
            perPage: 20,
            // Image error tracking
            imageErrors: {},
            // Thumbnail files discovery
            thumbnailFiles: {},
            // File-specific modal
            fileModal: {
                show: false,
                jobId: null,
                jobName: '',
                fileName: '',
                fileIndex: 0,
                thumbnails: [],
                currentIndex: 0,
                hasError: false // Track if current thumbnail has error
            },
            orderDrawerOpen: false,
            orderDrawerInvoice: null,
            lastAppliedDateRangeKey: null,
            generateEmptyLoading: false,
            showGenerateEmptyConfirm: false,
        };
    },
    mounted() {
        this.localInvoices = this.invoices.data.slice();
        this.paginationState = this.invoices;
        this.fetchUniqueClients();
        this.filteredInvoices = this.invoices.data;
        this.initFromUrl();
        const page = parseInt(new URLSearchParams(window.location.search).get('page') || '1', 10) || 1;
        this.applyFilter(page);
    },
    computed: {
        hasSelectedInvoices() {
            return Object.values(this.selectedInvoices).some(value => value);
        },

    },
    watch: {
        orderDrawerOpen(open) {
            if (!open) {
                this.$nextTick(() => {
                    this.orderDrawerInvoice = null;
                });
            }
        },
    },
    methods: {
        formatDate(dateStr) {
            if (!dateStr) {
                return '';
            }
            const s = formatDateDdMmYyyy(dateStr);
            return s === 'N/A' ? String(dateStr) : s;
        },
        getStatusRowClass(status) {
            if (status === 'Completed') return 'row-completed';
            if (status === 'In progress' || status === 'In Progress') return 'row-progress';
            if (status === 'Not started yet') return 'row-pending';
            return '';
        },
        toggleInvoiceSelection(invoice, event) {
            const toast = useToast();
            const isCurrentlySelected = this.selectedInvoices[invoice.id];

            // Check if invoice.client exists
            if (!invoice.client) {
                toast.error('This invoice does not have an associated client.');
                event.target.checked = false; // Revert checkbox state
                return;
            }

            // If unselecting an invoice, remove it and check if we should clear filter
            if (isCurrentlySelected) {
                this.selectedInvoices[invoice.id] = false;

                // If no invoices are selected anymore, clear the filter
                const remainingSelected = Object.keys(this.selectedInvoices).filter(id => this.selectedInvoices[id]);
                if (remainingSelected.length === 0 && this.clientId != null) {
                    this.clearAllSelections();
                }
                return;
            }

            // Get IDs of currently selected invoices
            const selectedInvoiceIds = Object.keys(this.selectedInvoices).filter(id => this.selectedInvoices[id]);

            // If this is the first selection, auto-filter and select
            if (selectedInvoiceIds.length === 0) {
                this.autoFilterByClient(invoice.client.name);
                this.selectedInvoices[invoice.id] = true;
                return;
            }

            // If we have existing selections, check if we're already filtered to the same client
            // or if the current invoice matches the filter
            if (this.clientId != null && invoice.client && this.clientId === invoice.client.id) {
                // We're already filtered to this client, so it's safe to select
                this.selectedInvoices[invoice.id] = true;
                return;
            }

            // If we're not filtered to a specific client, check against existing selections
            // We need to make an API call to get the client info for previously selected invoices
            this.checkClientCompatibilityAndSelect(invoice, selectedInvoiceIds);
        },
        handleSelectionClick(invoice) {
            const nextCheckedState = !this.selectedInvoices[invoice.id];
            this.toggleInvoiceSelection(invoice, {
                target: {
                    checked: nextCheckedState,
                },
            });
        },

        async checkClientCompatibilityAndSelect(invoice, selectedInvoiceIds) {
            const toast = useToast();

            try {
                // Get the first selected invoice's client info
                const firstSelectedId = selectedInvoiceIds[0];
                const response = await axios.get(`/api/invoice/${firstSelectedId}/client`);
                const existingClientName = response.data.client_name;

                if (existingClientName === invoice.client.name) {
                    // Same client - auto-filter and select
                    this.autoFilterByClient(invoice.client.name);
                    this.selectedInvoices[invoice.id] = true;
                } else {
                    // Different client - show error
                    toast.error('You can only select invoices from the same client.');
                    // Don't change checkbox state as it will be handled by Vue reactivity
                }
            } catch (error) {
                console.error('Error checking client compatibility:', error);

                // Fallback: if API fails, just allow the selection and auto-filter
                // This prevents the UI from being stuck due to API issues
                this.autoFilterByClient(invoice.client.name);
                this.selectedInvoices[invoice.id] = true;

                toast.warning('Unable to verify client compatibility, but selection was allowed.');
            }
        },

        getStatusColorClass(status) {
            if (status === "Not started yet") {
                return "orange";
            } else if (status === "In progress") {
                return "blue-text";
            } else if (status === "Completed") {
                return "green-text";
            }
        },
        getStatusBadgeClass(status) {
            if (status === "Completed") {
                return "status-completed";
            } else if (status === "In progress" || status === "In Progress") {
                return "status-progress";
            } else if (status === "Not started yet") {
                return "status-pending";
            }

            return "status-default";
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

                const response = await axios.get('/api/notInvoiced/filtered', {
                    params: this.buildRequestParams(page),
                });

                this.filteredInvoices = response.data.data || response.data;
                this.paginationState = response.data && response.data.links
                    ? response.data
                    : { data: this.filteredInvoices, links: [] };
                this.lastAppliedDateRangeKey = `${this.dateFrom || ''}|${this.dateTo || ''}`;

                window.history.replaceState({}, '', `/notInvoiced${this.buildHistoryQueryString(page)}`);
            } catch (error) {
                console.error('Error applying filters:', error);
            } finally {
                this.loading = false;
            }
        },
        onSearchSubmit() {
            this.searchQuery = (this.searchInput || '').trim();
            this.applyFilter(1);
        },
        onDateRangeChange() {
            this.normalizeDates();
            const dateRangeKey = `${this.dateFrom || ''}|${this.dateTo || ''}`;
            if (dateRangeKey === this.lastAppliedDateRangeKey) {
                return;
            }
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
        goToPage(page) {
            this.applyFilter(page);
        },
        async fetchUniqueClients() {
            try {
                const response = await axios.get('/unique-clients');
                this.uniqueClients = response.data;
            } catch (error) {
                console.error(error);
            }
        },

        async autoFilterByClient(clientName) {
            const c = this.uniqueClients.find((x) => x.name === clientName);
            this.clientId = c ? c.id : null;
            await this.applyFilter(1);
        },
        openOrderDrawer(invoice) {
            this.orderDrawerInvoice = invoice;
            this.orderDrawerOpen = true;
        },
        openOrderInNewTab(id) {
            window.open(`/orders/${id}`, '_blank', 'noopener,noreferrer');
        },
        clearAllSelections() {
            this.selectedInvoices = {};
            this.clientId = null;
            this.applyFilter(1);
        },

        async generateInvoices() {
            const toast = useToast();
            const selectedIds = Object.entries(this.selectedInvoices)
                .filter(([, isSelected]) => isSelected)
                .map(([id]) => id);

            if (selectedIds.length === 0) {
                toast.error('Please select at least one invoice to generate.');
                return;
            }

            // Redirect to invoice generation page with selected orders
            const queryParams = selectedIds.map(id => `orders[]=${id}`).join('&');
            this.$inertia.visit(`/invoiceGeneration?${queryParams}`);
        },

        async generateEmptyInvoice() {
            const toast = useToast();
            if (this.generateEmptyLoading) {
                return;
            }
            this.generateEmptyLoading = true;
            try {
                const payload = {
                    orders: [],
                    empty_faktura: true,
                    comment: '',
                    trade_items: [],
                    additional_services: [],
                    merge_groups: [],
                    job_units: [],
                    return_meta: true,
                };
                const response = await axios.post('/generate-invoice', payload, { responseType: 'json' });
                if (response?.data?.success && response?.data?.faktura_id) {
                    const fid = response.data.faktura_id;
                    toast.success(`Empty invoice created (faktura #${fid}).`);
                    this.$inertia.visit('/allInvoices');
                    return;
                }
                toast.error(response?.data?.error || 'Could not create empty invoice.');
            } catch (error) {
                console.error(error);
                let message = 'Could not create empty invoice.';
                if (error?.response?.data?.error) {
                    message = error.response.data.error;
                } else if (error?.response?.data?.message) {
                    message = error.response.data.message;
                }
                toast.error(message);
            } finally {
                this.generateEmptyLoading = false;
            }
        },
        openGenerateEmptyConfirm() {
            if (this.generateEmptyLoading) {
                return;
            }
            this.showGenerateEmptyConfirm = true;
        },
        cancelGenerateEmptyConfirm() {
            this.showGenerateEmptyConfirm = false;
        },
        async confirmGenerateEmptyInvoice() {
            this.showGenerateEmptyConfirm = false;
            await this.generateEmptyInvoice();
        },

        // Thumbnail-related methods
        hasDisplayableFiles(job) {
            // Check if job has any files to display (new or legacy system)
            if (this.hasMultipleFiles(job)) {
                return true; // Always try to display - let API handle missing thumbnails
            } else if (this.hasSingleNewFile(job)) {
                return true; // Always try to display
            } else if (this.isLegacyJob(job)) {
                // Legacy system - check if file exists and is not placeholder
                return job.file && job.file !== 'placeholder.jpeg';
            }
            return false;
        },

        hasMultipleFiles(job) {
            // Check if job has dimensions_breakdown (new system) and has 2 or more files
            return job.dimensions_breakdown && Array.isArray(job.dimensions_breakdown) && job.dimensions_breakdown.length > 1;
        },

        hasSingleNewFile(job) {
            // Check if job has dimensions_breakdown (new system) with exactly 1 file
            return job.dimensions_breakdown && Array.isArray(job.dimensions_breakdown) && job.dimensions_breakdown.length === 1;
        },

        isLegacyJob(job) {
            // Check if this is a legacy job (pre-dimensions_breakdown)
            return !job.dimensions_breakdown && job.file;
        },

        getJobFiles(job) {
            // Return dimensions_breakdown array for new system, or create array from legacy file
            if (job.dimensions_breakdown && Array.isArray(job.dimensions_breakdown)) {
                return job.dimensions_breakdown.map(fileData => fileData.filename || `File ${job.dimensions_breakdown.indexOf(fileData) + 1}`);
            }
            return job.file ? [job.file] : [];
        },

        getThumbnailUrl(jobId, fileIndex, page = null) {
            // Use dynamic API route like InvoiceDetails.vue for consistency
            try {
                const pageNumber = page || 1;
                return route('jobs.viewThumbnail', { jobId: jobId, fileIndex: fileIndex, page: pageNumber });
            } catch (error) {
                // Fallback to direct URL if route helper fails
                return `/jobs/${jobId}/view-thumbnail/${fileIndex}/${pageNumber || 1}`;
            }
        },

        handleThumbnailError(event, job, fileIndex) {
            const jobKey = `${job.id}_${fileIndex}`;

            // Mark this image as failed to prevent repeated requests
            this.imageErrors[jobKey] = true;

            // Hide the broken image and show a placeholder instead
            const parentElement = event.target.parentElement;
            if (parentElement) {
                // Create and show placeholder
                const placeholder = document.createElement('div');
                placeholder.className = 'image-error-placeholder';
                placeholder.innerHTML = '<i class="fa fa-file-o"></i><span>File not found</span>';

                // Replace the broken image with placeholder
                event.target.style.display = 'none';
                parentElement.appendChild(placeholder);
            }
        },

        shouldAttemptImageLoad(job, fileIndex) {
            if (fileIndex === 'legacy') {
                const jobKey = `${job.id}_legacy`;
                return !this.imageErrors[jobKey];
            }
            const jobKey = `${job.id}_${fileIndex}`;
            const shouldLoad = !this.imageErrors[jobKey];
            return shouldLoad;
        },

        getJobThumbnails(jobId) {
            // Find the job in the current invoices data
            for (const invoice of this.filteredInvoices) {
                if (invoice.jobs) {
                    const job = invoice.jobs.find(j => j.id === jobId);
                    if (job && job.thumbnails) {
                        return job.thumbnails;
                    }
                }
            }
            return [];
        },

        getThumbnailsForFile(jobId, fileIndex) {
            // For new API-based approach, we'll rely on SSR thumbnails when available
            const thumbnails = this.getJobThumbnails(jobId);
            const job = this.findJobById(jobId);
            if (!job) return [];

            // If we have SSR thumbnails, filter them by file index
            if (thumbnails && thumbnails.length > 0) {
                const matchingThumbnails = thumbnails.filter(t =>
                    t && t.file_index === fileIndex
                );

                // Sort by page number to ensure proper order
                return matchingThumbnails.sort((a, b) => {
                    const pageA = parseInt(a.page_number || '0');
                    const pageB = parseInt(b.page_number || '0');
                    return pageA - pageB;
                });
            }

            // Return empty array - getAvailableThumbnails will handle fallback
            return [];
        },

        findJobById(jobId) {
            // Find job by ID across all invoices
            for (const invoice of this.filteredInvoices) {
                if (invoice.jobs) {
                    const job = invoice.jobs.find(j => j.id === jobId);
                    if (job) return job;
                }
            }
            return null;
        },

        getAvailableThumbnails(jobId, fileIndex) {
            // Get all available thumbnail pages for a specific file
            const thumbnails = this.getThumbnailsForFile(jobId, fileIndex);

            // If no thumbnails found via SSR data, create placeholder thumbnail objects for API calls
            if (thumbnails.length === 0) {
                const job = this.findJobById(jobId);
                if (job && ((this.hasMultipleFiles(job) && fileIndex < job.dimensions_breakdown.length) ||
                    (this.hasSingleNewFile(job) && fileIndex === 0) ||
                    (this.isLegacyJob(job) && fileIndex === 0))) {

                    const fileThumbnails = [];

                    // Check if job has page dimensions breakdown for multiple pages
                    if (job.dimensions_breakdown && job.dimensions_breakdown[fileIndex] &&
                        job.dimensions_breakdown[fileIndex].page_dimensions) {

                        const pageCount = job.dimensions_breakdown[fileIndex].page_dimensions.length;

                        // Create thumbnail entry for each page
                        for (let page = 1; page <= pageCount; page++) {
                            fileThumbnails.push({
                                url: null, // Will be handled by API call in getThumbnailUrl
                                page_number: page,
                                file_index: fileIndex,
                                file_name: `placeholder_${jobId}_${fileIndex}_page_${page}.png`
                            });
                        }
                    } else {
                        // Single page fallback
                        fileThumbnails.push({
                            url: null, // Will be handled by API call in getThumbnailUrl
                            page_number: 1,
                            file_index: fileIndex,
                            file_name: `placeholder_${jobId}_${fileIndex}.png`
                        });
                    }

                    return fileThumbnails;
                }
            }

            return thumbnails;
        },

        openFileThumbnailModal(job, jobIndex, fileIndex) {
            const jobName = `Job #${jobIndex + 1}`;
            const fileName = this.getFileName(job, fileIndex);
            const thumbnails = this.getAvailableThumbnails(job.id, fileIndex);

            if (thumbnails.length === 0) {
                console.warn(`No thumbnails found for job ${job.id}, file ${fileIndex}`);
                return;
            }

            this.fileModal = {
                show: true,
                jobId: job.id,
                jobName: jobName,
                fileName: fileName,
                fileIndex: fileIndex,
                thumbnails,
                currentIndex: 0,
                hasError: false
            };
        },

        closeFileModal() {
            this.fileModal.show = false;
            this.fileModal = {
                show: false,
                jobId: null,
                jobName: '',
                fileName: '',
                fileIndex: 0,
                thumbnails: [],
                currentIndex: 0,
                hasError: false
            };
        },

        getCurrentFileThumbnail() {
            return this.fileModal.thumbnails[this.fileModal.currentIndex];
        },

        getModalThumbnailUrl() {
            // Use dynamic API route for modal thumbnails
            const thumbnail = this.getCurrentFileThumbnail();
            if (!thumbnail) return null;

            return this.getThumbnailUrl(this.fileModal.jobId, this.fileModal.fileIndex, thumbnail.page_number || 1);
        },

        previousFileThumbnail() {
            if (this.fileModal.currentIndex > 0) {
                this.fileModal.currentIndex--;
                this.fileModal.hasError = false; // Reset error state
            }
        },

        nextFileThumbnail() {
            if (this.fileModal.currentIndex < this.fileModal.thumbnails.length - 1) {
                this.fileModal.currentIndex++;
                this.fileModal.hasError = false; // Reset error state
            }
        },

        getFileName(job, fileIndex) {
            // Get filename for the specific file index
            if (job.dimensions_breakdown && job.dimensions_breakdown[fileIndex]) {
                const fileData = job.dimensions_breakdown[fileIndex];
                if (typeof fileData === 'string') {
                    return fileData.split('/').pop() || fileData;
                } else if (fileData.filename) {
                    return fileData.filename;
                }
            } else if (job.originalFile && job.originalFile[fileIndex]) {
                return this.getFileNameFromPath(job.originalFile[fileIndex]);
            } else if (job.file && fileIndex === 0) {
                return typeof job.file === 'string' ? job.file.split('/').pop() || job.file : 'Legacy File';
            }

            return `File ${fileIndex + 1}`;
        },

        getFileNameFromPath(filePath) {
            if (!filePath) return '';
            let fileName = filePath.split('/').pop() || filePath;

            // Remove timestamp prefix (e.g., "1759428892_adoadoadoado.pdf")
            fileName = fileName.replace(/^\d+_/, '');

            return fileName;
        },

        onThumbnailError(event) {
            console.warn('Modal thumbnail failed to load');
            // Set error flag to show placeholder
            this.fileModal.hasError = true;
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

.toolbar-actions {
    display: flex;
    align-items: center;
    gap: 12px;
    justify-content: flex-end;
    flex-wrap: wrap;
    white-space: nowrap;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
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

select {
    width: 100%;
}

.dark-gray {
    background-color: $dark-gray;
    justify-content: left;
    border-radius: 8px;
    align-items: center;
    min-height: 20vh;
    min-width: 80vh;
}

.finance-toolbar-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 38px;
    padding: 0 16px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    border: 1px solid transparent;
    transition:
        background 0.15s ease,
        border-color 0.15s ease,
        color 0.15s ease,
        box-shadow 0.15s ease;
}

.finance-toolbar-btn--ghost {
    background: transparent;
    color: rgba(255, 255, 255, 0.92);
    border-color: rgba(255, 255, 255, 0.22);
    box-shadow: none;
}

.finance-toolbar-btn--ghost:hover {
    background: rgba(255, 255, 255, 0.06);
    border-color: rgba(255, 255, 255, 0.34);
    color: $white;
}

.finance-toolbar-btn--ghost:focus-visible {
    outline: 2px solid rgba(96, 165, 250, 0.65);
    outline-offset: 2px;
}

.finance-toolbar-btn--primary {
    background: $green;
    color: $white;
    border-color: rgba(255, 255, 255, 0.12);
    box-shadow: 0 1px 0 rgba(0, 0, 0, 0.15);
}

.finance-toolbar-btn--primary:hover {
    filter: brightness(1.07);
    border-color: rgba(255, 255, 255, 0.2);
}

.finance-toolbar-btn--primary:focus-visible {
    outline: 2px solid rgba(134, 239, 172, 0.75);
    outline-offset: 2px;
}

.finance-toolbar-btn--secondary {
    background: rgba(255, 255, 255, 0.05);
    color: rgba(255, 255, 255, 0.92);
    border-color: rgba(251, 191, 36, 0.42);
}

.finance-toolbar-btn--secondary:hover:not(:disabled) {
    background: rgba(251, 191, 36, 0.1);
    border-color: rgba(253, 224, 71, 0.55);
    color: $white;
}

.finance-toolbar-btn--secondary:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.finance-toolbar-btn--secondary:focus-visible {
    outline: 2px solid rgba(251, 191, 36, 0.65);
    outline-offset: 2px;
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

.select-column {
    width: 76px;
}

.order-column {
    width: 110px;
}

.actions-column {
    width: 110px;
}

.select-cell {
    text-align: center;
}

.select-toggle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    padding: 0;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.04);
    color: rgba(255, 255, 255, 0.84);
    transition: all 0.2s ease;
}

.select-toggle:hover {
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(255, 255, 255, 0.2);
}

.select-toggle.selected {
    background: rgba(59, 130, 246, 0.16);
    border-color: rgba(96, 165, 250, 0.5);
    color: $white;
}

.select-indicator {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.04);
    font-size: 10px;
}

.select-toggle.selected .select-indicator {
    border-color: rgba(96, 165, 250, 0.6);
    background: rgba(59, 130, 246, 0.95);
    color: $white;
}

.row-selected {
    box-shadow: inset 3px 0 0 rgba(59, 130, 246, 0.95);
}

.order-primary-cell .cell-primary,
.cell-primary {
    font-weight: 700;
    color: $white;
}

.cell-secondary {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.68);
}

.title-cell {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 220px;
}

.truncate-title {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.invoice-table-row--clickable {
    cursor: pointer;
}

.invoice-table-row--clickable:hover {
    filter: brightness(1.05);
}

.customer-column {
    max-width: 280px;
    width: 18%;
}

.lock-note-trigger {
    flex-shrink: 0;
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

.status-pending {
    color: #facc15;
    background: rgba(250, 204, 21, 0.12);
    border: 1px solid rgba(250, 204, 21, 0.28);
}

.status-progress {
    color: #60a5fa;
    background: rgba(96, 165, 250, 0.12);
    border: 1px solid rgba(96, 165, 250, 0.28);
}

.status-completed {
    color: #4ade80;
    background: rgba(74, 222, 128, 0.12);
    border: 1px solid rgba(74, 222, 128, 0.28);
}

.status-default {
    color: rgba(255, 255, 255, 0.8);
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.14);
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
    .filter-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

/* Thumbnail styles — Files column scrolls horizontally when many attachments */
.files-column {
    width: 1%;
    max-width: 320px;
}

.files-cell {
    max-width: 320px;
    min-width: 0;
    vertical-align: middle;
}

.thumbnail-section {
    min-width: 0;
    max-width: 100%;
    display: block;
    overflow-x: auto;
    overflow-y: visible;
    padding-bottom: 4px;
    -webkit-overflow-scrolling: touch;

    /* Thin scrollbar (dark UI) */
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.28) rgba(255, 255, 255, 0.06);

    &::-webkit-scrollbar {
        height: 6px;
    }

    &::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.06);
        border-radius: 4px;
    }

    &::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.28);
        border-radius: 4px;
    }
}

.invoice-thumbnails-container {
    display: flex;
    flex-direction: row;
    flex-wrap: nowrap;
    align-items: center;
    width: max-content;
    max-width: none;
    gap: 6px;
}

.multiple-thumbnails-row {
    display: flex;
    flex-direction: row;
    flex-wrap: nowrap;
    gap: 4px;
    justify-content: flex-start;
    align-items: center;
    flex: 0 0 auto;
}

.single-thumbnail-container {
    display: flex;
    justify-content: flex-start;
    align-items: center;
    flex: 0 0 auto;
}

.file-thumbnail-wrapper {
    flex-shrink: 0;
    position: relative;
    border-radius: 4px;
    overflow: hidden;
    transition: all 0.2s ease;
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);

    &:hover {
        transform: scale(1.05);
        z-index: 10;
        position: relative;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    }

    &.single-file {
        width: 50px;
        height: 60px;
    }
}

.thumbnail-preview-container {
    position: relative;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    border: 1px solid $ultra-light-gray;
}

.preview-thumbnail-img {
    width: 40px;
    height: 50px;
    object-fit: contain;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s ease;

    &.single-file-img {
        width: 50px;
        height: 60px;
    }
}

.thumbnail-placeholder-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 60px;
    height: 50px;
    background-color: #f8f9fa;
    border: 2px dashed #dee2e6;
    border-radius: 4px;
    color: #6c757d;

    i {
        font-size: 16px;
    }
}

.thumbnail-loading-indicator {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 50px;
    color: #6c757d;

    i {
        font-size: 14px;
        animation: spin 1s linear infinite;
    }
}

.page-count-indicator {
    position: absolute;
    top: 2px;
    right: 2px;
    background-color: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 2px 5px;
    border-radius: 3px;
    font-size: 9px;
    font-weight: bold;
    line-height: 1;
    min-width: 16px;
    text-align: center;
}

.file-number-indicator {
    position: absolute;
    bottom: 2px;
    left: 2px;
    background-color: rgba(59, 130, 246, 0.9);
    color: white;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 9px;
    font-weight: bold;
    min-width: 16px;
    text-align: center;
}

.image-error-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background-color: #fef2f2;
    border: 2px dashed #fca5a5;
    border-radius: 6px;
    color: #dc2626;
    font-size: 9px;
    text-align: center;

    i {
        font-size: 14px;
        margin-bottom: 2px;
        color: #f87171;
    }

    span {
        font-size: 7px;
        line-height: 1;
    }
}

.no-thumbnails {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 50px;
    color: #6c757d;
    font-size: 12px;
}

@media (max-width: 768px) {
    .filter-grid {
        grid-template-columns: 1fr;
    }

    .toolbar-actions {
        width: 100%;
        justify-content: flex-start;
        flex-wrap: wrap;
        white-space: normal;
        margin-top: 14px;
        padding-top: 14px;
    }

    .toolbar-panel {
        padding: 14px;
    }
}

@media (max-width: 640px) {
    .files-column,
    .files-cell {
        max-width: 220px;
    }

    .multiple-thumbnails-row {
        gap: 4px;
    }

    .preview-thumbnail-img {
        width: 30px;
        height: 40px;

        &.single-file-img {
            width: 35px;
            height: 45px;
        }
    }

    .thumbnail-placeholder-icon,
    .thumbnail-loading-indicator {
        width: 30px;
        height: 40px;
    }

    .file-number-indicator,
    .page-count-indicator {
        font-size: 8px;
        padding: 1px 4px;
    }
}

/* Thumbnail modal styles */
.thumbnail-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.thumbnail-modal {
    background: $dark-gray;
    border-radius: 12px;
    max-width: 90vw;
    max-height: 90vh;
    width: 800px;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    overflow: hidden;
}

.thumbnail-modal .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid $ultra-light-gray;
    background-color: $dark-gray;

    .modal-title {
        margin: 0;
        font-size: 1.2rem;
        color: $white;
        font-weight: bold;
    }

    .close-btn {
        background: none;
        border: none;
        font-size: 1.2rem;
        cursor: pointer;
        color: $white;
        padding: 4px;
        border-radius: 4px;
        transition: all 0.2s ease;

        &:hover {
            color: $red;
        }
    }
}

.thumbnail-modal .modal-carousel {
    position: relative;
    padding: 20px;
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    background-color: $dark-gray;
    min-height: 400px;

    .modal-thumbnail {
        max-width: 100%;
        max-height: 70vh;
        width: auto;
        height: auto;
        object-fit: contain;
        border-radius: 4px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        margin: 0 auto;
        display: block;
    }

    .modal-no-thumbnail {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 200px;
        color: #6c757d;

        i {
            font-size: 48px;
            margin-bottom: 10px;
        }

        p {
            margin: 0;
            font-size: 16px;
        }
    }

    .modal-nav-btn {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background-color: rgba(0, 0, 0, 0.7);
        color: white;
        border: none;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        transition: all 0.2s ease;

        &:hover:not(:disabled) {
            background-color: rgba(0, 0, 0, 0.9);
            transform: translateY(-50%) scale(1.1);
        }

        &:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }

        &.prev-btn {
            left: 30px;
        }

        &.next-btn {
            right: 30px;
        }
    }

    .modal-page-indicator {
        position: absolute;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        background-color: rgba(0, 0, 0, 0.7);
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: bold;
    }
}

.confirm-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1100;
    padding: 20px;
}

.confirm-modal {
    width: min(520px, 100%);
    border-radius: 12px;
    background: $dark-gray;
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 20px 42px rgba(0, 0, 0, 0.35);
    padding: 20px;
}

.confirm-title {
    margin: 0 0 8px;
    color: $white;
    font-size: 1.1rem;
    font-weight: 700;
}

.confirm-text {
    margin: 0;
    color: rgba(255, 255, 255, 0.85);
    font-size: 0.95rem;
    line-height: 1.45;
}

.confirm-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 18px;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }

    to {
        transform: rotate(360deg);
    }
}
</style>

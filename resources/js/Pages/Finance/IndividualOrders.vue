<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="invoice2" subtitle="Individual Orders" icon="invoice.png" link="individual"/>
            <RedirectTabs :route="$page.url" />
            <div class="dark-gray p-2 text-white overflow-x-hidden">
                <div class="form-container p-2">
                    <div class="toolbar-panel">
                        <div class="toolbar-inline">
                            <div class="filter-toolbar-individual">
                                <FinanceCompactSearch
                                    v-model="searchInput"
                                    label="Search orders"
                                    placeholder="Order #, title, contact…"
                                    class="io-field io-field--search"
                                    @submit="onSearchSubmit"
                                />
                                <div class="filter-col io-field io-field--sort">
                                    <label class="toolbar-label">Sort order</label>
                                    <select v-model="sortOrder" class="text-black filter-select-compact" @change="applyFilter">
                                        <option value="desc" hidden>Sort order</option>
                                        <option value="desc">Newest to oldest</option>
                                        <option value="asc">Oldest to newest</option>
                                    </select>
                                </div>
                                <div class="filter-col io-field io-field--contact contact-filter">
                                    <label class="toolbar-label">Contact</label>
                                    <div class="contact-input-row">
                                        <div class="relative contact-dropdown-wrapper">
                                            <input
                                                :value="displayValue"
                                                placeholder="Select contact…"
                                                class="text-black filter-select-compact contact-search-input"
                                                @focus="showContactDropdown = true; contactSearchTerm = ''"
                                                @click="showContactDropdown = true; contactSearchTerm = ''"
                                                @input="contactSearchTerm = $event.target.value; setContactSearchDebounced()"
                                            />
                                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                </svg>
                                            </div>
                                            <div
                                                v-if="showContactDropdown"
                                                class="contact-dropdown-menu"
                                            >
                                                <div
                                                    class="contact-dropdown-item"
                                                    :class="contactFilter === '' ? 'selected' : ''"
                                                    @click="selectContact('')"
                                                >
                                                    All Contacts
                                                </div>
                                                <div
                                                    v-if="filteredContacts.length === 0 && contactSearchTerm"
                                                    class="contact-dropdown-empty"
                                                >
                                                    No contacts found for "{{ contactSearchTerm }}"
                                                </div>
                                                <div
                                                    v-for="contact in filteredContacts"
                                                    :key="contact.id"
                                                    class="contact-dropdown-item"
                                                    :class="contactFilter == contact.id ? 'selected' : ''"
                                                    @click="selectContact(contact.id)"
                                                >
                                                    {{ contact.name }}
                                                </div>
                                            </div>
                                        </div>
                                        <button
                                            v-if="contactFilter !== ''"
                                            @click="clearContactFilter"
                                            class="clear-filter-button"
                                            title="Clear contact filter"
                                        >
                                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="filter-col io-field io-field--payment">
                                    <label class="toolbar-label">Payment</label>
                                    <select v-model="status" class="text-black filter-select-compact" @change="applyFilter">
                                        <option value="" hidden>Payment</option>
                                        <option value="">All status</option>
                                        <option value="paid">Paid</option>
                                        <option value="unpaid">Unpaid</option>
                                    </select>
                                </div>
                                <div class="filter-col io-field io-field--progress">
                                    <label class="toolbar-label">Progress</label>
                                    <select v-model="completionStatus" class="text-black filter-select-compact" @change="applyFilter">
                                        <option value="" hidden>Progress</option>
                                        <option value="">All</option>
                                        <option value="Completed">Completed</option>
                                        <option value="In progress">In progress</option>
                                        <option value="Not started yet">Not started</option>
                                    </select>
                                </div>
                                <FinanceDateRangeCompact
                                    class="io-field io-field--dates"
                                    :date-from="dateFrom"
                                    :date-to="dateTo"
                                    label="Date created"
                                    @update:date-from="dateFrom = $event"
                                    @update:date-to="dateTo = $event"
                                    @change="onDateRangeChange"
                                />
                                <FinanceYearMonthSelects
                                    class="io-field io-field--ym"
                                    :fiscal-year="fiscalYear"
                                    :month="calendarMonth"
                                    @update:fiscal-year="fiscalYear = $event"
                                    @update:month="calendarMonth = $event"
                                    @change="applyFilter"
                                />
                            </div>
                            <FinancePeriodPresets
                                class="presets-inline-individual"
                                label=""
                                @preset="onPeriodPreset"
                                @clear-dates="onClearDates"
                            />
                        </div>
                    </div>

                    <DataTableShell compact variant="grid">
                        <template #header>
                            <tr>
                                <th class="number-column">Nr</th>
                                <th class="order-column">Order</th>
                                <th>Total</th>
                                <th>Date</th>
                                <th class="customer-column">Contact</th>
                                <th>Progress</th>
                                <th>Payment</th>
                                <th>Notes</th>
                                <th class="actions-column actions-header">Actions</th>
                            </tr>
                        </template>

                        <template v-if="orders.data && orders.data.length > 0">
                            <template v-for="(order, index) in orders.data" :key="order.id">
                                <tr :class="{ 'row-expanded': isOrderExpanded(order.id) }">
                                    <td class="cell-secondary">{{ rowNumber(index) }}</td>
                                    <td class="order-primary-cell">
                                        <div class="cell-primary">#{{ order.order_number || order.id }}</div>
                                        <div class="cell-secondary">Invoice #{{ order.invoice_id }}</div>
                                    </td>
                                    <td><div class="cell-primary">{{ formatCurrency(order.total_amount) }} MKD</div></td>
                                    <td><div class="cell-secondary">{{ formatDateDisplay(order.created_at) }}</div></td>
                                    <td class="customer-column">
                                        <FinanceClientNameCell :name="order.invoice?.contact?.name || ''" empty-label="No contact" />
                                    </td>
                                    <td>
                                        <span :class="['status-badge', getStatusBadgeClass(order.completion_status)]">
                                            {{ order.completion_status }}
                                        </span>
                                    </td>
                                    <td>
                                        <ActionDropdown
                                            :label="formatPaidStatus(localStatus[order.id])"
                                            :trigger-title="`Update payment status for order ${order.order_number || order.id}`"
                                            :groups="getPaymentMenuGroups()"
                                            :disabled="!order.is_completed"
                                            :tone="getPaymentTone(localStatus[order.id])"
                                            @select="handlePaymentMenuSelect(order.id, $event)"
                                        />
                                    </td>
                                    <td>
                                        <button :class="['note-button', { 'note-filled': order.notes }]" @click="openNoteModal(order)">
                                            <i class="fa-regular fa-note-sticky" aria-hidden="true"></i>
                                            <span class="note-button-text">{{ order.notes ? 'View note' : 'Add note' }}</span>
                                        </button>
                                        <div v-if="order.notes" class="note-preview" :title="order.notes">{{ order.notes }}</div>
                                    </td>
                                    <td class="actions-cell">
                                        <button
                                            v-if="hasPreviewJobs(order)"
                                            class="table-action-button"
                                            @click="toggleOrderExpansion(order.id)"
                                        >
                                            <span>{{ isOrderExpanded(order.id) ? 'Hide Previews' : 'Preview Cards' }}</span>
                                            <i :class="[isOrderExpanded(order.id) ? 'fa-chevron-up' : 'fa-chevron-down', 'fa-solid']" aria-hidden="true"></i>
                                        </button>
                                        <span v-else class="cell-secondary">No previews</span>
                                    </td>
                                </tr>

                                <tr v-if="hasPreviewJobs(order) && isOrderExpanded(order.id)" class="preview-detail-row">
                                    <td colspan="9" class="preview-detail-cell">
                                        <div class="preview-detail-panel">
                                            <div class="preview-panel-header">
                                                <div class="preview-panel-title">Job Preview Cards</div>
                                                <div class="preview-panel-count">{{ getOrderJobWithThumbnails(order).length }} job(s)</div>
                                            </div>

                                            <div class="jobs-container-grid">
                                                <template v-for="(job, jobIndex) in getOrderJobWithThumbnails(order)" :key="jobIndex">
                                                    <div
                                                        v-for="(filePath, fileIndex) in job.originalFile || []"
                                                        :key="`${job.id}-${fileIndex}`"
                                                        class="thumbnail-card"
                                                        @click="openThumbnailModal(order.id, job.id, fileIndex)"
                                                    >
                                                        <div v-if="getFileThumbnails(order.id, job.id, fileIndex).length > 0" class="thumbnail-clickable">
                                                            <img
                                                                :src="getFileThumbnails(order.id, job.id, fileIndex)[0]?.url"
                                                                :alt="`File ${fileIndex + 1}`"
                                                                class="thumbnail-mini clickable-thumbnail"
                                                                @error="onThumbnailError"
                                                            />
                                                            <div v-if="getFileThumbnails(order.id, job.id, fileIndex).length > 1" class="page-count-mini">
                                                                {{ getFileThumbnails(order.id, job.id, fileIndex).length }}
                                                            </div>
                                                        </div>
                                                        <div v-else-if="thumbnailLoading[order.id]" class="thumbnail-loading-mini">
                                                            <i class="fa fa-spinner fa-spin"></i>
                                                        </div>
                                                        <div v-else class="thumbnail-placeholder-mini">
                                                            <i class="fa fa-file-pdf-o"></i>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </template>

                        <tr v-else>
                            <td colspan="9" class="empty-cell">
                                No individual orders found matching your criteria.
                            </td>
                        </tr>
                    </DataTableShell>
                </div>
                <Pagination :pagination="orders" @pagination-change-page="handlePageChange"/>
            </div>
        </div>
    </MainLayout>

    <!-- Thumbnail Preview Modal -->
    <div v-if="thumbnailModal.show" class="thumbnail-modal-overlay" @click="closeThumbnailModal">
        <div class="thumbnail-modal" @click.stop>
            <div class="modal-header">
                <div class="modal-title">
                    File Preview - {{ thumbnailModal.fileName }}
                </div>
                <button class="close-btn" @click="closeThumbnailModal">
                    <i class="fa fa-times"></i>
                </button>
            </div>
            
            <div class="modal-carousel">
                <!-- Large thumbnail display -->
                <img 
                    v-if="getCurrentModalThumbnail()?.url"
                    :src="getCurrentModalThumbnail()?.url"
                    :alt="`Page ${thumbnailModal.currentIndex + 1}`"
                    class="modal-thumbnail"
                    @error="onThumbnailError"
                />
                <div v-else class="modal-no-thumbnail">
                    <i class="fa fa-image"></i>
                    <p>No preview available</p>
                </div>
                
                <!-- Navigation controls -->
                <button 
                    v-if="thumbnailModal.thumbnails.length > 1"
                    @click="previousModalThumbnail()"
                    class="modal-nav-btn prev-btn"
                    :disabled="thumbnailModal.currentIndex === 0"
                >
                    <i class="fa fa-chevron-left"></i>
                </button>
                
                <button 
                    v-if="thumbnailModal.thumbnails.length > 1"
                    @click="nextModalThumbnail()"
                    class="modal-nav-btn next-btn"
                    :disabled="thumbnailModal.currentIndex === thumbnailModal.thumbnails.length - 1"
                >
                    <i class="fa fa-chevron-right"></i>
                </button>
                
                <!-- Page indicator -->
                <div v-if="thumbnailModal.thumbnails.length > 1" class="modal-page-indicator">
                    Page {{ thumbnailModal.currentIndex + 1 }} of {{ thumbnailModal.thumbnails.length }}
                </div>
            </div>
        </div>
    </div>

    <!-- Note Modal -->
    <div v-if="showNoteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="dark-gray p-6 rounded-lg max-w-md w-full mx-4 text-white">
            <h3 class="text-lg font-bold mb-4 text-white">Order Notes</h3>
            <div class="mb-4">
                <label class="block text-sm font-medium text-white mb-2">Order #{{ selectedOrder?.order_number || selectedOrder?.id }}</label>
                <textarea 
                    v-model="noteText" 
                    class="w-full p-3 border border-gray-600 rounded-md h-32 resize-none bg-gray-800 text-white placeholder-gray-400"
                    placeholder="Add notes for this order..."
                ></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button 
                    @click="closeNoteModal" 
                    class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700"
                >
                    Cancel
                </button>
                <button 
                    @click="saveNote" 
                    class="px-4 py-2 bg-green text-white rounded hover:bg-green-600"
                >
                    Save Note
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import MainLayout from "@/Layouts/MainLayout.vue";
import Header from "@/Components/Header.vue";
import Pagination from "@/Components/Pagination.vue"
import DataTableShell from "@/Components/DataTableShell.vue";
import ActionDropdown from "@/Components/ActionDropdown.vue";
import axios from 'axios';
import RedirectTabs from "@/Components/RedirectTabs.vue";
import { useToast } from "vue-toastification";
import FinanceCompactSearch from "@/Components/Finance/FinanceCompactSearch.vue";
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
        ActionDropdown,
        RedirectTabs,
        FinanceCompactSearch,
        FinanceDateRangeCompact,
        FinanceYearMonthSelects,
        FinancePeriodPresets,
        FinanceClientNameCell,
    },
    props:{
        orders:Object,
        availableContacts:Array,
    },
    data() {
        return {
            searchInput: '',
            searchQuery: '',
            dateFrom: '',
            dateTo: '',
            fiscalYear: '',
            calendarMonth: '',
            sortOrder: 'desc',
            status: '',
            completionStatus: '',
            contactFilter: '',
            contactSearchTerm: '',
            showContactDropdown: false,
            contactSearchDebounceTimer: null,
            localStatus: {},
            localOrders: [],
            showNoteModal: false,
            selectedOrder: null,
            noteText: '',
            orderThumbnails: {}, // Store thumbnails for each order
            thumbnailLoading: {}, // Track loading state per order
            currentJobIndexes: {}, // Track current job index per order
            currentThumbnailIndexes: {}, // Track current thumbnail index per order/job
            currentFileThumbnailIndexes: {}, // Track current thumbnail index per order/job/file
            thumbnailModal: {
                show: false,
                orderId: null,
                jobId: null,
                fileIndex: null,
                fileName: '',
                thumbnails: [],
                currentIndex: 0
            },
            expandedOrders: {}  // Track which orders have expanded job sections
        };
    },
    computed: {
        filteredContacts() {
            if (!this.contactSearchTerm) {
                return this.availableContacts || [];
            }
            return (this.availableContacts || []).filter(contact =>
                contact.name.toLowerCase().includes(this.contactSearchTerm.toLowerCase())
            );
        },
        selectedContactName() {
            if (!this.contactFilter || !this.availableContacts) {
                return '';
            }
            const contact = this.availableContacts.find(c => c.id == this.contactFilter);
            return contact ? contact.name : '';
        },
        displayValue() {
            // Show search term when searching, selected contact when not searching
            if (this.contactSearchTerm) {
                return this.contactSearchTerm;
            }
            if (this.contactFilter && this.selectedContactName) {
                return this.selectedContactName;
            }
            return '';
        }
    },
    mounted() {
        // Close dropdown when clicking outside
        this.closeDropdownOnOutsideClick = (e) => {
            if (!e.target.closest('.contact-filter')) {
                this.showContactDropdown = false;
            }
        };
        document.addEventListener('click', this.closeDropdownOnOutsideClick);
        
        // Set initial contact search term based on selected contact
        this.updateContactSearchFromFilter();
    },
    beforeUnmount() {
        // Clean up event listener
        if (this.closeDropdownOnOutsideClick) {
            document.removeEventListener('click', this.closeDropdownOnOutsideClick);
        }
        if (this.contactSearchDebounceTimer) {
            clearTimeout(this.contactSearchDebounceTimer);
        }
    },
    methods: {
        rowNumber(index) {
            const currentPage = this.orders?.current_page || 1;
            const perPage = this.orders?.per_page || 20;
            return ((currentPage - 1) * perPage) + index + 1;
        },
        formatDateDisplay(date) {
            if (!date) return 'N/A';
            return new Date(date).toLocaleDateString('en-US', { month: '2-digit', day: '2-digit', year: 'numeric' });
        },
        formatCurrency(value) {
            return Number(value || 0).toFixed(2);
        },
        hasPreviewJobs(order) {
            return !!(order.invoice && order.invoice.jobs && order.invoice.jobs.length > 0);
        },
        formatPaidStatus(status) {
            return status === 'paid' ? 'Paid' : 'Unpaid';
        },
        getPaymentTone(status) {
            return status === 'paid' ? 'success' : 'danger';
        },
        getPaymentMenuGroups() {
            return [
                {
                    label: 'Payment Status',
                    items: [
                        { label: 'Paid', value: 'paid' },
                        { label: 'Unpaid', value: 'unpaid' },
                    ],
                },
            ];
        },
        handlePaymentMenuSelect(orderId, item) {
            this.localStatus[orderId] = item.value;
            this.updateStatus(orderId, item.value);
        },
        // Thumbnail methods
        async loadOrderThumbnails(orderId, jobs) {
            if (this.thumbnailLoading[orderId]) return;
            
            this.thumbnailLoading[orderId] = true;
            
            try {
                const thumbnailsByJob = {};
                
                // Load thumbnails for each job
                for (const job of jobs) {
                    const jobThumbnails = await this.loadJobThumbnails(job.id);
                    thumbnailsByJob[job.id] = jobThumbnails;
                }
                
                this.orderThumbnails[orderId] = thumbnailsByJob;
            } catch (error) {
                console.error('Error loading thumbnails for order:', orderId, error);
                this.orderThumbnails[orderId] = {};
            } finally {
                this.thumbnailLoading[orderId] = false;
            }
        },

        async loadJobThumbnails(jobId) {
            try {
                const response = await axios.get(`/jobs/${jobId}/thumbnails`);
                return response.data.thumbnails || [];
            } catch (error) {
                console.error('Error loading thumbnails for job:', jobId, error);
                return [];
            }
        },

        getJobThumbnails(orderId, jobId) {
            return this.orderThumbnails[orderId]?.[jobId] || [];
        },

        getOrderJobWithThumbnails(order) {
            if (!order.invoice || !order.invoice.jobs) return [];
            
            return order.invoice.jobs.map(job => ({
                ...job,
                thumbnails: this.getJobThumbnails(order.id, job.id)
            }));
        },

        buildIndividualQueryParams() {
            const state = { dateFrom: this.dateFrom, dateTo: this.dateTo };
            normalizeDateRangeFields(state);
            this.dateFrom = state.dateFrom;
            this.dateTo = state.dateTo;
            const params = new URLSearchParams();
            if (this.searchQuery) {
                params.append('searchQuery', this.searchQuery);
            }
            if (this.sortOrder) {
                params.append('sortOrder', this.sortOrder);
            }
            if (this.status) {
                params.append('status', this.status);
            }
            if (this.completionStatus) {
                params.append('completionStatus', this.completionStatus);
            }
            if (this.contactFilter) {
                params.append('contactFilter', this.contactFilter);
            }
            if (this.dateFrom) {
                params.append('date_from', this.dateFrom);
            }
            if (this.dateTo) {
                params.append('date_to', this.dateTo);
            }
            if (this.fiscalYear !== '' && this.fiscalYear != null) {
                params.append('fiscal_year', this.fiscalYear);
            }
            if (this.calendarMonth !== '' && this.calendarMonth != null) {
                params.append('month', this.calendarMonth);
            }
            return params;
        },
        async applyFilter() {
            try {
                const params = this.buildIndividualQueryParams();
                const queryString = params.toString();
                const url = queryString ? `/individual?${queryString}` : '/individual';
                this.$inertia.visit(url);
            } catch (error) {
                console.error(error);
            }
        },
        onSearchSubmit() {
            this.searchQuery = (this.searchInput || '').trim();
            this.applyFilter();
        },
        onDateRangeChange() {
            const state = { dateFrom: this.dateFrom, dateTo: this.dateTo };
            normalizeDateRangeFields(state);
            this.dateFrom = state.dateFrom;
            this.dateTo = state.dateTo;
            this.applyFilter();
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
            this.applyFilter();
        },
        onClearDates() {
            this.dateFrom = '';
            this.dateTo = '';
            this.applyFilter();
        },
        
        // Contact search methods
        setContactSearchDebounced() {
            if (this.contactSearchDebounceTimer) {
                clearTimeout(this.contactSearchDebounceTimer);
            }
            
            this.contactSearchDebounceTimer = setTimeout(() => {
                if (!this.showContactDropdown) {
                    this.showContactDropdown = true;
                }
            }, 100);
        },
        
        selectContact(contactId) {
            this.contactFilter = contactId;
            this.showContactDropdown = false;
            
            // Clear search term when selecting a contact
            this.contactSearchTerm = '';
            
            // Apply the filter
            this.applyFilter();
        },
        
        clearContactFilter() {
            this.contactFilter = '';
            this.contactSearchTerm = '';
            this.applyFilter();
        },
        
        updateContactSearchFromFilter() {
            // Initialize contact search term - no need to set from filter anymore
            // since we show selected contact differently now
        },
        
        async updateStatus(id, paid_status) {
            const toast = useToast();
            try {
                // Check if this is an uncompleted order (temp ID)
                if (id.toString().startsWith('temp_')) {
                    toast.error('Cannot update status for uncompleted orders');
                    return;
                }
                
                await axios.put(`/individual/${id}/status`, { paid_status });
                // Update local status immediately for better UX
                this.localStatus[id] = paid_status;
                toast.success('Payment status updated successfully');
            } catch (e) { 
                console.error(e);
                toast.error('Failed to update payment status');
            }
        },
        handlePageChange(page) {
            const params = this.buildIndividualQueryParams();
            params.set('page', page);
            const queryString = params.toString();
            const url = `/individual?${queryString}`;
            this.$inertia.visit(url);
        },
        openNoteModal(order) {
            this.selectedOrder = order;
            this.noteText = order.notes || '';
            this.showNoteModal = true;
        },
        closeNoteModal() {
            this.showNoteModal = false;
            this.selectedOrder = null;
            this.noteText = '';
        },
        async saveNote() {
            const toast = useToast();
            try {
                // Check if this is a temporary ID (uncompleted order)
                if (this.selectedOrder.id.toString().startsWith('temp_')) {
                    toast.error('Cannot save notes for uncompleted orders');
                    this.closeNoteModal();
                    return;
                }

                await axios.put(`/individual/${this.selectedOrder.id}/notes`, { 
                    notes: this.noteText 
                });
                
                // Update local data
                this.selectedOrder.notes = this.noteText;
                toast.success('Note saved successfully');
                
                this.closeNoteModal();
            } catch (e) {
                console.error('Error saving note:', e);
                toast.error('Failed to save note');
            }
        },

        async loadVisibleOrderThumbnails() {
            if (!this.orders || !this.orders.data) return;
            
            // Load thumbnails for each visible order
            for (const order of this.orders.data) {
                if (order.invoice && order.invoice.jobs && order.invoice.jobs.length > 0) {
                    // Load thumbnail for this order asynchronously
                    this.loadOrderThumbnails(order.id, order.invoice.jobs);
                }
            }
        },

        // Required helper methods
        getJobCountForOrder(order) {
            return order.invoice?.jobs?.length || 0;
        },

        getOrderJobWithThumbnails(order) {
            if (!order.invoice || !order.invoice.jobs) return [];
            
            return order.invoice.jobs.map(job => ({
                ...job,
                thumbnails: this.getJobThumbnails(order.id, job.id)
            }));
        },

        getJobThumbnails(orderId, jobId) {
            return this.orderThumbnails[orderId]?.[jobId] || [];
        },

        getJobById(orderId, jobId) {
            const order = this.orders.data?.find(o => o.id === orderId);
            return order?.invoice?.jobs?.find(job => job.id === jobId);
        },

        getFileNameFromPath(filePath) {
            if (!filePath) return '';
            let fileName = filePath.split('/').pop() || filePath;
            
            // Remove timestamp prefix (e.g., "1759428892_adoadoadoado.pdf")
            // Pattern: numbers followed by underscore at the start
            fileName = fileName.replace(/^\d+_/, '');
            
            return fileName;
        },

        getFileThumbnails(orderId, jobId, fileIndex) {
            const jobThumbnails = this.getJobThumbnails(orderId, jobId) || [];
            const fileName = this.getFileNameFromPath(this.getJobById(orderId, jobId)?.originalFile?.[fileIndex]);
            
            if (!fileName) return [];
            
            // Filter thumbnails that match this file
            return jobThumbnails.filter(thumb => {
                const thumbFileName = thumb.file_name || '';
                return thumbFileName.includes(fileName.replace('.pdf', ''));
            });
        },

        getCurrentFileThumbnail(orderId, jobId, fileIndex) {
            const thumbnails = this.getFileThumbnails(orderId, jobId, fileIndex);
            const currentIndex = this.getCurrentFileThumbnailIndex(orderId, jobId, fileIndex);
            return thumbnails[currentIndex] || thumbnails[0];
        },

        getCurrentFileThumbnailIndex(orderId, jobId, fileIndex) {
            const key = `${orderId}-${jobId}-${fileIndex}`;
            return this.currentFileThumbnailIndexes[key] || 0;
        },

        async loadVisibleOrderThumbnails() {
            if (!this.orders || !this.orders.data) return;
            
            // Load thumbnails for each visible order
            for (const order of this.orders.data) {
                if (order.invoice && order.invoice.jobs && order.invoice.jobs.length > 0) {
                    // Load thumbnail for this order asynchronously
                    this.loadOrderThumbnails(order.id, order.invoice.jobs);
                }
            }
        },

        // Modal methods
        openThumbnailModal(orderId, jobId, fileIndex) {
            const thumbnails = this.getFileThumbnails(orderId, jobId, fileIndex);
            const job = this.getJobById(orderId, jobId);
            const fileName = this.getFileNameFromPath(job?.originalFile?.[fileIndex]) || `File ${fileIndex + 1}`;
            
            if (thumbnails.length === 0) return;
            
            this.thumbnailModal = {
                show: true,
                orderId,
                jobId,
                fileIndex,
                fileName: fileName,
                thumbnails,
                currentIndex: 0
            };
        },

        closeThumbnailModal() {
            this.thumbnailModal.show = false;
        },

        getCurrentModalThumbnail() {
            return this.thumbnailModal.thumbnails[this.thumbnailModal.currentIndex];
        },

        previousModalThumbnail() {
            if (this.thumbnailModal.currentIndex > 0) {
                this.thumbnailModal.currentIndex--;
            }
        },

        nextModalThumbnail() {
            if (this.thumbnailModal.currentIndex < this.thumbnailModal.thumbnails.length - 1) {
                this.thumbnailModal.currentIndex++;
            }
        },

        onThumbnailError() {
            console.warn('Thumbnail failed to load');
        },

        // Accordion methods
        toggleOrderExpansion(orderId) {
            console.log('Toggling order:', orderId, 'Current state:', this.expandedOrders[orderId]);
            const currentState = this.expandedOrders[orderId] || false;
            this.expandedOrders[orderId] = !currentState;
            console.log('New state:', this.expandedOrders[orderId]);
            
            // Force nextTick to ensure DOM updates
            this.$nextTick(() => {
                if (this.expandedOrders[orderId]) {
                    // Load thumbnails when expanding
                    const order = this.orders.data?.find(o => o.id === orderId);
                    if (order && order.invoice && order.invoice.jobs) {
                        this.loadOrderThumbnails(orderId, order.invoice.jobs);
                    }
                }
            });
        },

        isOrderExpanded(orderId) {
            return !!this.expandedOrders[orderId];
        },

        getStatusClass(status) {
            const statusLower = status.toLowerCase().trim();
            switch(statusLower) {
                case 'completed':
                    return 'green-text';
                case 'in progress':
                case 'in-progress':
                    return 'blue-text';
                case 'not started yet':
                case 'not started':
                    return 'orange-text';
                default:
                    console.log('Unknown status:', status); // Debug log
                    return 'orange-text'; // Default to orange for unknown statuses
            }
        },
        getStatusBadgeClass(status) {
            return this.getStatusClass(status);
        }
    },
    created() {
        const url = new URL(window.location.href);
        this.searchQuery = url.searchParams.get('searchQuery') || '';
        this.searchInput = this.searchQuery;
        this.sortOrder = url.searchParams.get('sortOrder') || 'desc';
        this.status = url.searchParams.get('status') || '';
        this.completionStatus = url.searchParams.get('completionStatus') || '';
        this.contactFilter = url.searchParams.get('contactFilter') || '';
        this.dateFrom = url.searchParams.get('date_from') || '';
        this.dateTo = url.searchParams.get('date_to') || '';
        const fy = url.searchParams.get('fiscal_year');
        this.fiscalYear = fy !== null && fy !== '' ? parseInt(fy, 10) : '';
        if (Number.isNaN(this.fiscalYear)) {
            this.fiscalYear = '';
        }
        const mo = url.searchParams.get('month');
        this.calendarMonth = mo !== null && mo !== '' ? parseInt(mo, 10) : '';
        if (Number.isNaN(this.calendarMonth)) {
            this.calendarMonth = '';
        }

        // Initialize local status from props
        if (this.orders && this.orders.data) {
            this.localStatus = Object.fromEntries(this.orders.data.map(o => [o.id, o.paid_status]));
        }
        
        // Load thumbnails for visible orders
        this.loadVisibleOrderThumbnails();
    },

    watch: {
        orders: {
            handler(newOrders) {
                if (newOrders && newOrders.data) {
                    this.loadVisibleOrderThumbnails();
                }
            },
            deep: true
        }
    }
}
</script>

<style scoped lang="scss">

$blue: #1e3a8a;
$green: #22c55e;
$orange: #f97316;
$dark-gray: #374151;
$white: #ffffff;
$ultra-light-gray: #f9fafb;

// Vue transition styles for accordion
.accordion-enter-active, .accordion-leave-active {
    transition: all 0.3s ease;
}

.accordion-enter-from, .accordion-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}

.accordion-enter-to, .accordion-leave-from {
    opacity: 1;
    transform: translateY(0);
}

.edit-container {
    display: flex;
    align-items: center;
    justify-content: center;
}

.toolbar-inline {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 10px 12px;
}

.filter-toolbar-individual {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 10px 12px;
    flex: 1 1 auto;
    min-width: 0;
}

.filter-toolbar-individual .io-field--search {
    flex: 0 1 220px;
    min-width: 160px;
    max-width: 280px;
}

.filter-toolbar-individual .io-field--sort {
    flex: 0 0 150px;
    min-width: 130px;
}

.filter-toolbar-individual .io-field--contact {
    flex: 0 1 220px;
    min-width: 180px;
}

.filter-toolbar-individual .io-field--payment {
    flex: 0 1 120px;
    min-width: 100px;
}

.filter-toolbar-individual .io-field--progress {
    flex: 0 1 140px;
    min-width: 120px;
}

.filter-toolbar-individual .io-field--dates {
    flex: 1 1 280px;
    min-width: 260px;
    max-width: 340px;
}

.filter-toolbar-individual .io-field--ym {
    flex: 0 1 260px;
    min-width: 240px;
}

.presets-inline-individual {
    flex: 0 0 auto;
    align-self: flex-end;
    min-width: 0;
}

@media (min-width: 900px) {
    .presets-inline-individual :deep(.fc-pp__row) {
        flex-wrap: nowrap;
    }
}

@media (max-width: 1100px) {
    .filter-toolbar-individual .io-field--dates {
        flex-basis: 100%;
        max-width: none;
    }

    .filter-toolbar-individual .io-field--ym {
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
select {
    width: 100%;
    min-height: 40px;
    border-radius: 8px;
    padding: 0 12px;
}

.search-input {
    width: 100%;
    min-height: 40px;
    border-radius: 8px;
    padding: 0 14px;
}

.contact-input-row {
    display: flex;
    align-items: center;
    gap: 8px;
}

.contact-dropdown-wrapper {
    width: 100%;
}

.contact-search-input {
    padding-right: 36px;
}

.contact-dropdown-menu {
    position: absolute;
    z-index: 20;
    width: 100%;
    margin-top: 6px;
    max-height: 240px;
    overflow-y: auto;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 10px;
    background: white;
    color: #111827;
    box-shadow: 0 14px 30px rgba(0, 0, 0, 0.2);
}

.contact-dropdown-item,
.contact-dropdown-empty {
    padding: 10px 12px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}

.contact-dropdown-item {
    cursor: pointer;
}

.contact-dropdown-item:hover,
.contact-dropdown-item.selected {
    background: #dbeafe;
}

.contact-dropdown-empty {
    color: #6b7280;
}

.clear-filter-button {
    flex-shrink: 0;
    width: 34px;
    height: 34px;
    border: 1px solid rgba(248, 113, 113, 0.3);
    border-radius: 8px;
    color: #f87171;
    background: rgba(248, 113, 113, 0.08);
}

.blue-text{
    color: white;
    background: linear-gradient(135deg, $blue, darken($blue, 10%));
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-weight: bold;
    box-shadow: 0 2px 8px rgba($blue, 0.3);
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.5px;
}

.bold{
    font-weight: bold;
}

.green-text{
    color: white;
    background: linear-gradient(135deg, $green, darken($green, 10%));
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-weight: bold;
    box-shadow: 0 2px 8px rgba($green, 0.3);
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.5px;
}

.orange-text{
    color: white;
    background: linear-gradient(135deg, $orange, darken($orange, 10%));
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-weight: bold;
    box-shadow: 0 2px 8px rgba($orange, 0.3);
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.5px;
}

.blue{
    background-color: $blue;
}

.green{
    background-color: $green;
}

.green:hover{
    background-color: green;
}

.header{
    display: flex;
    align-items: center;
}

.dark-gray {
    background-color: $dark-gray;
    border-radius: 8px;
    min-height: 20vh;
    min-width: 80vh;
}

.ultra-light-gray{
    background-color: $ultra-light-gray;
}

.sub-title{
    font-size: 20px;
    font-weight: bold;
    align-items: center;
    color: $white;
    margin: 0;
}

.note-preview{
    display: inline-block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    color: rgba(255, 255, 255, 0.68);
    max-width: 160px;
    font-size: 12px;
}

.button-container{
    display: flex-end;
}

.btn {
    padding: 9px 12px;
    color: white;
    font-weight: bold;
    border-radius: 2px;
}

.toolbar-search-button {
    min-width: 110px;
    border: 1px solid rgba(96, 165, 250, 0.45);
    border-radius: 8px;
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.92), rgba(37, 99, 235, 0.92));
    color: $white;
    box-shadow: 0 10px 24px rgba(37, 99, 235, 0.18);
}

.toolbar-search-button:hover {
    background: linear-gradient(135deg, rgba(96, 165, 250, 0.96), rgba(59, 130, 246, 0.96));
}

.create-order{
    background-color: $green;
    color: white;
}

.number-column {
    width: 70px;
}

.order-column {
    width: 170px;
}

.customer-column {
    max-width: 280px;
    width: 18%;
}

.actions-column {
    width: 150px;
}

.actions-header {
    text-align: right;
}

.order-primary-cell {
    min-width: 160px;
}

.cell-primary {
    font-weight: 700;
    color: $white;
}

.cell-secondary {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.68);
}

.status-badge {
    display: inline-flex;
    align-items: center;
    width: fit-content;
}

.payment-select {
    min-height: 38px;
    min-width: 124px;
    border: 1px solid rgba(255, 255, 255, 0.14);
    border-radius: 8px;
    padding: 0 36px 0 12px;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    color: $white;
    font-weight: 700;
    background-color: rgba(255, 255, 255, 0.06);
    background-image:
        linear-gradient(45deg, transparent 50%, rgba(255, 255, 255, 0.68) 50%),
        linear-gradient(135deg, rgba(255, 255, 255, 0.68) 50%, transparent 50%);
    background-position:
        calc(100% - 16px) calc(50% - 2px),
        calc(100% - 11px) calc(50% - 2px);
    background-size: 5px 5px, 5px 5px;
    background-repeat: no-repeat;
}

.payment-select:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}

.payment-paid {
    border-color: rgba(74, 222, 128, 0.32);
    background-color: rgba(74, 222, 128, 0.12);
}

.payment-unpaid {
    border-color: rgba(248, 113, 113, 0.3);
    background-color: rgba(248, 113, 113, 0.1);
}

.note-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 34px;
    padding: 6px 10px;
    border: 1px solid rgba(255, 255, 255, 0.14);
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.06);
    color: $white;
    font-size: 12px;
    font-weight: 700;
    line-height: 1;
    white-space: nowrap;
    margin-bottom: 4px;
    transition: all 0.2s ease;
}

.note-button:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.24);
}

.note-button.note-filled {
    border-color: rgba(74, 222, 128, 0.32);
    background: rgba(74, 222, 128, 0.12);
}

.note-button.note-filled:hover {
    background: rgba(74, 222, 128, 0.18);
    border-color: rgba(74, 222, 128, 0.46);
}

.note-button i {
    font-size: 11px;
}

.note-button-text {
    white-space: nowrap;
}

.actions-cell {
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

.preview-detail-row :deep(td) {
    padding: 0 !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    background: rgba(255, 255, 255, 0.02);
}

.preview-detail-cell {
    text-align: left;
}

.preview-detail-panel {
    padding: 16px;
}

.preview-panel-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
}

.preview-panel-title {
    font-weight: 700;
    color: $white;
}

.preview-panel-count {
    color: rgba(255, 255, 255, 0.65);
    font-size: 12px;
    background: rgba(255, 255, 255, 0.08);
    padding: 3px 8px;
    border-radius: 999px;
}

// Accordion thumbnail container styles
.job-thumbnails-accordion {
    margin-top: 10px;
    width: 100%;
    
    border-top: 1px solid #dee2e6;
    
    .accordion-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        background-color: rgba(255, 255, 255, 0.05);
        cursor: pointer;
        border-radius: 4px;
      
        transition: all 0.2s ease;
        user-select: none;
        
        &:hover {
            background-color: rgba(255, 255, 255, 0.08);
            transform: translateY(-1px);
        }
        
        &:active {
            transform: translateY(0);
        }
        
        .accordion-header-content {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .jobs-title {
            font-weight: bold;
            color: $white;
            font-size: 16px;
        }
        
        .job-count {
            color: #6c757d;
            font-size: 12px;
            background: rgba(255, 255, 255, 0.1);
            padding: 2px 8px;
            border-radius: 12px;
        }
        
        .accordion-toggle {
            color: $white;
            font-size: 14px;
            transition: transform 0.2s ease;
        }
    }
    
    .accordion-content {
        padding: 10px;
        overflow: hidden;
    }
    
    .jobs-container-grid {
        display: flex;
        flex-wrap: nowrap;
        gap: 12px;
        padding: 8px 0;
        justify-content: flex-start;
        align-items: flex-start;
        width: 100%;
        min-width: 0;
        max-width: 100%;
        overflow-x: auto;
        overflow-y: hidden;
        scrollbar-width: thin;
        
        &:empty::after {
            content: "No job previews available";
            color: #6c757d;
            font-size: 14px;
            padding: 20px;
            text-align: center;
            width: 100%;
            display: block;
        }
        
        .thumbnail-card {
            flex: 0 0 auto;
            width: 40px;
            height: 50px;
            overflow: hidden;
            border-radius: 2px;
            transition: all 0.2s ease;
            
            &:hover {
                transform: scale(1.05);
                z-index: 10;
                position: relative;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            }
            
            .thumbnail-clickable {
                position: relative;
                width: 100%;
                height: 100%;
                
                .clickable-thumbnail {
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                    border-radius: 2px;
                    cursor: pointer;
                    transition: all 0.2s ease;
                }
                
                .page-count-mini {
                    position: absolute;
                    top: 2px;
                    right: 2px;
                    background-color: rgba(0, 0, 0, 0.7);
                    color: white;
                    padding: 1px 3px;
                    border-radius: 3px;
                    font-size: 8px;
                    font-weight: bold;
                    line-height: 1;
                }
            }
            
            .thumbnail-loading-mini {
                display: flex;
                align-items: center;
                justify-content: center;
                height: 50px;
                color: #6c757d;
                
                i {
                    font-size: 12px;
                    animation: spin 1s linear infinite;
                }
            }
            
            .thumbnail-placeholder-mini {
                display: flex;
                align-items: center;
                justify-content: center;
                height: 50px;
                background-color: #f8f9fa;
                color: #6c757d;
                
                i {
                    font-size: 16px;
                }
            }
        }
    }
}

// Thumbnail Modal Styles
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

    .thumbnail-modal {
        background: $dark-gray;
        border-radius: 8px;
        max-width: 80vw;
        max-height: 80vh;
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;

            .modal-title {
                font-weight: bold;
                font-size: 16px;
                color: $white;
            }

            .close-btn {
                background: none;
                border: none;
                font-size: 18px;
                cursor: pointer;
                color: $white;
                padding: 5px;
                border-radius: 50%;
                transition: all 0.2s ease;

                &:hover {
                    color: $red;
                }
            }
        }

        .modal-carousel {
            position: relative;
            padding: 20px;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;

            .modal-thumbnail {
                max-width: 100%;
                max-height: 60vh;
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
                bottom: 10px;
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
    }
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

</style>

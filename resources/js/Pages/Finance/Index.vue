<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="invoice2" subtitle="UninvoicedOrders" icon="invoice.png" link="notInvoiced"/>
            <div class="dark-gray p-2 text-white">
                <RedirectTabs :route="$page.url" />
                <div class="form-container p-2 ">
                    <h2 class="sub-title">
                        {{ $t('listOfNotInvoiced') }}
                    </h2>
                    <div class="filter-container flex gap-4 pb-10">
                        <div class="search flex gap-2">
                            <input v-model="searchQuery" placeholder="Enter order number or order name" class="text-black search-input" @keyup.enter="searchInvoices" />
                            <button class="btn create-order1" @click="searchInvoices">Search</button>
                        </div>
                        <div class="flex gap-2 filters-group">
                        <div class="status">
                            
                            <ClientSelectDropdown 
                                v-model="filterClient"
                                :clients="uniqueClients"
                                @change="applyFilter"
                            />
                        </div>
                        <div class="date">
                            <select v-model="sortOrder" class="text-black filter-select" @change="applyFilter">
                                <option value="desc" hidden>Date</option>
                                <option value="desc">Newest to Oldest</option>
                                <option value="asc">Oldest to Newest</option>
                            </select>
                        </div>
                        </div>
                        <div class="button flex gap-3">
                            <button @click="clearAllSelections" v-if="hasSelectedInvoices || filterClient !== 'All'" class="btn create-order1" >
                                Clear Selection <i class="fa-solid fa-times"></i>
                            </button>
                            <button @click="generateInvoices" class="btn create-order" >
                                Generate Invoice <i class="fa-solid fa-file-invoice-dollar"></i>
                            </button>
                        </div>
                    </div>

                    <div v-if="loading" class="loading-container">
                        <div class="loading-spinner">
                            <i class="fa fa-spinner fa-spin"></i>
                            <span>Loading orders...</span>
                        </div>
                    </div>
                    <div v-else-if="filteredInvoices && filteredInvoices.length > 0">
                        <div :class="['border mb-2 invoice-row', getStatusRowClass(invoice.status)]" v-for="invoice in filteredInvoices" :key="invoice.id">
                            <div class="text-black flex justify-between order-info" style="line-height: normal">
                                <div class="p-2 bold" style="font-size: 16px">{{invoice.invoice_title}}</div>
                                <div class="flex" style="font-size: 12px">
                                    <button class="flex items-center p-1" @click="viewInvoice(invoice.id)">
                                        <i class="fa fa-eye bg-gray-300 p-2 rounded" aria-hidden="true"></i>
                                    </button>
                                    <div class="flex items-center p-1">
                                        <input type="checkbox" :id="`invoice-${invoice.id}`" :checked="selectedInvoices[invoice.id]" @change="toggleInvoiceSelection(invoice, $event)" class="bg-gray-200 p-2 rounded px-3 py-3 border-gray-500">
                                    </div>
                                </div>
                            </div>
                            <div class="flex row-columns pl-2 pt-1" style="line-height: initial">
                                <div class="info col-order">
                                    <div>Order</div>
                                    <div class="bold">#{{invoice.id}}</div>
                                </div>
                                <div class="info min-w-80 no-wrap col-client">
                                    <div>Customer</div>
                                    <div class="bold ellipsis">{{ invoice.client.name }}</div>
                                </div>
                                <div class="info col-date">
                                    <div>End Date</div>
                                    <div class="bold truncate">{{ formatDate(invoice.end_date) }}</div>
                                </div>
                                <div class="info col-user">
                                    <div>Created By</div>
                                    <div class="bold truncate">{{ invoice.user.name }}</div>
                                </div>
                                <div class="info col-thumbnails">
                                    <div class="thumbnail-section">
                                        <!-- Display thumbnails for all jobs in this invoice -->
                                        <template v-if="invoice.jobs && invoice.jobs.length > 0">
                                            <div class="invoice-thumbnails-container">
                                                <template v-for="(job, jobIndex) in invoice.jobs" :key="job.id">
                                                    <template v-if="hasDisplayableFiles(job)">
                                                        <!-- Multiple files - display in flex row -->
                                                        <template v-if="hasMultipleFiles(job)">
                                                            <div class="multiple-thumbnails-row">
                                                                <div 
                                                                    v-for="(file, fileIndex) in getJobFiles(job)"
                                                                    :key="`${job.id}-${fileIndex}`"
                                                                    class="file-thumbnail-wrapper"
                                                                    @click="openFileThumbnailModal(job, jobIndex, fileIndex)"
                                                                >
                                                                    <!-- File thumbnail -->
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
                                                                        
                                                                        <!-- Page count indicator -->
                                                                        <div v-if="getAvailableThumbnails(job.id, fileIndex).length > 1" class="page-count-indicator">
                                                                            {{ getAvailableThumbnails(job.id, fileIndex).length }}
                                                                        </div>
                                                                        
                                                                        <!-- File number indicator -->
                                                                        <div class="file-number-indicator">
                                                                            {{ fileIndex + 1 }}
                                                                        </div>
                                                                    </div>
                                                                    
                                                                    <!-- Loading state -->
                                                                    <div v-else-if="thumbnailLoading && thumbnailLoading[job.id]" class="thumbnail-loading-indicator">
                                                                        <i class="fa fa-spinner fa-spin"></i>
                                                                    </div>
                                                                    
                                                                    <!-- No thumbnails available -->
                                                                    <div v-else class="thumbnail-placeholder-icon">
                                                                       <span class="text-xs">No preview</span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </template>
                                                        
                                                        <!-- Single file - centered display -->
                                                        <template v-else-if="hasSingleNewFile(job) || isLegacyJob(job)">
                                                            <div class="single-thumbnail-container">
                                                                <div 
                                                                    class="file-thumbnail-wrapper single-file"
                                                                    @click="openFileThumbnailModal(job, jobIndex, 0)"
                                                                >
                                                                    <!-- Single file thumbnail -->
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
                                                                        
                                                                        <!-- Page count indicator -->
                                                                        <div v-if="getAvailableThumbnails(job.id, 0).length > 1" class="page-count-indicator">
                                                                            {{ getAvailableThumbnails(job.id, 0).length }}
                                                                        </div>
                                                                    </div>
                                                                    
                                                                    <!-- Loading state -->
                                                                    <div v-else-if="thumbnailLoading && thumbnailLoading[job.id]" class="thumbnail-loading-indicator">
                                                                        <i class="fa fa-spinner fa-spin"></i>
                                                                    </div>
                                                                    
                                                                    <!-- No thumbnails available -->
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
                                </div>
                                <div v-if="invoice.LockedNote" class="info locked">
                                    <ViewLockDialog :invoice="invoice"/>
                                </div>
                                <div class="info col-status">
                                    <div>Status</div>
                                    <div :class="[getStatusColorClass(invoice.status), 'bold', 'truncate', 'status-pill']">{{invoice.status}}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <Pagination :pagination="{ data: [], links: invoices?.links || [] }" @pagination-change-page="goToPage"/>
            </div>

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
                        <img 
                            v-if="getCurrentFileThumbnail() && !fileModal.hasError"
                            :src="getModalThumbnailUrl()"
                            :alt="`Page ${fileModal.currentIndex + 1}`"
                            class="modal-thumbnail"
                            @error="onThumbnailError"
                        />
                        <div v-else class="modal-no-thumbnail">
                            <i class="fa fa-image"></i>
                            <p>No preview available</p>
                        </div>
                        
                        <!-- Navigation controls -->
                        <button 
                            v-if="fileModal.thumbnails.length > 1"
                            @click="previousFileThumbnail()"
                            class="modal-nav-btn prev-btn"
                            :disabled="fileModal.currentIndex === 0"
                        >
                            <i class="fa fa-chevron-left"></i>
                        </button>
                        
                        <button 
                            v-if="fileModal.thumbnails.length > 1"
                            @click="nextFileThumbnail()"
                            class="modal-nav-btn next-btn"
                            :disabled="fileModal.currentIndex === fileModal.thumbnails.length - 1"
                        >
                            <i class="fa fa-chevron-right"></i>
                        </button>
                        
                        <!-- Page indicator -->
                        <div v-if="fileModal.thumbnails.length > 1" class="modal-page-indicator">
                            Page {{ fileModal.currentIndex + 1 }} of {{ fileModal.thumbnails.length }}
                        </div>
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
import axios from 'axios';
import {reactive} from "vue";
import OrderJobDetails from "@/Pages/Invoice/OrderJobDetails.vue";
import ViewLockDialog from "@/Components/ViewLockDialog.vue";
import RedirectTabs from "@/Components/RedirectTabs.vue";
import ClientSelectDropdown from "@/Components/ClientSelectDropdown.vue";
import {useToast} from "vue-toastification";

export default {
    components: {Header, MainLayout,Pagination,OrderJobDetails, ViewLockDialog, RedirectTabs, ClientSelectDropdown },
    props:{
        invoices:Object,
    },
    data() {
        return {
            searchQuery: '',
            filterClient: 'All',
            sortOrder: 'desc',
            localInvoices: [],
            uniqueClients:[],
            filteredInvoices: [],
            selectedInvoices:{},
            loading: false,
            perPage: 10,
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
        };
    },
    mounted() {
        this.localInvoices = this.invoices.data.slice();
        this.fetchUniqueClients()
        this.filteredInvoices = this.invoices.data; // Backend now handles the faktura_id filtering
        // Force initial load with testing per-page value
        this.applyFilter(1)
    },
    computed:{
        hasSelectedInvoices() {
            return Object.values(this.selectedInvoices).some(value => value);
        },
        
    },
    methods: {
        formatDate(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            if (isNaN(d.getTime())) return dateStr;
            const dd = String(d.getDate()).padStart(2, '0');
            const mm = String(d.getMonth() + 1).padStart(2, '0');
            const yyyy = d.getFullYear();
            return `${dd}/${mm}/${yyyy}`;
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
                if (remainingSelected.length === 0 && this.filterClient !== 'All') {
                    this.clearAllSelections();
                }
                return;
            }

            // Get IDs of currently selected invoices
            const selectedInvoiceIds = Object.keys(this.selectedInvoices).filter(id => this.selectedInvoices[id]);

            // If this is the first selection OR same client, auto-filter and select
            if (selectedInvoiceIds.length === 0 || 
                (selectedInvoiceIds.length > 0 && invoice.client && selectedInvoiceIds.some(id => {
                    const existingInvoice = this.filteredInvoices.find(inv => inv.id == id);
                    return existingInvoice && existingInvoice.client && existingInvoice.client.name === invoice.client.name;
                }))) {
                
                // Auto-filter to same client if this is the first selection
                if (selectedInvoiceIds.length === 0) {
                    this.autoFilterByClient(invoice.client.name);
                }
                
                // Select the invoice
                this.selectedInvoices[invoice.id] = true;
                return;
            }

            // Different client - show error
            toast.error('You can only select invoices from the same client.');
            event.target.checked = false; // Revert checkbox state
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
        async applyFilter(page = 1) {
            try {
                this.loading = true;
                
                const response = await axios.get('/api/notInvoiced/filtered', {
                    params: {
                        searchQuery: this.searchQuery,
                        sortOrder: this.sortOrder,
                        client: this.filterClient,
                        page: page,
                    },
                });
                
                // Update the filtered invoices directly without showing all orders
                this.filteredInvoices = response.data.data || response.data;
                // Keep pagination links from backend if sent
                if (response.data && response.data.links) {
                    this.invoices.links = response.data.links;
                }
                
                // Build URL with current filters for browser history
                let redirectUrl = '/notInvoiced';
                const params = [];
                
                if (this.searchQuery) {
                    params.push(`searchQuery=${encodeURIComponent(this.searchQuery)}`);
                }
                if (this.sortOrder) {
                    params.push(`sortOrder=${this.sortOrder}`);
                }
                if (this.filterClient && this.filterClient !== 'All') {
                    params.push(`client=${encodeURIComponent(this.filterClient)}`);
                }
                if (page) {
                    params.push(`page=${page}`);
                }
                
                if (params.length > 0) {
                    redirectUrl += '?' + params.join('&');
                }

                // Update URL without full page reload
                window.history.pushState({}, '', redirectUrl);
            } catch (error) {
                console.error('Error applying filters:', error);
            } finally {
                this.loading = false;
            }
        },
        async searchInvoices() {
            // Use the same applyFilter method for consistency
            await this.applyFilter(1);
        },
        goToPage(page){
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
            // Set the client filter and apply it
            this.filterClient = clientName;
            await this.applyFilter(1);
        },
        viewInvoice(id) {
            this.$inertia.visit(`/orders/${id}`);
        },
        clearAllSelections() {
            this.selectedInvoices = {};
            this.filterClient = 'All';
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

.border:nth-child(odd) .order-info {
    background-color: white;
}

.border:nth-child(even) .order-info {
    background-color: rgba(255, 255, 255, 0.65);
}
 
.info {
    min-width: 0;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.filter-container{
    justify-content: space-between;
    flex-wrap: wrap;
}

.filter-container .search {
    flex: 1 1 320px;
}

.filter-container .filters-group {
    flex: 2 1 600px;
    flex-wrap: wrap;
}

.search-input{
    width: 50vh;
    max-width: 100%;
    border-radius: 3px;
}

.filter-select{
    width: 30vh;
    min-width: 200px;
    max-width: 100%;
}

.locked{
    display: flex;
    justify-content: center;
}

select{
    width: 25vh;
    border-radius: 3px;
}

.orange{
    color: $orange;
}

.blue-text{
    color: $blue;
}

.bold{
    font-weight: bold;
}

.green-text{
    color: $green;
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
    justify-content: left;
    border-radius: 8px;
    align-items: center;
    min-height: 20vh;
    min-width: 80vh;
}

.sub-title{
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    color: $white;
}

.button-container{
    display: flex;
    justify-content: end;
}

.btn {
    padding: 9px 12px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    border-radius: 2px;
}

.create-order1{
    background-color: $blue;
    color: white;
}

.create-order{
    background-color: $green;
    color: white;
}

.min-w-80 {
    min-width: 320px;
    flex-shrink: 0;
    display: block;
}

.row-columns {
    gap: 2rem;
    align-items: center;
    background-color: $background-color;
}

.col-status { margin-left: auto; }

.row-columns > .info div:nth-child(2) {
    white-space: nowrap;
}

.col-order { width: 90px; }
.col-client { width: 320px; }
.col-date { width: 170px; }
.col-user { width: 200px; }
.col-thumbnails { width: 180px; }
.col-status { width: 180px; }

.truncate {
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.ellipsis {
    width: 100%;
    text-overflow: ellipsis;
    white-space: nowrap;
    overflow: hidden;
    display: inline-block;
    max-width: 100%;
}

.ellipsis-file {
    text-overflow: ellipsis;
    white-space: nowrap;
    overflow: hidden;
    display: inline-block;
    width: 150px;
}

.w-150 {
    width: 150px;
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

/* Improved invoice row styling */
.invoice-row {
    border: 3px solid rgba(255,255,255,0.25);
    border-radius: 8px;
    overflow: hidden;
    background: rgba(255,255,255,0.06);
    box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    transition: box-shadow 0.2s ease, transform 0.2s ease, border-color 0.2s ease;
}

.invoice-row:nth-child(odd) {
    background-color: rgba(255, 255, 255, 0.05);
}

.invoice-row:nth-child(even) {
    background-color: rgba(255, 255, 255, 0.09);
}

.invoice-row .order-info {
    border-bottom: 1px solid rgba(255, 255, 255, 0.12);
}

.invoice-row .row-columns {
    padding-bottom: 8px;
}

/* Status-based row accents for a more lively UI */
.row-completed {
    border-color: rgba(16, 185, 129, 0.35); /* green */
}

.row-progress {
    border-color: rgba(59, 130, 246, 0.35); /* blue */
}

.row-pending {
    border-color: rgba(234, 179, 8, 0.35); /* amber */
}

/* Status pill styling */
.status-pill {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 9999px;
    background-color: rgba(255,255,255,0.08);
    width: fit-content;
}

@media (max-width: 1024px) {
    .filter-container { gap: 12px; }
    .search-input { width: 100%; }
    .filter-select { width: 100%; }
    .filters-group { flex: 1 1 100%; }
    .row-columns { gap: 1rem; }
    .col-client { width: 220px; }
    .col-user { width: 150px; }
    .col-thumbnails { width: 160px; }
    .col-status { width: 140px; }
}

@media (max-width: 768px) {
    .row-columns { gap: 0.75rem; }
    .col-client { width: 180px; }
    .col-user { width: 120px; }
    .col-thumbnails { width: 120px; }
    .col-status { width: 120px; }
}

@media (max-width: 640px) {
    .filter-container { gap: 8px; flex-wrap: nowrap; overflow-x: hidden; }
    .filters-group { flex-wrap: nowrap; }
    
    /* Responsive thumbnail adjustments */
    .thumbnail-section {
        min-width: 100px;
        max-width: 120px;
    }
    
    .multiple-thumbnails-row {
        gap: 4px;
        max-width: 100px;
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

/* Thumbnail styles */
.thumbnail-section{
    min-width: 140px;
    max-width: 180px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: visible;
    flex-shrink: 0;
}

.invoice-thumbnails-container {
    display: flex;
    align-items: center;
    width: 100%;
    gap: 4px;
}

.multiple-thumbnails-row {
    display: flex;
    flex-direction: row;
    gap: 4px;
    justify-content: center;
    align-items: center;
    max-width: 180px;
}

.single-thumbnail-container {
    display: flex;
    justify-content: center;
    align-items: center;
    max-width: 180px;
}

.file-thumbnail-wrapper {
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

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>


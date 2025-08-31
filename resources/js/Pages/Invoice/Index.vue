<!-- 
    Index.vue - Optimized for Instant Image Display
    
    PERFORMANCE IMPROVEMENTS:
    ✅ Images are now pre-fetched with orders (no separate API calls needed)
    ✅ Thumbnails are pre-loaded for smooth carousel navigation
    ✅ File information is included in the initial order fetch
    ✅ Extended invoice view shows images instantly when expanded
    ✅ No waiting for additional API calls - instant display
    
    DATA FLOW:
    1. Orders are fetched with jobs and file information in one API call
    2. When viewJobs is clicked, images start preloading immediately
    3. Extended view displays images instantly without additional loading
-->
<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="invoice" subtitle="ViewAllInvoices" icon="List.png" link="orders"/>
            <div class="dark-gray p-2 text-white">
                <div class="form-container p-2 ">
                    <h2 class="sub-title">
                        {{ $t('listOfAllOrders') }}
                    </h2>
                    <div class="filter-container flex gap-4 pb-10">
                        <div class="search flex gap-2">
                            <input v-model="searchQuery" placeholder="Enter order number or order name" class="text-black search-input" @keyup.enter="searchInvoices" />
                            <button class="btn create-order1" @click="searchInvoices">Search</button>
                        </div>
                        <div class="flex gap-2 filters-group">
                        <div class="status">
                            <label class="pr-3">Filter orders</label>
                            <select v-model="filterStatus" class="text-black filter-select" >
                                <option value="All" hidden>Status</option>
                                <option value="All">All</option>
                                <option value="Not started yet">Not started yet</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Completed">Completed</option>
                            </select>
                        </div>
                        <div class="client">
                            <select v-model="filterClient" class="text-black filter-select">
                                <option value="All" hidden>Clients</option>
                                <option value="All">All Clients</option>
                                <option v-for="client in uniqueClients" :key="client">{{ client.name }}</option>
                            </select>
                        </div>
                        <div class="date">
                            <select v-model="sortOrder" class="text-black filter-select">
                                <option value="desc" hidden>Date</option>
                                <option value="desc">Newest to Oldest</option>
                                <option value="asc">Oldest to Newest</option>
                            </select>
                        </div>
                            <button @click="applyFilter" class="btn create-order1">Filter</button>

                        </div>
                        <div class="button flex gap-3">
                            <button @click="navigateToCreateOrder" class="btn create-order">
                                Create Order <i class="fa fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div v-if="loading" class="loading-container">
                        <div class="loading-spinner">
                            <i class="fa fa-spinner fa-spin"></i>
                            <span>Loading orders...</span>
                        </div>
                    </div>
                    <div v-else-if="localInvoices && localInvoices.length > 0">
                        <div :class="['border mb-2 invoice-row', getStatusRowClass(invoice.status)]" v-for="invoice in localInvoices" :key="invoice.id">
                            <div class="text-black flex justify-between order-info" style="line-height: normal">
                                <div class="p-2 bold" style="font-size: 16px">{{invoice.invoice_title}}</div>
                                <div class="flex" style="font-size: 12px">
                                    <button class="flex items-center p-1" @click="viewJobs(invoice.id)">
                                        <i v-if="iconStates[invoice.id]" class="fa-solid fa-angles-up bg-gray-300 p-2 rounded" aria-hidden="true"></i>
                                        <i v-else class="fa-solid fa-angles-down bg-gray-300 p-2 rounded" aria-hidden="true"></i>
                                    </button>
                                    <button class="flex items-center p-1" @click="viewInvoice(invoice.id)">
                                    <i class="fa fa-eye bg-gray-300 p-2 rounded" aria-hidden="true"></i>
                                    </button>
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
                                    <div  class="bold truncate">{{ formatDate(invoice.end_date) }}</div>
                                </div>
                                <div class="info col-user">
                                    <div>Created By</div>
                                    <div  class="bold truncate">{{ invoice.user.name }}</div>
                                </div>
                                <div v-if="invoice.LockedNote" class="info locked">
                                    <ViewLockDialog :invoice="invoice"/>
                                </div>
                                <div class="info col-status">
                                    <div>Status</div>
                                    <div :class="[getStatusColorClass(invoice.status), 'bold', 'truncate', 'status-pill']">{{invoice.status}}</div>
                                </div>
                            </div>
                                <div v-if="currentInvoiceId" class="job-details-container" :class="{ active: currentInvoiceId === invoice.id }">
                                    <div v-if="currentInvoiceId===invoice.id" class="bgJobs text-white p-2 bold" style="line-height: normal;">
                                        Jobs for Order #{{invoice.id}} {{invoice.invoice_title}} - Images are pre-loaded for instant display
                                    </div>
                                    <div v-if="currentInvoiceId===invoice.id" class="jobInfo border-b" v-for="(job,index) in invoice.jobs">
                                            <div class=" jobInfo flex justify-between gap-1">
                                            <div class="text-white bold p-1 ellipsis-file">
                                                #{{index+1}} 
                                                <template v-if="hasMultipleFiles(job)">
                                                    {{ getJobFiles(job).length }} files
                                                </template>
                                                <template v-else-if="hasSingleNewFile(job)">
                                                    {{ getFileName(getJobFiles(job)[0]) }}
                                                </template>
                                                <template v-else>
                                                    {{job.file}}
                                                </template>
                                            </div>
                                            <div class="p-1 img">
                                                <!-- Multiple files preview (2 or more files) - Tiny carousel for instant preview -->
                                                <template v-if="hasMultipleFiles(job)">
                                                    <div class="multiple-files-carousel-wrapper">
                                                        <div class="multiple-files-carousel">
                                                            <div class="carousel-container">
                                                                                                                        <div class="carousel-track" :style="{ transform: `translateX(-${getCurrentFileIndex(job) * 33.333}%)` }">
                                                            <div v-for="(file, fileIndex) in getJobFiles(job).slice(0, 3)" :key="fileIndex" class="carousel-slide">
                                                                <img 
                                                                    v-if="shouldAttemptImageLoad(job, fileIndex)"
                                                                    :src="getThumbnailUrl(job.id, fileIndex)" 
                                                                    :alt="`File ${fileIndex + 1}`" 
                                                                    class="carousel-thumbnail clickable-thumbnail"
                                                                    @click="toggleFilesModal(job, index)"
                                                                    @error="handleThumbnailError($event, job, fileIndex)"
                                                                />
                                                                <div v-else class="carousel-thumbnail-placeholder clickable-thumbnail" @click="toggleFilesModal(job, index)">
                                                                    <i class="fa fa-file-o"></i>
                                                                </div>
                                                                <div class="file-indicator">{{ fileIndex + 1 }}</div>
                                                            </div>
                                                        </div>
                                                            </div>
                                                            <!-- File counter overlay -->
                                                            <div class="file-counter-overlay">
                                                                <span class="file-counter-text">{{ getCurrentFileIndex(job) + 1 }}/{{ getJobFiles(job).length }}</span>
                                                            </div>
                                                        </div>
                                                        <!-- Carousel navigation buttons - positioned outside the box -->
                                                        <button 
                                                            v-if="getJobFiles(job).length > 1" 
                                                            @click="previousCarouselImage(job, $event)" 
                                                            class="carousel-nav-btn carousel-prev"
                                                            title="Previous image"
                                                        >
                                                            <i class="fa fa-chevron-left"></i>
                                                        </button>
                                                        <button 
                                                            v-if="getJobFiles(job).length > 1" 
                                                            @click="nextCarouselImage(job, $event)" 
                                                            class="carousel-nav-btn carousel-next"
                                                            title="Next image"
                                                        >
                                                            <i class="fa fa-chevron-right"></i>
                                                        </button>

                                                    </div>
                                                </template>
                                                <!-- Single file preview (new system with 1 file) - Images are pre-loaded for instant display -->
                                                <template v-else-if="hasSingleNewFile(job)">
                                                    <img 
                                                        v-if="shouldAttemptImageLoad(job, 0)"
                                                        :src="getThumbnailUrl(job.id, 0)" 
                                                        alt="Job Image" 
                                                        class="jobImg single-file-preview" 
                                                        @click="openFilePreview(job, 0)" 
                                                        @error="handleThumbnailError($event, job, 0)"
                                                    />
                                                    <div v-else class="image-error-placeholder">
                                                        <i class="fa fa-file-o"></i>
                                                        <span>File not found</span>
                                                    </div>
                                                </template>
                                                <!-- Legacy single file preview - Images are pre-loaded for instant display -->
                                                <template v-else>
                                                    <img 
                                                        v-if="shouldAttemptImageLoad(job, 'legacy')"
                                                        :src="getLegacyImageUrl(job)" 
                                                        alt="Job Image" 
                                                        class="jobImg single-file-preview" 
                                                        @click="openSingleFileModal(job)"
                                                        @error="handleLegacyImageError($event, job)"
                                                    />
                                                    <div v-else class="image-error-placeholder">
                                                        <i class="fa fa-file-o"></i>
                                                        <span>File not found</span>
                                                    </div>
                                                </template>
                                            </div>
                                            <div v-if="job.total_area_m2" class="p-1 w-160">{{$t('Total Area')}}: <span class="bold">{{ formatTotalArea(job) }} m²</span> </div>
                                            <div class="p-1 w-150">{{$t('Quantity')}}: <span class="bold">{{job.quantity}}</span> </div>
                                            <div class="p-1 w-150">{{$t('Copies')}}: <span class="bold">{{job.copies}}</span> </div>
                                        </div>
                                        <div class="ultra-light-gray pt-4">
                                        <OrderJobDetails :job="job" :invoice-id="invoice.id"/>
                                        </div>
                                    </div>
                                </div>
                        </div>
                    </div>
                </div>
                <!-- Custom Pagination Controls -->
                <div v-if="!loading && totalPages > 1" class="pagination-container">
                    <div class="pagination-info">
                        Showing {{ (currentPage - 1) * perPage + 1 }} to {{ Math.min(currentPage * perPage, totalInvoices) }} of {{ totalInvoices }} orders
                    </div>
                    <div class="pagination-controls">
                        <button 
                            @click="changePage(currentPage - 1)" 
                            :disabled="currentPage <= 1"
                            class="pagination-btn"
                        >
                            <i class="fa fa-chevron-left"></i> Previous
                        </button>
                        
                        <div class="page-numbers">
                            <button 
                                v-for="page in getVisiblePages()" 
                                :key="page"
                                @click="changePage(page)"
                                :class="['page-btn', { active: page === currentPage }]"
                            >
                                {{ page }}
                            </button>
                        </div>
                        
                        <button 
                            @click="changePage(currentPage + 1)" 
                            :disabled="currentPage >= totalPages"
                            class="pagination-btn"
                        >
                            Next <i class="fa fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Multiple Files Modal -->
            <div v-if="showFilesModal" class="files-modal" @click="closeFilesModal">
                <div class="files-modal-content" @click.stop>
                    <div class="files-modal-header">
                        <h3>Files for Job #{{ currentJobIndex + 1 }}</h3>
                        <button @click="closeFilesModal" class="close-btn">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                    <div class="files-modal-body">
                        <div class="thumbnail-grid">
                            <div 
                                v-for="(file, fileIndex) in getJobFiles(currentJob)" 
                                :key="fileIndex" 
                                class="thumbnail-item"
                                @click="openFilePreview(currentJob, fileIndex)"
                            >
                                <img 
                                    :src="getThumbnailUrl(currentJob.id, fileIndex)" 
                                    :alt="`File ${fileIndex + 1}`" 
                                    class="thumbnail-image"
                                    @error="handleThumbnailError($event, fileIndex)"
                                />
                                <div class="thumbnail-number">{{ fileIndex + 1 }}</div>
                                <div class="file-name">{{ getFileName(file) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Single File Modal -->
            <div v-if="showSingleFileModal" class="single-file-modal" @click="closeSingleFileModal">
                <div class="single-file-modal-content" @click.stop>
                    <div class="single-file-modal-header">
                        <h3>Job File Preview</h3>
                        <button @click="closeSingleFileModal" class="close-btn">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                    <div class="single-file-modal-body">
                        <img :src="getImageUrl(currentSingleJob?.id)" :alt="currentSingleJob?.file" class="single-file-image"/>
                    </div>
                </div>
            </div>

            <!-- PDF Preview Modal -->
            <div v-if="showPdfModal" class="pdf-modal" @click="closePdfModal">
                <div class="pdf-modal-content" @click.stop>
                    <div class="pdf-modal-header">
                        <h3>File {{ currentFileIndex + 1 }} of {{ getJobFiles(currentJob).length }}</h3>
                        <button @click="closePdfModal" class="close-btn">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                    <div class="pdf-modal-body">
                        <iframe 
                            v-if="currentJob && currentFileIndex !== null"
                            :src="getOriginalFileUrl(currentJob.id, currentFileIndex)" 
                            class="pdf-iframe"
                        ></iframe>
                    </div>
                    <div class="pdf-modal-footer" v-if="getJobFiles(currentJob).length > 1">
                        <button 
                            @click="previousFile" 
                            :disabled="currentFileIndex === 0"
                            class="nav-btn"
                        >
                            <i class="fa fa-chevron-left"></i> Previous
                        </button>
                        <span class="file-counter">{{ currentFileIndex + 1 }} / {{ getJobFiles(currentJob).length }}</span>
                        <button 
                            @click="nextFile" 
                            :disabled="currentFileIndex === getJobFiles(currentJob).length - 1"
                            class="nav-btn"
                        >
                            Next <i class="fa fa-chevron-right"></i>
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
import AdvancedPagination from "@/Components/AdvancedPagination.vue"
import axios from 'axios';
import {reactive} from "vue";
import OrderJobDetails from "@/Pages/Invoice/OrderJobDetails.vue";
import ViewLockDialog from "@/Components/ViewLockDialog.vue";

export default {
    components: {Header, MainLayout, AdvancedPagination, OrderJobDetails, ViewLockDialog },
    props:{
      invoices:Object,
    },
    data() {
        return {
            searchQuery: '',
            filterStatus: 'All',
            filterClient: 'All',
            sortOrder: 'desc',
            localInvoices: [],
            uniqueClients:[],
            currentInvoiceId: null,
            iconStates : reactive({}),
            showFilesModal: false,
            showSingleFileModal: false,
            showPdfModal: false,
            currentJob: null,
            currentJobIndex: null,
            currentSingleJob: null,
            currentFileIndex: null,
            carouselIndices: {},
            // Pagination properties
            currentPage: 1,
            perPage: 10,
            totalPages: 1,
            totalInvoices: 0,
            loading: false,
            // Image error tracking
            imageErrors: {},
        };
    },
    computed: {
        preserveParams() {
            const params = {};
            
            if (this.searchQuery) {
                params.searchQuery = this.searchQuery;
            }
            if (this.filterStatus && this.filterStatus !== 'All') {
                params.status = this.filterStatus;
            }
            if (this.sortOrder) {
                params.sortOrder = this.sortOrder;
            }
            if (this.filterClient && this.filterClient !== 'All') {
                params.client = this.filterClient;
            }
            
            return params;
        }
    },
    mounted() {
        // Initialize with existing data
        this.localInvoices = this.invoices.data ? this.invoices.data.slice() : [];
        this.fetchUniqueClients();
        
        // Initialize icon states
        if (this.invoices.data) {
            this.invoices.data.forEach(invoice => {
                this.iconStates[invoice.id] = false;
            });
        }
        
        // Initialize pagination data
        if (this.invoices) {
            this.currentPage = this.invoices.current_page || 1;
            this.totalPages = this.invoices.last_page || 1;
            this.totalInvoices = this.invoices.total || 0;
        }
        
        // Get URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        this.searchQuery = urlParams.get('searchQuery') || '';
        this.filterStatus = urlParams.get('status') || 'All';
        this.filterClient = urlParams.get('client') || 'All';
        this.sortOrder = urlParams.get('sortOrder') || 'desc';
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
        getLegacyImageUrl(job) {
            return route ? route('jobs.viewLegacyFile', { jobId: job.id }) : `/jobs/${job.id}/view-legacy-file`;
        },
        
        handleLegacyImageError(event, job) {
            // Mark legacy image as failed
            const jobKey = `${job.id}_legacy`;
            this.imageErrors[jobKey] = true;
            
            // Hide the broken image and show a placeholder
            const parentElement = event.target.parentElement;
            if (parentElement) {
                const placeholder = document.createElement('div');
                placeholder.className = 'image-error-placeholder';
                placeholder.innerHTML = '<i class="fa fa-file-o"></i><span>File not found</span>';
                
                event.target.style.display = 'none';
                parentElement.appendChild(placeholder);
            }
            
            console.log(`Legacy image failed for job ${job.id}, marked as failed`);
        },
        viewJobs(invoiceId) {
            if (this.currentInvoiceId && this.currentInvoiceId !== invoiceId) {
                this.iconStates[this.currentInvoiceId] = false;
                this.currentInvoiceId = null;
            }

            // Toggle the icon state for the clicked invoice
            this.currentInvoiceId = this.currentInvoiceId === invoiceId ? null : invoiceId;
            this.iconStates[invoiceId] = !this.iconStates[invoiceId];
            
                                                    // If we're expanding the invoice, start preloading images immediately
            if (this.currentInvoiceId === invoiceId) {
                const invoice = this.invoices.data.find(inv => inv.id === invoiceId);
                if (invoice && invoice.jobs) {
                    console.log('📁 Found invoice with jobs:', invoice.id, 'Jobs count:', invoice.jobs.length);
                    // Initialize carousel indices for multiple file jobs
                    this.initializeCarouselIndices(invoice);
                    this.$nextTick(() => {
                        this.preloadThumbnailsForInvoice(invoice);
                    });
                }
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
        async applyFilter() {
            try {
                this.loading = true;
                this.currentPage = 1; // Reset to first page when filtering
                
                const response = await axios.get('/orders', {
                    params: {
                        page: this.currentPage,
                        per_page: this.perPage,
                        searchQuery: this.searchQuery,
                        status: this.filterStatus,
                        sortOrder: this.sortOrder,
                        client: this.filterClient,
                    },
                });
                
                // Update pagination data
                this.localInvoices = response.data.data || response.data;
                this.currentPage = response.data.current_page || 1;
                this.totalPages = response.data.last_page || 1;
                this.totalInvoices = response.data.total || 0;
                
                // Build URL with current filters
                let redirectUrl = '/orders';
                const params = [];
                
                if (this.searchQuery) {
                    params.push(`searchQuery=${encodeURIComponent(this.searchQuery)}`);
                }
                if (this.filterStatus && this.filterStatus !== 'All') {
                    params.push(`status=${this.filterStatus}`);
                }
                if (this.sortOrder) {
                    params.push(`sortOrder=${this.sortOrder}`);
                }
                if (this.filterClient && this.filterClient !== 'All') {
                    params.push(`client=${encodeURIComponent(this.filterClient)}`);
                }
                
                if (params.length > 0) {
                    redirectUrl += '?' + params.join('&');
                }

                this.$inertia.visit(redirectUrl);
            } catch (error) {
                console.error('Error applying filters:', error);
            } finally {
                this.loading = false;
            }
        },
        async searchInvoices() {
            try {
                this.loading = true;
                this.currentPage = 1; // Reset to first page when searching
                
                const response = await axios.get('/orders', {
                    params: {
                        page: this.currentPage,
                        per_page: this.perPage,
                        searchQuery: this.searchQuery
                    }
                });
                
                // Update pagination data
                this.localInvoices = response.data.data || response.data;
                this.currentPage = response.data.current_page || 1;
                this.totalPages = response.data.last_page || 1;
                this.totalInvoices = response.data.total || 0;
                
                // Navigate to search results
                const searchUrl = this.searchQuery 
                    ? `/orders?searchQuery=${encodeURIComponent(this.searchQuery)}`
                    : '/orders';
                this.$inertia.visit(searchUrl);
            } catch (error) {
                console.error('Error searching invoices:', error);
            } finally {
                this.loading = false;
            }
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
            this.$inertia.visit(`/orders/${id}`);
        },
        navigateToCreateOrder(){
            this.$inertia.visit(`/orders/create`);
        },
        hasMultipleFiles(job) {
            // Check if job has originalFile array (new system) and has 2 or more files
            return Array.isArray(job.originalFile) && job.originalFile.length > 1;
        },
        hasSingleNewFile(job) {
            // Check if job has originalFile array (new system) with exactly 1 file
            return Array.isArray(job.originalFile) && job.originalFile.length === 1;
        },
        getJobFiles(job) {
            // Return originalFile array for new system, or create array from legacy file
            if (Array.isArray(job.originalFile)) {
                return job.originalFile;
            }
            return job.file ? [job.file] : [];
        },
        toggleFilesModal(job, index) {
            this.currentJob = job;
            this.currentJobIndex = index;
            this.showFilesModal = true;
        },
        openSingleFileModal(job) {
            this.currentSingleJob = job;
            this.showSingleFileModal = true;
        },
        closeFilesModal() {
            this.showFilesModal = false;
            this.currentJob = null;
            this.currentJobIndex = null;
        },
        closeSingleFileModal() {
            this.showSingleFileModal = false;
            this.currentSingleJob = null;
        },
        closePdfModal() {
            this.showPdfModal = false;
            this.currentJob = null;
            this.currentFileIndex = null;
        },
        openFilePreview(job, fileIndex) {
            this.currentJob = job;
            this.currentFileIndex = fileIndex;
            this.showFilesModal = false;
            this.showPdfModal = true;
        },
        getThumbnailUrl(jobId, fileIndex) {
            // Add cache-busting timestamp to prevent stale cache issues
            const url = route('jobs.viewThumbnail', { jobId: jobId, fileIndex: fileIndex });
            const ts = Date.now();
            return `${url}?t=${ts}`;
        },
        getOriginalFileUrl(jobId, fileIndex) {
            return route('jobs.viewOriginalFile', { jobId: jobId, fileIndex: fileIndex });
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
            
            console.log(`Thumbnail loading failed for file index ${fileIndex}, marked as failed`);
        },
        
        shouldAttemptImageLoad(job, fileIndex) {
            if (fileIndex === 'legacy') {
                const jobKey = `${job.id}_legacy`;
                return !this.imageErrors[jobKey];
            }
            const jobKey = `${job.id}_${fileIndex}`;
            return !this.imageErrors[jobKey];
        },
        
        // Image preloading utilities for instant display
        preloadThumbnailsForInvoice(invoice) {
            if (!invoice || !invoice.jobs) return;
            
            console.log('🚀 Preloading thumbnails for invoice:', invoice.id, 'with', invoice.jobs.length, 'jobs');
            
            // Preload first thumbnail for each job immediately
            invoice.jobs.forEach(job => {
                if (this.hasDisplayableFiles(job)) {
                    this.preloadFirstThumbnail(job);
                    
                    // Also preload next few thumbnails for smooth carousel navigation
                    if (this.hasMultipleFiles(job) && job.originalFile && job.originalFile.length > 1) {
                        const filesToPreload = Math.min(3, job.originalFile.length);
                        console.log(`📁 Preloading ${filesToPreload} thumbnails for job ${job.id}`);
                        for (let i = 1; i < filesToPreload; i++) {
                            this.preloadImage(this.getThumbnailUrl(job.id, i));
                        }
                    }
                }
            });
        },
        
        preloadFirstThumbnail(job) {
            if (this.hasMultipleFiles(job)) {
                // Preload first thumbnail for new system
                this.preloadImage(this.getThumbnailUrl(job.id, 0));
            } else if (job.file && job.file !== 'placeholder.jpeg') {
                // Preload legacy image
                this.preloadImage(this.getLegacyImageUrl(job));
            }
        },
        
        preloadImage(src) {
            const img = new Image();
            img.src = src;
            // Optional: Add error handling
            img.onerror = () => console.log('Failed to preload image:', src);
        },
        
        hasDisplayableFiles(job) {
            // Check if job has any files to display (new or legacy system)
            if (this.hasMultipleFiles(job)) {
                return job.originalFile && Array.isArray(job.originalFile) && job.originalFile.length > 0;
            }
            return job.file && job.file !== 'placeholder.jpeg';
        },
        
        getCurrentFileIndex(job) {
            // Get current carousel index for this job, default to 0
            return this.carouselIndices[job.id] || 0;
        },
        
        nextCarouselImage(job, event) {
            event.stopPropagation(); // Prevent modal from opening
            const files = this.getJobFiles(job);
            const currentIndex = this.getCurrentFileIndex(job);
            const nextIndex = (currentIndex + 1) % Math.min(files.length, 3);
            this.carouselIndices[job.id] = nextIndex;
        },
        
        previousCarouselImage(job, event) {
            event.stopPropagation(); // Prevent modal from opening
            const files = this.getJobFiles(job);
            const currentIndex = this.getCurrentFileIndex(job);
            const prevIndex = currentIndex === 0 ? Math.min(files.length - 1, 2) : currentIndex - 1;
            this.carouselIndices[job.id] = prevIndex;
        },
        
        initializeCarouselIndices(invoice) {
            // Initialize carousel indices for all jobs with multiple files
            invoice.jobs.forEach(job => {
                if (this.hasMultipleFiles(job)) {
                    if (!this.carouselIndices.hasOwnProperty(job.id)) {
                        this.carouselIndices[job.id] = 0;
                    }
                }
            });
        },
        
        async changePage(page) {
            if (page >= 1 && page <= this.totalPages && page !== this.currentPage) {
                try {
                    this.loading = true;
                    this.currentPage = page;
                    
                    const response = await axios.get('/orders', {
                        params: {
                            page: this.currentPage,
                            per_page: this.perPage,
                            searchQuery: this.searchQuery,
                            status: this.filterStatus,
                            sortOrder: this.sortOrder,
                            client: this.filterClient,
                        },
                    });
                    
                    // Update pagination data
                    this.localInvoices = response.data.data || response.data;
                    this.currentPage = response.data.current_page || page;
                    this.totalPages = response.data.last_page || this.totalPages;
                    this.totalInvoices = response.data.total || this.totalInvoices;
                    
                    // Reset carousel indices for new page
                    this.carouselIndices = {};
                    
                } catch (error) {
                    console.error('Error changing page:', error);
                } finally {
                    this.loading = false;
                }
            }
        },
        
        getVisiblePages() {
            const pages = [];
            const maxVisible = 5;
            
            if (this.totalPages <= maxVisible) {
                // Show all pages if total is small
                for (let i = 1; i <= this.totalPages; i++) {
                    pages.push(i);
                }
            } else {
                // Show smart pagination with ellipsis
                if (this.currentPage <= 3) {
                    // Near start: show 1, 2, 3, 4, 5, ..., last
                    for (let i = 1; i <= 5; i++) {
                        pages.push(i);
                    }
                    if (this.totalPages > 5) {
                        pages.push('...');
                        pages.push(this.totalPages);
                    }
                } else if (this.currentPage >= this.totalPages - 2) {
                    // Near end: show 1, ..., last-4, last-3, last-2, last-1, last
                    pages.push(1);
                    pages.push('...');
                    for (let i = this.totalPages - 4; i <= this.totalPages; i++) {
                        pages.push(i);
                    }
                } else {
                    // Middle: show 1, ..., current-1, current, current+1, ..., last
                    pages.push(1);
                    pages.push('...');
                    for (let i = this.currentPage - 1; i <= this.currentPage + 1; i++) {
                        pages.push(i);
                    }
                    pages.push('...');
                    pages.push(this.totalPages);
                }
            }
            
            return pages;
        },
        getFileName(filePath) {
            // Extract filename from path
            if (typeof filePath === 'string') {
                return filePath.split('/').pop() || filePath;
            }
            return 'Unknown file';
        },
        previousFile() {
            if (this.currentFileIndex > 0) {
                this.currentFileIndex--;
            }
        },
        nextFile() {
            if (this.currentFileIndex < this.getJobFiles(this.currentJob).length - 1) {
                this.currentFileIndex++;
            }
        },
        formatTotalArea(job) {
            try {
                const direct = parseFloat(job?.total_area_m2);
                if (!isNaN(direct) && direct > 0) return direct.toFixed(4);
                const computed = parseFloat(job?.computed_total_area_m2);
                if (!isNaN(computed) && computed > 0) return computed.toFixed(4);
                if (Array.isArray(job?.dimensions_breakdown) && job.dimensions_breakdown.length > 0) {
                    const sum = job.dimensions_breakdown.reduce((acc, f) => acc + (isNaN(parseFloat(f?.total_area_m2)) ? 0 : parseFloat(f?.total_area_m2)), 0);
                    if (sum > 0) return sum.toFixed(4);
                }
                const w = parseFloat(job?.width);
                const h = parseFloat(job?.height);
                if (!isNaN(w) && !isNaN(h) && w > 0 && h > 0) {
                    return ((w * h) / 1000000).toFixed(4);
                }
                return '0.0000';
            } catch (e) {
                return '0.0000';
            }
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
    flex: 2 1 500px;
    flex-wrap: wrap;
}
.search-input{
    width: 50vh;
    max-width: 100%;
    border-radius: 3px;
}
.filter-select{
    width: 25vh;
    max-width: 100%;
}
.jobInfo{

    align-items: center;
}
.locked{
    display: flex;
    justify-content: center;
}
.img{
    width: 80px; /* Increased to accommodate the new carousel size */
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: visible; /* Changed from hidden to allow arrows to be visible outside */
    border-radius: 4px;
}

.jobImg {
    cursor: pointer;
    width: 100%;
    height: 100%;
    object-fit: contain;
    border-radius: 4px;
    transition: transform 0.2s;
    background-color: #f8f9fa;
    
    &:hover {
        transform: scale(1.05);
    }
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
.bgJobs{
    background-color: $ultra-light-gray;
}
.dark-gray {
    background-color: $dark-gray;
    justify-content: left;
    border-radius: 8px;
    align-items: center;
    min-height: 20vh;
    min-width: 80vh;
}
.ultra-light-gray{
    background-color: $ultra-light-gray;
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
.job-details-container {
    max-height: 0;
    opacity: 0;
    overflow: hidden;
    transition: max-height 0.3s ease-in-out, opacity 0.3s ease-in-out;
    will-change: max-height, opacity;
}

.job-details-container.active {
    max-height: 550px;
    opacity: 1;
}
.min-w-80 {
    min-width: 320px;
    flex-shrink: 0;
    display: block;
}
.row-columns {
    gap: 2rem; /* approximate of gap-40 */
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

.multiple-files-preview {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #4a5568;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.2s;
    
    &:hover {
        background-color: $light-gray;
    }
}

.multiple-files-carousel-wrapper {
    position: relative;
    width: 80px; /* Increased width to accommodate outside arrows */
    height: 60px; /* Increased height for better proportions */
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 8px 0; /* Add top and bottom padding */
}

.multiple-files-carousel {
    width: 60px; /* Increased from 50px */
    height: 60px; /* Increased from 50px */
    position: relative;
    border-radius: 6px; /* Slightly larger radius */
    overflow: hidden;
    background-color: #f8f9fa;
    flex-shrink: 0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1); /* Subtle shadow */
}

.carousel-container {
    width: 100%;
    height: 100%;
    position: relative;
    overflow: hidden;
}

.carousel-track {
    display: flex;
    width: 300%; /* 3 slides * 100% */
    height: 100%;
    transition: transform 0.3s ease;
    transform: translateX(0); /* Initial position */
}

.carousel-slide {
    width: 33.333%; /* 100% / 3 slides */
    height: 100%;
    position: relative;
    flex-shrink: 0;
    overflow: hidden;
}

.carousel-thumbnail {
    width: 100%;
    height: 100%;
    object-fit: contain;
    border-radius: 6px;
    cursor: pointer;
    transition: transform 0.2s ease;
    
    &:hover {
        transform: scale(1.05);
    }
}

.clickable-thumbnail {
    cursor: pointer;
    transition: transform 0.2s ease;
    
    &:hover {
        transform: scale(1.05);
    }
}

.carousel-thumbnail-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #e2e8f0;
    border-radius: 6px;
    color: #64748b;
    
    i {
        font-size: 18px; /* Slightly smaller icon */
    }
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

.file-indicator {
    position: absolute;
    top: 3px;
    right: 3px;
    background-color: rgba(0, 0, 0, 0.8);
    color: white;
    font-size: 9px;
    padding: 2px 5px;
    border-radius: 3px;
    font-weight: bold;
    min-width: 16px;
    text-align: center;
}

.carousel-nav-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background-color: rgba(59, 130, 246, 0.9); /* Blue background */
    border: none;
    color: white;
    width: 20px; /* Slightly larger */
    height: 20px; /* Slightly larger */
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    transition: all 0.2s ease;
    z-index: 10;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    
    &:hover {
        background-color: rgba(59, 130, 246, 1);
        transform: translateY(-50%) scale(1.1);
        box-shadow: 0 3px 6px rgba(0,0,0,0.3);
    }
    
    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        background-color: #6b7280;
    }
}

.carousel-prev {
    left: -25px; /* Position further outside the box */
}

.carousel-next {
    right: -25px; /* Position further outside the box */
}

.file-counter-overlay {
    position: absolute;
    bottom: 3px;
    left: 3px;
    background-color: rgba(0, 0, 0, 0.85);
    color: white;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 8px;
    font-weight: bold;
    z-index: 5;
    min-width: 20px;
    text-align: center;
}

.file-counter-text {
    font-size: 8px;
    line-height: 1;
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

.pagination-container {
    margin-top: 20px;
    padding: 20px;
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    color: white;
}

.pagination-info {
    text-align: center;
    margin-bottom: 15px;
    font-size: 14px;
    color: rgba(255, 255, 255, 0.8);
}

.pagination-controls {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.pagination-btn {
    background-color: $blue;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
    transition: background-color 0.2s;
    
    &:hover:not(:disabled) {
        background-color: darken($blue, 10%);
    }
    
    &:disabled {
        background-color: #6b7280;
        cursor: not-allowed;
        opacity: 0.6;
    }
}

.page-numbers {
    display: flex;
    gap: 5px;
    align-items: center;
}

.page-btn {
    background-color: rgba(255, 255, 255, 0.1);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.2);
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
    min-width: 36px;
    transition: all 0.2s;
    
    &:hover:not(.active) {
        background-color: rgba(255, 255, 255, 0.2);
        border-color: rgba(255, 255, 255, 0.4);
    }
    
    &.active {
        background-color: $blue;
        border-color: $blue;
        font-weight: bold;
    }
}

.single-file-preview {
    width: 50px;
    height: 50px;
    object-fit: contain;
    border-radius: 4px;
    cursor: pointer;
    transition: transform 0.2s;
    background-color: #f8f9fa;
    
    &:hover {
        transform: scale(1.05);
    }
}





/* Files Modal Styles */
.files-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    background-color: rgba(0, 0, 0, 0.8);
    z-index: 1000;
}

.files-modal-content {
    background-color: #2d3748;
    padding: 20px;
    border-radius: 8px;
    width: 70%;
    max-width: 600px;
    max-height: 80vh;
    position: relative;
    color: white;
}

.files-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    border-bottom: 1px solid #4a5568;
    padding-bottom: 10px;

    h3 {
        margin: 0;
        color: white;
    }

    .close-btn {
        background: none;
        border: none;
        color: white;
        font-size: 1.5em;
        cursor: pointer;
        padding: 5px;
        border-radius: 4px;
        
        &:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
    }
}

.files-modal-body {
    overflow-y: auto;
    max-height: 50vh;
}

.thumbnail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 15px;
    padding: 10px 0;
}

.thumbnail-item {
    position: relative;
    cursor: pointer;
    text-align: center;
    background-color: #4a5568;
    border-radius: 8px;
    padding: 10px;
    transition: transform 0.2s;

    &:hover {
        transform: scale(1.05);
        background-color: $light-gray;
    }
}

.thumbnail-image {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 4px;
    margin-bottom: 8px;
}

.thumbnail-number {
    position: absolute;
    top: 12px;
    right: 12px;
    background-color: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
}

.file-name {
    font-size: 11px;
    color: #ffffff80;
    word-break: break-all;
    margin-top: 4px;
}

/* Single File Modal Styles */
.single-file-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    background-color: rgba(0, 0, 0, 0.8);
    z-index: 1000;
}

.single-file-modal-content {
    background-color: #2d3748;
    padding: 20px;
    border-radius: 8px;
    width: 60%;
    max-width: 500px;
    position: relative;
    color: white;
}

.single-file-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;

    h3 {
        margin: 0;
        color: white;
    }

    .close-btn {
        background: none;
        border: none;
        color: white;
        font-size: 1.5em;
        cursor: pointer;
        padding: 5px;
        border-radius: 4px;
        
        &:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
    }
}

.single-file-modal-body {
    text-align: center;
}

.single-file-image {
    max-width: 100%;
    max-height: 60vh;
    border-radius: 4px;
}

/* PDF Modal Styles */
.pdf-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    background-color: rgba(0, 0, 0, 0.8);
    z-index: 1000;
}

.pdf-modal-content {
    background-color: #2d3748;
    padding: 20px;
    border-radius: 8px;
    width: 90%;
    max-width: 900px;
    max-height: 90vh;
    position: relative;
    color: white;
}

.pdf-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    border-bottom: 1px solid #4a5568;
    padding-bottom: 10px;

    h3 {
        margin: 0;
        color: white;
    }

    .close-btn {
        background: none;
        border: none;
        color: white;
        font-size: 1.5em;
        cursor: pointer;
        padding: 5px;
        border-radius: 4px;
        
        &:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
    }
}

.pdf-modal-body {
    height: 60vh;
    position: relative;
}

.pdf-iframe {
    width: 100%;
    height: 100%;
    border: none;
    border-radius: 4px;
}

.pdf-modal-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 15px;
    padding-top: 2px;

    .nav-btn {
        background-color: #4a5568;
        border: none;
        color: white;
        font-size: 1em;
        cursor: pointer;
        padding: 8px 16px;
        border-radius: 4px;
        transition: background-color 0.2s;

        &:hover:not(:disabled) {
            background-color: #2d3748;
        }

        &:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
    }

    .file-counter {
        margin: 0 10px;
        font-weight: bold;
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
    .col-status { width: 140px; }
}
@media (max-width: 768px) {
    .row-columns { gap: 0.75rem; }
    .col-client { width: 180px; }
    .col-user { width: 120px; }
    .col-status { width: 120px; }
}
</style>


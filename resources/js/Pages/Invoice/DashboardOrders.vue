<!-- 
    DashboardOrders.vue - Optimized for Instant Image Display
    
    PERFORMANCE IMPROVEMENTS:
    ✅ Images are now pre-fetched with orders (no separate API calls needed)
    ✅ Thumbnails are pre-loaded for smooth carousel navigation
    ✅ File information is included in the initial order fetch
    ✅ Reduced API calls from 2+ to 1 per order selection
    ✅ Instant image display when clicking on orders
    
    DATA FLOW:
    1. Orders are fetched with jobs and file information in one API call
    2. When an order is selected, images start preloading immediately
    3. No waiting for additional API calls - instant display
-->
<template>
    <div class="dashboard-orders">
        <div class="dashboard-layout">
            <!-- Left column stacking both lists -->
            <div class="orders-stack">
            <!-- Orders List -->
            <div class="orders-container">
                <div class="orders-header">
                    <h2 class="text-xl font-semibold text-white mb-4">Latest Orders</h2>
                    
                    <!-- Active Filter Indicator -->
                    <div v-if="activeFilter" class="active-filter-indicator">
                        <i class="fa-solid fa-filter"></i>
                        <span class="filter-text">{{ getFilterDisplayName(activeFilter) }}</span>
                        <button @click="clearDashboardFilter" class="clear-filter-btn" title="Clear filter">
                            <i class="fa-solid fa-times"></i>
                        </button>
                    </div>
                    
                    <div class="search-filters">
                        <input 
                            v-model="searchQuery" 
                            @input="debounceSearch"
                            type="text" 
                            placeholder="Search by order #, title, client..." 
                            class="search-input"
                        />
                        <select v-model="yearFilter" @change="fetchOrders" class="status-select">
                            <option v-for="year in availableYears" :key="year" :value="year">
                                {{ year }}
                            </option>
                        </select>
                        <select v-model="statusFilter" @change="fetchOrders" class="status-select">
                            <option value="">All Status</option>
                            <option value="Not started yet">Not started yet</option>
                            <option value="In progress">In progress</option>
                        </select>
                        <select v-model="createdByFilter" @change="fetchOrders" class="status-select">
                            <option value="">All Users</option>
                            <option v-for="user in availableUsers" :key="user.id" :value="user.id">
                                {{ user.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Orders List (Inline) -->
                <div class="orders-list" v-if="orders.data && orders.data.length > 0">
                    <!-- Global Labels Row -->
                    <div class="orders-labels">
                        <div class="label-column order-id">Order #</div>
                        <div class="label-column order-title">Order Title</div>
                        <div class="label-column client">Client</div>
                        <div class="label-column end-date">End Date</div>
                        <div class="label-column created-by">Created By</div>
                        <div class="label-column status">Status</div>
                    </div>
                    
                    <div 
                        v-for="order in orders.data" 
                        :key="order.id" 
                        class="order-row"
                        :class="{ 'order-selected': selectedOrder && selectedOrder.id === order.id }"
                        @click="openOrderDetails(order)"
                    >
                        <div class="order-id">#{{ order.order_number }}</div>
                        <div class="order-title" :title="order.invoice_title">{{ order.invoice_title }}</div>
                        <div class="client" :title="order.client?.name || 'N/A'">{{ order.client?.name || 'N/A' }}</div>
                        <div class="end-date" :title="formatDate(order.end_date)">{{ formatDate(order.end_date) }}</div>
                        <div class="created-by" :title="order.user?.name || order.user_name || 'N/A'">{{ order.user?.name || order.user_name || 'N/A' }}</div>
                        <div class="status">
                            <span 
                                class="status-badge"
                                :class="getStatusClass(order.status)"
                            >
                                {{ order.status }}
                            </span>
                        </div>
                    </div>
                </div>
                <div v-else class="no-orders">
                    <div class="no-orders-content">
                        <i class="fa-solid fa-inbox"></i>
                        <h3>No orders found</h3>
                        <p>There are no orders to display at the moment.</p>
                    </div>
                </div>

                <!-- Pagination: Latest Orders -->
                <div class="pagination-container" v-if="orders.last_page > 1">
                    <div class="pagination">
                        <button 
                            @click="changePage(orders.current_page - 1)"
                            :disabled="orders.current_page === 1"
                            class="pagination-btn"
                        >
                            Previous
                        </button>
                        
                        <span class="pagination-info">
                            Page {{ orders.current_page }} of {{ orders.last_page }}
                        </span>
                        
                        <button 
                            @click="changePage(orders.current_page + 1)"
                            :disabled="orders.current_page === orders.last_page"
                            class="pagination-btn"
                        >
                            Next
                        </button>
                    </div>
                </div>
            </div>

            <!-- Completed Orders Section -->
            <div class="orders-container compact mt-2">
                <div class="orders-header">
                    <h2 class="text-xl font-semibold text-white mb-4">Completed Orders</h2>
                    <div class="search-filters">
                        <input 
                            v-model="completedSearchQuery" 
                            @input="debounceCompletedSearch"
                            type="text" 
                            placeholder="Search by order #, title, client..." 
                            class="search-input"
                        />
                        <select v-model="completedYearFilter" @change="fetchCompletedOrders" class="status-select">
                            <option v-for="year in availableYears" :key="year" :value="year">
                                {{ year }}
                            </option>
                        </select>
                        <select v-model="completedCreatedByFilter" @change="fetchCompletedOrders" class="status-select">
                            <option value="">All Users</option>
                            <option v-for="user in availableUsers" :key="user.id" :value="user.id">
                                {{ user.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="orders-list" v-if="completedOrders.data && completedOrders.data.length > 0">
                    <!-- Global Labels Row -->
                    <div class="orders-labels">
                        <div class="label-column order-id">Order #</div>
                        <div class="label-column order-title">Order Title</div>
                        <div class="label-column client">Client</div>
                        <div class="label-column end-date">End Date</div>
                        <div class="label-column created-by">Created By</div>
                        <div class="label-column status">Status</div>
                    </div>
                    
                    <div 
                        v-for="order in completedOrders.data" 
                        :key="`completed-${order.id}`" 
                        class="order-row"
                        :class="{ 'order-selected': selectedOrder && selectedOrder.id === order.id }"
                        @click="openOrderDetails(order)"
                    >
                        <div class="order-id">#{{ order.order_number }}</div>
                        <div class="order-title" :title="order.invoice_title">{{ order.invoice_title }}</div>
                        <div class="client" :title="order.client?.name || 'N/A'">{{ order.client?.name || 'N/A' }}</div>
                        <div class="end-date" :title="formatDate(order.end_date)">{{ formatDate(order.end_date) }}</div>
                        <div class="created-by" :title="order.user?.name || order.user_name || 'N/A'">{{ order.user?.name || order.user_name || 'N/A' }}</div>
                        <div class="status">
                            <span class="status-badge status-completed">Completed</span>
                        </div>
                    </div>
                </div>
                <div v-else class="no-orders">
                    <div class="no-orders-content">
                        <i class="fa-solid fa-inbox"></i>
                        <h3>No completed orders</h3>
                        <p>There are no completed orders to display at the moment.</p>
                    </div>
                </div>

                <!-- Pagination: Completed Orders -->
                <div class="pagination-container" v-if="completedOrders.last_page > 1">
                    <div class="pagination">
                        <button 
                            @click="changeCompletedPage(completedOrders.current_page - 1)"
                            :disabled="completedOrders.current_page === 1"
                            class="pagination-btn"
                        >
                            Previous
                        </button>
                        <span class="pagination-info">
                            Page {{ completedOrders.current_page }} of {{ completedOrders.last_page }}
                        </span>
                        <button 
                            @click="changeCompletedPage(completedOrders.current_page + 1)"
                            :disabled="completedOrders.current_page === completedOrders.last_page"
                            class="pagination-btn"
                        >
                            Next
                        </button>
                    </div>
                </div>
            </div>
            </div>

            <!-- Order Details Sidebar (always present; placeholder when no selection) -->
            <div class="order-sidebar" :class="{ 'sidebar-open': selectedOrder }">
                <div class="order-sidebar-inner">
                    <template v-if="selectedOrder">
                        <div class="sidebar-header">
                            <h3 class="sidebar-title">Order: #{{ selectedOrder.order_number }}</h3>
                            <div class="mt-1 mb-1 px-2 rounded-full" :class="getStatusClass(selectedOrder.status)">
                                {{ selectedOrder.status }}
                            </div>
                            <div class="sidebar-actions">
                                <button @click="viewFullOrder" class="view-full-btn" title="View Full Order">
                                    <i class="fa-solid fa-external-link-alt"></i>
                                </button>
                                <button @click="closeOrderDetails" class="close-btn" title="Close (ESC)">
                                    <i class="fa-solid fa-times"></i>
                                </button>
                            </div>
                        </div>

                        <div class="sidebar-content">
                            <!-- Order Info -->
                            <div class="detail-info">
                                <div> <strong>Title:</strong> {{ selectedOrder.invoice_title }}</div>
                                <div><strong>Client:</strong> {{ selectedOrder.client?.name }}</div>
                            </div>
                            <div class="detail-info">
                                <div><strong>Start Date:</strong> {{ formatDate(selectedOrder.start_date) }}</div>
                                <div><strong>End Date:</strong> {{ formatDate(selectedOrder.end_date) }}</div>
                               </div>
                            <!-- Files Section -->
                            <div class="files-section">
                                <h4 class="section-title">Files</h4>
                                <div class="files-carousel" v-if="selectedOrder.jobs && selectedOrder.jobs.length > 0">
                                    <div 
                                        v-for="job in selectedOrder.jobs" 
                                        :key="job.id"
                                        class="border-1 border-gray-500 rounded-md"
                                    >   
                                        <!-- Carousel for this job's files -->
                                        <div class="carousel-container" v-if="hasDisplayableFiles(job)">
                                            <div class="carousel-image-container">
                                                <!-- Loading state - only show briefly while image loads -->
                                                <div v-if="!isImageLoaded(job.id)" class="loading-overlay">
                                                    <i class="fa-solid fa-spinner fa-spin"></i>
                                                    <span>Loading image...</span>
                                                </div>
                                                
                                                <!-- New system: Multiple files -->
                                                <div v-if="hasMultipleFiles(job)" class="carousel-slide">
                                                    <div class="image-container">
                                                        <img 
                                                            v-if="shouldAttemptImageLoad(job, currentFileIndex[job.id] || 0)"
                                                            :src="getThumbnailUrl(job.id, currentFileIndex[job.id] || 0)" 
                                                            :alt="'File ' + ((currentFileIndex[job.id] || 0) + 1)"
                                                            class="carousel-image"
                                                            :data-job-id="job.id"
                                                            :data-file-index="currentFileIndex[job.id] || 0"
                                                            loading="eager"
                                                            decoding="async"
                                                            @error="handleThumbnailError"
                                                            @load="handleImageLoad"
                                                        />
                                                        <div v-else class="image-error-placeholder">
                                                            <i class="fa fa-file-o"></i>
                                                            <span>File not found</span>
                                                        </div>
                                                        
                                                        <!-- Page navigation for multi-page files -->
                                                        <div v-if="getAvailableThumbnails(job.id, currentFileIndex[job.id] || 0).length > 1" class="page-navigation">
                                                            <button 
                                                                @click="previousPageImage(job, currentFileIndex[job.id] || 0, $event)" 
                                                                class="page-nav-btn page-prev"
                                                                title="Previous page"
                                                            >
                                                                <i class="fa fa-chevron-left"></i>
                                                            </button>
                                                            <div class="page-counter">
                                                                {{ getCurrentPageIndex(job, currentFileIndex[job.id] || 0) + 1 }}/{{ getAvailableThumbnails(job.id, currentFileIndex[job.id] || 0).length }}
                                                            </div>
                                                            <button 
                                                                @click="nextPageImage(job, currentFileIndex[job.id] || 0, $event)" 
                                                                class="page-nav-btn page-next"
                                                                title="Next page"
                                                            >
                                                                <i class="fa fa-chevron-right"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="carousel-info">
                                                        <span class="file-counter">{{ (currentFileIndex[job.id] || 0) + 1 }} of {{ getJobFiles(job).length }}</span>
                                                    </div>
                                                </div>
                                                <!-- Legacy system: Single file -->
                                                <div v-else-if="job.file && job.file !== 'placeholder.jpeg'" class="carousel-slide">
                                                    <div class="image-container">
                                                        <img 
                                                            v-if="shouldAttemptImageLoad(job, 'legacy')"
                                                            :src="getThumbnailUrl(job.id, 0)" 
                                                            alt="Job Image" 
                                                            :data-job-id="job.id"
                                                            class="carousel-image"
                                                            loading="eager"
                                                            decoding="async"
                                                            @error="handleLegacyImageError"
                                                            @load="handleImageLoad"
                                                        />
                                                        <div v-else class="image-error-placeholder">
                                                            <i class="fa fa-file-o"></i>
                                                            <span>File not found</span>
                                                        </div>
                                                        
                                                        <!-- Page navigation for single file with multiple pages -->
                                                        <div v-if="getAvailableThumbnails(job.id, 0).length > 1" class="page-navigation">
                                                            <button 
                                                                @click="previousPageImage(job, 0, $event)" 
                                                                class="page-nav-btn page-prev"
                                                                title="Previous page"
                                                            >
                                                                <i class="fa fa-chevron-left"></i>
                                                            </button>
                                                            <div class="page-counter">
                                                                {{ getCurrentPageIndex(job, 0) + 1 }}/{{ getAvailableThumbnails(job.id, 0).length }}
                                                            </div>
                                                            <button 
                                                                @click="nextPageImage(job, 0, $event)" 
                                                                class="page-nav-btn page-next"
                                                                title="Next page"
                                                            >
                                                                <i class="fa fa-chevron-right"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="carousel-info">
                                                        <span class="file-counter">1 of 1</span>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Navigation buttons -->
                                            <div class="carousel-nav" v-if="hasMultipleFiles(job) && getJobFiles(job).length > 1">
                                                <button 
                                                    @click="previousFile(job.id)"
                                                    :disabled="(currentFileIndex[job.id] || 0) === 0"
                                                    class="nav-btn prev-btn"
                                                >
                                                    <i class="fa-solid fa-chevron-left"></i>
                                                </button>
                                                <button 
                                                    @click="nextFile(job.id)"
                                                    :disabled="(currentFileIndex[job.id] || 0) === getJobFiles(job).length - 1"
                                                    class="nav-btn next-btn"
                                                >
                                                    <i class="fa-solid fa-chevron-right"></i>
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <!-- No files placeholder -->
                                        <div v-else class="no-files-placeholder">
                                            <i class="fa-solid fa-image"></i>
                                            <span>No files</span>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="no-files">
                                    <i class="fa-solid fa-file"></i>
                                    <span>No files uploaded</span>
                                </div>
                            </div>

                            <!-- Comments Section -->
                            <div class="comments-section">
                                <h4 class="section-title">Comments</h4>
                                <div class="comment-content">
                                    <p v-if="selectedOrder.comment" class="comment-text">
                                        {{ selectedOrder.comment }}
                                    </p>
                                    <p v-else class="no-comment">
                                        No comments added
                                    </p>
                                </div>
                            </div>

                            <!-- Job Progress -->
                            <div class="progress-section">
                                <h4 class="section-title">Job Progress</h4>
                                <div v-for="(job, idx) in selectedOrder.jobs" :key="job.id" class="job-progress-item compact">
                                    <div class="job-label">Job {{ idx + 1 }}</div>
                                    <OrderJobProgressCompact :job="job" :invoice-id="selectedOrder.id" />
                                </div>
                            </div>
                        </div>
                    </template>
                    <template v-else>
                        <div class="sidebar-placeholder">
                            <i class="fa-solid fa-info-circle"></i>
                            <p>Select an order to view details</p>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Mobile Overlay -->
        <div 
            v-if="selectedOrder" 
            class="sidebar-overlay"
            :class="{ 'sidebar-open': selectedOrder }"
            @click="closeOrderDetails"
        ></div>
    </div>
</template>

<script>
import axios from 'axios';
import OrderJobDetails from './OrderJobDetails.vue';
import OrderJobProgressCompact from './OrderJobProgressCompact.vue';

export default {
    name: 'DashboardOrders',
    components: {
        OrderJobDetails,
        OrderJobProgressCompact
    },
    props: {
        activeFilter: {
            type: String,
            default: null
        }
    },
    emits: ['clear-filter'],
    data() {
        return {
            orders: {
                data: [],
                current_page: 1,
                last_page: 1,
                total: 0,
                per_page: 10
            },
            completedOrders: {
                data: [],
                current_page: 1,
                last_page: 1,
                total: 0,
                per_page: 5
            },
            selectedOrder: null,
            searchQuery: '',
            completedSearchQuery: '',
            statusFilter: '',
            createdByFilter: '',
            completedCreatedByFilter: '',
            yearFilter: new Date().getFullYear(),
            completedYearFilter: new Date().getFullYear(),
            availableYears: [],
            availableUsers: [],
            searchTimeout: null,
            completedSearchTimeout: null,
            currentFileIndex: {},
            preloadedImages: {},
            imageLoadStates: {},
            imageErrors: {}, // Track failed image loads
            // Thumbnail system
            carouselIndices: {}, // Track current page for each job/file combination
        }
    },
    mounted() {
        this.initializeAvailableYears();
        this.fetchAvailableUsers();
        this.fetchOrders();
        this.fetchCompletedOrders();
        // Add ESC key listener
        document.addEventListener('keydown', this.handleKeydown);
    },

    beforeUnmount() {
        // Remove ESC key listener
        document.removeEventListener('keydown', this.handleKeydown);
    },
    watch: {
        activeFilter(newFilter) {
            // Reset to first page when filter changes
            this.orders.current_page = 1;
            this.completedOrders.current_page = 1;
            this.fetchOrders();
            this.fetchCompletedOrders();
        }
    },
    methods: {
        initializeAvailableYears() {
            const currentYear = new Date().getFullYear();
            // Generate years from 2025 (system start) to current year + 1
            this.availableYears = [];
            for (let year = currentYear + 1; year >= 2025; year--) {
                this.availableYears.push(year);
            }
        },

        async fetchOrders() {
            try {
                const params = {
                    page: this.orders.current_page,
                    per_page: this.orders.per_page
                };

                if (this.searchQuery) {
                    params.searchQuery = this.searchQuery;
                }

                if (this.createdByFilter) {
                    params.createdBy = this.createdByFilter;
                }

                if (this.yearFilter) {
                    params.fiscal_year = this.yearFilter;
                }

                // Add dashboard filter
                if (this.activeFilter) {
                    params.dashboard_filter = this.activeFilter;
                }

                // Always exclude completed orders from latest list (handled server-side)
                const response = await axios.get('/orders/latest-open', { params });
                this.orders = response.data;
            } catch (error) {
                console.error('Error fetching orders:', error);
            }
        },

        async fetchCompletedOrders() {
            try {
                const params = {
                    page: this.completedOrders.current_page
                };

                if (this.completedSearchQuery) {
                    params.searchQuery = this.completedSearchQuery;
                }

                if (this.completedCreatedByFilter) {
                    params.createdBy = this.completedCreatedByFilter;
                }

                if (this.completedYearFilter) {
                    params.fiscal_year = this.completedYearFilter;
                }

                const response = await axios.get('/orders/completed', { params });
                this.completedOrders = response.data;
            } catch (error) {
                console.error('Error fetching completed orders:', error);
            }
        },

        async fetchAvailableUsers() {
            try {
                const response = await axios.get('/orders/available-users');
                this.availableUsers = response.data;
            } catch (error) {
                console.error('Error fetching available users:', error);
            }
        },

        debounceSearch() {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.orders.current_page = 1;
                this.completedOrders.current_page = 1;
                this.fetchOrders();
                this.fetchCompletedOrders();
            }, 300);
        },

        debounceCompletedSearch() {
            clearTimeout(this.completedSearchTimeout);
            this.completedSearchTimeout = setTimeout(() => {
                this.completedOrders.current_page = 1;
                this.fetchCompletedOrders();
            }, 300);
        },

        clearCompletedSearch() {
            this.completedSearchQuery = '';
            this.completedOrders.current_page = 1;
            this.fetchCompletedOrders();
        },

        changePage(page) {
            if (page >= 1 && page <= this.orders.last_page) {
                this.orders.current_page = page;
                this.fetchOrders();
            }
        },

        changeCompletedPage(page) {
            if (page >= 1 && page <= this.completedOrders.last_page) {
                this.completedOrders.current_page = page;
                this.fetchCompletedOrders();
            }
        },

        async openOrderDetails(order) {
            // Immediately show the order with available data (including files)
            this.selectedOrder = order;
            
            // Initialize carousel indices for the selected order
            this.initializeCarouselIndices(order);
            
            // Reset image loading states for new order
            this.resetImageLoadingStates();
            
            // Since we now have file information pre-fetched, we can start preloading immediately
            this.$nextTick(() => {
                this.preloadThumbnailsForOrder(this.selectedOrder);
            });
            
            // No need for separate API call - we have all the data we need
            // this.loadOrderDetailsInBackground(order.id);
        },

        resetImageLoadingStates() {
            // Create a new reactive object
            this.imageLoadStates = {};
            this.currentFileIndex = {};
            
            // Initialize loading states for all jobs in the selected order
            if (this.selectedOrder && this.selectedOrder.jobs) {
                this.selectedOrder.jobs.forEach(job => {
                    if (this.hasDisplayableFiles(job)) {
                        // Set initial loading state to false (not loaded yet)
                        this.imageLoadStates[job.id] = false;
                        
                        // Set a timeout to automatically hide loading state after 10 seconds
                        setTimeout(() => {
                            if (this.imageLoadStates[job.id] === false) {
                                this.markImageAsFailed(job.id);
                            }
                        }, 10000);
                    }
                });
            }
            
            console.log('Reset image loading states:', this.imageLoadStates);
        },



        closeOrderDetails() {
            this.selectedOrder = null;
        },

        formatDate(dateString) {
            if (!dateString) return 'N/A';
            return new Date(dateString).toLocaleDateString();
        },

        getStatusClass(status) {
            switch(status) {
                case 'Not started yet':
                    return 'status-not-started';
                case 'In progress':
                    return 'status-in-progress';
                case 'Completed':
                    return 'status-completed';
                default:
                    return 'status-default';
            }
        },

        // Image handling methods (from InvoiceDetails.vue)
        getLegacyImageUrl(job) {
            // Proxy legacy files through backend so they are served from R2 (or local fallback)
            // Use stable URL without cache-busting to prevent flickering
            return route ? route('jobs.viewLegacyFile', { jobId: job.id }) : `/jobs/${job.id}/view-legacy-file`;
        },
        
        getThumbnailUrl(jobId, fileIndex, page = null) {
            // Always prefer the controller route which handles optimization and cache
            const pageNumber = page || (this.getCurrentPageIndex(this.findJobById(jobId), fileIndex) + 1) || 1;
            try {
                return route('jobs.viewThumbnail', { jobId: jobId, fileIndex: fileIndex, page: pageNumber });
            } catch (e) {
                return `/jobs/${jobId}/view-thumbnail/${fileIndex}/${pageNumber}`;
            }
        },
        
        getOriginalFileUrl(jobId, fileIndex) {
            return route('jobs.viewOriginalFile', { jobId: jobId, fileIndex: fileIndex });
        },
        
        hasMultipleFiles(job) {
            return job.originalFile && Array.isArray(job.originalFile) && job.originalFile.length > 0;
        },

        hasSingleNewFile(job) {
            return job.originalFile && Array.isArray(job.originalFile) && job.originalFile.length === 1;
        },

        hasDisplayableFiles(job) {
            // Check if job has any files to display (new or legacy system)
            // Now optimized for pre-fetched data
            if (this.hasMultipleFiles(job)) {
                return true;
            }
            
            return job.file && job.file !== 'placeholder.jpeg';
        },
        
        shouldAttemptImageLoad(job, fileIndex) {
            if (fileIndex === 'legacy') {
                const jobKey = `${job.id}_legacy`;
                return !this.imageErrors[jobKey];
            }
            const jobKey = `${job.id}_${fileIndex}`;
            return !this.imageErrors[jobKey];
        },
        
        getJobFiles(job) {
            if (this.hasMultipleFiles(job)) {
                return job.originalFile;
            }
            return [];
        },

        getFileName(filePath) {
            return filePath.split('/').pop() || filePath;
        },
        
        handleThumbnailError(event) {
            const jobId = event.target.dataset.jobId;
            const fileIndex = event.target.dataset.fileIndex;
            
            console.warn('Thumbnail failed to load:', { jobId, fileIndex, src: event.target.src });
            
            // Mark image as failed to prevent repeated requests
            if (jobId && fileIndex !== undefined) {
                const jobKey = `${jobId}_${fileIndex}`;
                this.imageErrors[jobKey] = true;
            }
            
            // Mark image as failed to hide loading overlay
            if (jobId) {
                this.markImageAsFailed(jobId);
            }
            
            // Hide the broken image and show a placeholder instead
            const parentElement = event.target.parentElement;
            if (parentElement) {
                const placeholder = document.createElement('div');
                placeholder.className = 'image-error-placeholder';
                placeholder.innerHTML = '<i class="fa fa-file-o"></i><span>File not found</span>';
                
                event.target.style.display = 'none';
                parentElement.appendChild(placeholder);
            }
        },

        handleImageLoad(event) {
            // Image loaded successfully
            const jobId = event.target.dataset.jobId || 
                          event.target.closest('[data-job-id]')?.dataset.jobId;
            
            if (jobId) {
                this.markImageAsLoaded(jobId);
            }
        },

        markImageAsLoaded(jobId) {
            // Use direct assignment and force reactivity
            this.imageLoadStates = { ...this.imageLoadStates, [jobId]: true };
        },

        markImageAsFailed(jobId) {
            // If image fails to load, still mark as "loaded" to hide loading overlay
            this.imageLoadStates = { ...this.imageLoadStates, [jobId]: true };
        },

        isImageLoaded(jobId) {
            return this.imageLoadStates[jobId] || false;
        },

        handleLegacyImageError(event) {
            const jobId = event.target.dataset.jobId;
            console.warn('Legacy image failed to load:', event.target.src);
            
            // Mark legacy image as failed to prevent repeated requests
            if (jobId) {
                const jobKey = `${jobId}_legacy`;
                this.imageErrors[jobKey] = true;
            }
            
            // Mark image as failed to hide loading overlay
            if (jobId) {
                this.markImageAsFailed(jobId);
            }
            
            // Hide the broken image and show a placeholder
            const parentElement = event.target.parentElement;
            if (parentElement) {
                const placeholder = document.createElement('div');
                placeholder.className = 'image-error-placeholder';
                placeholder.innerHTML = '<i class="fa fa-file-o"></i><span>File not found</span>';
                
                event.target.style.display = 'none';
                parentElement.appendChild(placeholder);
            }
        },

        // Prefetching utilities - now optimized for pre-fetched data
        preloadThumbnailsForOrder(order) {
            if (!order || !order.jobs) return;
            
            console.log('🚀 Preloading thumbnails for order:', order.id, 'with', order.jobs.length, 'jobs');
            
            // Preload first thumbnail for each job immediately
            // Since we have file information, we can be more aggressive with preloading
            order.jobs.forEach(job => {
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
                // Preload first file thumbnail
                const firstThumbnailUrl = this.getThumbnailUrl(job.id, 0);
                this.preloadImage(firstThumbnailUrl);
            } else if (job.file && job.file !== 'placeholder.jpeg') {
                // Preload legacy image
                const legacyUrl = this.getLegacyImageUrl(job);
                this.preloadImage(legacyUrl);
            }
        },

        preloadImage(url) {
            if (!url || this.preloadedImages[url]) return;
            const img = new Image();
            img.decoding = 'async';
            img.loading = 'eager';
            img.src = url;
            const markDone = () => { this.preloadedImages[url] = true; };
            if (img.decode) {
                img.decode().then(markDone).catch(markDone);
            } else {
                img.onload = markDone;
                img.onerror = markDone;
            }
        },

        // Carousel navigation methods
        nextFile(jobId) {
            if (!this.currentFileIndex[jobId]) {
                this.currentFileIndex[jobId] = 0;
            }
            const job = this.selectedOrder.jobs.find(j => j.id === jobId);
            if (job && this.hasMultipleFiles(job)) {
                const maxIndex = this.getJobFiles(job).length - 1;
                if (this.currentFileIndex[jobId] < maxIndex) {
                    this.currentFileIndex[jobId]++;
                    // Preload next image for smooth navigation
                    this.preloadImage(this.getThumbnailUrl(jobId, this.currentFileIndex[jobId]));
                }
            }
        },

        previousFile(jobId) {
            if (!this.currentFileIndex[jobId]) {
                this.currentFileIndex[jobId] = 0;
            }
            if (this.currentFileIndex[jobId] > 0) {
                this.currentFileIndex[jobId]--;
                // Preload previous image for smooth navigation
                this.preloadImage(this.getThumbnailUrl(jobId, this.currentFileIndex[jobId]));
            }
        },

        handleKeydown(event) {
            if (event.key === 'Escape' && this.selectedOrder) {
                this.closeOrderDetails();
            }
        },

        viewFullOrder() {
            if (this.selectedOrder) {
                window.open(`/orders/${this.selectedOrder.id}`, '_blank');
            }
        },
        
        // Thumbnail management methods
        getJobThumbnails(jobId) {
            // Find the job in the selected order
            if (this.selectedOrder && this.selectedOrder.jobs) {
                const job = this.selectedOrder.jobs.find(j => j.id === jobId);
                if (job && job.thumbnails) {
                    return job.thumbnails;
                }
            }
            return [];
        },
        
        getThumbnailsForFile(jobId, fileIndex) {
            const thumbnails = this.getJobThumbnails(jobId);
            const job = this.findJobById(jobId);
            if (!job) return [];
            
            // Get filename from dimensions_breakdown or originalFile
            let originalFileName = '';
            if (job.dimensions_breakdown && job.dimensions_breakdown[fileIndex]) {
                originalFileName = job.dimensions_breakdown[fileIndex].filename || `File ${fileIndex + 1}`;
            } else if (job.originalFile && job.originalFile[fileIndex]) {
                originalFileName = this.getFileName(job.originalFile[fileIndex]);
            } else {
                return [];
            }
            
            const fileNameWithoutExt = originalFileName.replace(/\.[^/.]+$/, "");
            
            // Filter thumbnails that match this file
            const matchingThumbnails = thumbnails.filter(t => 
                t && t.file_name && t.file_name.includes(fileNameWithoutExt) && t.file_name.endsWith('.png')
            );
            
            // Sort by page number to ensure proper order
            return matchingThumbnails.sort((a, b) => {
                const pageA = parseInt(a.file_name.match(/_page_(\d+)\.png$/)?.[1] || '0');
                const pageB = parseInt(b.file_name.match(/_page_(\d+)\.png$/)?.[1] || '0');
                return pageA - pageB;
            });
        },
        
        getAvailableThumbnails(jobId, fileIndex) {
            // Get all available thumbnail pages for a specific file
            const thumbnails = this.getThumbnailsForFile(jobId, fileIndex);
            return thumbnails;
        },
        
        getCurrentPageIndex(job, fileIndex) {
            const key = `${job.id}_${fileIndex}`;
            return this.carouselIndices[key] || 0;
        },
        
        findJobById(jobId) {
            if (this.selectedOrder && this.selectedOrder.jobs) {
                return this.selectedOrder.jobs.find(j => j.id === jobId);
            }
            return null;
        },
        
        nextPageImage(job, fileIndex, event) {
            event.stopPropagation();
            const thumbnails = this.getThumbnailsForFile(job.id, fileIndex);
            const currentPageIndex = this.getCurrentPageIndex(job, fileIndex);
            const nextPageIndex = (currentPageIndex + 1) % thumbnails.length;
            this.carouselIndices[`${job.id}_${fileIndex}`] = nextPageIndex;
            // Force reactivity update
            this.$forceUpdate();
        },
        
        previousPageImage(job, fileIndex, event) {
            event.stopPropagation();
            const thumbnails = this.getThumbnailsForFile(job.id, fileIndex);
            const currentPageIndex = this.getCurrentPageIndex(job, fileIndex);
            const prevPageIndex = currentPageIndex === 0 ? thumbnails.length - 1 : currentPageIndex - 1;
            this.carouselIndices[`${job.id}_${fileIndex}`] = prevPageIndex;
            // Force reactivity update
            this.$forceUpdate();
        },
        
        initializeCarouselIndices(order) {
            // Initialize carousel indices for all jobs with multiple files and pages
            if (order && order.jobs) {
                order.jobs.forEach(job => {
                    if (this.hasMultipleFiles(job)) {
                        const files = this.getJobFiles(job);
                        files.forEach((file, fileIndex) => {
                            const key = `${job.id}_${fileIndex}`;
                            if (!(key in this.carouselIndices)) {
                                this.carouselIndices[key] = 0;
                            }
                        });
                    } else {
                        // Single file with multiple pages
                        const key = `${job.id}_0`;
                        if (!(key in this.carouselIndices)) {
                            this.carouselIndices[key] = 0;
                        }
                    }
                });
            }
        },

        getFilterDisplayName(filter) {
            const filterNames = {
                'not-shipped': 'All jobs not shipped',
                'entered-today': 'Entered today',
                'shipping-today': 'Shipping today',
                'shipping-2-days': 'Shipping in 2 days',
                'overdue': '> 7 days old: NOT shipped'
            };
            return filterNames[filter] || filter;
        },

        clearDashboardFilter() {
            this.$emit('clear-filter');
        }

    }
}
</script>

<style scoped lang="scss">
.dashboard-orders {
    width: 100%;
    height: 100%;
}

.dashboard-layout {
    display: flex;
    width: 100%;
    height: 100%;
    gap: 1rem;
}

.orders-stack {
    flex: 1;
    min-width: 0;
}

.orders-container {
    width: 100%;
    padding: 1.5rem;
    background-color: $light-gray;
    border-radius: 8px;
    margin-bottom: 1rem;
    
    &:last-child {
        margin-bottom: 0;
    }
}

.orders-container.compact {
    padding: 1rem;
    margin-bottom: 0.75rem;
}

.orders-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem; /* Reduced from 1.5rem */
    flex-wrap: wrap;
    gap: 1rem;

    @media (max-width: 768px) {
        flex-direction: column;
        align-items: stretch;
    }
}

.active-filter-indicator {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    background: rgba(255, 255, 255, 0.95);
    border: 1px solid rgba(20, 151, 213, 0.2);
    border-radius: 8px;
    padding: 0.75rem 1rem;
    margin-bottom: 1rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    
    i.fa-filter {
        color: #1497D5;
        font-size: 0.9rem;
    }
    
    .filter-text {
        color: #374151;
        font-size: 0.9rem;
        font-weight: 500;
        flex: 1;
    }
    
    .clear-filter-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.2);
        border-radius: 4px;
        color: #ef4444;
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 0.75rem;
        
        &:hover {
            background: rgba(239, 68, 68, 0.15);
            border-color: rgba(239, 68, 68, 0.3);
        }
    }
}

.search-filters {
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;

    @media (max-width: 768px) {
        flex-direction: column;
    }
}

.search-input, .status-select {
    padding: 0.5rem 0.75rem; /* Reduced from 0.75rem 1rem */
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 6px;
    background-color: rgba(255, 255, 255, 0.1);
    color: $white;
    font-size: 0.875rem;
    backdrop-filter: blur(10px);
    transition: all 0.2s ease;
    cursor: pointer;

    &:focus {
        outline: none;
        border-color: $blue;
        background-color: rgba(255, 255, 255, 0.15);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    &::placeholder {
        color: rgba(255, 255, 255, 0.6);
    }
}

.search-input {
    min-width: 250px;
    cursor: text;
}

.status-select {
    min-width: 180px;
    cursor: pointer;
    
    // Custom dropdown arrow
    appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23ffffff' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 1.5em 1.5em;
    padding-right: 2.5rem;
    
    // Style the options
    option {
        background-color: $dark-gray;
        color: $white;
        padding: 0.5rem;
        
        &:hover {
            background-color: $blue;
        }
    }
}

// Orders list styling
.orders-list {
    display: flex;
    flex-direction: column;
    gap: 0.25rem; /* Reduced from 0.75rem */
    margin-bottom: 1rem; /* Reduced from 1.5rem */
}

.orders-container.compact .orders-list { 
    margin-bottom: 1rem;
}

.orders-labels {
    display: grid;
    grid-template-columns: 80px 2fr 1.5fr 1fr 1fr 120px; /* Added Created By column between End Date and Status */
    gap: 0.75rem;
    margin-bottom: 0.5rem; /* Reduced from 0.75rem */
    padding: 0.5rem 0.75rem;
    background-color: $ultra-light-gray;
    border-radius: 6px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.label-column {
    font-size: 0.75rem;
    font-weight: 600;
    color: $white;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    text-align: left;
}

.order-row {
    cursor: pointer;
    display: grid;
    grid-template-columns: 80px 2fr 1.5fr 1fr 1fr 120px; /* Added Created By column: Order ID, Title, Client, End Date, Created By, Status */
    gap: 0.75rem;
    padding: 0.5rem 0.75rem; /* Reduced padding from 0.75rem */
    background: linear-gradient(135deg, #7dc068 0%, #6bb052 100%);
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    transition: all 0.2s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    margin-bottom: 0.25rem; /* Added small margin between rows */

    &:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
        border-color: rgba(255, 255, 255, 0.2);
    }

    &:active {
        transform: translateY(0);
    }

    &.order-selected {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        border-color: rgba(59, 130, 246, 0.5);
        box-shadow: 0 4px 20px rgba(59, 130, 246, 0.3);
        transform: translateY(-1px);
        
        &:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        }
    }
}

.order-header {
    cursor: pointer;
    display: flex;
    background: linear-gradient(135deg, #7dc068 0%, #6bb052 100%);
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    padding: 1rem 1.25rem;
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    transition: all 0.2s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);

    &:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
        border-color: rgba(255, 255, 255, 0.2);
    }

    &:active {
        transform: translateY(0);
    }
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    font-size: 0.875rem;
    min-width: 0;
    flex: 1;

    &.status {
        justify-self: end;
        flex-shrink: 0;
        min-width: auto;
        flex: 0 0 auto;
        max-width: 140px;
    }
}

.label {
    color: rgba(0, 0, 0, 0.8);
    font-weight: 600;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    cursor: default;
}

.value {
    color: rgba(0, 0, 0, 0.95);
    font-weight: 700;
    cursor: default;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 100%;
}

.status-badge {
    padding: 0.375rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    display: inline-block;
    white-space: nowrap;
    min-width: fit-content;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.status-not-started {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: $white;
}

.status-in-progress {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: $white;
}

.status-completed {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: $white;
}

.status-default {
    background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
    color: $white;
}

// Pagination styling
.pagination-container {
    display: flex;
    justify-content: center;

}

.pagination {
    display: flex;
    align-items: center;
    gap: 1rem;
    border-radius: 25px;
    backdrop-filter: blur(10px);
}

.pagination-btn {
    padding: 0.5rem 1rem;
    background: rgba(255, 255, 255, 0.2);
    color: $white;
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
    font-weight: 500;

    &:hover:not(:disabled) {
        background: rgba(255, 255, 255, 0.3);
        border-color: rgba(255, 255, 255, 0.5);
        transform: translateY(-1px);
    }

    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
    }
}

.pagination-info {
    color: $white;
    font-size: 0.875rem;
    font-weight: 500;
}

// No orders state
.no-orders {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 200px;
    padding: 2rem;
}

.no-orders-content {
    text-align: center;
    color: rgba(255, 255, 255, 0.7);

    i {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.6;
        color: rgba(255, 255, 255, 0.5);
    }

    h3 {
        font-size: 1.25rem;
        margin-bottom: 0.5rem;
        color: $white;
        font-weight: 600;
    }

    p {
        font-size: 0.875rem;
        opacity: 0.8;
        line-height: 1.5;
    }
}

// Simple Fixed Sidebar - Always Visible
.order-sidebar {
    flex: 0 0 auto; // Allow dynamic sizing
    min-width: var(--sidebar-min-width, 400px); // Minimum width for usability
    max-width: var(--sidebar-max-width, 600px); // Maximum width to prevent it from getting too wide
    width: var(--sidebar-width, 500px); // Default width, can be controlled via CSS or JS
    position: sticky; // Make it sticky so it stays in viewport when scrolling
    top: 0; // Stick to top of viewport
    height: 100vh;
    max-height: 100vh;
    background-color: $dark-gray;
    border: 1px solid rgba(255, 255, 255, 0.45);
    overflow-y: auto;
    box-shadow: -10px 0 10px rgba(0, 0, 0, 0.7);
    z-index: 20;
    transition: width 0.3s ease, min-width 0.3s ease, max-width 0.3s ease; // Smooth width transitions
    
    // Smooth scrollbar for sidebar content
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.3) transparent;

    &::-webkit-scrollbar {
        width: 6px;
    }

    &::-webkit-scrollbar-track {
        background: transparent;
    }

    &::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.3);
        border-radius: 3px;
        
        &:hover {
            background: rgba(255, 255, 255, 0.5);
        }
    }

    @media (max-width: 1024px) {
        position: fixed;
        top: 0;
        right: -100%;
        width: 100%;
        height: 100vh;
        z-index: 1000;
        transition: right 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        
        &.sidebar-open {
            right: 0;
        }
    }
}

.order-sidebar-inner {
    position: relative;
}

.sidebar-placeholder {
    display: flex;
    height: 100%;
    min-height: 240px;
    align-items: center;
    justify-content: center;
    color: $ultra-light-gray;
    gap: 0.5rem;

    i { font-size: 1.2rem; }
    p { margin: 0; }
}

.sidebar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid $light-gray;
    background-color: $light-gray;
    padding: 0 0.4rem 0 0.4rem;
}

.sidebar-actions {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.sidebar-title {
    color: $white;
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
}

.view-full-btn {
    background-color: $blue;
    border: none;
    color: $white;
    font-size: 1rem;
    cursor: pointer;
    padding: 0.2rem;
    border-radius: 4px;
    transition: all 0.3s ease;

    &:hover {
        background-color: darken($blue, 10%);
        transform: scale(1.05);
    }
}

.close-btn {
    background: none;
    border: none;
    color: $white;
    font-size: 1rem;
    cursor: pointer;
    padding: 0.2rem;
    border-radius: 4px;
    transition: background-color 0.3s ease;

    &:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }
}

.sidebar-content {
    padding: 0.2rem;
}

.order-details {
    margin-bottom: 0.2rem;
}

.detail-title {
    color: $white;
    font-size: 1.125rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.detail-info {
    display: flex;
    flex-direction: row;
    justify-content: space-between;
    gap: 0.5rem;
    div {
        margin-bottom: 0.5rem;
        color: $white;
        font-size: 0.875rem;

        strong {
            color: $ultra-light-gray;
        }
    }
}

.section-title {
    color: $white;
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 1rem;
    border-bottom: 1px solid $light-gray;
    padding: 0rem 0.5rem 0rem 0.5rem;
}

.files-section, .progress-section, .comments-section {
    margin: 0.5rem 0 0.5rem 0;
    padding: 0.5rem;
    background-color: $ultra-light-gray;
}

// Enhanced files section with carousel
.files-carousel {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.job-files {
    background-color: $light-gray;
    border-radius: 8px;
    padding: 1.5rem;
}

.job-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.job-title {
    color: $white;
    font-size: 1rem;
    font-weight: 600;
    margin: 0;
}

.file-count {
    color: $ultra-light-gray;
    font-size: 0.875rem;
    font-weight: 500;
}

.carousel-container {
    position: relative;
    border-radius: 6px;
    overflow: hidden;
    min-height: 300px;
    display: flex;
    flex-direction: column;
}

.carousel-image-container {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    min-height: 250px;
}

.carousel-slide {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.image-container {
    position: relative;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.carousel-image {
    max-width: 100%;
    max-height: 350px;
    object-fit: contain;
    border-radius: 4px;
}

.loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.7);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: $white;
    border-radius: 4px;
    z-index: 10;

    i {
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
        color: $blue;
    }

    span {
        font-size: 0.875rem;
        opacity: 0.8;
        color: $white;
    }
}

.carousel-info {
    position: absolute;
    bottom: 10px;
    right: 10px;
    background-color: rgba(0, 0, 0, 0.7);
    color: $white;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
}

.carousel-nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background-color: rgba(0, 0, 0, 0.1);
}

.nav-btn {
    background-color: $blue;
    color: $white;
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 1rem;

    &:hover:not(:disabled) {
        background-color: darken($blue, 10%);
        transform: scale(1.1);
    }

    &:disabled {
        background-color: $ultra-light-gray;
        cursor: not-allowed;
        opacity: 0.5;
    }
}

.prev-btn {
    margin-right: auto;
}

.next-btn {
    margin-left: auto;
}

.no-files-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: $ultra-light-gray;
    padding: 3rem;
    text-align: center;
    min-height: 200px;

    i {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    span {
        font-size: 0.875rem;
        opacity: 0.7;
    }
}

.no-files {
    display: flex;
    flex-direction: column;
    align-items: center;
    color: $ultra-light-gray;
    padding: 2rem;
    text-align: center;

    i {
        font-size: 2rem;
        margin-bottom: 0.5rem;
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
    border-radius: 4px;
    color: #dc2626;
    font-size: 12px;
    text-align: center;
    
    i {
        font-size: 20px;
        margin-bottom: 4px;
        color: #f87171;
    }
    
    span {
        font-size: 10px;
        line-height: 1;
    }
}

.comment-content {
    background-color: $light-gray;
    border-radius: 6px;
    padding: 0.5rem;
    margin: 0.5rem;
}

.comment-text {
    color: $white;
    font-size: 1rem;
    line-height: 1.5;
    
}

.no-comment {
    color: $ultra-light-gray;
    font-size: 0.875rem;
    font-style: italic;
    margin: 0;
}

.job-progress-item {
    padding: 0.5rem;
    background-color: $light-gray;
    border-radius: 6px;
    margin-bottom: 0.5rem;
}

.job-progress-item .job-label {
    color: $white;
    font-size: 0.8rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.job-title {
    color: $white;
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

// Individual column styling
.order-id {
    font-weight: 700;
    color: $white;
    text-align: center;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.order-title {
    font-weight: 700;
    color: $white;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.client {
    font-weight: 700;
    color: $white;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.end-date {
    font-weight: 700;
    color: $white;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    text-align: center;
}

.created-by {
    font-weight: 700;
    color: $white;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    text-align: center;
}

.status {
    display: flex;
    justify-content: center;
    align-items: center;
    min-width: 0;
}

/* Vertical separators within each order row */
.order-row > .order-id,
.order-row > .order-title,
.order-row > .client,
.order-row > .end-date,
.order-row > .created-by {
    border-right: 1px solid rgba(255, 255, 255, 0.25);
    padding-right: 0.75rem;
}

.orders-labels > .label-column:not(:last-child) {
    border-right: 1px solid rgba(255, 255, 255, 0.25);
    padding-right: 0.75rem;
}

// Sidebar placeholder
.sidebar-placeholder {
    display: flex;
    height: 100%;
    align-items: center;
    justify-content: center;
    color: rgba(255, 255, 255, 0.5);
    gap: 0.75rem;
    padding: 2rem;
    text-align: center;

    i { 
        font-size: 2rem; 
        opacity: 0.6;
    }
    
    p { 
        margin: 0; 
        font-size: 0.875rem;
        line-height: 1.4;
    }
}

// Mobile overlay
.sidebar-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    z-index: 999;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    backdrop-filter: blur(4px);

    @media (max-width: 1024px) {
        &.sidebar-open {
            opacity: 1;
            visibility: visible;
        }
    }
}

// Responsive design improvements
@media (max-width: 1400px) {
    .order-sidebar {
        width: 450px;
        min-width: 400px;
        max-width: 500px;
    }
}

@media (max-width: 1200px) {
    .order-sidebar {
        width: 400px;
        min-width: 350px;
        max-width: 450px;
    }
    
    // Adjust table columns for medium screens
    .orders-labels,
    .order-row {
        grid-template-columns: 70px 2fr 1.3fr 0.8fr 0.8fr 90px;
        gap: 0.5rem;
    }
}

@media (max-width: 768px) {
    .dashboard-layout {
        flex-direction: column;
    }
    
    .orders-stack {
        order: 1;
    }
    
    .order-sidebar {
        order: 2;
        position: relative;
        height: auto;
        max-height: none;
        width: 100%;
        min-width: 100%;
        max-width: 100%;
    }
    
    .orders-container {
        padding: 1rem;
    }
    
    .search-input, .status-select {
        min-width: 100%;
        width: 100%;
    }
    
    // Stack table columns vertically on mobile
    .orders-labels,
    .order-row {
        grid-template-columns: 1fr;
        gap: 0.5rem;
        text-align: center;
    }
    
    .label-column {
        text-align: center;
        padding: 0.25rem 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        
        &:last-child {
            border-bottom: none;
        }
    }
    
    .order-id, .order-title, .client, .end-date, .created-by, .status {
        text-align: center;
        padding: 0.25rem 0;
    }

    .order-row > .order-id,
    .order-row > .order-title,
    .order-row > .client,
    .order-row > .end-date,
    .order-row > .created-by,
    .orders-labels > .label-column {
        border-right: none;
        padding-right: 0;
    }
}

/* Page navigation styles */
.page-navigation {
    position: absolute;
    bottom: 2px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: center;
    gap: 4px;
    z-index: 15;
}

.page-nav-btn {
    background-color: rgba(59, 130, 246, 0.9);
    border: none;
    color: white;
    width: 24px;
    height: 24px;
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
        transform: scale(1.1);
        box-shadow: 0 3px 6px rgba(0,0,0,0.3);
    }
    
    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        background-color: #6b7280;
    }
}

.page-counter {
    background-color: rgba(0, 0, 0, 0.8);
    color: white;
    font-size: 9px;
    padding: 2px 6px;
    border-radius: 4px;
    font-weight: bold;
    min-width: 24px;
    text-align: center;
    line-height: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}
 </style> 
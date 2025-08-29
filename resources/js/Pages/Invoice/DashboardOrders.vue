<template>
    <div class="dashboard-orders">
        <div class="dashboard-layout">
            <!-- Left column stacking both lists -->
            <div class="orders-stack">
            <!-- Orders List -->
            <div class="orders-container">
                <div class="orders-header">
                    <h2 class="text-xl font-semibold text-white mb-4">Latest Orders</h2>
                    <div class="search-filters">
                        <input 
                            v-model="searchQuery" 
                            @input="debounceSearch"
                            type="text" 
                            placeholder="Search orders..." 
                            class="search-input"
                        />
                        <select v-model="statusFilter" @change="fetchOrders" class="status-select">
                            <option value="">All Status</option>
                            <option value="Not started yet">Not started yet</option>
                            <option value="In progress">In progress</option>
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
                        <div class="order-id">#{{ order.id }}</div>
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
                            placeholder="Search completed orders..." 
                            class="search-input"
                        />
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
                        <div class="order-id">#{{ order.id }}</div>
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
                            <h3 class="sidebar-title">Order: #{{ selectedOrder.id }}</h3>
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
                                                <!-- New system: Multiple files -->
                                                <div v-if="hasMultipleFiles(job)" class="carousel-slide">
                                                    <div class="image-container">
                                                        <img 
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
                                                    </div>
                                                    <div class="carousel-info">
                                                        <span class="file-counter">{{ (currentFileIndex[job.id] || 0) + 1 }} of {{ getJobFiles(job).length }}</span>
                                                    </div>
                                                </div>
                                                <!-- Legacy system: Single file -->
                                                <div v-else-if="job.file && job.file !== 'placeholder.jpeg'" class="carousel-slide">
                                                    <img 
                                                        :src="getLegacyImageUrl(job)" 
                                                        alt="Job Image" 
                                                        class="carousel-image"
                                                        @error="handleLegacyImageError"
                                                    />
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
    data() {
        return {
            orders: {
                data: [],
                current_page: 1,
                last_page: 1,
                total: 0
            },
            completedOrders: {
                data: [],
                current_page: 1,
                last_page: 1,
                total: 0
            },
            selectedOrder: null,
            searchQuery: '',
            completedSearchQuery: '',
            statusFilter: '',
            searchTimeout: null,
            completedSearchTimeout: null,
            currentFileIndex: {},
            preloadedImages: {}
        }
    },
    mounted() {
        this.fetchOrders();
        this.fetchCompletedOrders();
        // Add ESC key listener
        document.addEventListener('keydown', this.handleKeydown);
    },

    beforeUnmount() {
        // Remove ESC key listener
        document.removeEventListener('keydown', this.handleKeydown);
    },
    methods: {
        async fetchOrders() {
            try {
                const params = {
                    page: this.orders.current_page,
                    per_page: this.orders.per_page
                };

                if (this.searchQuery) {
                    params.searchQuery = this.searchQuery;
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
                    status: 'Completed',
                    page: this.completedOrders.current_page
                };

                if (this.completedSearchQuery) {
                    params.searchQuery = this.completedSearchQuery;
                }

                const response = await axios.get('/orders', { params });
                this.completedOrders = response.data;
            } catch (error) {
                console.error('Error fetching completed orders:', error);
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
            try {
                const response = await axios.get(`/orders/${order.id}/details`);
                this.selectedOrder = response.data;
                console.log('Order details loaded:', this.selectedOrder);
                this.$nextTick(() => {
                    this.preloadThumbnailsForOrder(this.selectedOrder);
                });
            } catch (error) {
                console.error('Error fetching order details:', error);
                // Fallback to the basic order data if the detailed fetch fails
                this.selectedOrder = order;
                console.log('Using fallback order data:', this.selectedOrder);
                this.$nextTick(() => {
                    this.preloadThumbnailsForOrder(this.selectedOrder);
                });
            }
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
            return route ? route('jobs.viewLegacyFile', { jobId: job.id }) : `/jobs/${job.id}/view-legacy-file`;
        },
        
        getThumbnailUrl(jobId, fileIndex) {
            // Rely on server ETag/immutable caching to avoid extra traffic
            return route('jobs.viewThumbnail', { jobId: jobId, fileIndex: fileIndex });
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
            return this.hasMultipleFiles(job) || (job.file && job.file !== 'placeholder.jpeg');
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
            // Enhanced error handling like InvoiceDetails.vue
            const jobId = event.target.dataset.jobId;
            const fileIndex = event.target.dataset.fileIndex;
            
            console.warn('Thumbnail failed to load:', { jobId, fileIndex, src: event.target.src });
            
            const job = this.selectedOrder?.jobs?.find(j => j.id == jobId);
            // Prefer legacy image fallback first (image formats) because originals are PDFs
            if (job && job.file && job.file !== 'placeholder.jpeg') {
                const legacyUrl = this.getLegacyImageUrl(job);
                console.log('Trying fallback to legacy image:', legacyUrl);
                event.target.src = legacyUrl;
                return;
            }

            // As a secondary fallback, try original file URL (may be PDF and not displayable in <img>)
            if (jobId && fileIndex !== undefined) {
                const fallbackUrl = this.getOriginalFileUrl(jobId, fileIndex);
                console.log('Trying fallback to original file (may be PDF):', fallbackUrl);
                event.target.src = fallbackUrl;
            }
        },

        handleImageLoad(event) {
            // Image loaded successfully
        },

        handleLegacyImageError(event) {
            // Handle legacy image loading errors
            console.warn('Legacy image failed to load:', event.target.src);
            // Could add fallback logic here if needed
        },

        // Prefetching utilities
        preloadThumbnailsForOrder(order) {
            // Disable aggressive preloading to reduce traffic; rely on lazy image loading
            return;
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

.search-filters {
    display: flex;
    gap: 1rem;
    align-items: center;

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

    i {
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
    }

    span {
        font-size: 0.875rem;
        opacity: 0.8;
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
 </style> 
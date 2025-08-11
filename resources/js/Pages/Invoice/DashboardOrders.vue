<template>
    <div class="dashboard-orders">
        <div class="dashboard-layout" :class="{ 'with-sidebar': selectedOrder }">
            <!-- Left column stacking both lists -->
            <div class="orders-stack" :class="{ 'with-sidebar': selectedOrder }">
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
                            <option value="Completed">Completed</option>
                        </select>
                    </div>
                </div>

                <!-- Orders List (Inline) -->
                <div class="orders-list" v-if="orders.data && orders.data.length > 0">
                    <div 
                        v-for="order in orders.data" 
                        :key="order.id" 
                        
                        @click="openOrderDetails(order)"
                    >
                        <div class="order-header">
                            <div class="detail-item">
                                <span class="label">Order:</span>
                                <span class="value">#{{ order.id }}</span>
                            </div>
                            <div class="detail-item">
                                <div class="label">Order Title:</div>
                                <div class="value" :title="order.invoice_title">{{ order.invoice_title }}</div>
                            </div>
                            <div class="detail-item">
                                <span class="label">Client:</span>
                                <span class="value" :title="order.client?.name || 'N/A'">{{ order.client?.name || 'N/A' }}</span>
                            </div>
                            <div class="detail-item status">
                                <span 
                                    class="status-badge"
                                    :class="getStatusClass(order.status)"
                                >
                                    {{ order.status }}
                                </span>
                            </div>
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
                </div>

                <div class="orders-list" v-if="completedOrders.data && completedOrders.data.length > 0">
                    <div 
                        v-for="order in completedOrders.data" 
                        :key="`completed-${order.id}`" 
                        @click="openOrderDetails(order)"
                    >
                        <div class="order-header">
                            <div class="detail-item">
                                <span class="label">Order:</span>
                                <span class="value">#{{ order.id }}</span>
                            </div>
                            <div class="detail-item">
                                <div class="label">Order Title:</div>
                                <div class="value" :title="order.invoice_title">{{ order.invoice_title }}</div>
                            </div>
                            <div class="detail-item">
                                <span class="label">Client:</span>
                                <span class="value" :title="order.client?.name || 'N/A'">{{ order.client?.name || 'N/A' }}</span>
                            </div>
                            <div class="detail-item status">
                                <span class="status-badge status-completed">Completed</span>
                            </div>
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

            <!-- Order Details Sidebar -->
            <div 
                v-if="selectedOrder"
                class="order-sidebar"
                :class="{ 'sidebar-open': selectedOrder }"
            >
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

                <div v-if="selectedOrder" class="sidebar-content">
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
                                <div class="carousel-container" v-if="hasMultipleFiles(job) || (job.file && job.file !== 'placeholder.jpeg')">
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
                        <div v-for="job in selectedOrder.jobs" :key="job.id" class="job-progress-item">
                            <OrderJobDetails :job="job" />
                        </div>
                    </div>
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

export default {
    name: 'DashboardOrders',
    components: {
        OrderJobDetails
    },
    data() {
        return {
            orders: {
                data: [],
                current_page: 1,
                last_page: 1,
                per_page: 10,
                total: 0
            },
            completedOrders: {
                data: [],
                current_page: 1,
                last_page: 1,
                per_page: 5,
                total: 0
            },
            selectedOrder: null,
            searchQuery: '',
            statusFilter: '',
            searchTimeout: null,
            currentFileIndex: {}
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
                    page: this.completedOrders.current_page,
                    per_page: this.completedOrders.per_page
                };

                if (this.searchQuery) {
                    params.searchQuery = this.searchQuery;
                }

                // Status is forced to Completed on server
                const response = await axios.get('/orders/completed', { params });
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
            } catch (error) {
                console.error('Error fetching order details:', error);
                // Fallback to the basic order data if the detailed fetch fails
                this.selectedOrder = order;
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
            return `/storage/uploads/${job.file}`;
        },
        
        getThumbnailUrl(jobId, fileIndex) {
            // Add cache busting parameter like in OrderLines.vue
            const timestamp = Date.now();
            return route('jobs.viewThumbnail', { jobId: jobId, fileIndex: fileIndex }) + `?t=${timestamp}`;
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
            // Try to load the original file as fallback
            const jobId = event.target.dataset.jobId;
            const fileIndex = event.target.dataset.fileIndex;
            if (jobId && fileIndex !== undefined) {
                event.target.src = this.getOriginalFileUrl(jobId, fileIndex);
            }
        },

        handleImageLoad(event) {
            // Image loaded successfully
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
                }
            }
        },

        previousFile(jobId) {
            if (!this.currentFileIndex[jobId]) {
                this.currentFileIndex[jobId] = 0;
            }
            if (this.currentFileIndex[jobId] > 0) {
                this.currentFileIndex[jobId]--;
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
    position: relative;
    width: 100%;
    height: 100%;
}

.dashboard-layout {
    display: flex;
    width: 100%;
    height: 100%;
    position: relative;
    overflow-x: hidden;
    
    &.with-sidebar {
        .orders-stack { flex: 0 0 calc(100% - 500px); }
        .order-sidebar {
            flex: 0 0 500px;
        }
    }
}

.orders-stack {
    display: flex;
    flex-direction: column;
    width: 100%;
}

.orders-container {
    width: 100%;
    height: 100%;
    overflow-y: auto;
    padding: 1rem;
    background-color: $light-gray;
    min-width: 400px;
    transition: all 0.3s ease;
}

.orders-container.compact {
    height: auto;
    overflow: visible;
    padding: 0.5rem 0.75rem;
}

.orders-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
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
    padding: 0.5rem 1rem;
    border: 1px solid $light-gray;
    border-radius: 4px;
    background-color: $white;
    color: $dark-gray;
    font-size: 0.875rem;

    &:focus {
        outline: none;
        border-color: $blue;
    }
}

.search-input {
    min-width: 200px;
}

.status-select {
    min-width: 150px;
}

// Inline orders list (like /orders page)
.orders-list {
    cursor: pointer;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-bottom: 2rem;
}

.orders-container.compact .orders-list { margin-bottom: 0.5rem; }

.order-item {
    background-color: $light-gray;
    border-radius: 6px;
    padding: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    border: 1px solid transparent;

    &:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
}

.order-header {
    cursor: pointer;
    display: flex;
    background-color: #7dc068;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
    
    padding: 0.75rem;
    border-radius: 6px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.order-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: $white;
    margin: 0;
}

.order-actions {
    display: flex;
    gap: 0.5rem;
}

.action-btn {
    background: none;
    border: none;
    color: $white;
    padding: 0.5rem;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s ease;

    &:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }
}

.view-btn {
    background-color: $blue;
    color: $white;
    
    &:hover {
        background-color: darken($blue, 10%);
    }
}

.order-details {
    display: grid;
    grid-template-columns: 1fr 1.5fr 1.5fr auto;
    gap: 1rem;
    align-items: center;
    
    @media (max-width: 768px) {
        grid-template-columns: 1fr;
        gap: 0.5rem;
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
        max-width: 120px;
    }
}

.label {
    color: black;
    font-weight: 500;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    cursor: default;
}

.value {
    color: black;
    font-weight: 700;
    cursor: default;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 100%;
}

.status-badge {
    padding: 0.125rem 0.5rem;
    border-radius: 8px;
    font-size: 0.625rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    display: inline-block;
    white-space: nowrap;
    min-width: fit-content;
}

.status-not-started {
    background-color: #a36a03;
    color: $white;
}

.status-in-progress {
    background-color: #0073a9;
    color: $white;
}

.status-completed {
    background-color: #408a0b;
    color: $white;
}

.status-default {
    background-color: $dark-gray;
    color: $white;
}

.pagination-container {
    display: flex;
    justify-content: center;
    margin-top: 2rem;
}

.pagination {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.pagination-btn {
    padding: 0.5rem 1rem;
    background-color: $light-gray;
    color: $white;
    border: 1px solid $ultra-light-gray;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.3s ease;

    &:hover:not(:disabled) {
        background-color: $blue;
        border-color: $blue;
    }

    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
}

.pagination-info {
    color: $white;
    font-size: 0.875rem;
}

.no-orders {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 300px;
}

.no-orders-content {
    text-align: center;
    color: $ultra-light-gray;

    i {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    h3 {
        font-size: 1.25rem;
        margin-bottom: 0.5rem;
        color: $white;
    }

    p {
        font-size: 0.875rem;
        opacity: 0.7;
    }
}

// Sidebar Styles - Now part of the flex layout
.order-sidebar {
    flex: 0 0 500px;
    height: 100%;
    background-color: $dark-gray;
    border: 1px solid rgba(255, 255, 255, 0.45);
    overflow-y: auto;
    box-shadow: -10px 0 10px rgba(0, 0, 0, 0.7);

    @media (max-width: 768px) {
        position: fixed;
        top: 0;
        right: -100%;
        width: 100%;
        height: 100vh;
        z-index: 1000;
        transition: right 0.3s ease;
        
        &.sidebar-open {
            right: 0;
        }
    }
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
    flex-direction:row;
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
}

.job-title {
    color: $white;
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

// Mobile overlay for sidebar
.sidebar-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 999;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;

    @media (max-width: 768px) {
        &.sidebar-open {
            opacity: 1;
            visibility: visible;
        }
    }
}
</style> 
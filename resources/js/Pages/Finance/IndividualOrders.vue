<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="invoice2" subtitle="Individual Orders" icon="invoice.png" link="individual"/>
            <div class="dark-gray p-2 text-white">
                <RedirectTabs :route="$page.url" />
                <div class="form-container p-2 ">
                    <h2 class="sub-title">
                        Физичко лице
                    </h2>
                    <div class="filter-container flex gap-4 pb-10">
                        <div class="search flex gap-2">
                            <input v-model="searchQuery" placeholder="Enter Order Number or Order Name" class="text-black" style="width: 50vh; border-radius: 3px" @keyup.enter="searchOrders" />
                            <button class="btn create-order1" @click="searchOrders">Search</button>
                        </div>
                        <div class="flex gap-3">
                        <div class="status">
                            <label class="pr-3">Filter Status</label>
                            <select v-model="status" class="text-black" @change="applyFilter">
                                <option value="" hidden>Status</option>
                                <option value="">All Status</option>
                                <option value="paid">Paid</option>
                                <option value="unpaid">Unpaid</option>
                            </select>
                        </div>
                        <div class="completion-status">
                            <label class="pr-3">Status</label>
                            <select v-model="completionStatus" class="text-black" @change="applyFilter">
                                <option value="" hidden>Status</option>
                                <option value="">All</option>
                                <option value="Completed">Completed</option>
                                <option value="In progress">In Progress</option>
                                <option value="Not started yet">Not Started</option>
                            </select>
                        </div>
                        <div class="date">
                            <select v-model="sortOrder" class="text-black" @change="applyFilter">
                                <option value="desc" hidden>Date</option>
                                <option value="desc">Newest to Oldest</option>
                                <option value="asc">Oldest to Newest</option>
                            </select>
                        </div>
                        </div>
                    </div>
                    <div v-if="orders.data">
                        <div class="border mb-1" v-for="order in orders.data" :key="order.id">
                            <div class="bg-white text-black flex justify-between items-center p-2">
                                <div class="bold">Order #{{order.id}} - {{ Number(order.total_amount).toFixed(2) }} MKD</div>
                                <div class="note-header" @click="openNoteModal(order)">
									<span v-if="order.notes" class="note-preview" :title="order.notes">{{ order.notes }}</span>
                                    <span :class="order.notes ? 'text-green-500' : 'text-gray-400'" class="text-lg cursor-pointer">📝</span>
                                </div>
                            </div>
                            <div class="flex justify-between p-4 mx-4">
                                <div class="info">
                                    <div>Invoice</div>
                                    <div class="bold">#{{order.invoice_id}}</div>
                                </div>
                                <div class="info">
                                    <div>Status</div>
                                    <div class="bold w-fit" :class="getStatusClass(order.completion_status)">{{order.completion_status}}</div>
                                </div>
                                <div class="info">
                                    <div>Date</div>
                                    <div>{{ new Date(order.created_at).toLocaleDateString('en-US', { month: '2-digit', day: '2-digit', year: 'numeric' }) }}</div>
                                </div>
                                <div class="info">
                                    <div>Contact</div>
                                    <div class="bold">{{ order.invoice?.contact?.name || 'No contact' }}</div>
                                </div>
                                <div class="info">
                                    <div>Payment</div>
                                    <select v-model="localStatus[order.id]"
                                            class="text-black p-1"
                                            :disabled="!order.is_completed"
                                            @change="updateStatus(order.id, localStatus[order.id])">
                                        <option value="paid">Paid</option>
                                        <option value="unpaid">Unpaid</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Job Thumbnails Accordion -->
                            <div v-if="order.invoice && order.invoice.jobs && order.invoice.jobs.length > 0" class="job-thumbnails-accordion">
                                <div 
                                    class="accordion-header" 
                                    @click="toggleOrderExpansion(order.id)"
                                >
                                    <div class="accordion-header-content">
                                        <span class="jobs-title">Job Preview Cards</span>
                                        <span class="job-count">{{ getOrderJobWithThumbnails(order).length }} job(s)</span>
                                    </div>
                                    <div class="accordion-toggle">
                                        <i 
                                            :class="[isOrderExpanded(order.id) ? 'fa-chevron-up' : 'fa-chevron-down', 'fa']"
                                        ></i>
                                    </div>
                                </div>
                                
                                <transition name="accordion">
                                    <div 
                                        v-if="isOrderExpanded(order.id)"
                                        class="accordion-content"
                                    >
                                    <div class="jobs-container-grid">
                                        <template v-for="(job, jobIndex) in getOrderJobWithThumbnails(order)" :key="jobIndex">
                                            <!-- Each original file gets its own thumbnail container -->
                                            <div 
                                                v-for="(filePath, fileIndex) in job.originalFile || []"
                                                :key="`${job.id}-${fileIndex}`"
                                                class="thumbnail-card"
                                                @click="openThumbnailModal(order.id, job.id, fileIndex)"
                                            >
                                                <!-- File thumbnails for this specific file -->
                                                <div v-if="getFileThumbnails(order.id, job.id, fileIndex).length > 0" class="thumbnail-clickable">
                                                    <!-- Always show first thumbnail -->
                                                    <img 
                                                        :src="getFileThumbnails(order.id, job.id, fileIndex)[0]?.url"
                                                        :alt="`File ${fileIndex + 1}`"
                                                        class="thumbnail-mini clickable-thumbnail"
                                                        @error="onThumbnailError"
                                                    />
                                                    
                                                    <!-- Show page count if multiple pages -->
                                                    <div v-if="getFileThumbnails(order.id, job.id, fileIndex).length > 1" class="page-count-mini">
                                                        {{ getFileThumbnails(order.id, job.id, fileIndex).length }}
                                                    </div>
                                                </div>
                                                
                                                <!-- Loading state -->
                                                <div v-else-if="thumbnailLoading[order.id]" class="thumbnail-loading-mini">
                                                    <i class="fa fa-spinner fa-spin"></i>
                                                </div>
                                                
                                                <!-- No thumbnails -->
                                                <div v-else class="thumbnail-placeholder-mini">
                                                    <i class="fa fa-file-pdf-o"></i>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                    </div>
                                </transition>
                            </div>
                        </div>
                    </div>
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
                <label class="block text-sm font-medium text-white mb-2">Order #{{ selectedOrder?.id }}</label>
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
import axios from 'axios';
import RedirectTabs from "@/Components/RedirectTabs.vue";
import { useToast } from "vue-toastification";

export default {
    components: {Header, MainLayout, Pagination, RedirectTabs},
    props:{
        orders:Object,
    },
    data() {
        return {
            searchQuery: '',
            sortOrder: 'desc',
            status: '',
            completionStatus: '',
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
    methods: {
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

        async applyFilter() {
            try {
                const params = new URLSearchParams();
                if (this.searchQuery) params.append('searchQuery', this.searchQuery);
                if (this.sortOrder) params.append('sortOrder', this.sortOrder);
                if (this.status) params.append('status', this.status);
                if (this.completionStatus) params.append('completionStatus', this.completionStatus);
                
                const queryString = params.toString();
                const url = queryString ? `/individual?${queryString}` : '/individual';
                
                this.$inertia.visit(url);
            } catch (error) {
                console.error(error);
            }
        },
        async searchOrders() {
            try {
                const params = new URLSearchParams();
                if (this.searchQuery) params.append('searchQuery', this.searchQuery);
                if (this.sortOrder) params.append('sortOrder', this.sortOrder);
                if (this.status) params.append('status', this.status);
                if (this.completionStatus) params.append('completionStatus', this.completionStatus);
                
                const queryString = params.toString();
                const url = queryString ? `/individual?${queryString}` : '/individual';
                
                this.$inertia.visit(url);
            } catch (error) {
                console.error(error);
            }
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
            const params = new URLSearchParams();
            if (this.searchQuery) params.append('searchQuery', this.searchQuery);
            if (this.sortOrder) params.append('sortOrder', this.sortOrder);
            if (this.status) params.append('status', this.status);
            if (this.completionStatus) params.append('completionStatus', this.completionStatus);
            params.append('page', page);
            
            const queryString = params.toString();
            const url = queryString ? `/individual?${queryString}` : '/individual';
            
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
        }
    },
    created() {
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

.info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    justify-content: center;
    margin-right: 1rem;
    padding: 0.5rem;
    
    &:last-child {
        margin-right: 0;
    }
}

.filter-container{
    justify-content: space-between;
}

select{
    width: 25vh;
    border-radius: 3px;
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
    border-radius: 3px;
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
}

.note-header{
    display: flex;
    align-items: center;
    gap: 5px;
    max-width: 50%;
    cursor: pointer;
}

.note-preview{
    display: inline-block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    color: #4b5563;
}

.button-container{
    display: flex;
    justify-content: end;
}

.btn {
    padding: 9px 12px;
    color: white;
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
        flex-wrap: wrap;
        gap: 12px;
        padding: 8px 0;
        justify-content: flex-start;
        width: 100%;
        min-width: 0;
        max-width: 100%;
        
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

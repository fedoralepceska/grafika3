<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="invoice" subtitle="ViewAllInvoices" icon="List.png" link="orders"/>
            <div class="dark-gray p-2 text-white">
                <div class="form-container p-2">
                    <!-- Header row with title and create button -->
                    <div class="page-header">
                        <h2 class="sub-title">{{ $t('listOfAllOrders') }}</h2>
                        <button @click="navigateToCreateOrder" class="btn create-order">
                            <i class="fa fa-plus"></i> Create Order
                        </button>
                    </div>
                    
                    <!-- Filters row -->
                    <div class="filters-row">
                        <div class="search-box">
                            <i class="fa fa-search search-icon"></i>
                            <input 
                                v-model="searchQuery" 
                                placeholder="Search by order #, title, client..." 
                                class="search-input" 
                                @keyup.enter="searchInvoices" 
                            />
                        </div>
                        
                        <div class="filter-controls">
                            <select v-model="yearFilter" class="filter-select filter-year" @change="applyFilter">
                                <option v-for="year in availableYears" :key="year" :value="year">{{ year }}</option>
                            </select>
                            
                            <select v-model="filterStatus" class="filter-select filter-status" @change="applyFilter">
                                <option value="All">All Status</option>
                                <option value="Not started yet">Not started yet</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Completed">Completed</option>
                            </select>
                            
                            <select v-model="filterClient" class="filter-select filter-client" @change="applyFilter">
                                <option value="All">All Clients</option>
                                <option v-for="client in uniqueClients" :key="client">{{ client.name }}</option>
                            </select>
                            
                            <select v-model="sortOrder" class="filter-select filter-sort" @change="applyFilter">
                                <option value="desc">Newest First</option>
                                <option value="asc">Oldest First</option>
                            </select>
                            
                            <label class="archived-toggle">
                                <input type="checkbox" v-model="showArchived" @change="applyFilter">
                                <span class="toggle-slider"></span>
                                <span class="toggle-label">Archived</span>
                            </label>
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
                                    <button
                                        v-if="canDeleteInvoice(invoice)"
                                        class="flex items-center px-2 m-1 delete-btn"
                                        @click="openDeleteModal(invoice)"
                                        title="Delete order"
                                    >
                                        <i class="fa fa-trash" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="flex row-columns pl-2 pt-1" style="line-height: initial">
                                <div class="info col-order">
                                    <div>Order</div>
                                    <div class="bold">#{{invoice.order_number}}</div>
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
                                        Jobs for Order #{{invoice.order_number}} {{invoice.invoice_title}}
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
                                            <div class="p-1 thumbnail-section">
                                                <!-- Display thumbnails for this job -->
                                                <template v-if="hasDisplayableFiles(job)">
                                                    <div class="job-thumbnails-container">
                                                        <!-- Multiple files - display in flex row -->
                                                        <template v-if="hasMultipleFiles(job)">
                                                            <!-- Show all file thumbnails in a horizontal layout -->
                                                            <div class="multiple-thumbnails-row">
                                                                <div 
                                                                    v-for="(file, fileIndex) in getJobFiles(job)"
                                                                    :key="`${job.id}-${fileIndex}`"
                                                                    class="file-thumbnail-wrapper"
                                                                    @click="openFileThumbnailModal(job, index, fileIndex)"
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
                                                                       <span class="text-xs">No preview available</span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </template>
                                                        
                                                        <!-- Single file - centered display -->
                                                        <template v-else-if="hasSingleNewFile(job) || isLegacyJob(job)">
                                                            <div class="single-thumbnail-container">
                                                                <div 
                                                                    class="file-thumbnail-wrapper single-file"
                                                                    @click="openFileThumbnailModal(job, index, 0)"
                                                                >
                                                                    <!-- Single file thumbnail -->
                                                                    <div v-if="getAvailableThumbnails(job.id, 0).length > 0" class="thumbnail-preview-container">
                                                                        <img 
                                                                            v-if="shouldAttemptImageLoad(job, 0)"
                                                                            :src="getThumbnailUrl(job.id, 0)" 
                                                                            :alt="`Job ${index + 1} Preview`"
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
                                                                    <span class="text-xs">No preview available</span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </template>
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
                    <!-- <div class="pagination-info">
                        Showing {{ (currentPage - 1) * perPage + 1 }} to {{ Math.min(currentPage * perPage, totalInvoices) }} of {{ totalInvoices }} orders
                    </div> -->
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
            
            <!-- Delete Confirmation Modal -->
            <div v-if="showDeleteModal" class="files-modal" @click="closeDeleteModal">
                <div class="files-modal-content centered" @click.stop>
                    <div class="files-modal-header">
                        <h3>Delete Order #{{ targetInvoice?.order_number }}</h3>
                        <button @click="closeDeleteModal" class="close-btn">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                    <div class="files-modal-body center">
                        <p>Type admin passcode to confirm deletion of this order and all related data.</p>
                        <div class="code-inputs">
                            <input
                                ref="codeInput0"
                                class="code-box text-black"
                                type="password"
                                maxlength="1"
                                :value="codeDigits[0]"
                                @input="onCodeInput(0, $event)"
                                @keydown="onCodeKeydown(0, $event)"
                                @paste.prevent="onCodePaste($event)"
                            />
                            <input
                                ref="codeInput1"
                                class="code-box text-black"
                                type="password"
                                maxlength="1"
                                :value="codeDigits[1]"
                                @input="onCodeInput(1, $event)"
                                @keydown="onCodeKeydown(1, $event)"
                            />
                            <input
                                ref="codeInput2"
                                class="code-box text-black"
                                type="password"
                                maxlength="1"
                                :value="codeDigits[2]"
                                @input="onCodeInput(2, $event)"
                                @keydown="onCodeKeydown(2, $event)"
                            />
                            <input
                                ref="codeInput3"
                                class="code-box text-black"
                                type="password"
                                maxlength="1"
                                :value="codeDigits[3]"
                                @input="onCodeInput(3, $event)"
                                @keydown="onCodeKeydown(3, $event)"
                                @keyup.enter="confirmDelete"
                            />
                        </div>
                    </div>
                    <div class="pdf-modal-footer" style="justify-content: flex-end; gap: 8px;">
                        <button class="nav-btn" @click="closeDeleteModal">Cancel</button>
                        <button class="nav-btn danger" :disabled="deleting" @click="confirmDelete">{{ deleting ? 'Deleting…' : 'Delete' }}</button>
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
import { useToast } from 'vue-toastification';

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
            showArchived: false,
            yearFilter: new Date().getFullYear(),
            availableYears: [],
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
            // Thumbnail files discovery
            thumbnailFiles: {},
            // Delete modal state
            showDeleteModal: false,
            targetInvoice: null,
            deletePasscode: '',
            deleting: false,
            codeDigits: ['', '', '', ''],
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
        // Initialize available years
        this.initializeAvailableYears();
        
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
        this.showArchived = urlParams.get('showArchived') === '1';
        this.yearFilter = urlParams.get('fiscal_year') ? parseInt(urlParams.get('fiscal_year')) : new Date().getFullYear();
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
        canDeleteInvoice(invoice) {
            // Show the button for eligible orders; backend enforces admin + passcode
            const statusOk = (invoice?.status || '').toLowerCase() === 'not started yet';
            return statusOk;
        },
        openDeleteModal(invoice){
            this.targetInvoice = invoice;
            this.deletePasscode = '';
            this.codeDigits = ['', '', '', ''];
            this.showDeleteModal = true;
            this.$nextTick(() => {
                const first = this.$refs['codeInput0'];
                first && first.focus();
            });
        },
        closeDeleteModal(){
            if (this.deleting) return;
            this.showDeleteModal = false;
            this.targetInvoice = null;
            this.deletePasscode = '';
            this.codeDigits = ['', '', '', ''];
            // Clear any input DOM values
            for (let i = 0; i < 4; i++) {
                const el = this.$refs[`codeInput${i}`];
                if (el) el.value = '';
            }
        },
        async confirmDelete(){
            if (!this.targetInvoice || this.deleting) return;
            const toast = useToast();
            // Build passcode from split inputs if used
            const built = this.codeDigits.join('');
            const pass = built && built.length === 4 ? built : this.deletePasscode;
            if (pass !== '9632') {
                toast.error('Invalid passcode');
                return;
            }
            try{
                this.deleting = true;
                await axios.delete(`/orders/${this.targetInvoice.id}`, { data: { passcode: pass } });
                toast.success('Order deleted successfully');
                this.localInvoices = this.localInvoices.filter(inv => inv.id !== this.targetInvoice.id);
                this.closeDeleteModal();
            } catch (e){
                const msg = e?.response?.data?.error || 'Failed to delete order';
                toast.error(msg);
            } finally {
                this.deleting = false;
            }
        },
        onCodeInput(index, event){
            const val = (event.target.value || '').replace(/\D/g, '').slice(0,1);
            this.$set ? this.$set(this.codeDigits, index, val) : (this.codeDigits[index] = val);
            event.target.value = val;
            if (val && index < 3) {
                const next = this.$refs[`codeInput${index+1}`];
                next && next.focus();
            } else if (!val && index > 0) {
                const prev = this.$refs[`codeInput${index-1}`];
                prev && prev.focus();
            }
        },
        onCodeKeydown(index, event){
            if (event.key === 'Backspace' && !event.target.value && index > 0) {
                const prev = this.$refs[`codeInput${index-1}`];
                prev && prev.focus();
            }
            if (event.key === 'ArrowLeft' && index > 0) {
                const prev = this.$refs[`codeInput${index-1}`];
                prev && prev.focus();
                event.preventDefault();
            }
            if (event.key === 'ArrowRight' && index < 3) {
                const next = this.$refs[`codeInput${index+1}`];
                next && next.focus();
                event.preventDefault();
            }
        },
        onCodePaste(event){
            const text = (event.clipboardData || window.clipboardData).getData('text');
            const digits = (text || '').replace(/\D/g, '').slice(0,4).split('');
            for (let i=0;i<4;i++) {
                const d = digits[i] || '';
                this.$set ? this.$set(this.codeDigits, i, d) : (this.codeDigits[i] = d);
                const input = this.$refs[`codeInput${i}`];
                if (input) input.value = d;
            }
            const nextIndex = Math.min(digits.length, 3);
            const next = this.$refs[`codeInput${nextIndex}`];
            next && next.focus();
        },
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
            
        },
        viewJobs(invoiceId) {
            if (this.currentInvoiceId && this.currentInvoiceId !== invoiceId) {
                this.iconStates[this.currentInvoiceId] = false;
                this.currentInvoiceId = null;
            }

            // Toggle the icon state for the clicked invoice
            this.currentInvoiceId = this.currentInvoiceId === invoiceId ? null : invoiceId;
            this.iconStates[invoiceId] = !this.iconStates[invoiceId];
            
            // If we're expanding the invoice, initialize carousel indices
            if (this.currentInvoiceId === invoiceId) {
                const invoice = this.invoices.data.find(inv => inv.id === invoiceId);
                if (invoice && invoice.jobs) {
                    // Initialize carousel indices for multiple file jobs
                    this.initializeCarouselIndices(invoice);
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
                        showArchived: this.showArchived ? '1' : '0',
                        fiscal_year: this.yearFilter,
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
                if (this.showArchived) {
                    params.push(`showArchived=1`);
                }
                if (this.yearFilter) {
                    params.push(`fiscal_year=${this.yearFilter}`);
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
        
        // New simplified thumbnail methods
        hasDisplayableFiles(job) {
            // Check if job has any files to display (new or legacy system)
            // More robust check that covers all scenarios
            if (this.hasMultipleFiles(job)) {
                // New multi-file system
                return true; // Always try to display - let API handle missing thumbnails
            } else if (this.hasSingleNewFile(job)) {
                // New single file system
                return true; // Always try to display
            } else if (this.isLegacyJob(job)) {
                // Legacy system - check if file exists and is not placeholder
                return job.file && job.file !== 'placeholder.jpeg';
            }
            return false;
        },
        
        getJobFileCount(job) {
            if (this.hasMultipleFiles(job)) {
                return job.dimensions_breakdown.length;
            } else if (this.hasSingleNewFile(job)) {
                return 1;
            }
            return job.file ? 1 : 0;
        },
        
        getFirstAvailableThumbnail(job) {
            // Get thumbnails for the first file
            return this.getAvailableThumbnails(job.id, 0);
        },
        
        openFileThumbnailModal(job, jobIndex, fileIndex) {
            const jobName = `Job #${jobIndex + 1}`;
            const fileName = this.getFileName(job, fileIndex);
            const thumbnails = this.getAvailableThumbnails(job.id, fileIndex);
            
            // Don't return early anymore - getAvailableThumbnails now provides placeholders
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
        
        isLegacyJob(job) {
            // Check if this is a legacy job (pre-dimensions_breakdown)
            return !job.dimensions_breakdown && job.file;
        },
        
        onThumbnailError(event) {
            console.warn('Modal thumbnail failed to load');
            // Set error flag to show placeholder
            this.fileModal.hasError = true;
        },
        hasMultipleFiles(job) {
            // Check if job has dimensions_breakdown (new system) and has 2 or more files
            return job.dimensions_breakdown && Array.isArray(job.dimensions_breakdown) && job.dimensions_breakdown.length > 1;
        },
        hasSingleNewFile(job) {
            // Check if job has dimensions_breakdown (new system) with exactly 1 file
            return job.dimensions_breakdown && Array.isArray(job.dimensions_breakdown) && job.dimensions_breakdown.length === 1;
        },
        getJobFiles(job) {
            // Return dimensions_breakdown array for new system, or create array from legacy file
            if (job.dimensions_breakdown && Array.isArray(job.dimensions_breakdown)) {
                return job.dimensions_breakdown.map(fileData => fileData.filename || `File ${job.dimensions_breakdown.indexOf(fileData) + 1}`);
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
            this.modalCurrentPage = 1;
        },
        openFilePreview(job, fileIndex) {
            this.currentJob = job;
            this.currentFileIndex = fileIndex;
            this.modalCurrentPage = 1; // Reset to first page
            this.showFilesModal = false;
            this.showPdfModal = true;
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
        
        // Thumbnail loading system - now using SSR data
        getJobThumbnails(jobId) {
            // Find the job in the current invoices data
            for (const invoice of this.localInvoices) {
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
            // but fall back to dynamic API calls when needed
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
        
        
        getCurrentFileIndex(job) {
            // Get current carousel index for this job, default to 0
            return this.carouselIndices[job.id] || 0;
        },
        
        getCurrentPageIndex(job, fileIndex) {
            // Get current page index for a specific file within a job
            const key = `${job.id}_${fileIndex}`;
            return this.carouselIndices[key] || 0;
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
        
        initializeCarouselIndices(invoice) {
            // Initialize carousel indices for all jobs with multiple files and pages
            invoice.jobs.forEach(job => {
                if (this.hasMultipleFiles(job)) {
                    if (!this.carouselIndices.hasOwnProperty(job.id)) {
                        this.carouselIndices[job.id] = 0;
                    }
                }
                
                // Initialize page indices for each file in the job
                if (job.dimensions_breakdown && Array.isArray(job.dimensions_breakdown)) {
                    job.dimensions_breakdown.forEach((fileData, fileIndex) => {
                        const key = `${job.id}_${fileIndex}`;
                        if (!this.carouselIndices.hasOwnProperty(key)) {
                            this.carouselIndices[key] = 0;
                        }
                    });
                } else if (job.originalFile && Array.isArray(job.originalFile)) {
                    job.originalFile.forEach((file, fileIndex) => {
                        const key = `${job.id}_${fileIndex}`;
                        if (!this.carouselIndices.hasOwnProperty(key)) {
                            this.carouselIndices[key] = 0;
                        }
                    });
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
                            showArchived: this.showArchived ? '1' : '0',
                            fiscal_year: this.yearFilter,
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
        findJobById(jobId) {
            // Find job by ID across all invoices
            for (const invoice of this.localInvoices) {
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

        // Modal carousel methods
        getPreviewThumbnails() {
            if (!this.currentJob || this.currentFileIndex === null) return [];
            return this.getThumbnailsForFile(this.currentJob.id, this.currentFileIndex);
        },

        getModalCurrentThumbnail() {
            const thumbnails = this.getPreviewThumbnails();
            return thumbnails[this.modalCurrentPage - 1] || thumbnails[0];
        },

        nextModalPage() {
            const thumbnails = this.getPreviewThumbnails();
            if (this.modalCurrentPage < thumbnails.length) {
                this.modalCurrentPage++;
            }
        },

        previousModalPage() {
            if (this.modalCurrentPage > 1) {
                this.modalCurrentPage--;
            }
        },

        goToModalPage(pageNumber) {
            const thumbnails = this.getPreviewThumbnails();
            if (pageNumber >= 1 && pageNumber <= thumbnails.length) {
                this.modalCurrentPage = pageNumber;
            }
        },

        onThumbnailError(jobId, fileIndex) {
            console.warn(`Thumbnail failed to load for job ${jobId}, file ${fileIndex}`);
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

/* Page Header */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.sub-title {
    font-size: 20px;
    font-weight: bold;
    margin: 0;
    color: $white;
}

/* Filters Row */
.filters-row {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: center;
    margin-bottom: 20px;
    padding: 16px;
    background-color: rgba(255, 255, 255, 0.05);
    border-radius: 8px;
}

.search-box {
    position: relative;
    flex: 1;
    min-width: 200px;
    max-width: 320px;
}

.search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #6b7280;
    font-size: 14px;
}

.search-input {
    width: 100%;
    padding: 10px 12px 10px 36px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 6px;
    background-color: white;
    color: #1f2937;
    font-size: 14px;
    transition: border-color 0.2s, box-shadow 0.2s;
    
    &::placeholder {
        color: #9ca3af;
    }
    
    &:focus {
        outline: none;
        border-color: $blue;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
    }
}

.filter-controls {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
}

.filter-select {
    padding: 10px 32px 10px 12px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 6px;
    background-color: white;
    color: #1f2937;
    font-size: 14px;
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 8px center;
    background-size: 16px;
    transition: border-color 0.2s, box-shadow 0.2s;
    
    &:focus {
        outline: none;
        border-color: $blue;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
    }
    
    &:hover {
        border-color: rgba(255, 255, 255, 0.4);
    }
}

/* Fixed widths for each filter to prevent layout shift */
.filter-year {
    width: 90px;
}

.filter-status {
    width: 150px;
}

.filter-client {
    width: 160px;
}

.filter-sort {
    width: 140px;
}

/* Archived Toggle Styles */
.archived-toggle {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    user-select: none;
    padding: 6px 12px;
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: 6px;
    transition: background-color 0.2s;
    
    &:hover {
        background-color: rgba(255, 255, 255, 0.15);
    }
    
    input[type="checkbox"] {
        display: none;
    }
    
    .toggle-slider {
        position: relative;
        width: 36px;
        height: 20px;
        background-color: #4a5568;
        border-radius: 10px;
        transition: background-color 0.3s ease;
        
        &::after {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 16px;
            height: 16px;
            background-color: white;
            border-radius: 50%;
            transition: transform 0.3s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }
    }
    
    input[type="checkbox"]:checked + .toggle-slider {
        background-color: $blue;
        
        &::after {
            transform: translateX(16px);
        }
    }
    
    .toggle-label {
        font-size: 14px;
        color: white;
        font-weight: 500;
    }
}

/* Create Order Button */
.btn.create-order {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background-color: $green;
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: background-color 0.2s, transform 0.1s;
    
    &:hover {
        background-color: darken($green, 8%);
    }
    
    &:active {
        transform: scale(0.98);
    }
    
    i {
        font-size: 12px;
    }
}

/* Legacy styles cleanup */
.filter-container {
    justify-content: space-between;
    align-items: center;
    flex-wrap: nowrap;
    overflow-x: hidden;
    white-space: normal;
}
.filter-container .search {
    flex: 1 1 320px;
    min-width: 140px;
}
.filter-container .filters-group {
    flex: 0 1 auto;
    display: flex;
    flex-wrap: nowrap;
}
.jobInfo{

    align-items: center;
}
.locked{
    display: flex;
    justify-content: center;
}
.thumbnail-section{
    min-width: 140px; /* Adjusted for 40px thumbnails */
    max-width: 180px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: visible;
    flex-shrink: 0;
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
select {
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

.button-container{
    display: flex-end;
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

.delete-btn {
    background-color: $red;
    color: white;
    border-radius: 4px;
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

/* New simplified thumbnail styles */
.thumbnail-container-simple {
    width: 80px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    border-radius: 4px;
    
    &:hover {
        transform: scale(1.05);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }
}

.thumbnail-preview {
    position: relative;
    width: 100%;
    height: 100%;
    overflow: hidden;
    border-radius: 4px;
    background-color: #f8f9fa;
}

.thumbnail-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 4px;
    cursor: pointer;
    transition: transform 0.2s ease;
    
    &:hover {
        transform: scale(1.05);
    }
}

.thumbnail-placeholder {
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

.thumbnail-loading {
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

.file-count-badge {
    position: absolute;
    top: 3px;
    right: 3px;
    background-color: rgba(59, 130, 246, 0.9);
    color: white;
    font-size: 9px;
    padding: 2px 5px;
    border-radius: 3px;
    font-weight: bold;
    min-width: 16px;
    text-align: center;
}

.page-count-badge {
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

.file-number-badge {
    position: absolute;
    top: 12px; /* Match IndividualOrders.vue positioning */
    right: 12px;
    background-color: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
}

.files-container {
    display: flex;
    gap: 6px; /* Matches IndividualOrders.vue */
    flex-wrap: wrap;
    max-width: 90%; /* Allow multiple tiny thumbnails */
}

.file-thumbnail-container {
    width: 40px; /* Matches IndividualOrders.vue thumbnail-card sizing */
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

/* New thumbnail layout styles */
.job-thumbnails-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 100%;
}

.multiple-thumbnails-row {
    display: flex;
    flex-direction: row;
    gap: 8px;
    justify-content: center;
    align-items: center;
    flex-wrap: wrap;
    max-width: 180px;
}

.single-thumbnail-container {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 100%;
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
        /* Single file gets centered display */
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
    object-fit: contain; /* Preserve aspect ratio instead of cropping */
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

.file-thumbnail-container {
    position: relative;
    width: 100%;
    height: 100%;
}

.page-navigation {
    position: absolute;
    bottom: 2px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    flex-direction: row;
    gap: 2px;
    z-index: 15;
}

.page-nav-btn {
    background-color: rgba(59, 130, 246, 0.9);
    border: none;
    color: white;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 8px;
    transition: all 0.2s ease;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    
    &:hover {
        background-color: rgba(59, 130, 246, 1);
        transform: scale(1.1);
        box-shadow: 0 2px 4px rgba(0,0,0,0.3);
    }
    
    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        background-color: #6b7280;
    }
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
    padding: 10px;
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

.single-file-container {
    position: relative;
    width: 80px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
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

.single-file-page-navigation {
    position: absolute;
    bottom: 2px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 2px;
    z-index: 15;
}

.page-counter {
    background-color: rgba(0, 0, 0, 0.8);
    color: white;
    font-size: 8px;
    padding: 1px 4px;
    border-radius: 3px;
    font-weight: bold;
    min-width: 20px;
    text-align: center;
    line-height: 1;
}

.page-indicator {
    position: absolute;
    top: 2px;
    left: 50%;
    transform: translateX(-50%);
    background-color: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 1px 4px;
    border-radius: 8px;
    font-size: 8px;
    font-weight: bold;
    z-index: 5;
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
    width: 90%;
    max-width: 520px;
    max-height: 80vh;
    position: relative;
    color: white;
}

.files-modal-content.centered {
    margin: 0 auto;
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

.files-modal-body.center {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
}

.code-inputs {
    display: flex;
    gap: 12px;
    margin-top: 6px;
}

.code-box {
    width: 56px;
    height: 56px;
    text-align: center;
    font-size: 22px;
    border-radius: 6px;
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

/* Preview Modal Styles */
.preview-modal-overlay {
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
    backdrop-filter: blur(4px);
}

.preview-modal {
    background: white;
    border-radius: 12px;
    max-width: 90vw;
    max-height: 90vh;
    width: 800px;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    overflow: hidden;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid #e9ecef;
    background-color: #f8f9fa;

    h3 {
        margin: 0;
        font-size: 1.2rem;
        color: #333;
    }
}

.modal-close-btn {
    background: none;
    border: none;
    font-size: 1.2rem;
    cursor: pointer;
    color: #666;
    padding: 4px;
    border-radius: 4px;
    transition: all 0.2s ease;

    &:hover {
        background-color: #e9ecef;
        color: #333;
    }
}

.modal-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 0;
}

.modal-carousel {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 0;
}

.modal-image-container {
    flex: 1;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f8f9fa;
    min-height: 400px;
}

.modal-image {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    border-radius: 4px;
}

.modal-fallback {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #6c757d;
    
    i {
        font-size: 3rem;
        margin-bottom: 1rem;
    }
    
    p {
        margin: 0;
        font-size: 1.1rem;
    }
}

.modal-carousel-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background-color: rgba(0, 0, 0, 0.7);
    color: white;
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    font-size: 1rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    z-index: 10;

    &:hover:not(:disabled) {
        background-color: rgba(0, 0, 0, 0.9);
        transform: translateY(-50%) scale(1.1);
    }

    &:disabled {
        opacity: 0.3;
        cursor: not-allowed;
    }

    &.modal-carousel-prev {
        left: 20px;
    }

    &.modal-carousel-next {
        right: 20px;
    }
}

.modal-page-navigation {
    padding: 16px 20px;
    border-top: 1px solid #e9ecef;
    background-color: #f8f9fa;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}

.page-info {
    font-size: 0.9rem;
    color: #666;
    font-weight: 500;
}

.page-dots {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    max-height: 60px;
    overflow-y: auto;
}

.page-dot {
    background-color: #e9ecef;
    color: #666;
    border: none;
    border-radius: 4px;
    padding: 4px 8px;
    font-size: 0.8rem;
    cursor: pointer;
    transition: all 0.2s ease;
    min-width: 28px;

    &:hover {
        background-color: #dee2e6;
        color: #333;
    }

    &.active {
        background-color: #28a745;
        color: white;
    }
}

/* New simplified thumbnail modal styles */
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

.pdf-modal-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 15px;
    padding: 16px 20px;
    border-top: 1px solid #e9ecef;
    background-color: #f8f9fa;

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
            background-color: #5a6268;
        }

        &:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
    }
    .nav-btn.danger {
        background-color: $red;
    }

    .file-counter {
        margin: 0 10px;
        font-weight: bold;
        color: #333;
    }
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
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
    .filter-container { gap: 12px; flex-wrap: nowrap; overflow-x: hidden; }
    .filters-group { flex-wrap: nowrap; }
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
@media (max-width: 640px) {
    .filter-container { gap: 8px; flex-wrap: nowrap; overflow-x: hidden; }
    .filters-group { flex-wrap: nowrap; }
    
    /* Responsive thumbnail adjustments */
    .thumbnail-section {
        min-width: 120px;
        max-width: 140px;
    }
    
    .multiple-thumbnails-row {
        gap: 6px;
        max-width: 120px;
    }
    
    .preview-thumbnail-img {
        width: 35px;
        height: 45px;
        
        &.single-file-img {
            width: 40px;
            height: 50px;
        }
    }
    
    .thumbnail-placeholder-icon,
    .thumbnail-loading-indicator {
        width: 35px;
        height: 45px;
    }
}

@media (max-width: 480px) {
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

/* Responsive adjustments for filters */
@media (max-width: 1200px) {
    .filters-row {
        gap: 10px;
    }
    
    .search-box {
        max-width: 280px;
    }
    
    .filter-select {
        min-width: 120px;
    }
}

@media (max-width: 900px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    
    .filters-row {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-box {
        max-width: 100%;
    }
    
    .filter-controls {
        justify-content: flex-start;
    }
}
</style>


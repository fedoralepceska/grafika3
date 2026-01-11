<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="invoice2" subtitle="StockRealization" icon="invoice.png" link="stock-realizations" />
            <div class="dark-gray p-2 text-white">
                <RedirectTabs :route="$page.url" />
                <div class="form-container p-2">
                    <h2 class="sub-title">
                        Stock Realization Management
                    </h2>
                    <div class="filter-container flex gap-4 pb-10">
                        <div class="search flex gap-2">
                            <input v-model="searchQuery" placeholder="Enter order number or order name"
                                class="text-black search-input" @keyup.enter="searchStockRealizations" />
                            <button class="btn create-order1" @click="searchStockRealizations">Search</button>
                        </div>
                        <div class="flex gap-2 filters-group">
                            <div class="status">
                                <label class="pr-3">Filter by Status</label>
                                <select v-model="filterStatus" class="text-black filter-select" @change="applyFilter">
                                    <option value="All" hidden>Status</option>
                                    <option value="All">All Status</option>
                                    <option value="Pending">Pending</option>
                                    <option value="Realized">Realized</option>
                                </select>
                            </div>
                            <div class="client">
                                <label class="pr-3">Filter by Client</label>
                                <select v-model="filterClient" class="text-black filter-select" @change="applyFilter">
                                    <option value="All" hidden>Clients</option>
                                    <option value="All">All Clients</option>
                                    <option v-for="client in uniqueClients" :key="client.id" :value="client.name">{{
                                        client.name }}</option>
                                </select>
                            </div>
                            <div class="date">
                                <select v-model="sortOrder" class="text-black filter-select" @change="applyFilter">
                                    <option value="desc" hidden>Date</option>
                                    <option value="desc">Newest to Oldest</option>
                                    <option value="asc">Oldest to Newest</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div v-if="loading" class="loading-container">
                        <div class="loading-spinner">
                            <i class="fa fa-spinner fa-spin"></i>
                            <span>Loading stock realizations...</span>
                        </div>
                    </div>
                    <div v-else-if="filteredStockRealizations && filteredStockRealizations.length > 0">
                        <div :class="['border mb-2 invoice-row', getStatusRowClass(stockRealization.is_realized)]"
                            v-for="stockRealization in filteredStockRealizations" :key="stockRealization.id">
                            <div class="flex row-columns pl-2 pt-1 pb-1" style="line-height: initial">
                                <div class="info col-stock-id">
                                    <div class="info-label">Stock ID</div>
                                    <div class="bold">#{{ stockRealization.id }}</div>
                                </div>
                                <div class="info col-order-connection">
                                    <div class="info-label">Connected Order</div>
                                    <div class="bold order-link"
                                        @click="viewConnectedOrder(stockRealization.invoice_id)">
                                        #{{ getOrderNumber(stockRealization) }} - {{ stockRealization.invoice_title }}
                                    </div>
                                </div>
                                <div class="info col-client">
                                    <div class="info-label">Customer</div>
                                    <div class="bold ellipsis">{{ stockRealization.client.name }}</div>
                                </div>
                                <div class="info col-status">
                                    <div class="info-label">Status</div>
                                    <div
                                        :class="[getStatusColorClass(stockRealization.is_realized), 'bold', 'truncate', 'status-pill']">
                                        {{ stockRealization.is_realized ? 'Realized' : 'Pending' }}
                                    </div>
                                </div>
                                <div v-if="stockRealization.is_realized && stockRealization.realized_by"
                                    class="info col-realized-by">
                                    <div class="info-label">Realized By</div>
                                    <div class="bold truncate">{{ stockRealization.realized_by.name }}</div>
                                </div>
                                <div class="info col-expand">
                                    <div class="info-label">Actions</div>
                                    <div class="flex gap-2">
                                        <button class="flex items-center p-1"
                                            @click="toggleExpanded(stockRealization.id)"
                                            :title="expandedRows[stockRealization.id] ? 'Collapse' : 'Expand'">
                                            <i :class="[
                                                expandedRows[stockRealization.id] ? 'fa fa-chevron-up' : 'fa fa-chevron-down',
                                                'bg-gray-300 p-2 rounded'
                                            ]" aria-hidden="true"></i>
                                        </button>
                                        <button class="flex items-center p-1 pdf-button"
                                            @click="generatePDF(stockRealization)"
                                            :disabled="generatingPDF[stockRealization.id]"
                                            :title="generatingPDF[stockRealization.id] ? 'Generating PDF...' : 'Generate PDF Report'">
                                            <i :class="[
                                                generatingPDF[stockRealization.id] ? 'fa fa-spinner fa-spin' : 'fa fa-file-pdf-o',
                                                'bg-red-500 text-white p-2 rounded'
                                            ]" aria-hidden="true"></i>
                                        </button>
                                        <button v-if="stockRealization.is_realized && canRevert"
                                            class="flex items-center p-1" @click="openRevertModal(stockRealization)"
                                            :disabled="revertingStocks[stockRealization.id]"
                                            :title="revertingStocks[stockRealization.id] ? 'Reverting...' : 'Revert Realization'">
                                            <i :class="[
                                                revertingStocks[stockRealization.id] ? 'fa fa-spinner fa-spin' : 'fa fa-undo',
                                                'bg-yellow-500 text-white p-2 rounded'
                                            ]" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Expanded Jobs Table -->
                            <transition name="expand-collapse" appear>
                                <div v-if="expandedRows[stockRealization.id]" class="jobs-expanded-section">
                                    <!-- <div class="jobs-summary">
                                    <span class="jobs-count">{{ stockRealization.jobs?.length || 0 }} Jobs</span>
                                    <span v-if="!stockRealization.is_realized" class="edit-hint">Click values to edit</span>
                                </div> -->

                                    <div class="jobs-table-container">
                                        <table class="jobs-table">
                                            <thead>
                                                <tr>
                                                    <th>Job Name</th>
                                                    <th>Quantity</th>
                                                    <th>Copies</th>
                                                    <th>Area (m²)</th>
                                                    <th>Dimensions</th>
                                                    <!-- <th>Materials</th> -->
                                                    <!-- <th>Articles</th> -->
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="job in stockRealization.jobs" :key="job.id" class="job-row">
                                                    <td class="job-name">
                                                        <span>{{ job.name }}</span>
                                                    </td>
                                                    <td class="editable-cell">
                                                        <input v-if="!stockRealization.is_realized"
                                                            v-model.number="job.quantity" type="number" min="1"
                                                            class="edit-input"
                                                            @blur="updateJob(stockRealization.id, job)"
                                                            @keyup.enter="$event.target.blur()" />
                                                        <span v-else>{{ job.quantity }}</span>
                                                    </td>
                                                    <td class="editable-cell">
                                                        <input v-if="!stockRealization.is_realized"
                                                            v-model.number="job.copies" type="number" min="1"
                                                            class="edit-input"
                                                            @blur="updateJob(stockRealization.id, job)"
                                                            @keyup.enter="$event.target.blur()" />
                                                        <span v-else>{{ job.copies }}</span>
                                                    </td>
                                                    <td class="editable-cell">
                                                        <input v-if="!stockRealization.is_realized"
                                                            v-model.number="job.total_area_m2" type="number"
                                                            step="0.0001" min="0" class="edit-input"
                                                            @blur="updateJob(stockRealization.id, job)"
                                                            @keyup.enter="$event.target.blur()" />
                                                        <span v-else>{{ parseFloat(job.total_area_m2 || 0).toFixed(4)
                                                        }}</span>
                                                    </td>
                                                    <td class="dimensions-cell">
                                                        <div class="dimensions-inputs"
                                                            v-if="!stockRealization.is_realized">
                                                            <input v-model.number="job.width" type="number" step="0.01"
                                                                min="0" placeholder="W" class="dimension-input"
                                                                @blur="updateJob(stockRealization.id, job)"
                                                                @keyup.enter="$event.target.blur()" />
                                                            <span class="dimension-separator">×</span>
                                                            <input v-model.number="job.height" type="number" step="0.01"
                                                                min="0" placeholder="H" class="dimension-input"
                                                                @blur="updateJob(stockRealization.id, job)"
                                                                @keyup.enter="$event.target.blur()" />
                                                        </div>
                                                        <span v-else>{{ job.width || 0 }}cm × {{ job.height || 0
                                                        }}cm</span>
                                                    </td>
                                                    <!-- <td class="materials-cell">
                                                    <div class="materials-list">
                                                        <div v-if="job.small_material" class="material-item">
                                                            <span class="material-label">Small:</span>
                                                            <span class="material-name">{{ job.small_material.name || 'Unknown' }}</span>
                                                        </div>
                                                        <div v-if="job.large_material" class="material-item">
                                                            <span class="material-label">Large:</span>
                                                            <span class="material-name">{{ job.large_material.name || 'Unknown' }}</span>
                                                        </div>
                                                        <div v-if="!job.small_material && !job.large_material" class="no-materials">
                                                            No legacy materials
                                                        </div>
                                                    </div>
                                                </td> -->
                                                    <!-- <td class="articles-cell">
                                                    <div class="articles-summary">
                                                        <span v-if="job.articles && job.articles.length > 0">
                                                            {{ job.articles.length }} article(s)
                                                        </span>
                                                        <span v-else class="no-articles">
                                                            No articles
                                                        </span>
                                                    </div>
                                                </td> -->
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Articles Section - Displayed directly under the table -->
                                    <div class="articles-grid">
                                        <div v-for="job in stockRealization.jobs" :key="job.id" class="job-articles">
                                            <div class="job-articles-header">
                                                <h6 class="job-name">Materials used</h6>
                                                <button v-if="!stockRealization.is_realized" 
                                                    class="add-article-btn"
                                                    @click="openAddArticleModal(stockRealization.id, job)"
                                                    title="Add Material">
                                                    <i class="fa fa-plus"></i> Add
                                                </button>
                                            </div>
                                            <div v-if="job.articles && job.articles.length > 0" class="articles-list">
                                                <div v-for="articleData in job.articles" :key="articleData.id"
                                                    class="article-item">
                                                    <div class="article-name">{{ articleData.article.name }}</div>
                                                    <div class="article-quantity">
                                                        <input v-if="!stockRealization.is_realized"
                                                            v-model.number="articleData.quantity" type="number"
                                                            step="0.01" min="0" class="article-input"
                                                            @blur="updateArticle(stockRealization.id, job.id, articleData)"
                                                            @keyup.enter="$event.target.blur()" />
                                                        <span v-else>{{ parseFloat(articleData.quantity).toFixed(2) }}</span>
                                                        <span class="unit-type">{{ articleData.unit_type }}</span>
                                                        <button v-if="!stockRealization.is_realized"
                                                            class="remove-article-btn"
                                                            @click="removeArticle(stockRealization.id, job.id, articleData)"
                                                            :disabled="removingArticles[articleData.id]"
                                                            title="Remove Material">
                                                            <i :class="removingArticles[articleData.id] ? 'fa fa-spinner fa-spin' : 'fa fa-trash'"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div v-else class="no-articles">
                                                No articles for this job
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Stock Realization Button - Bottom Right Corner -->
                                    <div v-if="!stockRealization.is_realized"
                                        class="stock-realization-button-container">
                                        <button class="stock-realization-button" @click="realizeStock(stockRealization)"
                                            :disabled="realizingStocks[stockRealization.id] || generatingPDF[stockRealization.id] || revertingStocks[stockRealization.id]"
                                            title="Complete Stock Realization">
                                            <i :class="[
                                                realizingStocks[stockRealization.id] ? 'fa fa-spinner fa-spin' : 'fa fa-check',
                                                'mr-2'
                                            ]" aria-hidden="true"></i>
                                            {{ realizingStocks[stockRealization.id] ? 'Processing...' : 'Complete Realization' }}
                                        </button>
                                    </div>
                                </div>
                            </transition>
                        </div>
                    </div>
                    <div v-else class="text-center p-8">
                        <p class="text-gray-400">No stock realizations found.</p>
                    </div>
                </div>
                <Pagination :pagination="stockRealizations" @pagination-change-page="changePage" />

                <!-- Revert Confirmation Modal -->
                <div v-if="showRevertModal" class="files-modal" @click="closeRevertModal">
                    <div class="files-modal-content centered" @click.stop>
                        <div class="files-modal-header">
                            <h3>Revert Stock Realization #{{ targetStock?.id }}</h3>
                            <button @click="closeRevertModal" class="close-btn">
                                <i class="fa fa-times"></i>
                            </button>
                        </div>
                        <div class="files-modal-body center">
                            <p>Type admin passcode to confirm revert.</p>
                            <div class="code-inputs">
                                <input ref="codeInput0" class="code-box text-black" type="password" maxlength="1"
                                    :value="codeDigits[0]" @input="onCodeInput(0, $event)"
                                    @keydown="onCodeKeydown(0, $event)" @paste.prevent="onCodePaste($event)" />
                                <input ref="codeInput1" class="code-box text-black" type="password" maxlength="1"
                                    :value="codeDigits[1]" @input="onCodeInput(1, $event)"
                                    @keydown="onCodeKeydown(1, $event)" />
                                <input ref="codeInput2" class="code-box text-black" type="password" maxlength="1"
                                    :value="codeDigits[2]" @input="onCodeInput(2, $event)"
                                    @keydown="onCodeKeydown(2, $event)" />
                                <input ref="codeInput3" class="code-box text-black" type="password" maxlength="1"
                                    :value="codeDigits[3]" @input="onCodeInput(3, $event)"
                                    @keydown="onCodeKeydown(3, $event)" @keyup.enter="confirmRevert" />
                            </div>
                        </div>
                        <div class="pdf-modal-footer flex items-center mt-4 justify-end gap-2">
                            <button class="nav-btn" @click="closeRevertModal">Cancel</button>
                            <button class="nav-btn danger" :disabled="deleting" @click="confirmRevert">{{ deleting ?
                                'Processing…' : 'Confirm' }}</button>
                        </div>
                    </div>
                </div>

                <!-- Add Article Modal -->
                <div v-if="showAddArticleModal" class="files-modal" @click="closeAddArticleModal">
                    <div class="files-modal-content centered wide-modal" @click.stop>
                        <div class="files-modal-header">
                            <h3>Add Materials to Job</h3>
                            <button @click="closeAddArticleModal" class="close-btn">
                                <i class="fa fa-times"></i>
                            </button>
                        </div>
                        <div class="files-modal-body">
                            <!-- Search and Add Section -->
                            <div class="add-material-row">
                                <div class="search-col">
                                    <label>Search Material</label>
                                    <input 
                                        v-model="articleSearchQuery" 
                                        type="text" 
                                        class="form-input text-black"
                                        placeholder="Type to search..."
                                        @input="filterAvailableArticles"
                                    />
                                    <div class="articles-dropdown" v-if="filteredArticles.length > 0">
                                        <div 
                                            v-for="article in filteredArticles" 
                                            :key="article.id"
                                            class="article-option"
                                            :class="{ selected: selectedArticle?.id === article.id }"
                                            @click="selectArticle(article)"
                                        >
                                            <span class="article-option-name">{{ article.name }}</span>
                                            <span class="article-option-code">{{ article.code }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="qty-col">
                                    <label>Qty</label>
                                    <input 
                                        v-model.number="newArticleQuantity" 
                                        type="number" 
                                        step="0.01" 
                                        min="0.01"
                                        class="form-input text-black"
                                        placeholder="Qty"
                                        :disabled="!selectedArticle"
                                    />
                                </div>
                                <div class="unit-col">
                                    <label>Unit</label>
                                    <select v-model="newArticleUnitType" class="form-input text-black" :disabled="!selectedArticle">
                                        <option value="pieces">pieces</option>
                                        <option value="m²">m²</option>
                                        <option value="m">m</option>
                                        <option value="kg">kg</option>
                                        <option value="L">L</option>
                                    </select>
                                </div>
                                <div class="action-col">
                                    <label>&nbsp;</label>
                                    <button 
                                        class="add-to-list-btn"
                                        :disabled="!selectedArticle || !newArticleQuantity"
                                        @click="addToMaterialsList"
                                    >
                                        <i class="fa fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div v-if="loadingArticles" class="loading-articles">
                                <i class="fa fa-spinner fa-spin"></i> Loading...
                            </div>
                            
                            <!-- Materials to Add List -->
                            <div v-if="materialsToAdd.length > 0" class="materials-to-add-section">
                                <h4>Materials to Add ({{ materialsToAdd.length }})</h4>
                                <div class="materials-to-add-list">
                                    <div v-for="(item, index) in materialsToAdd" :key="index" class="material-to-add-item">
                                        <span class="material-name">{{ item.article.name }}</span>
                                        <span class="material-qty">{{ item.quantity }} {{ item.unit_type }}</span>
                                        <button class="remove-from-list-btn" @click="removeFromMaterialsList(index)">
                                            <i class="fa fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="no-materials-hint">
                                Search and add materials above
                            </div>
                        </div>
                        <div class="pdf-modal-footer flex items-center mt-4 justify-end gap-2">
                            <button class="nav-btn" @click="closeAddArticleModal">Cancel</button>
                            <button 
                                class="nav-btn success" 
                                :disabled="materialsToAdd.length === 0 || addingArticle"
                                @click="confirmAddArticles"
                            >
                                {{ addingArticle ? 'Adding...' : `Add ${materialsToAdd.length} Material(s)` }}
                            </button>
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
import Pagination from "@/Components/Pagination.vue";
import RedirectTabs from "@/Components/RedirectTabs.vue";
import axios from 'axios';
import { reactive, ref } from "vue";
import { useToast } from 'vue-toastification';

export default {
    components: {
        MainLayout,
        Header,
        Pagination,
        RedirectTabs,
    },
    props: {
        stockRealizations: Object,
        canRevert: {
            type: Boolean,
            default: false,
        },
    },
    setup(props) {
        const searchQuery = ref('');
        const filterStatus = ref('All');
        const filterClient = ref('All');
        const sortOrder = ref('desc');
        const loading = ref(false);
        const filteredStockRealizations = ref(props.stockRealizations.data || []);
        const uniqueClients = ref([]);
        const realizingStocks = reactive({});
        const expandedRows = reactive({});
        const updatingJobs = reactive({});
        const generatingPDF = reactive({});
        const revertingStocks = reactive({});
        const showRevertModal = ref(false);
        const targetStock = ref(null);
        const codeDigits = ref(['', '', '', '']);
        const deleting = ref(false);
        
        // Add Article Modal state
        const showAddArticleModal = ref(false);
        const addArticleStockId = ref(null);
        const addArticleJob = ref(null);
        const availableArticles = ref([]);
        const filteredArticles = ref([]);
        const articleSearchQuery = ref('');
        const selectedArticle = ref(null);
        const newArticleQuantity = ref(1);
        const newArticleUnitType = ref('pieces');
        const loadingArticles = ref(false);
        const addingArticle = ref(false);
        const removingArticles = reactive({});
        const materialsToAdd = ref([]);

        return {
            searchQuery,
            filterStatus,
            filterClient,
            sortOrder,
            loading,
            filteredStockRealizations,
            uniqueClients,
            realizingStocks,
            expandedRows,
            updatingJobs,
            generatingPDF,
            revertingStocks,
            showRevertModal,
            targetStock,
            codeDigits,
            deleting,
            // Add Article Modal
            showAddArticleModal,
            addArticleStockId,
            addArticleJob,
            availableArticles,
            filteredArticles,
            articleSearchQuery,
            selectedArticle,
            newArticleQuantity,
            newArticleUnitType,
            loadingArticles,
            addingArticle,
            removingArticles,
            materialsToAdd,
        };
    },
    mounted() {
        this.filteredStockRealizations = this.stockRealizations.data || [];
        this.loadUniqueClients();
    },
    watch: {
        stockRealizations: {
            handler(newVal) {
                this.filteredStockRealizations = newVal?.data || [];
            },
            deep: true
        }
    },
    computed: {},
    methods: {
        toastSuccess(message) {
            const toast = useToast();
            toast && typeof toast.success === 'function' ? toast.success(message) : console.log('SUCCESS:', message);
        },
        toastError(message) {
            const toast = useToast();
            toast && typeof toast.error === 'function' ? toast.error(message) : console.error('ERROR:', message);
        },
        getAxiosErrorMessage(err, fallback = 'An error occurred') {
            try {
                const resp = err && typeof err === 'object' ? err.response : null;
                if (!resp) return fallback;
                const data = resp.data || {};
                if (typeof data === 'string') return data;
                if (data && typeof data.error === 'string') return data.error;
                if (data && data.message) return String(data.message);
                return fallback;
            } catch (_) {
                return fallback;
            }
        },
        async loadUniqueClients() {
            try {
                const response = await axios.get('/unique-clients');
                this.uniqueClients = response.data;
            } catch (error) {
                console.error('Error loading unique clients:', error);
                this.toastError(this.getAxiosErrorMessage(error, 'Failed to load clients'));
            }
        },
        async searchStockRealizations() {
            this.applyFilter();
        },
        async applyFilter() {
            this.loading = true;
            const params = {
                searchQuery: this.searchQuery,
                status: this.filterStatus,
                client: this.filterClient,
                sortOrder: this.sortOrder,
            };
            this.$inertia.get('/stock-realizations', params, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                onFinish: () => {
                    this.loading = false;
                }
            });
        },
        changePage(page) {
            const params = {
                searchQuery: this.searchQuery,
                status: this.filterStatus,
                client: this.filterClient,
                sortOrder: this.sortOrder,
                page: page,
            };
            this.$inertia.get('/stock-realizations', params, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });
        },
        viewStockRealization(id) {
            this.$inertia.visit(`/stock-realizations/${id}`);
        },
        viewConnectedOrder(invoiceId) {
            window.open(`/orders/${invoiceId}`, '_blank');
        },
        async realizeStock(stockRealization) {
            if (this.realizingStocks[stockRealization.id]) return;

            this.realizingStocks[stockRealization.id] = true;

            try {
                const response = await axios.post(`/stock-realizations/${stockRealization.id}/realize`);

                if (response && response.data && response.data.message) {
                    // Update the local data
                    const index = this.filteredStockRealizations.findIndex(sr => sr.id === stockRealization.id);
                    if (index !== -1) {
                        this.filteredStockRealizations[index].is_realized = true;
                        this.filteredStockRealizations[index].realized_at = new Date().toISOString();
                        this.filteredStockRealizations[index].realized_by = response.data.stockRealization.realized_by;
                    }

                    this.toastSuccess('Stock realization completed successfully!');
                } else {
                    this.toastError('Failed to complete stock realization');
                }
            } catch (error) {
                console.error('Error realizing stock:', error);
                this.toastError(error.response?.data?.error || 'Failed to complete stock realization');
            } finally {
                this.realizingStocks[stockRealization.id] = false;
            }
        },
        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        },
        getOrderNumber(stockRealization) {
            if (stockRealization.invoice && stockRealization.invoice.order_number && stockRealization.invoice.fiscal_year) {
                return `${stockRealization.invoice.order_number}/${stockRealization.invoice.fiscal_year}`;
            }
            // Fallback to invoice_id if order_number not available
            return stockRealization.invoice_id;
        },
        getStatusRowClass(isRealized) {
            return isRealized ? 'status-completed' : 'status-pending';
        },
        getStatusColorClass(isRealized) {
            return isRealized ? 'status-completed-text' : 'status-pending-text';
        },
        toggleExpanded(stockRealizationId) {
            this.expandedRows[stockRealizationId] = !this.expandedRows[stockRealizationId];

            // Load job details if expanding for the first time
            if (this.expandedRows[stockRealizationId]) {
                this.loadJobDetails(stockRealizationId);
            }
        },
        async loadJobDetails(stockRealizationId) {
            try {
                const response = await axios.get(`/stock-realizations/${stockRealizationId}`);
                const stockRealization = response.data.stockRealization;

                // Find and update the stock realization in our local data
                const index = this.filteredStockRealizations.findIndex(sr => sr.id === stockRealizationId);
                if (index !== -1) {
                    this.filteredStockRealizations[index] = stockRealization;
                }
            } catch (error) {
                console.error('Error loading job details:', error);
                this.toastError(this.getAxiosErrorMessage(error, 'Failed to load job details'));
            }
        },
        async updateJob(stockRealizationId, job) {
            if (this.updatingJobs[job.id]) return;

            this.updatingJobs[job.id] = true;

            try {
                const response = await axios.put(
                    `/stock-realizations/${stockRealizationId}/jobs/${job.id}`,
                    {
                        quantity: job.quantity,
                        copies: job.copies,
                        width: job.width,
                        height: job.height,
                        total_area_m2: job.total_area_m2,
                    }
                );

                if (response.data.message) {
                    this.toastSuccess('Job updated successfully!');
                } else {
                    this.toastError('Failed to update job');
                }
            } catch (error) {
                console.error('Error updating job:', error);
                this.toastError(error.response?.data?.error || 'Failed to update job');
            } finally {
                this.updatingJobs[job.id] = false;
            }
        },
        async updateArticle(stockRealizationId, jobId, articleData) {
            try {
                const response = await axios.put(
                    `/stock-realizations/${stockRealizationId}/jobs/${jobId}/articles/${articleData.id}`,
                    {
                        quantity: articleData.quantity,
                    }
                );

                if (response.data.message) {
                    this.toastSuccess('Article quantity updated successfully!');
                } else {
                    this.toastError('Failed to update article');
                }
            } catch (error) {
                console.error('Error updating article:', error);
                this.toastError(error.response?.data?.error || 'Failed to update article');
            }
        },
        async generatePDF(stockRealization) {
            if (this.generatingPDF[stockRealization.id]) return;

            this.generatingPDF[stockRealization.id] = true;

            try {
                // Open PDF in new tab using the backend route
                const pdfUrl = `/stock-realizations/${stockRealization.id}/pdf`;
                window.open(pdfUrl, '_blank');

                this.toastSuccess('PDF opened in new tab!');
            } catch (error) {
                console.error('Error opening PDF:', error);
                this.toastError('Failed to open PDF');
            } finally {
                this.generatingPDF[stockRealization.id] = false;
            }
        },
        openRevertModal(stockRealization) {
            this.targetStock = stockRealization;
            this.codeDigits = ['', '', '', ''];
            this.showRevertModal = true;
            this.$nextTick(() => {
                const first = this.$refs['codeInput0'];
                first && first.focus();
            });
        },
        closeRevertModal() {
            if (this.deleting) return;
            this.showRevertModal = false;
            this.targetStock = null;
            this.codeDigits = ['', '', '', ''];
        },
        async confirmRevert() {
            if (!this.targetStock) return;
            const code = (this.codeDigits || []).join('');
            if (!code || code.length !== 4) {
                this.toastError('Enter 4-digit passcode');
                return;
            }
            // In-house: quick client-side passcode gate for immediate feedback
            if (code !== '9632') {
                this.toastError('Invalid passcode');
                return;
            }
            const stockRealization = this.targetStock;
            if (this.revertingStocks[stockRealization.id]) return;
            this.deleting = true;
            this.revertingStocks[stockRealization.id] = true;
            let shouldClose = false;
            try {
                const response = await axios.post(`/stock-realizations/${stockRealization.id}/revert`, { passcode: code });
                const respData = (response && response.data) ? response.data : {};
                if (respData && respData.message) {
                    const index = this.filteredStockRealizations.findIndex(sr => sr.id === stockRealization.id);
                    if (index !== -1) {
                        this.filteredStockRealizations[index].is_realized = false;
                        this.filteredStockRealizations[index].realized_at = null;
                        this.filteredStockRealizations[index].realized_by = null;
                    }
                    this.toastSuccess('Stock realization reverted successfully!');
                    shouldClose = true;
                } else {
                    const msg = (respData && respData.error) || 'Failed to revert stock realization';
                    this.toastError(msg);
                }
            } catch (error) {
                console.error('Error reverting stock:', error);
                const msg = this.getAxiosErrorMessage(error, 'Failed to revert stock realization');
                this.toastError(msg);
            } finally {
                this.revertingStocks[stockRealization.id] = false;
                this.deleting = false;
                if (shouldClose) {
                    this.closeRevertModal();
                }
            }
        },
        onCodeInput(index, event) {
            const val = (event.target.value || '').replace(/\D/g, '').slice(0, 1);
            if (Array.isArray(this.codeDigits)) {
                this.codeDigits.splice(index, 1, val);
            }
            event.target.value = val;
            if (val && index < 3) {
                const next = this.$refs[`codeInput${index + 1}`];
                next && next.focus();
            } else if (!val && index > 0) {
                const prev = this.$refs[`codeInput${index - 1}`];
                prev && prev.focus();
            }
        },
        onCodeKeydown(index, event) {
            if (event.key === 'Backspace' && !event.target.value && index > 0) {
                const prev = this.$refs[`codeInput${index - 1}`];
                prev && prev.focus();
            }
            if (event.key === 'ArrowLeft' && index > 0) {
                const prev = this.$refs[`codeInput${index - 1}`];
                prev && prev.focus();
                event.preventDefault();
            }
            if (event.key === 'ArrowRight' && index < 3) {
                const next = this.$refs[`codeInput${index + 1}`];
                next && next.focus();
                event.preventDefault();
            }
        },
        onCodePaste(event) {
            const text = (event.clipboardData || window.clipboardData).getData('text');
            const digits = (text || '').replace(/\D/g, '').slice(0, 4).split('');
            for (let i = 0; i < 4; i++) {
                const d = digits[i] || '';
                if (Array.isArray(this.codeDigits)) {
                    this.codeDigits.splice(i, 1, d);
                }
                const input = this.$refs[`codeInput${i}`];
                if (input) input.value = d;
            }
            const nextIndex = Math.min(digits.length, 3);
            const next = this.$refs[`codeInput${nextIndex}`];
            next && next.focus();
        },
        // Add Article Modal Methods
        async openAddArticleModal(stockRealizationId, job) {
            this.addArticleStockId = stockRealizationId;
            this.addArticleJob = job;
            this.articleSearchQuery = '';
            this.selectedArticle = null;
            this.newArticleQuantity = 1;
            this.newArticleUnitType = 'pieces';
            this.filteredArticles = [];
            this.materialsToAdd = [];
            this.showAddArticleModal = true;
            
            // Load available articles if not already loaded
            if (this.availableArticles.length === 0) {
                await this.loadAvailableArticles();
            }
        },
        closeAddArticleModal() {
            if (this.addingArticle) return;
            this.showAddArticleModal = false;
            this.addArticleStockId = null;
            this.addArticleJob = null;
            this.articleSearchQuery = '';
            this.selectedArticle = null;
            this.filteredArticles = [];
            this.materialsToAdd = [];
        },
        async loadAvailableArticles() {
            this.loadingArticles = true;
            try {
                const response = await axios.get('/stock-realizations/articles/available');
                this.availableArticles = response.data || [];
            } catch (error) {
                console.error('Error loading articles:', error);
                this.toastError('Failed to load available materials');
            } finally {
                this.loadingArticles = false;
            }
        },
        filterAvailableArticles() {
            const query = (this.articleSearchQuery || '').toLowerCase().trim();
            if (!query) {
                this.filteredArticles = [];
                return;
            }
            this.filteredArticles = this.availableArticles.filter(article => {
                const name = (article.name || '').toLowerCase();
                const code = (article.code || '').toLowerCase();
                return name.includes(query) || code.includes(query);
            }).slice(0, 10); // Limit to 10 results
        },
        selectArticle(article) {
            this.selectedArticle = article;
            this.articleSearchQuery = article.name;
            this.filteredArticles = [];
            // Auto-select unit type from article
            if (article.unit_type) {
                this.newArticleUnitType = article.unit_type;
            }
        },
        addToMaterialsList() {
            if (!this.selectedArticle || !this.newArticleQuantity) return;
            
            // Check if already in list
            const existingIndex = this.materialsToAdd.findIndex(m => m.article.id === this.selectedArticle.id);
            if (existingIndex !== -1) {
                // Update quantity if already exists
                this.materialsToAdd[existingIndex].quantity += this.newArticleQuantity;
                this.toastSuccess('Quantity updated for existing material');
            } else {
                // Add new material to list
                this.materialsToAdd.push({
                    article: { ...this.selectedArticle },
                    quantity: this.newArticleQuantity,
                    unit_type: this.newArticleUnitType,
                });
            }
            
            // Reset selection for next material
            this.selectedArticle = null;
            this.articleSearchQuery = '';
            this.newArticleQuantity = 1;
            this.newArticleUnitType = 'pieces';
        },
        removeFromMaterialsList(index) {
            this.materialsToAdd.splice(index, 1);
        },
        async confirmAddArticles() {
            if (this.materialsToAdd.length === 0 || this.addingArticle) return;
            
            this.addingArticle = true;
            let successCount = 0;
            let updatedCount = 0;
            let errorCount = 0;
            
            try {
                for (const item of this.materialsToAdd) {
                    try {
                        const response = await axios.post(
                            `/stock-realizations/${this.addArticleStockId}/jobs/${this.addArticleJob.id}/articles`,
                            {
                                article_id: item.article.id,
                                quantity: item.quantity,
                                unit_type: item.unit_type,
                            }
                        );
                        
                        if (response.data.message) {
                            const stockIndex = this.filteredStockRealizations.findIndex(sr => sr.id === this.addArticleStockId);
                            if (stockIndex !== -1) {
                                const jobIndex = this.filteredStockRealizations[stockIndex].jobs.findIndex(j => j.id === this.addArticleJob.id);
                                if (jobIndex !== -1) {
                                    if (!this.filteredStockRealizations[stockIndex].jobs[jobIndex].articles) {
                                        this.filteredStockRealizations[stockIndex].jobs[jobIndex].articles = [];
                                    }
                                    
                                    if (response.data.updated) {
                                        // Update existing article in local data
                                        const existingIndex = this.filteredStockRealizations[stockIndex].jobs[jobIndex].articles.findIndex(
                                            a => a.article_id === item.article.id || a.article?.id === item.article.id
                                        );
                                        if (existingIndex !== -1) {
                                            this.filteredStockRealizations[stockIndex].jobs[jobIndex].articles[existingIndex] = response.data.article;
                                        }
                                        updatedCount++;
                                    } else {
                                        // Add new article to local data
                                        this.filteredStockRealizations[stockIndex].jobs[jobIndex].articles.push(response.data.article);
                                        successCount++;
                                    }
                                }
                            }
                        }
                    } catch (err) {
                        console.error('Error adding article:', item.article.name, err);
                        errorCount++;
                    }
                }
                
                let message = '';
                if (successCount > 0) message += `${successCount} added`;
                if (updatedCount > 0) message += (message ? ', ' : '') + `${updatedCount} updated`;
                if (message) {
                    this.toastSuccess(`Materials: ${message}!`);
                }
                if (errorCount > 0) {
                    this.toastError(`${errorCount} material(s) failed`);
                }
                
                // Set addingArticle to false before closing so the guard doesn't block
                this.addingArticle = false;
                this.closeAddArticleModal();
            } catch (error) {
                console.error('Error adding articles:', error);
                this.toastError(this.getAxiosErrorMessage(error, 'Failed to add materials'));
                this.addingArticle = false;
            }
        },
        async removeArticle(stockRealizationId, jobId, articleData) {
            if (this.removingArticles[articleData.id]) return;
            
            if (!confirm('Are you sure you want to remove this material?')) return;
            
            this.removingArticles[articleData.id] = true;
            try {
                const response = await axios.delete(
                    `/stock-realizations/${stockRealizationId}/jobs/${jobId}/articles/${articleData.id}`
                );
                
                if (response.data.message) {
                    // Remove the article from local data
                    const stockIndex = this.filteredStockRealizations.findIndex(sr => sr.id === stockRealizationId);
                    if (stockIndex !== -1) {
                        const jobIndex = this.filteredStockRealizations[stockIndex].jobs.findIndex(j => j.id === jobId);
                        if (jobIndex !== -1) {
                            const articleIndex = this.filteredStockRealizations[stockIndex].jobs[jobIndex].articles.findIndex(a => a.id === articleData.id);
                            if (articleIndex !== -1) {
                                this.filteredStockRealizations[stockIndex].jobs[jobIndex].articles.splice(articleIndex, 1);
                            }
                        }
                    }
                    this.toastSuccess('Material removed successfully!');
                } else {
                    this.toastError('Failed to remove material');
                }
            } catch (error) {
                console.error('Error removing article:', error);
                this.toastError(this.getAxiosErrorMessage(error, 'Failed to remove material'));
            } finally {
                this.removingArticles[articleData.id] = false;
            }
        },
    },
};
</script>

<style scoped lang="scss">
/* Revert Modal Styles */
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

.nav-btn {
    background-color: #4a5568;
    border: none;
    color: white;
    font-size: 1em;
    cursor: pointer;
    padding: 8px 16px;
    border-radius: 4px;
    transition: background-color 0.2s;
}

.nav-btn:hover:not(:disabled) {
    background-color: $red;
}

.nav-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.nav-btn.danger {
    background-color: #dc2626;
}

.nav-btn.success {
    background-color: #10b981;
}

.nav-btn.success:hover:not(:disabled) {
    background-color: #059669;
}

/* Add Article Modal Styles */
.wide-modal {
    max-width: 650px !important;
}

.files-modal-body {
    max-height: 450px;
    overflow-y: auto;
}

.add-material-row {
    display: flex;
    gap: 10px;
    align-items: flex-start;
    margin-bottom: 15px;
}

.search-col {
    flex: 2;
    position: relative;
}

.qty-col {
    width: 80px;
}

.unit-col {
    width: 100px;
}

.action-col {
    width: 50px;
}

.add-material-row label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    font-size: 12px;
}

.add-to-list-btn {
    width: 100%;
    height: 42px;
    background-color: #10b981;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 16px;
    transition: background-color 0.2s;
}

.add-to-list-btn:hover:not(:disabled) {
    background-color: #059669;
}

.add-to-list-btn:disabled {
    background-color: #4a5568;
    cursor: not-allowed;
}

.materials-to-add-section {
    margin-top: 15px;
    padding: 12px;
    background-color: rgba(16, 185, 129, 0.1);
    border: 1px solid rgba(16, 185, 129, 0.3);
    border-radius: 6px;
}

.materials-to-add-section h4 {
    margin: 0 0 10px 0;
    font-size: 14px;
    color: #10b981;
}

.materials-to-add-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.material-to-add-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 12px;
    background-color: rgba(255, 255, 255, 0.05);
    border-radius: 4px;
}

.material-to-add-item .material-name {
    flex: 1;
    font-weight: 500;
}

.material-to-add-item .material-qty {
    margin-right: 10px;
    color: rgba(255, 255, 255, 0.7);
}

.remove-from-list-btn {
    background-color: rgba(239, 68, 68, 0.2);
    color: #ef4444;
    border: none;
    padding: 4px 8px;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.remove-from-list-btn:hover {
    background-color: rgba(239, 68, 68, 0.4);
}

.no-materials-hint {
    text-align: center;
    padding: 20px;
    color: rgba(255, 255, 255, 0.5);
    font-style: italic;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.form-input {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #4a5568;
    border-radius: 6px;
    background-color: rgba(255, 255, 255, 0.1);
    color: white;
    font-size: 14px;
}

.form-input:focus {
    outline: none;
    border-color: #0073a9;
    box-shadow: 0 0 0 2px rgba(0, 115, 169, 0.2);
}

.articles-dropdown {
    max-height: 200px;
    overflow-y: auto;
    border: 1px solid #4a5568;
    border-radius: 6px;
    margin-top: 5px;
    background-color: #1a202c;
}

.article-option {
    padding: 10px 12px;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #2d3748;
    transition: background-color 0.2s;
}

.article-option:last-child {
    border-bottom: none;
}

.article-option:hover {
    background-color: rgba(0, 115, 169, 0.2);
}

.article-option.selected {
    background-color: rgba(0, 115, 169, 0.3);
}

.article-option-name {
    font-weight: 500;
}

.article-option-code {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.6);
}

.no-results,
.loading-articles {
    padding: 15px;
    text-align: center;
    color: rgba(255, 255, 255, 0.6);
}

.selected-article-info {
    margin-top: 15px;
    padding: 12px;
    background-color: rgba(16, 185, 129, 0.1);
    border: 1px solid rgba(16, 185, 129, 0.3);
    border-radius: 6px;
}

.quantity-input-group {
    margin-top: 10px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.quantity-input-group label {
    margin-bottom: 0;
    min-width: 60px;
}

.quantity-input {
    width: 100px !important;
}

.unit-select {
    width: 100px !important;
}

/* Add/Remove Article Button Styles */
.job-articles-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.add-article-btn {
    background-color: rgba(16, 185, 129, 0.2);
    color: #10b981;
    border: 1px solid rgba(16, 185, 129, 0.4);
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 4px;
}

.add-article-btn:hover {
    background-color: rgba(16, 185, 129, 0.3);
    border-color: #10b981;
}

.remove-article-btn {
    background-color: rgba(239, 68, 68, 0.2);
    color: #ef4444;
    border: 1px solid rgba(239, 68, 68, 0.4);
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s;
    margin-left: 8px;
}

.remove-article-btn:hover:not(:disabled) {
    background-color: rgba(239, 68, 68, 0.3);
    border-color: #ef4444;
}

.remove-article-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Modern invoice row styling matching Index.vue */
.dark-gray {
    background-color: $dark-gray;
    justify-content: left;
    border-radius: 8px;
    align-items: center;
    min-height: 20vh;
    min-width: 80vh;
}

.filter-container {
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

.search-input {
    width: 50vh;
    max-width: 100%;
    border-radius: 3px;
}

.filter-select {
    width: 25vh;
    max-width: 100%;
    border-radius: 3px;
}

select {
    width: 25vh;
    border-radius: 3px;
}

.btn {
    padding: 9px 12px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    border-radius: 2px;
}

.create-order1 {
    background-color: $blue;
    color: white;
}

.invoice-row {
    border: 3px solid rgba(255, 255, 255, 0.25);
    border-radius: 8px;
    overflow: hidden;
    background: rgba(255, 255, 255, 0.06);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
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
.status-pending {
    border-color: rgba(234, 179, 8, 0.35);
    /* amber */
    background-color: rgba(234, 179, 8, 0.05);
}

.status-completed {
    border-color: rgba(16, 185, 129, 0.35);
    /* green */
    background-color: rgba(16, 185, 129, 0.05);
}

/* Status pill styling matching Index.vue */
.status-pill {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 9999px;
    background-color: rgba(255, 255, 255, 0.08);
    width: fit-content;
}

.status-pending-text {
    color: #ea8a00;
    background-color: rgba(234, 179, 8, 0.2);
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.875rem;
    font-weight: 500;
}

.status-completed-text {
    color: #10b981;
    background-color: rgba(16, 185, 129, 0.2);
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.875rem;
    font-weight: 500;
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
}

.loading-spinner i {
    font-size: 24px;
    color: #0073a9;
}

.loading-spinner span {
    font-size: 16px;
    color: white;
}

/* Expanded Jobs Section Styling - Modern Design */
.jobs-expanded-section {
    background-color: rgba(255, 255, 255, 0.06);
    border-top: 1px solid rgba(255, 255, 255, 0.12);
    padding: 1rem;
    margin-top: 0.5rem;
    border-radius: 0 0 8px 8px;
}

/* Smooth Expand/Collapse Transitions */
.expand-collapse-enter-active {
    transition: all 0.3s ease-out;
    overflow: hidden;
}

.expand-collapse-leave-active {
    transition: all 0.3s ease-in;
    overflow: hidden;
}

.expand-collapse-enter-from {
    opacity: 0;
    transform: translateY(-20px);
    max-height: 0;
    padding-top: 0;
    padding-bottom: 0;
    margin-top: 0;
}

.expand-collapse-leave-to {
    opacity: 0;
    transform: translateY(-20px);
    max-height: 0;
    padding-top: 0;
    padding-bottom: 0;
    margin-top: 0;
}

.expand-collapse-enter-to,
.expand-collapse-leave-from {
    opacity: 1;
    transform: translateY(0);
    max-height: 2000px;
}

.jobs-summary {
    display: flex;
    gap: 1rem;
    align-items: center;
    margin-bottom: 0.75rem;
}

.jobs-count {
    background-color: rgba(0, 115, 169, 0.2);
    color: #0073a9;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.875rem;
    font-weight: 500;
}

.edit-hint {
    color: #ea8a00;
    font-size: 0.75rem;
    font-style: italic;
}

.jobs-table-container {
    overflow-x: auto;
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.12);
    background: rgba(255, 255, 255, 0.02);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    backdrop-filter: blur(10px);
}

.jobs-table {
    width: 100%;
    border-collapse: collapse;
    background-color: rgba(255, 255, 255, 0.02);
}

.jobs-table thead {
    background-color: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(10px);
}

.jobs-table th {
    padding: 0.75rem;
    text-align: left;
    font-weight: 600;
    color: white;
    border-bottom: 1px solid rgba(255, 255, 255, 0.12);
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.jobs-table td {
    padding: 0.75rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    vertical-align: top;
    transition: background-color 0.2s ease;
}

.job-row:hover {
    background-color: rgba(255, 255, 255, 0.06);
}

.job-name {
    font-size: 18px;
    font-weight: 500;
    color: white;
    min-width: 150px;
}

.editable-cell {
    min-width: 80px;
}

.edit-input {
    width: 100%;
    padding: 0.5rem 0.75rem;
    border: 1px solid rgba(255, 255, 255, 0.25);
    border-radius: 6px;
    background-color: rgba(255, 255, 255, 0.1);
    color: white;
    font-size: 0.875rem;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.edit-input:focus {
    outline: none;
    border-color: #0073a9;
    background-color: rgba(255, 255, 255, 0.15);
    box-shadow: 0 0 0 3px rgba(0, 115, 169, 0.2);
}

.dimensions-cell {
    min-width: 120px;
}

.dimensions-inputs {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.dimension-input {
    width: 50px;
    padding: 0.5rem 0.25rem;
    border: 1px solid rgba(255, 255, 255, 0.25);
    border-radius: 6px;
    background-color: rgba(255, 255, 255, 0.1);
    color: white;
    font-size: 0.75rem;
    text-align: center;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.dimension-input:focus {
    outline: none;
    border-color: #0073a9;
    background-color: rgba(255, 255, 255, 0.15);
    box-shadow: 0 0 0 2px rgba(0, 115, 169, 0.2);
}

.dimension-separator {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.75rem;
    font-weight: bold;
}

.materials-cell {
    min-width: 150px;
}

.materials-list {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.material-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.material-label {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.7);
    font-weight: 500;
    min-width: 40px;
}

.material-name {
    font-size: 0.875rem;
    color: white;
    font-weight: 400;
}

.no-materials {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.5);
    font-style: italic;
}

.articles-cell {
    min-width: 200px;
}

.articles-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.article-item {
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 0.85rem;
    padding: 0.25rem;
    background-color: rgba(255, 255, 255, 0.08);
    border-radius: 6px;
    border: 1px solid rgba(255, 255, 255, 0.12);
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.article-name {
    font-size: 0.875rem;
    color: white;
    font-weight: 500;
}

.article-quantity {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.article-input {
    width: 80px;
    padding: 0.20rem 0.20rem;
    border: 1px solid rgba(255, 255, 255, 0.25);
    border-radius: 6px;
    background-color: rgba(255, 255, 255, 0.1);
    color: white;
    font-size: 0.75rem;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.article-input:focus {
    outline: none;
    border-color: #0073a9;
    background-color: rgba(255, 255, 255, 0.15);
    box-shadow: 0 0 0 2px rgba(0, 115, 169, 0.2);
}

.unit-type {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.6);
    font-weight: 500;
}

.no-articles {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.5);
    font-style: italic;
}

/* Articles Section Styling - Simplified */
.articles-grid {
    margin-top: 0.75rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

/* Stock Realization Button Styling */
.stock-realization-button-container {
    display: flex;
    justify-content: flex-end;
    margin-top: 1rem;
    padding-top: 0.75rem;
    border-top: 1px solid rgba(255, 255, 255, 0.12);
}

.stock-realization-button {
    display: flex;
    align-items: center;
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    backdrop-filter: blur(10px);
}

.stock-realization-button:hover:not(:disabled) {
    background: linear-gradient(135deg, #059669, #047857);
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
}

.stock-realization-button:active:not(:disabled) {
    transform: translateY(0);
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
}

.stock-realization-button:disabled {
    background: linear-gradient(135deg, #6b7280, #4b5563);
    cursor: not-allowed;
    transform: none;
    box-shadow: 0 2px 8px rgba(107, 114, 128, 0.2);
}

.stock-realization-button i {
    font-size: 0.875rem;
}

.job-articles {
    background-color: rgba(255, 255, 255, 0.04);
    border-radius: 4px;
    padding: 0.5rem;
    border: 1px solid rgba(255, 255, 255, 0.08);
}

.job-articles-header {
    margin-bottom: 0.5rem;
}

.job-articles-header .job-name {
    color: white;
    font-size: 0.875rem;
    font-weight: 600;
    margin: 0;
}

.articles-summary {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.7);
}

/* Improved Layout and Spacing - Minimal Padding */
.info {
    min-width: 0;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 0.25rem 0.5rem;
}

.info-label {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.7);
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.25rem;
}

.order-link {
    color: #0073a9;
    cursor: pointer;
    transition: color 0.2s ease;
    text-decoration: underline;
    text-decoration-color: transparent;
    transition: all 0.2s ease;
}

.row-columns {
    gap: 1.5rem;
    align-items: center;
    background-color: rgba(255, 255, 255, 0.02);
    padding: 0.5rem 0;
}

/* Column Widths - More Spacious */
.col-stock-id {
    width: 120px;
    flex-shrink: 0;
}

.col-order-connection {
    width: 350px;
    flex-shrink: 0;
}

.col-client {
    width: 300px;
    flex-shrink: 0;
}

.col-status {
    width: 140px;
    flex-shrink: 0;
}

.col-realized-by {
    width: 180px;
    flex-shrink: 0;
}

.col-expand {
    width: 160px;
    flex-shrink: 0;
    margin-left: auto;
}

/* PDF Button Styling */
.pdf-button {
    transition: all 0.3s ease;
}

.pdf-button:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.pdf-button:active:not(:disabled) {
    transform: translateY(0);
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
}

.pdf-button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.pdf-button i {
    transition: all 0.3s ease;
}

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

.bold {
    font-weight: bold;
}

/* Responsive Design Improvements */
@media (max-width: 1024px) {
    .filter-container {
        gap: 12px;
    }

    .search-input {
        width: 100%;
    }

    .filter-select {
        width: 100%;
    }

    .filters-group {
        flex: 1 1 100%;
    }

    .jobs-table-container {
        font-size: 0.875rem;
    }

    .jobs-table th,
    .jobs-table td {
        padding: 0.5rem;
    }

    .dimensions-inputs {
        flex-direction: column;
        gap: 0.125rem;
    }

    .dimension-input {
        width: 60px;
    }

    .invoice-row {
        margin-bottom: 1rem;
    }

    .row-columns {
        gap: 1rem;
        flex-wrap: wrap;
    }

    .col-order-connection {
        width: 300px;
    }

    .col-client {
        width: 250px;
    }

    .col-status {
        width: 120px;
    }

    .col-realized-by {
        width: 160px;
    }

    .col-expand {
        width: 140px;
    }
}

@media (max-width: 768px) {
    .jobs-table {
        font-size: 0.75rem;
    }

    .jobs-table th,
    .jobs-table td {
        padding: 0.375rem;
    }

    .article-item {
        padding: 0.5rem;
    }

    .row-columns {
        gap: 0.5rem;
        flex-direction: column;
        padding: 0.25rem 0;
    }

    .info {
        padding: 0.125rem 0.25rem;
    }

    .col-stock-id,
    .col-order-connection,
    .col-client,
    .col-status,
    .col-realized-by,
    .col-expand {
        width: 100%;
    }

    .filter-container {
        flex-direction: column;
        gap: 1rem;
    }

    .filters-group {
        flex-direction: column;
        gap: 0.5rem;
    }

    .search-input {
        width: 100%;
    }

    .filter-select {
        width: 100%;
    }
}

@media (max-width: 480px) {
    .jobs-table-container {
        overflow-x: scroll;
    }

    .jobs-table {
        min-width: 600px;
    }

    .edit-input,
    .dimension-input,
    .article-input {
        font-size: 0.875rem;
        padding: 0.75rem;
    }

    .jobs-expanded-section {
        padding: 0.5rem;
    }
}
</style>

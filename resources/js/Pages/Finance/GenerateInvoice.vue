<template>
    <MainLayout>
        <div class="pl-7 pr-7 flex">
            <div class="sidebar" v-if="isSidebarVisible">
                <button @click="toggleSidebar" class="close-sidebar">
                    <span class="mdi mdi-close"></span>
                </button>
            </div>
            <div class="left-column flex-1" style="width: 25%">
                <div class="flex justify-between">
                    <Header title="invoice2" subtitle="invoiceGeneration" icon="invoice.png" link="notInvoiced" />
                    <div class="flex pt-4">
                        <div class="buttons pt-3">
                            <button class="btn blue" @click="openCommentModal">
                                Add Comment <i class="fa-solid fa-comment"></i>
                            </button>
                            <button class="btn comment-order" @click="toggleEditMode">
                                {{ isEditMode ? 'Exit Edit Mode' : 'Enter Edit Mode' }}
                                <i class="fa-regular fa-edit"></i>
                            </button>
                            <button class="btn blue" @click="showTradeItemsModal = true">
                                Add Trade Items <i class="fa-solid fa-plus"></i>
                            </button>
                            <button class="btn orange" @click="previewInvoice">
                                Preview <i class="fa-solid fa-eye"></i>
                            </button>
                            <button class="btn generate-invoice" @click="generateInvoice">Generate Invoice <i
                                    class="fa-solid fa-file-invoice-dollar"></i></button>
                        </div>
                    </div>
                </div>
                <!-- Client Information Row -->
                <div class="client-info-section" v-if="clientName">
                    <div class="client-info">
                        <div class="client-info-container">
                        <div class="client-label">Client</div>
                        <div class="client-name">{{ clientName }}</div>
                        </div>
                        <!-- Pre-invoice meta (ID and Date) -->
                        <div class="invoice-info-minimal">
                            <div class="info-item">
                                <span class="info-label">Invoice ID</span>
                                <span class="info-value">{{ previewInvoiceId }}{{ previewInvoiceId ? '' : '...'
                                    }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Date Created</span>
                                <span class="info-value">
                                    <div v-if="isEditMode && editingDate" class="date-edit-container">
                                        <input v-model="dateEdit" @keyup.enter="commitPreviewDate" class="date-input"
                                            type="date" />
                                        <button @click="commitPreviewDate" class="save-btn" title="Save">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button @click="cancelEditPreviewDate" class="cancel-btn" title="Cancel">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <div v-else class="date-display" @click="startEditPreviewDate">
                                        {{ formattedPreviewDate }}
                                        <i v-if="isEditMode" class="fas fa-edit edit-icon"></i>
                                    </div>
                                </span>
                            </div>
                            <div class="info-item comment" v-if="comment">
                                <span class="info-label">Comment</span>
                                <span class="info-value">{{ comment }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="orders-container" v-for="(inv, index) in invoiceData" :key="index">
                    <div class="order-separator">
                        <div class="separator-line"></div>
                        <span class="separator-text">Order {{ index }}</span>
                        <div class="separator-line"></div>
                    </div>

                    <div class="order-header">
                        <div class="order-details">
                            <div class="detail-item title-item">
                                <span class="label">Title:</span>
                                <div class="title-content">
                                    <div v-if="isEditMode && editingTitle[inv.id]" class="title-edit-container">
                                        <input v-model="titleEdits[inv.id]" @keyup.enter="saveTitle(inv)"
                                            class="title-input" type="text" />
                                        <button @click="saveTitle(inv)" class="save-btn" title="Save">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button @click="cancelEditTitle(inv)" class="cancel-btn" title="Cancel">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <div v-else class="invoice-title" @click="startEditTitle(inv)">
                                        {{ inv.invoice_title }}
                                        <i v-if="isEditMode" class="fas fa-edit edit-icon"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="detail-item">
                                <span class="label">Order:</span>
                                <span>#{{ inv?.id }}</span>
                            </div>
                            <div class="detail-item">
                                <span class="label">{{ $t('startDate') }}:</span>
                                <span>{{ inv?.start_date }}</span>
                            </div>
                            <div class="detail-item">
                                <span class="label">{{ $t('endDate') }}:</span>
                                <span>{{ inv?.end_date }}</span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Created By:</span>
                                <span>{{ inv.user }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="jobs-section">
                        <div class="jobs-detail-mode">
                            <!-- Global labels rendered once per order -->
                            <div class="job-header-labels">
                                <div class="label-cell">Title</div>
                                <div class="label-cell">Quantity</div>
                                <div class="label-cell">Total m²</div>
                                <div class="label-cell">Sale Price</div>
                                <!-- <div class="label-cell">Job Price</div> -->
                                <div class="label-cell action-cell">Actions</div>
                            </div>
                            <div v-for="(job, jobIndex) in inv.jobs" :key="job.id" class="job-card">
                                <div class="job-header">
                                    <div class="value-cell job-title">
                                        <template v-if="isEditMode && editingJobId === job.id">
                                            <input class="inline-input" v-model="job.name" />
                                        </template>
                                        <template v-else>
                                            #{{ jobIndex + 1 }} {{ job.name }}
                                        </template>
                                    </div>
                                    <div class="value-cell">
                                        <template v-if="isEditMode && editingJobId === job.id">
                                            <input class="inline-input" type="number" min="0" v-model.number="job.quantity" />
                                        </template>
                                        <template v-else>{{ job.quantity }}</template>
                                    </div>
                                    <div class="value-cell">
                                        <template v-if="isEditMode && editingJobId === job.id">
                                            <input class="inline-input" type="number" min="0" step="0.0001" v-model.number="job.computed_total_area_m2" />
                                        </template>
                                        <template v-else>{{ formatArea(job.computed_total_area_m2) }}</template>
                                    </div>
                                    <div class="value-cell">
                                        <template v-if="isEditMode && editingJobId === job.id">
                                            <input class="inline-input" type="number" min="0" step="0.01" v-model.number="job.salePrice" />
                                        </template>
                                        <template v-else>{{ formatPrice(job.salePrice) }} ден.</template>
                                    </div>
                                    <!--
                                    <div class="value-cell">
                                        <template v-if="isEditMode && editingJobId === job.id">
                                            <input class="inline-input" type="number" min="0" step="0.01" v-model.number="job.totalPrice" />
                                        </template>
                                        <template v-else>{{ formatPrice(job.totalPrice) }} ден.</template>
                                    </div>
                                    -->
                                    <div class="value-cell actions">
                                        <template v-if="isEditMode">
                                            <template v-if="editingJobId !== job.id">
                                                <button class="btn-edit-small" @click="startEditingJob(job)">Edit</button>
                                            </template>
                                            <template v-else>
                                                <button class="btn-save-small" @click="saveEditingJob(job)">Save</button>
                                                <button class="btn-cancel-small" @click="cancelEditingJob(job)">Cancel</button>
                                            </template>
                                        </template>
                                        <button class="btn-edit-small" style="margin-left:6px" @click="toggleMaterials(job.id)">
                                            {{ materialsOpen[job.id] ? 'Hide Materials' : 'Show Materials' }}
                                        </button>
                                    </div>
                                </div>
                                <!-- Materials (styled like old material-info) -->
                                <div v-if="materialsOpen[job.id]" class="material-info">
                                    <span class="label">{{ $t('material') }}:</span>
                                    <div class="value materials-list">
                                        <template v-if="job.articles && job.articles.length">
                                            <div v-for="(article, aIdx) in job.articles" :key="aIdx" class="material-item">
                                                <span class="item-name">{{ article.name }}</span>
                                                <span class="item-sep"> - </span>
                                                <span class="item-qty">{{ article.pivot?.quantity ?? 0 }} {{ getUnitTextFromPivot(article) }}</span>
                                            </div>
                                        </template>
                                        <template v-else-if="job.large_material">
                                            <div class="material-item">
                                                <span class="item-name">{{ job.large_material.name }}</span>
                                            </div>
                                        </template>
                                        <template v-else-if="job.small_material">
                                            <div class="material-item">
                                                <span class="item-name">{{ job.small_material.name }}</span>
                                            </div>
                                        </template>
                                        <template v-else>
                                            <div class="material-item">No materials.</div>
                                        </template>
                                    </div>
                                </div>
                                <!--
                                <div class="job-details-grid">
                                    <div class="detail-item">
                                        <span class="label">{{ $t('height') }}:</span>
                                        <span class="value">{{ formatDimension(job.height) }} mm</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">{{ $t('width') }}:</span>
                                        <span class="value">{{ formatDimension(job.width) }} mm</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">{{ $t('quantity') }}:</span>
                                        <span class="value">{{ job.quantity }}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">{{ $t('copies') }}:</span>
                                        <span class="value">{{ job.copies }}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">{{ $t('totalm') }}<sup>2</sup>:</span>
                                        <span class="value">{{ formatArea(job.computed_total_area_m2) }}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">{{ $t('salePrice') }}:</span>
                                        <span class="value price">{{ formatPrice(job.salePrice) }} ден.</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">{{ $t('jobPrice') }}:</span>
                                        <span class="value total-price">{{ formatPrice(job.totalPrice) }} ден.</span>
                                    </div>
                                </div>
                                -->
                                <!-- Old materials display removed in favor of the new one above
                                <div class="material-info" v-if="getMaterialInfo(job)">
                                    <span class="label">{{ $t('material') }}:</span>
                                    <span class="value">{{ getMaterialInfo(job) }}</span>
                                </div>
                                -->
                                <!-- <div class="shipping-info" v-if="job.shippingInfo">
                                    <span class="label">{{ $t('shippingTo') }}:</span>
                                    <span class="value">{{ job.shippingInfo }}</span>
                                </div> -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Trade Items Section - redesigned like Invoice.vue and editable pre-generation -->
                <div class="trade-items-section" v-if="isEditMode || (tradeItems && tradeItems.length > 0)">
                    <div class="trade-items-container">
                        <div class="trade-items-header">
                            <h4 class="section-title">Trade Items</h4>
                            <button class="btn btn-add" @click="showTradeItemsModal = true">
                                <i class="fas fa-plus"></i> Add Item
                            </button>
                        </div>

                        <div v-if="tradeItems.length === 0" class="no-items">
                            No trade items added yet.
                        </div>

                        <div class="trade-items-list" v-else>
                            <div class="trade-item-card" v-for="(item, idx) in tradeItems" :key="idx">
                                <div class="item-header">
                                    <div class="item-title">
                                        <span class="article-code" v-if="item.article_code">Article Code: {{
                                            item.article_code }}</span>
                                        <span class="article-name">{{ item.article_name }}</span>
                                    </div>
                                    <div class="item-actions">
                                        <button v-if="!isEditingTrade[idx]" @click="startEditTrade(idx, item)"
                                            class="btn btn-edit-small" title="Edit Item">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button v-else @click="saveEditTrade(idx)" class="btn btn-save-small"
                                            title="Save Changes">
                                            <i class="fas fa-save"></i>
                                        </button>
                                        <button v-if="isEditingTrade[idx]" @click="cancelEditTrade(idx)"
                                            class="btn btn-cancel-small" title="Cancel">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <button @click="removeTradeItem(idx)" class="btn btn-delete"
                                            title="Delete Item">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="item-details">
                                    <div class="detail-item">
                                        <label>Quantity:</label>
                                        <input v-if="isEditingTrade[idx]" v-model.number="editTradeForms[idx].quantity"
                                            type="number" min="1" class="form-input-small" />
                                        <span v-else class="detail-value">{{ item.quantity }}</span>
                                    </div>

                                    <div class="detail-item">
                                        <label>Unit Price:</label>
                                        <input v-if="isEditingTrade[idx]"
                                            v-model.number="editTradeForms[idx].unit_price" type="number" step="0.01"
                                            min="0" class="form-input-small" />
                                        <span v-else class="detail-value">{{ formatNumber(item.unit_price) }}
                                            ден.</span>
                                    </div>

                                    <div class="detail-item">
                                        <label>VAT Rate:</label>
                                        <select v-if="isEditingTrade[idx]" v-model.number="editTradeForms[idx].vat_rate"
                                            class="form-select-small">
                                            <option :value="0">0%</option>
                                            <option :value="5">5%</option>
                                            <option :value="10">10%</option>
                                            <option :value="18">18%</option>
                                        </select>
                                        <span v-else class="detail-value">{{ item.vat_rate }}%</span>
                                    </div>

                                    <div class="detail-item">
                                        <label>Total Price:</label>
                                        <span class="detail-value total-price">{{ formatNumber(getPreGenItemTotal(item,
                                            idx)) }} ден.</span>
                                    </div>

                                    <div class="detail-item">
                                        <label>VAT Amount:</label>
                                        <span class="detail-value">{{ formatNumber(getPreGenItemVat(item, idx)) }}
                                            ден.</span>
                                    </div>

                                    <div class="detail-item">
                                        <label>Total with VAT:</label>
                                        <span class="detail-value total-final">{{
                                            formatNumber(getPreGenItemTotalWithVat(item, idx)) }} ден.</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="trade-items-total" v-if="tradeItems.length > 0">
                            <strong>Trade Items Total: {{ formatNumber(tradeItemsTotal) }} ден (incl. VAT)</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trade Items Modal -->
        <div v-if="showTradeItemsModal" class="modal-overlay" @click="closeModal">
            <div class="modal-content" @click.stop>
                <div class="modal-header">
                    <h3>Add Trade Items</h3>
                    <button @click="closeModal" class="close-btn">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Select Article:</label>
                        <select v-model="selectedArticle" class="form-control">
                            <option value="">Choose an article...</option>
                            <option v-for="article in availableTradeArticles" :key="article.id" :value="article">
                                {{ article.article.name }} ({{ article.article.code }}) - Stock: {{ article.quantity }}
                                - Price: {{ formatNumber(article.selling_price || 0) }} ден
                            </option>
                        </select>
                    </div>
                    <div class="form-group" v-if="selectedArticle">
                        <label>Quantity:</label>
                        <input type="number" v-model.number="tradeItemQuantity" :max="selectedArticle.quantity" min="1"
                            class="form-control">
                        <small class="form-help-text">Available: {{ selectedArticle.quantity }}</small>
                    </div>
                    <div class="form-group" v-if="selectedArticle">
                        <label>Unit Price:</label>
                        <input type="number" v-model.number="tradeItemPrice" step="0.01" min="0" class="form-control">
                    </div>
                    <div class="form-group" v-if="selectedArticle && tradeItemQuantity && tradeItemPrice">
                        <div class="price-breakdown">
                            <div>Subtotal: {{ formatNumber(tradeItemQuantity * tradeItemPrice) }} ден</div>
                            <div>VAT (18%): {{ formatNumber((tradeItemQuantity * tradeItemPrice) * 0.18) }} ден</div>
                            <div><strong>Total: {{ formatNumber((tradeItemQuantity * tradeItemPrice) * 1.18) }}
                                    ден</strong></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button @click="addTradeItem" :disabled="!canAddTradeItem" class="btn green">Add Item</button>
                    <button @click="closeModal" class="btn delete">Cancel</button>
                </div>
            </div>
        </div>

        <!-- Comment Modal -->
        <div v-if="showCommentModal" class="modal-overlay" @click="closeCommentModal">
            <div class="modal-content" @click.stop>
                <div class="modal-header">
                    <h3>Add Invoice Comment</h3>
                    <button @click="closeCommentModal" class="close-btn">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Comment:</label>
                        <textarea v-model="tempComment" class="form-control" rows="5"
                            placeholder="Enter a comment for the invoice..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button @click="saveComment" class="btn green">Save</button>
                    <button @click="closeCommentModal" class="btn delete">Cancel</button>
                </div>
            </div>
        </div>

        <!-- Preview Modal -->
        <div v-if="showPreviewModalFlag" class="modal-overlay" @click="closePreviewModal">
            <div class="preview-modal-content" @click.stop>
                <div class="modal-header">
                    <h3>Invoice Preview</h3>
                    <button @click="closePreviewModal" class="close-btn">&times;</button>
                </div>
                <div class="preview-modal-body">
                    <iframe :src="previewPdfUrl" width="100%" height="600px" frameborder="0" title="Invoice Preview">
                    </iframe>
                </div>
                <div class="modal-footer">
                    <button @click="closePreviewModal" class="btn delete">Close Preview</button>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script>
import MainLayout from "@/Layouts/MainLayout.vue";
import axios from "axios";
import Toast, { useToast } from "vue-toastification";
import OrderJobDetails from "@/Pages/Invoice/OrderJobDetails.vue";
import OrderSpreadsheet from "@/Components/OrderSpreadsheet.vue";
import Header from "@/Components/Header.vue";
import InvoiceJobEdit from "@/Components/InvoiceJobEdit.vue";

export default {
    components: {
        OrderJobDetails,
        MainLayout,
        Header,
        InvoiceJobEdit
    },
    props: {
        invoiceData: Object,
    },
    computed: {
        getStatusColorClass() {
            const invoiceStatus = this.invoiceData.status;
            if (invoiceStatus === "Not started yet") {
                return "orange-text";
            } else if (invoiceStatus === "In progress") {
                return "blue-text";
            } else if (invoiceStatus === "Completed") {
                return "green-text";
            }
        },
        tradeItemsTotal() {
            return this.tradeItems.reduce((total, item) => total + (item.total_price + item.vat_amount), 0);
        },
        canAddTradeItem() {
            return this.selectedArticle &&
                this.tradeItemQuantity > 0 &&
                this.tradeItemQuantity <= this.selectedArticle.quantity &&
                this.tradeItemPrice > 0;
        },
        clientName() {
            const values = Object.values(this.invoiceData || {});
            return values.length ? values[0].client : '';
        },
        formattedPreviewDate() {
            try {
                const d = new Date(this.previewDate);
                if (isNaN(d.getTime())) return this.previewDate || '';
                return d.toLocaleDateString('en-US', { year: 'numeric', month: '2-digit', day: '2-digit' });
            } catch (_) {
                return this.previewDate || '';
            }
        }
    },
    data() {
        return {
            showImagePopover: false,
            selectedJob: null,
            isSidebarVisible: false,
            spreadsheetMode: false,
            isEditMode: false,
            editingTitle: {},
            titleEdits: {},
            backgroundColor: null,
            openDialog: false,
            comment: '',
            previewInvoiceId: null,
            previewDate: new Date().toISOString().split('T')[0],
            editingDate: false,
            dateEdit: '',
            showTradeItemsModal: false,
            showCommentModal: false,
            tempComment: '',
            availableTradeArticles: [],
            selectedArticle: null,
            tradeItemQuantity: 1,
            tradeItemPrice: 0,
            tradeItems: [],
            isEditingTrade: {},
            editTradeForms: {},
            showPreviewModalFlag: false,
                previewPdfUrl: null,
                editingJobId: null,
                originalJobSnapshots: {},
                materialsOpen: {}
        }
    },
    methods: {
        startEditingJob(job) {
            this.editingJobId = job.id;
            // snapshot original values for cancel
            this.$set ? this.$set(this.originalJobSnapshots, job.id, {
                name: job.name,
                quantity: job.quantity,
                computed_total_area_m2: job.computed_total_area_m2,
                salePrice: job.salePrice,
                totalPrice: job.totalPrice
            }) : (this.originalJobSnapshots[job.id] = {
                name: job.name,
                quantity: job.quantity,
                computed_total_area_m2: job.computed_total_area_m2,
                salePrice: job.salePrice,
                totalPrice: job.totalPrice
            });
        },
        cancelEditingJob(job) {
            const snap = this.originalJobSnapshots[job.id];
            if (snap) {
                job.name = snap.name;
                job.quantity = snap.quantity;
                job.computed_total_area_m2 = snap.computed_total_area_m2;
                job.salePrice = snap.salePrice;
                job.totalPrice = snap.totalPrice;
            }
            this.editingJobId = null;
            delete this.originalJobSnapshots[job.id];
        },
        async saveEditingJob(job) {
            try {
                // Persist supported fields to backend JobController@update
                const response = await axios.put(`/jobs/${job.id}`, {
                    name: job.name,
                    quantity: job.quantity,
                    salePrice: job.salePrice
                });
                if (response?.data?.job) {
                    // Replace local job with server copy and notify
                    Object.assign(job, response.data.job);
                    this.onJobUpdated?.(response.data.job);
                    this.$toast?.success?.('Job updated');
                } else {
                    this.$toast?.success?.('Job updated');
                }
                this.editingJobId = null;
                delete this.originalJobSnapshots[job.id];
            } catch (e) {
                this.$toast?.error?.('Failed to update job');
            }
        },
        toggleMaterials(jobId) {
            const current = !!this.materialsOpen[jobId];
            this.$set ? this.$set(this.materialsOpen, jobId, !current) : (this.materialsOpen[jobId] = !current);
        },
        async fetchNextInvoiceId() {
            try {
                const res = await axios.get('/invoices/next-id');
                this.previewInvoiceId = res?.data?.next_id ?? null;
            } catch (_) {
                this.previewInvoiceId = null;
            }
        },
        openCommentModal() {
            this.tempComment = this.comment || '';
            this.showCommentModal = true;
        },
        saveComment() {
            this.comment = (this.tempComment || '').trim();
            this.showCommentModal = false;
        },
        closeCommentModal() {
            this.showCommentModal = false;
        },
        startEditPreviewDate() {
            if (!this.isEditMode) return;
            this.editingDate = true;
            this.dateEdit = this.previewDate;
            this.$nextTick(() => {
                const input = document.querySelector('.date-input');
                if (input) input.focus();
            });
        },
        commitPreviewDate() {
            if (!this.dateEdit) {
                this.cancelEditPreviewDate();
                return;
            }
            this.previewDate = this.dateEdit;
            this.cancelEditPreviewDate();
        },
        cancelEditPreviewDate() {
            this.editingDate = false;
            this.dateEdit = '';
        },
        toggleEditMode() {
            this.isEditMode = !this.isEditMode;
            if (!this.isEditMode) {
                this.editingTitle = {};
                this.titleEdits = {};
            }
        },
        startEditTitle(invoiceData) {
            if (!this.isEditMode) return;
            this.editingTitle[invoiceData.id] = true;
            this.titleEdits[invoiceData.id] = invoiceData.invoice_title;
            this.$nextTick(() => {
                const input = document.querySelector('.title-input');
                if (input) {
                    input.focus();
                    input.select();
                }
            });
        },
        async saveTitle(invoiceData) {
            if (!this.titleEdits[invoiceData.id] || this.titleEdits[invoiceData.id] === invoiceData.invoice_title) {
                this.cancelEditTitle(invoiceData);
                return;
            }
            try {
                const response = await axios.put(`/orders/${invoiceData.id}/title`, { invoice_title: this.titleEdits[invoiceData.id] });
                if (response.data.invoice) {
                    invoiceData.invoice_title = this.titleEdits[invoiceData.id];
                    this.$toast?.success?.('Title updated');
                }
                this.cancelEditTitle(invoiceData);
            } catch (e) {
                console.error(e);
                this.$toast?.error?.('Failed to update title');
                this.cancelEditTitle(invoiceData);
            }
        },
        cancelEditTitle(invoiceData) {
            this.editingTitle[invoiceData.id] = false;
            delete this.titleEdits[invoiceData.id];
        },
        toggleSidebar() {
            this.isSidebarVisible = !this.isSidebarVisible;
        },
        toggleSpreadsheetMode() {
            // removed spreadsheet view; keep no-op for compatibility
        },
        onJobUpdated(updatedJob) {
            Object.values(this.invoiceData).forEach(inv => {
                const idx = inv.jobs.findIndex(j => j.id === updatedJob.id);
                if (idx !== -1) inv.jobs[idx] = updatedJob;
            });
        },
        formatPrice(price) {
            if (!price && price !== 0) return '0.00';
            return typeof price === 'number' ? price.toFixed(2) : '0.00';
        },
        formatDimension(dimension) {
            if (!dimension && dimension !== 0) return '0.00';
            return typeof dimension === 'number' ? dimension.toFixed(2) : '0.00';
        },
        formatArea(area) {
            if (!area && area !== 0) return '0.0000';
            return typeof area === 'number' ? area.toFixed(4) : '0.0000';
        },
        getMaterialInfo(job) {
            if (job.articles && job.articles.length > 0) {
                return job.articles.map(article => {
                    const unit = this.getUnitText(article);
                    return `${article.name} (${article.pivot.quantity} ${unit})`;
                }).join(', ');
            } else if (job.large_material) {
                return job.large_material.name;
            } else if (job.small_material) {
                return job.small_material.name;
            }
            return null;
        },
        generatePdf(invoiceId) {
            window.open(`/orders/${invoiceId}/pdf`, '_blank');
        },
        async loadTradeArticles() {
            try {
                const response = await axios.get('/trade-articles');
                this.availableTradeArticles = response.data.data || [];
            } catch (error) {
                console.error('Error loading trade articles:', error);
            }
        },
        closeModal() {
            this.showTradeItemsModal = false;
            this.selectedArticle = null;
            this.tradeItemQuantity = 1;
            this.tradeItemPrice = 0;
        },
        addTradeItem() {
            if (!this.canAddTradeItem) return;

            const subtotal = this.tradeItemQuantity * this.tradeItemPrice;
            const vatAmount = subtotal * 0.18;

            this.tradeItems.push({
                article_id: this.selectedArticle.article.id,
                article_name: this.selectedArticle.article.name,
                article_code: this.selectedArticle.article.code,
                quantity: this.tradeItemQuantity,
                unit_price: this.tradeItemPrice,
                total_price: subtotal,
                vat_rate: 18.00,
                vat_amount: vatAmount
            });

            this.closeModal();
        },
        startEditTrade(index, item) {
            this.$set ? this.$set(this.isEditingTrade, index, true) : (this.isEditingTrade[index] = true);
            this.$set ? this.$set(this.editTradeForms, index, {
                quantity: Number(item.quantity || 0),
                unit_price: Number(item.unit_price || 0),
                vat_rate: Number(item.vat_rate || 0)
            }) : (this.editTradeForms[index] = {
                quantity: Number(item.quantity || 0),
                unit_price: Number(item.unit_price || 0),
                vat_rate: Number(item.vat_rate || 0)
            });
        },
        cancelEditTrade(index) {
            this.isEditingTrade[index] = false;
            delete this.editTradeForms[index];
        },
        saveEditTrade(index) {
            const form = this.editTradeForms[index];
            if (!form) return;
            const item = this.tradeItems[index];
            item.quantity = form.quantity;
            item.unit_price = form.unit_price;
            item.vat_rate = form.vat_rate;
            const subtotal = Number(item.quantity || 0) * Number(item.unit_price || 0);
            const vatAmount = subtotal * (Number(item.vat_rate || 0) / 100);
            item.total_price = subtotal;
            item.vat_amount = vatAmount;
            this.isEditingTrade[index] = false;
            delete this.editTradeForms[index];
        },
        getPreGenItemTotal(item, index) {
            const form = this.editTradeForms[index];
            const quantity = form ? Number(form.quantity || 0) : Number(item.quantity || 0);
            const unit = form ? Number(form.unit_price || 0) : Number(item.unit_price || 0);
            return quantity * unit;
        },
        getPreGenItemVat(item, index) {
            const form = this.editTradeForms[index];
            const vat = form ? Number(form.vat_rate || 0) : Number(item.vat_rate || 0);
            const total = this.getPreGenItemTotal(item, index);
            return total * (vat / 100);
        },
        getPreGenItemTotalWithVat(item, index) {
            const total = this.getPreGenItemTotal(item, index);
            const vat = this.getPreGenItemVat(item, index);
            return total + vat;
        },
        removeTradeItem(index) {
            this.tradeItems.splice(index, 1);
        },
        formatNumber(number) {
            return Number(number).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        getUnitText(article) {
            const a = article && article.article ? article.article : article;
            const isTrue = (v) => v === true || v === 1 || v === '1' || v === 'true';
            // Flag-based fields (coerce null/"1"/1/true)
            if (a && isTrue(a.in_square_meters)) return 'm²';
            if (a && isTrue(a.in_pieces)) return 'ком.'; // pcs
            if (a && isTrue(a.in_kilograms)) return 'кг';
            if (a && isTrue(a.in_meters)) return 'м';
            // String-based unit fallback (e.g., 'pcs', 'kg', 'm', 'm2')
            const u = (a && a.unit ? String(a.unit).toLowerCase() : '').trim();
            if (u === 'pcs' || u === 'kom' || u === 'ком' || u === 'pieces') return 'ком.';
            if (u === 'kg') return 'кг';
            if (u === 'm') return 'м';
            if (u === 'm2' || u === 'm²' || u === 'sqm' || u === 'm^2') return 'm²';
            return 'кол.';
        },
        getUnitTextFromPivot(article) {
            // Prefer pivot-provided unit if available in future (e.g., pivot.unit_type)
            const unitType = (article && article.pivot && article.pivot.unit_type) ? String(article.pivot.unit_type).toLowerCase() : '';
            if (unitType) {
                if (unitType === 'pieces' || unitType === 'pcs' || unitType === 'kom' || unitType === 'ком') return 'ком.';
                if (unitType === 'kilograms' || unitType === 'kg') return 'кг';
                if (unitType === 'meters' || unitType === 'm') return 'м';
                if (unitType === 'square_meters' || unitType === 'm2' || unitType === 'm²') return 'm²';
            }
            // Fallback to article flags/string
            return this.getUnitText(article);
        },
        buildTradeItemsPayload() {
            return (this.tradeItems || []).map((item, idx) => {
                const form = this.editTradeForms[idx];
                const quantity = form ? Number(form.quantity || item.quantity || 0) : Number(item.quantity || 0);
                const unitPrice = form ? Number(form.unit_price || item.unit_price || 0) : Number(item.unit_price || 0);
                const vatRate = form ? Number(form.vat_rate || item.vat_rate || 0) : Number(item.vat_rate || 0);
                const totalPrice = quantity * unitPrice;
                const vatAmount = totalPrice * (vatRate / 100);
                return {
                    article_id: item.article_id ?? null,
                    article_name: item.article_name ?? '',
                    article_code: item.article_code ?? '',
                    quantity: quantity,
                    unit_price: unitPrice,
                    total_price: totalPrice,
                    vat_rate: vatRate,
                    vat_amount: vatAmount,
                };
            });
        },
        async previewInvoice() {
            const toast = useToast();
            try {
                const orderIds = Object.values(this.invoiceData).map(order => order.id);
                const tradePayload = this.buildTradeItemsPayload();
                const response = await axios.post('/preview-invoice', {
                    orders: orderIds,
                    comment: this.comment,
                    trade_items: tradePayload,
                    created_at: this.previewDate
                }, {
                    responseType: 'blob' // Important for PDF response
                });

                // Create blob URL for iframe display
                const blob = new Blob([response.data], { type: 'application/pdf' });
                const url = window.URL.createObjectURL(blob);

                // Show preview in modal with iframe
                this.showPreviewModal(url);

            } catch (error) {
                let serverMessage = 'Internal Server Error';
                try {
                    if (error?.response?.data instanceof Blob) {
                        const text = await error.response.data.text();
                        try {
                            const parsed = JSON.parse(text);
                            serverMessage = parsed.error || parsed.message || text;
                        } catch (_) {
                            serverMessage = text;
                        }
                    } else if (error?.response?.data) {
                        const data = error.response.data;
                        serverMessage = data.error || data.message || JSON.stringify(data);
                    }
                } catch (_) { }
                console.error('Error generating preview:', serverMessage, error);
                toast.error(`Error generating preview: ${serverMessage}`);
            }
        },
        showPreviewModal(pdfUrl) {
            this.previewPdfUrl = pdfUrl;
            this.showPreviewModalFlag = true;
        },
        closePreviewModal() {
            this.showPreviewModalFlag = false;
            if (this.previewPdfUrl) {
                window.URL.revokeObjectURL(this.previewPdfUrl);
                this.previewPdfUrl = null;
            }
        },
        async generateInvoice() {
            const toast = useToast();
            try {
                const orderIds = Object.values(this.invoiceData).map(order => order.id);
                const tradePayload = this.buildTradeItemsPayload();
                // Request metadata so we can redirect; then separately open the PDF in new tab
                const response = await axios.post('/generate-invoice', {
                    orders: orderIds,
                    comment: this.comment,
                    trade_items: tradePayload,
                    created_at: this.previewDate,
                    return_meta: true
                }, { responseType: 'json' });

                if (response?.data?.success && response?.data?.faktura_id) {
                    // Open PDF if provided
                    if (response.data.pdf) {
                        const binary = atob(response.data.pdf);
                        const array = new Uint8Array(binary.length);
                        for (let i = 0; i < binary.length; i++) array[i] = binary.charCodeAt(i);
                        const blob = new Blob([array], { type: 'application/pdf' });
                        const url = window.URL.createObjectURL(blob);
                        window.open(url, '_blank');
                    }
                    // Redirect to the new invoice view page
                    this.$inertia.visit(`/invoice/${response.data.faktura_id}`);
                    toast.success('Invoice generated successfully');
                    return;
                }

                // Fallback: if server returns a blob (older path), open and keep current page
                if (response && response.data) {
                    try {
                        const blob = new Blob([response.data], { type: 'application/pdf' });
                        const url = window.URL.createObjectURL(blob);
                        window.open(url, '_blank');
                        toast.success('Invoice generated successfully');
                    } catch (_) { }
                }
            } catch (error) {
                console.error('Error generating invoice:', error);
                toast.error('Error generating invoice!');
            }
        }
    },
    mounted() {
        this.loadTradeArticles();
        this.fetchNextInvoiceId();

        // Watch for changes in selected article to auto-fill price
        this.$watch('selectedArticle', (newArticle) => {
            if (newArticle && newArticle.selling_price) {
                this.tradeItemPrice = newArticle.selling_price;
            }
        });
    }
};
</script>

<style scoped lang="scss">
// Import color variables
$background-color: #1a2732;
$gray: #3c4e59;
$dark-gray: #2a3946;
$light-gray: #54606b;
$ultra-light-gray: #77808b;
$white: #ffffff;
$black: #000000;
$green: #408a0b;
$light-green: #81c950;
$blue: #0073a9;
$red: #9e2c30;
$orange: #a36a03;

/* Client info styles */
.client-info-section {
    background: linear-gradient(135deg, #51A8B1 0%, darken(#51A8B1, 20%) 100%);
    border-radius: 8px 8px 0 0;
    padding: 5px 8px;
    margin: 10px 0 0 0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.client-info {
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.client-label {
    font-size: 14px;
    color: rgba($white, 0.8);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.client-name {
    font-size: 24px;
    color: $white;
    font-weight: 700;
    text-align: center;
}

.circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
}

.flexed {
    justify-content: center;
    align-items: center;
}

.popover-content[data-v-19f5b08d] {
    background-color: #2d3748;
}

.fa-close::before {
    color: white;
}

[type='checkbox']:checked {
    border: 1px solid white;
}

.orange-text {
    color: $orange;
}

.blue-text {
    color: #1ba5e4;
}

.bold {
    font-weight: bold;
}

.green-text {
    color: $green;
}

.light-gray {
    background-color: $light-gray;
}

.ultra-light-gray {
    background-color: $ultra-light-gray;
}

.blue {
    background-color: $blue;
}

.green {
    background-color: $green;
}

.background {
    background-color: $background-color;
}

.header {
    display: flex;
    align-items: center;
}

.dark-gray {
    background-color: $dark-gray;
    justify-content: left;
    align-items: center;
    min-height: 20vh;
    min-width: 80vh;
}

.sub-title {
    font-size: 20px;
    font-weight: bold;
    display: flex;
    align-items: center;
    color: $white;
}

.jobShippingInfo {
    max-width: 300px;
    border: 1px solid;
}

.jobPriceInfo {
    max-height: 40px;
    max-width: 30%;
}

.right {
    gap: 34.9rem;
}

.btn {
    margin-right: 4px;
    padding: 10px 15px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    color: white;
    border-radius: 2px;
}

.btn2 {
    font-size: 14px;
    margin-right: 4px;
    padding: 7px 10px;
    border: none;
    cursor: pointer;
    color: white;
    background-color: $blue;
    border-radius: 2px;
}

.btns {
    position: absolute;
    top: -11px;
    right: 0;
    padding: 0;
}

.comment-order {
    background-color: $blue;
    color: white;
}

.generate-invoice {
    background-color: $green;
    color: white;
}

.InvoiceDetails {
    border-bottom: 2px dashed lightgray;
}

.bt {
    font-size: 45px;
    cursor: pointer;
    padding: 0;
}

.popover {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    /* high z-index to be on top of other content */
}

.popover-content {
    width: 30%;
    background: white;
    padding: 20px;
    border-radius: 8px;
    position: relative;
}

.popover-close {
    position: absolute;
    top: 10px;
    right: 10px;
    color: black;
}

.right-column {
    background-color: $background-color;
    color: white;
    overflow-y: auto;
}

.hamburger {
    z-index: 2000;
    background-color: transparent;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #fff;
    /* Adjust the color to match your layout */
}

.sidebar {
    position: fixed;
    top: 0;
    right: 0;
    bottom: 0;
    width: 350px;
    /* Width of sidebar */
    background-color: $background-color;
    /* Sidebar background color */
    z-index: 1000;
    /* Should be below the overlay */
    overflow-y: auto;
    padding: 20px;
    border: 1px solid $white;
    border-right: none;
    border-radius: 4px 0 0 4px;
}

.order-history {
    padding: 20px;
}

.close-sidebar {
    position: absolute;
    top: 10px;
    right: 10px;
    background: transparent;
    border: none;
    font-size: 24px;
    color: #fff;
    /* Adjust close button color */
    cursor: pointer;
}

.is-blurred {
    filter: blur(5px);
}

.content {
    transition: filter 0.3s;
    /* Smooth transition for the blur effect */
}

.history-subtitle {
    background-color: white;
    color: black;
    padding: 10px;
    margin-bottom: 10px;
    font-weight: bold;
}

.jobImg {
    width: 45px;
    height: 45px;
}

/*
spreadheet style
*/
table {
    width: 100%;
    border-collapse: collapse;
    border-spacing: 0;
    margin-bottom: 20px;
    border: 1px solid #ddd;
    border-radius: 5px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

table th,
table td {
    padding: 10px;
    border: 1px solid #ddd;
    text-align: center;
}

table th {
    color: white;
    border-top: 1px solid #ddd;
    border-bottom: 1px solid #ddd;
    background-color: $ultra-light-gray;
}

/* Trade Items Styles */
.trade-items-table {
    width: 100%;
    margin-top: 10px;
    border-collapse: collapse;
}

.trade-items-table th,
.trade-items-table td {
    padding: 8px;
    border: 1px solid #ddd;
    text-align: center;
}

.trade-items-table th {
    background-color: $ultra-light-gray;
    color: white;
}

.trade-items-total {
    text-align: right;
    margin-top: 10px;
    padding: 10px;
    background-color: $dark-gray;
    border-radius: 4px;
}

/* Modal Styles */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.modal-content {
    background-color: $dark-gray;
    border: 1px solid $ultra-light-gray;
    border-radius: 8px;
    width: 90%;
    max-width: 600px;
    max-height: 80vh;
    overflow-y: auto;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid $ultra-light-gray;
    background-color: $dark-gray;
}

.modal-header h3 {
    color: $white;
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
}

.close-btn {
    background: none;
    border: none;
    color: $white;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0.25rem;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    transition: background-color 0.2s;
}

.close-btn:hover {
    background-color: $ultra-light-gray;
}

.modal-body {
    padding: 1.5rem;
    color: $white;
    background-color: $dark-gray;
}

.modal-footer {
    padding: 1rem 1.5rem;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    border-top: 1px solid $ultra-light-gray;
    background-color: $dark-gray;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    color: $white;
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 0.375rem;
}

.form-control {
    width: 100%;
    padding: 0.5rem 0.75rem;
    border: 1px solid $ultra-light-gray;
    border-radius: 4px;
    background-color: $light-gray;
    color: $white;
    font-size: 0.875rem;
    transition: border-color 0.15s, box-shadow 0.15s;
}

.form-control:focus {
    outline: none;
    border-color: $blue;
    box-shadow: 0 0 0 2px rgba($blue, 0.2);
}

.form-control::placeholder {
    color: $ultra-light-gray;
}

.form-control option {
    background-color: $light-gray;
    color: $white;
}

.form-help-text {
    display: block;
    color: $ultra-light-gray;
    font-size: 0.75rem;
    margin-top: 0.25rem;
    font-style: italic;
}

.price-breakdown {
    background-color: $light-gray;
    padding: 12px;
    border-radius: 4px;
    border: 1px solid $ultra-light-gray;
    margin-top: 8px;
}

.price-breakdown div {
    margin-bottom: 4px;
    color: $white;
}

.price-breakdown div:last-child {
    margin-bottom: 0;
    font-weight: bold;
    color: $white;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    font-weight: bold;
    cursor: pointer;
    transition: background-color 0.2s;
    font-size: 0.875rem;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn.green {
    background-color: $green;
    color: white;
}

.btn.green:hover:not(:disabled) {
    background-color: darken($green, 10%);
}

.btn.delete {
    background-color: $red;
    color: white;
}

.btn.delete:hover:not(:disabled) {
    background-color: darken($red, 10%);
}

.btn.orange {
    background-color: $orange;
    color: white;
}

.btn.orange:hover:not(:disabled) {
    background-color: darken($orange, 10%);
}

/* Modern Invoice Styles ported from Finance/Invoice.vue */
.orders-container {
    background: rgba($white, 0.2);
    border-radius: 0 0 3px 3px;
    padding: 5px 8px;
    margin-bottom: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    border: 1px solid $light-gray;
}

.order-separator {
    display: flex;
    align-items: center;
    margin: 10px 0;

    .separator-line {
        flex: 1;
        height: 1px;
        background: $white;
    }

    .separator-text {
        padding: 0 20px;
        color: $white;
        font-weight: bold;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
}

.order-header {
    background: $white;
    border-radius: 3px;
    padding: 5px;
    border: 1px solid rgba($light-gray, 0.3);
}

.order-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 4px;

    .label {
        font-size: 12px;
        color: $ultra-light-gray;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .value {
        font-size: 14px;
        color: $white;
        font-weight: bold;

        &.price {
            color: $blue;
            font-weight: bold;
        }

        &.total-price {
            color: greenyellow;
            font-weight: bold;
            font-size: 16px;
        }
    }
}

.title-item {
    .title-content {
        .invoice-title {
            font-size: 16px;
            font-weight: bold;
            color: $black;
            cursor: pointer;
            padding: 0;
            border-radius: 3px;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;

            .edit-icon {
                font-size: 14px;
                opacity: 0.6;
            }
        }

        .title-edit-container {
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            max-width: 350px;
        }

        .title-input {
            font-size: 16px;
            font-weight: bold;
            color: $black;
            border: 2px solid $blue;
            border-radius: 3px;
            padding: 4px 8px;
            background: $white;
            flex: 1;

            &:focus {
                outline: none;
                border-color: $light-green;
                box-shadow: 0 0 0 2px rgba($light-green, 0.3);
            }
        }

        .save-btn,
        .cancel-btn {
            padding: 6px 8px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 28px;
            height: 28px;

            &:hover {
                transform: scale(1.05);
            }
        }

        .save-btn {
            background-color: $green;
            color: $white;
        }

        .cancel-btn {
            background-color: $red;
            color: $white;
        }
    }
}

.jobs-section {
    background: rgba($light-gray, 0.02);
    border-radius: 3px;
    margin-top: 10px;
    border: 1px solid rgba($light-gray, 0.2);
}

.job-card {
    background: $dark-gray;
    border: 1px solid $light-gray;
    border-radius: 3px;
    padding: 5px 8px;
    transition: all 0.2s ease;
    margin-bottom: 3px;

    &:hover {
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        border-color: $ultra-light-gray;
    }
}

.job-header-labels {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr 1fr;
    gap: 8px;
    padding: 5px 10px;
    border-top: 1px solid $ultra-light-gray;
}

.job-header-labels.with-actions {
    /* Removed Job Price column, keep actions as last */
    grid-template-columns: 2fr 1fr 1fr 1fr 1fr;
}

.job-header-labels .label-cell {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.9);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}

.job-header {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr 1fr;
    align-items: center;
    padding: 10px;
    background-color: #7DC068;
    border-radius: 4px;
    gap: 8px;
}

.job-header.with-actions {
    /* Removed Job Price column, keep actions as last */
    grid-template-columns: 2fr 1fr 1fr 1fr 1fr;
}

.job-header .value-cell {
    color: #111827;
    font-weight: 700;
    font-size: 14px;
}

.inline-input {
    width: 100%;
    padding: 6px 8px;
    border: 1px solid rgba(0, 0, 0, 0.15);
    border-radius: 4px;
    background-color: #ffffff;
    color: #111827;
    font-weight: 600;
}

.btn-toggle-materials {
    background-color: #374151;
    color: #fff;
    border: none;
    padding: 6px 8px;
    border-radius: 4px;
    font-size: 12px;
    margin-left: 6px;
}

.materials-accordion {
    background: rgba(255, 255, 255, 0.85);
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    margin-top: 8px;
}

.materials-accordion .accordion-header {
    padding: 8px 12px;
    font-weight: 700;
    color: #111827;
    border-bottom: 1px solid #e5e7eb;
}

.materials-accordion .accordion-body {
    padding: 8px 12px;
}

.materials-accordion .accordion-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px 0;
    border-bottom: 1px dashed #e5e7eb;
}

.materials-accordion .accordion-item:last-child {
    border-bottom: none;
}

.materials-accordion .item-name {
    color: #1f2937;
    font-weight: 600;
}

.materials-accordion .item-qty {
    color: #374151;
    font-weight: 600;
}

/* New materials list styled to match .material-info */
.materials-list {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.materials-list .material-item {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
}

.materials-list .item-name,
.materials-list .item-qty {
    color: $white;
}

.materials-list .item-sep {
    color: rgba($white, 0.75);
    margin: 0 6px;
}

.job-title {
    font-size: 16px;
    font-weight: 600;
    color: $black;
}

.job-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 12px;
}

.material-info,
.shipping-info {
    grid-column: 1 / -1;
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding: 12px;
    background: rgba(white, 0.15);
    border-radius: 6px;
    margin-top: 12px;

    span {
        color: white;
    }
}

/* Trade items cards (pre-generation) */
.trade-items-section {
    background: $light-gray;
    border-radius: 3px;
    padding: 24px;
    margin: 20px 0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    border: 1px solid $light-gray;
}

.trade-items-container {
    color: $white;
}

.trade-items-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    padding-bottom: 10px;
    border-bottom: 2px solid rgba($white, 0.15);
}

.trade-items-header .section-title {
    font-size: 18px;
    font-weight: 600;
    margin: 0;
}

.btn-add {
    background-color: $green;
    color: $white;
}

.btn-add:hover {
    background-color: darken($green, 10%);
}

.trade-items-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.trade-item-card {
    background: rgba(white, 0.12);
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 16px;
}

.item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.item-title {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.item-title .article-code {
    font-size: 12px;
    color: $white;
    opacity: 0.9;
}

.item-title .article-name {
    font-size: 16px;
    font-weight: 600;
    color: $white;
}

.item-actions {
    display: flex;
    gap: 6px;
}

.btn-edit-small,
.btn-save-small,
.btn-cancel-small,
.btn-delete {
    padding: 4px 8px;
    font-size: 12px;
    min-width: 32px;
}

.btn-edit-small {
    background-color: #3182ce;
    color: white;
}

.btn-save-small {
    background-color: #38a169;
    color: white;
}

.btn-cancel-small,
.btn-delete {
    background-color: #e53e3e;
    color: white;
}

.item-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 12px;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.detail-item label {
    font-weight: 600;
    color: $white;
    font-size: 13px;
}

.detail-item .detail-value {
    color: $white;
    font-size: 14px;
}

.detail-item .total-price {
    color: $blue;
    font-weight: 600;
}

.detail-item .total-final {
    color: greenyellow;
    font-weight: 600;
}

.form-input-small,
.form-select-small {
    padding: 6px 8px;
    border: 1px solid $light-gray;
    border-radius: 3px;
    background: $white;
    color: $black;
}

.no-items {
    text-align: center;
    color: rgba($white, 0.8);
    padding: 8px 0;
}

/* Minimal Invoice Information Section (reused design) */
.invoice-info-minimal {
    background: rgba($white, 0.25);
    padding: 16px 24px;
    margin-bottom: 0;
    border: 1px solid rgba($white, 0.1);
    border-top: none;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: flex-start;
    gap: 20px;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 8px;
}

.info-label {
    font-size: 12px;
    color: rgba($white, 0.6);
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
}

.info-value {
    font-size: 14px;
    color: $white;
    font-weight: 600;
}

.date-display {
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 4px 8px;
    border-radius: 4px;
}

.date-display:hover {
    background: rgba($white, 0.1);
}

.date-input {
    font-size: 14px;
    color: $white;
    background: rgba($white, 0.1);
    border: 2px solid $blue;
    border-radius: 4px;
    padding: 6px 8px;
    font-weight: 600;
}

.save-btn,
.cancel-btn {
    padding: 6px 8px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 28px;
    height: 28px;
}

.save-btn {
    background-color: $green;
    color: $white;
}

.cancel-btn {
    background-color: $red;
    color: $white;
}

/* Preview Modal Styles */
.preview-modal-content {
    background-color: $dark-gray;
    border: 1px solid $ultra-light-gray;
    border-radius: 8px;
    width: 95%;
    max-width: 1200px;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
}

.preview-modal-body {
    padding: 0;
    background-color: $dark-gray;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.preview-modal-body iframe {
    border: none;
    border-radius: 4px;
    background-color: white;
}
</style>

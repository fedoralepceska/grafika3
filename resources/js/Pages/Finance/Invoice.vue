<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <div class="sidebar" v-if="isSidebarVisible">
                <button @click="toggleSidebar" class="close-sidebar">
                    <span class="mdi mdi-close"></span>
                </button>
            </div>
            <div class="invoice-container">
                <div class="invoice-header">
                    <Header title="invoice2" subtitle="invoiceEdit" icon="invoice.png" link="allInvoices"/>
                    <div class="invoice-actions">
                        <div class="flex flex-col pt-4">
                            <div class="buttons pt-3 gap-2 flex">
                                <button class="btn blue" @click="openCommentModal">
                                    Add Comment <i class="fa-solid fa-comment"></i>
                                </button>
                                <button class="btn generate-invoice" @click="printInvoice">
                                    Print Invoice <i class="fa-solid fa-file-invoice-dollar"></i>
                                </button>
                            </div>
                        <div class="buttons flex justify-end pt-3 gap-2">
                            <button v-if="isEditMode" class="btn btn3" @click="createOrAddToMergeGroup()">
                                Merge
                            </button>
                            <button v-if="isEditMode" class="btn btn4" @click="unmergeSelected()">
                                Unmerge 
                            </button>
                            <button v-if="isEditMode && hasUnsavedMergeChanges" class="btn generate-invoice" @click="saveMergeChanges()">
                                Save Changes
                            </button>
                        </div>
                        </div>
                    </div>
                </div>

                <!-- Client Information Row -->
                <div class="client-info-section" v-if="invoice[0]?.client">
                    <div class="client-info">
                        <div class="client-info-container">
                            <div class="client-label">Client</div>
                            <div class="client-name">{{ invoice[0]?.client }}</div>
                        </div>
                        <!-- Invoice meta (ID, Date, Created by) -->
                        <div class="invoice-info-minimal">
                            <div class="info-item">
                                <span class="info-label">Invoice ID</span>
                                <span class="info-value">{{ invoice[0]?.fakturaId }}/{{new Date(invoice[0]?.created).toLocaleDateString('en-US', { year: 'numeric'})}}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Date Created</span>
                                <span class="info-value">
                                    <div v-if="isEditMode && editingDate" class="date-edit-container">
                                        <input v-model="dateEdit" @keyup.enter="saveDate" class="date-input"
                                            type="date" />
                                        <button @click="saveDate" class="save-btn" title="Save">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button @click="cancelEditDate" class="cancel-btn" title="Cancel">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <div v-else class="date-display" @click="startEditDate">
                                        {{new Date(invoice[0]?.created).toLocaleDateString('en-US', { year: 'numeric', month: '2-digit', day: '2-digit' })}}
                                        <i v-if="isEditMode" class="fas fa-edit edit-icon"></i>
                                    </div>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Created by</span>
                                <span class="info-value">{{invoice[0]?.createdBy}}</span>
                            </div>
                            <div class="info-item comment" v-if="invoice[0]?.faktura_comment">
                                <span class="info-label">Comment</span>
                                <span class="info-value">{{invoice[0]?.faktura_comment}}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- All Orders Container -->
                <div class="orders-container">
                    <div class="section-header">
                        <h3 class="section-title">Orders & Jobs</h3>
                    </div>
                    
                    <div v-for="(invoiceData, index) in invoice" :key="index" class="order-section">
                        <!-- Order Separator (except for first order) -->
                        <div  class="order-separator">
                            <div class="separator-line"></div>
                            <span class="separator-text">Order {{ index + 1 }}</span>
                            <div class="separator-line"></div>
                        </div>

                        <!-- Order Header -->
                        <div class="order-header">
                            <div class="order-details">
                                <div class="detail-item title-item">
                                    <span class="label">Title:</span>
                                    <div class="title-content">
                                        <div v-if="isEditMode && editingTitle[invoiceData.id]" class="title-edit-container">
                                            <input 
                                                v-model="titleEdits[invoiceData.id]"
                                                @keyup.enter="saveTitle(invoiceData)"
                                                class="title-input"
                                                type="text"
                                            />
                                            <button @click="saveTitle(invoiceData)" class="save-btn" title="Save">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button @click="cancelEditTitle(invoiceData)" class="cancel-btn" title="Cancel">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                        <div v-else class="invoice-title" @click="startEditTitle(invoiceData)">
                                          {{ invoiceData.invoice_title }}
                                            <i v-if="isEditMode" class="fas fa-edit edit-icon"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <span class="label">Order:</span>
                                    <span>#{{ invoiceData?.id }}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="label">{{ $t('startDate') }}:</span>
                                    <span>{{ invoiceData?.start_date }}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="label">{{ $t('endDate') }}:</span>
                                    <span>{{ invoiceData?.end_date }}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="label">Created By:</span>
                                    <span>{{ invoiceData.user }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Jobs for this Order -->
                        <div class="jobs-section">
                            <div class="jobs-detail-mode">
                                <!-- Global labels rendered once per order -->
                                <div class="job-header-labels">
                                    <div class="label-cell">Title</div>
                                    <div class="label-cell">Quantity</div>
                                    <div class="label-cell">Total m²</div>
                                    <div class="label-cell">Sale Price</div>
                                    <div class="label-cell action-cell">Actions</div>
                                </div>
                                <!-- Always render non-group jobs as cards -->
                                <template v-for="(job, jobIndex) in invoiceData.jobs" :key="'flat-'+job.id">
                                    <div v-if="getMergeGroupIndexByJobId(job.id) === -1" class="job-card">
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
                                                <button class="merge-toggle ml-2" :class="{selected: isSelected(job.id)}" @click="toggleMergeSelection(job.id)" title="Toggle merge">
                                                    <i class="fa-solid fa-code-merge"></i>
                                                    Merge
                                                </button>
                                            </div>
                                        </div>
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
                                    </div>
                                </template>
                                <!-- Render merged containers per order -->
                                <div v-for="grp in getGroupsForOrder(invoiceData.id)" :key="'grp-'+grp.__idx" class="job-card merged-container" :class="'merged-color-' + (grp.__idx % 6)">
                                    <div class="job-header">
                                        <template v-if="isEditMode && isGroupEditableInOrder(grp.__idx, invoiceData.id) && editingMergedGroupId === grp.__idx">
                                            <input class="inline-input" placeholder="Group title" v-model="grp.title" />
                                            <input type="number" min="0" step="0.01" class="inline-input" placeholder="Quantity" v-model.number="grp.quantity" />
                                            <div class="value-cell"></div>
                                            <input type="number" min="0" step="0.01" class="inline-input" placeholder="Sale Price" v-model.number="grp.sale_price" />
                                            <div class="value-cell actions">
                                                <button class="btn-save-small" @click="saveEditingMergedGroup(grp.__idx)">Save</button>
                                                <button class="btn-cancel-small" @click="cancelEditingMergedGroup(grp.__idx)">Cancel</button>
                                            </div>
                                        </template>
                                        <template v-else>
                                            <div class="value-cell">{{ grp.title || 'Merged group' }}</div>
                                            <div class="value-cell">{{ formatNumber(grp.quantity || 0) }}</div>
                                            <div class="value-cell"></div>
                                            <div class="value-cell">{{ formatPrice(grp.sale_price || 0) }} ден.</div>
                                            <div class="value-cell actions">
                                                <template v-if="isEditMode && isGroupEditableInOrder(grp.__idx, invoiceData.id)">
                                                    <template v-if="editingMergedGroupId !== grp.__idx">
                                                        <button class="btn-edit-small" @click="startEditingMergedGroup(grp.__idx)">Edit</button>
                                                    </template>
                                                    <template v-else>
                                                        <button class="btn-save-small" @click="saveEditingMergedGroup(grp.__idx)">Save</button>
                                                        <button class="btn-cancel-small" @click="cancelEditingMergedGroup(grp.__idx)">Cancel</button>
                                                    </template>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                    <div class="merged-body">
                                        <div v-for="gid in jobsInGroupForOrder(invoiceData.id, grp.__idx)" :key="'gid-'+gid" class="job-card inside-merged">
                                            <div class="job-header">
                                                <div class="value-cell job-title">{{ displayJobName(gid) }}</div>
                                                <div class="value-cell">{{ displayJobQty(gid) }}</div>
                                                <div class="value-cell">{{ displayJobArea(gid) }}</div>
                                                <div class="value-cell">{{ displayJobPrice(gid) }} ден.</div>
                                                <div class="value-cell actions">
                                                    <button class="merge-toggle ml-2" :class="{selected: isSelected(gid)}" @click="toggleMergeSelection(gid)" title="Toggle merge">
                                                        <i class="fa-solid fa-code-merge"></i>
                                                        Merge
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Trade Items Section - At the Bottom -->
                <div class="trade-items-section" v-if="isEditMode || (tradeItems && tradeItems.length > 0)">
                    <div class="trade-items-container">
                        <div class="trade-items-header">
                            <h4 class="section-title">Trade Items</h4>
                        </div>

                        <div v-if="tradeItems.length === 0" class="no-items">
                            No trade items added yet.
                        </div>

                        <div class="trade-items-list" v-else>
                            <div class="trade-item-card" v-for="(item, idx) in tradeItems" :key="idx">
                                <div class="item-header">
                                    <div class="item-title">
                                        <span class="article-code" v-if="item.article_code">Article Code: {{ item.article_code }}</span>
                                        <span class="article-name">{{ item.article_name }}</span>
                                    </div>
                                </div>

                                <div class="item-details">
                                    <div class="detail-item">
                                        <label>Quantity:</label>
                                        <span class="detail-value">{{ item.quantity }}</span>
                                    </div>

                                    <div class="detail-item">
                                        <label>Unit Price:</label>
                                        <span class="detail-value">{{ formatNumber(item.unit_price) }} ден.</span>
                                    </div>

                                    <div class="detail-item">
                                        <label>VAT Rate:</label>
                                        <span class="detail-value">{{ item.vat_rate }}%</span>
                                    </div>

                                    <div class="detail-item">
                                        <label>Total Price:</label>
                                        <span class="detail-value total-price">{{ formatNumber(item.total_price) }} ден.</span>
                                    </div>

                                    <div class="detail-item">
                                        <label>VAT Amount:</label>
                                        <span class="detail-value">{{ formatNumber(item.vat_amount) }} ден.</span>
                                    </div>

                                    <div class="detail-item">
                                        <label>Total with VAT:</label>
                                        <span class="detail-value total-final">{{ formatNumber(item.total_price + item.vat_amount) }} ден.</span>
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
    </MainLayout>
</template>

<script>
import MainLayout from "@/Layouts/MainLayout.vue";
import axios from "axios";
import Toast, {useToast} from "vue-toastification";
import OrderJobDetails from "@/Pages/Invoice/OrderJobDetails.vue";
import OrderSpreadsheet from "@/Components/OrderSpreadsheet.vue";
import Header from "@/Components/Header.vue";
import InvoiceJobEdit from "@/Components/InvoiceJobEdit.vue";
import TradeItemsEdit from "@/Components/TradeItemsEdit.vue";

export default {
    components: {
        OrderSpreadsheet,
        OrderJobDetails,
        MainLayout,
        Header,
        InvoiceJobEdit,
        TradeItemsEdit
    },
    props: {
        invoice: Object,
        faktura: Object,
        tradeItems: Array,
    },
    computed:{
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
    },
    data() {
        return {
            showImagePopover: false,
            selectedJob: null,
            isSidebarVisible: false,
            spreadsheetMode: false,
            isEditMode: true,
            backgroundColor: null,
            openDialog: false,
            editingTitle: {},
            titleEdits: {},
            editingDate: false,
            dateEdit: '',
            toast: useToast(),
            editingJobId: null,
            originalJobSnapshots: {},
            materialsOpen: {},
            selectedJobIds: [],
            showCommentModal: false,
            tempComment: '',
            editingMergedGroupId: null,
            originalMergedGroupSnapshots: {},
            hasUnsavedMergeChanges: false,
            originalMergeGroups: []
        }
    },
    mounted() {
        // Component mounted
        // Initialize original merge groups for change tracking
        this.originalMergeGroups = JSON.parse(JSON.stringify(this.faktura.merge_groups || []));
    },
    methods: {
        getAllJobsFlat() {
            return (this.invoice || []).flatMap(o => o.jobs || []);
        },
        getJobById(jobId) {
            for (const inv of (this.invoice || [])) {
                const j = (inv.jobs || []).find(x => x.id === jobId);
                if (j) return j;
            }
            return null;
        },
        displayJobName(id) { const j = this.getJobById(id); return j ? j.name : ''; },
        displayJobQty(id) { const j = this.getJobById(id); return j ? j.quantity : ''; },
        displayJobArea(id) { const j = this.getJobById(id); return this.formatArea(j ? j.computed_total_area_m2 : 0); },
        displayJobPrice(id) { const j = this.getJobById(id); return this.formatPrice(j ? j.salePrice : 0); },
        getMergeGroupIndexByJobId(jobId) {
            const groups = (this.faktura && this.faktura.merge_groups) ? this.faktura.merge_groups : [];
            for (let i = 0; i < groups.length; i++) {
                const ids = Array.isArray(groups[i].job_ids) ? groups[i].job_ids : [];
                if (ids.includes(jobId)) return i;
            }
            return -1;
        },
        getGroupsForOrder(orderId) {
            const res = [];
            const groups = (this.faktura && this.faktura.merge_groups) ? this.faktura.merge_groups : [];
            (groups || []).forEach((g, idx) => {
                const ids = Array.isArray(g.job_ids) ? g.job_ids : [];
                const hasJobInOrder = (this.invoice || []).some(o => o.id === orderId && (o.jobs || []).some(j => ids.includes(j.id)));
                if (hasJobInOrder) {
                    // Add __idx property to the original object instead of creating a copy
                    g.__idx = idx;
                    res.push(g);
                }
            });
            return res;
        },
        jobsInGroupForOrder(orderId, groupIdx) {
            const groups = (this.faktura && this.faktura.merge_groups) ? this.faktura.merge_groups : [];
            const g = groups[groupIdx] || {}; const ids = Array.isArray(g.job_ids) ? g.job_ids : [];
            const order = (this.invoice || []).find(o => o.id === orderId) || { jobs: [] };
            const orderJobs = order.jobs || [];
            return ids.filter(id => orderJobs.some(j => j.id === id));
        },
        isGroupEditableInOrder(groupIdx, orderId) {
            const groups = (this.faktura && this.faktura.merge_groups) ? this.faktura.merge_groups : [];
            const g = groups[groupIdx] || {}; const ids = Array.isArray(g.job_ids) ? g.job_ids : [];
            if (ids.length === 0) return false;
            // Editable only in the order that is the first vertically (lowest id) among orders that contain jobs from this group
            const involvedOrderIds = (this.invoice || [])
                .filter(o => (o.jobs || []).some(j => ids.includes(j.id)))
                .map(o => o.id)
                .sort((a,b) => a - b);
            const firstOwnerId = involvedOrderIds[0];
            return firstOwnerId === orderId;
        },
        isSelected(id) {
            return (this.selectedJobIds || []).includes(id);
        },
        toggleMergeSelection(id) {
            const set = new Set(this.selectedJobIds || []);
            if (set.has(id)) set.delete(id); else set.add(id);
            this.selectedJobIds = Array.from(set);
        },
        createOrAddToMergeGroup() {
            const ids = Array.from(new Set(this.selectedJobIds));
            if (ids.length < 2) return;
            // no validation rules: allow merging any selected jobs
            const allJobs = this.getAllJobsFlat();
            const picked = ids.map(id => allJobs.find(j => j.id === id)).filter(Boolean);
            if (picked.length < 2) return;
            // If any existing group contains any of these ids, expand that group; else create new
            let group = this.faktura.merge_groups.find(g => g.job_ids.some(id => ids.includes(id)));
            if (!group) {
                group = { job_ids: [] };
                this.faktura.merge_groups.push(group);
            }
            group.job_ids = Array.from(new Set([...group.job_ids, ...ids]));
            // Attempt autofill when name and price are uniform
            this.autofillGroupFieldsIfUniform(this.faktura.merge_groups.indexOf(group));
            this.selectedJobIds = [];
            this.hasUnsavedMergeChanges = true;
            this.toast?.success?.('Jobs merged - click Save Changes to persist');
        },
        unmergeSelected() {
            if (!this.selectedJobIds.length) return;
            const set = new Set(this.selectedJobIds);
            this.faktura.merge_groups = this.faktura.merge_groups
                .map(g => ({...g, job_ids: g.job_ids.filter(id => !set.has(id))}))
                .filter(g => g.job_ids.length > 1);
            // Re-evaluate autofill for all groups after removal
            this.faktura.merge_groups.forEach((_, idx) => this.autofillGroupFieldsIfUniform(idx));
            this.selectedJobIds = [];
            this.hasUnsavedMergeChanges = true;
            this.toast?.success?.('Jobs unmerged - click Save Changes to persist');
        },
        autofillGroupFieldsIfUniform(groupIdx) {
            if (groupIdx == null || groupIdx < 0) return;
            const group = this.faktura.merge_groups[groupIdx];
            if (!group || !Array.isArray(group.job_ids) || group.job_ids.length < 1) return;
            const allJobs = this.getAllJobsFlat();
            const jobs = group.job_ids
                .map(id => allJobs.find(j => j && j.id === id))
                .filter(Boolean);
            if (jobs.length === 0) return;
            const baseName = jobs[0]?.name ?? null;
            const basePrice = Number(jobs[0]?.salePrice ?? NaN);
            const same = jobs.every(j => (j?.name ?? null) === baseName && Number(j?.salePrice ?? NaN) === basePrice);
            if (!same) return; // do not autofill when not uniform
            // Sum quantities
            const totalQty = jobs.reduce((acc, j) => acc + Number(j?.quantity || 0), 0);
            // Only set fields if not already explicitly set (or set to empty)
            if (!group.title) this.$set ? this.$set(group, 'title', baseName) : (group.title = baseName);
            if (!(group.sale_price > 0)) this.$set ? this.$set(group, 'sale_price', basePrice) : (group.sale_price = basePrice);
            if (!(group.quantity > 0)) this.$set ? this.$set(group, 'quantity', totalQty) : (group.quantity = totalQty);
        },
        mergedJobs(jobs) {
            try {
                // If faktura has merge_groups, apply them to display
                const groups = (this.faktura && this.faktura.merge_groups) ? this.faktura.merge_groups : [];
                if (!Array.isArray(groups) || groups.length === 0) return jobs;
                const byId = new Map((jobs || []).map(j => [j.id, {...j}]));
                // Remove all in groups and add merged representative
                for (const g of groups) {
                    const ids = Array.isArray(g.job_ids) ? g.job_ids : [];
                    const present = ids.filter(id => byId.has(id));
                    if (present.length < 2) continue;
                    const first = byId.get(present[0]);
                    // Validate same name/price
                    if (!present.every(id => {
                        const jj = byId.get(id);
                        return jj && jj.name === first.name && Number(jj.salePrice||0) === Number(first.salePrice||0);
                    })) continue;
                    let qty = 0; let area = 0;
                    for (const id of present) { const jj = byId.get(id); qty += Number(jj.quantity||0); area += Number(jj.computed_total_area_m2||0); byId.delete(id); }
                    const merged = {...first, quantity: qty, computed_total_area_m2: area, merged: true, merged_job_ids: present};
                    // Use synthetic key to avoid collisions; attaching back using first id is fine
                    byId.set(first.id, merged);
                }
                return Array.from(byId.values());
            } catch (_) { return jobs; }
        },
        toggleSidebar() {
            this.isSidebarVisible = !this.isSidebarVisible;
        },
        toggleSpreadsheetMode(){
            this.spreadsheetMode = !this.spreadsheetMode;
        },
        toggleEditMode() {},
        generatePdf(invoiceId) {
            window.open(`/orders/${invoiceId}/pdf`, '_blank');
        },
        getStatusClass(status) {
            if (status === "Not started yet") {
                return "status-orange";
            } else if (status === "In progress") {
                return "status-blue";
            } else if (status === "Completed") {
                return "status-green";
            }
            return "";
        },
        formatPrice(price) {
            if (!price && price !== 0) return '0.00';
            return typeof price === 'number' ? price.toFixed(2) : '0.00';
        },
        
        getTradeItemUnitPrice(item) {
            return Number(item.unit_price || 0);
        },
        
        getTradeItemTotalWithVat(item) {
            // Calculate total price from unit_price and quantity
            const totalPrice = Number(item.quantity || 0) * Number(item.unit_price || 0);
            
            // Calculate VAT amount
            const vatAmount = totalPrice * (Number(item.vat_rate || 0) / 100);
            
            // Return total with VAT
            return totalPrice + vatAmount;
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
                    const unit = article.in_square_meters ? 'm²' : 
                               article.in_pieces ? 'ком.' : 
                               article.in_kilograms ? 'кг' : 
                               article.in_meters ? 'м' : 'ед.';
                    return `${article.name} (${article.pivot.quantity} ${unit})`;
                }).join(', ');
            } else if (job.large_material) {
                return job.large_material.name;
            } else if (job.small_material) {
                return job.small_material.name;
            }
            return null;
        },
        startEditTitle(invoiceData) {
            if (!this.isEditMode) return;
            
            this.editingTitle[invoiceData.id] = true;
            this.titleEdits[invoiceData.id] = invoiceData.invoice_title;
            
            // Focus the input in the next tick
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
                const response = await axios.put(
                    `/invoice/${invoiceData.fakturaId}/invoice/${invoiceData.id}/title`,
                    { invoice_title: this.titleEdits[invoiceData.id] }
                );

                if (response.data.invoice) {
                    invoiceData.invoice_title = this.titleEdits[invoiceData.id];
                    this.toast.success('Invoice title updated successfully');
                }

                this.cancelEditTitle(invoiceData);

            } catch (error) {
                console.error('Error updating title:', error);
                this.toast.error('Failed to update invoice title');
                this.cancelEditTitle(invoiceData);
            }
        },
        cancelEditTitle(invoiceData) {
            this.editingTitle[invoiceData.id] = false;
            delete this.titleEdits[invoiceData.id];
        },
        startEditDate() {
            if (!this.isEditMode) return;
            
            this.editingDate = true;
            // Convert the current date to YYYY-MM-DD format for the date input
            const currentDate = new Date(this.invoice[0]?.created);
            this.dateEdit = currentDate.toISOString().split('T')[0];
            
            // Focus the input in the next tick
            this.$nextTick(() => {
                const input = document.querySelector('.date-input');
                if (input) {
                    input.focus();
                }
            });
        },
        async saveDate() {
            if (!this.dateEdit) {
                this.cancelEditDate();
                return;
            }

            try {
                const response = await axios.put(
                    `/invoice/${this.invoice[0].fakturaId}/date`,
                    { created: this.dateEdit }
                );

                if (response.data.success) {
                    // Update the invoice data
                    this.invoice[0].created = this.dateEdit;
                    this.toast.success('Invoice date updated successfully');
                }

                this.cancelEditDate();

            } catch (error) {
                console.error('Error updating date:', error);
                this.toast.error('Failed to update invoice date');
                this.cancelEditDate();
            }
        },
        cancelEditDate() {
            this.editingDate = false;
            this.dateEdit = '';
        },
        onJobUpdated(updatedJob) {
            // Find and update the job in the invoice data
            this.invoice.forEach(invoiceData => {
                const jobIndex = invoiceData.jobs.findIndex(job => job.id === updatedJob.id);
                if (jobIndex !== -1) {
                    invoiceData.jobs[jobIndex] = updatedJob;
                }
            });
        },
        onTradeItemsUpdated(updatedTradeItems) {
            // Update trade items - they belong to the faktura, not individual invoices
            this.tradeItems = updatedTradeItems;
        },
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
                    this.toast?.success?.('Job updated');
                } else {
                    this.toast?.success?.('Job updated');
                }
                this.editingJobId = null;
                delete this.originalJobSnapshots[job.id];
            } catch (e) {
                this.toast?.error?.('Failed to update job');
            }
        },
        toggleMaterials(jobId) {
            const current = !!this.materialsOpen[jobId];
            this.$set ? this.$set(this.materialsOpen, jobId, !current) : (this.materialsOpen[jobId] = !current);
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
        formatNumber(number) {
            return Number(number).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        buildTradeItemsPayload() {
            return (this.tradeItems || []).map((item, idx) => {
                const quantity = Number(item.quantity || 0);
                const unitPrice = Number(item.unit_price || 0);
                const vatRate = Number(item.vat_rate || 0);
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
        openCommentModal() {
            this.tempComment = this.invoice[0]?.faktura_comment || '';
            this.showCommentModal = true;
        },
        closeCommentModal() {
            this.showCommentModal = false;
        },
        async saveComment() {
            try {
                const response = await axios.put(`/invoice/${this.invoice[0].fakturaId}/update-comment`, {
                    comment: this.tempComment
                });
                this.invoice[0].faktura_comment = this.tempComment;
                this.showCommentModal = false;
                this.toast.success('Comment updated successfully');
            } catch (error) {
                console.error('Error updating comment:', error);
                this.toast.error('Failed to update comment');
            }
        },
        startEditingMergedGroup(groupIdx) {
            this.editingMergedGroupId = groupIdx;
            const group = this.faktura.merge_groups[groupIdx];
            // snapshot original values for cancel
            this.$set ? this.$set(this.originalMergedGroupSnapshots, groupIdx, {
                title: group.title,
                quantity: group.quantity,
                sale_price: group.sale_price
            }) : (this.originalMergedGroupSnapshots[groupIdx] = {
                title: group.title,
                quantity: group.quantity,
                sale_price: group.sale_price
            });
        },
        cancelEditingMergedGroup(groupIdx) {
            const snap = this.originalMergedGroupSnapshots[groupIdx];
            if (snap) {
                const group = this.faktura.merge_groups[groupIdx];
                group.title = snap.title;
                group.quantity = snap.quantity;
                group.sale_price = snap.sale_price;
            }
            this.editingMergedGroupId = null;
            delete this.originalMergedGroupSnapshots[groupIdx];
        },
        async saveEditingMergedGroup(groupIdx) {
            try {
                const group = this.faktura.merge_groups[groupIdx];
                // Persist merged group changes to backend
                const response = await axios.put(`/invoice/${this.invoice[0].fakturaId}/merge-groups`, {
                    merge_groups: this.faktura.merge_groups
                });
                if (response?.data?.success) {
                    this.toast?.success?.('Merged group updated');
                    // Ensure Vue reactivity by reassigning the array
                    this.faktura.merge_groups = [...this.faktura.merge_groups];
                } else {
                    this.toast?.success?.('Merged group updated');
                    this.faktura.merge_groups = [...this.faktura.merge_groups];
                }
                this.editingMergedGroupId = null;
                delete this.originalMergedGroupSnapshots[groupIdx];
            } catch (e) {
                this.toast?.error?.('Failed to update merged group');
            }
        },
        async saveMergeChanges() {
            try {
                const response = await axios.put(`/invoice/${this.invoice[0].fakturaId}/merge-groups`, {
                    merge_groups: this.faktura.merge_groups
                });
                if (response?.data?.success) {
                    this.toast?.success?.('Merge changes saved');
                    this.hasUnsavedMergeChanges = false;
                    this.originalMergeGroups = JSON.parse(JSON.stringify(this.faktura.merge_groups));
                } else {
                    this.toast?.success?.('Merge changes saved');
                    this.hasUnsavedMergeChanges = false;
                    this.originalMergeGroups = JSON.parse(JSON.stringify(this.faktura.merge_groups));
                }
            } catch (e) {
                this.toast?.error?.('Failed to save merge changes');
            }
        },
        async printInvoice() {
            const toast = useToast();
            try {
                // Use the same logic as GenerateInvoice.vue
                const orderIds = this.invoice.map(order => order.id);
                const tradePayload = this.buildTradeItemsPayload();
                
                // Request metadata so we can redirect; then separately open the PDF in new tab
                const response = await axios.post('/generate-invoice', {
                    orders: orderIds,
                    comment: this.invoice[0]?.faktura_comment || '',
                    trade_items: tradePayload,
                    created_at: new Date(this.invoice[0]?.created).toISOString().split('T')[0],
                    merge_groups: this.faktura.merge_groups || [],
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
        },
    },
};
</script>

<style scoped lang="scss">
// Color Variables from existing design
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

.merged-color-0 { background-color: #3182ce !important; }
.merged-color-1 { background-color: #51A8B1 !important; }
.merged-color-2 { background-color: #a36a03 !important; }
.merged-color-3 { background-color: #d1e93b !important; }
.merged-color-4 { background-color: #9e2c30 !important; }
.merged-color-5 { background-color: #81c950 !important; }


// Merge icon toggle
.merge-toggle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: fit-content;
    padding: 6px 8px;
    height: 30px;
    border: none;
    background: rgba(128, 0, 128, 0.6);
    color: #fff;
    cursor: pointer;
    border-radius: 4px;
}
.merge-toggle.selected {
    color: #fff;
    background: rgba(128, 0, 128, 1);
    border-radius: 4px;
}
.merge-toggle:hover {
    color: #fff;
    background: rgba(128, 0, 128, 0.4);
    border-radius: 4px;
}

.merged-container {
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 6px;
    margin-bottom: 8px;
}
.merged-container .merged-header {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr 1fr; /* align with job header: Title, Qty, Total m², Sale, Actions */
    gap: 8px;
    padding: 8px;
}
.merged-container .merged-body {
    padding: 6px 8px 10px 8px;
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

.btn {
    padding: 10px 16px;
    border: none;
    border-radius: 4px;
    font-weight: bold;
    cursor: pointer;
    transition: background-color 0.2s;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
}

.btn:hover {
    opacity: 0.9;
}

.btn i {
    font-size: 14px;
}

.btn.blue {
    background-color: $blue;
    color: $white;
}

.btn.blue:hover:not(:disabled) {
    background-color: darken($blue, 10%);
}

.btn.generate-invoice {
    background-color: $green;
    color: $white;
}

.btn.generate-invoice:hover:not(:disabled) {
    background-color: darken($green, 10%);
}

.btn3 {
    background-color: #800080;
    color: white;
}

.btn4 {
    background-color: #800020;
    color: white;
}

.invoice-actions {
    display: flex;
    gap: 22px;
    align-items: center;

    @media (max-width: 768px) {
        justify-content: center;
        flex-wrap: wrap;
    }
}
.invoice-container {
    margin: 0 auto;
}

.invoice-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    @media (max-width: 768px) {
        flex-direction: column;
        gap: 20px;
        align-items: stretch;
    }
}

.invoice-actions {
    display: flex;
    gap: 22px;
    align-items: center;

    @media (max-width: 768px) {
        justify-content: center;
        flex-wrap: wrap;
    }
}

.btn {
    padding: 10px 16px;
    border: none;
    border-radius: 2px;
    cursor: pointer;
    font-weight: bold;
    font-size: 14px;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;

    &:hover {
        opacity: 0.9;
    }

    i {
        font-size: 14px;
    }
}

.btn-edit-mode {
    background-color: $orange;
    color: $white;

    &:hover {
        background-color: darken($orange, 10%);
    }
}

.comment-order {
    background-color: $blue;
    color: $white;

    &:hover {
        background-color: darken($blue, 10%);
    }
}

.generate-invoice {
    background-color: $green;
    color: $white;

    &:hover {
        background-color: darken($green, 10%);
    }
}

// Client Information Section
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

.client-info-container {
    display: flex;
    flex-direction: column;
    gap: 4px;
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

// Minimal Invoice Information Section
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

.date-edit-container {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
    max-width: 250px;
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

.date-input:focus {
    outline: none;
    border-color: $light-green;
    box-shadow: 0 0 0 2px rgba($light-green, 0.3);
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

// Orders Container
.orders-container {
    background: rgba($white, 0.2);
    border-radius: 0 0 3px 3px;
    padding: 24px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    border: 1px solid $light-gray;
}

.order-section {
    &:not(:last-child) {
        margin-bottom: 30px;
    }
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

.order-info {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

// Title item specific styles
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
    }
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

        &.status {
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            text-align: center;
            max-width: fit-content;
        }

        &.status-orange {
            background-color: rgba($orange, 0.2);
            color: $orange;
        }

        &.status-blue {
            background-color: rgba($blue, 0.2);
            color: $blue;
        }

        &.status-green {
            background-color: rgba($green, 0.2);
            color: $green;
        }

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

// Jobs Section
.jobs-section {
    background: rgba($light-gray, 0.02);
    border-radius: 3px;
    margin-top: 10px;
    border: 1px solid rgba($light-gray, 0.2);
}

.trade-items-section {
    background: $light-gray;
    border-radius: 3px;
    padding: 24px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    border: 1px solid $light-gray;
}

.section-title {
    color: white;
    font-size: 20px;
    font-weight: bold;
    margin: 0;
}

// Job Cards (Detail View)
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

.btn-edit-small,
.btn-save-small,
.btn-cancel-small {
    padding: 4px 8px;
    font-size: 12px;
    min-width: 32px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 600;
}

.btn-edit-small {
    background-color: #3182ce;
    color: white;
}

.btn-save-small {
    background-color: #38a169;
    color: white;
}

.btn-cancel-small {
    background-color: #e53e3e;
    color: white;
}

.job-title {
    font-size: 16px;
    font-weight: 600;
    color: $black;
}

.job-image {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 6px;
    border: 2px solid #e2e8f0;
}

.job-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 12px;
    
}

.material-info, .shipping-info {
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

// Trade Items Display
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

.no-items {
    text-align: center;
    color: rgba($white, 0.8);
    padding: 8px 0;
}

.trade-items-total {
    text-align: right;
    margin-top: 10px;
    padding: 10px;
    background-color: $dark-gray;
    border-radius: 4px;
}

// Utility Classes
.bold {
    font-weight: bold;
}

.rounded {
    border-radius: 12px 12px 0 0;
}

// Legacy Styles (preserved for compatibility)
.sidebar {
    position: fixed;
    top: 0;
    right: 0;
    bottom: 0;
    width: 350px;
    background-color: #2d3748;
    z-index: 1000;
    overflow-y: auto;
    padding: 20px;
    border: 1px solid #e2e8f0;
    border-right: none;
    border-radius: 4px 0 0 4px;
}

.close-sidebar {
    position: absolute;
    top: 10px;
    right: 10px;
    background: transparent;
    border: none;
    font-size: 24px;
    color: #fff;
    cursor: pointer;
}

// Spreadsheet styles
table {
    width: 100%;
    border-collapse: collapse;
    border-spacing: 0;
    margin-bottom: 20px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

table th, table td {
    padding: 12px;
    border: 1px solid #e2e8f0;
    text-align: center;
    font-size: 14px;
}

table th {
    color: white;
    background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);
    font-weight: 600;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 0.5px;
}

table tr:nth-child(even) {
    background-color: #f8fafc;
}

table tr:hover {
    background-color: #edf2f7;
}

// Responsive Design
@media (max-width: 1200px) {
    .invoice-container {
        padding: 0 16px;
    }
}

@media (max-width: 768px) {
    .order-details {
        grid-template-columns: 1fr;
    }

    .job-details-grid {
        grid-template-columns: 1fr;
    }

    .invoice-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .btn {
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .invoice-container {
        padding: 0 12px;
    }

    .order-header, .jobs-section, .trade-items-section {
        padding: 16px;
    }

    .job-card {
        padding: 16px;
    }
}
</style>

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
                    <div class="flex flex-col">
                        <Header title="invoice2" subtitle="invoiceGeneration" icon="invoice.png" link="notInvoiced" />
                        <div v-if="splitMode" class="split-mode-indicator">
                            <i class="fas fa-cut"></i>
                            Split Mode Active - Create groups and assign jobs to them
                        </div>
                    </div>
                    <div class="flex flex-col pt-4">
                        <div class="buttons pt-3">
                            <button class="btn blue" @click="openCommentModal">
                                Add Comment <i class="fa-solid fa-comment"></i>
                            </button>
                            <button class="btn blue" @click="showTradeItemsModal = true">
                                Add Trade Items <i class="fa-solid fa-plus"></i>
                            </button>
                            <button class="btn orange" @click="previewInvoice" :disabled="!canGenerateOrPreview">
                                {{ splitMode ? 'Preview Split Invoices' : 'Preview' }} <i class="fa-solid fa-eye"></i>
                            </button>
                            <button class="btn generate-invoice" @click="generateInvoice"
                                :disabled="!canGenerateOrPreview">
                                {{ splitMode ? 'Generate Split Invoices' : 'Generate Invoice' }} <i
                                    class="fa-solid fa-file-invoice-dollar"></i></button>
                        </div>
                        <div class="buttons flex justify-end pt-3">
                            <button v-if="isEditMode && !splitMode" class="btn bg-[#800080]" @click="createOrAddToMergeGroup()">
                                Merge
                            </button>
                            <button v-if="isEditMode && !splitMode" class="btn bg-[#800020]" @click="unmergeSelected()">
                                Unmerge
                            </button>

                            <button v-if="isEditMode" class="btn" :class="splitMode ? 'bg-[#dc2626]' : 'bg-[#008080]'" @click="toggleSplitMode()">
                                {{ splitMode ? 'Exit Split Mode' : 'Split Jobs' }}
                            </button>
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
                            <div class="info-item">
                                <span class="info-label">Payment Deadline (days)</span>
                                <span class="info-value">
                                    <div v-if="isEditMode && editingPaymentDeadline" class="date-edit-container">
                                        <input v-model.number="paymentDeadlineEdit" @keyup.enter="commitPaymentDeadline"
                                            class="date-input" type="number" min="0" step="1" />
                                        <button @click="commitPaymentDeadline" class="save-btn" title="Save">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button @click="cancelEditPaymentDeadline" class="cancel-btn" title="Cancel">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <div v-else class="date-display" @click="startEditPaymentDeadline">
                                        {{ paymentDeadline }} days
                                        <i v-if="isEditMode" class="fas fa-edit edit-icon"></i>
                                    </div>
                                </span>
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
                                <div class="label-cell">Unit</div>
                                <div class="label-cell">Total m²</div>
                                <div class="label-cell">Sale Price</div>
                                <!-- <div class="label-cell">Job Price</div> -->
                                <div class="label-cell action-cell">Actions</div>
                            </div>
                            <!-- Always render all jobs as individual cards -->
                            <template v-for="(job, jobIndex) in inv.jobs" :key="'flat-'+job.id">
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
                                                <input class="inline-input" type="number" min="0"
                                                    v-model.number="job.quantity" />
                                            </template>
                                            <template v-else>{{ job.quantity }}</template>
                                        </div>
                                        <div class="value-cell">
                                            <select class="inline-input unit-select" :value="job.unit || 'ком'" @input="updateJobUnit(job, $event.target.value)">
                                                <option value="м">м</option>
                                                <option value="м²">м²</option>
                                                <option value="кг">кг</option>
                                                <option value="ком">ком</option>
                                            </select>
                                        </div>
                                        <div class="value-cell">
                                            <template v-if="isEditMode && editingJobId === job.id">
                                                <input class="inline-input" type="number" min="0" step="0.0001"
                                                    v-model.number="job.computed_total_area_m2" />
                                            </template>
                                            <template v-else>{{ formatArea(job.computed_total_area_m2) }}</template>
                                        </div>
                                        <div class="value-cell">
                                            <template v-if="isEditMode && editingJobId === job.id">
                                                <input class="inline-input" type="number" min="0" step="0.01"
                                                    v-model.number="job.salePrice" />
                                            </template>
                                            <template v-else>{{ formatPrice(job.salePrice) }} ден.</template>
                                        </div>
                                        <div class="value-cell actions">
                                            <div class="actions-row">
                                                <!-- Edit Actions -->
                                                <div class="action-group" v-if="isEditMode">
                                                    <template v-if="editingJobId !== job.id">
                                                        <button class="action-btn edit" @click="startEditingJob(job)" title="Edit Job">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    </template>
                                                    <template v-else>
                                                        <button class="action-btn save" @click="saveEditingJob(job)" title="Save Changes">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button class="action-btn cancel" @click="cancelEditingJob(job)" title="Cancel">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </template>
                                                </div>
                                                
                                                <!-- Materials Toggle -->
                                                <button class="action-btn materials" @click="toggleMaterials(job.id)" 
                                                        :title="materialsOpen[job.id] ? 'Hide Materials' : 'Show Materials'">
                                                    <i :class="materialsOpen[job.id] ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                                                </button>
                                                
                                                <!-- Mode-specific Actions -->
                                                <div class="mode-actions">
                                                    <!-- Merge Mode -->
                                                    <button v-if="!splitMode" class="action-btn merge" 
                                                            :class="{ selected: isSelected(job.id) }"
                                                            @click="toggleMergeSelection(job.id)" 
                                                            title="Toggle merge">
                                                        <i class="fas fa-code-merge"></i>
                                                    </button>
                                                    
                                                    <!-- Split Mode -->
                                                    <div v-if="splitMode" class="split-assignment-compact">
                                                        <div class="custom-split-dropdown" :class="{ open: openDropdowns[job.id] }">
                                                            <button class="split-dropdown-trigger" 
                                                                    @click="toggleDropdown(job.id)"
                                                                    :class="{ assigned: getJobSplitGroup(job.id) !== '' }"
                                                                    title="Assign to split group">
                                                                <span class="dropdown-text">
                                                                    {{ getJobSplitGroup(job.id) !== '' ? 'G' + (parseInt(getJobSplitGroup(job.id)) + 1) : '—' }}
                                                                </span>
                                                                <i class="fas fa-chevron-down dropdown-arrow"></i>
                                                            </button>
                                                            <div class="split-dropdown-menu" v-if="openDropdowns[job.id]">
                                                                <div class="dropdown-option unassign" 
                                                                     @click="assignJobToGroup(job.id, ''); closeDropdown(job.id)"
                                                                     :class="{ active: getJobSplitGroup(job.id) === '' }">
                                                                    <span class="option-badge">—</span>
                                                                    <span class="option-text">Unassigned</span>
                                                                </div>
                                                                <div v-for="(group, index) in splitGroups" :key="index"
                                                                     class="dropdown-option" 
                                                                     @click="assignJobToGroup(job.id, index.toString()); closeDropdown(job.id)"
                                                                     :class="{ active: getJobSplitGroup(job.id) === index.toString() }">
                                                                    <span class="option-badge" :style="{ backgroundColor: getGroupColor(index) }">
                                                                        G{{ index + 1 }}
                                                                    </span>
                                                                    <span class="option-text">{{ group.client }}</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-if="materialsOpen[job.id]" class="material-info">
                                        <span class="label">{{ $t('material') }}:</span>
                                        <div class="value materials-list">
                                            <template v-if="job.articles && job.articles.length">
                                                <div v-for="(article, aIdx) in job.articles" :key="aIdx"
                                                    class="material-item">
                                                    <span class="item-name">{{ article.name }}</span>
                                                    <span class="item-sep"> - </span>
                                                    <span class="item-qty">{{ article.pivot?.quantity ?? 0 }} {{
                                                        getUnitTextFromPivot(article) }}</span>
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
                            <!-- Render merged containers per order for any group that includes jobs from this order -->
                            <div v-for="grp in getGroupsForOrder(inv.id)" :key="'grp-' + grp.__idx"
                                class="merged-container" :class="'merged-color-' + (grp.__idx % mergedPalette.length)">
                                <div class="merged-header">
                                    <template v-if="isEditMode && isGroupEditableInOrder(grp.__idx, inv.id)">
                                        <input class="inline-input"
                                            :class="{ 'invalid-field': !isMergeGroupFieldValid(grp.__idx, 'title') }"
                                            placeholder="Group title" v-model="mergeGroups[grp.__idx].title" />
                                        <input type="number" min="0" step="0.01" class="inline-input"
                                            :class="{ 'invalid-field': !isMergeGroupFieldValid(grp.__idx, 'quantity') }"
                                            placeholder="Quantity" v-model.number="mergeGroups[grp.__idx].quantity" />
                                        <select class="inline-input unit-select" :value="mergeGroups[grp.__idx].unit || 'ком'" @input="updateMergeGroupUnit(grp.__idx, $event.target.value)">
                                            <option value="м">м</option>
                                            <option value="м²">м²</option>
                                            <option value="кг">кг</option>
                                            <option value="ком">ком</option>
                                        </select>
                                        <div class="value-cell"></div>
                                        <input type="number" min="0" step="0.01" class="inline-input"
                                            :class="{ 'invalid-field': !isMergeGroupFieldValid(grp.__idx, 'sale_price') }"
                                            placeholder="Sale Price"
                                            v-model.number="mergeGroups[grp.__idx].sale_price" />
                                        <div class="value-cell"></div>
                                    </template>
                                    <template v-else>
                                        <div class="value-cell">{{ mergeGroups[grp.__idx].title || 'Merged group' }}
                                        </div>
                                        <div class="value-cell">{{ formatNumber(mergeGroups[grp.__idx].quantity || 0) }}
                                        </div>
                                        <div class="value-cell">{{ mergeGroups[grp.__idx].unit || 'ком' }}</div>
                                        <div class="value-cell"></div>
                                        <div class="value-cell">{{ formatPrice(mergeGroups[grp.__idx].sale_price || 0)
                                            }} ден.</div>
                                        <div class="value-cell"></div>
                                    </template>
                                </div>
                                <div class="merged-body">
                                    <div v-for="gid in jobsInGroupForOrder(inv.id, grp.__idx)" :key="'gid-' + gid"
                                        class="job-card inside-merged">
                                        <div class="job-header">
                                            <div class="value-cell job-title">{{ displayJobName(gid) }}</div>
                                            <div class="value-cell">{{ displayJobQty(gid) }}</div>
                                            <div class="value-cell">
                                                <select class="inline-input unit-select" :value="getJobById(gid).unit || 'ком'" @input="updateJobUnit(getJobById(gid), $event.target.value)">
                                                    <option value="м">м</option>
                                                    <option value="м²">м²</option>
                                                    <option value="кг">кг</option>
                                                    <option value="ком">ком</option>
                                                </select>
                                            </div>
                                            <div class="value-cell">{{ displayJobArea(gid) }}</div>
                                            <div class="value-cell">{{ displayJobPrice(gid) }} ден.</div>
                                            <div class="value-cell actions">
                                                <div class="actions-row">
                                                    <div class="mode-actions">
                                                        <!-- Merge Mode -->
                                                        <button v-if="!splitMode" class="action-btn merge" 
                                                                :class="{ selected: isSelected(gid) }"
                                                                @click="toggleMergeSelection(gid)" 
                                                                title="Toggle merge">
                                                            <i class="fas fa-code-merge"></i>
                                                        </button>
                                                        
                                                        <!-- Split Mode -->
                                                        <div v-if="splitMode" class="split-assignment-compact">
                                                            <div class="custom-split-dropdown" :class="{ open: openDropdowns[gid] }">
                                                                <button class="split-dropdown-trigger" 
                                                                        @click="toggleDropdown(gid)"
                                                                        :class="{ assigned: getJobSplitGroup(gid) !== '' }"
                                                                        title="Assign to split group">
                                                                    <span class="dropdown-text">
                                                                        {{ getJobSplitGroup(gid) !== '' ? 'G' + (parseInt(getJobSplitGroup(gid)) + 1) : '—' }}
                                                                    </span>
                                                                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                                                                </button>
                                                                <div class="split-dropdown-menu" v-if="openDropdowns[gid]">
                                                                    <div class="dropdown-option unassign" 
                                                                         @click="assignJobToGroup(gid, ''); closeDropdown(gid)"
                                                                         :class="{ active: getJobSplitGroup(gid) === '' }">
                                                                        <span class="option-badge">—</span>
                                                                        <span class="option-text">Unassigned</span>
                                                                    </div>
                                                                    <div v-for="(group, index) in splitGroups" :key="index"
                                                                         class="dropdown-option" 
                                                                         @click="assignJobToGroup(gid, index.toString()); closeDropdown(gid)"
                                                                         :class="{ active: getJobSplitGroup(gid) === index.toString() }">
                                                                        <span class="option-badge" :style="{ backgroundColor: getGroupColor(index) }">
                                                                            G{{ index + 1 }}
                                                                        </span>
                                                                        <span class="option-text">{{ group.client }}</span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Split Groups Section -->
                <div class="split-groups-section" v-if="splitMode">
                    <div class="split-groups-container">
                        <div class="split-groups-header">
                            <h4 class="section-title">
                                <i class="fas fa-cut"></i>
                                Split Groups
                            </h4>
                            <button class="btn btn-add" @click="createEmptySplitGroup()">
                                <i class="fas fa-plus"></i> Add Group
                            </button>
                        </div>

                        <div v-if="splitGroups.length === 0" class="no-split-groups">
                            <p>No split groups created yet. Click "Add Group" to create your first split group.</p>
                        </div>

                        <div class="split-groups-list" v-else>
                            <div class="split-group-card job-card" v-for="(group, index) in splitGroups" :key="index">
                                <div class="split-group-header">
                                    <div class="split-group-info">
                                        <div class="group-title-row">
                                            <span class="group-badge">Group {{ index + 1 }}</span>
                                            <span class="group-title">{{ group.order_title || 'Split Group' }}</span>
                                        </div>
                                        <div class="group-meta-row">
                                            <span class="group-client">
                                                <i class="fas fa-user"></i>
                                                {{ group.client }}
                                            </span>
                                            <span class="group-job-count">
                                                <i class="fas fa-briefcase"></i>
                                                {{ group.job_ids.length }} jobs
                                            </span>
                                        </div>
                                    </div>
                                    <div class="group-actions">
                                        <button @click="changeSplitGroupClient(index)" class="btn-edit-small" title="Change Client">
                                            <i class="fas fa-user-edit"></i>
                                        </button>
                                        <button @click="removeSplitGroup(index)" class="btn-cancel-small" title="Remove Group">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="group-jobs" v-if="group.job_ids.length > 0">
                                    <div class="jobs-grid">
                                        <div v-for="jobId in group.job_ids" :key="jobId" class="group-job-item">
                                            <span class="job-name">{{ displayJobName(jobId) }}</span>
                                            <button @click="removeJobFromSplitGroup(index, jobId)" class="btn-remove-job" title="Remove Job">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="empty-group">
                                    <i class="fas fa-inbox"></i>
                                    <span>No jobs assigned yet. Use the dropdowns above to assign jobs to this group.</span>
                                </div>
                            </div>
                        </div>

                        <div class="split-validation" v-if="splitGroups.length > 0">
                            <div v-if="getUnassignedJobs().length > 0" class="validation-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                {{ getUnassignedJobs().length }} jobs not assigned to any split group
                            </div>
                            <div v-if="getUnassignedJobs().length === 0 && splitGroups.length > 0" class="validation-success">
                                <i class="fas fa-check-circle"></i>
                                All jobs assigned to split groups
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

        <!-- Client Change Modal -->
        <div v-if="showClientChangeModal" class="modal-overlay" @click="closeClientChangeModal">
            <div class="modal-content" @click.stop>
                <div class="modal-header">
                    <h3>Change Client for Split Group</h3>
                    <button @click="closeClientChangeModal" class="close-btn">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Search and Select Client:</label>
                        <div class="client-search-container">
                            <input v-model="clientSearchQuery" 
                                   @input="filterClients"
                                   class="form-control client-search-input" 
                                   type="text" 
                                   placeholder="Type to search clients..." />
                            <div v-if="filteredClients.length > 0" class="client-dropdown">
                                <div v-for="client in filteredClients" 
                                     :key="client.id"
                                     @click="selectClient(client)"
                                     class="client-option"
                                     :class="{ selected: tempClientName === client.name }">
                                    {{ client.name }}
                                </div>
                            </div>
                            <div v-else-if="clientSearchQuery.length > 0" class="no-clients">
                                No clients found matching "{{ clientSearchQuery }}"
                            </div>
                        </div>
                        <div v-if="tempClientName" class="selected-client">
                            <strong>Selected:</strong> {{ tempClientName }}
                            <button @click="clearClientSelection" class="btn-clear-client">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button @click="saveClientChange" :disabled="!tempClientName" class="btn green">Save</button>
                    <button @click="closeClientChangeModal" class="btn delete">Cancel</button>
                </div>
            </div>
        </div>

        <!-- Split Preview Modal -->
        <div v-if="showSplitPreviewModal" class="modal-overlay" @click="closeSplitPreviewModal">
            <div class="preview-modal-content" @click.stop>
                <div class="modal-header">
                    <h3>Split Invoice Previews</h3>
                    <button @click="closeSplitPreviewModal" class="close-btn">&times;</button>
                </div>
                <div class="split-preview-tabs">
                    <button v-for="(preview, index) in splitPreviewPdfs" :key="index"
                            class="split-tab" 
                            :class="{ active: selectedPreviewTab === index }"
                            @click="selectedPreviewTab = index">
                        {{ preview.order_title || `Group ${preview.split_group}` }} - {{ preview.client }}
                    </button>
                </div>
                <div class="preview-modal-body">
                    <iframe v-if="splitPreviewPdfs[selectedPreviewTab]?.pdfBlobUrl"
                            :src="splitPreviewPdfs[selectedPreviewTab].pdfBlobUrl" 
                            width="100%" height="600px" frameborder="0" title="Split Invoice Preview">
                    </iframe>
                    <div v-else class="pdf-error">
                        No PDF data available for this preview
                    </div>
                </div>
                <div class="modal-footer">
                    <button @click="closeSplitPreviewModal" class="btn delete">Close Preview</button>
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
        hasOrders() {
            const data = this.invoiceData;
            if (!data) return false;
            if (Array.isArray(data)) return data.length > 0;
            try { return Object.keys(data).length > 0; } catch (_) { return false; }
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
        },
        hasValidMergeGroups() {
            if (!this.mergeGroups || this.mergeGroups.length === 0) {
                return true; // No merge groups means validation passes
            }

            return this.mergeGroups.every(group => {
                const hasTitle = group.title && group.title.trim() !== '';
                const hasQuantity = group.quantity && Number(group.quantity) > 0;
                const hasSalePrice = group.sale_price && Number(group.sale_price) > 0;

                return hasTitle && hasQuantity && hasSalePrice;
            });
        },
        canGenerateOrPreview() {
            if (this.splitMode) {
                return this.splitGroups.length > 0 && this.getUnassignedJobs().length === 0;
            }
            return this.hasOrders && this.hasValidMergeGroups;
        },
        filteredClients() {
            if (!this.clientSearchQuery) {
                return this.availableClients.slice(0, 10); // Show first 10 clients when no search
            }
            return this.availableClients.filter(client => 
                client.name.toLowerCase().includes(this.clientSearchQuery.toLowerCase())
            ).slice(0, 10); // Limit to 10 results
        }
    },
    data() {
        return {
            showImagePopover: false,
            selectedJob: null,
            isSidebarVisible: false,
            spreadsheetMode: false,
            isEditMode: true,
            editingTitle: {},
            titleEdits: {},
            backgroundColor: null,
            openDialog: false,
            comment: '',
            previewInvoiceId: null,
            previewDate: new Date().toISOString().split('T')[0],
            editingDate: false,
            dateEdit: '',
            paymentDeadline: 30,
            defaultPaymentDeadline: 30, // Track the original default from client
            editingPaymentDeadline: false,
            paymentDeadlineEdit: 30,
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
            materialsOpen: {},
            selectedJobIds: [],
            mergeGroups: [], // [{job_ids:[1,2,3]}]
            mergedPalette: ['#7DC068', '#51A8B1', '#a36a03', '#3182ce', '#9e2c30', '#81c950'],
            // Split functionality
            splitMode: false,
            splitGroups: [], // [{job_ids: [1,2], client: 'Client A'}, {job_ids: [3], client: 'Client B'}]
            showClientChangeModal: false,
            selectedSplitGroup: null,
            tempClientName: '',
            showSplitPreviewModal: false,
            splitPreviewPdfs: [],
            selectedPreviewTab: 0,
            availableClients: [],
            clientSearchQuery: '',
            openDropdowns: {} // Track which dropdowns are open
        }
    },
    methods: {
        isSelected(id) {
            return (this.selectedJobIds || []).includes(id);
        },
        // Initialize units for all jobs
        initializeJobUnits() {
            if (this.invoiceData) {
                // Handle both array and object formats
                const invoices = Array.isArray(this.invoiceData) ? this.invoiceData : Object.values(this.invoiceData);
                invoices.forEach(invoice => {
                    if (invoice.jobs && Array.isArray(invoice.jobs)) {
                        invoice.jobs.forEach(job => {
                            if (!job.unit) {
                                this.$set ? this.$set(job, 'unit', 'ком') : (job.unit = 'ком');
                            }
                        });
                    }
                });
            }
        },
        // Initialize unit for a single job
        initializeJobUnit(job) {
            if (!job.unit) {
                this.$set ? this.$set(job, 'unit', 'ком') : (job.unit = 'ком');
            }
        },
        // Initialize unit for a merge group
        initializeMergeGroupUnit(groupIndex) {
            if (!this.mergeGroups[groupIndex].unit) {
                this.$set ? this.$set(this.mergeGroups[groupIndex], 'unit', 'ком') : (this.mergeGroups[groupIndex].unit = 'ком');
            }
        },
        // Update job unit
        updateJobUnit(job, newUnit) {
            this.$set ? this.$set(job, 'unit', newUnit) : (job.unit = newUnit);
        },
        // Update merge group unit
        updateMergeGroupUnit(groupIndex, newUnit) {
            this.$set ? this.$set(this.mergeGroups[groupIndex], 'unit', newUnit) : (this.mergeGroups[groupIndex].unit = newUnit);
        },
        toggleMergeSelection(id) {
            const set = new Set(this.selectedJobIds || []);
            if (set.has(id)) set.delete(id); else set.add(id);
            this.selectedJobIds = Array.from(set);
        },
        groupTotalAreaForOrder(orderId, groupIdx) {
            const ids = this.jobsInGroupForOrder(orderId, groupIdx);
            let total = 0;
            ids.forEach(id => { const j = this.getJobById(id); total += Number(j?.computed_total_area_m2 || 0); });
            return total;
        },
        getGroupsForOrder(orderId) {
            const res = [];
            (this.mergeGroups || []).forEach((g, idx) => {
                const ids = Array.isArray(g.job_ids) ? g.job_ids : [];
                const hasJobInOrder = (this.invoiceData && this.invoiceData[orderId] && (this.invoiceData[orderId].jobs || []).some(j => ids.includes(j.id)));
                if (hasJobInOrder) res.push({ ...g, __idx: idx });
            });
            return res;
        },
        jobsInGroupForOrder(orderId, groupIdx) {
            const g = (this.mergeGroups || [])[groupIdx] || {}; const ids = Array.isArray(g.job_ids) ? g.job_ids : [];
            const orderJobs = (this.invoiceData && this.invoiceData[orderId]) ? (this.invoiceData[orderId].jobs || []) : [];
            return ids.filter(id => orderJobs.some(j => j.id === id));
        },
        isGroupEditableInOrder(groupIdx, orderId) {
            const g = (this.mergeGroups || [])[groupIdx] || {}; const ids = Array.isArray(g.job_ids) ? g.job_ids : [];
            if (ids.length === 0) return false;
            // Editable only in the order that is the first vertically (lowest id) among orders that contain jobs from this group
            const involvedOrderIds = Object.values(this.invoiceData || {})
                .filter(o => (o.jobs || []).some(j => ids.includes(j.id)))
                .map(o => o.id)
                .sort((a, b) => a - b);
            const firstOwnerId = involvedOrderIds[0];
            return firstOwnerId === orderId;
        },
        displayJobName(id) {
            const j = this.getJobById(id); return j ? j.name : '';
        },
        displayJobQty(id) {
            const j = this.getJobById(id); return j ? j.quantity : '';
        },
        displayJobUnit(id) {
            const j = this.getJobById(id); return j ? (j.unit || 'ком') : 'ком';
        },
        displayJobArea(id) {
            const j = this.getJobById(id); return this.formatArea(j ? j.computed_total_area_m2 : 0);
        },
        displayJobPrice(id) {
            const j = this.getJobById(id); return this.formatPrice(j ? j.salePrice : 0);
        },
        isGroupHolderForOrder(groupIndex, orderId) {
            const group = this.mergeGroups[groupIndex] || {};
            const ids = Array.isArray(group.job_ids) ? group.job_ids : [];
            if (ids.length === 0) return false;
            const firstId = ids[0];
            // Determine which order owns the first job id
            const owner = Object.values(this.invoiceData || {}).find(o => (o.jobs || []).some(j => j.id === firstId));
            return owner && owner.id === orderId;
        },
        getJobById(jobId) {
            for (const o of Object.values(this.invoiceData || {})) {
                const j = (o.jobs || []).find(x => x.id === jobId);
                if (j) return j;
            }
            return null;
        },
        getMergeGroupIndexByJobId(jobId) {
            const groups = Array.isArray(this.mergeGroups) ? this.mergeGroups : [];
            for (let i = 0; i < groups.length; i++) {
                const ids = Array.isArray(groups[i].job_ids) ? groups[i].job_ids : [];
                if (ids.includes(jobId)) return i;
            }
            return -1;
        },
        getMergeClass(job) {
            // Prefer merged_job_ids or job id membership
            const id = job && job.id;
            if (!id) return '';
            const idx = this.getMergeGroupIndexByJobId(id);
            return idx >= 0 ? `merged-color-${idx % this.mergedPalette.length}` : '';
        },
        getMergedJobs(orderId) {
            try {
                const order = this.invoiceData && this.invoiceData[orderId] ? this.invoiceData[orderId] : null;
                const jobs = (order && Array.isArray(order.jobs)) ? order.jobs : [];
                const groups = Array.isArray(this.mergeGroups) ? this.mergeGroups : [];
                if (!groups.length) return jobs;
                const byId = new Map(jobs.map(j => [j.id, { ...j }]));
                // Build cross-order lookup so selected groups that include other orders still display correctly by placing merged job into this order if it owns the first id
                // For local display, only merge jobs that belong to this order; keep cross-order merged representative in the order owning the first id in group.
                const allJobs = Object.values(this.invoiceData || {}).flatMap(o => o.jobs || []);
                const jobToOrder = new Map();
                allJobs.forEach(oJob => { jobToOrder.set(oJob.id, Object.values(this.invoiceData).find(o => (o.jobs || []).some(j => j.id === oJob.id))?.id); });
                for (const g of groups) {
                    const ids = Array.isArray(g.job_ids) ? g.job_ids : [];
                    const presentHere = ids.filter(id => byId.has(id));
                    if (ids.length < 2) continue;
                    // Only the order that owns the first id will show the merged representative; others will remove their members
                    const holderOrderId = jobToOrder.get(ids[0]);
                    let qty = 0; let area = 0; let first = null;
                    for (const id of ids) {
                        const jAll = allJobs.find(j => j.id === id);
                        if (!jAll) continue;
                        if (!first) first = jAll;
                        qty += Number(jAll.quantity || 0);
                        area += Number(jAll.computed_total_area_m2 || 0);
                        if (byId.has(id)) byId.delete(id);
                    }
                    if (holderOrderId === orderId && first) {
                        const merged = { ...first, quantity: qty, computed_total_area_m2: area, merged: true, merged_job_ids: ids };
                        byId.set(first.id, merged);
                    }
                }
                return Array.from(byId.values());
            } catch (_) { return (this.invoiceData && this.invoiceData[orderId] && this.invoiceData[orderId].jobs) ? this.invoiceData[orderId].jobs : []; }
        },
        mergeable(jobA, jobB) {
            return (jobA?.name || '') === (jobB?.name || '') && Number(jobA?.salePrice || 0) === Number(jobB?.salePrice || 0);
        },
        createOrAddToMergeGroup(orderId) {
            const ids = Array.from(new Set(this.selectedJobIds));
            if (ids.length < 2) return;
            // no validation rules: allow merging any selected jobs
            const allJobs = Object.values(this.invoiceData).flatMap(o => o.jobs || []);
            const picked = ids.map(id => allJobs.find(j => j.id === id)).filter(Boolean);
            if (picked.length < 2) return;
            // If any existing group contains any of these ids, expand that group; else create new
            let group = this.mergeGroups.find(g => g.job_ids.some(id => ids.includes(id)));
            if (!group) {
                group = { job_ids: [] };
                this.mergeGroups.push(group);
            }
            group.job_ids = Array.from(new Set([...group.job_ids, ...ids]));
            // Attempt autofill when name and price are uniform
            this.autofillGroupFieldsIfUniform(this.mergeGroups.indexOf(group));
            this.selectedJobIds = [];
            this.$toast?.success?.('Jobs merged for preview/generation');
        },
        unmergeSelected() {
            if (!this.selectedJobIds.length) return;
            const set = new Set(this.selectedJobIds);
            this.mergeGroups = this.mergeGroups
                .map(g => ({ ...g, job_ids: g.job_ids.filter(id => !set.has(id)) }))
                .filter(g => g.job_ids.length > 1);
            // Re-evaluate autofill for all groups after removal
            this.mergeGroups.forEach((_, idx) => this.autofillGroupFieldsIfUniform(idx));
            this.selectedJobIds = [];
        },
        getAllJobsFlat() {
            return Object.values(this.invoiceData || {}).flatMap(o => o.jobs || []);
        },
        autofillGroupFieldsIfUniform(groupIdx) {
            if (groupIdx == null || groupIdx < 0) return;
            const group = this.mergeGroups[groupIdx];
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
        startEditingJob(job) {
            this.editingJobId = job.id;
            // Initialize unit if it doesn't exist
            if (!job.unit) {
                this.$set ? this.$set(job, 'unit', 'ком') : (job.unit = 'ком');
            }
            // snapshot original values for cancel
            this.$set ? this.$set(this.originalJobSnapshots, job.id, {
                name: job.name,
                quantity: job.quantity,
                unit: job.unit,
                computed_total_area_m2: job.computed_total_area_m2,
                salePrice: job.salePrice,
                totalPrice: job.totalPrice
            }) : (this.originalJobSnapshots[job.id] = {
                name: job.name,
                quantity: job.quantity,
                unit: job.unit,
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
                job.unit = snap.unit;
                job.computed_total_area_m2 = snap.computed_total_area_m2;
                job.salePrice = snap.salePrice;
                job.totalPrice = snap.totalPrice;
            }
            this.editingJobId = null;
            delete this.originalJobSnapshots[job.id];
        },
        async saveEditingJob(job) {
            try {
                // Store unit locally (frontend-only field)
                const localUnit = job.unit;
                
                // Persist supported fields to backend JobController@update
                const response = await axios.put(`/jobs/${job.id}`, {
                    name: job.name,
                    quantity: job.quantity,
                    salePrice: job.salePrice
                });
                
                if (response?.data?.job) {
                    // Update only the fields that came from server, preserve frontend-only fields
                    job.name = response.data.job.name;
                    job.quantity = response.data.job.quantity;
                    job.salePrice = response.data.job.salePrice;
                    if (response.data.job.computed_total_area_m2 !== undefined) {
                        job.computed_total_area_m2 = response.data.job.computed_total_area_m2;
                    }
                    // Keep the frontend-only unit field
                    job.unit = localUnit;
                    this.onJobUpdated?.(response.data.job);
                    this.$toast?.success?.('Job updated');
                } else {
                    // Even if no job data returned, keep the unit
                    job.unit = localUnit;
                    this.$toast?.success?.('Job updated');
                }
                this.editingJobId = null;
                delete this.originalJobSnapshots[job.id];
            } catch (e) {
                console.error('Save error:', e);
                this.$toast?.error?.('Failed to update job: ' + (e.response?.data?.message || e.message));
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
        async loadDefaultPaymentDeadline() {
            try {
                // Get client from first invoice
                const firstInvoice = Object.values(this.invoiceData || {})[0];
                if (!firstInvoice || !firstInvoice.client) {
                    console.warn('No invoice or client found, using default 30 days');
                    this.paymentDeadline = 30;
                    return;
                }

                const clientName = firstInvoice.client;

                // Fetch client card statement to get default payment deadline
                const res = await axios.get(`/clients/${encodeURIComponent(clientName)}/card-statement`);

                const deadline = res?.data?.payment_deadline;

                if (deadline !== null && deadline !== undefined && deadline !== '') {
                    // Handle both string and number types, convert to integer
                    const parsedDeadline = Number(deadline);
                    if (!isNaN(parsedDeadline) && parsedDeadline > 0) {
                        this.paymentDeadline = Math.round(parsedDeadline);
                        this.defaultPaymentDeadline = Math.round(parsedDeadline); // Store the default
                    } else {
                        this.paymentDeadline = 30;
                        this.defaultPaymentDeadline = 30;
                    }
                } else {
                    this.paymentDeadline = 30;
                    this.defaultPaymentDeadline = 30;
                }
            } catch (e) {
                this.paymentDeadline = 30;
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
        startEditPaymentDeadline() {
            if (!this.isEditMode) return;
            this.editingPaymentDeadline = true;
            this.paymentDeadlineEdit = this.paymentDeadline;
            this.$nextTick(() => {
                const input = document.querySelector('.date-input[type="number"]');
                if (input) input.focus();
            });
        },
        commitPaymentDeadline() {
            if (this.paymentDeadlineEdit === null || this.paymentDeadlineEdit === '') {
                this.cancelEditPaymentDeadline();
                return;
            }
            this.paymentDeadline = parseInt(this.paymentDeadlineEdit) || 30;
            this.cancelEditPaymentDeadline();
        },
        cancelEditPaymentDeadline() {
            this.editingPaymentDeadline = false;
            this.paymentDeadlineEdit = this.paymentDeadline;
        },
        toggleEditMode() { },
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
        isMergeGroupFieldValid(groupIdx, fieldName) {
            const group = this.mergeGroups[groupIdx];
            if (!group) return false;

            switch (fieldName) {
                case 'title':
                    return group.title && group.title.trim() !== '';
                case 'quantity':
                    return group.quantity && Number(group.quantity) > 0;
                case 'sale_price':
                    return group.sale_price && Number(group.sale_price) > 0;
                default:
                    return true;
            }
        },
        async previewInvoice() {
            const toast = useToast();
            
            // Check if we're in split mode
            if (this.splitMode && this.splitGroups.length > 0) {
                return this.previewSplitInvoices();
            }

            try {
                const orderIds = Object.values(this.invoiceData || {})
                    .map(order => order && order.id)
                    .filter(Boolean);
                if (!orderIds.length) {
                    toast.error('Cannot preview: no orders selected');
                    return;
                }

                // Validate merge groups before preview
                if (!this.hasValidMergeGroups) {
                    toast.error('Cannot preview: Please fill in all required fields (title, quantity, and price) for merged groups');
                    return;
                }
                const tradePayload = this.buildTradeItemsPayload();
                
                // Include job data with units for preview
                const jobsWithUnits = [];
                if (this.invoiceData) {
                    // Handle both array and object formats
                    const invoices = Array.isArray(this.invoiceData) ? this.invoiceData : Object.values(this.invoiceData);
                    invoices.forEach(invoice => {
                        if (invoice.jobs && Array.isArray(invoice.jobs)) {
                            invoice.jobs.forEach(job => {
                                jobsWithUnits.push({
                                    id: job.id,
                                    unit: job.unit || 'ком'
                                });
                            });
                        }
                    });
                }

                // Only send payment_deadline_override if user changed it from default
                const payloadData = {
                    orders: orderIds,
                    comment: this.comment,
                    trade_items: tradePayload,
                    created_at: this.previewDate,
                    merge_groups: this.mergeGroups,
                    job_units: jobsWithUnits
                };
                
                // Only include override if it's different from the client's default
                if (this.paymentDeadline !== this.defaultPaymentDeadline) {
                    payloadData.payment_deadline_override = this.paymentDeadline;
                }
                
                const response = await axios.post('/preview-invoice', payloadData, {
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
                // Check if we're in split mode
                if (this.splitMode && this.splitGroups.length > 0) {
                    return this.generateSplitInvoices();
                }

                const orderIds = Object.values(this.invoiceData || {})
                    .map(order => order && order.id)
                    .filter(Boolean);
                if (!orderIds.length) {
                    toast.error('Cannot generate invoice: no orders selected');
                    return;
                }

                // Validate merge groups before generation
                if (!this.hasValidMergeGroups) {
                    toast.error('Cannot generate invoice: Please fill in all required fields (title, quantity, and price) for merged groups');
                    return;
                }
                const tradePayload = this.buildTradeItemsPayload();
                // Include job data with units for generation
                const jobsWithUnits = [];
                if (this.invoiceData) {
                    // Handle both array and object formats
                    const invoices = Array.isArray(this.invoiceData) ? this.invoiceData : Object.values(this.invoiceData);
                    invoices.forEach(invoice => {
                        if (invoice.jobs && Array.isArray(invoice.jobs)) {
                            invoice.jobs.forEach(job => {
                                jobsWithUnits.push({
                                    id: job.id,
                                    unit: job.unit || 'ком'
                                });
                            });
                        }
                    });
                }

                // Request metadata so we can redirect; then separately open the PDF in new tab
                // Only send payment_deadline_override if user changed it from default
                const payloadData = {
                    orders: orderIds,
                    comment: this.comment,
                    trade_items: tradePayload,
                    created_at: this.previewDate,
                    merge_groups: this.mergeGroups,
                    job_units: jobsWithUnits,
                    return_meta: true
                };
                
                // Only include override if it's different from the client's default
                if (this.paymentDeadline !== this.defaultPaymentDeadline) {
                    payloadData.payment_deadline_override = this.paymentDeadline;
                }
                
                const response = await axios.post('/generate-invoice', payloadData, { responseType: 'json' });

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
        },

        // Split functionality methods
        toggleSplitMode() {
            this.splitMode = !this.splitMode;
            if (!this.splitMode) {
                // Clear split mode data
                this.splitGroups = [];
                this.selectedJobIds = [];
                this.showClientChangeModal = false;
                this.selectedSplitGroup = null;
                this.tempClientName = '';
                this.$toast?.info?.('Split mode disabled');
            } else {
                // Clear merge mode data when entering split mode
                this.mergeGroups = [];
                this.selectedJobIds = [];
                this.$toast?.info?.('Split mode enabled - Create groups and assign jobs');
            }
        },

        createEmptySplitGroup() {
            const newGroup = {
                job_ids: [],
                client: this.clientName // default to original client
            };

            this.splitGroups.push(newGroup);
            this.$toast?.success?.('Split group created');
        },

        assignJobToGroup(jobId, groupIndex) {
            if (groupIndex === '') {
                // Remove job from all groups
                this.splitGroups.forEach(group => {
                    group.job_ids = group.job_ids.filter(id => id !== jobId);
                });
                return;
            }

            const targetGroupIndex = parseInt(groupIndex);
            if (targetGroupIndex < 0 || targetGroupIndex >= this.splitGroups.length) return;

            // Remove job from all other groups first
            this.splitGroups.forEach((group, index) => {
                if (index !== targetGroupIndex) {
                    group.job_ids = group.job_ids.filter(id => id !== jobId);
                }
            });

            // Add job to target group if not already there
            const targetGroup = this.splitGroups[targetGroupIndex];
            if (!targetGroup.job_ids.includes(jobId)) {
                targetGroup.job_ids.push(jobId);
                
                // Update group title with order title if not already set or if this is the first job
                if (!targetGroup.order_title || targetGroup.job_ids.length === 1) {
                    const orderTitle = this.getOrderTitleForJob(jobId);
                    if (orderTitle) {
                        targetGroup.order_title = orderTitle;
                    }
                }
            }
        },

        getJobSplitGroup(jobId) {
            for (let i = 0; i < this.splitGroups.length; i++) {
                if (this.splitGroups[i].job_ids.includes(jobId)) {
                    return i.toString();
                }
            }
            return '';
        },

        removeSplitGroup(index) {
            this.splitGroups.splice(index, 1);
        },

        removeJobFromSplitGroup(groupIndex, jobId) {
            const group = this.splitGroups[groupIndex];
            if (group) {
                group.job_ids = group.job_ids.filter(id => id !== jobId);
                if (group.job_ids.length === 0) {
                    this.removeSplitGroup(groupIndex);
                }
            }
        },

        changeSplitGroupClient(groupIndex) {
            this.selectedSplitGroup = groupIndex;
            this.tempClientName = this.splitGroups[groupIndex].client;
            this.clientSearchQuery = '';
            this.showClientChangeModal = true;
            this.loadClients(); // Load clients when modal opens
        },

        async loadClients() {
            try {
                const response = await axios.get('/api/clients/all');
                this.availableClients = response.data || [];
            } catch (error) {
                console.error('Error loading clients:', error);
                this.$toast?.error?.('Failed to load clients');
            }
        },

        filterClients() {
            // This method is called on input, the computed property handles the actual filtering
        },

        selectClient(client) {
            this.tempClientName = client.name;
            this.clientSearchQuery = '';
        },

        clearClientSelection() {
            this.tempClientName = '';
            this.clientSearchQuery = '';
        },

        saveClientChange() {
            if (this.selectedSplitGroup !== null && this.tempClientName.trim()) {
                this.splitGroups[this.selectedSplitGroup].client = this.tempClientName.trim();
                this.closeClientChangeModal();
                this.$toast?.success?.('Client updated for split group');
            }
        },

        closeClientChangeModal() {
            this.showClientChangeModal = false;
            this.selectedSplitGroup = null;
            this.tempClientName = '';
            this.clientSearchQuery = '';
        },

        getUnassignedJobs() {
            const allJobIds = this.getAllJobIds();
            const assignedJobIds = this.splitGroups.flatMap(g => g.job_ids);
            return allJobIds.filter(id => !assignedJobIds.includes(id));
        },

        getAllJobIds() {
            return Object.values(this.invoiceData || {}).flatMap(order => 
                (order.jobs || []).map(job => job.id)
            );
        },

        getOrderTitleForJob(jobId) {
            for (const order of Object.values(this.invoiceData || {})) {
                if (order.jobs && order.jobs.some(job => job.id === jobId)) {
                    return order.invoice_title || `Order ${order.id}`;
                }
            }
            return null;
        },

        validateSplitGroups() {
            if (this.splitGroups.length === 0) {
                this.$toast?.error?.('Please create at least one split group');
                return false;
            }

            const unassignedJobs = this.getUnassignedJobs();
            if (unassignedJobs.length > 0) {
                this.$toast?.error?.('All jobs must be assigned to a split group');
                return false;
            }

            if (this.splitGroups.some(g => g.job_ids.length === 0)) {
                this.$toast?.error?.('Each split group must contain at least one job');
                return false;
            }

            return true;
        },

        async generateSplitInvoices() {
            const toast = useToast();
            
            if (!this.validateSplitGroups()) {
                return;
            }

            try {
                // Include job data with units for split generation
                const jobsWithUnits = [];
                if (this.invoiceData) {
                    // Handle both array and object formats
                    const invoices = Array.isArray(this.invoiceData) ? this.invoiceData : Object.values(this.invoiceData);
                    invoices.forEach(invoice => {
                        if (invoice.jobs && Array.isArray(invoice.jobs)) {
                            invoice.jobs.forEach(job => {
                                jobsWithUnits.push({
                                    id: job.id,
                                    unit: job.unit || 'ком'
                                });
                            });
                        }
                    });
                }

                const tradePayload = this.buildTradeItemsPayload();
                const payloadData = {
                    split_groups: this.splitGroups,
                    comment: this.comment,
                    trade_items: tradePayload,
                    created_at: this.previewDate,
                    job_units: jobsWithUnits,
                    return_meta: true
                };

                if (this.paymentDeadline !== this.defaultPaymentDeadline) {
                    payloadData.payment_deadline_override = this.paymentDeadline;
                }

                const response = await axios.post('/generate-invoice', payloadData, { responseType: 'json' });

                if (response?.data?.success && response?.data?.is_split) {
                    // Open PDFs for each split invoice
                    if (response.data.pdfs && response.data.pdfs.length > 0) {
                        response.data.pdfs.forEach(pdfData => {
                            const binary = atob(pdfData.pdf);
                            const array = new Uint8Array(binary.length);
                            for (let i = 0; i < binary.length; i++) array[i] = binary.charCodeAt(i);
                            const blob = new Blob([array], { type: 'application/pdf' });
                            const url = window.URL.createObjectURL(blob);
                            window.open(url, '_blank');
                        });
                    }

                    toast.success(`Generated ${response.data.invoices.length} split invoices successfully`);
                    
                    // Redirect to the first invoice or stay on current page
                    if (response.data.invoices.length > 0) {
                        this.$inertia.visit(`/invoice/${response.data.invoices[0].faktura_id}`);
                    }
                }
            } catch (error) {
                console.error('Error generating split invoices:', error);
                toast.error('Error generating split invoices!');
            }
        },

        async previewSplitInvoices() {
            const toast = useToast();
            
            if (!this.validateSplitGroups()) {
                return;
            }

            try {
                // Include job data with units for split preview
                const jobsWithUnits = [];
                if (this.invoiceData) {
                    // Handle both array and object formats
                    const invoices = Array.isArray(this.invoiceData) ? this.invoiceData : Object.values(this.invoiceData);
                    invoices.forEach(invoice => {
                        if (invoice.jobs && Array.isArray(invoice.jobs)) {
                            invoice.jobs.forEach(job => {
                                jobsWithUnits.push({
                                    id: job.id,
                                    unit: job.unit || 'ком'
                                });
                            });
                        }
                    });
                }

                const tradePayload = this.buildTradeItemsPayload();
                const payloadData = {
                    split_groups: this.splitGroups,
                    comment: this.comment,
                    trade_items: tradePayload,
                    created_at: this.previewDate,
                    job_units: jobsWithUnits
                };

                if (this.paymentDeadline !== this.defaultPaymentDeadline) {
                    payloadData.payment_deadline_override = this.paymentDeadline;
                }

                const response = await axios.post('/preview-invoice', payloadData, { responseType: 'json' });

                if (response?.data?.success && response?.data?.is_split_preview) {
                    console.log('Split preview response:', response.data);
                    console.log('PDF data length:', response.data.previews.map(p => p.pdf ? p.pdf.length : 0));
                    
                    // Convert base64 PDFs to blob URLs for better iframe compatibility
                    this.splitPreviewPdfs = response.data.previews.map(preview => {
                        if (preview.pdf) {
                            try {
                                const binary = atob(preview.pdf);
                                const array = new Uint8Array(binary.length);
                                for (let i = 0; i < binary.length; i++) {
                                    array[i] = binary.charCodeAt(i);
                                }
                                const blob = new Blob([array], { type: 'application/pdf' });
                                const blobUrl = window.URL.createObjectURL(blob);
                                
                                return {
                                    ...preview,
                                    pdfBlobUrl: blobUrl,
                                    originalPdf: preview.pdf // Keep original for download
                                };
                            } catch (error) {
                                console.error('Error creating blob URL:', error);
                                return preview;
                            }
                        }
                        return preview;
                    });
                    
                    this.selectedPreviewTab = 0;
                    this.showSplitPreviewModal = true;
                }
            } catch (error) {
                console.error('Error generating split preview:', error);
                toast.error('Error generating split preview!');
            }
        },

        closeSplitPreviewModal() {
            this.showSplitPreviewModal = false;
            
            // Clean up blob URLs to prevent memory leaks (same as regular preview)
            if (this.splitPreviewPdfs && this.splitPreviewPdfs.length > 0) {
                this.splitPreviewPdfs.forEach(preview => {
                    if (preview.pdfBlobUrl) {
                        window.URL.revokeObjectURL(preview.pdfBlobUrl);
                    }
                });
            }
            
            this.splitPreviewPdfs = [];
            this.selectedPreviewTab = 0;
        },

        // Custom dropdown methods
        toggleDropdown(jobId) {
            // Close all other dropdowns first
            Object.keys(this.openDropdowns).forEach(key => {
                if (key !== jobId.toString()) {
                    this.$set ? this.$set(this.openDropdowns, key, false) : (this.openDropdowns[key] = false);
                }
            });
            
            // Toggle the clicked dropdown
            const isOpen = this.openDropdowns[jobId];
            this.$set ? this.$set(this.openDropdowns, jobId, !isOpen) : (this.openDropdowns[jobId] = !isOpen);
        },

        closeDropdown(jobId) {
            this.$set ? this.$set(this.openDropdowns, jobId, false) : (this.openDropdowns[jobId] = false);
        },

        closeAllDropdowns() {
            Object.keys(this.openDropdowns).forEach(key => {
                this.$set ? this.$set(this.openDropdowns, key, false) : (this.openDropdowns[key] = false);
            });
        },

        getGroupColor(index) {
            const colors = ['#3182ce', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4'];
            return colors[index % colors.length];
        },


    },
    mounted() {
        this.loadTradeArticles();
        this.fetchNextInvoiceId();
        this.loadDefaultPaymentDeadline();
        this.initializeJobUnits();

        // Watch for changes in selected article to auto-fill price
        this.$watch('selectedArticle', (newArticle) => {
            if (newArticle && newArticle.selling_price) {
                this.tradeItemPrice = newArticle.selling_price;
            }
        });
    },
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
    grid-template-columns: 2fr 1fr 0.8fr 1fr 1fr 1fr;
    gap: 8px;
    padding: 5px 10px;
    border-top: 1px solid $ultra-light-gray;
}

.job-header-labels.with-actions {
    /* Removed Job Price column, keep actions as last */
    grid-template-columns: 2fr 1fr 0.8fr 1fr 1fr 1fr;
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
    grid-template-columns: 2fr 1fr 0.8fr 1fr 1fr 1fr;
    align-items: center;
    padding: 10px;
    background-color: #7DC068;
    border-radius: 4px;
    gap: 8px;
}

// Dynamic merged color variants
// We generate a few classes mapping to the palette defined in data()
.merged-color-0 {
    background-color: #3182ce !important;
}

.merged-color-1 {
    background-color: #51A8B1 !important;
}

.merged-color-2 {
    background-color: #a36a03 !important;
}

.merged-color-3 {
    background-color: #d1e93b !important;
}

.merged-color-4 {
    background-color: #9e2c30 !important;
}

.merged-color-5 {
    background-color: #81c950 !important;
}

.merged-container {
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 6px;
    margin-bottom: 8px;
}

.merged-container .merged-header {
    display: grid;
    grid-template-columns: 2fr 1fr 0.8fr 1fr 1fr 1fr;
    /* align with job header: Title, Qty, Unit, Total m², Sale, Actions */
    gap: 8px;
    padding: 8px;
}

.merged-container .merged-body {
    padding: 6px 8px 10px 8px;
}

/* Merge icon toggle */
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

/* Split toggle button */
.split-toggle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: fit-content;
    padding: 6px 8px;
    height: 30px;
    border: none;
    background: rgba(0, 128, 128, 0.6);
    color: #fff;
    cursor: pointer;
    border-radius: 4px;
}

.split-toggle.selected {
    color: #fff;
    background: rgba(0, 128, 128, 1);
    border-radius: 4px;
}

.split-toggle:hover {
    color: #fff;
    background: rgba(0, 128, 128, 0.8);
    border-radius: 4px;
}


.job-header.with-actions {
    /* Removed Job Price column, keep actions as last */
    grid-template-columns: 2fr 1fr 0.8fr 1fr 1fr 1fr;
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
    transition: all 0.2s ease;
    
    &:focus {
        outline: none;
        border-color: #008080;
        box-shadow: 0 0 0 2px rgba(0, 128, 128, 0.2);
        background-color: #ffffff;
    }
    
    &:hover {
        border-color: rgba(0, 0, 0, 0.25);
    }
}

.unit-select {
    font-size: 12px;
    padding: 4px 6px;
    cursor: pointer;
    width: fit-content;
    background: transparent;
    border: 1px solid #000000;
    border-radius: 3px;
    
    &:hover {
        background: rgba(255, 255, 255, 0.1);
        border-color: #333333;
    }
    
    &:focus {
        background: #ffffff;
        border-color: #008080;
        box-shadow: 0 0 0 2px rgba(0, 128, 128, 0.2);
    }
    
    option {
        padding: 4px 8px;
        font-size: 12px;
        background: #ffffff;
        color: #111827;
    }
}

.inline-input.invalid-field {
    border: 2px solid #e53e3e;
    background-color: #fed7d7;
    animation: shake 0.5s ease-in-out;
}

@keyframes shake {

    0%,
    100% {
        transform: translateX(0);
    }

    25% {
        transform: translateX(-2px);
    }

    75% {
        transform: translateX(2px);
    }
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

/* Legacy button styles - updated for consistency */
.btn-edit-small,
.btn-save-small,
.btn-cancel-small,
.btn-delete {
    padding: 6px 10px;
    font-size: 12px;
    min-width: 32px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
}

.btn-edit-small {
    background-color: #3182ce;
    color: white;
}

.btn-edit-small:hover {
    background-color: darken(#3182ce, 10%);
}

.btn-save-small {
    background-color: #38a169;
    color: white;
}

.btn-save-small:hover {
    background-color: darken(#38a169, 10%);
}

.btn-cancel-small,
.btn-delete {
    background-color: #e53e3e;
    color: white;
}

.btn-cancel-small:hover,
.btn-delete:hover {
    background-color: darken(#e53e3e, 10%);
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
    transition: all 0.2s ease;
    
    &:focus {
        outline: none;
        border-color: $light-green;
        box-shadow: 0 0 0 2px rgba($light-green, 0.3);
        background: rgba($white, 0.15);
    }
    
    &:hover {
        background: rgba($white, 0.12);
        border-color: lighten($blue, 10%);
    }
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

/* Split Groups Styles */
.split-groups-section {
    background: $light-gray;
    border-radius: 3px;
    padding: 24px;
    margin: 20px 0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    border: 1px solid $light-gray;
}

.split-groups-container {
    color: $white;
}

.split-groups-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    padding-bottom: 10px;
    border-bottom: 2px solid rgba($white, 0.15);
}

.split-groups-header .section-title {
    font-size: 18px;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.split-groups-header .section-title i {
    color: #008080;
    font-size: 16px;
}

.split-groups-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

/* Split Group Cards - Redesigned to match job cards */
.split-group-card {
    /* Inherits job-card styling */
    margin-bottom: 8px;
}

.split-group-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: rgba(#008080, 85%);
    border-radius: 4px;
    margin-bottom: 8px;
}

.split-group-info {
    display: flex;
    flex-direction: column;
    gap: 6px;
    flex: 1;
}

.group-title-row {
    display: flex;
    align-items: center;
    gap: 12px;
}

.group-badge {
    background: rgba($white, 0.2);
    color: $white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.group-title {
    font-size: 16px;
    font-weight: 600;
    color: $white;
}

.group-meta-row {
    display: flex;
    align-items: center;
    gap: 16px;
}

.group-client,
.group-job-count {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: rgba($white, 0.9);
    font-weight: 500;
}

.group-client i,
.group-job-count i {
    font-size: 12px;
    opacity: 0.8;
}

.group-actions {
    display: flex;
    gap: 6px;
    align-items: center;
}

.group-jobs {
    padding: 0 12px 8px 12px;
}

.jobs-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 8px;
}

.group-job-item {
    background: rgba($white, 0.15);
    border: 1px solid rgba($white, 0.2);
    padding: 8px 12px;
    border-radius: 4px;
    font-size: 13px;
    color: $white;
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: all 0.2s ease;
}

.group-job-item:hover {
    background: rgba($white, 0.25);
    border-color: rgba($white, 0.3);
}

.job-name {
    font-weight: 500;
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.btn-remove-job {
    background: rgba($red, 0.8);
    color: $white;
    border: none;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 10px;
    transition: all 0.2s ease;
    flex-shrink: 0;
}

.btn-remove-job:hover {
    background: $red;
    transform: scale(1.1);
}

.split-validation {
    margin-top: 16px;
    padding: 12px;
    border-radius: 4px;
}

/* Split Preview Tabs - added to regular preview modal */
.split-preview-tabs {
    display: flex;
    background: $ultra-light-gray;
    border-bottom: 1px solid $light-gray;
}

.split-tab {
    padding: 12px 20px;
    background: transparent;
    border: none;
    color: rgba($white, 0.8);
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    border-bottom: 3px solid transparent;
    transition: all 0.2s;
    flex: 1;
    text-align: center;
}

.split-tab:hover {
    background: rgba($white, 0.1);
    color: $white;
}

.split-tab.active {
    color: $white;
    border-bottom-color: $blue;
    background: rgba($blue, 0.2);
}

/* Client Search Dropdown Styles */
.client-search-container {
    position: relative;
}

.client-search-input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid $light-gray;
    border-radius: 4px;
    background: $white;
    color: $black;
    font-size: 14px;
}

.client-search-input:focus {
    outline: none;
    border-color: $blue;
    box-shadow: 0 0 0 2px rgba($blue, 0.2);
}

.client-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: $white;
    border: 1px solid $light-gray;
    border-top: none;
    border-radius: 0 0 4px 4px;
    max-height: 200px;
    overflow-y: auto;
    z-index: 1000;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.client-option {
    padding: 10px 12px;
    cursor: pointer;
    color: $black;
    border-bottom: 1px solid #f0f0f0;
    transition: background-color 0.2s;
}

.client-option:hover {
    background-color: #f8f9fa;
}

.client-option.selected {
    background-color: rgba($blue, 0.1);
    color: $blue;
    font-weight: 500;
}

.client-option:last-child {
    border-bottom: none;
}

.no-clients {
    padding: 10px 12px;
    color: #666;
    font-style: italic;
    text-align: center;
}

.selected-client {
    margin-top: 8px;
    padding: 8px 12px;
    background: rgba($green, 0.1);
    border: 1px solid rgba($green, 0.3);
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    color: $green;
}

.btn-clear-client {
    background: none;
    border: none;
    color: $red;
    cursor: pointer;
    padding: 2px 4px;
    border-radius: 2px;
    font-size: 12px;
}

.btn-clear-client:hover {
    background: rgba($red, 0.1);
}

.pdf-error {
    text-align: center;
    padding: 40px;
    color: $red;
    font-size: 16px;
}

/* Split Mode Indicator */
.split-mode-indicator {
    background: rgba(0, 128, 128, 0.2);
    color: #008080;
    padding: 8px 16px;
    border-radius: 4px;
    margin-top: 8px;
    font-size: 14px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
    border: 1px solid rgba(0, 128, 128, 0.3);
}

/* Redesigned Actions Section */
.actions-row {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: nowrap;
}

.action-group {
    display: flex;
    align-items: center;
    gap: 4px;
}

.action-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    transition: all 0.2s ease;
    color: white;
}

.action-btn.edit {
    background: #3182ce;
}

.action-btn.edit:hover {
    background: darken(#3182ce, 10%);
}

.action-btn.save {
    background: #38a169;
}

.action-btn.save:hover {
    background: darken(#38a169, 10%);
}

.action-btn.cancel {
    background: #e53e3e;
}

.action-btn.cancel:hover {
    background: darken(#e53e3e, 10%);
}

.action-btn.materials {
    background: #6b7280;
}

.action-btn.materials:hover {
    background: darken(#6b7280, 10%);
}

.action-btn.merge {
    background: rgba(128, 0, 128, 0.6);
}

.action-btn.merge:hover {
    background: rgba(128, 0, 128, 0.8);
}

.action-btn.merge.selected {
    background: rgba(128, 0, 128, 1);
    box-shadow: 0 0 0 2px rgba(128, 0, 128, 0.3);
}

.mode-actions {
    display: flex;
    align-items: center;
}

/* Compact Split Assignment */
.split-assignment-compact {
    display: flex;
    align-items: center;
    position: relative;
}

/* Custom Split Dropdown */
.custom-split-dropdown {
    position: relative;
    display: inline-block;
}

.split-dropdown-trigger {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 45px;
    height: 28px;
    padding: 4px 6px;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    background: white;
    color: #374151;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.split-dropdown-trigger:hover {
    border-color: #9ca3af;
    background: #f9fafb;
}

.split-dropdown-trigger.assigned {
    border-color: #008080;
    background: rgba(0, 128, 128, 0.1);
    color: #008080;
}

.split-dropdown-trigger .dropdown-text {
    flex: 1;
    text-align: center;
}

.split-dropdown-trigger .dropdown-arrow {
    font-size: 8px;
    margin-left: 2px;
    transition: transform 0.2s ease;
}

.custom-split-dropdown.open .dropdown-arrow {
    transform: rotate(180deg);
}

.split-dropdown-menu {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    min-width: 180px;
    background: white;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    z-index: 1000;
    overflow: hidden;
    margin-top: 2px;
}

.dropdown-option {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    cursor: pointer;
    transition: background-color 0.2s ease;
    border-bottom: 1px solid #f3f4f6;
}

.dropdown-option:last-child {
    border-bottom: none;
}

.dropdown-option:hover {
    background: #f3f4f6;
}

.dropdown-option.active {
    background: rgba(0, 128, 128, 0.1);
    color: #008080;
}

.dropdown-option.unassign {
    color: #6b7280;
    font-style: italic;
}

.dropdown-option.unassign:hover {
    background: #fef2f2;
    color: #dc2626;
}

.option-badge {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 20px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 700;
    color: white;
    background: #6b7280;
    flex-shrink: 0;
}

.dropdown-option.unassign .option-badge {
    background: #9ca3af;
    color: #6b7280;
}

.option-text {
    font-size: 12px;
    font-weight: 500;
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.split-select-mini {
    padding: 4px 6px;
    border: 1px solid #ccc;
    border-radius: 4px;
    background: white;
    color: #333;
    font-size: 11px;
    font-weight: 600;
    width: 40px;
    text-align: center;
    cursor: pointer;
}

.split-select-mini:focus {
    outline: none;
    border-color: #008080;
    box-shadow: 0 0 0 2px rgba(0, 128, 128, 0.2);
    width: 120px; /* Expand when focused */
    text-align: left;
}

.split-select-mini:focus option {
    padding: 4px 8px;
}

/* Legacy styles for backward compatibility */
.split-assignment {
    display: inline-flex;
    align-items: center;
}

.split-group-select {
    padding: 4px 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
    background: white;
    color: #333;
    font-size: 12px;
    min-width: 120px;
    max-width: 200px;
}

.split-group-select.compact {
    min-width: 100px;
    width: auto;
}

.split-group-select:focus {
    outline: none;
    border-color: #008080;
    box-shadow: 0 0 0 2px rgba(0, 128, 128, 0.2);
    min-width: 180px;
}

/* Split Groups Styles */
.no-split-groups {
    text-align: center;
    padding: 40px 20px;
    color: rgba($white, 0.8);
    font-style: italic;
}

.empty-group {
    color: rgba($white, 0.7);
    padding: 16px;
    text-align: center;
    background: rgba($white, 0.05);
    border-radius: 4px;
    border: 2px dashed rgba($white, 0.2);
    margin: 0 12px 8px 12px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}

.empty-group i {
    font-size: 24px;
    opacity: 0.5;
}

.empty-group span {
    font-size: 14px;
    font-style: italic;
}

.validation-success {
    background: rgba(green, 0.6);
    color: white;
    padding: 12px 16px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
    border: 1px solid rgba($green, 0.3);
}

.validation-warning {
    background: rgba(#ffda03, 0.6);
    color: black;
    padding: 12px 16px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
    border: 1px solid rgba($orange, 0.3);
}
</style>

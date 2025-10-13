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
                                <button class="btn generate-invoice" @click="printInvoice" :disabled="!canPrintInvoice">
                                    Print Invoice <i class="fa-solid fa-file-invoice-dollar"></i>
                                </button>
                            <button v-if="isEditMode" class="btn blue" @click="openAttachOrdersModal">
                                Add Orders <i class="fa-solid fa-plus"></i>
                            </button>
                            <button v-if="isEditMode" class="btn delete" @click="openDetachOrdersModal">
                                Remove Orders <i class="fa-solid fa-minus"></i>
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
                <div class="client-info-section" v-if="(invoice && invoice.length > 0) || faktura">
                    <div class="client-info">
                        <div class="client-info-container">
                            <div class="client-label">Client</div>
                            <div class="client-name">{{ invoice[0]?.client || '' }}</div>
                        </div>
                        <!-- Invoice meta (ID, Date, Created by) -->
                        <div class="invoice-info-minimal">
                            <div class="info-item">
                                <span class="info-label">Invoice ID</span>
                                <span class="info-value">{{ fakturaId }}/{{ fakturaYear }}</span>
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
                                        {{ fakturaCreatedDateFormatted }}
                                        <i v-if="isEditMode" class="fas fa-edit edit-icon"></i>
                                    </div>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Created by</span>
                                <span class="info-value">{{ fakturaCreatedByName }}</span>
                            </div>
                            <div class="info-item comment" v-if="invoice[0]?.faktura_comment">
                                <span class="info-label">Comment</span>
                                <span class="info-value">{{invoice[0]?.faktura_comment}}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Payment Deadline (days)</span>
                                <span class="info-value">
                                    <div v-if="isEditMode && editingPaymentDeadline" class="date-edit-container">
                                        <input v-model.number="paymentDeadlineEdit" @keyup.enter="savePaymentDeadline" class="date-input"
                                            type="number" min="0" step="1" />
                                        <button @click="savePaymentDeadline" class="save-btn" title="Save">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button @click="cancelEditPaymentDeadline" class="cancel-btn" title="Cancel">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <div v-else class="date-display" @click="startEditPaymentDeadline">
                                        {{ displayPaymentDeadline }} days
                                        <i v-if="isEditMode" class="fas fa-edit edit-icon"></i>
                                    </div>
                                </span>
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
                                          {{ getDisplayOrderTitle(invoiceData.id, invoiceData.invoice_title) }}
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
                                    <div class="label-cell">Unit</div>
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
                                                    #{{ jobIndex + 1 }} {{ getDisplayJobName(job.id, job.name) }}
                                                </template>
                                            </div>
                                            <div class="value-cell">
                                                <template v-if="isEditMode && editingJobId === job.id">
                                                    <input class="inline-input" type="number" min="0" v-model.number="job.quantity" />
                                                </template>
                                                <template v-else>{{ getDisplayJobQuantity(job.id, job.quantity) }}</template>
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
                                            <input 
                                                class="inline-input" 
                                                :class="{ 'invalid-field': !isMergeGroupFieldValid(grp.__idx, 'title') }"
                                                placeholder="Group title" 
                                                v-model="grp.title" />
                                            <input 
                                                type="number" 
                                                min="0" 
                                                step="0.01" 
                                                class="inline-input" 
                                                :class="{ 'invalid-field': !isMergeGroupFieldValid(grp.__idx, 'quantity') }"
                                                placeholder="Quantity" 
                                                v-model.number="grp.quantity" />
                                            <select class="inline-input unit-select" :value="grp.unit || 'ком'" @input="updateMergeGroupUnit(grp.__idx, $event.target.value)">
                                                <option value="м">м</option>
                                                <option value="м²">м²</option>
                                                <option value="кг">кг</option>
                                                <option value="ком">ком</option>
                                            </select>
                                            <div class="value-cell"></div>
                                            <input 
                                                type="number" 
                                                min="0" 
                                                step="0.01" 
                                                class="inline-input" 
                                                :class="{ 'invalid-field': !isMergeGroupFieldValid(grp.__idx, 'sale_price') }"
                                                placeholder="Sale Price" 
                                                v-model.number="grp.sale_price" />
                                            <div class="value-cell actions">
                                                <button class="btn-save-small" @click="saveEditingMergedGroup(grp.__idx)">Save</button>
                                                <button class="btn-cancel-small" @click="cancelEditingMergedGroup(grp.__idx)">Cancel</button>
                                            </div>
                                        </template>
                                        <template v-else>
                                            <div class="value-cell">{{ grp.title || 'Merged group' }}</div>
                                            <div class="value-cell">{{ formatNumber(grp.quantity || 0) }}</div>
                                            <div class="value-cell">{{ grp.unit || 'м²' }}</div>
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
                                                <div class="value-cell">{{ displayJobUnit(gid) }}</div>
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
                            <button v-if="isEditMode" class="btn green" @click="showTradeItemsModal = true">
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
                                        <span class="article-code" v-if="item.article_code">Article Code: {{ item.article_code }}</span>
                                        <span class="article-name">{{ item.article_name }}</span>
                                    </div>
                                    <div class="item-actions" v-if="isEditMode">
                                        <button v-if="!isEditingTrade[idx]" @click="startEditTrade(idx, item)" class="btn-edit-small" title="Edit Item">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button v-else @click="saveEditTrade(idx, item)" class="btn-save-small" title="Save Changes">
                                            <i class="fas fa-save"></i>
                                        </button>
                                        <button v-if="isEditingTrade[idx]" @click="cancelEditTrade(idx)" class="btn-cancel-small" title="Cancel">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <button @click="removeTradeItem(idx, item)" class="btn-cancel-small" title="Delete Item">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="item-details">
                                    <div class="detail-item">
                                        <label>Quantity:</label>
                                        <template v-if="isEditingTrade[idx]">
                                            <input v-model.number="editTradeForms[idx].quantity" type="number" min="1" class="form-control" />
                                        </template>
                                        <span v-else class="detail-value">{{ item.quantity }}</span>
                                    </div>

                                    <div class="detail-item">
                                        <label>Unit Price:</label>
                                        <template v-if="isEditingTrade[idx]">
                                            <input v-model.number="editTradeForms[idx].unit_price" type="number" step="0.01" min="0" class="form-control" />
                                        </template>
                                        <span v-else class="detail-value">{{ formatNumber(item.unit_price) }} ден.</span>
                                    </div>

                                    <div class="detail-item">
                                        <label>VAT Rate:</label>
                                        <template v-if="isEditingTrade[idx]">
                                            <select v-model.number="editTradeForms[idx].vat_rate" class="form-control">
                                                <option :value="0">0%</option>
                                                <option :value="5">5%</option>
                                                <option :value="10">10%</option>
                                                <option :value="18">18%</option>
                                            </select>
                                        </template>
                                        <span v-else class="detail-value">{{ item.vat_rate }}%</span>
                                    </div>

                                    <div class="detail-item">
                                        <label>Total Price:</label>
                                        <span class="detail-value total-price">{{ formatNumber(getItemTotal(idx, item)) }} ден.</span>
                                    </div>

                                    <div class="detail-item">
                                        <label>VAT Amount:</label>
                                        <span class="detail-value">{{ formatNumber(getItemVat(idx, item)) }} ден.</span>
                                    </div>

                                    <div class="detail-item">
                                        <label>Total with VAT:</label>
                                        <span class="detail-value total-final">{{ formatNumber(getItemTotalWithVat(idx, item)) }} ден.</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="trade-items-total" v-if="tradeItems.length > 0">
                            <strong>Trade Items Total: {{ formatNumber(tradeItemsTotal) }} ден (incl. VAT)</strong>
                        </div>
                    </div>
                </div>

                <!-- Additional Services Section -->
                <div class="additional-services-section" v-if="isEditMode || (additionalServices && additionalServices.length > 0)">
                    <div class="additional-services-container">
                        <div class="additional-services-header">
                            <h4 class="section-title">Additional Services</h4>
                            <button v-if="isEditMode" class="btn green" @click="showAdditionalServicesModal = true">
                                <i class="fas fa-plus"></i> Add Service
                            </button>
                        </div>

                        <div v-if="additionalServices.length === 0" class="no-items">
                            No additional services added yet.
                        </div>

                        <div class="additional-services-list" v-else>
                            <div class="additional-service-card" v-for="(service, idx) in additionalServices" :key="idx">
                                <div class="item-header">
                                    <div class="item-title">
                                        <span class="service-name">{{ service.name }}</span>
                                    </div>
                                    <div class="item-actions" v-if="isEditMode">
                                        <button v-if="!isEditingService[idx]" @click="startEditService(idx, service)" class="btn-edit-small" title="Edit Service">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button v-else @click="saveEditService(idx, service)" class="btn-save-small" title="Save Changes">
                                            <i class="fas fa-save"></i>
                                        </button>
                                        <button v-if="isEditingService[idx]" @click="cancelEditService(idx)" class="btn-cancel-small" title="Cancel">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <button @click="removeAdditionalService(idx, service)" class="btn-cancel-small" title="Delete Service">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="item-details">
                                    <div class="detail-item">
                                        <label>Service Name:</label>
                                        <template v-if="isEditingService[idx]">
                                            <input v-model="editServiceForms[idx].name" type="text" class="form-control" />
                                        </template>
                                        <span v-else class="detail-value">{{ service.name }}</span>
                                    </div>

                                    <div class="detail-item">
                                        <label>Quantity:</label>
                                        <template v-if="isEditingService[idx]">
                                            <input v-model.number="editServiceForms[idx].quantity" type="number" min="0" step="0.01" class="form-control" />
                                        </template>
                                        <span v-else class="detail-value">{{ service.quantity }}</span>
                                    </div>

                                    <div class="detail-item">
                                        <label>Unit:</label>
                                        <template v-if="isEditingService[idx]">
                                            <select v-model="editServiceForms[idx].unit" class="form-control">
                                                <option value="м">м</option>
                                                <option value="м²">м²</option>
                                                <option value="кг">кг</option>
                                                <option value="ком">ком</option>
                                                <option value="час">час</option>
                                                <option value="ед">ед</option>
                                            </select>
                                        </template>
                                        <span v-else class="detail-value">{{ service.unit }}</span>
                                    </div>

                                    <div class="detail-item">
                                        <label>Unit Price:</label>
                                        <template v-if="isEditingService[idx]">
                                            <input v-model.number="editServiceForms[idx].sale_price" type="number" step="0.01" min="0" class="form-control" />
                                        </template>
                                        <span v-else class="detail-value">{{ formatNumber(service.sale_price) }} ден.</span>
                                    </div>

                                    <div class="detail-item">
                                        <label>VAT Rate:</label>
                                        <template v-if="isEditingService[idx]">
                                            <select v-model.number="editServiceForms[idx].vat_rate" class="form-control">
                                                <option :value="0">0%</option>
                                                <option :value="5">5%</option>
                                                <option :value="10">10%</option>
                                                <option :value="18">18%</option>
                                            </select>
                                        </template>
                                        <span v-else class="detail-value">{{ service.vat_rate }}%</span>
                                    </div>

                                    <div class="detail-item">
                                        <label>Total Price:</label>
                                        <span class="detail-value total-price">{{ formatNumber(getServiceTotal(idx, service)) }} ден.</span>
                                    </div>

                                    <div class="detail-item">
                                        <label>VAT Amount:</label>
                                        <span class="detail-value">{{ formatNumber(getServiceVat(idx, service)) }} ден.</span>
                                    </div>

                                    <div class="detail-item">
                                        <label>Total with VAT:</label>
                                        <span class="detail-value total-final">{{ formatNumber(getServiceTotalWithVat(idx, service)) }} ден.</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="additional-services-total" v-if="additionalServices.length > 0">
                            <strong>Additional Services Total: {{ formatNumber(additionalServicesTotal) }} ден (incl. VAT)</strong>
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
        <!-- Add Trade Item Modal -->
        <div v-if="showTradeItemsModal" class="modal-overlay" @click="closeAddTradeItemModal">
            <div class="modal-content" @click.stop>
                <div class="modal-header">
                    <h3>Add Trade Item</h3>
                    <button @click="closeAddTradeItemModal" class="close-btn">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Select Article:</label>
                        <select v-model="selectedArticle" class="form-control">
                            <option value="">Choose an article...</option>
                            <option v-for="article in availableTradeArticles" :key="article.id" :value="article">
                                {{ article.article.name }} ({{ article.article.code }}) - Stock: {{ article.quantity }} - Price: {{ formatNumber(article.selling_price || 0) }} ден
                            </option>
                        </select>
                    </div>
                    <div class="form-group" v-if="selectedArticle">
                        <label>Quantity:</label>
                        <input type="number" v-model.number="tradeItemQuantity" :max="selectedArticle.quantity" min="1" class="form-control">
                        <small class="form-help-text">Available: {{ selectedArticle.quantity }}</small>
                    </div>
                    <div class="form-group" v-if="selectedArticle">
                        <label>Unit Price:</label>
                        <input type="number" v-model.number="tradeItemPrice" step="0.01" min="0" class="form-control">
                    </div>
                    <div class="form-group" v-if="selectedArticle">
                        <label>VAT Rate:</label>
                        <select v-model.number="tradeItemVatRate" class="form-control">
                            <option :value="0">0%</option>
                            <option :value="5">5%</option>
                            <option :value="10">10%</option>
                            <option :value="18">18%</option>
                        </select>
                    </div>
                    <div class="form-group" v-if="selectedArticle && tradeItemQuantity && (tradeItemPrice || tradeItemPrice===0)">
                        <div class="price-breakdown">
                            <div>Subtotal: {{ formatNumber(tradeItemQuantity * tradeItemPrice) }} ден</div>
                            <div>VAT ({{ tradeItemVatRate }}%): {{ formatNumber((tradeItemQuantity * tradeItemPrice) * (tradeItemVatRate/100)) }} ден</div>
                            <div><strong>Total: {{ formatNumber((tradeItemQuantity * tradeItemPrice) * (1 + tradeItemVatRate/100)) }} ден</strong></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button @click="addTradeItem" :disabled="!canAddTradeItem()" class="btn green">Add Item</button>
                    <button @click="closeAddTradeItemModal" class="btn delete">Cancel</button>
                </div>
            </div>
        </div>
        <!-- Attach Orders Modal -->
        <div v-if="showAttachOrders" class="modal-overlay" @click="closeAttachOrdersModal">
            <div class="modal-content wide-modal" @click.stop>
                <div class="modal-header">
                    <h3>Add Orders to Faktura</h3>
                    <button @click="closeAttachOrdersModal" class="close-btn">&times;</button>
                </div>
                <div class="modal-body">
                        <div class="form-group">
                            <label>Search and select uninvoiced orders<span v-if="attachClientName"> (Client: {{ attachClientName }})</span>:</label>
                            <input v-model="attachSearch" class="form-control" placeholder="Search by title or #id" />
                        </div>
                    <div class="form-group flex" style="gap:8px; align-items:center;">
                        <button class="btn blue" @click="selectAllAttachVisible" :disabled="filteredAttachableOrders.length===0">Select all visible</button>
                        <button class="btn delete" @click="clearAttachSelection" :disabled="selectedAttachOrderIds.length===0">Clear selection</button>
                    </div>
                    <div class="form-group select-list" style="max-height: 320px; overflow-y: auto;">
                        <div v-if="attachLoading">Loading...</div>
                        <div v-else>
                            <div v-for="ord in filteredAttachableOrders" :key="ord.id" class="flex items-center gap-2 select-row" :class="{selected: selectedAttachOrderIds.includes(ord.id)}" @click="toggleAttachSelection(ord)" style="padding: 6px 0;">
                                <input type="checkbox" :checked="selectedAttachOrderIds.includes(ord.id)" @click.stop @change="toggleAttachSelection(ord)" />
                                <div class="bold">#{{ ord.id }}</div>
                                <div class="select-title" :title="ord.invoice_title">{{ truncateTitle(ord.invoice_title, 40) }}</div>
                                <div class="ml-auto">{{ ord.client?.name || ord.client }}</div>
                                <div v-if="selectedAttachOrderIds.includes(ord.id)" class="selected-badge">✓ Selected</div>
                            </div>
                            <div v-if="filteredAttachableOrders.length === 0">No matching orders.</div>
                        </div>
                    </div>
                    
                    <!-- Pagination Controls -->
                    <div v-if="attachPagination.lastPage > 1" class="pagination-controls" style="margin-top: 15px; padding: 10px; border-top: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center;">
                        <div class="pagination-info">
                            <span>Page {{ attachPagination.currentPage }} of {{ attachPagination.lastPage }}</span>
                            <span style="margin-left: 10px;">({{ attachPagination.total }} total orders)</span>
                        </div>
                        <div class="pagination-buttons" style="display: flex; gap: 8px;">
                            <button @click="prevAttachPage" :disabled="attachPagination.currentPage <= 1" class="btn btn-sm">
                                <i class="fa-solid fa-chevron-left"></i> Previous
                            </button>
                            <button @click="nextAttachPage" :disabled="attachPagination.currentPage >= attachPagination.lastPage" class="btn btn-sm">
                                Next <i class="fa-solid fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button @click="confirmAttachOrders" :disabled="selectedAttachOrderIds.length === 0" class="btn green">Attach Selected ({{ selectedAttachOrderIds.length }})</button>
                    <button @click="closeAttachOrdersModal" class="btn delete">Cancel</button>
                </div>
            </div>
        </div>
        <!-- Detach Orders Modal -->
        <div v-if="showDetachOrders" class="modal-overlay" @click="closeDetachOrdersModal">
            <div class="modal-content wide-modal" @click.stop>
                <div class="modal-header">
                    <h3>Remove Orders from Faktura</h3>
                    <button @click="closeDetachOrdersModal" class="close-btn">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Search current faktura orders:</label>
                        <input v-model="detachSearch" class="form-control" placeholder="Search by title or #id" />
                    </div>
                    <div class="form-group flex" style="gap:8px; align-items:center;">
                        <button class="btn blue" @click="selectAllDetachVisible" :disabled="filteredDetachableOrders.length===0">Select all visible</button>
                        <button class="btn delete" @click="clearDetachSelection" :disabled="selectedDetachOrderIds.length===0">Clear selection</button>
                    </div>
                    <div class="form-group select-list" style="max-height: 320px; overflow-y: auto;">
                        <div v-for="ord in filteredDetachableOrders" :key="ord.id" class="flex items-center gap-2 select-row" :class="{selected: selectedDetachOrderIds.includes(ord.id)}" @click="toggleDetachSelection(ord)" style="padding: 6px 0;">
                            <input type="checkbox" :checked="selectedDetachOrderIds.includes(ord.id)" @click.stop @change="toggleDetachSelection(ord)" />
                            <div class="bold">#{{ ord.id }}</div>
                            <div class="select-title" :title="ord.invoice_title">{{ truncateTitle(ord.invoice_title, 40) }}</div>
                            <div class="ml-auto">{{ ord.client }}</div>
                        </div>
                        <div v-if="filteredDetachableOrders.length === 0">No matching orders.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button @click="confirmDetachOrders" :disabled="selectedDetachOrderIds.length === 0" class="btn delete">Remove Selected ({{ selectedDetachOrderIds.length }})</button>
                    <button @click="closeDetachOrdersModal" class="btn blue">Cancel</button>
                </div>
            </div>
        </div>

        <!-- Additional Services Modal -->
        <div v-if="showAdditionalServicesModal" class="modal-overlay" @click="closeAdditionalServicesModal">
            <div class="modal-content" @click.stop>
                <div class="modal-header">
                    <h3>Add Additional Service</h3>
                    <button @click="closeAdditionalServicesModal" class="close-btn">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Service Name:</label>
                        <input type="text" v-model="newServiceName" class="form-control" placeholder="Enter service name...">
                    </div>
                    <div class="form-group">
                        <label>Quantity:</label>
                        <input type="number" v-model.number="newServiceQuantity" min="0" step="0.01" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Unit:</label>
                        <select v-model="newServiceUnit" class="form-control">
                            <option value="м">м</option>
                            <option value="м²">м²</option>
                            <option value="кг">кг</option>
                            <option value="ком">ком</option>
                            <option value="час">час</option>
                            <option value="ед">ед</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Unit Price:</label>
                        <input type="number" v-model.number="newServicePrice" step="0.01" min="0" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>VAT Rate:</label>
                        <select v-model.number="newServiceVatRate" class="form-control">
                            <option :value="0">0%</option>
                            <option :value="5">5%</option>
                            <option :value="10">10%</option>
                            <option :value="18">18%</option>
                        </select>
                    </div>
                    <div class="form-group" v-if="newServiceName && newServiceQuantity && newServicePrice">
                        <div class="price-breakdown">
                            <div>Subtotal: {{ formatNumber(newServiceQuantity * newServicePrice) }} ден</div>
                            <div>VAT ({{ newServiceVatRate }}%): {{ formatNumber((newServiceQuantity * newServicePrice) * (newServiceVatRate/100)) }} ден</div>
                            <div><strong>Total: {{ formatNumber((newServiceQuantity * newServicePrice) * (1 + newServiceVatRate/100)) }} ден</strong></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button @click="addAdditionalService" :disabled="!canAddAdditionalService" class="btn green">Add Service</button>
                    <button @click="closeAdditionalServicesModal" class="btn delete">Cancel</button>
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
        additionalServices: Array,
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
            const items = Array.isArray(this.tradeItems) ? this.tradeItems : [];
            return items.reduce((sum, it) => sum + Number(it?.total_price ?? 0) + Number(it?.vat_amount ?? 0), 0);
        },
        additionalServicesTotal() {
            const services = Array.isArray(this.additionalServices) ? this.additionalServices : [];
            return services.reduce((total, service) => {
                const subtotal = Number(service.quantity || 0) * Number(service.sale_price || 0);
                const vatAmount = subtotal * (Number(service.vat_rate || 0) / 100);
                return total + subtotal + vatAmount;
            }, 0);
        },
        canAddAdditionalService() {
            return this.newServiceName && this.newServiceName.trim() && 
                   this.newServiceQuantity > 0 && 
                   this.newServicePrice >= 0;
        },
        fakturaId() {
            // Prefer faktura prop, fallback to first invoice's fakturaId
            return this.faktura?.id ?? this.invoice?.[0]?.fakturaId ?? '';
        },
        fakturaYear() {
            const created = this.faktura?.created_at || this.invoice?.[0]?.created || null;
            if (!created) return '';
            try { return new Date(created).toLocaleDateString('en-US', { year: 'numeric' }); } catch (_) { return ''; }
        },

        fakturaCreatedDateFormatted() {
            const created = this.faktura?.created_at || null;
            if (!created) return '';
            
            try { 
                // Use the same approach as AllInvoices.vue for consistency
                return new Date(created).toLocaleDateString('en-US', { year: 'numeric', month: '2-digit', day: '2-digit' });
            } catch (_) { return ''; }
        },
        fakturaCreatedByName() {
            // If faktura prop includes created_by relation, prefer it; else fallback if invoice embeds createdBy string
            return this.faktura?.created_by_name || this.invoice?.[0]?.createdBy || '';
        },
        displayPaymentDeadline() {
            // Use override if set, otherwise fall back to client card statement default
            console.log('displayPaymentDeadline - faktura:', this.faktura);
            console.log('displayPaymentDeadline - override value:', this.faktura?.payment_deadline_override);
            console.log('displayPaymentDeadline - override type:', typeof this.faktura?.payment_deadline_override);
            
            if (this.faktura?.payment_deadline_override !== null && this.faktura?.payment_deadline_override !== undefined) {
                console.log('Using override:', this.faktura.payment_deadline_override);
                return this.faktura.payment_deadline_override;
            }
            
            const clientDeadline = this.invoice?.[0]?.client?.client_card_statement?.payment_deadline;
            console.log('Using client default:', clientDeadline);
            return clientDeadline !== null && clientDeadline !== undefined && clientDeadline !== '' ? parseInt(clientDeadline) : 30;
        },
        allSelectedAttachableOrders() {
            // This will show selected orders from any page
            // We need to fetch them separately or store them globally
            // For now, we'll show selected orders that are in our current attachableOrders list
            return this.attachableOrders.filter(o => this.selectedAttachOrderIds.includes(o.id));
        },
        filteredAttachableOrders() {
            const q = (this.attachSearch || '').toLowerCase().trim();
            const base = this.attachableOrders;
            const client = this.attachClientName || (this.invoice?.[0]?.client?.name || this.invoice?.[0]?.client || '');
            const byClient = client ? base.filter(o => (o.client?.name || o.client || '') === client) : base;
            let filtered = byClient;
            
            // Apply search filter if provided
            if (q) {
                filtered = byClient.filter(o => String(o.id).includes(q) || (o.invoice_title || '').toLowerCase().includes(q));
            }
            
            // Get selected orders from all pages (not just current page)
            const selectedFromAllPages = this.allSelectedOrders.filter(o => {
                // Apply same client filter
                const orderClient = o.client?.name || o.client || '';
                const clientMatch = !client || orderClient === client;
                
                // Apply same search filter
                const searchMatch = !q || String(o.id).includes(q) || (o.invoice_title || '').toLowerCase().includes(q);
                
                return clientMatch && searchMatch;
            });
            
            // Get non-selected orders from current page
            const nonSelectedOrders = filtered.filter(o => !this.selectedAttachOrderIds.includes(o.id));
            
            // Return selected orders from all pages first, then non-selected orders from current page
            return [...selectedFromAllPages, ...nonSelectedOrders];
        },
        filteredDetachableOrders() {
            const list = Array.isArray(this.invoice) ? this.invoice : [];
            const mapped = list.map(o => ({ id: o.id, invoice_title: o.invoice_title, client: o.client?.name || o.client || '' }));
            const q = (this.detachSearch || '').toLowerCase().trim();
            if (!q) return mapped;
            return mapped.filter(o => String(o.id).includes(q) || (o.invoice_title || '').toLowerCase().includes(q));
        },
        hasValidMergeGroups() {
            const mergeGroups = this.faktura?.merge_groups || [];
            if (!mergeGroups || mergeGroups.length === 0) {
                return true; // No merge groups means validation passes
            }
            
            return mergeGroups.every(group => {
                const hasTitle = group.title && group.title.trim() !== '';
                const hasQuantity = group.quantity && Number(group.quantity) > 0;
                const hasSalePrice = group.sale_price && Number(group.sale_price) > 0;
                
                return hasTitle && hasQuantity && hasSalePrice;
            });
        },
        canPrintInvoice() {
            return this.hasValidMergeGroups;
        }
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
            editingPaymentDeadline: false,
            paymentDeadlineEdit: null,
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
            originalMergeGroups: [],
            // Trade Items edit/add state
            showTradeItemsModal: false,
            availableTradeArticles: [],
            selectedArticle: null,
            tradeItemQuantity: 1,
            tradeItemPrice: 0,
            tradeItemVatRate: 18,
            isEditingTrade: {},
            editTradeForms: {},
            // Attach orders state
            showAttachOrders: false,
            attachLoading: false,
            attachableOrders: [],
            attachSearch: '',
            selectedAttachOrderIds: [],
            attachClientName: '',
            attachPagination: {
                currentPage: 1,
                lastPage: 1,
                perPage: 10,
                total: 0
            },
            // Store selected orders from all pages
            allSelectedOrders: [],
            // Detach orders state
            showDetachOrders: false,
            detachSearch: '',
            selectedDetachOrderIds: [],
            // Additional Services state
            showAdditionalServicesModal: false,
            newServiceName: '',
            newServiceQuantity: 1,
            newServiceUnit: 'ком',
            newServicePrice: 0,
            newServiceVatRate: 18,
            isEditingService: {},
            editServiceForms: {},
            // Track original values for override detection
            originalOrderTitles: {},
            originalJobNames: {},
            originalJobQuantities: {},
            // Track current overrides
            currentOverrides: {
                order_titles: {},
                job_names: {},
                job_quantities: {}
            }
        }
    },
    mounted() {
        // Component mounted
        // Initialize original merge groups for change tracking
        this.originalMergeGroups = JSON.parse(JSON.stringify(this.faktura.merge_groups || []));
        this.loadTradeArticles();
        this.initializeJobUnits();
        this.initializeOverrides();
    },
    methods: {
        // Helper method to get the faktura date consistently
        getFakturaDate() {
            return this.faktura?.created_at || null;
        },
        // Helper method to extract date in YYYY-MM-DD format consistently from faktura
        getDateForPayload() {
            const created = this.getFakturaDate();
            if (!created) return new Date().toISOString().split('T')[0];
            
            try {
                // Use the same Date conversion as the display to ensure consistency
                const date = new Date(created);
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            } catch (_) {
                return new Date().toISOString().split('T')[0];
            }
        },
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
        displayJobUnit(id) { const j = this.getJobById(id); return j ? (j.unit || 'ком') : 'ком'; },
        // Initialize units for all jobs
        initializeJobUnits() {
            if (this.invoice) {
                // Handle both array and object formats
                const invoices = Array.isArray(this.invoice) ? this.invoice : Object.values(this.invoice);
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
        // Update job unit
        async updateJobUnit(job, newUnit) {
            this.$set ? this.$set(job, 'unit', newUnit) : (job.unit = newUnit);
            
            // Find the invoice that contains this job
            let invoiceId = null;
            for (const invoiceData of (this.invoice || [])) {
                if (invoiceData.jobs && invoiceData.jobs.some(j => j.id === job.id)) {
                    invoiceId = invoiceData.id;
                    break;
                }
            }
            
            if (invoiceId) {
                try {
                    await axios.put(`/invoice/${invoiceId}/job/${job.id}/unit`, {
                        unit: newUnit
                    });
                } catch (error) {
                    console.error('Failed to update job unit:', error);
                    this.$toast?.error?.('Failed to save unit change');
                }
            }
        },
        // Update merge group unit
        updateMergeGroupUnit(groupIndex, newUnit) {
            this.$set ? this.$set(this.faktura.merge_groups[groupIndex], 'unit', newUnit) : (this.faktura.merge_groups[groupIndex].unit = newUnit);
        },
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
            // Use the display value (which includes overrides) instead of original
            this.titleEdits[invoiceData.id] = this.getDisplayOrderTitle(invoiceData.id, invoiceData.invoice_title);
            
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
                // Store the override instead of updating the original data
                this.currentOverrides.order_titles[invoiceData.id] = this.titleEdits[invoiceData.id];
                
                // Save overrides to faktura
                const saved = await this.saveOverrides();
                if (saved) {
                    this.cancelEditTitle(invoiceData);
                }
            } catch (error) {
                console.error('Error saving title override:', error);
                this.toast.error('Failed to save title changes');
                this.cancelEditTitle(invoiceData);
            }
        },
        cancelEditTitle(invoiceData) {
            this.editingTitle[invoiceData.id] = false;
            delete this.titleEdits[invoiceData.id];
        },
        // Override management methods
        initializeOverrides() {
            // Load existing overrides from faktura
            if (this.faktura?.faktura_overrides) {
                this.currentOverrides = { ...this.faktura.faktura_overrides };
            }
            
            // Initialize original values
            if (this.invoice) {
                const invoices = Array.isArray(this.invoice) ? this.invoice : Object.values(this.invoice);
                invoices.forEach(invoice => {
                    if (invoice.id && invoice.invoice_title) {
                        this.originalOrderTitles[invoice.id] = invoice.invoice_title;
                    }
                    if (invoice.jobs && Array.isArray(invoice.jobs)) {
                        invoice.jobs.forEach(job => {
                            if (job.id) {
                                this.originalJobNames[job.id] = job.name;
                                this.originalJobQuantities[job.id] = job.quantity;
                            }
                        });
                    }
                });
            }
        },
        // Get display value for order title (override if exists, otherwise original)
        getDisplayOrderTitle(orderId, originalTitle) {
            return this.currentOverrides.order_titles[orderId] || originalTitle;
        },
        // Get display value for job name (override if exists, otherwise original)
        getDisplayJobName(jobId, originalName) {
            return this.currentOverrides.job_names[jobId] || originalName;
        },
        // Get display value for job quantity (override if exists, otherwise original)
        getDisplayJobQuantity(jobId, originalQuantity) {
            return this.currentOverrides.job_quantities[jobId] !== undefined ? 
                   this.currentOverrides.job_quantities[jobId] : originalQuantity;
        },
        // Save overrides to faktura
        async saveOverrides() {
            try {
                const response = await axios.put(`/faktura/${this.fakturaId}/overrides`, {
                    faktura_overrides: this.currentOverrides
                });
                
                if (response.data.success) {
                    this.toast.success('Changes saved successfully');
                    return true;
                }
            } catch (error) {
                console.error('Error saving overrides:', error);
                this.toast.error('Failed to save changes');
                return false;
            }
        },
        startEditDate() {
            if (!this.isEditMode) return;
            
            this.editingDate = true;
            // Use the same date logic for consistency
            this.dateEdit = this.getDateForPayload();
            
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
                    // Update the faktura data
                    if (this.faktura) {
                        this.faktura.created_at = this.dateEdit;
                    }
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
        startEditPaymentDeadline() {
            if (!this.isEditMode) return;
            this.editingPaymentDeadline = true;
            this.paymentDeadlineEdit = this.displayPaymentDeadline;
            this.$nextTick(() => {
                const input = document.querySelector('.date-input[type="number"]');
                if (input) input.focus();
            });
        },
        async savePaymentDeadline() {
            if (this.paymentDeadlineEdit === null || this.paymentDeadlineEdit === '') {
                this.cancelEditPaymentDeadline();
                return;
            }
            try {
                const response = await axios.put(`/invoice/${this.faktura.id}/payment-deadline`, {
                    payment_deadline_override: parseInt(this.paymentDeadlineEdit)
                });
                if (response.data.success) {
                    this.faktura.payment_deadline_override = parseInt(this.paymentDeadlineEdit);
                    this.toast.success('Payment deadline updated successfully');
                }
                this.cancelEditPaymentDeadline();
            } catch (error) {
                console.error('Error updating payment deadline:', error);
                this.toast.error('Failed to update payment deadline');
                this.cancelEditPaymentDeadline();
            }
        },
        cancelEditPaymentDeadline() {
            this.editingPaymentDeadline = false;
            this.paymentDeadlineEdit = null;
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
        async loadTradeArticles() {
            try {
                const response = await axios.get('/trade-articles');
                this.availableTradeArticles = response.data.data || [];
            } catch (error) {
                console.error('Error loading trade articles:', error);
            }
        },
        async openAttachOrdersModal() {
            this.showAttachOrders = true;
            this.attachSearch = '';
            this.selectedAttachOrderIds = [];
            this.allSelectedOrders = [];
            this.attachPagination.currentPage = 1;
            // Always refresh full list to avoid stale filtered state
            await this.fetchAttachableOrders();
            const first = (this.invoice || [])[0];
            this.attachClientName = first?.client?.name || first?.client || '';
        },
        openDetachOrdersModal() {
            this.showDetachOrders = true;
        },
        closeDetachOrdersModal() {
            this.showDetachOrders = false;
            this.detachSearch = '';
            this.selectedDetachOrderIds = [];
        },
        async confirmDetachOrders() {
            try {
                if (!this.faktura || !this.faktura.id) {
                    this.toast.error('Missing faktura');
                    return;
                }
                if (!this.selectedDetachOrderIds.length) return;
                await axios.put(`/invoice/${this.faktura.id}/detach-orders`, { orders: this.selectedDetachOrderIds });
                const set = new Set(this.selectedDetachOrderIds);
                const remaining = (this.invoice || []).filter(o => !set.has(o.id));
                // Replace array contents in place to avoid reassigning prop
                this.invoice.splice(0, this.invoice.length, ...remaining);
                this.toast.success('Orders removed');
                this.closeDetachOrdersModal();
            } catch (e) {
                const msg = e?.response?.data?.error || e?.message || 'Failed to remove orders';
                console.error('Failed to detach orders', msg, e);
                this.toast.error(msg);
            }
        },
        closeAttachOrdersModal() {
            this.showAttachOrders = false;
            this.attachSearch = '';
            this.selectedAttachOrderIds = [];
            this.attachClientName = '';
        },
        async fetchAttachableOrders(page = 1) {
            try {
                this.attachLoading = true;
                const res = await axios.get('/api/notInvoiced/filtered', { 
                    params: { 
                        page: page, 
                        per_page: this.attachPagination.perPage,
                        sortOrder: 'desc' 
                    } 
                });
                
                // Handle paginated response
                if (res.data && res.data.data) {
                    // Laravel pagination response
                    this.attachableOrders = (Array.isArray(res.data.data) ? res.data.data : []).map(r => ({
                        id: r.id,
                        invoice_title: r.invoice_title || r.title || `Order ${r.id}`,
                        client: r.client || r.client?.name || (r.client_name || ''),
                    }));
                    
                    // Update pagination info
                    this.attachPagination.currentPage = res.data.current_page || 1;
                    this.attachPagination.lastPage = res.data.last_page || 1;
                    this.attachPagination.total = res.data.total || 0;
                } else {
                    // Fallback for non-paginated response
                    const data = res?.data?.data || res?.data || [];
                    this.attachableOrders = (Array.isArray(data) ? data : []).map(r => ({
                        id: r.id,
                        invoice_title: r.invoice_title || r.title || `Order ${r.id}`,
                        client: r.client || r.client?.name || (r.client_name || ''),
                    }));
                }
            } catch (e) {
                console.error('Failed loading uninvoiced orders', e);
                this.toast.error('Failed to load uninvoiced orders');
            } finally {
                this.attachLoading = false;
            }
        },
        async confirmAttachOrders() {
            try {
                if (!this.faktura || !this.faktura.id) {
                    this.toast.error('Missing faktura');
                    return;
                }
                if (!this.selectedAttachOrderIds.length) return;
                const res = await axios.put(`/invoice/${this.faktura.id}/attach-orders`, { orders: this.selectedAttachOrderIds });
                const appended = (res?.data?.invoices || []).map(r => ({
                    id: r.id,
                    invoice_title: r.invoice_title,
                    client: r.client,
                    jobs: r.jobs || [],
                    user: r.user,
                    start_date: r.start_date,
                    end_date: r.end_date,
                    status: r.status
                }));
                this.invoice.push(...appended);
                // If faktura header fields were empty, hydrate from faktura prop
                // Keep existing created date if already set
                if (!this.fakturaCreatedDateFormatted) {
                    // Force computed to recompute; assigning same object is fine
                    this.$forceUpdate?.();
                }
                this.toast.success('Orders attached');
                this.closeAttachOrdersModal();
            } catch (e) {
                console.error('Failed to attach orders', e);
                this.toast.error('Failed to attach orders');
            }
        },
        toggleAttachSelection(ord) {
            const ids = new Set(this.selectedAttachOrderIds);
            let currentClient = this.attachClientName || (this.invoice?.[0]?.client?.name || this.invoice?.[0]?.client || '');
            const ordClient = ord?.client?.name || ord?.client || '';
            // If no client locked yet (empty faktura), lock to first selected client's name
            if (!currentClient && ordClient) {
                this.attachClientName = ordClient;
                currentClient = ordClient;
            }
            if (currentClient && ordClient && ordClient !== currentClient) {
                this.toast.error('You can only add orders from the same client.');
                return;
            }
            if (ids.has(ord.id)) ids.delete(ord.id); else ids.add(ord.id);
            const next = Array.from(ids);
            this.selectedAttachOrderIds = next;
            
            // Update global selected orders array
            if (ids.has(ord.id)) {
                // Adding order - add to global array if not already there
                const exists = this.allSelectedOrders.some(o => o.id === ord.id);
                if (!exists) {
                    this.allSelectedOrders.push(ord);
                }
            } else {
                // Removing order - remove from global array
                this.allSelectedOrders = this.allSelectedOrders.filter(o => o.id !== ord.id);
            }
            
            if (next.length === 0) {
                // If last item was deselected, reset client lock and search
                this.clearAttachSelection();
            }
        },
        selectAllAttachVisible() {
            const currentClient = this.attachClientName || (this.invoice?.[0]?.client?.name || this.invoice?.[0]?.client || '');
            const ids = this.filteredAttachableOrders.map(o => o.id);
            this.selectedAttachOrderIds = ids;
            
            // Add visible orders to global selected array
            this.filteredAttachableOrders.forEach(order => {
                const exists = this.allSelectedOrders.some(o => o.id === order.id);
                if (!exists) {
                    this.allSelectedOrders.push(order);
                }
            });
        },
        clearAttachSelection() { 
            this.selectedAttachOrderIds = []; 
            this.attachClientName = ''; 
            this.allSelectedOrders = [];
        },
        async loadAttachPage(page) {
            if (page >= 1 && page <= this.attachPagination.lastPage) {
                await this.fetchAttachableOrders(page);
            }
        },
        async nextAttachPage() {
            if (this.attachPagination.currentPage < this.attachPagination.lastPage) {
                await this.loadAttachPage(this.attachPagination.currentPage + 1);
            }
        },
        async prevAttachPage() {
            if (this.attachPagination.currentPage > 1) {
                await this.loadAttachPage(this.attachPagination.currentPage - 1);
            }
        },
        toggleDetachSelection(ord) {
            const ids = new Set(this.selectedDetachOrderIds);
            if (ids.has(ord.id)) ids.delete(ord.id); else ids.add(ord.id);
            const next = Array.from(ids);
            this.selectedDetachOrderIds = next;
            if (next.length === 0) {
                this.clearDetachSelection();
            }
        },
        selectAllDetachVisible() {
            this.selectedDetachOrderIds = this.filteredDetachableOrders.map(o => o.id);
        },
        clearDetachSelection() { this.selectedDetachOrderIds = []; },
        truncateTitle(title, max = 40) {
            const t = String(title || '');
            if (t.length <= max) return t;
            return t.slice(0, max - 1) + '…';
        },
        closeAddTradeItemModal() {
            this.showTradeItemsModal = false;
            this.selectedArticle = null;
            this.tradeItemQuantity = 1;
            this.tradeItemPrice = 0;
            this.tradeItemVatRate = 18;
        },
        canAddTradeItem() {
            return !!this.selectedArticle && this.tradeItemQuantity > 0 && this.tradeItemPrice >= 0;
        },
        async addTradeItem() {
            if (!this.canAddTradeItem()) return;
            try {
                if (!this.faktura || !this.faktura.id) {
                    this.toast.error('Cannot add item: missing faktura');
                    return;
                }
                const subtotal = Number(this.tradeItemQuantity || 0) * Number(this.tradeItemPrice || 0);
                const vatAmount = subtotal * (Number(this.tradeItemVatRate || 0) / 100);
                const payload = {
                    article_id: this.selectedArticle?.article?.id ?? null,
                    quantity: this.tradeItemQuantity,
                    unit_price: this.tradeItemPrice,
                    total_price: subtotal,
                    vat_rate: this.tradeItemVatRate,
                    vat_amount: vatAmount,
                };
                const res = await axios.post(`/invoice/${this.faktura.id}/trade-items`, payload);
                // Backend returns created item? If not, append locally from payload
                const created = res?.data?.trade_item || {
                    id: Date.now(),
                    article_id: payload.article_id,
                    article_name: this.selectedArticle?.article?.name || '',
                    article_code: this.selectedArticle?.article?.code || '',
                    quantity: payload.quantity,
                    unit_price: payload.unit_price,
                    total_price: payload.total_price,
                    vat_rate: payload.vat_rate,
                    vat_amount: payload.vat_amount,
                };
                this.tradeItems.push(created);
                this.closeAddTradeItemModal();
                this.toast.success('Trade item added');
            } catch (e) {
                console.error(e);
                this.toast.error('Failed to add trade item');
            }
        },
        startEditTrade(index, item) {
            this.$set ? this.$set(this.isEditingTrade, index, true) : (this.isEditingTrade[index] = true);
            this.$set ? this.$set(this.editTradeForms, index, {
                quantity: Number(item.quantity || 0),
                unit_price: Number(item.unit_price || 0),
                vat_rate: Number(item.vat_rate || 0)
            }) : (this.editTradeForms[index] = { quantity: Number(item.quantity || 0), unit_price: Number(item.unit_price || 0), vat_rate: Number(item.vat_rate || 0) });
        },
        cancelEditTrade(index) {
            this.isEditingTrade[index] = false;
            delete this.editTradeForms[index];
        },
        async saveEditTrade(index, item) {
            try {
                const form = this.editTradeForms[index];
                if (!form) return;
                if (!this.faktura || !this.faktura.id) {
                    this.toast.error('Cannot update item: missing faktura');
                    return;
                }
                const subtotal = Number(form.quantity || 0) * Number(form.unit_price || 0);
                const vatAmount = subtotal * (Number(form.vat_rate || 0) / 100);
                const payload = {
                    quantity: form.quantity,
                    unit_price: form.unit_price,
                    total_price: subtotal,
                    vat_rate: form.vat_rate,
                    vat_amount: vatAmount,
                };
                await axios.put(`/invoice/${this.faktura.id}/trade-items/${item.id}`, payload);
                item.quantity = payload.quantity;
                item.unit_price = payload.unit_price;
                item.total_price = payload.total_price;
                item.vat_rate = payload.vat_rate;
                item.vat_amount = payload.vat_amount;
                this.isEditingTrade[index] = false;
                delete this.editTradeForms[index];
                this.toast.success('Trade item updated');
            } catch (e) {
                console.error(e);
                this.toast.error('Failed to update trade item');
            }
        },
        async removeTradeItem(index, item) {
            try {
                if (!this.faktura || !this.faktura.id) {
                    this.toast.error('Cannot delete item: missing faktura');
                    return;
                }
                await axios.delete(`/invoice/${this.faktura.id}/trade-items/${item.id}`);
                this.tradeItems.splice(index, 1);
                this.toast.success('Trade item deleted');
            } catch (e) {
                console.error(e);
                this.toast.error('Failed to delete trade item');
            }
        },
        getItemTotal(index, item) {
            const form = this.editTradeForms[index];
            const q = form ? Number(form.quantity || 0) : Number(item.quantity || 0);
            const u = form ? Number(form.unit_price || 0) : Number(item.unit_price || 0);
            return q * u;
        },
        getItemVat(index, item) {
            const form = this.editTradeForms[index];
            const rate = form ? Number(form.vat_rate || 0) : Number(item.vat_rate || 0);
            return this.getItemTotal(index, item) * (rate / 100);
        },
        getItemTotalWithVat(index, item) {
            const total = this.getItemTotal(index, item);
            const vat = this.getItemVat(index, item);
            return total + vat;
        },
        startEditingJob(job) {
            this.editingJobId = job.id;
            
            // Update job object with display values (which include overrides) for editing
            job.name = this.getDisplayJobName(job.id, job.name);
            job.quantity = this.getDisplayJobQuantity(job.id, job.quantity);
            
            // snapshot current display values (which include overrides) for cancel
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
                // Store unit locally (frontend-only field)
                const localUnit = job.unit;
                
                // For faktura editing, we don't want to persist changes to the original job
                // Instead, we'll store the changes as overrides
                // Only persist salePrice changes to the original job (as this affects pricing)
                if (job.salePrice !== this.originalJobSnapshots[job.id]?.salePrice) {
                    const response = await axios.put(`/jobs/${job.id}`, {
                        salePrice: job.salePrice
                    });
                    
                    if (response?.data?.job) {
                        job.salePrice = response.data.job.salePrice;
                        this.onJobUpdated?.(response.data.job);
                    }
                }
                
                // Store name and quantity changes as overrides
                if (job.name !== this.originalJobSnapshots[job.id]?.name) {
                    this.currentOverrides.job_names[job.id] = job.name;
                }
                if (job.quantity !== this.originalJobSnapshots[job.id]?.quantity) {
                    this.currentOverrides.job_quantities[job.id] = job.quantity;
                }
                
                // Save overrides to faktura
                const saved = await this.saveOverrides();
                if (saved) {
                    // Keep the frontend-only unit field
                    job.unit = localUnit;
                    this.editingJobId = null;
                    delete this.originalJobSnapshots[job.id];
                }
            } catch (e) {
                console.error('Error saving job changes:', e);
                this.toast?.error?.('Failed to save job changes');
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
            const n = Number(number);
            if (!Number.isFinite(n)) return '0.00';
            return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
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
        isMergeGroupFieldValid(groupIdx, fieldName) {
            const groups = this.faktura?.merge_groups || [];
            const group = groups[groupIdx];
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
        async printInvoice() {
            const toast = useToast();
            try {
                // Check if this is a split invoice
                const isSplitInvoice = this.faktura?.is_split_invoice || false;
                
                if (isSplitInvoice) {
                    // For split invoices, build job units and send as POST request
                    const jobsWithUnits = [];
                    if (this.invoice) {
                        // Handle both array and object formats
                        const invoices = Array.isArray(this.invoice) ? this.invoice : Object.values(this.invoice);
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

                    const response = await axios.post(`/invoice/${this.faktura.id}/pdf`, {
                        job_units: jobsWithUnits,
                        merge_groups: this.faktura?.merge_groups || []
                    }, { 
                        responseType: 'blob' 
                    });
                    
                    // Open blob in new tab
                    const blob = new Blob([response.data], { type: 'application/pdf' });
                    const url = window.URL.createObjectURL(blob);
                    window.open(url, '_blank');
                    toast.success('Split invoice printed');
                    return;
                }

                // For regular invoices, use the existing preview-invoice endpoint
                const orderIds = (this.invoice || []).map(order => order.id);
                if (!orderIds.length) {
                    toast.error('Cannot print: no orders available');
                    return;
                }
                
                // Validate merge groups before printing
                if (!this.hasValidMergeGroups) {
                    toast.error('Cannot print: Please fill in all required fields (title, quantity, and price) for merged groups');
                    return;
                }
                
                // Include job data with units for printing
                const jobsWithUnits = [];
                if (this.invoice) {
                    // Handle both array and object formats
                    const invoices = Array.isArray(this.invoice) ? this.invoice : Object.values(this.invoice);
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
                // Use the same date logic as the display to ensure consistency
                const createdAt = this.getDateForPayload();
                
                console.log('Print Invoice - final createdAt being sent:', createdAt);
                
                const response = await axios.post('/preview-invoice', {
                    orders: orderIds,
                    comment: (this.invoice && this.invoice[0]?.faktura_comment) || '',
                    trade_items: tradePayload,
                    additional_services: this.additionalServices,
                    created_at: createdAt,
                    merge_groups: this.faktura?.merge_groups || [],
                    job_units: jobsWithUnits,
                    payment_deadline_override: this.displayPaymentDeadline,
                    // Include current overrides for print
                    order_title_overrides: this.currentOverrides.order_titles || {},
                    job_name_overrides: this.currentOverrides.job_names || {},
                    job_quantity_overrides: this.currentOverrides.job_quantities || {}
                }, { responseType: 'blob' });

                // Open blob in new tab
                const blob = new Blob([response.data], { type: 'application/pdf' });
                const url = window.URL.createObjectURL(blob);
                window.open(url, '_blank');
                toast.success('Invoice preview generated');
            } catch (error) {
                console.error('Error generating preview for print:', error);
                toast.error('Error generating preview for print');
            }
        },

        // Additional Services Methods
        closeAdditionalServicesModal() {
            this.showAdditionalServicesModal = false;
            this.newServiceName = '';
            this.newServiceQuantity = 1;
            this.newServiceUnit = 'ком';
            this.newServicePrice = 0;
            this.newServiceVatRate = 18;
        },

        async addAdditionalService() {
            if (!this.canAddAdditionalService) return;
            
            try {
                if (!this.faktura || !this.faktura.id) {
                    this.toast.error('Cannot add service: missing faktura');
                    return;
                }

                const payload = {
                    name: this.newServiceName.trim(),
                    quantity: this.newServiceQuantity,
                    unit: this.newServiceUnit,
                    sale_price: this.newServicePrice,
                    vat_rate: this.newServiceVatRate
                };

                const res = await axios.post(`/invoice/${this.faktura.id}/additional-services`, payload);
                
                const created = res?.data?.service || {
                    id: Date.now(),
                    ...payload
                };

                this.additionalServices.push(created);
                this.closeAdditionalServicesModal();
                this.toast.success('Additional service added');
            } catch (e) {
                console.error(e);
                this.toast.error('Failed to add additional service');
            }
        },

        startEditService(index, service) {
            this.$set ? this.$set(this.isEditingService, index, true) : (this.isEditingService[index] = true);
            this.$set ? this.$set(this.editServiceForms, index, {
                name: service.name,
                quantity: Number(service.quantity || 0),
                unit: service.unit || 'ком',
                sale_price: Number(service.sale_price || 0),
                vat_rate: Number(service.vat_rate || 18)
            }) : (this.editServiceForms[index] = {
                name: service.name,
                quantity: Number(service.quantity || 0),
                unit: service.unit || 'ком',
                sale_price: Number(service.sale_price || 0),
                vat_rate: Number(service.vat_rate || 18)
            });
        },

        cancelEditService(index) {
            this.isEditingService[index] = false;
            delete this.editServiceForms[index];
        },

        async saveEditService(index, service) {
            try {
                const form = this.editServiceForms[index];
                if (!form) return;
                
                if (!this.faktura || !this.faktura.id) {
                    this.toast.error('Cannot update service: missing faktura');
                    return;
                }

                const payload = {
                    name: form.name,
                    quantity: form.quantity,
                    unit: form.unit,
                    sale_price: form.sale_price,
                    vat_rate: form.vat_rate
                };

                await axios.put(`/invoice/${this.faktura.id}/additional-services/${service.id}`, payload);
                
                service.name = payload.name;
                service.quantity = payload.quantity;
                service.unit = payload.unit;
                service.sale_price = payload.sale_price;
                service.vat_rate = payload.vat_rate;
                
                this.isEditingService[index] = false;
                delete this.editServiceForms[index];
                this.toast.success('Additional service updated');
            } catch (e) {
                console.error(e);
                this.toast.error('Failed to update additional service');
            }
        },

        async removeAdditionalService(index, service) {
            try {
                if (!this.faktura || !this.faktura.id) {
                    this.toast.error('Cannot delete service: missing faktura');
                    return;
                }
                
                await axios.delete(`/invoice/${this.faktura.id}/additional-services/${service.id}`);
                this.additionalServices.splice(index, 1);
                this.toast.success('Additional service deleted');
            } catch (e) {
                console.error(e);
                this.toast.error('Failed to delete additional service');
            }
        },

        getServiceTotal(index, service) {
            const form = this.editServiceForms[index];
            const quantity = form ? Number(form.quantity || 0) : Number(service.quantity || 0);
            const price = form ? Number(form.sale_price || 0) : Number(service.sale_price || 0);
            return quantity * price;
        },

        getServiceVat(index, service) {
            const form = this.editServiceForms[index];
            const vatRate = form ? Number(form.vat_rate || 0) : Number(service.vat_rate || 0);
            const total = this.getServiceTotal(index, service);
            return total * (vatRate / 100);
        },

        getServiceTotalWithVat(index, service) {
            const total = this.getServiceTotal(index, service);
            const vat = this.getServiceVat(index, service);
            return total + vat;
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
    grid-template-columns: 2fr 1fr 0.8fr 1fr 1fr 1fr; /* align with job header: Title, Qty, Unit, Total m², Sale, Actions */
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

.wide-modal {
    max-width: 900px;
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

/* Select list styling for add/remove orders */
.select-list {
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 6px;
    background: rgba(255,255,255,0.05);
    padding: 6px 8px;
}
.select-row {
    padding: 8px 10px !important;
    border-bottom: 1px dashed rgba(255,255,255,0.1);
    border-radius: 4px;
    transition: background-color 0.15s ease, transform 0.05s ease;
}
.select-row:hover {
    background: rgba(255,255,255,0.08);
}
.select-row.selected {
    background: rgba(56, 161, 105, 0.25); /* green tint */
    outline: 1px solid rgba(56,161,105,0.5);
}
.select-row input[type='checkbox'] {
    width: 16px;
    height: 16px;
    accent-color: #38a169; /* green */
}

.select-title {
    flex: 1 1 auto;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
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
    grid-template-columns: 2fr 1fr 0.8fr 1fr 1fr 1fr;
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
    grid-template-columns: 2fr 1fr 0.8fr 1fr 1fr 1fr;
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

.inline-input.invalid-field {
    border: 2px solid #e53e3e;
    background-color: #fed7d7;
    animation: shake 0.5s ease-in-out;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-2px); }
    75% { transform: translateX(2px); }
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

// Additional Services Styles
.additional-services-section {
    background: $light-gray;
    border-radius: 3px;
    padding: 24px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    border: 1px solid $light-gray;
}

.additional-services-container {
    color: $white;
}

.additional-services-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    padding-bottom: 10px;
    border-bottom: 2px solid rgba($white, 0.15);
}

.additional-services-header .section-title {
    font-size: 18px;
    font-weight: 600;
    margin: 0;
}

.additional-services-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.additional-service-card {
    background: rgba(white, 0.12);
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 16px;
}

.additional-services-total {
    text-align: right;
    margin-top: 10px;
    padding: 10px;
    background-color: $dark-gray;
    border-radius: 4px;
}

.service-name {
    font-size: 16px;
    font-weight: 600;
    color: $white;
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

// Pagination styles
.pagination-controls {
    background-color: rgba($light-gray, 0.1);
    border-radius: 6px;
}

.pagination-info {
    font-size: 14px;
    color: $white;
}

.pagination-buttons .btn-sm {
    padding: 6px 12px;
    font-size: 13px;
    border-radius: 4px;
}

.pagination-buttons .btn-sm:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.selected-badge {
    background-color: $green;
    color: $white;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    margin-left: 8px;
    white-space: nowrap;
}
</style>

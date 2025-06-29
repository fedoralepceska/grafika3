<template>
    <div v-if="!$props.shipping">
        <!-- Show manual catalog creation when isCatalog prop is true -->
        <div v-if="isCatalog" class="catalog-wizard-container">
    

            <!-- Job Selection - Always visible at top -->
            <div class="job-selection-section">
                <div class="section-header justify-between">
                    <div class="flex gap-1  justify-between items-center">
                        <i class="mdi mdi-checkbox-multiple-marked"></i>
                        <div class="text-lg font-bold text-white">{{ $t('Job Selection') }}</div>
                        <div class="selection-badge">{{ selectedJobIds.length }}/{{ jobs?.length || 0 }}</div>
                    </div>
                
                    <div class="job-selection-controls">
                        <button type="button" @click="selectAllJobs" class="control-btn select-all">
                            {{ $t('Select All') }}
                        </button>
                        <button type="button" @click="clearJobSelection" class="control-btn clear-all">
                            {{ $t('Clear All') }}
                        </button>
                    </div>
                </div>

                <div class="jobs-grid-compact">
                    <div v-for="(job, index) in jobs" :key="job.id" 
                         class="job-card-compact" 
                         :class="{ 'selected': selectedJobIds.includes(job.id) }"
                         @click="toggleJobSelection(job.id)">
                        <div class="job-checkbox">
                            <i class="mdi" :class="selectedJobIds.includes(job.id) ? 'mdi-checkbox-marked' : 'mdi-checkbox-blank-outline'"></i>
                        </div>
                        <div class="job-info">
                            <div class="job-title">#{{ index + 1 }}</div>
                            <div class="job-details">{{ job.width.toFixed(2) }}×{{ job.height.toFixed(2) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step Navigation -->
            <div class="step-navigation">
                <div class="step-indicators">
                    <div v-for="(step, index) in wizardSteps" :key="index" 
                         class="step-indicator" 
                         :class="{
                             'active': currentStep === index,
                             'completed': index < currentStep,
                             'available': index <= currentStep
                         }"
                         @click="goToStep(index)">
                        <div class="step-circle">
                            <i v-if="index < currentStep" class="mdi mdi-check"></i>
                            <span v-else>{{ index + 1 }}</span>
                        </div>
                        <div class="step-label">{{ step.title }}</div>
                    </div>
                </div>
            </div>

            <!-- Step Content -->
            <div class="step-content">
                <!-- Step 1: Basic Information -->
                <div v-if="currentStep === 0" class="step-panel">
                    <div class="step-title">
                        <i class="mdi mdi-information"></i>
                        {{ $t('Basic Information') }}
                    </div>
                    <div class="form-row">
                        <div class="form-field">
                            <label>{{ $t('Name') }} *</label>
                            <input type="text" v-model="catalogData.name" class="input-field" :placeholder="$t('Enter catalog name')">
                        </div>
                        <div class="form-field">
                            <label>{{ $t('Price') }} *</label>
                            <input type="number" v-model="catalogData.price" step="0.01" min="0" class="input-field" :placeholder="$t('Enter price')">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-field">
                            <label>{{ $t('Quantity') }} *</label>
                            <input type="number" v-model="catalogData.quantity" min="1" class="input-field">
                        </div>
                        <div class="form-field">
                            <label>{{ $t('Copies') }} *</label>
                            <input type="number" v-model="catalogData.copies" min="1" class="input-field">
                        </div>
                    </div>
                </div>

                <!-- Step 2: Machines -->
                <div v-if="currentStep === 1" class="step-panel">
                    <div class="step-title">
                        <i class="mdi mdi-printer-3d"></i>
                        {{ $t('Machine Selection') }}
                    </div>
                    <div class="form-row">
                        <div class="form-field">
                            <label>{{ $t('machineP') }}</label>
                            <select v-model="catalogData.machinePrint" class="input-field">
                                <option value="">{{ $t('Select Machine') }}</option>
                                <option v-for="machine in machinesPrint" :key="machine.id" :value="machine.name">
                                    {{ machine.name }}
                                </option>
                            </select>
                        </div>
                        <div class="form-field">
                            <label>{{ $t('machineC') }}</label>
                            <select v-model="catalogData.machineCut" class="input-field">
                                <option value="">{{ $t('Select Machine') }}</option>
                                <option v-for="machine in machinesCut" :key="machine.id" :value="machine.name">
                                    {{ machine.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Materials -->
                <div v-if="currentStep === 2" class="step-panel">
                    <div class="step-title">
                        <i class="mdi mdi-texture"></i>
                        {{ $t('Material Selection') }}
                    </div>
                    <div class="material-selection">
                        <div class="material-option">
                            <div class="material-header">
                                <input type="radio" v-model="materialType" value="large" id="largeMaterial">
                                <label for="largeMaterial" class="material-type-label">{{ $t('Large Format Material') }}</label>
                            </div>
                            <div v-if="materialType === 'large'" class="material-selector">
                                <select v-model="catalogData.selectedLargeMaterial" class="input-field">
                                    <option value="">{{ $t('Select Large Material') }}</option>
                                    <option v-for="material in largeMaterials" :key="material.id" :value="material" :disabled="material.disabled">
                                        {{ material.type === 'category' ? `[${$t('category')}] ${material.name}` : material.name }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="material-divider">{{ $t('OR') }}</div>
                        
                        <div class="material-option">
                            <div class="material-header">
                                <input type="radio" v-model="materialType" value="small" id="smallMaterial">
                                <label for="smallMaterial" class="material-type-label">{{ $t('Small Format Material') }}</label>
                            </div>
                            <div v-if="materialType === 'small'" class="material-selector">
                                <select v-model="catalogData.selectedSmallMaterial" class="input-field">
                                    <option value="">{{ $t('Select Small Material') }}</option>
                                    <option v-for="material in materialsSmall" :key="material.id" :value="material" :disabled="material.disabled">
                                        {{ material.type === 'category' ? `[${$t('category')}] ${material.name}` : material.name }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Actions -->
                <div v-if="currentStep === 3" class="step-panel">
                    <div class="step-title">
                        <i class="mdi mdi-cog"></i>
                        {{ $t('Actions & Refinements') }}
                    </div>
                    <div class="actions-container">
                        <div v-for="(action, index) in catalogData.actions" :key="index" class="action-item">
                            <div class="action-row">
                                <div class="action-select">
                                    <label>{{ $t('Action') }} {{ index + 1 }}</label>
                                    <select v-model="action.selectedAction" class="input-field">
                                        <option value="">{{ $t('Select Action') }}</option>
                                        <option v-for="refinement in refinements" :key="refinement.id" :value="refinement">
                                            {{ refinement.name }}
                                        </option>
                                    </select>
                                </div>
                                <div v-if="action.selectedAction?.isMaterialized" class="action-quantity">
                                    <label>{{ $t('Quantity') }}</label>
                                    <input type="number" min="0" v-model="action.quantity" class="input-field" style="width: 30%;" :placeholder="getUnit(action.selectedAction)">
                                </div>
                                <div class="action-controls">
                                    <button v-if="index === catalogData.actions.length - 1" type="button" @click="addCatalogAction" class="add-btn">
                                        <i class="mdi mdi-plus"></i>
                                    </button>
                                    <button v-if="catalogData.actions.length > 1" type="button" @click="removeCatalogAction(index)" class="remove-btn">
                                        <i class="mdi mdi-delete"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 5: Component Articles -->
                <div v-if="currentStep === 4" class="step-panel">
                    <div class="step-title">
                        <i class="mdi mdi-package-variant"></i>
                        {{ $t('Component Articles') }}
                    </div>
                    
                    <div class="articles-container">
                        <!-- Products Section -->
                        <div class="article-section">
                            <div class="article-section-header">
                                <h4 class="text-white"><i class="mdi mdi-cube"></i> {{ $t('Products') }}</h4>
                                <button type="button" @click="addArticle('product')" class="add-btn small">
                                    <i class="mdi mdi-plus"></i> {{ $t('Add Product') }}
                                </button>
                            </div>
                            <div v-if="catalogData.productArticles.length === 0" class="empty-state">
                                <p>{{ $t('No products added yet') }}</p>
                            </div>
                            <div v-else class="article-list">
                                <div v-for="(article, index) in catalogData.productArticles" :key="index" class="article-row">
                                    <div class="article-select">
                                        <CatalogArticleSelect
                                            v-model="article.id"
                                            :type="'product'"
                                            @article-selected="handleArticleSelected($event, index, 'product')"
                                            class="input-field"
                                        />
                                    </div>
                                    <div class="article-quantity">
                                        <input v-model="article.quantity" type="number" min="0.01" step="0.01" class="input-field" style="width: 50px;"
                                               :placeholder="$t('Quantity') + (article.unitLabel ? ` (${article.unitLabel})` : '')" />
                                    </div>
                                    <button type="button" @click="removeArticle(index, 'product')" class="remove-btn">
                                        <i class="mdi mdi-delete"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Services Section -->
                        <div class="article-section">
                            <div class="article-section-header">
                                <h4><i class="mdi mdi-tools"></i> {{ $t('Services') }}</h4>
                                <button type="button" @click="addArticle('service')" class="add-btn small">
                                    <i class="mdi mdi-plus"></i> {{ $t('Add Service') }}
                                </button>
                            </div>
                            <div v-if="catalogData.serviceArticles.length === 0" class="empty-state">
                                <p>{{ $t('No services added yet') }}</p>
                            </div>
                            <div v-else class="article-list">
                                <div v-for="(article, index) in catalogData.serviceArticles" :key="index" class="article-row">
                                    <div class="article-select">
                                        <CatalogArticleSelect
                                            v-model="article.id"
                                            :type="'service'"
                                            @article-selected="handleArticleSelected($event, index, 'service')"
                                            class="input-field"
                                        />
                                    </div>
                                    <div class="article-quantity">
                                        <input v-model="article.quantity" type="number" min="0.01" step="0.01" class="input-field" style="width: 50px;"
                                               :placeholder="$t('Quantity') + (article.unitLabel ? ` (${article.unitLabel})` : '')" />
                                    </div>
                                    <button type="button" @click="removeArticle(index, 'service')" class="remove-btn">
                                        <i class="mdi mdi-delete"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="step-navigation-buttons">
                <button v-if="currentStep > 0" @click="previousStep" class="nav-btn secondary">
                    <i class="mdi mdi-chevron-left"></i>
                    {{ $t('Previous') }}
                </button>
                <div class="nav-spacer"></div>
                <button v-if="currentStep < wizardSteps.length - 1" @click="nextStep" class="nav-btn primary">
                    {{ $t('Next') }}
                    <i class="mdi mdi-chevron-right"></i>
                </button>
                <button v-if="currentStep === wizardSteps.length - 1" @click="goToSummary" class="nav-btn primary">
                    {{ $t('Review & Sync') }}
                    <i class="mdi mdi-arrow-right"></i>
                </button>
                <button v-if="currentStep === wizardSteps.length - 1" @click="syncJobsWithCatalog" :disabled="!isCatalogFormValid || isSyncing" class="nav-btn sync-only">
                    <i v-if="isSyncing" class="mdi mdi-loading mdi-spin"></i>
                    <i v-else class="mdi mdi-sync"></i>
                    <span v-if="isSyncing">{{ $t('Syncing...') }}</span>
                    <span v-else>{{ $t('Sync Only') }}</span>
                </button>
            </div>

            <!-- Summary Modal -->
            <div v-if="showSummary" class="modal-overlay" @click="closeSummary">
                <div class="modal-content" @click.stop>
                    <div class="modal-header">
                        <h3><i class="mdi mdi-clipboard-check"></i> {{ $t('Sync Summary') }}</h3>
                        <button @click="closeSummary" class="modal-close">
                            <i class="mdi mdi-close"></i>
                        </button>
                    </div>
                    
                    <div class="modal-body">
                        <div class="summary-grid">
                            <div class="summary-card">
                                <h4>{{ $t('Selected Jobs') }}</h4>
                                <div class="summary-value">{{ selectedJobIds.length }} {{ $t('jobs') }}</div>
                            </div>
                            
                            <div class="summary-card">
                                <h4>{{ $t('Basic Information') }}</h4>
                                <div class="summary-details">
                                    <div><strong>{{ $t('Name') }}:</strong> {{ catalogData.name || $t('Not set') }}</div>
                                    <div><strong>{{ $t('Price') }}:</strong> {{ catalogData.price || '0' }} ден.</div>
                                    <div><strong>{{ $t('Quantity') }}:</strong> {{ catalogData.quantity }}</div>
                                </div>
                            </div>
                            
                            <div class="summary-card">
                                <h4>{{ $t('Machines') }}</h4>
                                <div class="summary-details">
                                    <div><strong>{{ $t('Print') }}:</strong> {{ catalogData.machinePrint || $t('Not selected') }}</div>
                                    <div><strong>{{ $t('Cut') }}:</strong> {{ catalogData.machineCut || $t('Not selected') }}</div>
                                </div>
                            </div>
                            
                            <div class="summary-card">
                                <h4>{{ $t('Material') }}</h4>
                                <div class="summary-details">
                                    <div v-if="catalogData.selectedLargeMaterial">
                                        <strong>{{ $t('Large Format') }}:</strong> {{ catalogData.selectedLargeMaterial.name }}
                                    </div>
                                    <div v-if="catalogData.selectedSmallMaterial">
                                        <strong>{{ $t('Small Format') }}:</strong> {{ catalogData.selectedSmallMaterial.name }}
                                    </div>
                                    <div v-if="!catalogData.selectedLargeMaterial && !catalogData.selectedSmallMaterial">
                                        {{ $t('No material selected') }}
                                    </div>
                                </div>
                            </div>
                            
                            <div class="summary-card">
                                <h4>{{ $t('Actions') }}</h4>
                                <div class="summary-details">
                                    <div v-if="getSelectedActions().length === 0">{{ $t('No actions selected') }}</div>
                                    <div v-for="action in getSelectedActions()" :key="action.name">
                                        <strong>{{ action.name }}</strong>
                                        <span v-if="action.quantity"> ({{ action.quantity }} {{ action.unit }})</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="summary-card cost-card">
                                <h4>{{ $t('Cost Summary') }}</h4>
                                <div class="cost-breakdown">
                                    <div class="cost-row">
                                        <span>{{ $t('Products') }}:</span>
                                        <span>{{ displayProductsCost.toFixed(2) }} ден.</span>
                                    </div>
                                    <div class="cost-row">
                                        <span>{{ $t('Services') }}:</span>
                                        <span>{{ displayServicesCost.toFixed(2) }} ден.</span>
                                    </div>
                                    <div class="cost-row total">
                                        <span>{{ $t('Total Cost') }}:</span>
                                        <span>{{ displayTotalCost.toFixed(2) }} ден.</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Validation Status -->
                        <div class="validation-status" :class="getValidationClass()">
                            <i class="mdi" :class="getValidationIcon()"></i>
                            <span>{{ getValidationMessage() }}</span>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button @click="closeSummary" class="modal-btn secondary">
                            <i class="mdi mdi-pencil"></i>
                            {{ $t('Back to Edit') }}
                        </button>
                        
                        <button @click="syncFromModal" :disabled="!isCatalogFormValid || isSyncing" class="modal-btn primary">
                            <i v-if="isSyncing" class="mdi mdi-loading mdi-spin"></i>
                            <i v-else class="mdi mdi-sync"></i>
                            <span v-if="isSyncing">{{ $t('Syncing...') }}</span>
                            <span v-else>{{ $t('Sync Jobs') }} ({{ selectedJobIds.length }})</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Regular sync mode when isCatalog is false -->
        <div v-else>
            <div class="light-gray p-2">
                <div>
                    <div class="text-white">{{ $t('syncJobs') }}</div>
                    <div>
                        <VueMultiselect
                            :searchable="false"
                            v-model="selectedJobs"
                            :options="formattedJobOptions"
                            :multiple="true"
                            label="title"
                            track-by="value"
                            :close-on-select="true"
                            :show-labels="false"
                            placeholder="Select Jobs">
                        </VueMultiselect>
                    </div>
                </div>
            </div>
            <div class="order-info-box">
                <div class="form-group mt-2 p-2 text-black sameRow">
                    <label class="label-fixed-width">{{ $t('Name') }}</label>
                    <input type="text" v-model="name" class="select-fixed-width">
                </div>
                <div class="form-group mt-2 p-2 text-black sameRow">
                    <label class="label-fixed-width">{{ $t('machineP') }}</label>
                    <select v-model="selectedMachinePrint" class="select-fixed-width">
                        <option v-for="machine in machinesPrint" :key="machine.id" :value="machine.name">
                            {{ machine.name }}
                        </option>
                    </select>
                </div>

                <div class="form-group mt-2 p-2 text-black sameRow">
                    <label class="label-fixed-width">{{ $t('machineC') }}</label>
                    <select v-model="selectedMachineCut" class="select-fixed-width">
                        <option v-for="machine in machinesCut" :key="machine.id" :value="machine.name">
                            {{ machine.name }}
                        </option>
                    </select>
                </div>

                <div class="form-group mt-2 p-2 text-black sameRow">
                    <label class="label-fixed-width">{{ $t('material') }}</label>
                    <select v-model="selectedMaterial" :disabled="selectedMaterialSmall !== ''" class="select-fixed-width">
                        <option v-for="material in largeMaterials" :key="material.id" :value="material" :disabled="material.disabled">
                            <template v-if="material.type === 'category'">
                                <img v-if="material.icon" :src="`/storage/icons/${material.icon}`" alt="icon" style="width: 18px; height: 18px; margin-right: 4px;" />
                                [{{ $t('category') }}] {{ material.name }}
                            </template>
                            <template v-else>
                                {{ material.name }}
                            </template>
                        </option>
                    </select>
                    <button v-if="selectedMaterial !== ''" @click="clearSelection('selectedMaterial')" class="removeBtn"><span class="mdi mdi-minus-circle"></span></button>
                </div>

                <div class="form-group mt-2 p-2 text-black sameRow">
                    <label class="label-fixed-width">{{ $t('materialSmallFormat') }}</label>
                    <select v-model="selectedMaterialSmall" :disabled="selectedMaterial !== ''" class="select-fixed-width">
                        <option v-for="material in materialsSmall" :key="material.id" :value="material" :disabled="material.disabled">
                            <template v-if="material.type === 'category'">
                                <img v-if="material.icon" :src="`/storage/icons/${material.icon}`" alt="icon" style="width: 18px; height: 18px; margin-right: 4px;" />
                                [{{ $t('category') }}] {{ material.name }}
                            </template>
                            <template v-else>
                                {{ material.name }}
                            </template>
                        </option>
                    </select>
                    <button v-if="selectedMaterialSmall !== ''" @click="clearSelection('selectedMaterialSmall')" class="removeBtn"><span class="mdi mdi-minus-circle"></span></button>

                </div>

                <div v-for="(action, index) in actions" :key="index">
                    <div class="form-group mt-2 p-2 text-black sameRow">
                        <label class="label-fixed-width">{{ $t('action') }} {{ index + 1 }}</label>
                        <select v-model="action.selectedAction" class="select-fixed-width">
                            <option v-for="actionOption in refinements" :key="actionOption" :value="actionOption">
                                {{ actionOption.name }}
                            </option>
                        </select>
                        <button class="addBtn" @click="addAction"><span class="mdi mdi-plus-circle"></span></button>
                        <button v-if="index > 0" class="removeBtn" @click="removeAction(index)"><span class="mdi mdi-minus-circle"></span></button>
                    </div>
                    <div v-if="action.selectedAction?.isMaterialized" class="form-group mt-2 p-2 text-black sameRow">
                        <label class="label-fixed-width">{{ $t('actionQuantity') }}</label>
                        <input type="number" min="0" v-model="action.quantity">
                    </div>
                </div>
                <div class="form-group mt-2 p-2 text-black sameRow">
                    <label class="label-fixed-width">{{ $t('Quantity') }}</label>
                    <input type="number" v-model="quantity">
                </div>
                <div class="form-group mt-2 p-2 text-black sameRow">
                    <label class="label-fixed-width">{{ $t('Copies') }}</label>
                    <input type="number" v-model="copies">
                </div>

                <div class="button-container rowButtons">
                    <PrimaryButton class="mt-5" @click="syncAll">
                        {{ $t('syncAllJobs') }}
                    </PrimaryButton>
                    <PrimaryButton class="mt-5" @click="syncAll">
                        {{ $t('syncJobs') }}
                    </PrimaryButton>
                </div>
            </div>
        </div>
    </div>
    <div v-else>
        <div class="sameColumn">
            <span class="text-white">Add the shipping information here:</span>
            <textarea v-model="shippingDetails"></textarea>
        </div>
        <div>
            <label class="text-white">{{ $t('syncJobs') }}</label>
            <div>
                <VueMultiselect
                    :searchable="false"
                    v-model="selectedJobs"
                    :options="formattedJobOptions"
                    :multiple="true"
                    label="title"
                    track-by="value"
                    :close-on-select="true"
                    :show-labels="false"
                    placeholder="Select Jobs">
                </VueMultiselect>
            </div>
        </div>
        <div class="button-container rowButtons">
            <PrimaryButton class="mt-5" @click="syncAllWithShipping">
                {{ $t('syncAllJobs') }}
            </PrimaryButton>
            <PrimaryButton class="mt-5" @click="syncAllWithShipping">
                {{ $t('syncJobs') }}
            </PrimaryButton>
        </div>
    </div>
</template>

<script>
import { useI18n } from 'vue-i18n';
import PrimaryButton from "@/Components/buttons/PrimaryButton.vue";
import { useToast } from "vue-toastification";
import SecondaryButton from "@/Components/buttons/SecondaryButton.vue";
import store from '../orderStore.js';
import VueMultiselect from 'vue-multiselect'
import axios from "axios";
import Checkbox from "@/Components/inputs/Checkbox.vue";
import CatalogSelector from './CatalogSelector.vue';
import CatalogArticleSelect from './CatalogArticleSelect.vue';

export default {
    name: "OrderInfo",
    components: {Checkbox, VueMultiselect, SecondaryButton, PrimaryButton, CatalogSelector, CatalogArticleSelect },
    props: {
        jobs: Array,
        shippingDetails: String,
        shipping: Boolean,
        isCatalog: {
            type: Boolean,
            default: false
        },
        clientId: {
            type: [String, Number],
            default: null
        }
    },
    data() {
        return {
            selectedMaterial: '',
            selectedMaterialSmall: '',
            selectedMachineCut: '',
            selectedMachinePrint: '',
            shippingDetails: '',
            selectedAction: '',
            quantity: 1,
            copies: 1,
            name: '',
            selectedJobs: [],
            actions: [{}],
            actionOptions: this.generateActionOptions(),
            largeMaterials: [],
            materialsSmall: [],
            machinesPrint: [],
            machinesCut: [],
            refinements: this.getRefinements(),
            catalogData: {
                name: '',
                description: '',
                price: 0,
                quantity: 1,
                copies: 1,
                machinePrint: '',
                machineCut: '',
                selectedLargeMaterial: '',
                selectedSmallMaterial: '',
                actions: [{ selectedAction: '', quantity: 0 }],
                productArticles: [],
                serviceArticles: []
            },
            isSyncing: false,
            selectedJobIds: [],
            activeTab: 'products',
            currentStep: 0,
            wizardSteps: [
                { title: 'Basic Information' },
                { title: 'Machine Selection' },
                { title: 'Material Selection' },
                { title: 'Actions & Refinements' },
                { title: 'Component Articles' }
            ],
            materialType: 'large',
            showSummary: false
        }
    },
    computed: {
        formattedJobOptions() {
            return this.jobs?.map((job, index) => ({ value: job.id, title: `#${index + 1}` }));
        },
        isCatalogFormValid() {
            return this.catalogData.name.trim() !== '' &&
                   this.catalogData.price > 0 &&
                   this.catalogData.quantity > 0 &&
                   this.catalogData.copies > 0 &&
                   (this.catalogData.selectedLargeMaterial !== '' || this.catalogData.selectedSmallMaterial !== '') &&
                   this.catalogData.actions.some(action => action.selectedAction !== '') &&
                   this.selectedJobIds.length > 0 && // Must have at least one job selected
                   this.clientId; // Must have a client selected
        },
        displayProductsCost() {
            return this.catalogData.productArticles.reduce((total, article) => {
                return total + ((article.purchase_price || 0) * (article.quantity || 0));
            }, 0);
        },
        displayServicesCost() {
            return this.catalogData.serviceArticles.reduce((total, article) => {
                return total + ((article.purchase_price || 0) * (article.quantity || 0));
            }, 0);
        },
        displayTotalCost() {
            return this.displayProductsCost + this.displayServicesCost;
        }
    },
    watch: {
        materialType(newType) {
            if (newType === 'large') {
                this.catalogData.selectedSmallMaterial = '';
            } else if (newType === 'small') {
                this.catalogData.selectedLargeMaterial = '';
            }
        }
    },
    async mounted() {
        // Fetch large and small materials for dropdowns
        const [largeRes, smallRes] = await Promise.all([
            axios.get('/api/materials/large-dropdown'),
            axios.get('/api/materials/small-dropdown')
        ]);
        this.largeMaterials = largeRes.data;
        this.materialsSmall = smallRes.data;
        this.fetchMachines();
    },
    methods: {
        fetchMachines() {
            axios.get('/get-machines')
                .then((response) => {
                    this.machinesCut = response.data.machinesCut;
                    this.machinesPrint = response.data.machinesPrint;
                })
                .catch((error) => {
                    console.error('Error fetching machines:', error);
                });
        },
        async getRefinements() {
            const response = await axios.get('/refinements/all');
            this.refinements = response.data;
        },
        generateActionOptions() {
            const actions = [];
            for (let i = 1; i <= 28; i++) {
                actions.push(`Action ${i}`);
            }
            return actions;
        },
        addAction() {
            this.actions.push({});
        },
        removeAction(index) {
            this.actions.splice(index, 1);
        },
        clearSelection(fieldName) {
            this[fieldName] = ''; // Reset the selected value to an empty string
        },
        syncAll() {
            const toast = useToast();
            let jobIds;
            // Get all job ids
            if (this.selectedJobs.length) {
                jobIds = this.selectedJobs.map(job => job.value)
            }
            else {
                jobIds = this.jobs?.map(job => job?.id);
            }

            // Create jobsWithActions for all jobs
            const jobsWithActions = jobIds.map(jobId => {
                const actions = this.actions.map(action => ({
                    action_id: action.selectedAction,
                    quantity: action.quantity,
                    status: 'Not started yet'
                }));

                return {
                    job_id: jobId,
                    actions: actions
                };
            });

            if (this.selectedJobs.length) {
                const filteredJobs = this.jobs.filter(job =>
                    this.selectedJobs.some(selectedJob => selectedJob.value === job.id)
                );
                jobIds = filteredJobs.filter(j => j.isPlaceholder).map(j => j.id);
            }
            else {
                jobIds = this.jobs.filter(j => j.isPlaceholder).map(j => j.id);
            }
            if (jobIds.length) {
                axios.post('/sync-jobs-with-machine', {
                    jobs: jobIds,
                    selectedMachinePrint: this.selectedMachinePrint,
                })
                    .then(response => {
                        toast.success(`Successfully synced ${jobIds.length} jobs!`);
                        jobIds = this.jobs.map(job => job.id);
                        axios.post('/get-jobs-by-ids', {
                            jobs: jobIds,
                        })
                            .then(response => {
                                this.$emit('jobs-updated', response.data.jobs);
                            })
                            .catch(error => {
                                toast.error("Couldn't fetch updated jobs");
                            });
                    })
                    .catch(error => {
                        if (error.response && error.response.data.message) {
                            // Specific error message from the backend
                            toast.error(error.response.data.message);
                        } else {
                            // Generic error message
                            toast.error("Couldn't sync jobs");
                        }
                    });
            } else {
                if (this.selectedJobs.length) {
                    jobIds = this.selectedJobs.filter(j => !j.isPlaceholder).map(j => j.value);
                }
                else {
                    jobIds = this.jobs.filter(j => !j.isPlaceholder).map(j => j.id);
                }
                axios.post('/sync-all-jobs', {
                    selectedMaterial: this.selectedMaterial.id,
                    selectedMachinePrint: this.selectedMachinePrint,
                    selectedMachineCut: this.selectedMachineCut,
                    selectedMaterialsSmall: this.selectedMaterialSmall.id,
                    quantity: this.quantity,
                    copies: this.copies,
                    shipping: store.state.shippingDetails,
                    name: this.name,
                    jobs: jobIds,
                    jobsWithActions: jobsWithActions,
                })
                    .then(response => {
                        toast.success(`Successfully synced ${jobIds.length} jobs!`);
                        jobIds = this.jobs.map(job => job.id);
                        axios.post('/get-jobs-by-ids', {
                            jobs: jobIds,
                        })
                            .then(response => {
                                this.$emit('jobs-updated', response.data.jobs);
                            })
                            .catch(error => {
                                toast.error("Couldn't fetch updated jobs");
                            });
                    })
                    .catch(error => {
                        if (error.response && error.response.data.message) {
                            // Specific error message from the backend
                            toast.error(error.response.data.message);
                        } else {
                            // Generic error message
                            toast.error("Couldn't sync jobs");
                        }
                    });
            }
        },
        syncAllWithShipping() {
            const toast = useToast();
            let jobIds;
            // Get all job ids
            if (this.selectedJobs.length) {
                jobIds = this.selectedJobs.map(job => job.value)
            }
            else {
                jobIds = this.jobs.map(job => job.id);
            }

            axios.post('/sync-jobs-shipping', {
                shipping: this.shippingDetails,
                jobs: jobIds,
            })
                .then(response => {
                    toast.success(`Successfully synced ${jobIds.length} jobs!`);
                    jobIds = this.jobs.map(job => job.id)
                    axios.post('/get-jobs-by-ids', {
                        jobs: jobIds,
                    })
                        .then(response => {
                            this.$emit('jobs-updated', response.data.jobs);
                        })
                        .catch(error => {
                            toast.error("Couldn't fetch updated jobs");
                        });
                })
                .catch(error => {
                    toast.error("Couldn't sync jobs");
                });
        },
        getUnit(refinement) {
            const small = refinement?.small_material;
            const large = refinement?.large_format_material;

            if (small !== null || large !== null) {
                if (small?.article?.in_meters === 1 || large?.article?.in_meters === 1) {
                    return 'meters'
                }
                else if (small?.article?.in_kilograms === 1 || large?.article?.in_kilograms === 1) {
                    return 'kilograms'
                }
                else if (small?.article?.in_pieces === 1 || large?.article?.in_pieces === 1) {
                    return 'pieces'
                }
            }
            else {
                return '';
            }
        },
        handleCatalogJobs(jobs) {
            this.$emit('catalog-jobs-created', jobs);
        },
        async syncJobsWithCatalog() {
            this.isSyncing = true;
            const toast = useToast();
            
            try {
                // Check if client is selected first
                if (!this.clientId) {
                    toast.error(this.$t('Please select a client before syncing jobs'));
                    return;
                }

                // Get selected job IDs to sync
                const jobIds = this.selectedJobIds;
                
                if (jobIds.length === 0) {
                    toast.error('Please select at least one job to sync');
                    return;
                }

                // Prepare catalog data for syncing
                const syncData = {
                    jobs: jobIds,
                    name: this.catalogData.name,
                    price: this.catalogData.price,
                    selectedMachinePrint: this.catalogData.machinePrint,
                    selectedMachineCut: this.catalogData.machineCut,
                    quantity: this.catalogData.quantity,
                    copies: this.catalogData.copies,
                    selectedMaterial: this.catalogData.selectedLargeMaterial,
                    selectedMaterialsSmall: this.catalogData.selectedSmallMaterial,
                    large_material_category_id: this.catalogData.largeMaterialCategory,
                    client_id: this.clientId,
                    catalog_item_id: null,
                };

                // Handle materials (category vs individual)
                if (this.catalogData.selectedLargeMaterial) {
                    if (this.catalogData.selectedLargeMaterial.type === 'category') {
                        syncData.selectedMaterial = null;
                        syncData.large_material_category_id = this.catalogData.selectedLargeMaterial.id.replace('cat_', '');
                    } else {
                        syncData.selectedMaterial = this.catalogData.selectedLargeMaterial.id;
                    }
                }

                if (this.catalogData.selectedSmallMaterial) {
                    if (this.catalogData.selectedSmallMaterial.type === 'category') {
                        syncData.selectedMaterialsSmall = null;
                        syncData.small_material_category_id = this.catalogData.selectedSmallMaterial.id.replace('cat_', '');
                    } else {
                        syncData.selectedMaterialsSmall = this.catalogData.selectedSmallMaterial.id;
                    }
                }

                // Create jobsWithActions for selected jobs only
                const jobsWithActions = jobIds.map(jobId => {
                    const actions = this.catalogData.actions
                        .filter(action => action.selectedAction !== '')
                        .map(action => ({
                            action_id: action.selectedAction.id,
                            quantity: action.quantity || 0,
                            status: 'Not started yet'
                        }));

                    return {
                        job_id: jobId,
                        actions: actions
                    };
                });

                syncData.jobsWithActions = jobsWithActions;

                // Include articles for cost calculation
                const articles = [
                    ...this.catalogData.productArticles.map(article => ({
                        id: article.id,
                        quantity: article.quantity
                    })),
                    ...this.catalogData.serviceArticles.map(article => ({
                        id: article.id,
                        quantity: article.quantity
                    }))
                ].filter(article => article.id); // Only include articles that have been selected

                if (articles.length > 0) {
                    syncData.articles = articles;
                }

                // Use the existing sync endpoint
                const response = await axios.post('/sync-all-jobs', syncData);
                
                toast.success(`Successfully synced ${jobIds.length} selected jobs with catalog data!`);
                
                // Fetch updated jobs
                const updatedJobsResponse = await axios.post('/get-jobs-by-ids', {
                    jobs: jobIds,
                });
                
                this.$emit('jobs-updated', updatedJobsResponse.data.jobs);
                
                // Reset all state to provide clean slate for next sync
                this.currentStep = 0;
                this.showSummary = false;
                this.selectedJobIds = [];
                this.materialType = 'large';
                this.activeTab = 'products';
                
                // Reset all catalog data to initial state
                this.catalogData = {
                    name: '',
                    description: '',
                    price: 0,
                    quantity: 1,
                    copies: 1,
                    machinePrint: '',
                    machineCut: '',
                    selectedLargeMaterial: '',
                    selectedSmallMaterial: '',
                    actions: [{ selectedAction: '', quantity: 0 }],
                    productArticles: [],
                    serviceArticles: []
                };
                
            } catch (error) {
                console.error('Error syncing jobs with catalog:', error);
                if (error.response && error.response.data.message) {
                    toast.error(error.response.data.message);
                } else {
                    toast.error('Failed to sync jobs with catalog data');
                }
            } finally {
                this.isSyncing = false;
            }
        },
        addCatalogAction() {
            this.catalogData.actions.push({});
        },
        removeCatalogAction(index) {
            this.catalogData.actions.splice(index, 1);
        },
        addArticle(type) {
            const article = {
                id: null,
                quantity: 1,
                type: type,
                purchase_price: 0,
                unitLabel: ''
            };

            if (type === 'product') {
                this.catalogData.productArticles.push(article);
            } else {
                this.catalogData.serviceArticles.push(article);
            }
        },
        removeArticle(index, type) {
            if (type === 'product') {
                this.catalogData.productArticles.splice(index, 1);
            } else {
                this.catalogData.serviceArticles.splice(index, 1);
            }
        },
        handleArticleSelected(article, index, type) {
            const targetArray = type === 'product' ? this.catalogData.productArticles : this.catalogData.serviceArticles;
            targetArray[index] = {
                ...targetArray[index],
                ...article,
                purchase_price: article.purchase_price || 0,
                unitLabel: article.unitLabel || ''
            };
        },
        selectAllJobs() {
            this.selectedJobIds = this.jobs?.map(job => job.id) || [];
        },
        clearJobSelection() {
            this.selectedJobIds = [];
        },
        toggleJobSelection(jobId) {
            if (this.selectedJobIds.includes(jobId)) {
                this.selectedJobIds = this.selectedJobIds.filter(id => id !== jobId);
            } else {
                this.selectedJobIds.push(jobId);
            }
        },
        getValidationClass() {
            return this.isSyncing ? 'validation-processing' : this.isCatalogFormValid ? 'validation-success' : 'validation-error';
        },
        getValidationIcon() {
            return this.isSyncing ? 'mdi-loading mdi-spin' : this.isCatalogFormValid ? 'mdi-check-circle' : 'mdi-exclamation-triangle';
        },
        getValidationMessage() {
            if (this.isSyncing) {
                return this.$t('Syncing...');
            } else if (!this.isCatalogFormValid) {
                return this.$t('Please fill in all required fields');
            } else {
                return this.$t('Ready to sync');
            }
        },
        goToStep(index) {
            this.currentStep = index;
        },
        previousStep() {
            if (this.currentStep > 0) {
                this.currentStep--;
            }
        },
        nextStep() {
            if (this.currentStep < this.wizardSteps.length - 1) {
                this.currentStep++;
            }
        },
        canProceedToNext() {
            return this.isCatalogFormValid;
        },
        goToSummary() {
            this.showSummary = true;
        },
        closeSummary() {
            this.showSummary = false;
        },
        syncFromModal() {
            this.syncJobsWithCatalog();
        },
        getSelectedActions() {
            return this.catalogData.actions.filter(action => action.selectedAction !== '').map(action => ({
                name: action.selectedAction.name,
                quantity: action.quantity,
                unit: this.getUnit(action.selectedAction)
            }));
        }
    }
};
</script>
<style src="vue-multiselect/dist/vue-multiselect.css"></style>
<style>
.multiselect__tag {
    background-color: #408a0b;
}
.multiselect__option--highlight{
    background-color: #408a0b;
}
.multiselect__option--selected.multiselect__option--highlight{
    background-color: indianred;
}
</style>
    <style scoped lang="scss">
    .light-gray {
        background-color: $light-gray;
    }
    
    .catalog-wizard-container {
        padding: 1rem;
    
    }
    
    .job-selection-section {

        border: 1px solid #e9ecef;
        margin-bottom: 1rem;
        
        .section-header {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            background-color: $dark-gray;
            border-bottom: 1px solid #e9ecef;
            
            i {
                font-size: 1rem;
                color: #6c757d;
                margin-right: 0.5rem;
            }
            
            span {
                color: #495057;
                font-weight: 500;
                font-size: 0.875rem;
                flex-grow: 1;
            }
            
            .selection-badge {
                background-color: #007bff;
                color: white;
                padding: 0.125rem 0.5rem;
                border-radius: 10px;
                font-size: 0.75rem;
                font-weight: 500;
            }
        }
    }
    
    .job-selection-controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        
        .control-btn {
            display: flex;
            align-items: center;
            padding: 0.375rem 0.75rem;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            background-color: white;
            color: #495057;
            font-size: 0.8rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.15s;
            
            i {
                margin-right: 0.375rem;
                font-size: 0.8rem;
            }
            
            &:hover {
                background-color: #e9ecef;
            }
            
            &.select-all {
                border-color: #007bff;
                color: #007bff;
                
                &:hover {
                    background-color: #007bff;
                    color: white;
                }
            }
            
            &.clear-all {
                border-color: #6c757d;
                color: #6c757d;
                
                &:hover {
                    background-color: #6c757d;
                    color: white;
                }
            }
        }
    }

    .jobs-grid-compact {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
        gap: 0.5rem;
        padding: 1rem;
        max-height: 180px;
        overflow-y: auto;
        background-color: $dark-gray;
        
        .job-card-compact {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 0.5rem;
            background-color: rgba(255, 255, 255, 0.1);
            border: 1px solid #dee2e6;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.15s;
            
            &:hover {
                border-color: #007bff;
            }
            
            &.selected {
                border-color: #007bff;
                ;
            }
            
            .job-checkbox {
                margin-bottom: 0.375rem;
                
                i {
                    font-size: 1rem;
                    color: #007bff;
                }
            }
            
            .job-info {
                text-align: center;
                
                .job-title {
                    font-weight: 500;
                    font-size: 0.75rem;
                    color: $white;
                    margin-bottom: 0.125rem;
                }
                
                .job-details {
                    font-size: 0.6875rem;
                    color: #6c757d;
                }
            }
        }
    }

    .step-navigation {
        display: flex;
        justify-content: center;
        margin-bottom: 1.5rem;
        
        .step-indicators {
            display: flex;
            align-items: center;
            gap: 2rem;
            
            .step-indicator {
                display: flex;
                flex-direction: column;
                align-items: center;
                cursor: pointer;
                transition: all 0.15s;
                
                .step-circle {
                    width: 32px;
                    height: 32px;
                    border-radius: 50%;
                    background-color: #e9ecef;
                    color: #6c757d;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin-bottom: 0.375rem;
                    font-weight: 500;
                    font-size: 0.8rem;
                    transition: all 0.15s;
                }
                
                .step-label {
                    font-size: 0.75rem;
                    color: $white;
                    text-align: center;
                    font-weight: 400;
                }
                
                &.active {
                    .step-circle {
                        background-color: #007bff;
                        color: white;
                    }
                    
                    .step-label {
                        color: white;
                        font-weight: 500;
                    }
                }
                
                &.completed {
                    .step-circle {
                        background-color: #28a745;
                        color: white;
                    }
                    
                    .step-label {
                        color: #28a745;
                    }
                }
            }
        }
    }

    .step-content {
        background-color: $dark-gray;
        border: 1px solid #e9ecef;
        margin-bottom: 1rem;
        
        .step-panel {
            padding: 1rem;
            
            .step-title {
                display: flex;
                align-items: center;
                color: $white;
                font-size: 1rem;
                font-weight: 600;
                margin-bottom: 1.25rem;
                
                i {
                    margin-right: 0.5rem;
                    color: #6c757d;
                    font-size: 1rem;
                }
            }
            
            .form-row {
                display: flex;
                gap: 1rem;
                margin-bottom: 1rem;
                
                @media (max-width: 768px) {
                    flex-direction: column;
                    gap: 0.75rem;
                }
                
                .form-field {
                    flex: 1;
                    
                    &.full-width {
                        flex: none;
                        width: 100%;
                    }
                    
                    label {
                        display: block;
                        color: $white;
                        font-size: 0.8rem;
                        font-weight: 500;
                        margin-bottom: 0.375rem;
                    }
                    .input-field-quantity{
                        background-color: $dark-gray;
                       
                    }
                    
                    .input-field {
                        width: 100%;
                        padding: 0.5rem 0.75rem;
                        border: 1px solid #ced4da;
                        border-radius: 4px;
                        background-color: white;
                        color: #495057;
                        font-size: 0.875rem;
                        transition: border-color 0.15s;
                        
                        &:focus {
                            outline: none;
                            border-color: #007bff;
                            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.1);
                        }
                        
                        &:disabled {
                            background-color: #e9ecef;
                            opacity: 0.6;
                        }
                        
                        &::placeholder {
                            color: #6c757d;
                        }
                    }
                    
                    textarea.input-field {
                        resize: vertical;
                        min-height: 70px;
                    }
                }
            }
        }
    }

    .material-selection {
        .material-option {
            background-color: $dark-gray;
            border: 1px solid #e9ecef;
            padding: 1rem;
            margin-bottom: 0.75rem;
            
            .material-header {
                display: flex;
                align-items: center;
                justify-items: center;
                margin-bottom: 0.75rem;
                
                input[type="radio"] {
                    margin-right: 0.5rem;
                    width: 25px;
                    height: 10px;
                    accent-color: #007bff;
                }
                
                .material-type-label {
                    color: $white;
                    font-size: 0.875rem;
                    font-weight: 500;
                    cursor: pointer;
                }
            }
            
            .material-selector {
                margin-left: 1.5rem;
            }
        }
        
        .material-divider {
            text-align: center;
            color: #6c757d;
            font-weight: 500;
            font-size: 0.8rem;
            margin: 1rem 0;
            position: relative;
            
            &::before, &::after {
                content: '';
                position: absolute;
                top: 50%;
                width: 45%;
                height: 1px;
                background-color: #e9ecef;
            }
            
            &::before {
                left: 0;
            }
            
            &::after {
                right: 0;
            }
        }
    }

    .actions-container {
        .action-item {
            background-color: $dark-gray;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            
            .action-row {
                display: grid;
                grid-template-columns: 1fr auto auto auto;
                gap: 0.75rem;
                align-items: end;
                
                .action-select, .action-quantity {
                    label {
                        display: block;
                        color: $white;
                        font-size: 0.8rem;
                        font-weight: 500;
                        margin-bottom: 0.375rem;
                    }
                }
                
                .action-controls {
                    display: flex;
                    gap: 0.375rem;
                    
                    .add-btn, .remove-btn {
                        padding: 0.375rem;
                        border: 1px solid #dee2e6;
                        border-radius: 4px;
                        background-color: $dark-gray;
                        cursor: pointer;
                        transition: all 0.15s;
                        
                        i {
                            font-size: 0.875rem;
                        }
                        
                        &:hover {
                            background-color: $dark-gray;
                        }
                    }
                    
                    .add-btn {
                        color: #28a745;
                        border-color: #28a745;
                        
                        &:hover {
                            background-color: #28a745;
                            color: white;
                        }
                    }
                    
                    .remove-btn {
                        color: #dc3545;
                        border-color: #dc3545;
                        
                        &:hover {
                            background-color: #dc3545;
                            color: white;
                        }
                    }
                }
            }
        }
    }

    .articles-container {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        
        @media (max-width: 768px) {
            flex-direction: column;
        }
        
        .article-section {
            flex: 1;
            background-color: $dark-gray;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 1rem;
            
            .article-section-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 1rem;
                
                h4 {
                    color: $white;
                    font-size: 0.875rem;
                    font-weight: 600;
                    display: flex;
                    align-items: center;
                    margin: 0;
                    
                    i {
                        margin-right: 0.375rem;
                        color: #6c757d;
                    }
                }
                
                .add-btn.small {
                    background-color: #28a745;
                    color: white;
                    border: none;
                    padding: 0.25rem 0.5rem;
                    border-radius: 4px;
                    font-size: 0.75rem;
                    font-weight: 500;
                    cursor: pointer;
                    transition: all 0.15s;
                    
                    &:hover {
                        background-color: #218838;
                    }
                    
                    i {
                        margin-right: 0.25rem;
                        font-size: 0.75rem;
                    }
                }
            }
            
            .empty-state {
                text-align: center;
                padding: 1.5rem;
                border: 1px dashed #dee2e6;
                border-radius: 4px;
                
                p {
                    color: #6c757d;
                    font-size: 0.8rem;
                    margin: 0;
                }
            }
            
            .article-list {
                .article-row {
                    display: flex;
                    align-items: center;
                    gap: 1rem;
                    margin-bottom: 0.75rem;
                    
                    &:last-child {
                        margin-bottom: 0;
                    }
                    
                    .article-select {
                        flex: 1;
                    }
                    
                    .article-quantity {
                        width: 25px;
                        margin-right: 1rem;
                    }
                    
                    .remove-btn {
                        background-color: #dc3545;
                        color: white;
                        border: none;
                        padding: 0.375rem;
                        border-radius: 4px;
                        cursor: pointer;
                        transition: all 0.15s;
                        
                        &:hover {
                            background-color: #c82333;
                        }
                        
                        i {
                            font-size: 0.875rem;
                        }
                    }
                }
            }
        }
    }

    .step-navigation-buttons {
        display: flex;
        justify-content: space-between;
        align-items: center;
        
        .nav-btn {
            display: flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            background-color: white;
            color: #495057;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.15s;
            text-decoration: none;
            
            i {
                font-size: 0.875rem;
            }
            
            &:hover:not(:disabled) {
                background-color: #e9ecef;
            }
            
            &:disabled {
                opacity: 0.6;
                cursor: not-allowed;
            }
            
            &.secondary {
                i {
                    margin-right: 0.375rem;
                }
            }
            
            &.primary {
                background-color: #007bff;
                color: white;
                border-color: #007bff;
                
                &:hover {
                    background-color: #0056b3;
                }
                
                i {
                    margin-left: 0.375rem;
                }
            }
            
            &.sync-only {
                background-color: #28a745;
                color: white;
                border-color: #28a745;
                
                &:hover:not(:disabled) {
                    background-color: #218838;
                }
                
                &:disabled {
                    opacity: 0.6;
                    cursor: not-allowed;
                    background-color: #6c757d;
                    border-color: #6c757d;
                }
                
                i {
                    margin-right: 0.375rem;
                }
            }
        }
        
        .nav-spacer {
            flex-grow: 1;
        }
    }

    // Modal Styles
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        
        .modal-content {
            background-color: $dark-gray;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            width: 90%;
            max-width: 900px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            
            .modal-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 1rem 1.5rem;
                border-bottom: 1px solid #e9ecef;
                background-color: $dark-gray;
                
                h3 {
                    color: $white;
                    font-size: 1.1rem;
                    font-weight: 600;
                    margin: 0;
                    display: flex;
                    align-items: center;
                    
                    i {
                        margin-right: 0.5rem;
                        color: #007bff;
                    }
                }
                
                .modal-close {
                    background: none;
                    border: none;
                    color: #6c757d;
                    font-size: 1.5rem;
                    cursor: pointer;
                    padding: 0.25rem;
                    
                    &:hover {
                        color: #dc3545;
                    }
                }
            }
            
            .modal-body {
                padding: 1.5rem;
                
                .summary-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                    gap: 1rem;
                    margin-bottom: 1.5rem;
                }
                
                .summary-card {
                    background-color: rgba(255, 255, 255, 0.05);
                    border: 1px solid #e9ecef;
                    border-radius: 6px;
                    padding: 1rem;
                    
                    h4 {
                        color: $white;
                        font-size: 0.875rem;
                        font-weight: 600;
                        margin-bottom: 0.75rem;
                        margin-top: 0;
                    }
                    
                    .summary-value {
                        color: #007bff;
                        font-size: 1.25rem;
                        font-weight: 600;
                    }
                    
                    .summary-details {
                        div {
                            margin-bottom: 0.375rem;
                            color: $white;
                            font-size: 0.8rem;
                            
                            &:last-child {
                                margin-bottom: 0;
                            }
                            
                            strong {
                                color: #6c757d;
                                font-weight: 500;
                            }
                        }
                    }
                    
                    &.cost-card {
                        .cost-breakdown {
                            .cost-row {
                                display: flex;
                                justify-content: space-between;
                                align-items: center;
                                padding: 0.25rem 0;
                                border-bottom: 1px solid #e9ecef;
                                
                                &:last-child {
                                    border-bottom: none;
                                }
                                
                                &.total {
                                    font-weight: 600;
                                    border-top: 1px solid #dee2e6;
                                    padding-top: 0.5rem;
                                    margin-top: 0.375rem;
                                    
                                    span:last-child {
                                        color: #28a745;
                                        font-size: 1rem;
                                    }
                                }
                                
                                span {
                                    color: $white;
                                    font-size: 0.8rem;
                                }
                            }
                        }
                    }
                }
                
                .validation-status {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 0.75rem 1rem;
                    border-radius: 6px;
                    font-size: 0.875rem;
                    font-weight: 500;
                    
                    i {
                        margin-right: 0.5rem;
                        font-size: 1rem;
                    }
                }
            }
            
            .modal-footer {
                display: flex;
                justify-content: flex-end;
                gap: 1rem;
                padding: 1rem 1.5rem;
                border-top: 1px solid #e9ecef;
                background-color: $dark-gray;
                
                .modal-btn {
                    display: flex;
                    align-items: center;
                    padding: 0.5rem 1rem;
                    border: 1px solid #dee2e6;
                    border-radius: 4px;
                    font-size: 0.875rem;
                    font-weight: 500;
                    cursor: pointer;
                    transition: all 0.15s;
                    text-decoration: none;
                    
                    i {
                        margin-right: 0.375rem;
                        font-size: 0.875rem;
                    }
                    
                    &:hover:not(:disabled) {
                        transform: translateY(-1px);
                    }
                    
                    &:disabled {
                        opacity: 0.6;
                        cursor: not-allowed;
                    }
                    
                    &.secondary {
                        background-color: #6c757d;
                        color: white;
                        border-color: #6c757d;
                        
                        &:hover {
                            background-color: #5a6268;
                        }
                    }
                    
                    &.primary {
                        background-color: #007bff;
                        color: white;
                        border-color: #007bff;
                        
                        &:hover:not(:disabled) {
                            background-color: #0056b3;
                        }
                        
                        &:disabled {
                            background-color: #6c757d;
                            border-color: #6c757d;
                        }
                    }
                }
            }
        }
    }

    .summary-section {
        background-color: white;
        border: 1px solid #e9ecef;
        border-radius: 6px;
        margin-top: 1rem;
        
        .summary-header {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
            background-color: $dark-gray;
            
            i {
                font-size: 1rem;
                color: #6c757d;
                margin-right: 0.5rem;
            }
            
            span {
                color: #495057;
                font-size: 0.95rem;
                font-weight: 600;
            }
        }
        
        .summary-content {
            padding: 1rem;
            
            .summary-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 1rem;
            }
            
            .summary-card {
                background-color: $dark-gray;
                border: 1px solid #e9ecef;
                border-radius: 4px;
                padding: 1rem;
                
                h4 {
                    color: #495057;
                    font-size: 0.875rem;
                    font-weight: 600;
                    margin-bottom: 0.75rem;
                    margin-top: 0;
                }
                
                .summary-value {
                    color: #007bff;
                    font-size: 1.25rem;
                    font-weight: 600;
                }
                
                .summary-details {
                    div {
                        margin-bottom: 0.375rem;
                        color: #495057;
                        font-size: 0.8rem;
                        
                        &:last-child {
                            margin-bottom: 0;
                        }
                        
                        strong {
                            color: #6c757d;
                            font-weight: 500;
                        }
                    }
                }
                
                &.cost-card {
                    .cost-breakdown {
                        .cost-row {
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            padding: 0.25rem 0;
                            border-bottom: 1px solid #e9ecef;
                            
                            &:last-child {
                                border-bottom: none;
                            }
                            
                            &.total {
                                font-weight: 600;
                                border-top: 1px solid #dee2e6;
                                padding-top: 0.5rem;
                                margin-top: 0.375rem;
                                
                                span:last-child {
                                    color: #28a745;
                                    font-size: 1rem;
                                }
                            }
                            
                            span {
                                color: $white;
                                font-size: 0.8rem;
                            }
                        }
                    }
                }
            }
        }
        
        .sync-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-top: 1px solid #e9ecef;
            background-color: $dark-gray;
            
            .validation-status {
                display: flex;
                align-items: center;
                padding: 0.5rem 0.75rem;
                border-radius: 4px;
                font-size: 0.8rem;
                font-weight: 500;
                
                i {
                    margin-right: 0.375rem;
                    font-size: 0.875rem;
                }
            }
            
            .sync-buttons {
                display: flex;
                gap: 0.75rem;
                
                .nav-btn {
                    display: flex;
                    align-items: center;
                    padding: 0.5rem 1rem;
                    border: 1px solid #dee2e6;
                    border-radius: 4px;
                    background-color: white;
                    color: #495057;
                    font-size: 0.875rem;
                    font-weight: 500;
                    cursor: pointer;
                    transition: all 0.15s;
                    text-decoration: none;
                    
                    i {
                        margin-right: 0.375rem;
                        font-size: 0.875rem;
                    }
                    
                    &:hover:not(:disabled) {
                        transform: translateY(-1px);
                    }
                    
                    &:disabled {
                        opacity: 0.6;
                        cursor: not-allowed;
                    }
                    
                    &.secondary {
                        &:hover {
                            background-color: #6c757d;
                            color: white;
                        }
                    }
                }
                
                .sync-btn {
                    display: flex;
                    align-items: center;
                    padding: 0.5rem 1.5rem;
                    background-color: #007bff;
                    color: white;
                    border: 1px solid #007bff;
                    border-radius: 4px;
                    font-size: 0.875rem;
                    font-weight: 500;
                    cursor: pointer;
                    transition: all 0.15s;
                    text-decoration: none;
                    
                    &:hover:not(:disabled) {
                        background-color: #0056b3;
                    }
                    
                    &:disabled {
                        opacity: 0.6;
                        cursor: not-allowed;
                        background-color: #6c757d;
                        border-color: #6c757d;
                    }
                    
                    i {
                        margin-right: 0.375rem;
                        font-size: 0.875rem;
                    }
                }
            }
        }
    }

    .validation-error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .validation-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .validation-processing {
        background-color: #d1ecf1;
        color: #0c5460;
        border: 1px solid #bee5eb;
    }

    .sameRow {
        display: flex;
        align-items: center;
        margin-bottom: 1rem;
    }

    .sameColumn {
        display: flex;
        flex-direction: column;
        margin-bottom: 1rem;
    }

    .form-group label {
        margin-right: 1rem;
        color: $white;
        position: relative;
    }

    .addBtn {
        color: $light-green;
        margin-left: 8px;
        cursor: pointer;
    }

    .removeBtn {
        color: #c95050;
        margin-left: 7px;
        cursor: pointer;
    }

    .button-container {
        display: flex;
        justify-content: flex-end;
        margin:5px;
        padding: 5px;
    }

    .label-fixed-width {
        width: 11rem;
    }

    .select-fixed-width {
        width: 15rem;
    }

    input, select, .multiselect {
        height: 36px;
        min-height: 26px;
    }

    .rowButtons {
        display: flex;
        flex-direction: row;
        justify-content: right;
        gap: 10px;
    }
    </style>


<template>
    <div v-if="$props.jobs?.length > 0">
        <table class="border">
            <tbody>
            <tr v-for="(job, index) in jobsToDisplay" :key="`job-row-${job.id}`">
                <!-- ORDER INDEX, NAME, AND ADDITIONAL INFO -->
                <div class="text-white">
                    <td class="text-black bg-gray-200 font-weight-black flex justify-between items-center" style="padding: 0 0 0 5px">
                        <span class="bold">#{{ index + 1 }} {{ job.name }}</span>
                        <div class="flex gap-2">
                            <button
                                @click="refreshJobState(job)"
                                class="refresh-btn text-blue-600 hover:text-blue-800"
                                :disabled="jobRefreshStates[job.id] === 'refreshing'"
                                title="Refresh job state"
                            >
                                <i v-if="jobRefreshStates[job.id] !== 'refreshing'" class="fa fa-refresh"></i>
                                <i v-else class="fa fa-spinner fa-spin"></i>
                            </button>
                            <button
                                @click="confirmDelete(job)"
                                class="delete-btn text-red-600 hover:text-red-800"
                                :disabled="jobDeletionStates[job.id] === 'deleting'"
                            >
                                <i v-if="jobDeletionStates[job.id] !== 'deleting'" class="fa fa-times"></i>
                                <i v-else class="fa fa-spinner fa-spin"></i>
                            </button>
                        </div>
                    </td>
                    <td> File: <span class="bold">{{ job.file }}</span></td>
                    <td>ID: <span class="bold">{{ job.id }}</span></td>
                    <td>
                        {{ $t('Quantity') }}:
                        <span
                            class="bold editable bg-white/20"
                            :class="{ 'disabled': !hasOriginalFiles(job) }"
                            @dblclick="hasOriginalFiles(job) && startEditing(job, 'quantity')"
                            v-if="!(editingJob?.id === job.id && editingField === 'quantity')"
                        >
                            {{ job.quantity }}
                        </span>
                        <input
                            min="1"
                            v-else
                            type="number"
                            v-model="editingValue"
                            @keyup.enter="saveEdit(job)"
                            @blur="saveEdit(job)"
                            :ref="el => { if (el) quantityInput = el }"
                            class="edit-input"
                        />
                    </td>
                    <td>
                        {{ $t('Copies') }}:
                        <span
                            class="bold editable bg-white/20"
                            :class="{ 'disabled': !hasOriginalFiles(job) }"
                            @dblclick="hasOriginalFiles(job) && startEditing(job, 'copies')"
                            v-if="!(editingJob?.id === job.id && editingField === 'copies')"
                        >
                            {{ job.copies }}
                        </span>
                        <input
                            min="1"
                            v-else
                            type="number"
                            v-model="editingValue"
                            @keyup.enter="saveEdit(job)"
                            @blur="saveEdit(job)"
                            :ref="el => { if (el) copiesInput = el }"
                            class="edit-input"
                        />
                    </td>
                    <td>
                        <div v-if="job.machinePrint" class="machine-field">
                            {{ $t('machineP') }}: 
                            <span
                                class="bold editable bg-white/20"
                                :class="{ 'disabled': !hasOriginalFiles(job) }"
                                @dblclick="hasOriginalFiles(job) && startEditingMachine(job, 'machinePrint')"
                                v-if="!(editingJob?.id === job.id && editingField === 'machinePrint')"
                            >
                                {{ job.machinePrint }}
                            </span>
                            <select
                                v-else
                                v-model="editingValue"
                                @change="saveMachineEdit(job)"
                                @blur="saveMachineEdit(job)"
                                :ref="el => { if (el) machinePrintInput = el }"
                                class="edit-select"
                            >
                                <option value="">{{ $t('Select Machine') || 'Select a Print Machine' }}</option>
                                <option v-for="machine in machinesPrint" :key="machine.id" :value="machine.name">
                                    {{ machine.name }}
                                </option>
                            </select>
                        </div>
                    </td>
                    <td>
                        <div v-if="job.machineCut" class="machine-field">
                            {{ $t('machineC') }}: 
                            <span
                                class="bold editable bg-white/20"
                                :class="{ 'disabled': !hasOriginalFiles(job) }"
                                @dblclick="hasOriginalFiles(job) && startEditingMachine(job, 'machineCut')"
                                v-if="!(editingJob?.id === job.id && editingField === 'machineCut')"
                            >
                                {{ job.machineCut }}
                            </span>
                            <select
                                v-else
                                v-model="editingValue"
                                @change="saveMachineEdit(job)"
                                @blur="saveMachineEdit(job)"
                                :ref="el => { if (el) machineCutInput = el }"
                                class="edit-select"
                            >
                                <option value="">{{ $t('Select Machine') || 'Select a Cut Machine' }}</option>
                                <option v-for="machine in machinesCut" :key="machine.id" :value="machine.name">
                                    {{ machine.name }}
                                </option>
                            </select>
                        </div>
                    </td>
                </div>

                <!-- FILE INFO -->
                <div class="flex text-white">
                    <td>
                        <FileUploadManager
                            :key="`stable-file-manager-${job.id}`"
                            :job-id="job.id"
                            :files="getStableOriginalFiles(job.id)"
                            upload-type="general"
                            accepted-types=".pdf"
                            @files-updated="handleFilesUpdated(job, $event)"
                            @file-removed="handleFileRemoved(job, $event)"
                            @upload-started="handleUploadStarted(job)"
                            @upload-completed="handleUploadCompleted(job, $event)"
                            @upload-failed="handleUploadFailed(job, $event)"
                            @preview-requested="openPreviewModal({ index: $event }, job)"
                        />
                    </td>
                    <td>
                        <CuttingFileUploadManager
                            :key="`stable-cutting-manager-${job.id}`"
                            :job-id="job.id"
                            :files="getStableCuttingFiles(job.id)"
                            @files-updated="handleCuttingFilesUpdated(job, $event)"
                            @file-removed="handleCuttingFileRemoved(job, $event)"
                            @upload-started="handleCuttingUploadStarted(job)"
                            @upload-completed="handleCuttingUploadCompleted(job, $event)"
                            @upload-failed="handleCuttingUploadFailed(job, $event)"
                        />
                    </td>
                    <td>
                        <div v-if="job.dimensions_breakdown && job.dimensions_breakdown.length > 0 && job.total_area_m2 > 0">
                            <div class="files-container" style="max-width: 30rem;">
                                <div class="files-scroll">
                                    <div v-for="(file, fileIndex) in job.dimensions_breakdown" :key="fileIndex" class="file-dimensions">
                                        <div class="file-name" :title="file.filename">{{ file.filename }}</div>
                                        <div v-for="(page, pageIndex) in file.page_dimensions" :key="pageIndex" class="page-dimensions">
                                            <span class="page-label">Page {{ page.page }}:</span>
                                            <span class="dimensions">{{ (page.width_mm && !isNaN(parseFloat(page.width_mm))) ? parseFloat(page.width_mm).toFixed(2) : '0.00' }}×{{ (page.height_mm && !isNaN(parseFloat(page.height_mm))) ? parseFloat(page.height_mm).toFixed(2) : '0.00' }}mm</span>
                                        </div>
                                        <div class="file-total">
                                            <span class="file-total-label">File Total:</span>
                                            <span class="file-total-area">{{ (file.total_area_m2 && typeof file.total_area_m2 === 'number') ? file.total_area_m2.toFixed(4) : '0.0000' }}m²</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="total-area">
                                <strong>Job Total: {{ (job.total_area_m2 && typeof job.total_area_m2 === 'number') ? job.total_area_m2.toFixed(4) : '0.0000' }}m²</strong>
                            </div>
                        </div>
                        <div v-else>
                            <div class="files-container" style="max-width: 30rem;">
                                <div class="files-scroll">
                                    <div class="file-dimensions">
                                        <div class="file-name">{{ $t('Dimensions') }}</div>
                                        <div class="page-dimensions">
                                            <span class="page-label">{{ $t('width') }}:</span>
                                            <span class="dimensions">0.00mm</span>
                                        </div>
                                        <div class="page-dimensions">
                                            <span class="page-label">{{ $t('height') }}:</span>
                                            <span class="dimensions">0.00mm</span>
                                        </div>
                                        <div class="file-total">
                                            <span class="file-total-label">Area:</span>
                                            <span class="file-total-area">0.0000m²</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="total-area">
                                <strong>Job Total: 0.0000m²</strong>
                            </div>
                        </div>
                    </td>
                </div>

                <!-- ACTIONS SECTION -->
                <div v-if="job.actions && job.actions.length > 0">
                    <td>
                        <div class="materials-header green p-1 pl-1 w-[40rem] text-white bg-gray-700" style="cursor: pointer" @click="toggleActions(job.id)">
                            <span>ACTIONS </span>
                            <button class="materials-toggle-btn" @click.stop="toggleActions(job.id)">
                                <i class="fa" :class="showActions[job.id] ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                            </button>
                        </div>
                        <transition name="slide-fade">
                            <div v-if="showActions[job.id] !== false" class="ultra-light-green text-white pb-1">
                                <OrderJobProgressCompact 
                                class="bg-gray-700"
                                    :job="job" 
                                    :invoiceId="invoiceId" 
                                    :clickable="false"
                                    :showLabel="false"
                                />
                            </div>
                        </transition>
                    </td>
                </div>

                <!-- MATERIALS SECTION -->
                <div v-if="job.articles && job.articles.length > 0">
                    <td>
                        <div class="materials-header blue p-1 pl-1 w-[40rem] text-white bg-gray-700" style="cursor: pointer" @click="toggleMaterials(job.id)">
                            <span>{{$t('MATERIALS')}}</span>
                            <button class="materials-toggle-btn" @click.stop="toggleMaterials(job.id)">
                                <i class="fa" :class="showMaterials[job.id] ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                            </button>
                        </div>
                        <transition name="slide-fade">
                            <div v-if="showMaterials[job.id] !== false" class="ultra-light-blue text-white pb-1">
                                <div v-for="(article, articleIndex) in job.articles" :key="articleIndex" class="materials-item bg-gray-700 pl-1 w-full text-left py-1">
                                    <span class="text-sm">{{articleIndex + 1}}. {{ article.name }} ({{ article.pivot.quantity }} {{ article.in_square_meters ? 'm²' : (article.in_pieces ? 'ком.' : (article.in_kilograms ? 'кг' : (article.in_meters ? 'м' : 'ед.'))) }})</span>
                                </div>
                            </div>
                        </transition>
                    </td>
                </div>

                <!-- CALCULATION SETTINGS SECTION -->
                <div v-if="job.catalog_item_id">
                    <td>
                        <div class="calculation-settings-header orange p-1 pl-1 w-[40rem] text-white bg-gray-700">
                            <span>Calculation Settings</span>
                        </div>
                        <div class="calculation-settings-content bg-gray-600 pl-1 w-full text-left py-1">
                            <span class="text-sm text-white">{{ getCalculationMethod(job) }}</span>
                        </div>
                    </td>
                </div>

                <!-- SHIPPING INFO -->
                <div class="flex justify-between">
                    <td class="flex items-center bg-gray-200 text-black" style="padding: 0 5px 0 0;">
                        <img src="/images/shipping.png" class="w-8 h-8 pr-1" alt="Shipping">
                        {{ $t('Shipping') }}: <strong> {{ job.shippingInfo }}</strong>
                    </td>
                    <div v-if="!isRabotnikComputed" class="bg-gray-200 text-black bold">
                        <div class="pt-1 pl-2 pr-2 pb-2 flex items-center gap-2">
                                                            {{ $t('jobPrice') }}: <span class="bold">{{ ((job.salePrice || job.price) && typeof (job.salePrice || job.price) === 'number') ? (job.salePrice || job.price).toFixed(2) : '0.00' }} ден.</span>
                        </div>
                        <div class="pt-1 pl-2 pr-2 flex items-center gap-2">
                                                            {{ $t('jobPriceCost') }}: <span class="bold">{{ (job.price && typeof job.price === 'number') ? job.price.toFixed(2) : '0.00' }} ден.</span>
                            <button 
                                @click="showCostBreakdown(job)" 
                                class="info-btn"
                                title="Show cost breakdown"
                            >
                                <i class="fa fa-info-circle"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </tr>
            </tbody>
        </table>

        <!-- Confirmation Dialog -->
        <div v-if="showDeleteConfirm" class="confirmation-dialog">
            <div class="dialog-content">
                <p>Are you sure you want to delete this job?</p>
                <div class="dialog-buttons">
                    <button
                        @click="deleteJob"
                        class="confirm-btn"
                    >
                        Yes, Delete
                    </button>
                    <button
                        @click="showDeleteConfirm = false"
                        class="cancel-btn"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>

        <!-- Preview Modal -->
        <div v-if="showPreviewModal" class="preview-modal" @click="closePreviewModal">
            <div class="preview-modal-content" @click.stop>
                <button @click="closePreviewModal" class="preview-close-btn">
                    <i class="fa fa-times"></i>
                </button>
                
                <!-- Display PDF using iframe if it's a PDF file -->
                <iframe 
                    v-if="previewFile && previewFile.type === 'pdf'"
                    :src="previewFile.url" 
                    class="preview-pdf"
                    frameborder="0"
                >
                    <p>Your browser does not support PDFs. <a :href="previewFile.url" target="_blank">Download the PDF</a>.</p>
                </iframe>
                
                <!-- Display image thumbnail if available -->
                <img 
                    v-else-if="previewImage" 
                    :src="previewImage" 
                    alt="Preview" 
                    class="preview-image" 
                />
                
                <!-- Fallback message -->
                <div v-else class="preview-fallback">
                    <i class="fa fa-file-pdf"></i>
                    <p>Preview not available</p>
                </div>
            </div>
        </div>

        <!-- Cutting File Preview Modal -->
        <div v-if="showCuttingFilePreviewModal" class="preview-modal" @click="closeCuttingFilePreviewModal">
            <div class="preview-modal-content" @click.stop>
                <button @click="closeCuttingFilePreviewModal" class="preview-close-btn">
                    <i class="fa fa-times"></i>
                </button>
                
                <!-- Display PDF using iframe if it's a PDF file -->
                <iframe 
                    v-if="cuttingPreviewFile && cuttingPreviewFile.type === 'pdf'"
                    :src="cuttingPreviewFile.url" 
                    class="preview-pdf"
                    frameborder="0"
                >
                    <p>Your browser does not support PDFs. <a :href="cuttingPreviewFile.url" target="_blank">Download the PDF</a>.</p>
                </iframe>
                
                <!-- Display SVG or other files -->
                <iframe 
                    v-else-if="cuttingPreviewFile && (cuttingPreviewFile.type === 'svg' || cuttingPreviewFile.type === 'dxf' || cuttingPreviewFile.type === 'cdr' || cuttingPreviewFile.type === 'ai')"
                    :src="cuttingPreviewFile.url" 
                    class="preview-pdf"
                    frameborder="0"
                >
                    <p>Your browser does not support this file type. <a :href="cuttingPreviewFile.url" target="_blank">Download the file</a>.</p>
                </iframe>
                
                <!-- Fallback message -->
                <div v-else class="preview-fallback">
                    <i class="fa fa-file-o"></i>
                    <p>Preview not available for this file type</p>
                    <a v-if="cuttingPreviewFile" :href="cuttingPreviewFile.url" target="_blank" class="download-link">
                        <i class="fa fa-download"></i> Download File
                    </a>
                </div>
            </div>
        </div>

        <!-- Cost Breakdown Modal -->
        <CostBreakdownModal 
            :visible="showCostBreakdownModal" 
            :job="selectedJob" 
            @close="closeCostBreakdownModal" 
        />

    </div>
</template>

<script>
import { useToast } from "vue-toastification";
import axios from "axios";
import useRoleCheck from '@/Composables/useRoleCheck';
import { computed, ref } from 'vue';
import OrderJobProgressCompact from './OrderJobProgressCompact.vue';
import CostBreakdownModal from '@/Components/CostBreakdownModal.vue';
import { uploadManager } from '@/utils/UploadManager.js';
import { uploadFileInParts } from '@/utils/r2Multipart.js';
import FileUploadManager from '@/Components/FileUploadManager.vue';
import CuttingFileUploadManager from '@/Components/CuttingFileUploadManager.vue';

export default {
    name: "OrderLines",
    components: {
        OrderJobProgressCompact,
        CostBreakdownModal,
        FileUploadManager,
        CuttingFileUploadManager,
    },
    setup() {
        const { isRabotnik } = useRoleCheck();
        
        const isRabotnikComputed = computed(() => isRabotnik.value);
        
        // Create refs for section state
        const showMaterials = ref({});
        const showActions = ref({});
        
        // Create methods that can access the refs
        const toggleActions = (jobId) => {
            showActions.value[jobId] = !showActions.value[jobId];
        };
        
        const toggleMaterials = (jobId) => {
            showMaterials.value[jobId] = !showMaterials.value[jobId];
        };
        
        return {
            isRabotnikComputed,
            showMaterials,
            showActions,
            toggleActions,
            toggleMaterials
        };
    },

    props: {
        jobs: Array,
        updatedJobs: Array,
        invoiceId: String,
    },

    data() {
        return {
            jobsWithPrices: [],
            editingJob: null,
            editingField: null,
            editingValue: null,
            quantityInput: null,
            copiesInput: null,
            showDeleteConfirm: false,
            jobToDelete: null,
            jobThumbnails: {}, // Store thumbnails for each job
            showPreviewModal: false,
            previewImage: null,
            previewFile: null, // Store file info for PDF preview
            previewUrl: null, // Store the direct URL for iframe
            selectedThumbnail: null, // Store selected thumbnail info
            machinesPrint: [], // Available print machines
            machinesCut: [], // Available cut machines
            machinePrintInput: null,
            machineCutInput: null,
            // Cutting files data
            jobCuttingFiles: {}, // Store cutting files for each job
            // Upload manager instance
            uploadManager: uploadManager,
            showCuttingFilePreviewModal: false,
            cuttingPreviewFile: null,
            // File removal states
            fileRemovalStates: {}, // Track file removal states: 'idle', 'removing', 'complete', 'error'
            // Cutting file removal states
            cuttingFileRemovalStates: {}, // Track cutting file removal states: 'idle', 'removing', 'complete', 'error'
            // Job deletion states
            jobDeletionStates: {}, // Track job deletion states: 'idle', 'deleting', 'complete', 'error'
            // Job refresh states
            jobRefreshStates: {}, // Track job refresh states: 'idle', 'refreshing', 'complete', 'error'
            // Cost breakdown modal
            showCostBreakdownModal: false,
            selectedJob: null,
            costBreakdown: {},
            // Upload state tracking to prevent component re-creation
            activeUploads: new Set(), // Track jobs with active uploads
            stableFileCache: {}, // Cache stable file arrays during uploads
            stableJobCache: {} // Cache stable job objects during uploads
        };
    },

    computed: {
        jobsToDisplay() {
            const mergedJobs = [...this.jobs || [], ...this.updatedJobs || [], ...this.jobsWithPrices || []];
            
            // Debug logging
            // Skip expensive operations during uploads

            // Create a Map to store jobs by ID, with later entries overriding earlier ones
            const jobMap = new Map();

            // Process all jobs - later ones will override earlier ones with same ID
            for (const job of mergedJobs) {
                const jobId = job.id;
                
                // If upload is active, return cached stable job object
                if (this.activeUploads.has(jobId) && this.stableJobCache && this.stableJobCache[jobId]) {
                    jobMap.set(jobId, this.stableJobCache[jobId]);
                    continue;
                }
                
                // Create normalized job object
                const normalizedJob = {
                    ...job,
                    // Ensure numeric fields are properly typed
                    quantity: parseInt(job.quantity) || 1,
                    copies: parseInt(job.copies) || 1,
                    price: parseFloat(job.price) || 0,
                    salePrice: parseFloat(job.salePrice) || null
                };
                
                // Cache the job object for stability during uploads
                if (!this.stableJobCache) this.stableJobCache = {};
                this.stableJobCache[jobId] = normalizedJob;
                
                jobMap.set(jobId, normalizedJob);
            }

            // Convert Map values back to array and sort by ID
            const result = Array.from(jobMap.values()).sort((a, b) => a.id - b.id);
            
            // If we have active uploads, try to return a stable array reference
            if (this.activeUploads.size > 0) {
                if (!this._stableJobsArray) {
                    this._stableJobsArray = result;
                } else {
                    // Only update if the array actually changed (not just object references)
                    const currentIds = result.map(j => j.id).sort().join(',');
                    const cachedIds = this._stableJobsArray.map(j => j.id).sort().join(',');
                    
                    if (currentIds !== cachedIds) {
                        this._stableJobsArray = result;
                    } else {
                        return this._stableJobsArray;
                    }
                }
                return this._stableJobsArray;
            } else {
                // Clear stable array when no uploads active
                this._stableJobsArray = null;
            }
            
            return result;
        },

        fileJobs() {
            return this.jobsToDisplay.filter(job => job.file && job.file !== 'placeholder.jpeg');
        },

        // Helper method to check if a job has original files
        hasOriginalFiles() {
            return (job) => {
                return job.originalFile && job.originalFile.length > 0;
            };
        },

        // Stable file computed properties that prevent re-renders during uploads
        getStableOriginalFiles() {
            return (jobId) => {
                const cacheKey = `${jobId}_originalFile`;
                
                // If upload is active, return cached version (stable reference)
                if (this.activeUploads.has(jobId) && this.stableFileCache[cacheKey]) {
                    return this.stableFileCache[cacheKey];
                }
                
                // Find the job
                const job = this.jobsToDisplay.find(j => j.id === jobId);
                if (!job) return [];
                
                // Get current files
                const currentFiles = job.originalFile || [];
                
                // Check if cached version matches current files (avoid unnecessary updates)
                const cachedFiles = this.stableFileCache[cacheKey];
                if (cachedFiles && JSON.stringify(cachedFiles) === JSON.stringify(currentFiles)) {
                    return cachedFiles; // Return existing stable reference
                }
                
                // Update cache with new frozen array only when files actually changed
                this.stableFileCache[cacheKey] = Object.freeze([...currentFiles]);
                
                return this.stableFileCache[cacheKey];
            };
        },

        getStableCuttingFiles() {
            return (jobId) => {
                const cacheKey = `${jobId}_cuttingFiles`;
                
                // If upload is active, return cached version (stable reference)
                if (this.activeUploads.has(jobId) && this.stableFileCache[cacheKey]) {
                    return this.stableFileCache[cacheKey];
                }
                
                // Find the job
                const job = this.jobsToDisplay.find(j => j.id === jobId);
                if (!job) return [];
                
                // Get current files
                const currentFiles = job.cuttingFiles || [];
                
                // Check if cached version matches current files (avoid unnecessary updates)
                const cachedFiles = this.stableFileCache[cacheKey];
                if (cachedFiles && JSON.stringify(cachedFiles) === JSON.stringify(currentFiles)) {
                    return cachedFiles; // Return existing stable reference
                }
                
                // Update cache with new frozen array only when files actually changed
                this.stableFileCache[cacheKey] = Object.freeze([...currentFiles]);
                
                return this.stableFileCache[cacheKey];
            };
        },

    },

    mounted() {
        // Load thumbnails for all existing jobs that have original files
        this.jobsToDisplay.forEach(job => {
            if (job.id && job.originalFile && job.originalFile.length > 0) {
                this.loadJobThumbnails(job.id);
            }
            // Initialize upload states for all jobs
            this.initializeJobStates(job.id);
            
            // Initialize sections to be open by default
            if (job.actions && job.actions.length > 0) {
                this.showActions[job.id] = true;
            }
            if (job.articles && job.articles.length > 0) {
                this.showMaterials[job.id] = true;
            }
        });

        // Load available machines
        this.loadMachines();
        
        // Initialize cutting file states for all jobs
        this.jobsToDisplay.forEach(job => {
            this.initializeCuttingJobStates(job.id);
            if (job.id && job.cuttingFiles && job.cuttingFiles.length > 0) {
                this.loadJobCuttingFiles(job.id);
            }
        });
    },

    watch: {
        jobs: {
            handler(newJobs) {
                // Clean up jobsWithPrices for jobs that no longer exist in the main jobs array
                if (newJobs && this.jobsWithPrices.length > 0) {
                    const jobIds = newJobs.map(job => job.id);
                    this.jobsWithPrices = this.jobsWithPrices.filter(job => jobIds.includes(job.id));
                }
            },
            deep: true
        },
        updatedJobs: {
            handler(newUpdatedJobs) {
                // Clean up jobsWithPrices for jobs that no longer exist in the updatedJobs array
                if (newUpdatedJobs && this.jobsWithPrices.length > 0) {
                    const jobIds = newUpdatedJobs.map(job => job.id);
                    this.jobsWithPrices = this.jobsWithPrices.filter(job => jobIds.includes(job.id));
                }
            },
            deep: true
        },
        jobsToDisplay: {
            handler(newJobs) {
                // Initialize states for new jobs
                newJobs.forEach(job => {
                    this.initializeJobStates(job.id);
                    this.initializeCuttingJobStates(job.id);
                    
                    // Initialize sections to be open by default for new jobs
                    if (job.actions && job.actions.length > 0 && !this.showActions[job.id]) {
                        this.showActions[job.id] = true;
                    }
                    if (job.articles && job.articles.length > 0 && !this.showMaterials[job.id]) {
                        this.showMaterials[job.id] = true;
                    }
                    
                    // Check if thumbnails need to be synchronized
                    if (job.originalFile && job.originalFile.length > 0) {
                        const currentThumbnails = this.jobThumbnails[job.id] || [];
                        if (currentThumbnails.length !== job.originalFile.length) {
                            // Thumbnail count doesn't match, reload them
                            this.loadJobThumbnails(job.id);
                        }
                    } else if (this.jobThumbnails[job.id] && this.jobThumbnails[job.id].length > 0) {
                        // No original files but thumbnails exist, clear them
                        this.jobThumbnails[job.id] = [];
                        this.$forceUpdate();
                    }
                    
                                // Check if cutting file thumbnails need to be synchronized
            if (job.cuttingFiles && job.cuttingFiles.length > 0) {
                const currentCuttingThumbnails = this.jobCuttingFiles[job.id] || [];
                if (currentCuttingThumbnails.length !== job.cuttingFiles.length) {
                    // Cutting thumbnail count doesn't match, reload them
                    this.loadJobCuttingFiles(job.id);
                }
            } else if (this.jobCuttingFiles[job.id] && this.jobCuttingFiles[job.id].length > 0) {
                // No cutting files but thumbnails exist, clear them
                this.jobCuttingFiles[job.id] = [];
                this.$forceUpdate();
            }
                });
                
                // Force UI update to ensure dimensions are displayed correctly
                this.$nextTick(() => {
                    this.forceUpdateDimensions();
                });
            },
            deep: true
        }
    },

    methods: {
        // Get calculation method for display
        getCalculationMethod(job) {
            if (job.catalog_item) {
                return job.catalog_item.by_copies ? 'By Copies' : 'By Quantity';
            }
            return 'By Quantity'; // Default fallback
        },

        // Cost breakdown methods
        async showCostBreakdown(job) {
            try {
                this.selectedJob = job;
                this.showCostBreakdownModal = true;
                
                // Get detailed cost breakdown from the backend
                const response = await axios.post('/jobs/recalculate-cost', {
                    job_id: job.id,
                    total_area_m2: parseFloat(job.total_area_m2) || 0,
                    quantity: parseInt(job.quantity) || 1,
                    copies: parseInt(job.copies) || 1
                });
                
                // Ensure we have valid data
                const componentBreakdown = Array.isArray(response.data.component_breakdown) ? response.data.component_breakdown : [];
                const materialDeduction = Array.isArray(response.data.material_deduction) ? response.data.material_deduction : [];
                
                this.costBreakdown = {
                    total_cost: parseFloat(response.data.price) || 0,
                    component_breakdown: componentBreakdown,
                    material_deduction: materialDeduction
                };
            } catch (error) {
                console.error('Failed to get cost breakdown:', error);
                // Use stored job data as fallback
                this.costBreakdown = {
                    total_cost: parseFloat(job.price) || 0,
                    component_breakdown: [],
                    material_deduction: []
                };
            }
        },

        closeCostBreakdownModal() {
            this.showCostBreakdownModal = false;
            this.selectedJob = null;
            this.costBreakdown = {};
        },

        // Initialize upload states for a job
        initializeJobStates(jobId) {
            // Initialize upload manager for general files
            this.uploadManager.initializeJob(jobId, 'general');
            // Initialize file removal state
            if (!this.fileRemovalStates[jobId]) {
                this.fileRemovalStates[jobId] = 'idle';
            }
            // Initialize job refresh state
            if (!this.jobRefreshStates[jobId]) {
                this.jobRefreshStates[jobId] = 'idle';
            }
        },
        triggerFilesInput(jobId) {
            // Don't trigger if upload is disabled
            if (this.isGeneralUploadDisabled(jobId)) {
                return;
            }
            
            const fileInput = document.getElementById('files-input-' + jobId);
            if (fileInput && !fileInput.disabled) {
                fileInput.click();
            }
        },
        getImageUrl(id) {
            const job = this.jobsToDisplay.find(j => j.id === id);
            return job && job.file !== 'placeholder.jpeg'
                ? `/storage/uploads/${job.file}`
                : '/storage/uploads/placeholder.jpeg';
        },

        getJobThumbnails(job) {
            // If we have loaded thumbnails for this job, return them
            if (this.jobThumbnails[job.id] && this.jobThumbnails[job.id].length > 0) {
                // Ensure the number of thumbnails matches the number of original files
                const originalFileCount = job.originalFile ? job.originalFile.length : 0;
                const thumbnailCount = this.jobThumbnails[job.id].length;
                
                // If counts don't match, clear thumbnails and let them reload
                if (originalFileCount !== thumbnailCount) {
                    this.jobThumbnails[job.id] = [];
                    this.$forceUpdate();
                    return [];
                }
                
                return this.jobThumbnails[job.id];
            }
            
            // If job has original files but no thumbnails loaded, create placeholder thumbnails
            if (job.originalFile && job.originalFile.length > 0) {
                return job.originalFile.map((file, index) => ({
                    type: 'file',
                    thumbnailUrl: null, // No thumbnail available yet
                    originalFile: file,
                    index: index,
                    filename: this.getFileName(file),
                    imageLoadError: false, // Initialize error state
                    imageLoaded: false // Add imageLoaded state
                }));
            }
            
            // No files at all
            return [];
        },

        async loadJobThumbnails(jobId) {
            try {
                // this.thumbnailLoading[jobId] = true; // Removed global loading state
                this.$forceUpdate();
                
                const response = await axios.get(`/jobs/${jobId}/thumbnails`);
                
                if (response.data.thumbnails && response.data.thumbnails.length > 0) {
                    // Initialize imageLoadError and imageLoaded for each thumbnail
                    this.jobThumbnails[jobId] = response.data.thumbnails.map(thumb => ({
                        ...thumb,
                        imageLoadError: false,
                        imageLoaded: false // Mark as not loaded initially
                    }));
                } else {
                    this.jobThumbnails[jobId] = [];
                }
                
                // Force reactivity update
                this.$forceUpdate();
            } catch (error) {
                console.error('Failed to load thumbnails for job', jobId, error);
                // Clear thumbnails on error to prevent stale data
                this.jobThumbnails[jobId] = [];
                this.$forceUpdate();
            } finally {
                // this.thumbnailLoading[jobId] = false; // Removed global loading state
                this.$forceUpdate();
            }
        },

        async openPreviewModal(thumbnail, job) {
            const toast = useToast();
            
            try {
                // Use backend route to serve PDF with authentication (similar to catalog template preview)
                const jobId = job.id;
                const fileIndex = thumbnail.index;
                
                // Create backend URL for authenticated PDF serving
                const backendUrl = route('jobs.viewOriginalFile', { jobId: jobId, fileIndex: fileIndex });
                
                this.selectedThumbnail = thumbnail;
                this.previewUrl = backendUrl;
                this.previewFile = {
                    url: backendUrl,
                    type: 'pdf'
                };
                this.previewImage = null;
                this.showPreviewModal = true;
            } catch (error) {
                console.error('Failed to open PDF preview:', error);
                toast.error('Failed to open PDF preview');
            }
        },

        closePreviewModal() {
            this.showPreviewModal = false;
            this.previewImage = null;
            this.previewFile = null;
            this.previewUrl = null;
            this.selectedThumbnail = null;
        },

        getThumbnailUrl(jobId, fileIndex) {
            // Use backend route to serve thumbnail with authentication and cache busting
            const timestamp = Date.now(); // Add cache busting parameter
            const url = route('jobs.viewThumbnail', { jobId: jobId, fileIndex: fileIndex }) + `?t=${timestamp}`;
            return url;
        },

        // Build authenticated PDF URL for inline iframe preview
        getPdfUrl(job, fileIndex) {
            const url = route('jobs.viewOriginalFile', { jobId: job.id, fileIndex: fileIndex });
            const filePath = job.originalFile?.[fileIndex] || '';
            const stamp = encodeURIComponent(filePath);
            return `${url}?v=${stamp}`;
        },

        handleThumbnailError(thumb, event) {
            const imgElement = event.target;
            console.error('Thumbnail failed to load:', imgElement.src);
            thumb.imageLoadError = true;
            this.$forceUpdate();
        },

        handleThumbnailLoad(thumb) {
            thumb.imageLoaded = true;
            thumb.imageLoadError = false;
        },



        async refreshThumbnails(jobId) {
            const toast = useToast();
            
            try {
                // this.thumbnailLoading[jobId] = true; // Removed global loading state
                this.$forceUpdate();
                
                // Clear existing thumbnails completely
                this.jobThumbnails[jobId] = [];
                this.$forceUpdate();
                
                // Reload thumbnails
                await this.loadJobThumbnails(jobId);
                
                toast.success('Thumbnails refreshed');
            } catch (error) {
                console.error('Failed to refresh thumbnails:', error);
                toast.error('Failed to refresh thumbnails');
            } finally {
                // this.thumbnailLoading[jobId] = false; // Removed global loading state
                this.$forceUpdate();
            }
        },

        // Force refresh thumbnails for a job (used when files are replaced)
        async forceRefreshThumbnails(jobId) {
            try {
                // Clear thumbnails and force update
                this.jobThumbnails[jobId] = [];
                this.$forceUpdate();
                
                // Wait a moment for backend processing
                await new Promise(resolve => setTimeout(resolve, 500));
                
                // Reload thumbnails
                await this.loadJobThumbnails(jobId);
            } catch (error) {
                console.error('Failed to force refresh thumbnails:', error);
            }
        },

        // Load available machines from the backend
        async loadMachines() {
            try {
                const response = await axios.get('/get-machines');
                this.machinesPrint = response.data.machinesPrint || [];
                this.machinesCut = response.data.machinesCut || [];
            } catch (error) {
                console.error('Failed to load machines:', error);
            }
        },





        // Professional file upload handler using UploadManager with multipart for large files
        async handleMultipleFiles(event, job) {
            const files = Array.from(event.target.files);
            if (!files.length) return;

            const toast = useToast();

            // Clear existing thumbnails immediately to prevent showing old ones
            this.jobThumbnails[job.id] = [];
            this.$forceUpdate();

            try {
                // Check if any file is larger than 15MB - use multipart upload
                const largeFiles = files.filter(file => file.size > 1 * 1024 * 1024);
                const smallFiles = files.filter(file => file.size <= 1 * 1024 * 1024);

                // Handle large files with multipart upload
                if (largeFiles.length > 0) {
                    await this.handleLargeFiles(largeFiles, job, toast);
                }

                // Handle small files with regular upload
                if (smallFiles.length > 0) {
                    const uploadConfig = {
                        uploadType: 'general',
                        endpoint: `/jobs/${job.id}/upload-multiple-files`,
                        progressEndpoint: `/jobs/${job.id}/upload-progress`
                    };

                    const callbacks = {
                        onStateChange: (uploadState) => {
                            // Force UI update when state changes
                            this.$forceUpdate();
                        },
                        onProgress: (progressData) => {
                            // Progress tracking handled by component
                        },
                        onUploadProgress: (progress) => {
                            // Progress tracking handled by component
                        },
                        onError: (error) => {
                            console.error('Upload error:', error);
                        }
                    };

                    const response = await this.uploadManager.startUpload(job.id, smallFiles, uploadConfig, callbacks);

                // Normalize payload to support Axios (response.data) and fetch/multipart (response|response.completion)
                const res = response?.data ?? response?.completion ?? response ?? {};

                // Update job with new original files and total area
                const existingBreakdown = Array.isArray(job.dimensions_breakdown) ? job.dimensions_breakdown : [];
                const newBreakdown = Array.isArray(res.dimensions_breakdown) ? res.dimensions_breakdown : [];
                const mergedBreakdown = existingBreakdown.concat(newBreakdown);

                const updatedJob = {
                    ...job,
                    originalFile: res.originalFiles || [],
                    // Update total area and dimensions breakdown if available
                    ...(res.total_area_m2 !== undefined && {
                        total_area_m2: parseFloat(res.total_area_m2) || 0
                    }),
                    // Merge existing + newly returned breakdown so we keep all files
                    ...(newBreakdown.length > 0 && {
                        dimensions_breakdown: mergedBreakdown
                    })
                };

                // Job updated with new dimensions

                // Update in jobsWithPrices
                const index = this.jobsWithPrices.findIndex(j => j.id === job.id);
                if (index !== -1) {
                    this.jobsWithPrices[index] = updatedJob;
                } else {
                    this.jobsWithPrices.push(updatedJob);
                }

                // Also update in updatedJobs if it exists
                if (this.updatedJobs) {
                    const updatedIndex = this.updatedJobs.findIndex(j => j.id === job.id);
                    if (updatedIndex !== -1) {
                        this.updatedJobs[updatedIndex] = updatedJob;
                    } else {
                        this.updatedJobs.push(updatedJob);
                    }
                }

                // Also update the original job object to ensure immediate UI update
                Object.assign(job, updatedJob);

                // Force immediate update of the job object properties
                job.dimensions_breakdown = updatedJob.dimensions_breakdown;
                job.total_area_m2 = updatedJob.total_area_m2;
                job.originalFile = updatedJob.originalFile;

                // Recalculate cost if this is a catalog-based job and area was updated
                if (updatedJob.catalog_item_id && res.total_area_m2 > 0) {
                    await this.recalculateJobCost(updatedJob);
                }

                // Use thumbnails from upload response immediately if available
                if (res.thumbnails && res.thumbnails.length > 0) {
                    // Initialize imageLoadError and imageLoaded for each thumbnail
                    this.jobThumbnails[job.id] = res.thumbnails.map(thumb => ({
                        ...thumb,
                        imageLoadError: false,
                        imageLoaded: false // Mark as not loaded initially
                    }));
                    this.$forceUpdate();
                } else {
                    // Wait a moment for backend processing, then reload thumbnails
                    setTimeout(async () => {
                        await this.loadJobThumbnails(job.id);
                    }, 1000);
                }

                                // Emit updates
                this.$emit('jobs-updated', [updatedJob]);
                this.$emit('job-updated', updatedJob);
                
                // Force UI update to show new dimensions
                this.forceUpdateDimensions();
                
                // Additional force update to ensure UI reflects changes
                this.$nextTick(() => {
                    this.$forceUpdate();
                });
                
                // Show success message with total area info
                if (res.total_area_m2 > 0) {
                    const totalM2 = res.total_area_m2;
                    const fileCount = res.dimensions?.files_count || smallFiles.length;
                    
                    toast.success(`${smallFiles.length} files uploaded successfully. Job total area: ${totalM2.toFixed(4)}m² from ${fileCount} files`);
                } else {
                    toast.success(`${smallFiles.length} files uploaded successfully`);
                }
                }

            } catch (error) {
                console.error('Error uploading files:', error);
                
                if (error.response) {
                    console.error('Server response:', error.response.data);
                    toast.error(`Failed to upload files: ${error.response.data.details || error.response.data.error}`);
                } else {
                    toast.error('Failed to upload files: Network error');
                }
            }

            // Reset the input
            event.target.value = '';
        },

        // Handle large files (>15MB) with multipart upload
        async handleLargeFiles(files, job, toast) {
            try {
                for (const file of files) {
                    
                    const onProgress = ({ loaded, total, partNumber, totalParts }) => {
                        const percentage = Math.round((loaded / total) * 100);
                        // Update UI with progress if needed
                        this.$forceUpdate();
                    };

                    const mpResult = await uploadFileInParts({
                        file,
                        jobId: job.id,
                        chunkSize: 10 * 1024 * 1024, // 10MB chunks
                        onProgress
                    });

                    // Normalize completion payload and update job immediately (no waiting)
                    const res = mpResult?.completion ?? {};
                    const existingBreakdown = Array.isArray(job.dimensions_breakdown) ? job.dimensions_breakdown : [];
                    const newBreakdown = Array.isArray(res.dimensions_breakdown) ? res.dimensions_breakdown : [];
                    const mergedBreakdown = existingBreakdown.concat(newBreakdown);

                    const updatedJob = {
                        ...job,
                        originalFile: res.originalFiles || job.originalFile || [],
                        ...(res.total_area_m2 !== undefined && {
                            total_area_m2: parseFloat(res.total_area_m2) || 0
                        }),
                        ...(newBreakdown.length > 0 && {
                            dimensions_breakdown: mergedBreakdown
                        })
                    };

                    // Update local state so breakdown appears instantly
                    const idx = this.jobsWithPrices.findIndex(j => j.id === job.id);
                    if (idx !== -1) {
                        this.jobsWithPrices[idx] = updatedJob;
                    } else {
                        this.jobsWithPrices.push(updatedJob);
                    }
                    Object.assign(job, updatedJob);
                    this.$emit('job-updated', updatedJob);
                    this.forceUpdateDimensions();
                }

                toast.success(`${files.length} large file(s) uploaded successfully.`);
                
                // Final refresh as a safety to pull any updated totals from server
                await this.refreshJobData(job.id);
                
            } catch (error) {
                console.error('Error uploading large files:', error);
                toast.error(`Failed to upload large files: ${error.message}`);
            }
        },

        // Refresh job data after multipart upload
        async refreshJobData(jobId) {
            try {
                const response = await axios.get(`/jobs/${jobId}`);
                if (response.data) {
                    const updatedJob = {
                        ...response.data,
                        total_area_m2: parseFloat(response.data.total_area_m2) || 0,
                        dimensions_breakdown: response.data.dimensions_breakdown || []
                    };

                    // Update the job in our local data
                    const jobIndex = this.jobsToDisplay.findIndex(j => j.id === jobId);
                    if (jobIndex !== -1) {
                        this.jobsToDisplay[jobIndex] = updatedJob;
                    }

                    // Update in jobsWithPrices
                    const index = this.jobsWithPrices.findIndex(j => j.id === jobId);
                    if (index !== -1) {
                        this.jobsWithPrices[index] = updatedJob;
                    } else {
                        this.jobsWithPrices.push(updatedJob);
                    }

                    // Also update in updatedJobs if it exists
                    if (this.updatedJobs) {
                        const updatedIndex = this.updatedJobs.findIndex(j => j.id === jobId);
                        if (updatedIndex !== -1) {
                            this.updatedJobs[updatedIndex] = updatedJob;
                        } else {
                            this.updatedJobs.push(updatedJob);
                        }
                    }

                    // Recalculate cost if this is a catalog-based job
                    if (updatedJob.catalog_item_id && updatedJob.total_area_m2 > 0) {
                        await this.recalculateJobCost(updatedJob);
                    }

                    this.$emit('job-updated', updatedJob);
                    this.forceUpdateDimensions();
                }
            } catch (error) {
                console.error('Error refreshing job data:', error);
            }
        },

        startEditing(job, field) {
            this.editingJob = job;
            this.editingField = field;
            this.editingValue = job[field];
            this.$nextTick(() => {
                if (field === 'quantity' && this.quantityInput) {
                    this.quantityInput.focus();
                } else if (field === 'copies' && this.copiesInput) {
                    this.copiesInput.focus();
                }
            });
        },

        startEditingMachine(job, field) {
            this.editingJob = job;
            this.editingField = field;
            this.editingValue = job[field];
            this.$nextTick(() => {
                if (field === 'machinePrint' && this.machinePrintInput) {
                    this.machinePrintInput.focus();
                } else if (field === 'machineCut' && this.machineCutInput) {
                    this.machineCutInput.focus();
                }
            });
        },

        async saveEdit(job) {
            if (!this.editingJob || !this.editingField) return;

            const toast = useToast();
            const valueToUpdate = parseInt(this.editingValue);

            // Get catalog_item_id and client_id from the effective attributes
            const catalog_item_id = job.effective_catalog_item_id;
            const client_id = job.effective_client_id;

            // Sending job data update

            try {
                // Prepare the request data - only send what's actually being edited
                const requestData = {
                    [this.editingField]: valueToUpdate
                };

                // Include catalog info if we're updating quantity or copies (needed for price recalculation)
                if (this.editingField === 'quantity' || this.editingField === 'copies') {
                    requestData.catalog_item_id = catalog_item_id;
                    requestData.client_id = client_id;
                }

                // Debug log to check what data is being sent
                // Request data being sent

                // First update the backend
                const response = await axios.put(`/jobs/${job.id}`, requestData);

                // Debug log to check response data
                // Response from server received

                if (response.status === 200) {
                    // Update the local job with the response data
                    const updatedJob = {
                        ...response.data.job,
                        quantity: parseInt(response.data.job.quantity),
                        copies: parseInt(response.data.job.copies),
                        price: parseFloat(response.data.job.price),
                        salePrice: parseFloat(response.data.job.salePrice) || null,
                        // Focus on dimensions breakdown and total area
                        total_area_m2: parseFloat(response.data.job.total_area_m2) || 0,
                        dimensions_breakdown: response.data.job.dimensions_breakdown || [],
                        effective_catalog_item_id: catalog_item_id,
                        effective_client_id: client_id
                    };

                    // Debug log to check updated job data
                    // Job data updated successfully

                    // Update in jobsWithPrices
                    const index = this.jobsWithPrices.findIndex(j => j.id === job.id);
                    if (index !== -1) {
                        this.jobsWithPrices[index] = updatedJob;
                    } else {
                        this.jobsWithPrices.push(updatedJob);
                    }

                    // Also update in updatedJobs if it exists
                    if (this.updatedJobs) {
                        const updatedIndex = this.updatedJobs.findIndex(j => j.id === job.id);
                        if (updatedIndex !== -1) {
                            this.updatedJobs[updatedIndex] = updatedJob;
                        } else {
                            this.updatedJobs.push(updatedJob);
                        }
                    }

                    // Emit the update
                    this.$emit('job-updated', updatedJob);
                    toast.success('Updated successfully');
                }
            } catch (error) {
                console.error('Error details:', error.response?.data || error);
                toast.error('Failed to update');
            }

            // Reset editing state
            this.editingJob = null;
            this.editingField = null;
            this.editingValue = null;
        },

        async saveMachineEdit(job) {
            if (!this.editingJob || !this.editingField) return;

            const toast = useToast();
            const valueToUpdate = this.editingValue;

            try {
                const requestData = {
                    [this.editingField]: valueToUpdate
                };

                const response = await axios.put(`/jobs/${this.editingJob.id}/update-machine`, requestData);

                if (response.status === 200) {
                    const updatedJob = {
                        ...response.data.job,
                        machinePrint: response.data.job.machinePrint,
                        machineCut: response.data.job.machineCut
                    };

                    const index = this.jobsWithPrices.findIndex(j => j.id === this.editingJob.id);
                    if (index !== -1) {
                        this.jobsWithPrices[index] = updatedJob;
                    } else {
                        this.jobsWithPrices.push(updatedJob);
                    }

                    if (this.updatedJobs) {
                        const updatedIndex = this.updatedJobs.findIndex(j => j.id === this.editingJob.id);
                        if (updatedIndex !== -1) {
                            this.updatedJobs[updatedIndex] = updatedJob;
                        } else {
                            this.updatedJobs.push(updatedJob);
                        }
                    }

                    this.$emit('job-updated', updatedJob);
                    toast.success('Machine assignment updated successfully');
                }
            } catch (error) {
                console.error('Error updating machine:', error);
                if (error.response) {
                    toast.error(`Failed to update machine: ${error.response.data.details || error.response.data.error}`);
                } else {
                    toast.error('Failed to update machine assignment');
                }
            }

            this.editingJob = null;
            this.editingField = null;
            this.editingValue = null;
        },

        confirmDelete(job) {
            this.jobToDelete = job;
            this.showDeleteConfirm = true;
        },

        deleteJob() {
            if (this.jobToDelete) {
                // Set deletion state
                this.jobDeletionStates[this.jobToDelete.id] = 'deleting';
                this.$forceUpdate();
                
                // Emit event to parent component to handle the deletion
                this.$emit('delete-job', this.jobToDelete.id);

                // Clean up jobsWithPrices array to prevent deleted jobs from reappearing
                this.jobsWithPrices = this.jobsWithPrices.filter(job => job.id !== this.jobToDelete.id);

                // Reset the confirmation dialog
                this.showDeleteConfirm = false;
                this.jobToDelete = null;
            }
        },

        // Method to clean up jobsWithPrices when jobs are deleted from parent
        cleanupDeletedJob(jobId) {
            this.jobsWithPrices = this.jobsWithPrices.filter(job => job.id !== jobId);
            // Clear deletion state
            if (this.jobDeletionStates[jobId]) {
                delete this.jobDeletionStates[jobId];
            }
        },

        // Method to clear job deletion state (used when deletion fails)
        clearJobDeletionState(jobId) {
            if (this.jobDeletionStates[jobId]) {
                delete this.jobDeletionStates[jobId];
                this.$forceUpdate();
            }
        },

        // Refresh job state method
        async refreshJobState(job) {
            const toast = useToast();
            
            // Set refresh state
            this.jobRefreshStates[job.id] = 'refreshing';
            this.$forceUpdate();
            
            try {
                // Reset upload manager state for this job
                this.uploadManager.forceResetJob(job.id);
                
                // Clear all local state for this job
                this.jobThumbnails[job.id] = [];
                this.jobCuttingFiles[job.id] = [];
                this.fileRemovalStates[job.id] = 'idle';
                this.cuttingFileRemovalStates[job.id] = {};
                
                // Reinitialize job states
                this.initializeJobStates(job.id);
                this.initializeCuttingJobStates(job.id);
                
                // Reload job data from backend
                await this.refreshJobData(job.id);
                
                // Reload thumbnails if job has files
                if (job.originalFile && job.originalFile.length > 0) {
                    await this.loadJobThumbnails(job.id);
                }
                
                // Reload cutting files if job has cutting files
                if (job.cuttingFiles && job.cuttingFiles.length > 0) {
                    await this.loadJobCuttingFiles(job.id);
                }
                
                // Set refresh state to complete
                this.jobRefreshStates[job.id] = 'complete';
                this.$forceUpdate();
                
                toast.success('Job state refreshed successfully');
                
                // Reset to idle after a delay
                setTimeout(() => {
                    this.jobRefreshStates[job.id] = 'idle';
                    this.$forceUpdate();
                }, 2000);
                
            } catch (error) {
                console.error('Error refreshing job state:', error);
                toast.error('Failed to refresh job state');
                
                // Set refresh state to error
                this.jobRefreshStates[job.id] = 'error';
                this.$forceUpdate();
                
                // Reset to idle after a delay
                setTimeout(() => {
                    this.jobRefreshStates[job.id] = 'idle';
                    this.$forceUpdate();
                }, 3000);
            }
        },

        getFileName(filePath) {
            if (!filePath) return 'Unknown File';
            
            // Extract filename from path
            const parts = filePath.split('/');
            const filename = parts[parts.length - 1];
            
            // Remove timestamp prefix if present (format: timestamp_originalname.ext)
            return filename.replace(/^\d+_/, '');
        },

        async downloadOriginalFile(job, filePath) {
            const toast = useToast();
            
            try {
                const response = await axios.get(`/jobs/${job.id}/download-original-file`, {
                    params: { file_path: filePath },
                    responseType: 'blob'
                });

                // Create download link
                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', this.getFileName(filePath));
                document.body.appendChild(link);
                link.click();
                link.remove();
                window.URL.revokeObjectURL(url);
                
                toast.success('File downloaded successfully');
            } catch (error) {
                toast.error('Failed to download file');
                console.error(error);
            }
        },

        async removeOriginalFile(job, fileIndex) {
            const toast = useToast();
            
            // Set removal state
            this.fileRemovalStates[job.id] = 'removing';
            this.$forceUpdate();
            
            try {
                const filePath = job.originalFile?.[fileIndex];
                const response = await axios.delete(`/jobs/${job.id}/remove-original-file`, {
                    data: { file_index: fileIndex, original_file: filePath }
                });

                // Update job with new original files and recalculated area
                const updatedJob = {
                    ...job,
                    originalFile: response.data.originalFiles || [],
                    // Update total area if it was recalculated
                    ...(response.data.total_area_m2 !== undefined && {
                        total_area_m2: parseFloat(response.data.total_area_m2) || 0
                    }),
                    // Always apply server-provided breakdown, even if it's an empty array
                    ...(Object.prototype.hasOwnProperty.call(response.data, 'dimensions_breakdown') && {
                        dimensions_breakdown: Array.isArray(response.data.dimensions_breakdown)
                            ? response.data.dimensions_breakdown
                            : []
                    })
                };

                // Ensure dimensions_breakdown is explicitly set to empty array if no files remain
                if (!updatedJob.originalFile || updatedJob.originalFile.length === 0) {
                    updatedJob.dimensions_breakdown = [];
                    updatedJob.total_area_m2 = 0;
                }

                // File removed and job updated successfully

                // Update in jobsWithPrices
                const index = this.jobsWithPrices.findIndex(j => j.id === job.id);
                if (index !== -1) {
                    this.jobsWithPrices[index] = updatedJob;
                } else {
                    this.jobsWithPrices.push(updatedJob);
                }

                // Also update in updatedJobs if it exists
                if (this.updatedJobs) {
                    const updatedIndex = this.updatedJobs.findIndex(j => j.id === job.id);
                    if (updatedIndex !== -1) {
                        this.updatedJobs[updatedIndex] = updatedJob;
                    } else {
                        this.updatedJobs.push(updatedJob);
                    }
                }

                // Also update the original job object to ensure immediate UI update
                Object.assign(job, updatedJob);

                // Force immediate update of the job object properties
                job.dimensions_breakdown = updatedJob.dimensions_breakdown;
                job.total_area_m2 = updatedJob.total_area_m2;
                job.originalFile = updatedJob.originalFile;

                // Update thumbnails locally using API-provided list to avoid refetch
                if (Array.isArray(response.data.thumbnails)) {
                    this.jobThumbnails[job.id] = response.data.thumbnails.map(thumb => ({
                        ...thumb,
                        imageLoadError: false,
                        imageLoaded: false
                    }));
                } else {
                    // Fallback to reloading if not provided
                    this.jobThumbnails[job.id] = [];
                    this.$forceUpdate();
                    if (response.data.originalFiles && response.data.originalFiles.length > 0) {
                        setTimeout(async () => {
                            await this.loadJobThumbnails(job.id);
                        }, 500);
                    }
                }

                // Recalculate cost if this is a catalog-based job and area was updated
                if (updatedJob.catalog_item_id && response.data.total_area_m2 !== undefined) {
                    await this.recalculateJobCost(updatedJob);
                }

                // Emit updates
                this.$emit('jobs-updated', [updatedJob]);
                this.$emit('job-updated', updatedJob);
                
                // Force UI update to show new dimensions
                this.forceUpdateDimensions();
                
                // Additional force update to ensure UI reflects changes
                this.$nextTick(() => {
                    this.$forceUpdate();
                });
                
                // Show success message with area info if available
                if (response.data.total_area_m2 !== undefined) {
                    const newArea = Number(response.data.total_area_m2);
                    toast.success(`File removed successfully. Job area updated: ${newArea.toFixed(4)}m²`);
                } else {
                    toast.success('File removed successfully');
                }
                
                // Reset upload manager state when files are removed
                this.uploadManager.forceResetJob(job.id);
                
                // Set removal state to complete
                this.fileRemovalStates[job.id] = 'complete';
                this.$forceUpdate();
                
                // Reset to idle after a delay
                setTimeout(() => {
                    this.fileRemovalStates[job.id] = 'idle';
                    this.$forceUpdate();
                }, 2000);
                
            } catch (error) {
                console.error('Error removing file:', error);
                toast.error('Failed to remove file');
                
                // Set removal state to error
                this.fileRemovalStates[job.id] = 'error';
                this.$forceUpdate();
                
                // Reset to idle after a delay
                setTimeout(() => {
                    this.fileRemovalStates[job.id] = 'idle';
                    this.$forceUpdate();
                }, 3000);
            }
        },

        // --- CUTTING FILES METHODS ---

        triggerCuttingFilesInput(jobId) {
            // Don't trigger if upload is disabled
            if (this.isCuttingUploadDisabled(jobId)) {
                return;
            }
            
            const fileInput = document.getElementById('cutting-files-input-' + jobId);
            if (fileInput && !fileInput.disabled) {
                fileInput.value = '';
                fileInput.click();
            }
        },

        getJobCuttingFiles(job) {
            if (this.jobCuttingFiles[job.id] && this.jobCuttingFiles[job.id].length > 0) {
                const cuttingFileCount = job.cuttingFiles ? job.cuttingFiles.length : 0;
                const thumbnailCount = this.jobCuttingFiles[job.id].length;
                
                if (cuttingFileCount !== thumbnailCount) {
                    this.jobCuttingFiles[job.id] = [];
                    this.$forceUpdate();
                    return [];
                }
                
                return this.jobCuttingFiles[job.id];
            }
            
            if (job.cuttingFiles && job.cuttingFiles.length > 0) {
                return job.cuttingFiles.map((file, index) => ({
                    type: this.getFileExtension(file).toLowerCase(),
                    thumbnailUrl: null,
                    originalFile: file,
                    index: index,
                    filename: this.getFileName(file),
                    imageLoadError: false,
                    imageLoaded: false
                }));
            }
            
            return [];
        },

        getFileExtension(filename) {
            return filename.split('.').pop() || '';
        },

        async loadJobCuttingFiles(jobId) {
            try {
                this.$forceUpdate();
                
                const response = await axios.get(`/jobs/${jobId}/cutting-file-thumbnails`);
                
                if (response.data.thumbnails && response.data.thumbnails.length > 0) {
                    this.jobCuttingFiles[jobId] = response.data.thumbnails.map(thumb => ({
                        ...thumb,
                        imageLoadError: false,
                        imageLoaded: false
                    }));
                } else {
                    this.jobCuttingFiles[jobId] = [];
                }
                
                this.$forceUpdate();
            } catch (error) {
                console.error('Failed to load cutting file thumbnails for job', jobId, error);
                this.jobCuttingFiles[jobId] = [];
                this.$forceUpdate();
            }
        },

        getCuttingFileThumbnailUrl(jobId, fileIndex) {
            const timestamp = Date.now();
            const url = route('jobs.viewCuttingFileThumbnail', { jobId: jobId, fileIndex: fileIndex }) + `?t=${timestamp}`;
            return url;
        },

        handleCuttingFileError(cuttingFile, event) {
            const imgElement = event.target;
            console.error('Cutting file thumbnail failed to load:', imgElement.src);
            cuttingFile.imageLoadError = true;
            this.$forceUpdate();
        },

        handleCuttingFileLoad(cuttingFile) {
            cuttingFile.imageLoaded = true;
            cuttingFile.imageLoadError = false;
        },

        getCuttingFileIcon(fileType) {
            const iconMap = {
                'pdf': 'fa fa-file-pdf-o',
                'svg': 'fa fa-file-image-o',
                'dxf': 'fa fa-file-code-o',
                'cdr': 'fa fa-file-o',
                'ai': 'fa fa-file-o'
            };
            return iconMap[fileType] || 'fa fa-file-o';
        },

        openCuttingFileInNewTab(job, fileIndex) {
            const url = route('jobs.viewCuttingFile', { jobId: job.id, fileIndex });
            window.open(url, '_blank');
        },

        closeCuttingFilePreviewModal() {
            this.showCuttingFilePreviewModal = false;
            this.cuttingPreviewFile = null;
        },

        async refreshCuttingFileThumbnails(jobId) {
            const toast = useToast();
            
            try {
                await this.loadJobCuttingFiles(jobId);
                toast.success('Cutting file thumbnails refreshed');
            } catch (error) {
                console.error('Failed to refresh cutting file thumbnails:', error);
                toast.error('Failed to refresh cutting file thumbnails');
            }
        },

        // Professional cutting files upload handler using UploadManager
        async handleCuttingFiles(event, job) {
            const files = Array.from(event.target.files);
            if (!files.length) {
                event.target.value = '';
                return;
            }

            const toast = useToast();

            // Clear existing cutting files immediately to prevent showing old ones
            this.jobCuttingFiles[job.id] = [];
            this.$forceUpdate();

            try {
                const uploadConfig = {
                    uploadType: 'cutting',
                    endpoint: `/jobs/${job.id}/upload-cutting-files`,
                    progressEndpoint: `/jobs/${job.id}/cutting-upload-progress`
                };

                const callbacks = {
                    onStateChange: (uploadState) => {
                        // Force UI update when state changes
                        this.$forceUpdate();
                    },
                    onProgress: (progressData) => {
                        // Cutting files upload progress tracked
                    },
                    onUploadProgress: (progress) => {
                        // Cutting files upload progress tracked
                    },
                    onError: (error) => {
                        console.error('Cutting files upload error:', error);
                    }
                };

                const response = await this.uploadManager.startUpload(job.id, files, uploadConfig, callbacks);

                const updatedJob = {
                    ...job,
                    cuttingFiles: response.data.cuttingFiles || [],
                    // Update dimensions breakdown if available
                    ...(response.data.dimensions && response.data.dimensions.total_area_m2 > 0 && {
                        total_area_m2: response.data.dimensions.total_area_m2,
                        dimensions_breakdown: response.data.dimensions.individual_files || []
                    })
                };

                const index = this.jobsWithPrices.findIndex(j => j.id === job.id);
                if (index !== -1) {
                    this.jobsWithPrices[index] = updatedJob;
                } else {
                    this.jobsWithPrices.push(updatedJob);
                }

                if (this.updatedJobs) {
                    const updatedIndex = this.updatedJobs.findIndex(j => j.id === job.id);
                    if (updatedIndex !== -1) {
                        this.updatedJobs[updatedIndex] = updatedJob;
                    } else {
                        this.updatedJobs.push(updatedJob);
                    }
                }

                if (response.data.thumbnails && response.data.thumbnails.length > 0) {
                    this.jobCuttingFiles[job.id] = response.data.thumbnails.map(thumb => ({
                        ...thumb,
                        imageLoadError: false,
                        imageLoaded: false
                    }));
                    this.$forceUpdate();
                } else {
                    setTimeout(async () => {
                        await this.loadJobCuttingFiles(job.id);
                    }, 1000);
                }

                                this.$emit('jobs-updated', [updatedJob]);
                this.$emit('job-updated', updatedJob);
                
                // Force UI update to show new dimensions
                this.forceUpdateDimensions();
                
                toast.success(`${files.length} cutting files uploaded successfully`);

            } catch (error) {
                console.error('Error uploading cutting files:', error);
                
                if (error.response) {
                    toast.error(`Failed to upload cutting files: ${error.response.data.details || error.response.data.error}`);
                } else {
                    toast.error('Failed to upload cutting files: Network error');
                }
            }

            event.target.value = '';
        },

        async removeCuttingFile(job, fileIndex) {
            const toast = useToast();
            
            // Set removal state for this specific cutting file
            if (!this.cuttingFileRemovalStates[job.id]) {
                this.cuttingFileRemovalStates[job.id] = {};
            }
            this.cuttingFileRemovalStates[job.id][fileIndex] = 'removing';
            this.$forceUpdate();
            
            try {
                const response = await axios.delete(`/jobs/${job.id}/remove-cutting-file`, {
                    data: { fileIndex: fileIndex }
                });

                const updatedJob = {
                    ...job,
                    cuttingFiles: response.data.remaining_files || [],
                    // Update total area if it was recalculated
                    ...(response.data.dimensions && {
                        total_area_m2: response.data.dimensions.area_m2
                    })
                };

                const index = this.jobsWithPrices.findIndex(j => j.id === job.id);
                if (index !== -1) {
                    this.jobsWithPrices[index] = updatedJob;
                } else {
                    this.jobsWithPrices.push(updatedJob);
                }

                if (this.updatedJobs) {
                    const updatedIndex = this.updatedJobs.findIndex(j => j.id === job.id);
                    if (updatedIndex !== -1) {
                        this.updatedJobs[updatedIndex] = updatedJob;
                    } else {
                        this.updatedJobs.push(updatedJob);
                    }
                }

                this.jobCuttingFiles[job.id] = [];
                this.$forceUpdate();

                if (response.data.remaining_files && response.data.remaining_files.length > 0) {
                    setTimeout(async () => {
                        await this.loadJobCuttingFiles(job.id);
                    }, 500);
                }

                                this.$emit('jobs-updated', [updatedJob]);
                this.$emit('job-updated', updatedJob);
                
                // Force UI update to show new dimensions
                this.forceUpdateDimensions();
                
                // Reset upload manager state when cutting files are removed
                this.uploadManager.forceResetJob(job.id);
                
                // Set removal state to complete
                this.cuttingFileRemovalStates[job.id][fileIndex] = 'complete';
                this.$forceUpdate();
                
                toast.success('Cutting file removed successfully');
            } catch (error) {
                console.error('Error removing cutting file:', error);
                // Set removal state to error
                this.cuttingFileRemovalStates[job.id][fileIndex] = 'error';
                this.$forceUpdate();
                toast.error('Failed to remove cutting file');
            }
        },

        // Initialize cutting file states for a job
        initializeCuttingJobStates(jobId) {
            // Initialize upload manager for cutting files
            this.uploadManager.initializeJob(jobId, 'cutting');
        },

        // Helper methods for upload states
        getGeneralUploadState(jobId) {
            return this.uploadManager.getUploadState(jobId, 'general');
        },

        getCuttingUploadState(jobId) {
            return this.uploadManager.getUploadState(jobId, 'cutting');
        },

        isGeneralUploadDisabled(jobId) {
            return this.uploadManager.isDisabled(jobId, 'general') || this.fileRemovalStates[jobId] === 'removing';
        },

        isCuttingUploadDisabled(jobId) {
            return this.uploadManager.isDisabled(jobId, 'cutting') || (this.cuttingFileRemovalStates[jobId] && Object.values(this.cuttingFileRemovalStates[jobId]).includes('removing'));
        },

        // Force UI update when dimensions change
        forceUpdateDimensions() {
            this.$forceUpdate();
        },

        // New event handlers for FileUploadManager
        handleFilesUpdated(job, data) {
            // Update job with new files and dimensions
            const updatedJob = {
                ...job,
                originalFile: data.originalFiles || [],
                ...(data.total_area_m2 !== undefined && {
                    total_area_m2: parseFloat(data.total_area_m2) || 0
                }),
                ...(data.dimensions_breakdown && {
                    dimensions_breakdown: data.dimensions_breakdown
                })
            };

            // Use in-place update to prevent component re-creation
            this.updateJobInStateInPlace(job, updatedJob);
            this.$emit('job-updated', updatedJob);
        },

        handleFileRemoved(job, { fileIndex, response }) {
            const updatedJob = {
                ...job,
                originalFile: response.originalFiles || [],
                ...(response.total_area_m2 !== undefined && {
                    total_area_m2: parseFloat(response.total_area_m2) || 0
                }),
                ...(Object.prototype.hasOwnProperty.call(response, 'dimensions_breakdown') && {
                    dimensions_breakdown: Array.isArray(response.dimensions_breakdown)
                        ? response.dimensions_breakdown
                        : []
                })
            };

            // Update thumbnails if provided
            if (Array.isArray(response.thumbnails)) {
                this.jobThumbnails[job.id] = response.thumbnails.map(thumb => ({
                    ...thumb,
                    imageLoadError: false,
                    imageLoaded: false
                }));
            } else {
                this.jobThumbnails[job.id] = [];
            }

            // Use in-place update to prevent component re-creation
            this.updateJobInStateInPlace(job, updatedJob);
            this.$emit('job-updated', updatedJob);
            this.forceUpdateDimensions();
        },

        handleUploadStarted(job) {
            // Upload started for job
            // Mark job as having active upload to prevent component re-creation
            this.activeUploads.add(job.id);
            // Cache current files with frozen reference to prevent prop changes
            this.stableFileCache[`${job.id}_originalFile`] = Object.freeze([...(job.originalFile || [])]);
            // Cache the job object to prevent component recreation
            if (!this.stableJobCache) this.stableJobCache = {};
            this.stableJobCache[job.id] = { ...job };
            // Clear existing thumbnails
            this.jobThumbnails[job.id] = [];
        },

        async handleUploadCompleted(job, data) {
            // Update job with upload response
            if (data) {
                // Normalize payload to support Axios (data.data) and fetch/multipart (data|data.completion)
                const res = data?.data ?? data?.completion ?? data ?? {};
                const updatedJob = {
                    ...job,
                    originalFile: res.originalFiles || [],
                    ...(res.total_area_m2 !== undefined && {
                        total_area_m2: parseFloat(res.total_area_m2) || 0
                    }),
                    ...(res.dimensions_breakdown && {
                        dimensions_breakdown: res.dimensions_breakdown
                    })
                };

                this.updateJobInState(updatedJob);

                // Recalculate cost if needed
                if (updatedJob.catalog_item_id && res.total_area_m2 > 0) {
                    await this.recalculateJobCost(updatedJob);
                }

                this.$emit('job-updated', updatedJob);
                this.forceUpdateDimensions();
            } else {
                // For multipart uploads, refresh job data to get updated dimensions
                await this.refreshJobData(job.id);
            }
            
            // Mark upload as completed and clear cache
            this.activeUploads.delete(job.id);
            delete this.stableFileCache[`${job.id}_originalFile`];
            delete this.stableJobCache[job.id];
        },

        handleUploadFailed(job, error) {
            console.error('Upload failed for job', job.id, error);
            // Reset upload manager state
            this.uploadManager.forceResetJob(job.id);
            // Mark upload as completed and clear cache
            this.activeUploads.delete(job.id);
            delete this.stableFileCache[`${job.id}_originalFile`];
            delete this.stableJobCache[job.id];
        },

        // New event handlers for CuttingFileUploadManager
        handleCuttingFilesUpdated(job, data) {
            const updatedJob = {
                ...job,
                cuttingFiles: data.cuttingFiles || [],
                ...(data.dimensions && data.dimensions.total_area_m2 > 0 && {
                    total_area_m2: data.dimensions.total_area_m2,
                    dimensions_breakdown: data.dimensions.individual_files || []
                })
            };

            // Use in-place update to prevent component re-creation
            this.updateJobInStateInPlace(job, updatedJob);
            this.$emit('job-updated', updatedJob);
        },

        handleCuttingFileRemoved(job, { fileIndex, response }) {
            const updatedJob = {
                ...job,
                cuttingFiles: response.remaining_files || [],
                ...(response.dimensions && {
                    total_area_m2: response.dimensions.area_m2
                })
            };

            // Clear cutting file thumbnails
            this.jobCuttingFiles[job.id] = [];

            // Reload cutting files if any remain
            if (response.remaining_files && response.remaining_files.length > 0) {
                setTimeout(() => {
                    this.loadJobCuttingFiles(job.id);
                }, 500);
            }

            // Use in-place update to prevent component re-creation
            this.updateJobInStateInPlace(job, updatedJob);
            this.$emit('job-updated', updatedJob);
            this.forceUpdateDimensions();
        },

        handleCuttingUploadStarted(job) {
            // Cutting upload started for job
            // Mark job as having active upload to prevent component re-creation
            this.activeUploads.add(job.id);
            // Cache current files with frozen reference to prevent prop changes
            this.stableFileCache[`${job.id}_cuttingFiles`] = Object.freeze([...(job.cuttingFiles || [])]);
            // Cache the job object to prevent component recreation
            if (!this.stableJobCache) this.stableJobCache = {};
            this.stableJobCache[job.id] = { ...job };
            // Clear existing cutting file thumbnails
            this.jobCuttingFiles[job.id] = [];
        },

        handleCuttingUploadCompleted(job, data) {
            const updatedJob = {
                ...job,
                cuttingFiles: data.cuttingFiles || [],
                ...(data.dimensions && data.dimensions.total_area_m2 > 0 && {
                    total_area_m2: data.dimensions.total_area_m2,
                    dimensions_breakdown: data.dimensions.individual_files || []
                })
            };

            // Update cutting file thumbnails if provided
            if (data.thumbnails && data.thumbnails.length > 0) {
                this.jobCuttingFiles[job.id] = data.thumbnails.map(thumb => ({
                    ...thumb,
                    imageLoadError: false,
                    imageLoaded: false
                }));
            } else {
                setTimeout(() => {
                    this.loadJobCuttingFiles(job.id);
                }, 1000);
            }

            this.updateJobInState(updatedJob);
            this.$emit('job-updated', updatedJob);
            this.forceUpdateDimensions();
            
            // Mark upload as completed and clear cache
            this.activeUploads.delete(job.id);
            delete this.stableFileCache[`${job.id}_cuttingFiles`];
            delete this.stableJobCache[job.id];
        },

        handleCuttingUploadFailed(job, error) {
            console.error('Cutting upload failed for job', job.id, error);
            // Reset upload manager state
            this.uploadManager.forceResetJob(job.id);
            // Mark upload as completed and clear cache
            this.activeUploads.delete(job.id);
            delete this.stableFileCache[`${job.id}_cuttingFiles`];
            delete this.stableJobCache[job.id];
        },

        // Helper method to update job in all states
        updateJobInState(updatedJob) {
            // Update in jobsWithPrices
            const index = this.jobsWithPrices.findIndex(j => j.id === updatedJob.id);
            if (index !== -1) {
                this.jobsWithPrices[index] = updatedJob;
            } else {
                this.jobsWithPrices.push(updatedJob);
            }

            // Also update in updatedJobs if it exists
            if (this.updatedJobs) {
                const updatedIndex = this.updatedJobs.findIndex(j => j.id === updatedJob.id);
                if (updatedIndex !== -1) {
                    this.updatedJobs[updatedIndex] = updatedJob;
                } else {
                    this.updatedJobs.push(updatedJob);
                }
            }

            // Force immediate update of the original job object
            Object.assign(updatedJob, updatedJob);
        },

        // Helper method to update job properties in-place to prevent component re-creation
        updateJobInStateInPlace(originalJob, updatedJob) {
            // Update properties in-place instead of replacing the object
            Object.assign(originalJob, updatedJob);

            // Update in jobsWithPrices by finding and updating in-place
            const index = this.jobsWithPrices.findIndex(j => j.id === originalJob.id);
            if (index !== -1) {
                Object.assign(this.jobsWithPrices[index], updatedJob);
            } else {
                this.jobsWithPrices.push(originalJob);
            }

            // Also update in updatedJobs if it exists
            if (this.updatedJobs) {
                const updatedIndex = this.updatedJobs.findIndex(j => j.id === originalJob.id);
                if (updatedIndex !== -1) {
                    Object.assign(this.updatedJobs[updatedIndex], updatedJob);
                } else {
                    this.updatedJobs.push(originalJob);
                }
            }
        },

        async recalculateJobCost(job) {
            try {
                // Call the backend to recalculate cost with new dimensions
                const response = await axios.post('/jobs/recalculate-cost', {
                    job_id: job.id,
                    total_area_m2: job.total_area_m2 || 0,
                    quantity: job.quantity,
                    copies: job.copies
                });

                // Update the job with new cost
                const updatedJob = {
                    ...job,
                    price: response.data.price,
                    salePrice: response.data.salePrice
                };

                // Component breakdown received for job cost calculation

                // Update in jobsWithPrices
                const index = this.jobsWithPrices.findIndex(j => j.id === job.id);
                if (index !== -1) {
                    this.jobsWithPrices[index] = updatedJob;
                } else {
                    this.jobsWithPrices.push(updatedJob);
                }

                // Also update in updatedJobs if it exists
                if (this.updatedJobs) {
                    const updatedIndex = this.updatedJobs.findIndex(j => j.id === job.id);
                    if (updatedIndex !== -1) {
                        this.updatedJobs[updatedIndex] = updatedJob;
                    } else {
                        this.updatedJobs.push(updatedJob);
                    }
                }

                // Emit the update
                this.$emit('job-updated', updatedJob);

                // Force reactivity update
                this.$forceUpdate();
            } catch (error) {
                console.error('Error recalculating job cost:', error);
            }
        },
    },

    // Cleanup when component is destroyed
    beforeUnmount() {
        // Clean up upload manager resources
        if (this.uploadManager) {
            this.uploadManager.destroy();
        }
    },
};
</script>

<style scoped lang="scss">
.placeholder-upload {
    width: 60px;
    height: 60px;
    margin: 0 1rem;
    border: 2px dashed #ccc;
    border-radius: 4px;
    position: relative;
    background-color: #f9f9f9;
    overflow: hidden;
}
input[data-v-81b90cf3], select[data-v-81b90cf3]{
    width: 25vh;
    border-radius: 3px;
}
.bold {
    font-weight: bolder;
}

.slide-fade-enter-active, .slide-fade-leave-active {
    transition-property: max-height;
    transition-duration: 0.5s;
    transition-timing-function: ease-in-out;
}

.slide-fade-enter-to, .slide-fade-leave-from {
    overflow: hidden;
    max-height: 1000px;
}
.slide-fade-enter-from, .slide-fade-leave-to {
    overflow: hidden;
    max-height: 0;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

th, td {

    padding: 10px;
    text-align: center;

}
tr {
    margin-bottom: 5px;
    border: 1px solid white;
}
th {

    background-color: #f0f0f0;
}

.jobImg {
    width: 60px;
    margin: 0 1rem;
    display: flex;
}
.thumbnail {
    top:-50px;
    left:-35px;
    display:block;
    z-index:999;
    cursor: pointer;
    -webkit-transition-property: all;
    -webkit-transition-duration: 0.3s;
    -webkit-transition-timing-function: ease;
}
.thumbnail:hover {
    transform: scale(4);
}

input, select {
    height: 36px;
}

.text {
    display: flex;
    flex-direction: column;
    padding: 10px;
}

.placeholder-upload {
    width: 60px;
    height: 60px;
    margin: 0 1rem;
    border: 2px dashed $gray;
    border-radius: 4px;
    position: relative;
    background-color: $ultra-light-gray;
    overflow: hidden;
}

.placeholder-content {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

.placeholder-text {
    font-size: 0.8rem;
    color: $white;
    text-align: center;
    padding: 0.5rem;
}

.file-input {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
}

.placeholder-upload:hover {
    border-color: $light-green;
    background-color: rgba($light-green, 0.1);
}

.editable {
    cursor: pointer;
    padding: 2px 4px;
    border-radius: 3px;
    transition: all 0.2s ease;
}

.editable:hover:not(.disabled) {
    background-color: rgba(255, 255, 255, 0.1);
}

.editable.disabled {
    cursor: not-allowed;
    opacity: 0.6;
    background-color: rgba(255, 255, 255, 0.05);
    color: rgba(255, 255, 255, 0.7);
}

.edit-input {
    width: 60px;
    padding: 2px 4px;
    border: 1px solid #ccc;
    border-radius: 3px;
    background-color: white;
    color: black;
}

.edit-select {
    min-width: 120px;
    padding: 2px 4px;
    border: 1px solid #ccc;
    border-radius: 3px;
    background-color: white;
    color: black;
    font-size: 0.9rem;
    
    &:focus {
        outline: none;
        border-color: #28a745;
        box-shadow: 0 0 0 2px rgba(40, 167, 69, 0.2);
    }
}

.delete-btn, .refresh-btn {
    padding: 4px 8px;
    border-radius: 4px;
    transition: all 0.2s;
}

.refresh-btn {
    background: none;
    border: none;
    cursor: pointer;
}

.refresh-btn:hover:not(:disabled) {
    transform: scale(1.1);
}

.refresh-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.confirmation-dialog {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.7);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.dialog-content {
    background-color: $gray;
    padding: 20px;
    border-radius: 8px;
    text-align: center;

    p {
        margin-bottom: 20px;
        color: white;
    }
}

.dialog-buttons {
    display: flex;
    justify-content: center;
    gap: 10px;

    button {
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
    }
}

.confirm-btn {
    background-color: $red;
    color: white;

    &:hover {
        background-color: darken($red, 10%);
    }
}

.cancel-btn {
    background-color: $light-gray;
    color: white;

    &:hover {
        background-color: darken($gray, 10%);
    }
}

/* Multiple Files Styles */
.file-display-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    min-width: 150px;
}

.thumbnails-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    max-width: 200px;
    justify-content: center;
}

.thumbnail-item {
    position: relative;
    width: 60px;
    height: 60px;
    border: 2px solid rgba(255, 255, 255, 0.2);
    border-radius: 6px;
    overflow: hidden;
    transition: all 0.2s;
    background-color: rgba(0, 0, 0, 0.1);

    &:hover {
        border-color: $light-green;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }
    
    &.uploading {
        border-color: $light-green;
        animation: pulse 1.5s infinite;
    }
    
    &.error {
        border-color: $red;
        animation: shake 0.5s ease-in-out;
    }
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-2px); }
    75% { transform: translateX(2px); }
}

.thumbnail-preview {
    width: 100%;
    height: 100%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;

    &:hover {
        &::after {
            content: '🔍';
            position: absolute;
            bottom: 2px;
            right: 2px;
            font-size: 0.8rem;
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            border-radius: 50%;
            width: 16px;
            height: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    }
}

.thumbnail-remove-btn {
    position: absolute;
    top: 2px;
    right: 2px;
    width: 18px;
    height: 18px;
    background-color: rgba(220, 53, 69, 0.9);
    color: white;
    border: 1px solid white;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    z-index: 10;
    transition: all 0.2s;
    backdrop-filter: blur(2px);

    &:hover:not(:disabled) {
        background-color: rgba(220, 53, 69, 1);
        transform: scale(1.1);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    }

    &:disabled {
        cursor: not-allowed;
        transform: none;
        opacity: 0.7;
    }

    &.removing {
        background-color: rgba(255, 193, 7, 0.9);
        animation: pulse 1.5s infinite;
    }

    i {
        margin: 0;
        line-height: 1;
    }
}

.thumbnail-image {
    width: 100%;
    height: 100%;
    object-fit: contain;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s ease;
    background-color: #f8f9fa;

    &:hover {
        transform: scale(1.02);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }
}

.thumbnail-iframe {
    width: 100%;
    height: 100%;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s ease;
    background-color: #f8f9fa;
    overflow: hidden;

    &:hover {
        transform: scale(1.02);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }
}

.pdf-thumbnail {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #28a745, #20c997);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.6rem;
    text-align: center;
    padding: 4px;
    cursor: pointer;
    transition: all 0.2s ease;

    &:hover {
        background: linear-gradient(135deg, #218838, #17a2b8);
        transform: scale(1.02);
    }

    i {
        font-size: 1.5rem;
        margin-bottom: 3px;
        color: white;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
    }

    span {
        font-weight: bold;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        margin-bottom: 2px;
    }

    .preview-hint {
        font-size: 0.5rem;
        opacity: 0.8;
        font-weight: normal;
    }
}

.thumbnail-placeholder {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #6c757d, #495057);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.6rem;
    text-align: center;
    padding: 4px;

    i {
        font-size: 1.5rem;
        margin-bottom: 3px;
        color: #dc3545;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
    }

    span {
        font-weight: bold;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
    }
}

.thumbnail-label {
    position: absolute;
    top: 2px;
    right: 2px;
    background-color: rgba(0, 0, 0, 0.7);
    color: white;
    border-radius: 50%;
    width: 16px;
    height: 16px;
    font-size: 0.6rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.file-action-buttons {
    display: flex;
    gap: 4px;
    margin-top: 4px;
}

.file-action-btn {
    border: none;
    padding: 4px 8px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.7rem;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;

    i {
        margin-right: 4px;
    }

    &.primary {
        background-color: $light-green;
        color: white;

        &:hover:not(:disabled) {
            background-color: darken($light-green, 10%);
            transform: translateY(-1px);
        }
        
        &:disabled {
            background-color: rgba($light-green, 0.5);
            cursor: not-allowed;
            transform: none;
        }
    }

    &.secondary {
        background-color: #6c757d;
        color: white;
        padding: 4px 6px;
        min-width: 28px;

        i {
            margin: 0;
            font-size: 0.8rem;
        }

        &:hover:not(:disabled) {
            background-color: #5a6268;
            transform: translateY(-1px);
        }
        
        &:disabled {
            background-color: rgba(#6c757d, 0.5);
            cursor: not-allowed;
            transform: none;
        }
    }
}

/* Upload Progress Styles */
.upload-progress-container {
    margin: 6px 0;
    padding: 8px;
    background: linear-gradient(135deg, rgba(0, 0, 0, 0.15), rgba(0, 0, 0, 0.08));
    border-radius: 6px;
    border: 1px solid rgba(255, 255, 255, 0.15);
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
    backdrop-filter: blur(2px);
}

.upload-progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
}

.upload-status {
    display: flex;
    align-items: center;
    gap: 6px;
}

.status-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background-color: #ccc;
    animation: pulse 2s infinite;

    &.starting {
        background-color: #ffc107;
        box-shadow: 0 0 6px rgba(#ffc107, 0.4);
    }
    &.uploading {
        background-color: #17a2b8;
        box-shadow: 0 0 6px rgba(#17a2b8, 0.4);
    }
    &.processing {
        background-color: #007bff;
        box-shadow: 0 0 6px rgba(#007bff, 0.4);
    }
    &.finalizing {
        background-color: #6c757d;
        box-shadow: 0 0 6px rgba(#6c757d, 0.4);
    }
    &.complete {
        background-color: #28a745;
        box-shadow: 0 0 6px rgba(#28a745, 0.4);
    }
    &.error {
        background-color: $red;
        box-shadow: 0 0 6px rgba($red, 0.4);
    }
}

.status-text {
    font-size: 0.65rem;
    color: $white;
    font-weight: 500;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}

.upload-percentage {
    font-size: 0.7rem;
    color: $white;
    font-weight: bold;
    background: rgba(255, 255, 255, 0.08);
    padding: 2px 6px;
    border-radius: 8px;
    min-width: 32px;
    text-align: center;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}

.upload-progress-bar {
    width: 100%;
    height: 6px;
    background: linear-gradient(90deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.04));
    border-radius: 3px;
    overflow: hidden;
    position: relative;
    box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.15);
}

.upload-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, $light-green, #28a745, #20c997);
    border-radius: 3px;
    transition: width 0.3s ease;
    position: relative;
    box-shadow: 0 0 8px rgba($light-green, 0.25);
    
    &::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        animation: shimmer 2s infinite;
    }
}

.upload-progress-glow {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.08) 0%, transparent 70%);
    border-radius: 3px;
    opacity: 0.4;
    animation: glow 2s infinite;
}

@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

@keyframes glow {
    0% { opacity: 0.5; }
    50% { opacity: 0.2; }
    100% { opacity: 0.5; }
}

@keyframes pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.7; transform: scale(1.1); }
}

/* Individual Thumbnail Loader Styles */
.thumbnail-loader {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    z-index: 5;
}

.thumbnail-spinner {
    width: 20px;
    height: 20px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top: 2px solid $light-green;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

.thumbnail-image {
    width: 100%;
    height: 100%;
    object-fit: contain;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s ease;
    opacity: 1;

    &.loading {
        opacity: 0.3;
    }

    &:hover {
        transform: scale(1.02);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Preview Modal Styles */
.preview-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.95);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 2000;
    backdrop-filter: blur(5px);
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.preview-modal-content {
    position: relative;
    max-width: 95vw;
    max-height: 95vh;
    display: flex;
    justify-content: center;
    align-items: center;
    animation: scaleIn 0.3s ease-in-out;
}

@keyframes scaleIn {
    from { transform: scale(0.8); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

.preview-close-btn {
    position: absolute;
    top: -50px;
    right: -10px;
    background: rgba(220, 53, 69, 0.9);
    border: 2px solid white;
    color: white;
    font-size: 1.2rem;
    cursor: pointer;
    z-index: 2001;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;

    &:hover {
        background: rgba(220, 53, 69, 1);
        transform: scale(1.1);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
    }
}

.preview-image {
    max-width: 90vw;
    max-height: 90vh;
    width: auto;
    height: auto;
    object-fit: contain;
    border-radius: 8px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.6);
    border: 2px solid rgba(255, 255, 255, 0.1);
}

.preview-pdf {
    width: 90vw;
    height: 90vh;
    max-width: 1200px;
    max-height: 800px;
    border-radius: 8px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.6);
    border: 2px solid rgba(255, 255, 255, 0.1);
}

.preview-fallback {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 300px;
    height: 200px;
    background: linear-gradient(135deg, #6c757d, #495057);
    border-radius: 8px;
    color: white;
    text-align: center;

    i {
        font-size: 3rem;
        margin-bottom: 1rem;
        color: #dc3545;
    }

    p {
        margin: 0;
        font-size: 1.1rem;
        font-weight: bold;
    }
}

.machine-field {
    padding: 4px 8px;
    border-radius: 4px;
    transition: all 0.2s ease;
    border: 1px solid transparent;

    .editable {
        cursor: pointer;
        padding: 2px 4px;
        border-radius: 3px;
        transition: all 0.2s ease;
        border: 1px solid transparent;
        position: relative;
        
        &:hover:not(.disabled) {
            background-color: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.3);
        }
        
        &.disabled {
            cursor: not-allowed;
            opacity: 0.6;
            background-color: rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.7);
        }
        
        &::after {
            position: absolute;
            right: 4px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.8rem;
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        
        &:hover:not(.disabled)::after {
            opacity: 1;
        }
    }
}

/* --- CUTTING FILES STYLES --- */

.cutting-files-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    min-width: 150px;
}

.cutting-files-display {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    min-width: 150px;
}

.cutting-files-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    max-width: 200px;
    justify-content: center;
}

.cutting-file-item {
    position: relative;
    width: 60px;
    height: 60px;
    border: 2px solid rgba(255, 255, 255, 0.2);
    border-radius: 6px;
    overflow: hidden;
    transition: all 0.2s;
    background-color: rgba(0, 0, 0, 0.1);

    &:hover {
        border-color: #ff6b35;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }
}

.cutting-file-remove-btn {
    position: absolute;
    top: 2px;
    right: 2px;
    width: 18px;
    height: 18px;
    background-color: rgba(220, 53, 69, 0.9);
    color: white;
    border: 1px solid white;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    z-index: 10;
    transition: all 0.2s;
    backdrop-filter: blur(2px);

    &:hover {
        background-color: rgba(220, 53, 69, 1);
        transform: scale(1.1);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    }

    i {
        margin: 0;
        line-height: 1;
    }
}

.cutting-file-preview {
    width: 100%;
    height: 100%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;

    &:hover {
        &::after {
            content: '🔍';
            position: absolute;
            bottom: 2px;
            right: 2px;
            font-size: 0.8rem;
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            border-radius: 50%;
            width: 16px;
            height: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    }
}

.cutting-file-image {
    width: 100%;
    height: 100%;
    object-fit: contain;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s ease;
    opacity: 1;

    &.loading {
        opacity: 0.3;
    }

    &:hover {
        transform: scale(1.02);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }
}

.cutting-file-icon {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #ff6b35, #f7931e);
    color: white;
    font-size: 0.6rem;
    text-align: center;
    padding: 4px;
    border-radius: 4px;

    i {
        font-size: 1.5rem;
        margin-bottom: 3px;
        color: white;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
    }

    .file-type {
        font-weight: bold;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        margin-bottom: 2px;
    }

    .preview-hint {
        font-size: 0.5rem;
        opacity: 0.8;
        font-weight: normal;
    }
}

.cutting-file-loader {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    z-index: 5;
}

.cutting-file-spinner {
    width: 20px;
    height: 20px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top: 2px solid #ff6b35;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

.cutting-file-label {
    position: absolute;
    top: 2px;
    right: 2px;
    background-color: rgba(0, 0, 0, 0.7);
    color: white;
    border-radius: 50%;
    width: 16px;
    height: 16px;
    font-size: 0.6rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.cutting-placeholder-upload {
    width: 60px;
    height: 60px;
    margin: 0 1rem;
    border: 2px dashed #ff6b35;
    border-radius: 4px;
    position: relative;
    background-color: rgba(255, 107, 53, 0.1);
    overflow: hidden;
    transition: all 0.2s ease;

    &:hover {
        border-color: #f7931e;
        background-color: rgba(255, 107, 53, 0.2);
        transform: translateY(-2px);
    }
}

.cutting-placeholder-content {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    padding: 4px;
}

.cutting-placeholder-text {
    font-size: 0.5rem;
    color: #ff6b35;
    text-align: center;
    font-weight: bold;
    margin-bottom: 2px;
}

.cutting-file-types-info {
    font-size: 0.5rem;
    color: rgba(255, 107, 53, 0.8);
    text-align: center;
    line-height: 1.2;
}

.cutting-file-action-buttons {
    display: flex;
    gap: 4px;
    margin-top: 4px;
}

.cutting-file-action-btn {
    border: none;
    padding: 4px 8px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.7rem;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;

    i {
        margin-right: 4px;
    }

    &.primary {
        background-color: #ff6b35;
        color: white;

        &:hover:not(:disabled) {
            background-color: #f7931e;
            transform: translateY(-1px);
        }
        
        &:disabled {
            background-color: rgba(255, 107, 53, 0.5);
            cursor: not-allowed;
            transform: none;
        }
    }

    &.secondary {
        background-color: #6c757d;
        color: white;
        padding: 4px 6px;
        min-width: 28px;

        i {
            margin: 0;
            font-size: 0.8rem;
        }

        &:hover:not(:disabled) {
            background-color: #5a6268;
            transform: translateY(-1px);
        }
        
        &:disabled {
            background-color: rgba(#6c757d, 0.5);
            cursor: not-allowed;
            transform: none;
        }
    }
}

/* Cutting Upload Progress Styles */
.cutting-upload-progress-container {
    margin: 6px 0;
    padding: 8px;
    background: linear-gradient(135deg, rgba(255, 107, 53, 0.15), rgba(247, 147, 30, 0.08));
    border-radius: 6px;
    border: 1px solid rgba(255, 107, 53, 0.15);
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
    backdrop-filter: blur(2px);
}

.cutting-upload-progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
}

.cutting-upload-status {
    display: flex;
    align-items: center;
    gap: 6px;
}

.cutting-status-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background-color: #ccc;
    animation: pulse 2s infinite;

    &.starting {
        background-color: #ffc107;
        box-shadow: 0 0 6px rgba(#ffc107, 0.4);
    }
    &.uploading {
        background-color: #17a2b8;
        box-shadow: 0 0 6px rgba(#17a2b8, 0.4);
    }
    &.processing {
        background-color: #007bff;
        box-shadow: 0 0 6px rgba(#007bff, 0.4);
    }
    &.finalizing {
        background-color: #6c757d;
        box-shadow: 0 0 6px rgba(#6c757d, 0.4);
    }
    &.complete {
        background-color: #28a745;
        box-shadow: 0 0 6px rgba(#28a745, 0.4);
    }
    &.error {
        background-color: $red;
        box-shadow: 0 0 6px rgba($red, 0.4);
    }
}

.cutting-status-text {
    font-size: 0.65rem;
    color: $white;
    font-weight: 500;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}

.cutting-upload-percentage {
    font-size: 0.7rem;
    color: $white;
    font-weight: bold;
    background: rgba(255, 107, 53, 0.08);
    padding: 2px 6px;
    border-radius: 8px;
    min-width: 32px;
    text-align: center;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}

.cutting-upload-progress-bar {
    width: 100%;
    height: 6px;
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: 3px;
    overflow: hidden;
    position: relative;
}

.cutting-upload-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #ff6b35, #f7931e);
    border-radius: 3px;
    transition: width 0.3s ease;
    position: relative;
    overflow: hidden;
}

.cutting-upload-progress-glow {
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
    animation: cuttingGlow 2s infinite;
}

@keyframes cuttingGlow {
    0% { left: -100%; }
    100% { left: 100%; }
}

.download-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-top: 12px;
    padding: 8px 16px;
    background: linear-gradient(135deg, #ff6b35, #f7931e);
    color: white;
    text-decoration: none;
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.2s ease;

    &:hover {
        background: linear-gradient(135deg, #f7931e, #ff6b35);
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    i {
        font-size: 1rem;
    }
}

.blue {
    color: #007bff;
}

.ultra-light-blue {
    color: #b3d9ff;
}

/* File Dimensions Display Styles */
.files-container {
    margin-bottom: 8px;
    max-width: 80%;
    overflow: hidden;
    box-sizing: border-box;
}

.files-scroll {
    display: flex;
    width: 100%;
    overflow-x: auto;
    gap: 6px;
    padding-bottom: 3px;
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.3) transparent;
}

.files-scroll::-webkit-scrollbar {
    height: 6px;
}

.files-scroll::-webkit-scrollbar-track {
    background: transparent;
}

.files-scroll::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.3);
    border-radius: 3px;
}

.files-scroll::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.5);
}

.files-scroll .file-dimensions {
    flex: 0 0 220px;
    max-width: 220px;
    min-width: 220px;
}

.file-dimensions {
    text-align: left;
    padding: 3px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 4px;
    margin-bottom: 3px;
    flex-shrink: 0;
}

.file-name {
    font-weight: bold;
    color: #ffd700;
    font-size: 0.75rem;
    margin-bottom: 1px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    padding-bottom: 1px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.page-dimensions {
    font-size: 0.7rem;
    margin: 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.page-label {
    color: #ccc;
    margin-right: 8px;
}

.dimensions {
    color: #fff;
    font-weight: 500;
}

.total-area {
    margin-top: 6px;
    padding-top: 6px;
    border-top: 2px solid rgba(255, 255, 255, 0.3);
    text-align: center;
    color: #ffd700;
    font-size: 0.9rem;
    font-weight: bold;
}

.file-total {
    margin-top: 2px;
    padding-top: 2px;
    border-top: 1px solid rgba(255, 255, 255, 0.2);
    text-align: center;
    font-size: 0.75rem;
}

.file-total-label {
    color: #ccc;
    margin-right: 8px;
}

.file-total-area {
    color: greenyellow;
    font-weight: 500;
}



.debug-info {
    margin-top: 4px;
    font-size: 0.65rem;
    color: #888;
    opacity: 0.8;
}

/* Cost Breakdown Modal Styles */
.cost-breakdown-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.cost-breakdown-content {
    background: white;
    border-radius: 8px;
    padding: 20px;
    max-width: 800px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.cost-breakdown-close-btn {
    position: absolute;
    top: 15px;
    right: 20px;
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
    padding: 5px;
    border-radius: 50%;
    transition: background-color 0.2s;
}

.cost-breakdown-close-btn:hover {
    background-color: #f0f0f0;
}

.cost-breakdown-header {
    text-align: center;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid #e0e0e0;
}

.cost-breakdown-header h3 {
    margin: 0 0 10px 0;
    color: #333;
    font-size: 24px;
    font-weight: bold;
}

.job-name {
    margin: 0;
    color: #666;
    font-size: 16px;
    font-style: italic;
}

.cost-breakdown-body {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.breakdown-section {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    border-left: 4px solid #007bff;
}

.breakdown-section h4 {
    margin: 0 0 15px 0;
    color: #333;
    font-size: 18px;
    font-weight: bold;
    border-bottom: 1px solid #dee2e6;
    padding-bottom: 8px;
}

.breakdown-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.breakdown-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    background: white;
    border-radius: 6px;
    border: 1px solid #e9ecef;
}

.breakdown-item .label {
    font-weight: 600;
    color: #495057;
}

.breakdown-item .value {
    font-weight: bold;
    color: #007bff;
}

.component-breakdown {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.component-item {
    background: white;
    border-radius: 6px;
    border: 1px solid #e9ecef;
    overflow: hidden;
}

.component-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    background: #e3f2fd;
    border-bottom: 1px solid #e9ecef;
}

.component-name {
    font-weight: bold;
    color: #1976d2;
}

.component-cost {
    font-weight: bold;
    color: #2e7d32;
    font-size: 18px;
}

.component-details {
    padding: 15px;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.detail-row:last-child {
    margin-bottom: 0;
}

.detail-label {
    font-weight: 600;
    color: #495057;
}

.detail-value {
    font-weight: 500;
    color: #333;
}

.total-cost {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: #e8f5e8;
    border-radius: 6px;
    margin-top: 15px;
    border: 2px solid #4caf50;
}

.total-label {
    font-weight: bold;
    color: #2e7d32;
    font-size: 18px;
}

.total-value {
    font-weight: bold;
    color: #2e7d32;
    font-size: 20px;
}

.no-breakdown, .no-deduction {
    text-align: center;
    padding: 20px;
    color: #666;
    font-style: italic;
}

.stored-cost {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: #fff3cd;
    border-radius: 6px;
    margin-top: 15px;
    border: 1px solid #ffeaa7;
}

.material-breakdown {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.material-item {
    background: white;
    border-radius: 6px;
    border: 1px solid #e9ecef;
    overflow: hidden;
}

.material-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    background: #fff3cd;
    border-bottom: 1px solid #e9ecef;
}

.material-name {
    font-weight: bold;
    color: #856404;
}

.material-quantity {
    font-weight: bold;
    color: #d63384;
    font-size: 16px;
}

.material-details {
    padding: 15px;
}

.formula-display {
    background: white;
    padding: 15px;
    border-radius: 6px;
    border: 1px solid #e9ecef;
}

.formula-display p {
    margin: 0;
    line-height: 1.6;
    color: #333;
}

.formula-display strong {
    color: #007bff;
}

/* Info button styles */
.info-btn {
    background: none;
    border: none;
    color: #007bff;
    cursor: pointer;
    padding: 2px 6px;
    border-radius: 50%;
    transition: all 0.2s;
    font-size: 14px;
}

.info-btn:hover {
    background-color: #e3f2fd;
    transform: scale(1.1);
}

.info-btn:active {
    transform: scale(0.95);
}

/* Enhanced Materials Section Styling */
.materials-item {
    border-left: 2px solid #3B82F6;
    padding-left: 8px;
    transition: all 0.2s ease;
}

.materials-item:hover {
    background-color: rgba(59, 130, 246, 0.1);
    border-left-color: #1D4ED8;
}

.calculation-settings-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 4px 8px;
    background-color: #374151;
    border-radius: 4px 4px 0 0;
    border-left: 3px solid #F59E0B;
}

.calculation-settings-content {
    border-left: 3px solid #F59E0B;
    padding-left: 8px;
    border-radius: 0 0 4px 4px;
    background-color: rgba(245, 158, 11, 0.1);
    border: 1px solid rgba(245, 158, 11, 0.2);
    border-top: none;
}

.materials-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 4px 8px;
    background-color: #374151;
    border-radius: 4px 4px 0 0;
}

.materials-toggle-btn {
    background: none;
    border: none;
    color: #E5E7EB;
    padding: 2px 6px;
    border-radius: 4px;
    transition: all 0.2s ease;
    cursor: pointer;
}

.materials-toggle-btn:hover {
    background-color: rgba(255, 255, 255, 0.1);
    color: #FFFFFF;
}

/* Make sections feel more contained */
.compact-progress {
    border-radius: 0 0 4px 4px;
    margin: 0;
    padding: 4px;
}

/* Loading states and spinners */
.fa-spinner {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Disabled button states */
.delete-btn:disabled,
.cutting-file-remove-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Disabled drop zone states */
.placeholder-upload.disabled,
.cutting-placeholder-upload.disabled {
    opacity: 0.6;
    pointer-events: none;
}

.placeholder-content.disabled,
.cutting-placeholder-content.disabled {
    cursor: not-allowed;
    opacity: 0.6;
    pointer-events: none;
}

</style>


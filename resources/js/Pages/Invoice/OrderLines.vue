<template>
    <div v-if="$props.jobs?.length > 0">
        <table class="border">
            <tbody>
            <tr v-for="(job, index) in jobsToDisplay" :key="index">
                <!-- ORDER INDEX, NAME, AND ADDITIONAL INFO -->
                <div class="text-white">
                    <td class="text-black bg-gray-200 font-weight-black flex justify-between items-center" style="padding: 0 0 0 5px">
                        <span class="bold">#{{ index + 1 }} {{ job.name }}</span>
                        <button
                            @click="confirmDelete(job)"
                            class="delete-btn text-red-600 hover:text-red-800"
                        >
                            <i class="fa fa-times"></i>
                        </button>
                    </td>
                    <td> File: <span class="bold">{{ job.file }}</span></td>
                    <td>ID: <span class="bold">{{ job.id }}</span></td>
                    <td>{{ $t('width') }}: <span class="bold">{{ job.width ? job.width.toFixed(2) : '0.00' }}mm</span></td>
                    <td>{{ $t('height') }}: <span class="bold">{{ job.height ? job.height.toFixed(2) : '0.00' }}mm</span></td>
                    <td>
                        {{ $t('Quantity') }}:
                        <span
                            class="bold editable bg-white/20"
                            @dblclick="startEditing(job, 'quantity')"
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
                            @dblclick="startEditing(job, 'copies')"
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
                </div>

                <!-- FILE INFO -->
                <div class="flex text-white">
                    <td>
                        <!-- Hidden file input -->
                        <input
                            type="file"
                            accept=".pdf"
                            multiple
                            @change="(e) => handleMultipleFiles(e, job)"
                            class="file-input"
                            :id="'files-input-' + job.id"
                            style="display: none;"
                        />

                        <!-- Simplified File Display -->
                        <div class="file-display-container">
                            <!-- Thumbnails Grid -->
                            <div v-if="getJobThumbnails(job).length > 0" class="thumbnails-grid">
                                <div 
                                    v-for="(thumb, thumbIndex) in getJobThumbnails(job)" 
                                    :key="thumbIndex"
                                    class="thumbnail-item"
                                >
                                    <!-- Remove button -->
                                    <button 
                                        @click="removeOriginalFile(job, thumbIndex)" 
                                        class="thumbnail-remove-btn"
                                        title="Remove file"
                                    >
                                        <i class="fa fa-times"></i>
                                    </button>
                                    
                                    <!-- Thumbnail or PDF Icon -->
                                    <div @click="openPreviewModal(thumb, job)" class="thumbnail-preview">
                                        <!-- Individual thumbnail loader -->
                                        <div v-if="!thumb.imageLoaded && !thumb.imageLoadError" class="thumbnail-loader">
                                            <div class="thumbnail-spinner"></div>
                                        </div>
                                        
                                        <!-- Always try to show thumbnail first -->
                                        <img
                                            v-if="!thumb.imageLoadError"
                                            :src="getThumbnailUrl(job.id, thumb.index)"
                                            :alt="'Thumbnail ' + (thumbIndex + 1)"
                                            class="thumbnail-image"
                                            :class="{ 'loading': !thumb.imageLoaded }"
                                            @error="handleThumbnailError(thumb, $event)"
                                            @load="handleThumbnailLoad(thumb)"
                                        />
                                        <!-- Fallback to PDF icon only if thumbnail fails to load -->
                                        <div v-else class="pdf-thumbnail">
                                            <i class="fa fa-file-pdf"></i>
                                            <span>PDF</span>
                                            <div class="preview-hint">Click to view</div>
                                        </div>
                                    </div>
                                    
                                    <div class="thumbnail-label">{{ thumbIndex + 1 }}</div>
                                </div>
                            </div>

                            <!-- Placeholder when no files -->
                            <div v-else class="placeholder-upload">
                                <div class="placeholder-content" @click="triggerFilesInput(job.id)">
                                    <span class="placeholder-text">Drop Files</span>
                            </div>
                        </div>

                            <!-- Upload Progress Indicator -->
                            <div v-if="uploadStates[job.id] === 'starting' || uploadStates[job.id] === 'uploading' || uploadStates[job.id] === 'processing' || uploadStates[job.id] === 'finalizing'" class="upload-progress-container">
                                <div class="upload-progress-header">
                                    <div class="upload-status">
                                        <div class="status-indicator" :class="uploadStates[job.id]"></div>
                                        <span class="status-text">
                                            <span v-if="uploadStates[job.id] === 'starting'">Starting upload...</span>
                                            <span v-else-if="uploadStates[job.id] === 'uploading'">Files uploaded, processing...</span>
                                            <span v-else-if="uploadStates[job.id] === 'processing'">Processing files...</span>
                                            <span v-else-if="uploadStates[job.id] === 'finalizing'">Finalizing...</span>
                                        </span>
                                    </div>
                                    <div class="upload-percentage">{{ uploadProgress[job.id] || 0 }}%</div>
                                </div>
                                <div class="upload-progress-bar">
                                    <div 
                                        class="upload-progress-fill" 
                                        :style="{ width: `${uploadProgress[job.id] || 0}%` }"
                                    ></div>
                                    <div class="upload-progress-glow"></div>
                                </div>
                            </div>

                            <!-- Action buttons -->
                            <div class="file-action-buttons">
                                <button
                                    @click="triggerFilesInput(job.id)"
                                    class="file-action-btn primary"
                                    :disabled="uploadStates[job.id] === 'starting' || uploadStates[job.id] === 'uploading' || uploadStates[job.id] === 'processing' || uploadStates[job.id] === 'finalizing'"
                                    title="Upload files"
                                >
                                    <i class="fa fa-upload"></i> 
                                    {{ uploadStates[job.id] === 'starting' || uploadStates[job.id] === 'uploading' || uploadStates[job.id] === 'processing' || uploadStates[job.id] === 'finalizing' ? 'Uploading...' : 'Upload Files' }}
                                </button>
                                <button
                                    v-if="job.originalFile && job.originalFile.length > 0"
                                    @click="refreshThumbnails(job.id)"
                                    class="file-action-btn secondary"
                                    title="Refresh thumbnails"
                                >
                                    <i class="fa fa-refresh"></i>
                                </button>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div v-if="job.machinePrint" class="machine-field">
                            {{ $t('machineP') }}: 
                            <span
                                class="bold editable bg-white/20"
                                @dblclick="startEditingMachine(job, 'machinePrint')"
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
                                @dblclick="startEditingMachine(job, 'machineCut')"
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
                    <td>
                        <!-- Cutting Files Upload Area -->
                        <div class="cutting-files-container">
                            <!-- Hidden file input for cutting files -->
                            <input
                                type="file"
                                accept=".pdf,.svg,.dxf,.cdr,.ai"
                                multiple
                                @change="(e) => handleCuttingFiles(e, job)"
                                class="file-input"
                                :id="'cutting-files-input-' + job.id"
                                style="display: none;"
                            />
                            <!-- Cutting Files Display -->
                            <div class="cutting-files-display">
                                <!-- Cutting Files Grid -->
                                <div v-if="job.cuttingFiles && job.cuttingFiles.length > 0" class="cutting-files-grid">
                                    <div 
                                        v-for="(cuttingFile, cuttingIndex) in job.cuttingFiles" 
                                        :key="cuttingIndex"
                                        class="cutting-file-item"
                                    >
                                        <!-- Remove button -->
                                        <button 
                                            @click="removeCuttingFile(job, cuttingIndex)" 
                                            class="cutting-file-remove-btn"
                                            title="Remove cutting file"
                                        >
                                            <i class="fa fa-times"></i>
                                        </button>
                                        
                                        <!-- File icon and click to view -->
                                        <div @click="openCuttingFileInNewTab(job, cuttingIndex)" class="cutting-file-preview">
                                            <div class="cutting-file-icon">
                                                <i :class="getCuttingFileIcon(getFileExtension(cuttingFile))"></i>
                                                <span class="file-type">{{ getFileExtension(cuttingFile).toUpperCase() }}</span>
                                                <div class="preview-hint">Click to view</div>
                                            </div>
                                        </div>
                                        <div class="cutting-file-label">{{ cuttingIndex + 1 }}</div>
                                    </div>
                                </div>

                                <!-- Placeholder when no cutting files -->
                                <div v-else class="cutting-placeholder-upload">
                                    <div class="cutting-placeholder-content" @click="triggerCuttingFilesInput(job.id)">
                                        <span class="cutting-placeholder-text">Drop Cutting Files</span>
                                        <div class="cutting-file-types-info">
                                            <small>PDF, SVG, DXF, CDR, AI</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Cutting Files Upload Progress -->
                                <div v-if="cuttingUploadStates[job.id] === 'starting' || cuttingUploadStates[job.id] === 'uploading' || cuttingUploadStates[job.id] === 'processing' || cuttingUploadStates[job.id] === 'finalizing'" class="cutting-upload-progress-container">
                                    <div class="cutting-upload-progress-header">
                                        <div class="cutting-upload-status">
                                            <div class="cutting-status-indicator" :class="cuttingUploadStates[job.id]"></div>
                                            <span class="cutting-status-text">
                                                <span v-if="cuttingUploadStates[job.id] === 'starting'">Starting upload...</span>
                                                <span v-else-if="cuttingUploadStates[job.id] === 'uploading'">Files uploaded, processing...</span>
                                                <span v-else-if="cuttingUploadStates[job.id] === 'processing'">Processing files...</span>
                                                <span v-else-if="cuttingUploadStates[job.id] === 'finalizing'">Finalizing...</span>
                                            </span>
                                        </div>
                                        <div class="cutting-upload-percentage">{{ cuttingUploadProgress[job.id] || 0 }}%</div>
                                    </div>
                                    <div class="cutting-upload-progress-bar">
                                        <div 
                                            class="cutting-upload-progress-fill" 
                                            :style="{ width: `${cuttingUploadProgress[job.id] || 0}%` }"
                                        ></div>
                                        <div class="cutting-upload-progress-glow"></div>
                                    </div>
                                </div>

                                <!-- Action buttons -->
                                <div class="cutting-file-action-buttons">
                                    <button
                                        @click="triggerCuttingFilesInput(job.id)"
                                        class="cutting-file-action-btn primary"
                                        :disabled="cuttingUploadStates[job.id] === 'starting' || cuttingUploadStates[job.id] === 'uploading' || cuttingUploadStates[job.id] === 'processing' || cuttingUploadStates[job.id] === 'finalizing'"
                                        title="Upload cutting files"
                                    >
                                        <i class="fa fa-scissors"></i> 
                                        {{ cuttingUploadStates[job.id] === 'starting' || cuttingUploadStates[job.id] === 'uploading' || cuttingUploadStates[job.id] === 'processing' || cuttingUploadStates[job.id] === 'finalizing' ? 'Uploading...' : 'Upload Cutting Files' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </td>
                </div>

                <!-- ACTIONS SECTION -->
                <div v-if="job.actions && job.actions.length > 0">
                    <td>
                        <div class="green p-1 pl-1 w-[40rem] text-white bg-gray-700" @click="toggleActions(job.id)" style="cursor: pointer">
                            {{$t('ACTIONS')}} ⏷
                        </div>
                        <transition name="slide-fade">
                            <div v-if="showActions === job.id" class="ultra-light-green text-white   pb-1">
                                <div v-for="(action, actionIndex) in job.actions" :key="actionIndex" class="bg-gray-700 pl-1 w-full text-left">
                                    <span>{{actionIndex +1 }}.{{ action.name }}</span>
                                </div>
                            </div>
                        </transition>
                    </td>
                </div>

                <!-- SHIPPING INFO -->
                <div class="flex justify-between">
                    <td class="flex items-center bg-gray-200 text-black" style="padding: 0 5px 0 0;">
                        <img src="/images/shipping.png" class="w-8 h-8 pr-1" alt="Shipping">
                        {{ $t('Shipping') }}: <strong> {{ job.shippingInfo }}</strong>
                    </td>
                    <div v-if="!isRabotnikComputed" class="bg-gray-200 text-black bold">
                        <div class="pt-1 pl-2 pr-2 pb-2">
                            {{ $t('jobPrice') }}: <span class="bold">{{ (job.salePrice || job.price).toFixed(2) }} ден.</span>
                        </div>
                        <div class="pt-1 pl-2 pr-2">
                            {{ $t('jobPriceCost') }}: <span class="bold">{{ job.price.toFixed(2) }} ден.</span>
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


    </div>
</template>

<script>
import { useToast } from "vue-toastification";
import axios from "axios";
import useRoleCheck from '@/Composables/useRoleCheck';
import { computed } from 'vue';

export default {
    name: "OrderLines",
    setup() {
        const { isRabotnik } = useRoleCheck();
        
        const isRabotnikComputed = computed(() => isRabotnik.value);
        
        return {
            isRabotnikComputed
        };
    },

    props: {
        jobs: Array,
        updatedJobs: Array,
    },

    data() {
        return {
            showActions: null,
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
            uploadProgress: {}, // Track upload progress for each job
            uploadStates: {}, // Track upload states: 'idle', 'uploading', 'processing', 'finalizing', 'complete', 'error'
            machinesPrint: [], // Available print machines
            machinesCut: [], // Available cut machines
            machinePrintInput: null,
            machineCutInput: null,
            // Cutting files data
            jobCuttingFiles: {}, // Store cutting files for each job
            cuttingUploadProgress: {}, // Track cutting upload progress for each job
            cuttingUploadStates: {}, // Track cutting upload states
            showCuttingFilePreviewModal: false,
            cuttingPreviewFile: null
        };
    },

    computed: {
        jobsToDisplay() {
            const mergedJobs = [...this.jobs || [], ...this.updatedJobs || [], ...this.jobsWithPrices || []];

            // Create a Map to store jobs by ID, with later entries overriding earlier ones
            const jobMap = new Map();

            // Process all jobs - later ones will override earlier ones with same ID
            for (const job of mergedJobs) {
                jobMap.set(job.id, {
                    ...job,
                    // Ensure numeric fields are properly typed
                    quantity: parseInt(job.quantity) || 1,
                    copies: parseInt(job.copies) || 1,
                    price: parseFloat(job.price) || 0,
                    salePrice: parseFloat(job.salePrice) || null
                });
            }

            // Convert Map values back to array and sort by ID
            const result = Array.from(jobMap.values()).sort((a, b) => a.id - b.id);
            
            return result;
        },

        fileJobs() {
            return this.jobsToDisplay.filter(job => job.file && job.file !== 'placeholder.jpeg');
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
            },
            deep: true
        }
    },

    methods: {
        // Initialize upload states for a job
        initializeJobStates(jobId) {
            if (!this.uploadStates[jobId]) {
                this.uploadStates[jobId] = 'idle';
            }
            if (!this.uploadProgress[jobId]) {
                this.uploadProgress[jobId] = 0;
            }
        },
        triggerFilesInput(jobId) {
            const fileInput = document.getElementById('files-input-' + jobId);
            if (fileInput) {
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
            console.log('Opening PDF preview for:', thumbnail, 'from job:', job.id);
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
                
                console.log('PDF preview opened with backend URL:', backendUrl);
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

        handleThumbnailError(thumb, event) {
            const imgElement = event.target;
            console.error('Thumbnail failed to load:', imgElement.src);
            thumb.imageLoadError = true;
            this.$forceUpdate();
        },

        handleThumbnailLoad(thumb) {
            thumb.imageLoaded = true; // Mark as loaded
            thumb.imageLoadError = false; // Ensure error is false
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



        toggleActions(jobId) {
            this.showActions = this.showActions === jobId ? null : jobId;
        },

        // Removed handleFileDrop - now using handleMultipleFiles for all uploads

        async handleMultipleFiles(event, job) {
            const files = Array.from(event.target.files);
            if (!files.length) return;

            const toast = useToast();

            // Initialize upload state
            this.uploadStates[job.id] = 'uploading';
            this.uploadProgress[job.id] = 0;
            // this.thumbnailLoading[job.id] = true; // Removed global loading state

            // Clear existing thumbnails immediately to prevent showing old ones
            this.jobThumbnails[job.id] = [];
            this.$forceUpdate();

            try {
                const formData = new FormData();
                
                // Add all files to FormData
                files.forEach((file, index) => {
                    formData.append(`files[${index}]`, file);
                });

                // Start progress polling
                const progressInterval = setInterval(async () => {
                    try {
                        const progressResponse = await axios.get(`/jobs/${job.id}/upload-progress`);
                        const progressData = progressResponse.data;
                        
                        if (progressData.status === 'starting') {
                            this.uploadProgress[job.id] = progressData.progress;
                            this.uploadStates[job.id] = progressData.status;
                        } else if (progressData.status === 'uploading') {
                            this.uploadProgress[job.id] = progressData.progress;
                            this.uploadStates[job.id] = progressData.status;
                        } else if (progressData.status === 'processing' || progressData.status === 'finalizing') {
                            this.uploadProgress[job.id] = progressData.progress;
                            this.uploadStates[job.id] = progressData.status;
                        } else if (progressData.status === 'complete') {
                            this.uploadProgress[job.id] = 100;
                            this.uploadStates[job.id] = 'complete';
                            clearInterval(progressInterval);
                        } else if (progressData.status === 'error') {
                            this.uploadStates[job.id] = 'error';
                            clearInterval(progressInterval);
                        }
                    } catch (error) {
                        console.error('Failed to get progress:', error);
                    }
                }, 300); // Poll every 300ms for more responsive updates

                // Create axios instance with progress tracking
                const response = await axios.post(
                    `/jobs/${job.id}/upload-multiple-files`, 
                    formData, 
                    {
                        headers: {
                            'Content-Type': 'multipart/form-data',
                        },
                        onUploadProgress: (progressEvent) => {
                            if (progressEvent.total) {
                                // Only update progress if backend hasn't started processing yet
                                if (this.uploadStates[job.id] === 'uploading') {
                                    this.uploadProgress[job.id] = Math.round(
                                        (progressEvent.loaded * 100) / progressEvent.total
                                    );
                                }
                            }
                        },
                    }
                );

                // Clear progress polling
                clearInterval(progressInterval);

                // Update job with new original files and summed dimensions if calculated
                const updatedJob = {
                    ...job,
                    originalFile: response.data.originalFiles || [],
                    // Update dimensions if they were calculated (now using total dimensions)
                    ...(response.data.dimensions && response.data.dimensions.total_width_mm > 0 && {
                        width: response.data.dimensions.total_width_mm,
                        height: response.data.dimensions.total_height_mm
                    })
                };

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

                // Use thumbnails from upload response immediately if available
                if (response.data.thumbnails && response.data.thumbnails.length > 0) {
                    // Initialize imageLoadError and imageLoaded for each thumbnail
                    this.jobThumbnails[job.id] = response.data.thumbnails.map(thumb => ({
                        ...thumb,
                        imageLoadError: false,
                        imageLoaded: false // Mark as not loaded initially
                    }));
                    this.$forceUpdate();
                    this.uploadProgress[job.id] = 100;
                    this.uploadStates[job.id] = 'complete';
                    // this.thumbnailLoading[job.id] = false; // Removed global loading state
                } else {
                    // Wait a moment for backend processing, then reload thumbnails
                    setTimeout(async () => {
                        await this.loadJobThumbnails(job.id);
                        this.uploadProgress[job.id] = 100;
                        this.uploadStates[job.id] = 'complete';
                        // this.thumbnailLoading[job.id] = false; // Removed global loading state
                    }, 1000); // Reduced from 2000ms to 1000ms
                }

                // Emit updates
                this.$emit('jobs-updated', [updatedJob]);
                this.$emit('job-updated', updatedJob);

                // Show success message with cumulative dimension info
                if (response.data.dimensions && response.data.dimensions.total_width_mm > 0) {
                    const totalWidth = response.data.dimensions.total_width_mm;
                    const totalHeight = response.data.dimensions.total_height_mm;
                    const totalM2 = response.data.dimensions.total_area_m2;
                    const fileCount = response.data.dimensions.files_count;
                    
                    toast.success(`${files.length} files uploaded successfully. Job total: ${totalWidth.toFixed(2)}×${totalHeight.toFixed(2)}mm (${totalM2.toFixed(4)}m²) from ${fileCount} files`);
                } else {
                    toast.success(`${files.length} files uploaded successfully`);
                }

                // Reset upload state after a delay
                setTimeout(() => {
                    this.uploadStates[job.id] = 'idle';
                    this.uploadProgress[job.id] = 0;
                }, 2000);

            } catch (error) {
                console.error('Error uploading files:', error);
                this.uploadStates[job.id] = 'error';
                this.uploadProgress[job.id] = 0;
                // this.thumbnailLoading[job.id] = false; // Removed global loading state
                
                if (error.response) {
                    console.error('Server response:', error.response.data);
                    toast.error(`Failed to upload files: ${error.response.data.details || error.response.data.error}`);
                } else {
                    toast.error('Failed to upload files: Network error');
            }

                // Reset error state after a delay
                setTimeout(() => {
                    this.uploadStates[job.id] = 'idle';
                }, 3000);
            }

            // Reset the input
            event.target.value = '';
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

            // Debug log to check job data
            console.log('Job data being sent:', {
                jobId: job.id,
                catalog_item_id,
                client_id,
                quantity: this.editingField === 'quantity' ? valueToUpdate : job.quantity,
                copies: this.editingField === 'copies' ? valueToUpdate : job.copies
            });

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
                console.log('Request data being sent:', {
                    jobId: job.id,
                    editingField: this.editingField,
                    value: valueToUpdate,
                    requestData
                });

                // First update the backend
                const response = await axios.put(`/jobs/${job.id}`, requestData);

                // Debug log to check response data
                console.log('Response from server:', response.data);

                if (response.status === 200) {
                    // Update the local job with the response data, preserving width and height
                    const updatedJob = {
                        ...response.data.job,
                        width: job.width || response.data.job.width,
                        height: job.height || response.data.job.height,
                        quantity: parseInt(response.data.job.quantity),
                        copies: parseInt(response.data.job.copies),
                        price: parseFloat(response.data.job.price),
                        salePrice: parseFloat(response.data.job.salePrice) || null,
                        effective_catalog_item_id: catalog_item_id,
                        effective_client_id: client_id
                    };

                    // Debug log to check updated job data
                    console.log('Updated job data:', updatedJob);

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
            
            try {
                const response = await axios.delete(`/jobs/${job.id}/remove-original-file`, {
                    data: { file_index: fileIndex }
                });

                // Update job with new original files and recalculated dimensions
                const updatedJob = {
                    ...job,
                    originalFile: response.data.originalFiles || [],
                    // Update dimensions if they were recalculated
                    ...(response.data.dimensions && {
                        width: response.data.dimensions.width_mm,
                        height: response.data.dimensions.height_mm
                    })
                };

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

                // Clear the thumbnail cache for this job FIRST
                this.jobThumbnails[job.id] = [];
                this.$forceUpdate();

                // If there are still files remaining, reload thumbnails
                if (response.data.originalFiles && response.data.originalFiles.length > 0) {
                    // Wait a moment for backend processing, then reload thumbnails
                setTimeout(async () => {
                    await this.loadJobThumbnails(job.id);
                }, 500);
                }

                // Emit updates
                this.$emit('jobs-updated', [updatedJob]);
                this.$emit('job-updated', updatedJob);

                // Show success message with dimension info if available
                if (response.data.dimensions && response.data.removed_file_dimensions) {
                    const removedWidth = response.data.removed_file_dimensions.width_mm;
                    const removedHeight = response.data.removed_file_dimensions.height_mm;
                    const removedArea = response.data.removed_file_dimensions.area_m2;
                    const newWidth = response.data.dimensions.width_mm;
                    const newHeight = response.data.dimensions.height_mm;
                    const newArea = response.data.dimensions.area_m2;
                    
                    toast.success(`File removed successfully. Job dimensions updated: ${newWidth?.toFixed(2) || '0.00'}×${newHeight?.toFixed(2) || '0.00'}mm (${newArea?.toFixed(4) || '0.0000'}m²)`);
                } else {
                toast.success('File removed successfully');
                }
            } catch (error) {
                console.error('Error removing file:', error);
                toast.error('Failed to remove file');
            }
        },

        // --- CUTTING FILES METHODS ---

        triggerCuttingFilesInput(jobId) {
            const fileInput = document.getElementById('cutting-files-input-' + jobId);
            if (fileInput) {
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

        async handleCuttingFiles(event, job) {
            const files = Array.from(event.target.files);
            if (!files.length) {
                event.target.value = '';
                return;
            }

            const toast = useToast();

            this.cuttingUploadStates[job.id] = 'uploading';
            this.cuttingUploadProgress[job.id] = 0;

            this.jobCuttingFiles[job.id] = [];
            this.$forceUpdate();

            try {
                const formData = new FormData();
                
                files.forEach((file, index) => {
                    formData.append(`files[${index}]`, file);
                });

                const progressInterval = setInterval(async () => {
                    try {
                        const progressResponse = await axios.get(`/jobs/${job.id}/cutting-upload-progress`);
                        const progressData = progressResponse.data;
                        
                        if (progressData.status === 'starting') {
                            this.cuttingUploadProgress[job.id] = progressData.progress;
                            this.cuttingUploadStates[job.id] = progressData.status;
                        } else if (progressData.status === 'uploading') {
                            this.cuttingUploadProgress[job.id] = progressData.progress;
                            this.cuttingUploadStates[job.id] = progressData.status;
                        } else if (progressData.status === 'processing' || progressData.status === 'finalizing') {
                            this.cuttingUploadProgress[job.id] = progressData.progress;
                            this.cuttingUploadStates[job.id] = progressData.status;
                        } else if (progressData.status === 'complete') {
                            this.cuttingUploadProgress[job.id] = 100;
                            this.cuttingUploadStates[job.id] = 'complete';
                            clearInterval(progressInterval);
                        } else if (progressData.status === 'error') {
                            this.cuttingUploadStates[job.id] = 'error';
                            clearInterval(progressInterval);
                        }
                    } catch (error) {
                        console.error('Failed to get cutting upload progress:', error);
                    }
                }, 300);

                const response = await axios.post(
                    `/jobs/${job.id}/upload-cutting-files`, 
                    formData, 
                    {
                        headers: {
                            'Content-Type': 'multipart/form-data',
                        },
                        onUploadProgress: (progressEvent) => {
                            if (progressEvent.total) {
                                if (this.cuttingUploadStates[job.id] === 'uploading') {
                                    this.cuttingUploadProgress[job.id] = Math.round(
                                        (progressEvent.loaded * 100) / progressEvent.total
                                    );
                                }
                            }
                        },
                    }
                );

                clearInterval(progressInterval);

                const updatedJob = {
                    ...job,
                    cuttingFiles: response.data.cuttingFiles || []
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
                    this.cuttingUploadProgress[job.id] = 100;
                    this.cuttingUploadStates[job.id] = 'complete';
                } else {
                    setTimeout(async () => {
                        await this.loadJobCuttingFiles(job.id);
                        this.cuttingUploadProgress[job.id] = 100;
                        this.cuttingUploadStates[job.id] = 'complete';
                    }, 1000);
                }

                this.$emit('jobs-updated', [updatedJob]);
                this.$emit('job-updated', updatedJob);

                toast.success(`${files.length} cutting files uploaded successfully`);

                setTimeout(() => {
                    this.cuttingUploadStates[job.id] = 'idle';
                    this.cuttingUploadProgress[job.id] = 0;
                }, 2000);

            } catch (error) {
                console.error('Error uploading cutting files:', error);
                this.cuttingUploadStates[job.id] = 'error';
                this.cuttingUploadProgress[job.id] = 0;
                
                if (error.response) {
                    toast.error(`Failed to upload cutting files: ${error.response.data.details || error.response.data.error}`);
                } else {
                    toast.error('Failed to upload cutting files: Network error');
                }

                setTimeout(() => {
                    this.cuttingUploadStates[job.id] = 'idle';
                }, 3000);
            }

            event.target.value = '';
        },

        async removeCuttingFile(job, fileIndex) {
            const toast = useToast();
            
            try {
                const response = await axios.delete(`/jobs/${job.id}/remove-cutting-file`, {
                    data: { fileIndex: fileIndex }
                });

                const updatedJob = {
                    ...job,
                    cuttingFiles: response.data.remaining_files || []
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

                toast.success('Cutting file removed successfully');
            } catch (error) {
                console.error('Error removing cutting file:', error);
                toast.error('Failed to remove cutting file');
            }
        },

        // Initialize cutting file states for a job
        initializeCuttingJobStates(jobId) {
            if (!this.cuttingUploadStates[jobId]) {
                this.cuttingUploadStates[jobId] = 'idle';
            }
            if (!this.cuttingUploadProgress[jobId]) {
                this.cuttingUploadProgress[jobId] = 0;
            }
        },
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
    transition: max-height 0.5s ease-in-out;
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
}

.editable:hover {
    background-color: rgba(255, 255, 255, 0.1);
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

.delete-btn {
    padding: 4px 8px;
    border-radius: 4px;
    transition: all 0.2s;
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

.thumbnail-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s ease;

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
    object-fit: cover;
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
    max-width: 100%;
    max-height: 100%;
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
        
        &:hover {
            background-color: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.3);
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
        
        &:hover::after {
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
    object-fit: cover;
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

</style>


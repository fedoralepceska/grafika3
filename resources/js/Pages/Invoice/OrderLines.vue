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
                                        <!-- Always try to show thumbnail first -->
                                        <img
                                            v-if="!thumb.imageLoadError"
                                            :src="getThumbnailUrl(job.id, thumb.index)"
                                            :alt="'Thumbnail ' + (thumbIndex + 1)"
                                            class="thumbnail-image"
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

                            <!-- Action buttons -->
                            <div class="file-action-buttons">
                                <button
                                    @click="triggerFilesInput(job.id)"
                                    class="file-action-btn primary"
                                    title="Upload files"
                                >
                                    <i class="fa fa-upload"></i> Upload Files
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
                        <div v-if="job.machinePrint">
                            {{ $t('machineP') }}: <span class="bold"> {{ job.machinePrint }}</span>
                        </div>
                    </td>
                    <td>
                        <div v-if="job.machineCut">
                            {{ $t('machineC') }}: <span class="bold"> {{ job.machineCut }}</span>
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
            selectedThumbnail: null // Store selected thumbnail info
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
        console.log('Component mounted, loading thumbnails for jobs:', this.jobsToDisplay.map(j => j.id));
        
        // Load thumbnails for all existing jobs that have original files
        this.jobsToDisplay.forEach(job => {
            if (job.id && job.originalFile && job.originalFile.length > 0) {
                console.log('Loading thumbnails for job', job.id, 'with', job.originalFile.length, 'files');
                this.loadJobThumbnails(job.id);
            }
        });
    },

    watch: {
        jobs: {
            handler(newJobs) {
                // Watch for job prop changes
            },
            deep: true
        },
        updatedJobs: {
            handler(newUpdatedJobs) {
                // Watch for updatedJobs prop changes
            },
            deep: true
        }
    },

    methods: {
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
            console.log('Getting thumbnails for job', job.id, {
                loadedThumbnails: this.jobThumbnails[job.id],
                originalFiles: job.originalFile
            });
            
            // If we have loaded thumbnails for this job, return them
            if (this.jobThumbnails[job.id] && this.jobThumbnails[job.id].length > 0) {
                console.log('Returning loaded thumbnails:', this.jobThumbnails[job.id]);
                return this.jobThumbnails[job.id];
            }
            
            // If job has original files but no thumbnails loaded, create placeholder thumbnails
            if (job.originalFile && job.originalFile.length > 0) {
                console.log('Creating placeholder thumbnails for job', job.id);
                return job.originalFile.map((file, index) => ({
                    type: 'file',
                    thumbnailUrl: null, // No thumbnail available yet
                    originalFile: file,
                    index: index,
                    filename: this.getFileName(file),
                    imageLoadError: false // Initialize error state
                }));
            }
            
            // No files at all
            console.log('No files found for job', job.id);
            return [];
        },

        async loadJobThumbnails(jobId) {
            try {
                console.log('Loading thumbnails for job', jobId);
                const response = await axios.get(`/jobs/${jobId}/thumbnails`);
                console.log('Thumbnails response for job', jobId, response.data);
                
                if (response.data.thumbnails && response.data.thumbnails.length > 0) {
                    // Initialize imageLoadError for each thumbnail
                    this.jobThumbnails[jobId] = response.data.thumbnails.map(thumb => ({
                        ...thumb,
                        imageLoadError: false
                    }));
                    console.log('Set thumbnails for job', jobId, ':', this.jobThumbnails[jobId]);
                } else {
                    console.log('No thumbnails found for job', jobId);
                    this.jobThumbnails[jobId] = [];
                }
                
                // Force reactivity update
                this.$forceUpdate();
            } catch (error) {
                console.error('Failed to load thumbnails for job', jobId, error);
                if (error.response) {
                    console.error('Server response:', error.response.data);
                }
                this.jobThumbnails[jobId] = [];
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
            // Use backend route to serve thumbnail with authentication
            const url = route('jobs.viewThumbnail', { jobId: jobId, fileIndex: fileIndex });
            console.log('Generated thumbnail URL:', url, 'for job:', jobId, 'fileIndex:', fileIndex);
            return url;
        },

        handleThumbnailError(thumb, event) {
            const imgElement = event.target;
            console.error('❌ Thumbnail failed to load!', {
                url: imgElement.src,
                thumb: thumb,
                error: event,
                status: imgElement.complete ? 'loaded but error' : 'failed to load'
            });
            thumb.imageLoadError = true;
            this.$forceUpdate();
        },

        handleThumbnailLoad(thumb) {
            console.log('Thumbnail loaded successfully for:', thumb.filename);
            thumb.imageLoadError = false;
        },



        async refreshThumbnails(jobId) {
            console.log('Manually refreshing thumbnails for job', jobId);
            const toast = useToast();
            
            try {
                // Clear existing thumbnails
                this.jobThumbnails[jobId] = [];
                this.$forceUpdate();
                
                // Reload thumbnails
                await this.loadJobThumbnails(jobId);
                
                toast.success('Thumbnails refreshed');
            } catch (error) {
                console.error('Failed to refresh thumbnails:', error);
                toast.error('Failed to refresh thumbnails');
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
            console.log('Starting multiple file upload for job', job.id, 'with', files.length, 'files');

            try {
                const formData = new FormData();
                
                // Add all files to FormData
                files.forEach((file, index) => {
                    console.log(`Adding file ${index}:`, file.name, file.size, 'bytes');
                    formData.append(`files[${index}]`, file);
                });

                console.log('Sending upload request to:', `/jobs/${job.id}/upload-multiple-files`);

                const response = await axios.post(
                    `/jobs/${job.id}/upload-multiple-files`, 
                    formData, 
                    {
                        headers: {
                            'Content-Type': 'multipart/form-data',
                        },
                    }
                );

                console.log('Upload response:', response.data);

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

                console.log('Updated job data:', updatedJob);
                if (response.data.dimensions) {
                    console.log('📐 Total dimensions calculated:', {
                        totalWidth: response.data.dimensions.total_width_mm,
                        totalHeight: response.data.dimensions.total_height_mm,
                        totalArea: response.data.dimensions.total_area_m2,
                        filesCount: response.data.dimensions.files_count
                    });
                    console.log('📄 Individual file dimensions:', response.data.dimensions.individual_files);
                }
                console.log('Thumbnails from upload response:', response.data.thumbnails);

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

                // Use thumbnails from upload response if available
                if (response.data.thumbnails && response.data.thumbnails.length > 0) {
                    console.log('Using thumbnails from upload response');
                    this.jobThumbnails[job.id] = response.data.thumbnails;
                    this.$forceUpdate();
                } else {
                    // Clear existing thumbnails first and reload
                    console.log('No thumbnails in response, reloading separately');
                    this.jobThumbnails[job.id] = [];
                    this.$forceUpdate();

                    // Wait a moment for backend processing, then reload thumbnails
                    setTimeout(async () => {
                        console.log('Loading thumbnails for job', job.id);
                        await this.loadJobThumbnails(job.id);
                    }, 2000);
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
                    
                    console.log('Cumulative dimensions updated:', {
                        filesUploaded: files.length,
                        totalJobWidth: totalWidth,
                        totalJobHeight: totalHeight,
                        totalJobArea: totalM2,
                        totalFilesInJob: fileCount,
                        individualFiles: response.data.dimensions.individual_files
                    });
                    
                    toast.success(`${files.length} files uploaded successfully. Job total: ${totalWidth.toFixed(2)}×${totalHeight.toFixed(2)}mm (${totalM2.toFixed(4)}m²) from ${fileCount} files`);
                } else {
                    toast.success(`${files.length} files uploaded successfully`);
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

        resetFile(job) {
            const updatedJob = {
                ...job,
                file: 'placeholder.jpeg',
                width: null,
                height: null,
            };

            // Update the job in the reactive array
            const index = this.jobs.findIndex(j => j.id === job.id);
            if (index !== -1) {
                // Replace the job object in the jobs array
                this.jobs.splice(index, 1, updatedJob);
            }

            // Emit an event to notify parent components, if necessary
            this.$emit('job-updated', updatedJob);
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

                // Only include catalog info if we're updating quantity (needed for price recalculation)
                if (this.editingField === 'quantity') {
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

        confirmDelete(job) {
            this.jobToDelete = job;
            this.showDeleteConfirm = true;
        },

        deleteJob() {
            if (this.jobToDelete) {
                // Emit event to parent component to handle the deletion
                this.$emit('delete-job', this.jobToDelete.id);

                // Reset the confirmation dialog
                this.showDeleteConfirm = false;
                this.jobToDelete = null;
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
            console.log('Removing file at index', fileIndex, 'from job', job.id);
            
            try {
                const response = await axios.delete(`/jobs/${job.id}/remove-original-file`, {
                    data: { file_index: fileIndex }
                });

                console.log('Remove file response:', response.data);

                // Update job with new original files
                const updatedJob = {
                    ...job,
                    originalFile: response.data.originalFiles || []
                };

                console.log('Updated job after file removal:', updatedJob);

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

                // Wait a moment, then reload thumbnails
                setTimeout(async () => {
                    console.log('Reloading thumbnails after file removal for job', job.id);
                    await this.loadJobThumbnails(job.id);
                }, 500);

                // Emit updates
                this.$emit('jobs-updated', [updatedJob]);
                this.$emit('job-updated', updatedJob);

                toast.success('File removed successfully');
            } catch (error) {
                console.error('Error removing file:', error);
                toast.error('Failed to remove file');
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

        &:hover {
            background-color: darken($light-green, 10%);
            transform: translateY(-1px);
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

        &:hover {
            background-color: #5a6268;
            transform: translateY(-1px);
        }
    }
}

// Removed unused styles for original files dropdown

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
</style>

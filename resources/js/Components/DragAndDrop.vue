<template>
    <div class="FileBox light-gray">
        <TabsWrapper>
            <Tab title="Mockup" icon="mdi-image" class="text">
                <div class="mockup-upload-container">
                    <!-- Left Side: Upload Area -->
                    <div class="mockup-upload-section">
                        <div
                            class="mockup-drop-zone"
                            v-if="!uploadingMockup"
                            @dragover.prevent
                            @drop="handleMockupDrop"
                            @click="browseForMockup"
                        >
                            <div v-if="!mockupFile && !mockupFileName" class="drop-zone-empty">
                                <i class="mdi mdi-cloud-upload" style="font-size: 48px; margin-bottom: 10px;"></i>
                                <p class="drop-zone-text">{{ $t('dragAndDrop') || 'Drag and drop mockup here' }}</p>
                                <p class="drop-zone-hint">or click to browse</p>
                            </div>
                            <div v-else class="drop-zone-filled">
                                <i class="mdi mdi-file-image" style="font-size: 32px; margin-bottom: 8px;"></i>
                                <p class="file-name">{{ mockupFile ? mockupFile.name : (mockupFileName || 'Mockup file') }}</p>
                                <p class="file-size" v-if="mockupFile">{{ formatFileSize(mockupFile.size) }}</p>
                            </div>
                            <input
                                type="file"
                                accept="image/jpeg,image/jpg,image/png"
                                @change="handleMockupBrowse"
                                style="display: none;"
                                ref="mockupInput"
                            />
                        </div>
                        <div
                            v-else
                            class="mockup-uploading"
                        >
                            <img src="/images/Loading.gif" alt="Loading" class="loading-gif" style="width: 40px; height: 40px"/>
                            <p class="uploading-text">Uploading...</p>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="mockup-actions">
                            <button @click="browseForMockup" class="mockup-btn mockup-btn-primary">
                                <i class="mdi mdi-folder-open"></i> {{ $t('browse') || 'Browse' }}
                            </button>
                            <button 
                                v-if="mockupFile || mockupFileName" 
                                @click="removeMockup" 
                                class="mockup-btn mockup-btn-danger"
                            >
                                <i class="mdi mdi-delete"></i> Remove
                            </button>
                        </div>
                        
                        <div class="mockup-hint">
                            <i class="mdi mdi-information"></i> Supported: JPG, PNG (Max 5MB)
                        </div>
                    </div>
                    
                    <!-- Right Side: Small Preview -->
                    <div class="mockup-preview-section" v-if="mockupPreview || mockupFileName">
                        <div class="preview-header">
                            <i class="mdi mdi-eye"></i> Preview
                        </div>
                        <div class="mockup-preview-small">
                            <img 
                                :src="mockupPreview || (mockupFileName ? `/mockups/${mockupFileName}` : '')" 
                                alt="Mockup preview" 
                                class="preview-thumbnail"
                            />
                        </div>
                    </div>
                </div>
            </Tab>
            <Tab title="Art" icon="mdi-file-image">
                <div class="flex pb-1 pt-2 justify-center gap-4">
                    <!-- Drop Area -->
                    <div class="drop-zone-container">
                        <div
                            class="drop-zone text-white"
                            v-if="!uploading"
                            @dragover.prevent
                            @drop="handleFileDrop"
                        >
                            <p>{{ $t('dragAndDrop') }}</p>
                            <input
                                type="file"
                                accept=".pdf, .tiff, .tif"
                                @change="handleFileBrowse"
                                style="display: none;"
                                ref="fileInput"
                                multiple
                            />
                        </div>
                        <div
                            v-else
                            class="uploading-animation flex flex-col items-center justify-center"
                        >
                            <p class="uploading-text text-white">Uploading...</p>
                            <img src="/images/Loading.gif" alt="Loading" class="loading-gif" style="width: 35px; height: 35px"/>
                        </div>
                    </div>

                    <!-- Job Count and Browse Button -->
                    <div class="ultra-light-gray p-1 rounded d-flex">
                        <div class="text-white pb-10">
                            {{ fileJobs.length ? `${fileJobs.length} jobs selected` : 'No files selected..' }}
                        </div>
                        <button @click="browseForFiles" class="bg-white rounded text-black py-2 px-5">
                            {{ $t('browse') }}
                        </button>
                    </div>
                </div>

                <!-- File Size and Details -->
                <div class="fbox ultra-light-gray rounded flex justify-between text-center m-6">
                    <div class="text-white flex-wrap align-center d-flex p-2">
                        Jobs: {{ fileJobs.length }} | Total Size: {{ calculateTotalFileSize() }}MB<br>
                    </div>
                    <div class="position-relative p-2">
                        <button
                            @mouseover="showPopover = true"
                            @mouseout="showPopover = false"
                            class="bg-white rounded text-black py-2 px-5"
                        >
                            Details
                        </button>
                        <div v-if="showPopover" class="popover">
                            <div v-for="job in fileJobs" :key="job.id || job.file.name">
                                <strong>{{ job.file || 'Unknown' }}</strong> ({{ jobSize(job.fileSize) }}MB)
                                <br>
                                <small class="text-green-300">
                                    ✓ R2 Cloud Storage ({{ job.original_files_count || 1 }} original files)
                                </small>
                                <br><br>
                            </div>
                        </div>
                    </div>
                </div>
            </Tab>
        </TabsWrapper>
    </div>
</template>

<script>
import axios from 'axios';
import { useToast } from 'vue-toastification';
import Tab from "@/Components/tabs/Tab.vue";
import TabsWrapper from "@/Components/tabs/TabsWrapper.vue";

export default {
    name: "DragAndDrop",
    components: { TabsWrapper, Tab },

    props: {
        invoiceComment: String,
        initialJobs: Array,
        initialMockup: String,
    },

    data() {
        return {
            jobs: Array.isArray(this.initialJobs) ? [...this.initialJobs] : [],
            showPopover: false,
            localComment: this.invoiceComment,
            uploading: false, // Track uploading state
            uploadingMockup: false, // Track mockup uploading state
            mockupFile: null, // Store selected mockup file
            mockupFileName: this.initialMockup || null, // Store uploaded mockup filename
            mockupPreview: null, // Store preview URL for mockup
        };
    },

    computed: {
        // Filter jobs that have a file
        fileJobs() {
            return this.jobs.filter(job => job.file && job.file !== 'placeholder.jpeg');
        },
    },

    watch: {
        invoiceComment(newVal) {
            this.localComment = newVal;
        },
        jobs: {
            handler(newJobs) {
                this.$emit('update:jobs', newJobs);
            },
            deep: true,
        },
    },

    emits: ['update:jobs', 'mockupUpdated'],

    methods: {
        async createJob(imageFile) {
            this.uploading = true; // Start uploading
            try {
                const formData = new FormData();
                formData.append('file', imageFile);

                const response = await axios.post('/jobs', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    },
                });

                this.uploading = false; // Finish uploading
                return response.data.job;
            } catch (error) {
                this.uploading = false; // Handle failure
                throw error;
            }
        },

        handleFileDrop(event) {
            const toast = useToast();

            event.preventDefault();
            const files = event.dataTransfer.files;

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                if (file.type === 'application/pdf') {
                    this.convertPDFToImage(file);
                } else {
                    toast.error('Only PDF files are supported.');
                }
            }
        },

        handleFileBrowse(event) {
            const toast = useToast();
            const files = event.target.files;

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                if (file.type === 'application/pdf') {
                    this.convertPDFToImage(file);
                } else {
                    toast.error('Only PDF files are supported.');
                }
            }
        },

        browseForFiles() {
            this.$refs.fileInput.click();
        },

        calculateTotalFileSize() {
            return this.fileJobs.reduce((size, job) => size + job.fileSize, 0).toFixed(2);
        },

        jobSize(size) {
            const megabyteToByte = 1048576;
            return (size / megabyteToByte).toFixed(2);
        },

        async convertPDFToImage(file) {
            try {
                const tempJob = await this.createJob(file);

                // Job dimensions are now calculated during creation
                this.jobs.push({
                    file: tempJob.file,
                    width: tempJob.width || 0,
                    height: tempJob.height || 0,
                    id: tempJob.id,
                    fileSize: file.size,
                    originalFile: tempJob.originalFile || [], // R2 storage structure
                    has_original_files: true, // Always true for R2 storage
                    original_files_count: tempJob.original_files_count || 1,
                    storage_type: 'R2'
                });

                const toast = useToast();
                toast.success(`File uploaded successfully to R2 cloud storage.`);
            } catch (error) {
                console.error('Error creating job:', error);
                const toast = useToast();
                toast.error('Failed to create job from uploaded file');
            }
        },

        updateComment() {
            this.$emit('commentUpdated', this.localComment);
        },

        handleCatalogJobs(catalogJobs) {
            catalogJobs.forEach(job => {
                this.jobs.push({
                    ...job,
                    isPlaceholder: true, // Mark jobs created from catalog
                });
            });
        },

        async handlePlaceholderFileDrop(event, job) {
            const file = event.target.files[0];
            if (!file) return;

            try {
                const tempJob = await this.createJob(file);

                const index = this.jobs.findIndex(j => j.id === job.id);
                if (index !== -1) {
                    // Update job with new file and dimensions
                    const updatedJob = {
                        ...job,
                        file: tempJob.file,
                        width: tempJob.width || job.width || 0,
                        height: tempJob.height || job.height || 0,
                        originalFile: tempJob.originalFile || [], // R2 storage structure
                        has_original_files: true, // Always true for R2 storage
                        original_files_count: tempJob.original_files_count || 1,
                        storage_type: 'R2',
                        isPlaceholder: false,
                        needsFile: false,
                    };

                    this.jobs[index] = updatedJob;

                    // Recalculate cost if this is a catalog-based job
                    if (updatedJob.catalog_item_id) {
                        await this.recalculateJobCost(updatedJob);
                    }
                }

                const toast = useToast();
                toast.success('File uploaded successfully to R2 cloud storage.');
            } catch (error) {
                const toast = useToast();
                toast.error('Failed to upload file');
                console.error('Error uploading placeholder file:', error);
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
                const index = this.jobs.findIndex(j => j.id === job.id);
                if (index !== -1) {
                    this.jobs[index] = {
                        ...this.jobs[index],
                        price: response.data.price,
                        salePrice: response.data.salePrice
                    };
                }

                // Force reactivity update
                this.$forceUpdate();
            } catch (error) {
                console.error('Error recalculating job cost:', error);
            }
        },

        // Mockup upload methods
        browseForMockup() {
            this.$refs.mockupInput.click();
        },

        handleMockupDrop(event) {
            event.preventDefault();
            const files = event.dataTransfer.files;
            if (files.length > 0) {
                this.validateAndSetMockup(files[0]);
            }
        },

        handleMockupBrowse(event) {
            const files = event.target.files;
            if (files.length > 0) {
                this.validateAndSetMockup(files[0]);
            }
        },

        validateAndSetMockup(file) {
            const toast = useToast();
            const maxSize = 5 * 1024 * 1024; // 5MB in bytes
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];

            // Validate file type
            if (!allowedTypes.includes(file.type)) {
                toast.error('Only JPG and PNG images are supported.');
                return;
            }

            // Validate file size
            if (file.size > maxSize) {
                toast.error('File size must be less than 5MB.');
                return;
            }

            this.mockupFile = file;
            
            // Create preview URL
            if (this.mockupPreview) {
                URL.revokeObjectURL(this.mockupPreview);
            }
            this.mockupPreview = URL.createObjectURL(file);
            
            this.$emit('mockupUpdated', file);
        },

        removeMockup() {
            // Clean up preview URL
            if (this.mockupPreview) {
                URL.revokeObjectURL(this.mockupPreview);
                this.mockupPreview = null;
            }
            this.mockupFile = null;
            this.mockupFileName = null;
            if (this.$refs.mockupInput) {
                this.$refs.mockupInput.value = '';
            }
            this.$emit('mockupUpdated', null);
        },

        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        },
    },
    beforeUnmount() {
        // Clean up preview URL when component is destroyed
        if (this.mockupPreview) {
            URL.revokeObjectURL(this.mockupPreview);
        }
    },
};
</script>
<style scoped lang="scss">
.dark-gray{
    background-color: $dark-gray;
}
.ultra-light-gray{
    background-color: $ultra-light-gray;
}
.fbox{
    margin-bottom: 45.5px;
}
.FileBox{
    min-height: 90%;
    max-height: 600px;
    border-radius: 5px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    margin: 0 auto;
}

.drop-zone {
    display: flex;
    border: 3px dashed #ccc;
    border-radius: 10px;
    align-items: center;
    font-size: 18px;
    justify-content: center;
    width: 320px;
    height: 150px;
    background-color: $ultra-light-gray;
}

.uploading-animation {
    width: 320px;
    height: 150px;
    background-color: #ccc;
    border-radius: 10px;
    display: flex;
    justify-content: center;
    align-items: center;
}

.d-flex {
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;
}

.text {
    display: flex;
    flex-direction: column;
    padding: 10px;
}

.hint {
    font-size: 12px;
    color: #cbd5e1;
    margin-top: 4px;
}

.char-count {
    font-weight: 500;
}

.char-count--warning {
    color: #f59e0b;
}

.notes-textarea {
    min-height: 120px;
    width: 100%;
    border-radius: 6px;
    border: 1px solid #4b5563;
    padding: 8px 10px;
    resize: vertical;
    font-family: inherit;
}

.popover {
    border: 1px solid #ccc;
    padding: 10px;
    right: 1px;
    position: absolute;
    background-color: white;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
}

.placeholder-container {
    position: relative;
    width: 150px;
    height: 150px;
    margin: 10px;
}

.placeholder-image {
    width: 100%;
    height: 100%;
    border: 2px dashed $gray;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: $ultra-light-gray;
    cursor: pointer;
    position: relative;
    overflow: hidden;

    span {
        color: $white;
        text-align: center;
    }
}

.placeholder-input {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
}

.placeholder-info {
    text-align: center;
    color: $white;
    margin-top: 5px;
}

/* Mockup Upload Redesign Styles */
.mockup-upload-container {
    display: flex;
    gap: 20px;
    padding: 20px;
    min-height: 300px;
}

.mockup-upload-section {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.mockup-drop-zone {
    flex: 1;
    min-height: 200px;
    border: 2px dashed #6b7280;
    border-radius: 12px;
    background-color: rgba(55, 65, 81, 0.5);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    padding: 20px;
    
    &:hover {
        border-color: #9ca3af;
        background-color: rgba(55, 65, 81, 0.7);
    }
}

.drop-zone-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    color: #9ca3af;
    text-align: center;
}

.drop-zone-text {
    font-size: 16px;
    font-weight: 500;
    margin: 8px 0 4px 0;
    color: #e5e7eb;
}

.drop-zone-hint {
    font-size: 12px;
    color: #6b7280;
    margin: 0;
}

.drop-zone-filled {
    display: flex;
    flex-direction: column;
    align-items: center;
    color: #e5e7eb;
    text-align: center;
}

.file-name {
    font-size: 14px;
    font-weight: 500;
    margin: 8px 0 4px 0;
    color: #e5e7eb;
    word-break: break-word;
    max-width: 100%;
}

.file-size {
    font-size: 12px;
    color: #9ca3af;
    margin: 0;
}

.mockup-uploading {
    flex: 1;
    min-height: 200px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    border-radius: 12px;
    background-color: rgba(55, 65, 81, 0.5);
}

.uploading-text {
    color: #e5e7eb;
    font-size: 14px;
}

.mockup-actions {
    display: flex;
    gap: 10px;
    justify-content: center;
}

.mockup-btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s ease;
    
    i {
        font-size: 18px;
    }
}

.mockup-btn-primary {
    background-color: #ffffff;
    color: #1f2937;
    
    &:hover {
        background-color: #f3f4f6;
    }
}

.mockup-btn-danger {
    background-color: #ef4444;
    color: #ffffff;
    
    &:hover {
        background-color: #dc2626;
    }
}

.mockup-hint {
    text-align: center;
    color: #9ca3af;
    font-size: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    
    i {
        font-size: 16px;
    }
}

.mockup-preview-section {
    width: 280px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.preview-header {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #e5e7eb;
    font-size: 14px;
    font-weight: 600;
    padding: 8px 12px;
    background-color: rgba(55, 65, 81, 0.8);
    border-radius: 6px;
    
    i {
        font-size: 18px;
    }
}

.mockup-preview-small {
    flex: 1;
    border: 2px solid #4b5563;
    border-radius: 8px;
    overflow: hidden;
    background-color: rgba(55, 65, 81, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 200px;
    max-height: 200px;
}

.preview-thumbnail {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    display: block;
}
</style>

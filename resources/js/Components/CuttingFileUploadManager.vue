<template>
    <div class="cutting-file-upload-manager">
        <!-- Cutting Files Display -->
        <div class="cutting-files-display">
            <!-- Cutting Files Grid -->
            <div v-if="files && files.length > 0" class="cutting-files-grid">
                <div
                    v-for="(cuttingFile, cuttingIndex) in files"
                    :key="`cutting-file-${cuttingIndex}-${cuttingFile}`"
                    class="cutting-file-wrapper"
                    :class="{ 
                        'uploading': isUploading, 
                        'deleting': deletingFiles.has(cuttingIndex),
                        'disabled': isLocked
                    }"
                >
                    <div class="cutting-file-item">
                    <!-- Remove button -->
                    <button 
                        @click="removeFile(cuttingIndex)" 
                        class="cutting-file-remove-btn"
                        :class="{ 
                            'removing': deletingFiles.has(cuttingIndex),
                            'disabled': isLocked || isUploading
                        }"
                        :disabled="isLocked || isUploading || deletingFiles.has(cuttingIndex)"
                        :title="getRemoveButtonTitle(cuttingIndex)"
                    >
                        <i v-if="deletingFiles.has(cuttingIndex)" class="fa fa-spinner fa-spin"></i>
                        <i v-else class="fa fa-times"></i>
                    </button>
                    
                    <!-- File preview with thumbnail support -->
                    <div class="cutting-file-preview">
                        <!-- Show thumbnails if available, otherwise show icon -->
                        <div v-if="hasThumbnail(cuttingIndex)" class="cutting-thumbnail-container">
                            <!-- Carousel for multiple thumbnails -->
                            <div v-if="getFileThumbnails(cuttingIndex).length > 1" class="cutting-thumbnail-carousel">
                                <div class="cutting-carousel-container">
                                    <img
                                        :src="getCurrentThumbnail(cuttingIndex).url"
                                        class="cutting-carousel-image"
                                        :alt="`Page ${getCurrentPageNumber(cuttingIndex)} of cutting file ${cuttingIndex + 1}`"
                                        @error="onThumbnailError(cuttingIndex)"
                                    />
                                    <!-- Page indicator -->
                                    <div class="cutting-page-indicator">
                                        {{ getCurrentPageNumber(cuttingIndex) }}/{{ getFileThumbnails(cuttingIndex).length }}
                                    </div>
                                </div>
                            </div>
                            <!-- Single thumbnail -->
                            <div v-else class="cutting-single-thumbnail">
                                <img
                                    :src="getFileThumbnails(cuttingIndex)[0].url"
                                    class="cutting-thumbnail-image"
                                    :alt="`Page 1 of cutting file ${cuttingIndex + 1}`"
                                    @error="onThumbnailError(cuttingIndex)"
                                />
                            </div>
                        </div>
                        <div v-else-if="thumbnailsLoading" class="cutting-thumbnail-loading">
                            <i class="fa fa-spinner fa-spin"></i>
                            <span>Loading...</span>
                        </div>
                        <div v-else @click="openFile(cuttingIndex)" class="cutting-file-icon">
                            <i :class="getCuttingFileIcon(getFileExtension(cuttingFile))"></i>
                            <span class="file-type">{{ getFileExtension(cuttingFile).toUpperCase() }}</span>
                            <div class="preview-hint">Click to view</div>
                        </div>
                        
                        <!-- Zoom icon overlay -->
                        <div v-if="hasThumbnail(cuttingIndex)" @click.stop="openPreview(cuttingIndex)" class="cutting-zoom-overlay">
                            <i class="fa fa-search-plus"></i>
                        </div>
                    </div>



                    <div class="cutting-file-label">{{ cuttingIndex + 1 }}</div>
                </div>
                
                <!-- External carousel controls (under thumbnail) -->
                <div v-if="hasThumbnail(cuttingIndex) && getFileThumbnails(cuttingIndex).length > 1" class="cutting-external-carousel-controls">
                    <button 
                        @click.stop="previousThumbnail(cuttingIndex)"
                        class="cutting-external-carousel-btn cutting-external-carousel-prev"
                        :disabled="getFileThumbnails(cuttingIndex).length <= 1 || getCurrentThumbnailIndex(cuttingIndex) === 0"
                    >
                        <i class="fa fa-chevron-left"></i>
                    </button>
                    <button 
                        @click.stop="nextThumbnail(cuttingIndex)"
                        class="cutting-external-carousel-btn cutting-external-carousel-next"
                        :disabled="getFileThumbnails(cuttingIndex).length <= 1 || getCurrentThumbnailIndex(cuttingIndex) === getFileThumbnails(cuttingIndex).length - 1"
                    >
                        <i class="fa fa-chevron-right"></i>
                    </button>
                </div>
                </div>
            </div>

            <!-- Placeholder when no cutting files -->
            <div v-else class="cutting-placeholder-upload" :class="{ 'disabled': isLocked, 'uploading': isUploading }">
                <div 
                    class="cutting-placeholder-content" 
                    @click="!isLocked && triggerFileInput()"
                    :class="{ 'disabled': isLocked }"
                >
                    <div v-if="isUploading" class="upload-spinner">
                        <i class="fa fa-spinner fa-spin"></i>
                    </div>
                    <span class="cutting-placeholder-text">
                        {{ getPlaceholderText() }}
                    </span>
                    <div class="cutting-file-types-info">
                        <small>PDF</small>
                    </div>
                </div>
            </div>

            <!-- Cutting Files Upload Progress -->
            <div v-if="isUploading" class="cutting-upload-progress-container">
                <div class="cutting-upload-progress-header">
                    <div class="cutting-upload-status">
                        <div class="cutting-status-indicator" :class="uploadState.state"></div>
                        <span class="cutting-status-text">
                            {{ uploadState.stage.message }}
                        </span>
                    </div>
                    <div class="cutting-upload-percentage">{{ uploadState.progress }}%</div>
                </div>
                <div class="cutting-upload-progress-bar">
                    <div 
                        class="cutting-upload-progress-fill" 
                        :style="{ width: `${uploadState.progress}%` }"
                    ></div>
                    <div class="cutting-upload-progress-glow"></div>
                </div>


            </div>

            <!-- Action buttons -->
            <div class="cutting-file-action-buttons">
                <button
                    @click="triggerFileInput()"
                    class="cutting-file-action-btn primary"
                    :disabled="isLocked"
                    :title="getUploadButtonTitle()"
                >
                    <i class="fa" :class="isUploading ? 'fa-spinner fa-spin' : 'fa-scissors'"></i> 
                    {{ getUploadButtonText() }}
                </button>
            </div>
        </div>

        <!-- Hidden file input for cutting files -->
        <input
            type="file"
            accept=".pdf,.svg,.dxf,.cdr,.ai"
            multiple
            @change="handleFileChange"
            class="file-input"
            :id="inputId"
            :disabled="isLocked"
            style="display: none;"
            ref="fileInput"
        />

        <!-- Preview Modal -->
        <div v-if="showPreviewModal" class="cutting-preview-modal-overlay" @click="closePreview">
            <div class="cutting-preview-modal" @click.stop>
                <div class="cutting-modal-header">
                    <h3>Cutting File {{ previewFileIndex + 1 }} Preview</h3>
                    <button @click="closePreview" class="cutting-modal-close-btn">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                
                <div class="cutting-modal-content">
                    <div class="cutting-modal-carousel">
                        <div class="cutting-modal-image-container">
                            <img
                                :src="getModalCurrentThumbnail().url"
                                class="cutting-modal-image"
                                :alt="`Page ${modalCurrentPage} of cutting file ${previewFileIndex + 1}`"
                                @error="onThumbnailError(previewFileIndex)"
                            />
                            
                            <!-- Modal carousel controls -->
                            <button 
                                v-if="getPreviewThumbnails().length > 1"
                                @click="previousModalPage"
                                class="cutting-modal-carousel-btn cutting-modal-carousel-prev"
                                :disabled="modalCurrentPage === 1"
                            >
                                <i class="fa fa-chevron-left"></i>
                            </button>
                            <button 
                                v-if="getPreviewThumbnails().length > 1"
                                @click="nextModalPage"
                                class="cutting-modal-carousel-btn cutting-modal-carousel-next"
                                :disabled="modalCurrentPage === getPreviewThumbnails().length"
                            >
                                <i class="fa fa-chevron-right"></i>
                            </button>
                        </div>
                        
                        <!-- Page navigation -->
                        <div v-if="getPreviewThumbnails().length > 1" class="cutting-modal-page-navigation">
                            <div class="cutting-page-info">
                                Page {{ modalCurrentPage }} of {{ getPreviewThumbnails().length }}
                            </div>
                            <div class="cutting-page-dots">
                                <button
                                    v-for="(thumbnail, index) in getPreviewThumbnails()"
                                    :key="thumbnail.file_name"
                                    @click="goToModalPage(index + 1)"
                                    class="cutting-page-dot"
                                    :class="{ active: modalCurrentPage === index + 1 }"
                                >
                                    {{ index + 1 }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { useToast } from "vue-toastification";
import { uploadFileInParts } from '@/utils/r2Multipart.js';

export default {
    name: 'CuttingFileUploadManager',
    emits: ['files-updated', 'file-removed', 'upload-started', 'upload-completed', 'upload-failed'],
    props: {
        jobId: {
            type: Number,
            required: true
        },
        files: {
            type: Array,
            default: () => []
        },
        maxFileSize: {
            type: Number,
            default: 100 * 1024 * 1024 // 100MB
        }
    },
    data() {
        return {
            isUploading: false,
            deletingFiles: new Set(),
            uploadState: {
                state: 'idle',
                stage: { message: '' },
                progress: 0
            },
            thumbnails: [], // Array of thumbnail objects
            thumbnailsLoading: false,
            finalCuttingFiles: [], // Store final cutting files list
            showPreviewModal: false,
            previewFileIndex: null,
            modalCurrentPage: 1,
            currentThumbnailIndexes: {} // Track current thumbnail index for each file
        };
    },
    computed: {
        inputId() {
            return `cutting-files-input-${this.jobId}`;
        },
        isLocked() {
            return this.isUploading || this.deletingFiles.size > 0;
        }
    },
    mounted() {
        // Load thumbnails when component mounts
        if (this.files && this.files.length > 0) {
            this.loadThumbnails();
        }
    },
    watch: {
        // Watch for file changes and reload thumbnails
        files: {
            handler(newFiles, oldFiles) {
                if (newFiles && newFiles.length > 0 && 
                    (!oldFiles || newFiles.length !== oldFiles.length)) {
                    // Delay thumbnail loading to allow for generation
                    setTimeout(() => {
                        this.loadThumbnails();
                    }, 1000);
                }
            },
            immediate: false
        }
    },
    methods: {
        triggerFileInput() {
            if (this.isLocked) return;
            this.$refs.fileInput.click();
        },

        async handleFileChange(event) {
            const files = Array.from(event.target.files);
            if (!files.length) return;

            const toast = useToast();

            // Validate files
            const invalidFiles = files.filter(file => file.size > this.maxFileSize);
            if (invalidFiles.length > 0) {
                toast.error(`Some files are too large. Maximum size is ${(this.maxFileSize / 1024 / 1024).toFixed(0)}MB`);
                return;
            }

            this.isUploading = true;
            this.updateUploadState('starting', 'Preparing upload...', 0);
            this.$emit('upload-started');

            try {
                // Use multipart upload for all files (same as original files)
                await this.handleAllFiles(files, toast);

                this.updateUploadState('complete', 'Upload completed', 100);
                
                // Create a response object that matches what the parent expects
                const response = {
                    data: {
                        cuttingFiles: this.finalCuttingFiles,
                        uploadedCount: files.length,
                        message: 'Cutting files uploaded successfully'
                    }
                };
                
                this.$emit('upload-completed', response.data);
                toast.success(`${files.length} cutting file(s) uploaded successfully`);

                // Load thumbnails after successful upload
                setTimeout(() => {
                    this.loadThumbnails();
                }, 2000); // Give time for thumbnail generation

            } catch (error) {
                console.error('Upload error:', error);
                this.updateUploadState('error', 'Upload failed', 0);
                this.$emit('upload-failed', error);
                toast.error(`Upload failed: ${error.message}`);
            } finally {
                this.isUploading = false;
                this.resetUploadState();
                this.finalCuttingFiles = [];
                event.target.value = '';
            }
        },

        async handleAllFiles(files, toast) {
            this.updateUploadState('uploading', 'Processing files...', 10);
            
            let allCuttingFiles = [];

            for (let i = 0; i < files.length; i++) {
                const file = files[i];

                try {
                    const baseProgress = 10 + (i / files.length) * 80;
                    this.updateUploadState('uploading', `Uploading ${file.name}...`, baseProgress);

                    const onProgress = ({ loaded, total, partNumber, totalParts }) => {
                        const percentage = Math.round((loaded / total) * 100);
                        const fileProgress = (percentage / 100) * (80 / files.length);
                        this.updateUploadState('uploading', 
                            `Uploading ${file.name} (part ${partNumber}/${totalParts})`, 
                            baseProgress + fileProgress
                        );
                    };

                    // Use multipart upload for all files with appropriate chunk size
                    const chunkSize = file.size > 50 * 1024 * 1024 ? 10 * 1024 * 1024 : 5 * 1024 * 1024; // 10MB for large, 5MB for smaller
                    
                    const response = await uploadFileInParts({
                        file,
                        jobId: this.jobId,
                        chunkSize,
                        onProgress,
                        uploadType: 'cutting' // Specify this is a cutting file upload
                    });

                    // The multipart completion returns the response, extract cutting files
                    if (response && response.completion && response.completion.cuttingFiles) {
                        allCuttingFiles = response.completion.cuttingFiles;
                    }

                } catch (error) {
                    console.error(`Failed to upload ${file.name}:`, error);
                    throw new Error(`Failed to upload ${file.name}: ${error.message}`);
                }
            }
            
            // Store the final cutting files list for the completion event
            this.finalCuttingFiles = allCuttingFiles;
        },

        async removeFile(fileIndex) {
            if (this.isLocked) return;

            const toast = useToast();
            this.deletingFiles.add(fileIndex);

            try {
                const response = await axios.delete(`/jobs/${this.jobId}/remove-cutting-file`, {
                    data: { fileIndex: fileIndex }
                });

                this.$emit('file-removed', { fileIndex, response: response.data });
                toast.success('Cutting file removed successfully');

            } catch (error) {
                console.error('Error removing cutting file:', error);
                toast.error('Failed to remove cutting file');
            } finally {
                this.deletingFiles.delete(fileIndex);
            }
        },

        openFile(fileIndex) {
            const url = route('jobs.viewCuttingFile', { jobId: this.jobId, fileIndex });
            window.open(url, '_blank');
        },

        openPreview(fileIndex) {
            this.previewFileIndex = fileIndex;
            this.modalCurrentPage = 1;
            this.showPreviewModal = true;
        },

        closePreview() {
            this.showPreviewModal = false;
            this.previewFileIndex = null;
            this.modalCurrentPage = 1;
        },

        // Thumbnail carousel methods
        getCurrentThumbnailIndex(fileIndex) {
            return this.currentThumbnailIndexes[fileIndex] || 0;
        },

        getCurrentThumbnail(fileIndex) {
            const thumbnails = this.getFileThumbnails(fileIndex);
            const currentIndex = this.getCurrentThumbnailIndex(fileIndex);
            return thumbnails[currentIndex] || thumbnails[0];
        },

        getCurrentPageNumber(fileIndex) {
            return this.getCurrentThumbnailIndex(fileIndex) + 1;
        },

        nextThumbnail(fileIndex) {
            const thumbnails = this.getFileThumbnails(fileIndex);
            const currentIndex = this.getCurrentThumbnailIndex(fileIndex);
            if (currentIndex < thumbnails.length - 1) {
                this.currentThumbnailIndexes[fileIndex] = currentIndex + 1;
            }
        },

        previousThumbnail(fileIndex) {
            const currentIndex = this.getCurrentThumbnailIndex(fileIndex);
            if (currentIndex > 0) {
                this.currentThumbnailIndexes[fileIndex] = currentIndex - 1;
            }
        },

        // Modal carousel methods
        getPreviewThumbnails() {
            if (this.previewFileIndex === null) return [];
            return this.getFileThumbnails(this.previewFileIndex);
        },

        getModalCurrentThumbnail() {
            const thumbnails = this.getPreviewThumbnails();
            return thumbnails[this.modalCurrentPage - 1] || thumbnails[0];
        },

        nextModalPage() {
            const thumbnails = this.getPreviewThumbnails();
            if (this.modalCurrentPage < thumbnails.length) {
                this.modalCurrentPage++;
            }
        },

        previousModalPage() {
            if (this.modalCurrentPage > 1) {
                this.modalCurrentPage--;
            }
        },

        goToModalPage(pageNumber) {
            const thumbnails = this.getPreviewThumbnails();
            if (pageNumber >= 1 && pageNumber <= thumbnails.length) {
                this.modalCurrentPage = pageNumber;
            }
        },

        getFileExtension(filename) {
            return filename.split('.').pop() || '';
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

        resetUploadState() {
            this.uploadState = {
                state: 'idle',
                stage: { message: '' },
                progress: 0
            };
        },

        getPlaceholderText() {
            if (this.isUploading) return 'Uploading...';
            if (this.isLocked) return 'Processing...';
            return 'Drop Cutting Files';
        },

        getUploadButtonText() {
            if (this.isUploading) return 'Uploading...';
            return 'Upload Cutting Files';
        },

        getUploadButtonTitle() {
            if (this.isLocked) return 'Upload disabled while processing';
            return 'Upload cutting files';
        },

        getRemoveButtonTitle(fileIndex) {
            if (this.deletingFiles.has(fileIndex)) return 'Removing file...';
            if (this.isLocked) return 'Remove disabled while processing';
            return 'Remove cutting file';
        },

        // Thumbnail methods
        async loadThumbnails() {
            if (this.thumbnailsLoading) return;
            
            this.thumbnailsLoading = true;
            try {
                const response = await axios.get(`/jobs/${this.jobId}/cutting-file-thumbnails`);
                this.thumbnails = response.data.thumbnails || [];
            } catch (error) {
                console.error('Error loading cutting thumbnails:', error);
                this.thumbnails = [];
            } finally {
                this.thumbnailsLoading = false;
            }
        },

        getFileThumbnails(fileIndex) {
            // Find all thumbnails for this file index
            const file = this.files[fileIndex];
            if (!file) return [];
            
            const fileName = this.getFileNameFromPath(file);
            const fileThumbnails = this.thumbnails.filter(t => 
                t && t.file_name && 
                t.file_name.includes(fileName.replace(/\.(pdf|svg|dxf|cdr|ai)$/i, '')) && 
                t.file_name.includes('_' + fileIndex + '_')
            );
            
            // Sort thumbnails by page number (extracted from filename)
            return fileThumbnails.sort((a, b) => {
                const pageA = this.extractPageNumber(a.file_name);
                const pageB = this.extractPageNumber(b.file_name);
                return pageA - pageB;
            });
        },

        extractPageNumber(fileName) {
            // Extract page number from filename like "filename_0_page_1.png"
            const match = fileName.match(/_page_(\d+)\.png$/);
            return match ? parseInt(match[1]) : 0;
        },

        getThumbnailUrl(fileIndex) {
            // Get first thumbnail URL for backward compatibility
            const thumbnails = this.getFileThumbnails(fileIndex);
            return thumbnails.length > 0 ? thumbnails[0].url : null;
        },

        getFileNameFromPath(filePath) {
            // Extract filename from path like "job-cutting-files/123_filename.pdf"
            return filePath.split('/').pop() || filePath;
        },

        hasThumbnail(fileIndex) {
            return this.getFileThumbnails(fileIndex).length > 0;
        },



        onThumbnailError(fileIndex, thumbIndex = null) {
            const errorMsg = thumbIndex !== null 
                ? `Cutting thumbnail failed to load for file ${fileIndex}, page ${thumbIndex + 1}`
                : `Cutting thumbnail failed to load for file ${fileIndex}`;
            console.warn(errorMsg);
            // Could implement retry logic or fallback here
        },

        updateUploadState(state, message, progress) {
            this.uploadState = {
                state,
                stage: { message },
                progress: Math.round(progress)
            };
        },

        resetUploadState() {
            this.uploadState = {
                state: 'idle',
                stage: { message: '' },
                progress: 0
            };
        }
    }
};
</script>

<style scoped lang="scss">
.cutting-file-upload-manager {
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
    gap: 8px;
    max-width: 200px;
    justify-content: center;
}

.cutting-file-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    transition: all 0.3s ease;

    &.uploading {
        animation: pulse 1.5s infinite;
    }
    
    &.deleting {
        animation: shake 0.5s ease-in-out;
        opacity: 0.7;
    }

    &.disabled {
        opacity: 0.5;
        pointer-events: none;
    }
}

.cutting-file-item {
    position: relative;
    width: 60px;
    height: 60px;
    border: 2px solid rgba(255, 255, 255, 0.2);
    border-radius: 6px;
    overflow: hidden;
    transition: all 0.3s ease;
    background-color: rgba(0, 0, 0, 0.1);

    .cutting-file-wrapper:hover:not(.disabled) & {
        border-color: #ff6b35;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }
    
    .cutting-file-wrapper.uploading & {
        border-color: #17a2b8;
    }
    
    .cutting-file-wrapper.deleting & {
        border-color: #dc3545;
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

    &:hover:not(:disabled) {
        background-color: rgba(220, 53, 69, 1);
        transform: scale(1.1);
    }

    &:disabled, &.disabled {
        cursor: not-allowed;
        opacity: 0.6;
    }

    &.removing {
        background-color: rgba(255, 193, 7, 0.9);
        animation: pulse 1.5s infinite;
    }
}

.cutting-file-preview {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.cutting-thumbnail-container {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    border-radius: 4px;
}

.cutting-thumbnail-image {
    width: 100%;
    height: 100%;
    object-fit: contain;
    border-radius: 4px;
    transition: all 0.2s ease;
    background-color: #f8f9fa;
}

.cutting-thumbnail-loading,
.cutting-file-icon {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background-color: #f8f9fa;
    color: #6c757d;
    font-size: 0.6rem;
    border-radius: 4px;
    
    i {
        font-size: 1.2rem;
        margin-bottom: 2px;
    }
    
    span {
        font-size: 0.5rem;
        text-align: center;
    }
}

.cutting-thumbnail-loading {
    color: #17a2b8;
    
    i {
        animation: spin 1s linear infinite;
    }
}

.cutting-file-icon {
    color: #dc3545;
    
    &:hover {
        background-color: #e9ecef;
    }
}

.cutting-file-label {
    position: absolute;
    bottom: 2px;
    left: 2px;
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

// Carousel styles
.cutting-thumbnail-carousel {
    width: 100%;
    height: 100%;
    position: relative;
}

.cutting-carousel-container {
    width: 100%;
    height: 100%;
    position: relative;
    overflow: hidden;
    border-radius: 4px;
}

.cutting-carousel-image {
    width: 100%;
    height: 100%;
    object-fit: contain;
    background-color: #f8f9fa;
    transition: opacity 0.3s ease;
}

.cutting-single-thumbnail {
    width: 100%;
    height: 100%;
}

.cutting-page-indicator {
    position: absolute;
    top: 2px;
    left: 50%;
    transform: translateX(-50%);
    background-color: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 1px 4px;
    border-radius: 8px;
    font-size: 0.5rem;
    font-weight: bold;
    z-index: 5;
}

.cutting-zoom-overlay {
    position: absolute;
    bottom: 2px;
    right: 2px;
    background-color: rgba(0, 0, 0, 0.7);
    color: white;
    border-radius: 50%;
    width: 16px;
    height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.6rem;
    cursor: pointer;
    transition: all 0.2s ease;
    z-index: 10;

    &:hover {
        background-color: rgba(0, 0, 0, 0.9);
        transform: scale(1.1);
    }
}

// External carousel controls
.cutting-external-carousel-controls {
    display: flex;
    gap: 4px;
    justify-content: center;
    align-items: center;
    margin-top: 2px;
    padding: 1px;
}

.cutting-external-carousel-btn {
    background-color: rgba(255, 107, 53, 0.8);
    color: white;
    border: none;
    border-radius: 50%;
    width: 16px;
    height: 16px;
    font-size: 0.6rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;

    &:hover:not(:disabled) {
        background-color: rgba(255, 107, 53, 1);
        transform: scale(1.1);
    }

    &:disabled {
        opacity: 0.3;
        cursor: not-allowed;
        transform: none;
    }

    &.cutting-external-carousel-prev {
        margin-right: 2px;
    }

    &.cutting-external-carousel-next {
        margin-left: 2px;
    }
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
    transition: all 0.3s ease;

    &:hover:not(.disabled) {
        border-color: #f7931e;
        background-color: rgba(255, 107, 53, 0.2);
        transform: translateY(-2px);
    }

    &.uploading {
        border-color: #f7931e;
        background-color: rgba(247, 147, 30, 0.1);
        animation: pulse 1.5s infinite;
    }

    &.disabled {
        opacity: 0.6;
        pointer-events: none;
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

    &.disabled {
        cursor: not-allowed;
        pointer-events: none;
    }
}

.upload-spinner {
    font-size: 1.2rem;
    color: #f7931e;
    margin-bottom: 4px;
}

.cutting-placeholder-text {
    font-size: 0.5rem;
    color: #ff6b35;
    text-align: center;
    font-weight: bold;
    margin-bottom: 2px;
}

.cutting-file-types-info {
    font-size: 0.45rem;
    color: rgba(255, 107, 53, 0.8);
    text-align: center;
    line-height: 1.2;
}

.cutting-upload-progress-container {
    width: 100%;
    margin: 6px 0;
    padding: 8px;
    background: linear-gradient(135deg, rgba(255, 107, 53, 0.15), rgba(247, 147, 30, 0.08));
    border-radius: 6px;
    border: 1px solid rgba(255, 107, 53, 0.15);
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
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
        box-shadow: 0 0 6px rgba(255, 193, 7, 0.4);
    }
    &.uploading {
        background-color: #17a2b8;
        box-shadow: 0 0 6px rgba(23, 162, 184, 0.4);
    }
    &.processing {
        background-color: #007bff;
        box-shadow: 0 0 6px rgba(0, 123, 255, 0.4);
    }
    &.complete {
        background-color: #28a745;
        box-shadow: 0 0 6px rgba(40, 167, 69, 0.4);
    }
    &.error {
        background-color: #dc3545;
        box-shadow: 0 0 6px rgba(220, 53, 69, 0.4);
    }
}

.cutting-status-text {
    font-size: 0.65rem;
    color: white;
    font-weight: 500;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}

.cutting-upload-percentage {
    font-size: 0.7rem;
    color: white;
    font-weight: bold;
    background: rgba(255, 107, 53, 0.08);
    padding: 2px 6px;
    border-radius: 8px;
    min-width: 32px;
    text-align: center;
}

.cutting-upload-progress-bar {
    width: 100%;
    height: 6px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 3px;
    overflow: hidden;
    position: relative;
    margin-bottom: 8px;
}

.cutting-upload-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #ff6b35, #f7931e);
    border-radius: 3px;
    transition: width 0.3s ease;
    position: relative;

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

.cutting-upload-progress-glow {
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

@keyframes pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.7; transform: scale(1.05); }
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-2px); }
    75% { transform: translateX(2px); }
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

// Preview Modal Styles
.cutting-preview-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    backdrop-filter: blur(4px);
}

.cutting-preview-modal {
    background: white;
    border-radius: 12px;
    max-width: 90vw;
    max-height: 90vh;
    width: 800px;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    overflow: hidden;
}

.cutting-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid #e9ecef;
    background-color: #f8f9fa;

    h3 {
        margin: 0;
        font-size: 1.2rem;
        color: #333;
    }
}

.cutting-modal-close-btn {
    background: none;
    border: none;
    font-size: 1.2rem;
    cursor: pointer;
    color: #666;
    padding: 4px;
    border-radius: 4px;
    transition: all 0.2s ease;

    &:hover {
        background-color: #e9ecef;
        color: #333;
    }
}

.cutting-modal-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 0;
}

.cutting-modal-carousel {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 0;
}

.cutting-modal-image-container {
    flex: 1;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f8f9fa;
    min-height: 400px;
}

.cutting-modal-image {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    border-radius: 4px;
}

.cutting-modal-carousel-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background-color: rgba(0, 0, 0, 0.7);
    color: white;
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    font-size: 1rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    z-index: 10;

    &:hover:not(:disabled) {
        background-color: rgba(0, 0, 0, 0.9);
        transform: translateY(-50%) scale(1.1);
    }

    &:disabled {
        opacity: 0.3;
        cursor: not-allowed;
    }

    &.cutting-modal-carousel-prev {
        left: 20px;
    }

    &.cutting-modal-carousel-next {
        right: 20px;
    }
}

.cutting-modal-page-navigation {
    padding: 16px 20px;
    border-top: 1px solid #e9ecef;
    background-color: #f8f9fa;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}

.cutting-page-info {
    font-size: 0.9rem;
    color: #666;
    font-weight: 500;
}

.cutting-page-dots {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    max-height: 60px;
    overflow-y: auto;
}

.cutting-page-dot {
    background-color: #e9ecef;
    color: #666;
    border: none;
    border-radius: 4px;
    padding: 4px 8px;
    font-size: 0.8rem;
    cursor: pointer;
    transition: all 0.2s ease;
    min-width: 28px;

    &:hover {
        background-color: #dee2e6;
        color: #333;
    }

    &.active {
        background-color: #ff6b35;
        color: white;
    }
}
</style>

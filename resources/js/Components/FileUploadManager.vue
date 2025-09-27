<template>
    <div class="file-upload-manager">
        <!-- File Display Container -->
        <div class="file-display-container">
            <!-- Thumbnails Grid -->
            <div v-if="files && files.length > 0" class="thumbnails-grid">
                <div
                    v-for="(file, fileIndex) in files"
                    :key="file"
                    class="thumbnail-wrapper"
                    :class="{ 
                        'uploading': isUploading, 
                        'deleting': deletingFiles.has(fileIndex),
                        'disabled': isLocked
                    }"
                >
                    <div class="thumbnail-item">
                    <!-- Remove button -->
                    <button
                        @click="removeFile(fileIndex)"
                        class="thumbnail-remove-btn"
                        :class="{ 
                            'removing': deletingFiles.has(fileIndex),
                            'disabled': isLocked || isUploading
                        }"
                        :disabled="isLocked || isUploading || deletingFiles.has(fileIndex)"
                        :title="getRemoveButtonTitle(fileIndex)"
                    >
                        <i v-if="deletingFiles.has(fileIndex)" class="fa fa-spinner fa-spin"></i>
                        <i v-else class="fa fa-times"></i>
                    </button>

                    <!-- PDF preview -->
                    <div class="thumbnail-preview">
                        <!-- Show thumbnails if available, otherwise show loading or fallback -->
                        <div v-if="hasThumbnail(fileIndex)" class="thumbnail-container">
                            <!-- Carousel for multiple thumbnails -->
                            <div v-if="getThumbnailsForFile(fileIndex).length > 1" class="thumbnail-carousel">
                                <div class="carousel-container">
                                    <img
                                        :src="getCurrentThumbnail(fileIndex).url"
                                        class="carousel-image"
                                        :alt="`Page ${getCurrentPageNumber(fileIndex)} of file ${fileIndex + 1}`"
                                        @error="onThumbnailError(fileIndex)"
                                    />
                                    <!-- Page indicator -->
                                    <div class="page-indicator">
                                        {{ getCurrentPageNumber(fileIndex) }}/{{ getThumbnailsForFile(fileIndex).length }}
                                    </div>
                                </div>
                            </div>
                            <!-- Single thumbnail -->
                            <div v-else class="single-thumbnail">
                                <img
                                    :src="getThumbnailsForFile(fileIndex)[0].url"
                                    class="thumbnail-image"
                                    :alt="`Page 1 of file ${fileIndex + 1}`"
                                    @error="onThumbnailError(fileIndex)"
                                />
                            </div>
                        </div>
                        <div v-else-if="thumbnailsLoading" class="thumbnail-loading">
                            <i class="fa fa-spinner fa-spin"></i>
                            <span>Loading...</span>
                        </div>
                        <div v-else class="thumbnail-fallback">
                            <i class="fa fa-file-pdf-o"></i>
                            <span>PDF</span>
                        </div>
                        
                        <!-- Zoom icon overlay -->
                        <div v-if="hasThumbnail(fileIndex)" @click.stop="openPreview(fileIndex)" class="zoom-overlay">
                            <i class="fa fa-search-plus"></i>
                        </div>
                    </div>

                        <div class="thumbnail-label">{{ fileIndex + 1 }}</div>
                    </div>
                    
                    <!-- External carousel controls (under thumbnail) -->
                    <div v-if="hasThumbnail(fileIndex) && getThumbnailsForFile(fileIndex).length > 1" class="external-carousel-controls">
                        <button 
                            @click.stop="previousThumbnail(fileIndex)"
                            class="external-carousel-btn external-carousel-prev"
                            :disabled="getThumbnailsForFile(fileIndex).length <= 1 || getCurrentThumbnailIndex(fileIndex) === 0"
                        >
                            <i class="fa fa-chevron-left"></i>
                        </button>
                        <button 
                            @click.stop="nextThumbnail(fileIndex)"
                            class="external-carousel-btn external-carousel-next"
                            :disabled="getThumbnailsForFile(fileIndex).length <= 1 || getCurrentThumbnailIndex(fileIndex) === getThumbnailsForFile(fileIndex).length - 1"
                        >
                            <i class="fa fa-chevron-right"></i>
                        </button>
                    </div>

                </div>
            </div>

            <!-- Placeholder when no files -->
            <div v-else class="placeholder-upload" :class="{ 'disabled': isLocked, 'uploading': isUploading }">
                <div 
                    class="placeholder-content" 
                    @click="!isLocked && triggerFileInput()"
                    :class="{ 'disabled': isLocked }"
                >
                    <div v-if="isUploading" class="upload-spinner">
                        <i class="fa fa-spinner fa-spin"></i>
                    </div>
                    <span class="placeholder-text">
                        {{ getPlaceholderText() }}
                    </span>
                </div>
            </div>

            <!-- Upload Progress -->
            <div v-if="isUploading" class="upload-progress-container">
                <div class="upload-progress-header">
                    <div class="upload-status">
                        <div class="status-indicator" :class="uploadState.state"></div>
                        <span class="status-text">
                            {{ uploadState.stage.message }}
                        </span>
                    </div>
                    <div class="upload-percentage">{{ uploadState.progress }}%</div>
                </div>
                <div class="upload-progress-bar">
                    <div 
                        class="upload-progress-fill" 
                        :style="{ width: `${uploadState.progress}%` }"
                    ></div>
                    <div class="upload-progress-glow"></div>
                </div>
            </div>

            <!-- Action buttons -->
            <div class="file-action-buttons">
                <button
                    @click="triggerFileInput()"
                    class="file-action-btn primary"
                    :disabled="isLocked"
                    :title="getUploadButtonTitle()"
                >
                    <i class="fa" :class="isUploading ? 'fa-spinner fa-spin' : 'fa-upload'"></i> 
                    {{ getUploadButtonText() }}
                </button>
                <button
                    v-if="files && files.length > 0"
                    @click="refreshThumbnails()"
                    class="file-action-btn secondary"
                    :disabled="isLocked"
                    title="Refresh thumbnails"
                >
                    <i class="fa fa-refresh"></i>
                </button>
            </div>
        </div>

        <!-- Hidden file input -->
        <input
            type="file"
            :accept="acceptedTypes"
            multiple
            @change="handleFileChange"
            class="file-input"
            :id="inputId"
            :disabled="isLocked"
            style="display: none;"
            ref="fileInput"
        />

        <!-- Preview Modal -->
        <div v-if="showPreviewModal" class="preview-modal-overlay" @click="closePreview">
            <div class="preview-modal" @click.stop>
                <div class="modal-header">
                    <h3>File {{ previewFileIndex + 1 }} Preview</h3>
                    <button @click="closePreview" class="modal-close-btn">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                
                <div class="modal-content">
                    <div class="modal-carousel">
                        <div class="modal-image-container">
                            <img
                                :src="getModalCurrentThumbnail().url"
                                class="modal-image"
                                :alt="`Page ${modalCurrentPage} of file ${previewFileIndex + 1}`"
                                @error="onThumbnailError(previewFileIndex)"
                            />
                            
                            <!-- Modal carousel controls -->
                            <button 
                                v-if="getPreviewThumbnails().length > 1"
                                @click="previousModalPage"
                                class="modal-carousel-btn modal-carousel-prev"
                                :disabled="modalCurrentPage === 1"
                            >
                                <i class="fa fa-chevron-left"></i>
                            </button>
                            <button 
                                v-if="getPreviewThumbnails().length > 1"
                                @click="nextModalPage"
                                class="modal-carousel-btn modal-carousel-next"
                                :disabled="modalCurrentPage === getPreviewThumbnails().length"
                            >
                                <i class="fa fa-chevron-right"></i>
                            </button>
                        </div>
                        
                        <!-- Page navigation -->
                        <div v-if="getPreviewThumbnails().length > 1" class="modal-page-navigation">
                            <div class="page-info">
                                Page {{ modalCurrentPage }} of {{ getPreviewThumbnails().length }}
                            </div>
                            <div class="page-dots">
                                <button
                                    v-for="(thumbnail, index) in getPreviewThumbnails()"
                                    :key="thumbnail.file_name"
                                    @click="goToModalPage(index + 1)"
                                    class="page-dot"
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
import { uploadManager } from '@/utils/UploadManager.js';
import { uploadFileInParts } from '@/utils/r2Multipart.js';

export default {
    name: 'FileUploadManager',
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
        uploadType: {
            type: String,
            default: 'general' // 'general' or 'cutting'
        },
        acceptedTypes: {
            type: String,
            default: '.pdf'
        },
        maxFileSize: {
            type: Number,
            default: 300 * 1024 * 1024 // 300MB
        },
        chunkThreshold: {
            type: Number,
            default: 1 * 1024 * 1024 // 15MB
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
            showPreviewModal: false,
            previewFileIndex: null,
            modalCurrentPage: 1,
            currentThumbnailIndexes: {} // Track current thumbnail index for each file
        };
    },
    computed: {
        inputId() {
            return `file-input-${this.uploadType}-${this.jobId}`;
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
                // Use multipart upload for all files (consistent approach)
                await this.handleAllFiles(files, toast);

                this.updateUploadState('complete', 'Upload completed', 100);
                this.$emit('upload-completed');
                toast.success(`${files.length} file(s) uploaded successfully`);

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
                event.target.value = '';
            }
        },

        async handleAllFiles(files, toast) {
            this.updateUploadState('uploading', 'Processing files...', 10);

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
                    
                    await uploadFileInParts({
                        file,
                        jobId: this.jobId,
                        chunkSize,
                        onProgress
                    });

                } catch (error) {
                    console.error(`Failed to upload ${file.name}:`, error);
                    throw new Error(`Failed to upload ${file.name}: ${error.message}`);
                }
            }
        },

        async removeFile(fileIndex) {
            if (this.isLocked) return;

            const toast = useToast();
            this.deletingFiles.add(fileIndex);

            try {
                const filePath = this.files[fileIndex];
                const response = await axios.delete(`/jobs/${this.jobId}/remove-original-file`, {
                    data: { file_index: fileIndex, original_file: filePath }
                });

                this.$emit('file-removed', { fileIndex, response: response.data });
                toast.success('File removed successfully');

            } catch (error) {
                console.error('Error removing file:', error);
                toast.error('Failed to remove file');
            } finally {
                this.deletingFiles.delete(fileIndex);
            }
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
            const thumbnails = this.getThumbnailsForFile(fileIndex);
            const currentIndex = this.getCurrentThumbnailIndex(fileIndex);
            return thumbnails[currentIndex] || thumbnails[0];
        },

        getCurrentPageNumber(fileIndex) {
            return this.getCurrentThumbnailIndex(fileIndex) + 1;
        },

        nextThumbnail(fileIndex) {
            const thumbnails = this.getThumbnailsForFile(fileIndex);
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
            return this.getThumbnailsForFile(this.previewFileIndex);
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

        async refreshThumbnails() {
            await this.loadThumbnails();
            this.$emit('refresh-thumbnails');
        },

        async loadThumbnails() {
            if (this.thumbnailsLoading) return;
            
            this.thumbnailsLoading = true;
            try {
                const response = await axios.get(`/jobs/${this.jobId}/thumbnails`);
                this.thumbnails = response.data.thumbnails || [];
            } catch (error) {
                console.error('Error loading thumbnails:', error);
                this.thumbnails = [];
            } finally {
                this.thumbnailsLoading = false;
            }
        },

        getThumbnailUrl(fileIndex) {
            // Find first thumbnail for this file index (page 1)
            const file = this.files[fileIndex];
            if (!file) return null;
            
            const fileName = this.getFileNameFromPath(file);
            const thumbnail = this.thumbnails.find(t => 
                t && t.file_name && 
                t.file_name.includes(fileName.replace('.pdf', '')) && 
                t.file_name.includes('_page_1')
            );
            
            return thumbnail ? thumbnail.url : null;
        },

        getThumbnailsForFile(fileIndex) {
            // Get all thumbnails for this file (all pages)
            const file = this.files[fileIndex];
            if (!file) return [];
            
            const fileName = this.getFileNameFromPath(file);
            const fileThumbnails = this.thumbnails.filter(t => 
                t && t.file_name && t.file_name.includes(fileName.replace('.pdf', ''))
            );
            
            // Sort by page number
            return fileThumbnails.sort((a, b) => {
                const pageA = parseInt(a.file_name.match(/_page_(\d+)/)?.[1] || '0');
                const pageB = parseInt(b.file_name.match(/_page_(\d+)/)?.[1] || '0');
                return pageA - pageB;
            });
        },

        getFileNameFromPath(filePath) {
            // Extract filename from path like "job-originals/123_filename.pdf"
            return filePath.split('/').pop() || filePath;
        },

        hasThumbnail(fileIndex) {
            return this.getThumbnailUrl(fileIndex) !== null;
        },

        onThumbnailError(fileIndex) {
            console.warn(`Thumbnail failed to load for file ${fileIndex}`);
            // Could implement retry logic or fallback here
        },

        getPdfUrl(fileIndex) {
            const url = route('jobs.viewOriginalFile', { jobId: this.jobId, fileIndex });
            const filePath = this.files[fileIndex] || '';
            const stamp = encodeURIComponent(filePath);
            return `${url}?v=${stamp}`;
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
        },

        getPlaceholderText() {
            if (this.isUploading) return 'Uploading...';
            if (this.isLocked) return 'Processing...';
            return this.uploadType === 'cutting' ? 'Drop Cutting Files' : 'Drop Files';
        },

        getUploadButtonText() {
            if (this.isUploading) return 'Uploading...';
            return this.uploadType === 'cutting' ? 'Upload Cutting Files' : 'Upload Files';
        },

        getUploadButtonTitle() {
            if (this.isLocked) return 'Upload disabled while processing';
            return `Upload ${this.uploadType} files`;
        },

        getRemoveButtonTitle(fileIndex) {
            if (this.deletingFiles.has(fileIndex)) return 'Removing file...';
            if (this.isLocked) return 'Remove disabled while processing';
            return 'Remove file';
        }
    }
};
</script>

<style scoped lang="scss">
.file-upload-manager {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    min-width: 150px;
}

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
    gap: 8px;
    max-width: 200px;
    justify-content: center;
}

.thumbnail-wrapper {
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

.thumbnail-item {
    position: relative;
    width: 60px;
    height: 60px;
    border: 2px solid rgba(255, 255, 255, 0.2);
    border-radius: 6px;
    overflow: hidden;
    transition: all 0.3s ease;
    background-color: rgba(0, 0, 0, 0.1);

    .thumbnail-wrapper:hover:not(.disabled) & {
        border-color: #28a745;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }
    
    .thumbnail-wrapper.uploading & {
        border-color: #17a2b8;
    }
    
    .thumbnail-wrapper.deleting & {
        border-color: #dc3545;
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

.thumbnail-preview {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.thumbnail-image {
    width: 100%;
    height: 100%;
    object-fit: contain;
    border-radius: 4px;
    transition: all 0.2s ease;
    background-color: #f8f9fa;
}

.thumbnail-container {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    border-radius: 4px;
}

.thumbnail-loading,
.thumbnail-fallback {
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

.thumbnail-loading {
    color: #17a2b8;
    
    i {
        animation: spin 1s linear infinite;
    }
}

.thumbnail-fallback {
    color: #dc3545;
    
    &:hover {
        background-color: #e9ecef;
    }
}

.thumbnail-label {
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
.thumbnail-carousel {
    width: 100%;
    height: 100%;
    position: relative;
}

.carousel-container {
    width: 100%;
    height: 100%;
    position: relative;
    overflow: hidden;
    border-radius: 4px;
}

.carousel-image {
    width: 100%;
    height: 100%;
    object-fit: contain;
    background-color: #f8f9fa;
    transition: opacity 0.3s ease;
}

.single-thumbnail {
    width: 100%;
    height: 100%;
}

.carousel-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background-color: rgba(0, 0, 0, 0.6);
    color: white;
    border: none;
    border-radius: 50%;
    width: 16px;
    height: 16px;
    font-size: 0.5rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    z-index: 5;

    &:hover:not(:disabled) {
        background-color: rgba(0, 0, 0, 0.8);
        transform: translateY(-50%) scale(1.1);
    }

    &:disabled {
        opacity: 0.3;
        cursor: not-allowed;
    }

    &.carousel-prev {
        left: 2px;
    }

    &.carousel-next {
        right: 2px;
    }
}

.page-indicator {
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

.zoom-overlay {
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
.external-carousel-controls {
    display: flex;
    gap: 4px;
    justify-content: center;
    align-items: center;
    margin-top: 2px;
    padding: 1px;
}

.external-carousel-btn {
    background-color: rgba(0, 0, 0, 0.7);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.3);
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
        background-color: rgba(0, 0, 0, 0.9);
        transform: scale(1.1);
    }

    &:disabled {
        opacity: 0.3;
        cursor: not-allowed;
    }

    &:active {
        transform: scale(0.95);
    }
}

// Modal styles
.preview-modal-overlay {
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

.preview-modal {
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

.modal-header {
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

.modal-close-btn {
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

.modal-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 0;
}

.modal-carousel {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 0;
}

.modal-image-container {
    flex: 1;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f8f9fa;
    min-height: 400px;
}

.modal-image {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    border-radius: 4px;
}

.modal-carousel-btn {
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

    &.modal-carousel-prev {
        left: 20px;
    }

    &.modal-carousel-next {
        right: 20px;
    }
}

.modal-page-navigation {
    padding: 16px 20px;
    border-top: 1px solid #e9ecef;
    background-color: #f8f9fa;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}

.page-info {
    font-size: 0.9rem;
    color: #666;
    font-weight: 500;
}

.page-dots {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    max-height: 60px;
    overflow-y: auto;
}

.page-dot {
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
        background-color: #28a745;
        color: white;
    }
}


.placeholder-upload {
    width: 60px;
    height: 60px;
    margin: 0 1rem;
    border: 2px dashed #ccc;
    border-radius: 4px;
    position: relative;
    background-color: #f9f9f9;
    overflow: hidden;
    transition: all 0.3s ease;

    &:hover:not(.disabled) {
        border-color: #28a745;
        background-color: rgba(44, 123, 90, 0.739);

        .placeholder-text {
            color: white;
        }
    }

    &.uploading {
        border-color: #17a2b8;
        background-color: rgba(23, 162, 184, 0.1);
        animation: pulse 1.5s infinite;
    }

    &.disabled {
        opacity: 0.6;
        pointer-events: none;
    }
}

.placeholder-content {
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
    color: #17a2b8;
    margin-bottom: 4px;
}

.placeholder-text {
    font-size: 0.7rem;
    color: #218838;
    text-align: center;
    font-weight: 500;
}

.upload-progress-container {
    width: 100%;
    margin: 6px 0;
    padding: 8px;
    background: linear-gradient(135deg, rgba(0, 0, 0, 0.15), rgba(0, 0, 0, 0.08));
    border-radius: 6px;
    border: 1px solid rgba(255, 255, 255, 0.15);
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
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

.status-text {
    font-size: 0.65rem;
    color: white;
    font-weight: 500;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}

.upload-percentage {
    font-size: 0.7rem;
    color: white;
    font-weight: bold;
    background: rgba(255, 255, 255, 0.08);
    padding: 2px 6px;
    border-radius: 8px;
    min-width: 32px;
    text-align: center;
}

.upload-progress-bar {
    width: 100%;
    height: 6px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 3px;
    overflow: hidden;
    position: relative;
    margin-bottom: 8px;
}

.upload-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #28a745, #20c997);
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
        background-color: #28a745;
        color: white;

        &:hover:not(:disabled) {
            background-color: #218838;
            transform: translateY(-1px);
        }
        
        &:disabled {
            background-color: rgba(40, 167, 69, 0.5);
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
            background-color: rgba(108, 117, 125, 0.5);
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
</style>

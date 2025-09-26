<template>
    <div class="cutting-file-upload-manager">
        <!-- Cutting Files Display -->
        <div class="cutting-files-display">
            <!-- Cutting Files Grid -->
            <div v-if="files && files.length > 0" class="cutting-files-grid">
                <div 
                    v-for="(cuttingFile, cuttingIndex) in files" 
                    :key="cuttingIndex"
                    class="cutting-file-item"
                    :class="{ 
                        'uploading': isUploading, 
                        'deleting': deletingFiles.has(cuttingIndex),
                        'disabled': isLocked
                    }"
                >
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
                    
                    <!-- File icon and click to view -->
                    <div @click="openFile(cuttingIndex)" class="cutting-file-preview">
                        <div class="cutting-file-icon">
                            <i :class="getCuttingFileIcon(getFileExtension(cuttingFile))"></i>
                            <span class="file-type">{{ getFileExtension(cuttingFile).toUpperCase() }}</span>
                            <div class="preview-hint">Click to view</div>
                        </div>
                    </div>

                    <!-- File progress overlay -->
                    <div v-if="fileProgress[cuttingIndex]" class="file-progress-overlay">
                        <div class="file-progress-bar">
                            <div 
                                class="file-progress-fill" 
                                :style="{ width: `${fileProgress[cuttingIndex]}%` }"
                            ></div>
                        </div>
                        <div class="file-progress-text">{{ fileProgress[cuttingIndex] }}%</div>
                    </div>

                    <div class="cutting-file-label">{{ cuttingIndex + 1 }}</div>
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
                        <small>PDF, SVG, DXF, CDR, AI</small>
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

                <!-- Individual file progress -->
                <div v-if="uploadingFiles.length > 0" class="individual-files-progress">
                    <div v-for="(fileInfo, index) in uploadingFiles" :key="index" class="file-progress-item">
                        <span class="file-name">{{ fileInfo.name }}</span>
                        <div class="file-progress-mini">
                            <div class="file-progress-mini-fill" :style="{ width: `${fileInfo.progress}%` }"></div>
                        </div>
                        <span class="file-progress-percent">{{ fileInfo.progress }}%</span>
                    </div>
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
    </div>
</template>

<script>
import { useToast } from "vue-toastification";
import { uploadManager } from '@/utils/UploadManager.js';

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
            fileProgress: {},
            uploadingFiles: [],
            uploadState: {
                state: 'idle',
                stage: { message: '' },
                progress: 0
            }
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
            this.uploadingFiles = files.map(file => ({
                name: file.name,
                progress: 0,
                size: file.size
            }));

            this.$emit('upload-started');

            try {
                const uploadConfig = {
                    uploadType: 'cutting',
                    endpoint: `/jobs/${this.jobId}/upload-cutting-files`,
                    progressEndpoint: `/jobs/${this.jobId}/cutting-upload-progress`
                };

                const callbacks = {
                    onStateChange: (uploadState) => {
                        this.uploadState = { ...uploadState };
                    },
                    onProgress: (progressData) => {
                        // Update individual file progress if available
                        if (progressData.files) {
                            progressData.files.forEach((fileData, index) => {
                                const fileIndex = this.uploadingFiles.findIndex(f => f.name === fileData.name);
                                if (fileIndex !== -1) {
                                    this.uploadingFiles[fileIndex].progress = fileData.progress || 0;
                                }
                            });
                        }
                    },
                    onError: (error) => {
                        console.error('Cutting files upload error:', error);
                    }
                };

                const response = await uploadManager.startUpload(this.jobId, files, uploadConfig, callbacks);
                
                this.$emit('upload-completed', response.data);
                toast.success(`${files.length} cutting file(s) uploaded successfully`);

            } catch (error) {
                console.error('Error uploading cutting files:', error);
                this.$emit('upload-failed', error);
                
                if (error.response) {
                    toast.error(`Failed to upload cutting files: ${error.response.data.details || error.response.data.error}`);
                } else {
                    toast.error('Failed to upload cutting files: Network error');
                }
            } finally {
                this.isUploading = false;
                this.uploadingFiles = [];
                this.fileProgress = {};
                this.resetUploadState();
                event.target.value = '';
            }
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
    transition: all 0.3s ease;
    background-color: rgba(0, 0, 0, 0.1);

    &:hover:not(.disabled) {
        border-color: #ff6b35;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    &.uploading {
        border-color: #f7931e;
        animation: pulse 1.5s infinite;
    }
    
    &.deleting {
        border-color: #dc3545;
        animation: shake 0.5s ease-in-out;
        opacity: 0.7;
    }

    &.disabled {
        opacity: 0.5;
        pointer-events: none;
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
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;

    &:hover::after {
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

.file-progress-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 2px;
    border-radius: 0 0 4px 4px;
}

.file-progress-bar {
    height: 4px;
    background: rgba(255, 255, 255, 0.3);
    border-radius: 2px;
    overflow: hidden;
    margin-bottom: 2px;
}

.file-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #ff6b35, #f7931e);
    border-radius: 2px;
    transition: width 0.3s ease;
}

.file-progress-text {
    font-size: 0.5rem;
    text-align: center;
    font-weight: bold;
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

.individual-files-progress {
    margin-top: 8px;
    max-height: 120px;
    overflow-y: auto;
}

.file-progress-item {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 4px;
    font-size: 0.65rem;
    color: white;

    &:last-child {
        margin-bottom: 0;
    }
}

.file-name {
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    min-width: 0;
}

.file-progress-mini {
    width: 60px;
    height: 4px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 2px;
    overflow: hidden;
}

.file-progress-mini-fill {
    height: 100%;
    background: linear-gradient(90deg, #ff6b35, #f7931e);
    border-radius: 2px;
    transition: width 0.3s ease;
}

.file-progress-percent {
    width: 35px;
    text-align: right;
    font-weight: bold;
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
</style>

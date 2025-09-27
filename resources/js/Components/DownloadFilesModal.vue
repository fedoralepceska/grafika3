<template>
    <div v-if="visible" class="download-modal-overlay" @click="closeModal" @keydown.esc="closeModal">
        <div class="download-modal" @click.stop>
            <div class="modal-header">
                <h3>Download Files</h3>
                <button @click="closeModal" class="modal-close-btn">
                    <i class="fa fa-times"></i>
                </button>
            </div>
            
            <div class="modal-content">
                <div class="download-options">
                    <h4>Select what you want to download:</h4>
                    
                    <div class="checkbox-group">
                        <label class="checkbox-item">
                            <input 
                                type="checkbox" 
                                v-model="downloadOptions.jobOriginalThumbnails"
                                :disabled="!hasJobOriginalThumbnails"
                            >
                            <span class="checkbox-label">
                                <i class="fa fa-image"></i>
                                Job Original File Thumbnails
                                <span v-if="!hasJobOriginalThumbnails" class="unavailable">(Not available)</span>
                            </span>
                        </label>
                        
                        <label class="checkbox-item">
                            <input 
                                type="checkbox" 
                                v-model="downloadOptions.cuttingThumbnails"
                                :disabled="!hasCuttingThumbnails"
                            >
                            <span class="checkbox-label">
                                <i class="fa fa-scissors"></i>
                                Cutting File Thumbnails
                                <span v-if="!hasCuttingThumbnails" class="unavailable">(Not available)</span>
                            </span>
                        </label>
                        <label class="checkbox-item">
                            <input 
                                type="checkbox" 
                                v-model="downloadOptions.jobOriginalFiles"
                                :disabled="!hasJobOriginalFiles"
                            >
                            <span class="checkbox-label">
                                <i class="fa fa-file-pdf-o"></i>
                                Job Original Files
                                <span v-if="!hasJobOriginalFiles" class="unavailable">(Not available)</span>
                            </span>
                        </label>
                        
                        <label class="checkbox-item">
                            <input 
                                type="checkbox" 
                                v-model="downloadOptions.cuttingFiles"
                                :disabled="!hasCuttingFiles"
                            >
                            <span class="checkbox-label">
                                <i class="fa fa-file-code-o"></i>
                                Cutting Files
                                <span v-if="!hasCuttingFiles" class="unavailable">(Not available)</span>
                            </span>
                        </label>
                    </div>
                </div>
                
                <div class="download-summary" v-if="hasSelectedOptions">
                    <h4>Download Summary:</h4>
                    <ul>
                        <li v-if="downloadOptions.jobOriginalThumbnails">
                            Job Original Thumbnails ({{ getJobThumbnailCount() }} files)
                        </li>
                        <li v-if="downloadOptions.cuttingThumbnails">
                            Cutting Thumbnails ({{ getCuttingThumbnailCount() }} files)
                        </li>
                        <li v-if="downloadOptions.jobOriginalFiles">
                            Job Original Files ({{ getJobOriginalFileCount() }} files)
                        </li>
                        <li v-if="downloadOptions.cuttingFiles">
                            Cutting Files ({{ getCuttingFileCount() }} files)
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="modal-footer">
                <button @click="closeModal" class="btn btn-secondary">
                    Cancel
                </button>
                <button 
                    @click="startDownload" 
                    class="btn btn-primary"
                    :disabled="!hasSelectedOptions || isDownloading"
                >
                    <i v-if="isDownloading" class="fa fa-spinner fa-spin"></i>
                    <i v-else class="fa fa-download"></i>
                    {{ isDownloading ? 'Downloading...' : 'Download' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import { useToast } from "vue-toastification";
import JSZip from 'jszip';

export default {
    name: 'DownloadFilesModal',
    props: {
        visible: {
            type: Boolean,
            default: false
        },
        invoice: {
            type: Object,
            required: true
        }
    },
    emits: ['close'],
    data() {
        return {
            downloadOptions: {
                jobOriginalThumbnails: true,
                cuttingThumbnails: false,
                jobOriginalFiles: true,
                cuttingFiles: false
            },
            isDownloading: false
        };
    },
    computed: {
        hasJobOriginalThumbnails() {
            return this.invoice.jobs && this.invoice.jobs.some(job => 
                job.dimensions_breakdown && job.dimensions_breakdown.length > 0
            );
        },
        
        hasCuttingThumbnails() {
            return this.invoice.jobs && this.invoice.jobs.some(job => 
                job.cuttingFiles && job.cuttingFiles.length > 0
            );
        },
        
        hasJobOriginalFiles() {
            return this.invoice.jobs && this.invoice.jobs.some(job => 
                job.originalFile && job.originalFile.length > 0
            );
        },
        
        hasCuttingFiles() {
            return this.invoice.jobs && this.invoice.jobs.some(job => 
                job.cuttingFiles && job.cuttingFiles.length > 0
            );
        },
        
        hasSelectedOptions() {
            return Object.values(this.downloadOptions).some(option => option);
        }
    },
    mounted() {
        // Add escape key listener
        document.addEventListener('keydown', this.handleEscapeKey);
    },
    beforeUnmount() {
        // Clean up event listeners
        document.removeEventListener('keydown', this.handleEscapeKey);
    },
    methods: {
        closeModal() {
            this.$emit('close');
        },
        
        handleEscapeKey(event) {
            if (event.key === 'Escape' && this.visible) {
                this.closeModal();
            }
        },
        
        getJobThumbnailCount() {
            let count = 0;
            this.invoice.jobs.forEach(job => {
                if (job.dimensions_breakdown) {
                    job.dimensions_breakdown.forEach(fileData => {
                        if (fileData.page_dimensions) {
                            count += fileData.page_dimensions.length;
                        }
                    });
                }
            });
            return count;
        },
        
        getCuttingThumbnailCount() {
            let count = 0;
            this.invoice.jobs.forEach(job => {
                if (job.cuttingFiles) {
                    count += job.cuttingFiles.length;
                }
            });
            return count;
        },
        
        getJobOriginalFileCount() {
            let count = 0;
            this.invoice.jobs.forEach(job => {
                if (job.originalFile) {
                    count += job.originalFile.length;
                }
            });
            return count;
        },
        
        getCuttingFileCount() {
            let count = 0;
            this.invoice.jobs.forEach(job => {
                if (job.cuttingFiles) {
                    count += job.cuttingFiles.length;
                }
            });
            return count;
        },
        
        async startDownload() {
            if (!this.hasSelectedOptions) return;
            
            this.isDownloading = true;
            const toast = useToast();
            
            try {
                const totalFiles = this.getTotalFileCount();
                toast.info(`Preparing download of ${totalFiles} files...`);
                
                // Create a new ZIP instance
                const zip = new JSZip();
                let addedFiles = 0;
                
                // Download files and add them to ZIP
                for (const job of this.invoice.jobs) {
                    // Job Original Thumbnails
                    if (this.downloadOptions.jobOriginalThumbnails && job.dimensions_breakdown) {
                        for (let fileIndex = 0; fileIndex < job.dimensions_breakdown.length; fileIndex++) {
                            const fileData = job.dimensions_breakdown[fileIndex];
                            if (fileData.page_dimensions) {
                                for (let pageIndex = 0; pageIndex < fileData.page_dimensions.length; pageIndex++) {
                                    try {
                                        const url = route('jobs.viewThumbnail', { 
                                            jobId: job.id, 
                                            fileIndex: fileIndex, 
                                            page: pageIndex + 1 
                                        });
                                        const blob = await this.fetchFile(url);
                                        const filename = `Job_${job.id}_Thumbnails/Page_${pageIndex + 1}.png`;
                                        zip.file(filename, blob);
                                        addedFiles++;
                                    } catch (error) {
                                        console.warn(`Failed to download thumbnail for job ${job.id}, file ${fileIndex}, page ${pageIndex + 1}:`, error);
                                    }
                                }
                            }
                        }
                    }
                    
                    // Cutting Thumbnails
                    if (this.downloadOptions.cuttingThumbnails && job.cuttingFiles) {
                        for (let fileIndex = 0; fileIndex < job.cuttingFiles.length; fileIndex++) {
                            try {
                                const url = route('jobs.viewCuttingFileThumbnail', { 
                                    jobId: job.id, 
                                    fileIndex: fileIndex 
                                });
                                const blob = await this.fetchFile(url);
                                const extension = this.getCuttingFileExtension(job.cuttingFiles[fileIndex]);
                                const filename = `Job_${job.id}_CuttingThumbnails/File_${fileIndex + 1}.${extension}`;
                                zip.file(filename, blob);
                                addedFiles++;
                            } catch (error) {
                                console.warn(`Failed to download cutting thumbnail for job ${job.id}, file ${fileIndex}:`, error);
                            }
                        }
                    }
                    
                    // Job Original Files (from R2)
                    if (this.downloadOptions.jobOriginalFiles && job.originalFile) {
                        for (let fileIndex = 0; fileIndex < job.originalFile.length; fileIndex++) {
                            try {
                                const url = route('jobs.viewOriginalFile', { 
                                    jobId: job.id, 
                                    fileIndex: fileIndex 
                                });
                                const { blob, originalFilename } = await this.fetchFileWithFilename(url);
                                
                                const filename = `Job_${job.id}_Originals/${originalFilename}`;
                                zip.file(filename, blob);
                                addedFiles++;
                            } catch (error) {
                                console.warn(`Failed to download original file for job ${job.id}, file ${fileIndex}:`, error);
                            }
                        }
                    }
                    
                    // Cutting Files (from R2)
                    if (this.downloadOptions.cuttingFiles && job.cuttingFiles) {
                        for (let fileIndex = 0; fileIndex < job.cuttingFiles.length; fileIndex++) {
                            try {
                                const url = route('jobs.viewCuttingFile', { 
                                    jobId: job.id, 
                                    fileIndex: fileIndex 
                                });
                                const { blob, originalFilename } = await this.fetchFileWithFilename(url);
                                
                                const filename = `Job_${job.id}_CuttingFiles/${originalFilename}`;
                                zip.file(filename, blob);
                                addedFiles++;
                            } catch (error) {
                                console.warn(`Failed to download cutting file for job ${job.id}, file ${fileIndex}:`, error);
                            }
                        }
                    }
                }
                
                if (addedFiles === 0) {
                    toast.warning('No files were found to download');
                    return;
                }
                
                toast.info(`Creating ZIP file with ${addedFiles} files...`);
                
                // Generate the ZIP file
                const zipBlob = await zip.generateAsync({ type: 'blob' });
                
                // Download the ZIP file
                const zipFilename = `order_${this.invoice.id}.zip`;
                
                const fileURL = window.URL.createObjectURL(zipBlob);
                const fileLink = document.createElement('a');
                fileLink.href = fileURL;
                fileLink.setAttribute('download', zipFilename);
                document.body.appendChild(fileLink);
                fileLink.click();
                fileLink.remove();
                
                // Clean up the object URL
                setTimeout(() => {
                    window.URL.revokeObjectURL(fileURL);
                }, 1000);
                
                toast.success(`Successfully downloaded ${addedFiles} files in ZIP format`);
                this.closeModal();
                
            } catch (error) {
                console.error('Download error:', error);
                toast.error('Download failed: ' + (error.message || 'Unknown error'));
            } finally {
                this.isDownloading = false;
            }
        },
        
        async fetchFile(url) {
            const response = await axios.get(url, {
                responseType: 'blob',
                timeout: 30000
            });
            return response.data;
        },
        
        async fetchFileWithFilename(url) {
            const response = await axios.get(url, {
                responseType: 'blob',
                timeout: 30000
            });
            
            // Extract original filename from response headers
            const originalFilename = response.headers['x-original-filename'] || 
                                   this.extractFilenameFromContentDisposition(response.headers['content-disposition']) ||
                                   'unknown_file';
            
            return {
                blob: response.data,
                originalFilename: originalFilename
            };
        },
        
        extractFilenameFromContentDisposition(contentDisposition) {
            if (!contentDisposition) return null;
            
            const filenameMatch = contentDisposition.match(/filename="([^"]+)"/);
            return filenameMatch ? filenameMatch[1] : null;
        },
        
        getTotalFileCount() {
            let count = 0;
            
            for (const job of this.invoice.jobs) {
                // Job Original Thumbnails
                if (this.downloadOptions.jobOriginalThumbnails && job.dimensions_breakdown) {
                    job.dimensions_breakdown.forEach(fileData => {
                        if (fileData.page_dimensions) {
                            count += fileData.page_dimensions.length;
                        }
                    });
                }
                
                // Cutting Thumbnails
                if (this.downloadOptions.cuttingThumbnails && job.cuttingFiles) {
                    count += job.cuttingFiles.length;
                }
                
                // Job Original Files
                if (this.downloadOptions.jobOriginalFiles && job.originalFile) {
                    count += job.originalFile.length;
                }
                
                // Cutting Files
                if (this.downloadOptions.cuttingFiles && job.cuttingFiles) {
                    count += job.cuttingFiles.length;
                }
            }
            
            return count;
        },
        
        getCuttingFileExtension(filePath) {
            return filePath.split('.').pop() || 'pdf';
        },
        
    }
};
</script>

<style scoped lang="scss">
.download-modal-overlay {
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
    backdrop-filter: blur(4px);
}

.download-modal {
    background: $dark-gray;
    border-radius: 12px;
    max-width: 600px;
    width: 90%;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    overflow: hidden;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid #e9ecef;
    background-color: $dark-gray;

    h3 {
        margin: 0;
        font-size: 1.5rem;
        color: $white;
        font-weight: 600;
    }
}

.modal-close-btn {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: $white;
    padding: 8px;
    border-radius: 50%;
    transition: all 0.2s ease;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;

    &:hover {
        background-color: $dark-gray;
        color: $red;
    }
}

.modal-content {
    flex: 1;
    padding: 24px;
    overflow-y: auto;
}

.download-options {
    margin-bottom: 24px;

    h4 {
        margin: 0 0 16px 0;
        color: $white;
        font-size: 1.1rem;
        font-weight: 600;
    }
}

.checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.checkbox-item {
    display: flex;
    align-items: center;
    cursor: pointer;
    padding: 12px;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    transition: all 0.2s ease;

    &:hover:not(:has(input:disabled)) {
        background-color: #ffffff25;
        border-color: #007bff;
    }

    input[type="checkbox"] {
        margin-right: 12px;
        width: 18px;
        height: 18px;
        cursor: pointer;
    }

    input[type="checkbox"]:disabled {
        cursor: not-allowed;
        opacity: 0.5;
    }
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 1rem;
    color: $white;
    cursor: pointer;

    i {
        color: #007bff;
        width: 16px;
    }

    .unavailable {
        color: #6c757d;
        font-style: italic;
        font-size: 0.9rem;
    }
}

.download-summary {
    background-color: $background-color;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 16px;

    h4 {
        margin: 0 0 12px 0;
        color: $white;
        font-size: 1rem;
        font-weight: 600;
    }

    ul {
        margin: 0;
        padding-left: 20px;
        color: $white;

        li {
            margin-bottom: 4px;
        }
    }
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding: 20px 24px;
    border-top: 1px solid #e9ecef;
    background-color: $dark-gray;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 1rem;
    font-weight: 500;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 8px;

    &:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    &.btn-secondary {
        background-color: #6c757d;
        color: white;

        &:hover:not(:disabled) {
            background-color: #5a6268;
        }
    }

    &.btn-primary {
        background-color: #007bff;
        color: white;

        &:hover:not(:disabled) {
            background-color: #0056b3;
        }
    }
}
</style>

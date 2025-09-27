/**
 * Professional Upload Manager for handling file uploads with progress tracking
 * Supports both general files and cutting files with detailed progress stages
 */
export class UploadManager {
    constructor() {
        // Upload states for each job
        this.uploadStates = {};
        this.uploadProgress = {};
        this.progressIntervals = {};
        
        // Upload stages with descriptions
        this.stages = {
            idle: { progress: 0, message: 'Ready to upload' },
            preparing: { progress: 5, message: 'Preparing files...' },
            uploading: { progress: 25, message: 'Uploading to server...' },
            processing: { progress: 60, message: 'Converting to WebP/JPG...' },
            finalizing: { progress: 90, message: 'Finalizing upload...' },
            complete: { progress: 100, message: 'Upload complete' },
            error: { progress: 0, message: 'Upload failed' }
        };
        
        // Track active uploads to prevent conflicts
        this.activeUploads = new Set();
        
        // Simple upload coordination - track upload promises per job
        this.uploadPromises = new Map(); // jobId -> Promise
        
        // Global upload queue to ensure jobs upload sequentially
        this.globalUploadQueue = [];
        this.isProcessingGlobalQueue = false;
    }
    
    /**
     * Initialize upload states for a job
     */
    initializeJob(jobId, uploadType = 'general') {
        const key = `${jobId}_${uploadType}`;
        if (!this.uploadStates[key]) {
            this.uploadStates[key] = 'idle';
            this.uploadProgress[key] = 0;
        }
    }
    
    /**
     * Get current upload state for a job
     */
    getUploadState(jobId, uploadType = 'general') {
        const key = `${jobId}_${uploadType}`;
        return {
            state: this.uploadStates[key] || 'idle',
            progress: this.uploadProgress[key] || 0,
            stage: this.stages[this.uploadStates[key]] || this.stages.idle,
            isUploading: this.isUploading(jobId, uploadType),
            isDisabled: this.isDisabled(jobId, uploadType)
        };
    }
    
    /**
     * Check if upload is in progress
     */
    isUploading(jobId, uploadType = 'general') {
        const key = `${jobId}_${uploadType}`;
        const state = this.uploadStates[key];
        return ['preparing', 'uploading', 'processing', 'finalizing'].includes(state);
    }
    
    /**
     * Check if upload controls should be disabled
     */
    isDisabled(jobId, uploadType = 'general') {
        return this.isUploading(jobId, uploadType) || this.activeUploads.has(`${jobId}_${uploadType}`);
    }
    
    /**
     * Start upload process
     */
    async startUpload(jobId, files, uploadConfig, callbacks = {}) {
        const { uploadType = 'general', endpoint, progressEndpoint } = uploadConfig;
        const key = `${jobId}_${uploadType}`;
        
        // Prevent multiple uploads for the same job/type
        if (this.activeUploads.has(key)) {
            throw new Error('Upload already in progress for this job');
        }
        
        this.activeUploads.add(key);
        
        try {
            // Reset state
            this.resetUploadState(jobId, uploadType);
            
            // Set preparing state
            this.updateUploadState(jobId, uploadType, 'preparing');
            callbacks.onStateChange?.(this.getUploadState(jobId, uploadType));
            
            // Prepare form data
            const formData = new FormData();
            files.forEach((file, index) => {
                formData.append(`files[${index}]`, file);
            });
            
            // Start progress polling
            this.startProgressPolling(jobId, uploadType, progressEndpoint, callbacks);
            
            // Set uploading state
            this.updateUploadState(jobId, uploadType, 'uploading');
            callbacks.onStateChange?.(this.getUploadState(jobId, uploadType));
            
            // Perform upload
            const response = await this.performUpload(endpoint, formData, jobId, uploadType, callbacks);
            
            // Stop progress polling
            this.stopProgressPolling(jobId, uploadType);
            
            // Set complete state
            this.updateUploadState(jobId, uploadType, 'complete');
            callbacks.onStateChange?.(this.getUploadState(jobId, uploadType));
            
            // Reset to idle after delay
            setTimeout(() => {
                this.resetUploadState(jobId, uploadType);
                callbacks.onStateChange?.(this.getUploadState(jobId, uploadType));
            }, 2000);
            
            return response;
            
        } catch (error) {
            this.stopProgressPolling(jobId, uploadType);
            this.updateUploadState(jobId, uploadType, 'error');
            callbacks.onError?.(error);
            callbacks.onStateChange?.(this.getUploadState(jobId, uploadType));
            
            // Reset to idle after error delay
            setTimeout(() => {
                this.resetUploadState(jobId, uploadType);
                callbacks.onStateChange?.(this.getUploadState(jobId, uploadType));
            }, 3000);
            
            throw error;
        } finally {
            this.activeUploads.delete(key);
        }
    }
    
    /**
     * Start progress polling
     */
    startProgressPolling(jobId, uploadType, progressEndpoint, callbacks) {
        const key = `${jobId}_${uploadType}`;
        
        this.progressIntervals[key] = setInterval(async () => {
            try {
                const response = await axios.get(progressEndpoint);
                const progressData = response.data;
                
                if (progressData.status && this.stages[progressData.status]) {
                    this.uploadStates[key] = progressData.status;
                    this.uploadProgress[key] = progressData.progress || this.stages[progressData.status].progress;
                    
                    callbacks.onProgress?.(progressData);
                    callbacks.onStateChange?.(this.getUploadState(jobId, uploadType));
                    
                    // Stop polling if complete or error
                    if (['complete', 'error'].includes(progressData.status)) {
                        this.stopProgressPolling(jobId, uploadType);
                    }
                }
            } catch (error) {
                console.error('Failed to get progress:', error);
                // Continue polling on error
            }
        }, 300);
    }
    
    /**
     * Stop progress polling
     */
    stopProgressPolling(jobId, uploadType) {
        const key = `${jobId}_${uploadType}`;
        if (this.progressIntervals[key]) {
            clearInterval(this.progressIntervals[key]);
            delete this.progressIntervals[key];
        }
    }
    
    /**
     * Perform the actual upload
     */
    async performUpload(endpoint, formData, jobId, uploadType, callbacks) {
        const key = `${jobId}_${uploadType}`;
        
        return await axios.post(endpoint, formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
            onUploadProgress: (progressEvent) => {
                if (progressEvent.total && this.uploadStates[key] === 'uploading') {
                    const uploadProgress = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                    // Map upload progress to our stage system (uploading is 25-60%)
                    this.uploadProgress[key] = Math.min(25 + (uploadProgress * 0.35), 60);
                    callbacks.onUploadProgress?.(uploadProgress);
                    callbacks.onStateChange?.(this.getUploadState(jobId, uploadType));
                }
            },
        });
    }
    
    /**
     * Update upload state
     */
    updateUploadState(jobId, uploadType, state, progress = null) {
        const key = `${jobId}_${uploadType}`;
        this.uploadStates[key] = state;
        this.uploadProgress[key] = progress !== null ? progress : this.stages[state]?.progress || 0;
    }
    
    /**
     * Reset upload state to idle
     */
    resetUploadState(jobId, uploadType) {
        const key = `${jobId}_${uploadType}`;
        this.uploadStates[key] = 'idle';
        this.uploadProgress[key] = 0;
        this.stopProgressPolling(jobId, uploadType);
    }
    
    /**
     * Force reset all states for a job (useful when files are removed)
     */
    forceResetJob(jobId) {
        ['general', 'cutting'].forEach(uploadType => {
            const key = `${jobId}_${uploadType}`;
            this.resetUploadState(jobId, uploadType);
            this.activeUploads.delete(key);
        });
    }
    
    /**
     * Get formatted progress message
     */
    getProgressMessage(jobId, uploadType = 'general') {
        const state = this.getUploadState(jobId, uploadType);
        return `${state.stage.message} (${state.progress}%)`;
    }
    
    /**
     * Start an upload and track it with global job coordination
     */
    async startUpload(jobId, uploadType, uploadFunction) {
        const key = `${jobId}_${uploadType}`;
        
        // If there's already an upload for this job, wait for it
        if (this.uploadPromises.has(jobId)) {
            await this.uploadPromises.get(jobId);
        }
        
        // Add to global queue to ensure jobs upload sequentially
        return new Promise((resolve, reject) => {
            const uploadTask = {
                jobId,
                uploadType,
                uploadFunction,
                key,
                resolve,
                reject
            };
            
            this.globalUploadQueue.push(uploadTask);
            
            // Start processing global queue if not already processing
            if (!this.isProcessingGlobalQueue) {
                this.processGlobalQueue();
            }
        });
    }
    
    /**
     * Process the global upload queue
     */
    async processGlobalQueue() {
        if (this.isProcessingGlobalQueue || this.globalUploadQueue.length === 0) {
            return;
        }
        
        this.isProcessingGlobalQueue = true;
        
        while (this.globalUploadQueue.length > 0) {
            const uploadTask = this.globalUploadQueue.shift();
            
            try {
                // Mark as active
                this.activeUploads.add(uploadTask.key);
                
                // Create and store the upload promise
                const uploadPromise = uploadTask.uploadFunction()
                    .finally(() => {
                        // Clean up when done
                        this.activeUploads.delete(uploadTask.key);
                        this.uploadPromises.delete(uploadTask.jobId);
                    });
                
                this.uploadPromises.set(uploadTask.jobId, uploadPromise);
                
                // Wait for this upload to complete
                const result = await uploadPromise;
                uploadTask.resolve(result);
                
            } catch (error) {
                uploadTask.reject(error);
            }
        }
        
        this.isProcessingGlobalQueue = false;
    }
    
    /**
     * Check if any upload is active for a job
     */
    isAnyUploadActive(jobId) {
        return this.activeUploads.has(`${jobId}_general`) || this.activeUploads.has(`${jobId}_cutting`);
    }
    
    /**
     * Clean up resources
     */
    destroy() {
        // Clear all intervals
        Object.values(this.progressIntervals).forEach(interval => clearInterval(interval));
        this.progressIntervals = {};
        this.activeUploads.clear();
        this.uploadStates = {};
        this.uploadProgress = {};
        this.uploadPromises.clear();
        
        // Clear global queue and reject any pending promises
        this.globalUploadQueue.forEach(task => {
            if (task.reject) {
                task.reject(new Error('Upload manager destroyed'));
            }
        });
        this.globalUploadQueue = [];
        this.isProcessingGlobalQueue = false;
    }
}

// Create singleton instance
export const uploadManager = new UploadManager();

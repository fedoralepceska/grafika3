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

        // Track active uploads to prevent conflicts - use jobId_uploadType as key
        this.activeUploads = new Set();

        // Track upload promises per job+uploadType to prevent conflicts
        this.uploadPromises = new Map(); // "jobId_uploadType" -> Promise

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
     * Check if any upload is in progress for a job (any upload type)
     */
    isJobUploading(jobId) {
        return this.isUploading(jobId, 'general') || this.isUploading(jobId, 'cutting');
    }

    /**
     * Check if upload controls should be disabled
     */
    isDisabled(jobId, uploadType = 'general') {
        return this.isUploading(jobId, uploadType) || this.activeUploads.has(`${jobId}_${uploadType}`);
    }

    /**
     * Start upload process with proper job isolation
     */
    async startUpload(jobId, files, uploadConfig, callbacks = {}) {
        const { uploadType = 'general', endpoint, progressEndpoint } = uploadConfig;
        const key = `${jobId}_${uploadType}`;

        // Add to global queue to ensure sequential processing
        return new Promise((resolve, reject) => {
            const uploadTask = {
                jobId,
                uploadType,
                key,
                files,
                uploadConfig,
                callbacks,
                resolve,
                reject,
                uploadFunction: async () => {
                    return await this.executeUpload(jobId, files, uploadConfig, callbacks);
                }
            };

            this.globalUploadQueue.push(uploadTask);

            // Start processing global queue if not already processing
            if (!this.isProcessingGlobalQueue) {
                this.processGlobalQueue();
            }
        });
    }

    /**
     * Execute the actual upload with proper state management
     */
    async executeUpload(jobId, files, uploadConfig, callbacks = {}) {
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

        // Starting progress polling

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
                        // Stopping progress polling - upload complete
                        this.stopProgressPolling(jobId, uploadType);
                    }
                }
            } catch (error) {
                console.error(`Failed to get progress for ${key}:`, error);
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
            // Stopping progress polling
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
     * Update upload state with proper isolation
     */
    updateUploadState(jobId, uploadType, state, progress = null) {
        const key = `${jobId}_${uploadType}`;
        this.uploadStates[key] = state;
        this.uploadProgress[key] = progress !== null ? progress : this.stages[state]?.progress || 0;

        // Upload state updated
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
            // Also clear any pending upload promises
            this.uploadPromises.delete(key);
        });

        // Force reset completed
    }

    /**
     * Get formatted progress message
     */
    getProgressMessage(jobId, uploadType = 'general') {
        const state = this.getUploadState(jobId, uploadType);
        return `${state.stage.message} (${state.progress}%)`;
    }

    /**
     * Start an upload with custom function and track it with global job coordination
     */
    async startCustomUpload(jobId, uploadType, uploadFunction) {
        const key = `${jobId}_${uploadType}`;

        // If there's already an upload for this specific job+uploadType, wait for it
        if (this.uploadPromises.has(key)) {
            await this.uploadPromises.get(key);
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
     * Process the global upload queue with proper job isolation
     */
    async processGlobalQueue() {
        if (this.isProcessingGlobalQueue || this.globalUploadQueue.length === 0) {
            return;
        }

        this.isProcessingGlobalQueue = true;

        while (this.globalUploadQueue.length > 0) {
            const uploadTask = this.globalUploadQueue.shift();

            try {
                // Create and store the upload promise using jobId_uploadType as key
                const uploadPromise = uploadTask.uploadFunction()
                    .finally(() => {
                        // Clean up when done - use the specific key for this job+uploadType
                        this.uploadPromises.delete(uploadTask.key);
                    });

                this.uploadPromises.set(uploadTask.key, uploadPromise);

                // Wait for this upload to complete
                const result = await uploadPromise;
                uploadTask.resolve(result);

            } catch (error) {
                console.error(`Upload failed for ${uploadTask.key}:`, error);
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
     * Check if a specific upload is active
     */
    isUploadActive(jobId, uploadType = 'general') {
        return this.activeUploads.has(`${jobId}_${uploadType}`);
    }

    /**
     * Get upload promise for a specific job+uploadType
     */
    getUploadPromise(jobId, uploadType = 'general') {
        return this.uploadPromises.get(`${jobId}_${uploadType}`);
    }

    /**
     * Get debug information about current upload states
     */
    getDebugInfo() {
        return {
            activeUploads: Array.from(this.activeUploads),
            uploadPromises: Array.from(this.uploadPromises.keys()),
            queueLength: this.globalUploadQueue.length,
            isProcessingQueue: this.isProcessingGlobalQueue,
            uploadStates: { ...this.uploadStates },
            uploadProgress: { ...this.uploadProgress }
        };
    }

    /**
     * Clean up resources
     */
    destroy() {
        // Destroying upload manager

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

        // Upload manager destroyed
    }
}

// Create singleton instance
export const uploadManager = new UploadManager();

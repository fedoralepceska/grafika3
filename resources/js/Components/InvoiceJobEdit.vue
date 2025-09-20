<template>
    <div class="job-edit-container">
        <div class="job-edit-header">
            <h4 class="job-title">{{ localJob.name || 'Unnamed Job' }}</h4>
            <div class="job-actions">
                <button 
                    v-if="!isEditing" 
                    @click="startEditing" 
                    class="btn btn-edit"
                    title="Edit Job"
                >
                    <i class="fas fa-edit"></i>
                </button>
                <button 
                    v-if="isEditing" 
                    @click="saveChanges" 
                    class="btn btn-save"
                    :disabled="isSaving"
                    title="Save Changes"
                >
                    <i class="fas fa-save"></i>
                </button>
                <button 
                    v-if="isEditing" 
                    @click="cancelEditing" 
                    class="btn btn-cancel"
                    title="Cancel"
                >
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <div class="job-details">
            <div class="job-info-grid">
                <div class="info-item">
                    <label>Job Name:</label>
                    <input 
                        v-if="isEditing"
                        v-model="editForm.name"
                        type="text"
                        class="form-input"
                        placeholder="Enter job name"
                    />
                    <span v-else class="info-value">{{ localJob.name || 'N/A' }}</span>
                </div>

                <div class="info-item">
                    <label>Quantity:</label>
                    <input 
                        v-if="isEditing"
                        v-model.number="editForm.quantity"
                        type="number"
                        min="1"
                        class="form-input"
                    />
                    <span v-else class="info-value">{{ localJob.quantity || 0 }}</span>
                </div>

                <div class="info-item">
                    <label>Copies:</label>
                    <input 
                        v-if="isEditing"
                        v-model.number="editForm.copies"
                        type="number"
                        min="1"
                        class="form-input"
                    />
                    <span v-else class="info-value">{{ localJob.copies || 0 }}</span>
                </div>

                <div class="info-item">
                    <label>Sale Price:</label>
                    <input 
                        v-if="isEditing"
                        v-model.number="editForm.salePrice"
                        type="number"
                        step="0.01"
                        min="0"
                        class="form-input"
                    />
                    <span v-else class="info-value">{{ formatPrice(localJob.salePrice) }} ден.</span>
                </div>

                <div class="info-item">
                    <label>Width:</label>
                    <input 
                        v-if="isEditing"
                        v-model.number="editForm.width"
                        type="number"
                        step="0.01"
                        min="0"
                        class="form-input"
                    />
                    <span v-else class="info-value">{{ formatDimension(localJob.width) }} mm</span>
                </div>

                <div class="info-item">
                    <label>Height:</label>
                    <input 
                        v-if="isEditing"
                        v-model.number="editForm.height"
                        type="number"
                        step="0.01"
                        min="0"
                        class="form-input"
                    />
                    <span v-else class="info-value">{{ formatDimension(localJob.height) }} mm</span>
                </div>

                <div class="info-item">
                    <label>Total Area:</label>
                    <span class="info-value">{{ formatArea(localJob.computed_total_area_m2) }} m²</span>
                </div>

            </div>

            <div class="material-info" v-if="getMaterialInfo()">
                <label>Material:</label>
                <span class="info-value">{{ getMaterialInfo() }}</span>
            </div>
        </div>

        <!-- Error Display -->
        <div v-if="error" class="error-message">
            {{ error }}
        </div>
    </div>
</template>

<script>
import axios from 'axios';
import { useToast } from 'vue-toastification';

export default {
    name: 'InvoiceJobEdit',
    props: {
        job: {
            type: Object,
            required: true
        },
        fakturaId: {
            type: Number,
            required: true
        }
    },
    emits: ['job-updated'],
    data() {
        return {
            isEditing: false,
            isSaving: false,
            localJob: { ...this.job },
            editForm: {},
            error: null,
            toast: useToast()
        };
    },
    watch: {
        job: {
            handler(newJob) {
                this.localJob = { ...newJob };
            },
            deep: true
        }
    },
    methods: {
        startEditing() {
            this.isEditing = true;
            this.editForm = {
                name: this.localJob.name || '',
                quantity: this.localJob.quantity || 1,
                copies: this.localJob.copies || 1,
                salePrice: this.localJob.salePrice || 0,
                width: this.localJob.width || 0,
                height: this.localJob.height || 0
            };
            this.error = null;
        },

        cancelEditing() {
            this.isEditing = false;
            this.editForm = {};
            this.error = null;
        },

        async saveChanges() {
            if (this.isSaving) return;

            try {
                this.isSaving = true;
                this.error = null;

                // Use the simpler job update endpoint since jobs belong to invoices
                const response = await axios.put(
                    `/jobs/${this.localJob.id}`,
                    this.editForm
                );

                if (response.data.job) {
                    this.localJob = { ...response.data.job };
                    this.$emit('job-updated', this.localJob);
                    this.toast.success('Job updated successfully');
                }

                this.isEditing = false;
                this.editForm = {};

            } catch (error) {
                console.error('Error updating job:', error);
                
                // Handle different types of errors
                if (error.response?.status === 404) {
                    this.error = 'Job not found. It may have been deleted.';
                } else if (error.response?.status === 403) {
                    this.error = 'You do not have permission to edit this job.';
                } else if (error.response?.data?.message) {
                    this.error = error.response.data.message;
                } else if (error.response?.data?.error) {
                    this.error = error.response.data.error;
                } else {
                    this.error = 'Failed to update job. Please try again.';
                }
                
                this.toast.error(this.error);
            } finally {
                this.isSaving = false;
            }
        },

        formatPrice(price) {
            if (!price && price !== 0) return '0.00';
            return typeof price === 'number' ? price.toFixed(2) : '0.00';
        },

        formatDimension(dimension) {
            if (!dimension && dimension !== 0) return '0.00';
            return typeof dimension === 'number' ? dimension.toFixed(2) : '0.00';
        },

        formatArea(area) {
            if (!area && area !== 0) return '0.0000';
            return typeof area === 'number' ? area.toFixed(4) : '0.0000';
        },

        getMaterialInfo() {
            if (this.localJob.articles && this.localJob.articles.length > 0) {
                return this.localJob.articles.map(article => {
                    const unit = article.in_square_meters ? 'm²' : 
                               article.in_pieces ? 'ком.' : 
                               article.in_kilograms ? 'кг' : 
                               article.in_meters ? 'м' : 'ед.';
                    return `${article.name} (${article.pivot.quantity} ${unit})`;
                }).join(', ');
            } else if (this.localJob.large_material) {
                return this.localJob.large_material.name;
            } else if (this.localJob.small_material) {
                return this.localJob.small_material.name;
            }
            return null;
        }
    }
};
</script>

<style scoped lang="scss">
// Color Variables from existing design
$background-color: #1a2732;
$gray: #3c4e59;
$dark-gray: #2a3946;
$light-gray: #54606b;
$ultra-light-gray: #77808b;
$white: #ffffff;
$black: #000000;
$green: #408a0b;
$light-green: #81c950;
$blue: #0073a9;
$red: #9e2c30;
$orange: #a36a03;

.job-edit-container {
    background: $dark-gray;
    border-radius: 3px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    border: 1px solid $light-gray;
}

.job-edit-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding: 10px;
    background-color: #7DC068;
}

.job-title {
    color: $black;
    font-size: 18px;
    font-weight: bold;
    margin: 0;
}

.job-actions {
    display: flex;
    gap: 8px;
}

.btn {
    padding: 8px 12px;
    border: none;
    border-radius: 2px;
    cursor: pointer;
    font-size: 14px;
    font-weight: bold;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;

    &:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    i {
        font-size: 14px;
    }
}

.btn-edit {
    background-color: $blue;
    color: $white;

    &:hover:not(:disabled) {
        background-color: darken($blue, 10%);
    }
}

.btn-save {
    background-color: $green;
    color: $white;

    &:hover:not(:disabled) {
        background-color: darken($green, 10%);
    }
}

.btn-cancel {
    background-color: $red;
    color: $white;

    &:hover:not(:disabled) {
        background-color: darken($red, 10%);
    }
}

.job-details {
    display: flex;
    gap: 20px;
    align-items: flex-start;
}

.job-image {
    flex-shrink: 0;
}

.job-thumbnail {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid #e0e0e0;
}

.job-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    flex: 1;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 5px;

    label {
        font-weight: 600;
        color:  $white;
        font-size: 14px;
    }

    .info-value {
        color: $white;
        font-size: 14px;
        padding: 8px 0;
    }

    .total-price {
        font-weight: 600;
        color: #38a169;
        font-size: 16px;
    }
}

.form-input {
    padding: 8px 12px;
    border: 1px solid $light-gray;
    border-radius: 3px;
    font-size: 14px;
    background-color: $white;
    color: $black;
    transition: border-color 0.2s ease;

    &:focus {
        outline: none;
        border-color: $light-green;
        box-shadow: 0 0 0 2px rgba($light-green, 0.3);
    }

    &:invalid {
        border-color: $red;
    }
}

.material-info {
    grid-column: 1 / -1;
    display: flex;
    flex-direction: column;
    gap: 5px;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #e0e0e0;

    label {
        font-weight: 600;
        color: $white;
        font-size: 14px;
    }

    .info-value {
        color: $white;
        font-size: 14px;
        background-color: rgba(white, 0.15);
        padding: 8px 12px;
        border-radius: 4px;
        border: 1px solid #e2e8f0;
    }
}

.error-message {
    background-color: #fed7d7;
    color: #c53030;
    padding: 10px 15px;
    border-radius: 4px;
    margin-top: 15px;
    border: 1px solid #feb2b2;
    font-size: 14px;
}

@media (max-width: 768px) {
    .job-details {
        flex-direction: column;
    }

    .job-info-grid {
        grid-template-columns: 1fr;
    }

    .job-edit-header {
        flex-direction: column;
        gap: 15px;
        align-items: stretch;
    }

    .job-actions {
        justify-content: center;
    }
}
</style>

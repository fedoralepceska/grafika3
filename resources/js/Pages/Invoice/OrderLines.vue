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
                            @change="(e) => handleFileDrop(e, job)"
                            class="file-input"
                            :id="'file-input-' + job.id"
                            style="display: none;"
                        />

                        <!-- Show placeholder or image -->
                        <div v-if="!job.file || job.file === 'placeholder.jpeg'" class="placeholder-upload">
                            <div class="placeholder-content" @click="triggerFileInput(job.id)">
                                <span class="placeholder-text">Drop File</span>
                            </div>
                        </div>

                        <div v-else>
                            <img
                                :src="getImageUrl(job.id)"
                                alt="Job Image"
                                class="jobImg thumbnail"
                                @click="triggerFileInput(job.id)"
                                style="cursor: pointer;"
                            />
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
                    <div class="bg-gray-200 text-black bold">
                        <div class="pt-1 pl-2 pr-2">
                            {{ $t('jobPrice') }}: <span class="bold">{{ job?.totalPrice?.toFixed(2) }} ден.</span>
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
    </div>
</template>

<script>
import { useToast } from "vue-toastification";
import axios from "axios";

export default {
    name: "OrderLines",

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
        };
    },

    computed: {
        jobsToDisplay() {
            const mergedJobs = [...this.jobs || [], ...this.updatedJobs || [], ...this.jobsWithPrices || []];

            // Create a Map to store jobs by ID, preserving the most recent updates
            const jobMap = new Map();

            // First pass: store initial values
            for (const job of mergedJobs) {
                if (!jobMap.has(job.id)) {
                    jobMap.set(job.id, { ...job });
                }
            }

            // Second pass: update with latest values, preserving edited fields
            for (const job of mergedJobs) {
                const existingJob = jobMap.get(job.id);
                if (existingJob) {
                    jobMap.set(job.id, {
                        ...existingJob,
                        ...job,
                        // Always keep the most recent quantity and copies values
                        quantity: job.quantity !== undefined ? job.quantity : existingJob.quantity,
                        copies: job.copies !== undefined ? job.copies : existingJob.copies,
                        // Keep other important fields
                        totalPrice: job.totalPrice || existingJob.totalPrice,
                        actions: job.actions || existingJob.actions,
                        file: job.file || existingJob.file
                    });
                }
            }

            return Array.from(jobMap.values());
        },

        fileJobs() {
            return this.jobsToDisplay.filter(job => job.file && job.file !== 'placeholder.jpeg');
        },
    },

    methods: {
        triggerFileInput(jobId) {
            const fileInput = document.getElementById('file-input-' + jobId);
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

        toggleActions(jobId) {
            this.showActions = this.showActions === jobId ? null : jobId;
        },

        async handleFileDrop(event, job) {
            const file = event.target.files[0];
            if (!file) return;

            const toast = useToast();

            try {
                const formData = new FormData();
                formData.append('file', file);

                // Store current quantity and copies values
                const currentQuantity = job.quantity;
                const currentCopies = job.copies;

                const response = await axios.post(
                    `/jobs/${job.id}/update-file`, formData, {
                        headers: {
                            'Content-Type': 'multipart/form-data',
                        },
                    });

                const dimensions = await axios.get(`/jobs/${response.data.job.id}/image-dimensions`);

                const updatedJob = {
                    ...job,
                    file: response.data.job.file,
                    width: dimensions.data.width,
                    height: dimensions.data.height,
                    fileSize: file.size,
                    // Preserve the current quantity and copies
                    quantity: currentQuantity,
                    copies: currentCopies
                };

                await axios.put(`/jobs/${job.id}`, {
                    file: response.data.job.file,
                    width: dimensions.data.width,
                    height: dimensions.data.height,
                    // Also update quantity and copies in the backend
                    quantity: currentQuantity,
                    copies: currentCopies
                });

                axios.post('/get-jobs-by-ids', {
                    jobs: [job.id],
                })
                    .then(response => {
                        // Preserve quantity and copies in the response data
                        const jobsWithPreservedValues = response.data.jobs.map(j => ({
                            ...j,
                            quantity: currentQuantity,
                            copies: currentCopies
                        }));

                        this.$emit('jobs-updated', jobsWithPreservedValues);
                        this.$emit('job-updated', jobsWithPreservedValues);
                        this.jobsWithPrices = jobsWithPreservedValues;
                    })
                    .catch(error => {
                        toast.error("Couldn't fetch updated jobs");
                    });

                this.updatedJobs.push(updatedJob);
                toast.success('File uploaded successfully');
            } catch (error) {
                toast.error('Failed to upload file');
                console.error(error);
            }
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

            try {
                // Log the data being sent to verify
                console.log('Updating job:', {
                    field: this.editingField,
                    value: valueToUpdate,
                    jobId: job.id
                });

                // First update the backend
                const response = await axios.put(`/jobs/${job.id}`, {
                    [this.editingField]: valueToUpdate,
                    // Include both fields to ensure both are updated
                    quantity: this.editingField === 'quantity' ? valueToUpdate : job.quantity,
                    copies: this.editingField === 'copies' ? valueToUpdate : job.copies
                });

                if (response.status === 200) {
                    // Immediately update the local job data
                    job[this.editingField] = valueToUpdate;

                    // Get fresh data with updated calculations
                    const updatedJobResponse = await axios.post('/get-jobs-by-ids', {
                        jobs: [job.id],
                    });

                    if (updatedJobResponse.data.jobs?.[0]) {
                        const updatedJob = {
                            ...updatedJobResponse.data.jobs[0],
                            // Ensure we keep our updated values
                            [this.editingField]: valueToUpdate
                        };

                        // Update in jobsWithPrices
                        const index = this.jobsWithPrices.findIndex(j => j.id === job.id);
                        if (index !== -1) {
                            this.jobsWithPrices[index] = {
                                ...this.jobsWithPrices[index],
                                ...updatedJob,
                                [this.editingField]: valueToUpdate
                            };
                        } else {
                            this.jobsWithPrices.push(updatedJob);
                        }

                        // Also update in updatedJobs if it exists
                        if (this.updatedJobs) {
                            const updatedIndex = this.updatedJobs.findIndex(j => j.id === job.id);
                            if (updatedIndex !== -1) {
                                this.updatedJobs[updatedIndex] = {
                                    ...this.updatedJobs[updatedIndex],
                                    ...updatedJob,
                                    [this.editingField]: valueToUpdate
                                };
                            } else {
                                this.updatedJobs.push(updatedJob);
                            }
                        }

                        // Emit the update with preserved values
                        this.$emit('job-updated', {
                            ...updatedJob,
                            [this.editingField]: valueToUpdate
                        });
                    }

                    toast.success('Updated successfully');
                }
            } catch (error) {
                toast.error('Failed to update');
                console.error('Error updating job:', error);
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
</style>

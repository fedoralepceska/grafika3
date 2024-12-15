<template>
    <div v-if="$props.jobs?.length > 0">
        <table class="border">
            <tbody>
            <tr v-for="(job, index) in jobsToDisplay" :key="index">
                <!-- ORDER INDEX, NAME, AND ADDITIONAL INFO -->
                <div class="text-white">
                    <td class="text-black bg-gray-200 font-weight-black">
                        <span class="bold">#{{ index + 1 }} {{ job.name }}</span>
                    </td>
                    <td> File: <span class="bold">{{ job.file }}</span></td>
                    <td>ID: <span class="bold">{{ job.id }}</span></td>
                    <td>{{ $t('width') }}: <span class="bold">{{ job.width ? job.width.toFixed(2) : '0.00' }}mm</span></td>
                    <td>{{ $t('height') }}: <span class="bold">{{ job.height ? job.height.toFixed(2) : '0.00' }}mm</span></td>
                    <td>{{ $t('Quantity') }}: <span class="bold">{{ job.quantity }}</span></td>
                    <td>{{ $t('Copies') }}: <span class="bold">{{ job.copies }}</span></td>
                </div>

                <!-- FILE INFO -->
                <div class="flex text-white">
                    <td>
                        <div v-if="job.file === 'placeholder.jpeg'" class="placeholder-upload">
                            <div class="placeholder-content">
                                <span class="placeholder-text">Drop PDF here</span>
                                <input
                                    type="file"
                                    accept=".pdf"
                                    @change="(e) => handleFileDrop(e, job)"
                                    class="file-input"
                                />
                            </div>
                        </div>
                        <img v-else :src="getImageUrl(job.id)" alt="Job Image" class="jobImg thumbnail" />
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
                    <div class="bg-white text-black bold">
                        <div class="pt-1 pl-2 pr-2">
                            {{ $t('jobPrice') }}: <span class="bold">{{ job?.totalPrice?.toFixed(2) }} ден.</span>
                        </div>
                    </div>
                </div>
            </tr>
            </tbody>
        </table>
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
            jobsWithPrices: []
        };
    },

    computed: {
        jobsToDisplay() {
            const mergedJobs = [...this.updatedJobs, ...this.jobs, ...this.jobsWithPrices];

            // Create a Map to store jobs by ID, prioritizing those with totalPrice
            const jobMap = new Map();
            for (const job of mergedJobs) {
                if (!jobMap.has(job.id) || (job.totalPrice && !jobMap.get(job.id).totalPrice)) {
                    jobMap.set(job.id, job); // Keep the job with totalPrice if available
                }
            }

            return Array.from(jobMap.values());
        },

        fileJobs() {
            return this.jobsToDisplay.filter(job => job.file && job.file !== 'placeholder.jpeg');
        },
    },

    methods: {
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
                    fileSize: file.size, // Add file size for accurate calculations
                };

                await axios.put(`/jobs/${job.id}`, {
                    file: response.data.job.file,
                    width: dimensions.data.width,
                    height: dimensions.data.height,
                });

                axios.post('/get-jobs-by-ids', {
                    jobs: [job.id],
                })
                    .then(response => {
                        this.$emit('jobs-updated', response.data.jobs);
                        this.$emit('job-updated', response.data.jobs);
                        this.jobsWithPrices = response.data.jobs;
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
</style>

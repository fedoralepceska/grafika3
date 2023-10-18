<template>
    <MainLayout>
        <div class="pl-7 pr-7 mb-5">
            <div class="header pt-10 pb-4">
                <div class="left mr-10">
                    <img src="/images/List.png" alt="UserLogo" class="image-icon" />
                </div>
                <div class="right">
                    <h1 class="page-title">{{ $t('invoice') }}</h1>
                    <h3 class="text-white">{{ $t('invoice') }} / {{ $t('createNewInvoice') }}</h3>
                </div>
            </div>
            <div class="wrapper">
                <div class="right dark-gray client-form">
                    <div class="form-container p15">
                        <form @submit.prevent="submitForm">
                            <div class="two-column-layout">
                                <div class="left-column">
                                    <h2 class="sub-title uppercase">{{ $t('clientDetails') }}</h2>
                                    <div class="form-group">
                                        <label for="invoice_title">{{ $t('invoiceTitle') }}:</label>
                                        <input type="text" v-model="invoice.invoice_title" id="invoice_title" class="text-gray-700" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="client">{{ $t('client') }}:</label>
                                        <select v-model="invoice.client_id" @change="onClientSelected" id="client" class="text-gray-700" required>
                                            <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.name }}</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="client_email">{{ $t('company') }}:</label>
                                        <span id="client_email">{{ selectedClientCompany }}</span>
                                    </div>
                                    <div class="form-group">
                                        <label for="client_email">{{ $t('phone') }}:</label>
                                        <span id="client_email">{{ selectedClientPhone }}</span>
                                    </div>

                                    <div class="form-group">
                                        <label for="comment">{{ $t('comment') }}:</label>
                                        <textarea v-model="invoice.comment" id="comment" class="text-gray-700"></textarea>
                                    </div>
                                </div>
                                <div class="right-column">
                                    <h2 class="sub-title uppercase">{{ $t('shippingDetails') }}</h2>
                                    <div class="form-group">
                                        <label for="start_date">{{ $t('startDate') }}:</label>
                                        <input type="date" v-model="invoice.start_date" id="start_date" class="text-gray-700" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="end_date">{{ $t('endDate') }}:</label>
                                        <input type="date" v-model="invoice.end_date" id="end_date" class="text-gray-700" required>
                                    </div>
                                </div>
                            </div>
                            <!-- Submit Button -->
                            <div class="button-container mt-10">
                                <PrimaryButton type="submit">{{ $t('createInvoice') }}</PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="left pl-5">
                    <DragAndDrop ref="dragAndDrop"/>
                </div>
            </div>
        </div>
        <div class="pl-7 pr-7">
            <div class="wrapper2 gap-4">
                <div class="left2">
                    <div class="orderInfo dark-gray">
                        <h2 class="sub-title uppercase">{{ $t('orderInfo') }}</h2>
                        <OrderInfo v-if="$refs.dragAndDrop?.jobs?.length > 0" @jobs-updated="updateJobs" :jobs="$refs.dragAndDrop.jobs"/>
                    </div>
                </div>
                <div class="right2">
                    <div class="Order dark-gray">
                        <h2 class="sub-title uppercase">{{ $t('orderLines') }}</h2>
                        <table class="border" v-if="$refs.dragAndDrop?.jobs?.length > 0">
                            <tbody>
                            <template v-if="updatedJobs.length === 0">
                                <tr v-for="(job, index) in $refs.dragAndDrop?.jobs" :key="index">

                                    <!--FILE INFO BEFORE SYNCING-->
                                    <div class="bg-gray-500 text-white">
                                        <td class="light-gray ">#{{ index + 1 }}</td>
                                        <td>{{$t('name')}}: </td>
                                        <td>ID: {{ job.id }}</td>
                                    </div>


                                    <div class="flex text-white">
                                        <td><img :src="getImageUrl(job.id)" alt="Job Image" class="jobImg" /></td>
                                        <td>{{ $t('width') }}: {{ job.width }} </td>
                                        <td>{{ $t('height') }}: {{ job.height }}</td>
                                        <td>{{$t('Quantity')}}: </td>
                                        <td>{{$t('Copies')}}:</td>
                                    </div>

                                    <div class="flex " >
                                        <td class="flex items-center bg-gray-200 ">
                                            <img src="/images/shipping.png" class="w-10 h-10 pr-1" alt="Shipping">
                                            {{ $t('Shipping') }}: </td>
                                    </div>

                                </tr>
                            </template>
                            <template v-else>
                                <tr v-for="(job, index) in mergedJobs" :key="index">
                                    <!--ORDER INDEX, NAME AND ADDITIONAL INFO-->
                                    <div class="bg-gray-500 text-white">
                                        <td class="light-gray ">#{{ index + 1 }}</td>
                                        <td>{{$t('name')}}: </td>
                                        <td>ID: {{ job.id }}</td>
                                    </div>

                                    <!--FILE INFO-->
                                    <div class="flex text-white">
                                        <td><img :src="getImageUrl(job.id)" alt="Job Image" class="jobImg" /></td>
                                        <td>{{ $t('width') }}: {{ job.width }} </td>
                                        <td>{{ $t('height') }}: {{ job.height }}</td>
                                        <td>{{$t('Quantity')}}: </td>
                                        <td>{{$t('Copies')}}:</td>
                                    </div>

                                    <!--SHIPPING INFO-->
                                    <div class="flex " >
                                        <td class="flex items-center bg-gray-200 ">
                                            <img src="/images/shipping.png" class="w-10 h-10 pr-1" alt="Shipping">
                                            {{ $t('Shipping') }}: </td>
                                    </div>

                                    <!--ORDER INFO-->
                                    <div>
                                        <div class="flex gap-5 justify-center" >
                                            <div class="jobInfo mt-3 mb-5" >
                                                <div class="blue p-1 pl-1 text-white">
                                                    {{ $t('machineP') }}
                                                    <button class="toggle-button" @click="toggleMachinePrint">&#9207;</button>
                                                </div>
                                                <transition name="slide-fade">
                                                    <div v-if="showMachinePrint" class="ultra-light-blue form-group pl-1 pt-1 pb-1">
                                                        <div>&#9659; {{ $t(`machinePrint.${job.machinePrint}`) }}</div>
                                                    </div>
                                                </transition>
                                            </div>
                                            <div class="jobInfo mt-3 mb-5">
                                                <div class="red p-1 pl-1 text-white">
                                                    {{ $t('machineC') }}
                                                    <button class="toggle-button" @click="toggleMachineCut">&#9207;</button>
                                                </div>
                                                <transition name="slide-fade">
                                                    <div v-if="showMachineCut" class="ultra-light-red form-group pl-1 pt-1 pb-1">
                                                        <div>&#9659; {{ $t(`machineCut.${job.machineCut}`) }}</div>
                                                    </div>
                                                </transition>
                                            </div>
                                            <div class="jobInfo mt-3 mb-5">
                                                <div class="orange p-1 pl-1 text-white">
                                                    {{ $t('materialLargeFormat') }}
                                                    <button class="toggle-button" @click="toggleMaterials">&#9207;</button>
                                                </div>
                                                <transition name="slide-fade">
                                                    <div v-if="showMaterials" class="ultra-light-orange form-group pl-1 pt-1 pb-1">
                                                        <div>&#9659; {{ $t(`materials.${job.materials}`) }}</div>
                                                    </div>
                                                </transition>
                                            </div>
                                            <div class="jobInfo mt-3 mb-5">
                                                <div class="orange p-1 pl-1 text-white">
                                                    {{ $t('materialSmallFormat') }}
                                                    <button class="toggle-button" @click="toggleMaterialsSmall">&#9207;</button>
                                                </div>
                                                <transition name="slide-fade">
                                                    <div v-if="showMaterialsSmall" class="ultra-light-orange form-group pl-1 pt-1 pb-1">
                                                        <div>&#9659; {{ $t(`materialsSmall.${job.materialsSmall}`) }}</div>
                                                    </div>
                                                </transition>
                                            </div>
                                        </div>
                                        <div class="pl-14 pr-14">
                                            <div class="jobInfo mt-3 mb-5">
                                                <div class="green p-1 pl-1 text-white">
                                                    {{$t('ACTIONS')}}
                                                    <button class="toggle-button" @click="toggleActions">&#9207;</button>
                                                </div>
                                                <transition name="slide-fade">
                                                    <div v-if="showActions" class="ultra-light-green text-white pl-1 pt-1 pb-1">
                                                        <div v-for="action in actions(job.id)" :key="action">
                                                            <span>&#9659; {{ $t(`actions.${action}`) }}</span>
                                                        </div>
                                                    </div>
                                                </transition>
                                            </div>
                                        </div>
                                    </div>
                                </tr>
                            </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script>
import axios from 'axios';
import MainLayout from "@/Layouts/MainLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import DragAndDrop from "@/Components/DragAndDrop.vue";
import {useToast} from "vue-toastification";
import OrderInfo from "@/Components/OrderInfo.vue";

export default {
    name: "InvoiceForm",
    components: {OrderInfo, DragAndDrop, MainLayout, PrimaryButton },
    data() {
        return {
            invoice: {
                start_date: '',
                end_date: '',
                client_id: '',
                invoice_title: '',
                comment: '',
            },
            clients: [],
            invoices: [],
            selectedClientPhone: '',
            selectedClientCompany: '',
            updatedJobs: [],
            showMaterials: false,
            showMaterialsSmall: false,
            showMachineCut: false,
            showMachinePrint: false,
            showActions: false,
            newJobs: []
        };
    },
    async created() {
        // Fetch clients when component is created
        await this.fetchInvoices();
        await this.fetchClients();
        await this.fetchJobs();
    },
    computed: {
        mergedJobs() {
            return this.mergeArraysByUniqueId(this.updatedJobs, this.newJobs, 'id');
        }
    },
    methods: {
        async fetchInvoices() {
            try {
                let response = await axios.get('/invoices'); // Adjust this endpoint to your API route
                this.invoices = response.data;
            } catch (error) {
                console.error("Failed to fetch invoices:", error);
            }
        },
        async fetchClients() {
            try {
                let response = await axios.get('/clients'); // Adjust this endpoint to your API route
                this.clients = response.data;
            } catch (error) {
                console.error("Failed to fetch clients:", error);
            }
        },
        async fetchJobs() {
            try {
                let response = await axios.get('/jobs');
                this.newJobs = response.data;
            } catch (error) {
                console.error("Failed to fetch jobs:", error);
            }
        },
        async onClientSelected() {
            const selectedClient = this.clients.find(client => client.id === this.invoice.client_id);
            if (selectedClient) {
                this.selectedClientPhone = selectedClient.phone;
                this.selectedClientCompany = selectedClient.company;
            } else {
                this.selectedClientPhone = '';
                this.selectedClientCompany= '';
            }
        },
        toggleMachineCut() {
            this.showMachineCut = !this.showMachineCut;
        },
        toggleMachinePrint() {
            this.showMachinePrint = !this.showMachinePrint;
        },
        toggleMaterials() {
            this.showMaterials = !this.showMaterials;
        },
        toggleMaterialsSmall() {
            this.showMaterialsSmall = !this.showMaterialsSmall;
        },
        toggleActions() {
            this.showActions = !this.showActions;
        },
        actions(id) {
            this.fetchJobs();
            const job = this.newJobs.find(job => job.id === id);
            // Check if the job exists
            if (job) {
                // Assuming actions is an array of objects containing name and status properties
                const jobActions = job.actions;

                // If there are actions, you can map them to an array of names
                if (jobActions && jobActions.length > 0) {
                    return jobActions.map(action => action.name);
                }
            }
            return 'No actions'; // Return a default value if there are no actions for the job
        },
        mergeArraysByUniqueId(updatedJobs, newJobs, uniqueId) {
            const result = [];

            // Create a map to store newJobs by their uniqueId for efficient lookups
            const newJobsMap = new Map();
            newJobs.forEach((job) => {
                newJobsMap.set(job[uniqueId], job);
            });

            updatedJobs.forEach((job) => {
                const newJob = newJobsMap.get(job[uniqueId]);
                if (newJob) {
                    // Merge properties from newJob into updated job
                    const mergedJob = { ...job, ...newJob };
                    result.push(mergedJob);
                    newJobsMap.delete(job[uniqueId]); // Mark as used
                } else {
                    result.push(job);
                }
            });

            // Add any remaining newJobs to the result
            newJobsMap.forEach((job) => {
                result.push(job);
            });

            return result;
        },

        async submitForm() {
            this.invoice.status = "NOT_STARTED_YET";
            this.invoice.jobs = [];
            const toast = useToast();

            // Push the job objects created in the DragAndDrop component to the jobs array
            for (const job of this.$refs.dragAndDrop.jobs) {
                let finalJob;
                finalJob = {
                    width: job.width,
                    height: job.height,
                    file: job.file,
                    id: job.id
                }
                this.invoice.jobs.push(finalJob);
            }
            try {
                let response = await axios.post('/invoices', this.invoice, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    },
                });

                toast.success('Invoice created successfully');
                // You might want to reset the form or navigate the user to another page
            } catch (error) {
                console.error("Failed to create invoice:", error);
                toast.error('Error creating job');
            }
        },
        updateJobs(updatedJobs) {
            this.updatedJobs = updatedJobs;
        },
        getImageUrl(id) {
            return this.$refs.dragAndDrop.jobs.find(j => j.id === id).imageData;
        }
    },
};
</script>

<style scoped lang="scss">
.light-gray{
    background-color: $light-gray;
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
.jobInfo{
    min-width: 200px;

}

.toggle-button {
    background: none;
    color: $white;
}
.ultra-light-orange{
    background-color: rgba(199,165,103,0.2);
}
.ultra-light-green {
    background-color: rgba(121, 173, 84, 0.2);
}
.ultra-light-blue{
    background-color: rgba(102,171,203,0.2);
}
.ultra-light-red{
    background-color: rgba(196,128,130,0.2);
}
.blue{
    background-color: $blue;
}
.light-green{
    background-color: $light-green;
}
.red{
    background-color: $red;
}
.orange{
    background-color: $orange;
}
.green {
    background-color: $green;
}
.two-column-layout {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    column-gap: 20px;
}

.left-column, .right-column {
    display: flex;
    flex-direction: column;
}

.left-column {
    margin-right: 20px;
}

.right-column {
    margin-left: 20px;
}
.wrapper{
    display: flex;
}
.wrapper2{
    display: flex;
}
.left2{
    width: 30%;
}
.right2{
    width: 70%;
}
.Order,.orderInfo{
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}
.header{
    margin-left: 20px;
    display: flex;
    align-items: center;
}
.dark-gray {
    background-color: $dark-gray;
}

.client-form {
    width: 100%;
    max-width: 1000px;
    justify-content: left;
    align-items: center;
    min-height: 50vh;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}
.page-title {
    font-size: 34px;
    display: flex;
    align-items: center;
    color: $white;
}
.sub-title{
    font-size: 20px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    color: $white;
}

.image-icon {
    margin-left: 10px;
    max-width: 60px;
}
.form-group {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    color: $white;
}

.label {
    flex: 1;
    text-align: left;
    margin-right: 20px;
}
.button-container{
    display: flex;
    justify-content: flex-end;
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
tr{
    border: 1px solid white;
}
th {

    background-color: #f0f0f0;
}

.jobImg {
    width: 50px;
    height: 50px;
    display: block;
    margin: 0 auto;
}

</style>

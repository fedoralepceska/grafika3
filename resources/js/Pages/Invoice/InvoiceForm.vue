<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <div class="header pt-3 pb-4">
                <div class="left mr-3">
                    <img src="/images/List.png" alt="UserLogo" class="image-icon" />
                </div>
                <div class="right">
                    <h1 class="page-title">{{ $t('invoice') }}</h1>
                    <h3 class="text-white"><span class="green-text">{{ $t('invoice') }}</span> / {{ $t('createNewInvoice') }}</h3>
                </div>
            </div>
        </div>
        <div class="pl-2 pr-2 ml-2 mr-2 dark-gray">
            <div class="wrapper  p-5">
                <div class="right light-gray client-form">
                    <div class="form-container ">
                        <form @submit.prevent="submitForm">
                            <div class="two-column-layout">
                                <div class="left-column">
                                    <h2 class="sub-title uppercase">{{ $t('clientDetails') }}</h2>
                                    <div class="form-group gap-4">
                                        <label for="invoice_title">{{ $t('invoiceTitle') }}:</label>
                                        <input type="text" v-model="invoice.invoice_title" id="invoice_title" class="text-gray-700" required>
                                    </div>
                                    <div class="form-group gap-4">
                                        <label for="client">{{ $t('client') }}:</label>
                                        <select v-model="invoice.client_id" @change="onClientSelected" id="client" class="text-gray-700" required>
                                            <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.name }}</option>
                                        </select>
                                    </div>
                                    <div class="form-group gap-4">
                                        <label for="client_email">{{ $t('company') }}:</label>
                                        <span id="client_email">{{ selectedClientCompany }}</span>
                                    </div>
                                    <div class="form-group gap-4">
                                        <label for="client_email">{{ $t('phone') }}:</label>
                                        <span id="client_email">{{ selectedClientPhone }}</span>
                                    </div>
                                </div>
                                <div class="right-column">
                                    <h2 class="sub-title uppercase">{{ $t('shippingDetails') }}</h2>
                                    <div class="form-group gap-4">
                                        <label for="start_date">{{ $t('startDate') }}:</label>
                                        <input type="date" v-model="invoice.start_date" id="start_date" class="text-gray-700" required>
                                    </div>
                                    <div class="form-group gap-4">
                                        <label for="end_date">{{ $t('endDate') }}:</label>
                                        <input type="date" v-model="invoice.end_date" id="end_date" class="text-gray-700 "  required>
                                    </div>
                                </div>
                            </div>
                            <!-- BUTTON MUST BE IN THE FORM TAG -->
                            <div class="button-container mt-10 p-1">
                                <PrimaryButton onclick="submitForm()" type="submit">{{ $t('createInvoice') }}</PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="left pl-5">
                    <DragAndDrop ref="dragAndDrop" :invoiceComment="invoice.comment" @commentUpdated="handleCommentUpdate"/>
                </div>
            </div>
            <div class="wrapper2 p-5 gap-4">
                <div class="left2">
                    <div class="orderInfo light-gray">

                        <TabsWrapper>
                            <Tab title="ADD" icon="mdi mdi-plus-circle">
                                <TabsWrapperV2>
                                    <TabV2 title="Manual" icon="mdi mdi-gesture-tap">
                                        <OrderInfo v-if="$refs.dragAndDrop?.jobs?.length > 0" @jobs-updated="updateJobs" :jobs="$refs.dragAndDrop.jobs"/>
                                    </TabV2>
                                    <TabV2 title="From Catalog" icon="mdi mdi-book-open-variant">

                                    </TabV2>
                                </TabsWrapperV2>
                            </Tab>

                            <Tab title="SHIPPING" class="text" icon="mdi mdi-truck">
                                <span class="text-white">Add the shipping information here:</span>
                                <textarea v-model="shippingDetails" @input="emitShippingDetails"></textarea>
                            </Tab>
                        </TabsWrapper>
                    </div>
                </div>
                <div class="right2">
                    <div class="Order light-gray">
                        <h2 class="sub-title uppercase">{{ $t('orderLines') }}</h2>
                        <table class="border" v-if="$refs.dragAndDrop?.jobs?.length > 0">
                            <tbody>
                            <template v-if="updatedJobs.length === 0">
                                <tr v-for="(job, index) in $refs.dragAndDrop?.jobs" :key="index" >

                                    <!--FILE INFO BEFORE SYNCING-->
                                    <div class=" text-white">
                                        <td class="text-black bg-gray-200 font-weight-black ">#{{ index + 1 }}</td>
                                        <td>{{$t('name')}}: <input  type="text"></td>
                                        <td>ID: {{ job.id }}</td>

                                        <td>{{ $t('width') }}: {{ job.width }} </td>
                                        <td>{{ $t('height') }}: {{ job.height }}</td>
                                        <td>{{$t('Quantity')}}: {{ job.quantity }}</td>
                                        <td>{{$t('Copies')}}: {{ job.copies }}</td>
                                    </div>


                                    <div class="flex text-white">
                                        <td><img :src="getImageUrl(job.id)" alt="Job Image" class="jobImg thumbnail" /></td>

                                    </div>

                                    <div class="flex">
                                        <td class="flex items-center bg-gray-200 text-black">
                                            <img src="/images/shipping.png" class="w-10 h-10 pr-1" alt="Shipping">
                                            {{ $t('Shipping') }}: </td>
                                    </div>

                                </tr>
                            </template>
                            <template v-else>
                                <tr v-for="(job, index) in updatedJobs" :key="index">
                                    <!--ORDER INDEX, NAME AND ADDITIONAL INFO-->
                                    <div class=" text-white">
                                        <td class="text-black bg-gray-200 font-weight-black ">#{{ index + 1 }}</td>
                                        <td>{{$t('name')}}: </td>
                                        <td>ID: {{ job.id }}</td>
                                        <td>{{ $t('width') }}: {{ job.width }} </td>
                                        <td>{{ $t('height') }}: {{ job.height }}</td>
                                        <td>{{$t('Quantity')}}: {{ job.quantity }}</td>
                                        <td>{{$t('Copies')}}: {{ job.copies }}</td>
                                    </div>

                                    <!--FILE INFO-->
                                    <div class="flex text-white">
                                        <td><img :src="getImageUrl(job.id)" alt="Job Image" class="jobImg thumbnail" /></td>
                                        <td>
                                            {{ $t('machineP') }}: {{ $t(`machinePrint.${job.machinePrint}`) }}
                                        </td>
                                        <td>
                                            {{ $t('machineC') }}: {{ $t(`machineCut.${job.machineCut}`) }}
                                        </td>
                                        <td>
                                            {{ job.materials === null ? '' : $t('materialLargeFormat') + ':' + $t(`materials.${job.materials}`) }}
                                            {{ job.materialsSmall === null ? '' : $t('materialLargeFormat') + ':' +  $t(`materialsSmall.${job.materialsSmall}`) }}
                                        </td>
                                        <td>

                                        </td>
                                    </div>

                                    <!--ORDER INFO-->
                                    <div>
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

                                    <!--SHIPPING INFO-->
                                    <div class="flex " >
                                        <td class="flex items-center bg-gray-200 text-black ">
                                            <img src="/images/shipping.png" class="w-10 h-10 pr-1" alt="Shipping">
                                            {{ $t('Shipping') }}: {{ job.shippingInfo }}</td>

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
import TabsWrapper from "@/Components/TabsWrapper.vue";
import Tab from "@/Components/Tab.vue";
import TabV2 from "@/Components/TabV2.vue";
import TabsWrapperV2 from "@/Components/TabsWrapperV2.vue";
import store from '../../orderStore.js';


export default {
    name: "InvoiceForm",
    components: {TabsWrapperV2,TabV2, TabsWrapper,Tab, OrderInfo, DragAndDrop, MainLayout, PrimaryButton },
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
            shippingDetails: '',
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
    methods: {
        emitShippingDetails() {
            store.commit('updateShippingDetails', this.shippingDetails);
        },
        handleCommentUpdate(updatedComment) {
            this.invoice.comment = updatedComment;
        },
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

        toggleActions() {
            this.showActions = !this.showActions;
        },
        actions(id) {
            this.fetchJobs();
            const job = this.newJobs.find(job => job.id === id);
            // Check if the job exists
            if (job) {
                const jobActions = job.actions;

                if (jobActions && jobActions.length > 0) {
                    return jobActions.map(action => action.name);
                }
            }

            return 'No actions'; // Return a default value if there are no actions for the job
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
.green-text{
    color: $green;
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
    width: 38%;
}
.right2{
    width: 63%;
}
.Order,.orderInfo{
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}
.header{
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
    min-height: 20vh;
    padding: 5px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}
.page-title {
    font-size: 24px;
    display: flex;
    align-items: center;
    color: $white;
}
.sub-title{
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    color: $white;
}

.image-icon {
    margin-left: 2px;
    max-width: 40px;
}
.form-group {
    display: flex;
    justify-content: right;
    align-items: center;
    width: 350px;
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
    margin-bottom: 5px;
    border: 1px solid white;
}
th {

    background-color: #f0f0f0;
}

.jobImg {
    width: 60px;
    height: 60px;
    margin: 0 auto;
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
    padding: 10px;
}
</style>

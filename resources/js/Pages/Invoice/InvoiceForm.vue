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
                        <table v-if="$refs.dragAndDrop?.jobs?.length > 0">
                            <thead>
                            <tr>
                                <th>{{ $t('image') }}</th>
                                <th>{{ $t('width') }}</th>
                                <th>{{ $t('height') }}</th>
                                <th>ID</th>
                                <th v-if="updatedJobs.length > 0">Material</th>
                            </tr>
                            </thead>
                            <tbody>
                            <template v-if="updatedJobs.length === 0">
                                <tr v-for="(job, index) in $refs.dragAndDrop?.jobs" :key="index">
                                    <td><img :src="job.imageData" alt="Job Image" class="jobImg" /></td>
                                    <td>{{ job.width }}</td>
                                    <td>{{ job.height }}</td>
                                    <td>{{ job.id }}</td>
                                    <!-- Add options form here -->
                                </tr>
                            </template>
                            <template v-else>
                                <tr v-for="(job, index) in updatedJobs" :key="index">
                                    <td><img :src="getImageUrl(job.id)" alt="Job Image" class="jobImg" /></td>
                                    <td>{{ job.width }}</td>
                                    <td>{{ job.height }}</td>
                                    <td>{{ job.id }}</td>
                                    <td>{{ job.materials }}</td>
                                    <!-- Add options form here -->
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
            updatedJobs: []
        };
    },
    created() {
        // Fetch clients when component is created
        this.fetchInvoices();
        this.fetchClients();
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
        async submitForm() {
            this.invoice.status = "NOT_STARTED_YET";
            this.invoice.jobs = [];
            const toast = useToast();

            // Push the job objects created in the DragAndDrop component to the jobs array
            for (const job of this.$refs.dragAndDrop.jobs) {
                console.log(job.file);
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
    justify-content: end;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

th, td {
    border: 1px solid #ccc;
    padding: 8px;
    text-align: center;
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

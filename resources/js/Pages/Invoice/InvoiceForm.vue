<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="invoice" subtitle="createNewInvoice" icon="List.png" link="orders"/>
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
                                        <select v-model="invoice.client_id" id="client" class="text-gray-700" required>
                                            <option v-for="client in clients" :key="client.id" :value="client.id">{{ client?.name }}</option>
                                        </select>
                                    </div>
                                    <div class="form-group gap-4" v-if="invoice.client_id !== ''">
                                        <label for="contact">{{ $t('contact') }}:</label>
                                        <select v-model="this.invoice.contact_id" @change="onClientSelected" id="contact" class="text-gray-700" required>
                                            <option v-for="contact in client.contacts" :key="contact?.id" :value="contact?.id">{{ contact?.name }}</option>
                                        </select>
                                    </div>
                                    <div class="form-group gap-4">
                                        <label for="client_email">{{ $t('company') }}:</label>
                                        <input type="text" disabled :placeholder="selectedClientCompany || contact?.name">
                                    </div>
                                    <div class="form-group gap-4">
                                        <label for="client_email">{{ $t('phone') }}:</label>
                                        <input type="text" disabled :placeholder=" selectedClientPhone || contact?.phone ">
                                    </div>
                                </div>
                                <div class="right-column">
                                    <h2 class="sub-title uppercase">{{ $t('shippingDetails') }}</h2>
                                    <div class="form-group gap-4">
                                        <label for="start_date">{{ $t('startDate') }}:</label>
                                        <input type="date" :value="invoice.start_date" id="start_date" class="text-gray-700" readonly required>                                    </div>
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
                                        <OrderInfo v-if="$refs.dragAndDrop?.jobs?.length > 0" @jobs-updated="updateJobs" @catalog-jobs-created="handleCatalogJobs" :jobs="$refs.dragAndDrop?.jobs"/>
                                    </TabV2>
                                    <TabV2 title="From Catalog" icon="mdi mdi-book-open-variant">
                                        <CatalogSelector @jobs-created="handleCatalogJobs" />
                                    </TabV2>
                                </TabsWrapperV2>
                            </Tab>

                            <Tab title="SHIPPING" class="text" icon="mdi mdi-truck">
                                <OrderInfo v-if="$refs.dragAndDrop?.jobs?.length > 0" @jobs-updated="updateJobs" :shipping="true" :jobs="$refs.dragAndDrop.jobs"/>
                            </Tab>
                        </TabsWrapper>
                    </div>
                </div>
                <div class="right2">
                    <div class="Order light-gray">
                        <h2 class="sub-title uppercase">{{ $t('orderLines') }}</h2>
                        <OrderLines
                            :jobs="$refs.dragAndDrop?.jobs"
                            :updatedJobs="updatedJobs"
                            @job-updated="handleJobUpdate"
                        />
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script>
import axios from 'axios';
import MainLayout from "@/Layouts/MainLayout.vue";
import PrimaryButton from "@/Components/buttons/PrimaryButton.vue";
import DragAndDrop from "@/Components/DragAndDrop.vue";
import {useToast} from "vue-toastification";
import OrderInfo from "@/Components/OrderInfo.vue";
import TabsWrapper from "@/Components/tabs/TabsWrapper.vue";
import Tab from "@/Components/tabs/Tab.vue";
import TabV2 from "@/Components/tabs/TabV2.vue";
import TabsWrapperV2 from "@/Components/tabs/TabsWrapperV2.vue";
import Header from "@/Components/Header.vue";
import OrderLines from "@/Pages/Invoice/OrderLines.vue";
import CatalogSelector from "@/Components/CatalogSelector.vue";


export default {
    name: "InvoiceForm",
    components: {
        OrderLines,
        Header,
        TabsWrapperV2,
        TabV2,
        TabsWrapper,
        Tab,
        OrderInfo,
        DragAndDrop,
        MainLayout,
        PrimaryButton,
        CatalogSelector,
    },
    data() {
        return {
            invoice: {
                start_date: this.getCurrentDate(),
                end_date: this.invoiceData?.end_date || '',
                client_id: this.invoiceData?.client_id || '',
                invoice_title: this.invoiceData?.invoice_title || '',
                comment: '',
                contact_id: this.invoiceData?.contact_id || 0,
            },
            clients: [],
            invoices: [],
            selectedClientPhone: this.invoiceData?.client?.phone || '',
            selectedClientCompany: this.invoiceData?.client?.company || '',
            updatedJobs: [],
            showMaterials: false,
            showMaterialsSmall: false,
            showMachineCut: false,
            showMachinePrint: false,
            showActions: false,
            newJobs: [],
            contacts: []
        };
    },
    props: {
        invoiceData: Object,
    },
    async beforeMount() {
        // Fetch clients when component is created
        await this.fetchInvoices();
        if (!this.clients.length) {
            await this.fetchClients();
        }
        if (!this.contacts.length) {
            await this.fetchContacts();
        }
        await this.fetchJobs();
    },
    computed: {
        client() {
          return this.clients.find(c => this.invoiceData?.client_id == c.id || this.invoice.client_id === c.id);
        },
        contact() {
            return this.contacts.find(c => c.id == this.invoiceData?.contact_id);
        }
    },
    methods: {
        getCurrentDate() {
            const today = new Date();
            const year = today.getFullYear();
            let month = today.getMonth() + 1;
            let day = today.getDate();

            // Add leading zero if month or day is a single digit
            month = month < 10 ? '0' + month : month;
            day = day < 10 ? '0' + day : day;

            return `${year}-${month}-${day}`;
        },
        handleCommentUpdate(updatedComment) {
            this.invoice.comment = updatedComment;
        },
        async fetchInvoices() {
            try {
                let response = await axios.get('/orders'); // Adjust this endpoint to your API route
                this.invoices = response.data;
            } catch (error) {
                console.error("Failed to fetch invoices:", error);
            }
        },
        async fetchClients() {
            try {
                let response = await axios.get('/clients'); // Adjust this endpoint to your API route
                this.clients = response?.data?.data;
            } catch (error) {
                console.error("Failed to fetch clients:", error);
            }
        },
        async fetchContacts() {
            try {
                let response = await axios.get('/contacts'); // Adjust this endpoint to your API route
                this.contacts = response.data;
            } catch (error) {
                console.error("Failed to fetch contacts:", error);
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
            const contact = this.contact || this.client.contacts.find(c => c.id === this.invoice.contact_id);
            if (contact) {
                this.selectedClientPhone = contact.phone;
                this.selectedClientCompany = contact.name;
            } else {
                this.selectedClientPhone = '';
                this.selectedClientCompany= '';
            }
        },

        async submitForm() {
            // Check if the device is currently online
            if (!navigator.onLine) {
                // Handle the case when the device is offline
                const toast = useToast();
                toast.error('No internet connection. Please check your connection and try again.');
                return;
            }
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
                };
                this.invoice.jobs.push(finalJob);
            }
            try {
                let response = await axios.post('/orders', this.invoice, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    },
                });

                toast.success('Invoice created successfully');
                this.$inertia.visit(`/orders/${response.data.invoice.id}`, {
                    preserveState: true,
                    preserveScroll: true,
                });
            } catch (error) {
                console.error("Failed to create invoice:", error);
                toast.error('Error creating job');
            }
        },
        updateJobs(updatedJobs) {
            this.updatedJobs = updatedJobs;
        },
        getImageUrl(id) {
            return `/storage/uploads/${this.$refs.dragAndDrop.jobs.find(j => j.id === id).file}`
        },
        handleCatalogJobs(catalogJobs) {
            if (this.$refs.dragAndDrop) {
                this.$refs.dragAndDrop.handleCatalogJobs(catalogJobs);
            }
        },
        handleJobUpdate(updatedJob) {
            if (this.updatedJobs) {
                const index = this.updatedJobs.findIndex(j => j.id === updatedJob.id);
                const index2 = this.$refs.dragAndDrop.jobs.findIndex(j => j.id === updatedJob.id);
                if (index !== -1) {
                    this.$refs.dragAndDrop.jobs[index2] = updatedJob;
                    this.updatedJobs[index] = updatedJob;
                }
            }
        }
    },
};
</script>
<style scoped lang="scss">
input, select{
    width: 60%;
    border-radius: 3px;
}
.w70{
    width: 60%;
}
.bold{
    font-weight: bolder;
}
.light-gray{
    background-color: $light-gray;
}
.left{
    width: 900%;
    max-width: 860px;

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
    width: auto;
}
.right2{
    min-width: 63%;
    width: 90%;
}
.Order,.orderInfo {
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
    height: 90%;
    min-height: 80%;
    padding: 5px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}
.sub-title{
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    color: $white;
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
.button-container {
    display: flex;
    justify-content: flex-end;
}

input, select {
    height: 36px;
}

.text {
    display: flex;
    flex-direction: column;
    padding: 10px;
}
</style>

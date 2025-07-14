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
                                        <div class="relative w70">
                                            <input
                                                type="text"
                                                v-model="clientSearch"
                                                @focus="showClientDropdown = true"
                                                @input="filterClients"
                                                :placeholder="selectedClientName || 'Search client...'"
                                                class="text-gray-700 w70"
                                                style="width: 100%;"
                                            />
                                            <div v-if="showClientDropdown"
                                                 class="absolute z-1 0 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto">
                                                <div
                                                    v-for="client in filteredClients"
                                                    :key="client.id"
                                                    @click="selectClient(client)"
                                                    class="px-4 py-2 hover:bg-gray-100 cursor-pointer text-gray-700"
                                                    :class="{'bg-gray-200': invoice.client_id === client.id}"
                                                >
                                                    {{ client.name }}
                                                </div>
                                                <div v-if="filteredClients.length === 0" class="px-4 py-2 text-gray-500">
                                                    No clients found
                                                </div>
                                            </div>
                                        </div>
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
                                <TabsWrapperV2 style="margin-top: 5px">
                                    <TabV2 title="Manual" icon="mdi mdi-gesture-tap">
                                        <OrderInfo
                                            v-if="$refs.dragAndDrop?.jobs?.length > 0"
                                            @jobs-updated="updateJobs"
                                            @catalog-jobs-created="handleCatalogJobs"
                                            :jobs="$refs.dragAndDrop?.jobs"
                                            :isCatalog="true"
                                            :clientId="invoice.client_id"
                                        />
                                    </TabV2>
                                    <TabV2 title="From Catalog" icon="mdi mdi-book-open-variant">
                                        <CatalogSelector
                                            @jobs-created="handleCatalogJobs"
                                            :clientId="invoice.client_id"
                                        />
                                    </TabV2>
                                </TabsWrapperV2>
                            </Tab>

                            <Tab title="SHIPPING" class="text" icon="mdi mdi-truck">
                                <OrderInfo 
                                    v-if="$refs.dragAndDrop?.jobs?.length > 0" 
                                    @jobs-updated="updateJobs" 
                                    :shipping="true" 
                                    :jobs="$refs.dragAndDrop.jobs"
                                    :clientId="invoice.client_id"
                                />
                            </Tab>
                        </TabsWrapper>
                    </div>
                </div>
                <div class="right2">
                    <div class="Order light-gray">
                        <h2 class="sub-title uppercase pl-1" >{{ $t('orderLines') }}</h2>
                        <OrderLines
                            ref="orderLines"
                            :key="`order-lines-${$refs.dragAndDrop?.jobs?.length || 0}-${updatedJobs.length || 0}`"
                            :jobs="$refs.dragAndDrop?.jobs"
                            :updatedJobs="updatedJobs"
                            @job-updated="handleJobUpdate"
                            @delete-job="handleJobDelete"
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
            contacts: [],
            clientSearch: '',
            showClientDropdown: false,
            filteredClients: [],
            selectedClientName: '',
        };
    },
    props: {
        invoiceData: Object,
    },
    async beforeMount() {
        await this.fetchInvoices();
        await this.fetchAllClients();
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
            const contact = this.contact || this.client?.contacts?.find(c => c.id === this.invoice.contact_id);
            if (contact) {
                this.selectedClientPhone = contact.phone;
                this.selectedClientCompany = contact.name;
            } else {
                this.selectedClientPhone = '';
                this.selectedClientCompany = '';
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
                let message = error?.response?.data?.message;
                if (message) {
                    message.split("\n").forEach((msg) => {
                        if (msg.trim()) {
                            toast.error(msg.trim(), {
                                timeout: 20000 // 10 seconds
                            });
                        }
                    });
                } else {
                    // Show a fallback toast if no message is provided
                    toast.error("An unexpected error occurred.");
                }
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
                // Ensure client is selected before proceeding
                if (!this.invoice.client_id) {
                    const toast = useToast();
                    toast.error('Please select a client first');
                    return;
                }

                // Add the jobs to the DragAndDrop component
                this.$refs.dragAndDrop.handleCatalogJobs(catalogJobs);
            }
        },
        handleJobUpdate(updatedJob) {
            if (this.$refs.dragAndDrop) {
                const index = this.$refs.dragAndDrop.jobs.findIndex(j => j.id === updatedJob.id);
                if (index !== -1) {
                    this.$refs.dragAndDrop.jobs[index] = updatedJob;
                }
            }
        },
        filterClients() {
            if (!this.clientSearch) {
                this.filteredClients = [...this.clients];
                return;
            }

            this.filteredClients = this.clients.filter(client =>
                client.name.toLowerCase().includes(this.clientSearch.toLowerCase())
            );
        },
        selectClient(client) {
            this.invoice.client_id = client.id;
            this.clientSearch = client.name;
            this.selectedClientName = client.name;
            this.showClientDropdown = false;

            // Reset contact selection when client changes
            this.invoice.contact_id = '';
            this.selectedClientPhone = '';
            this.selectedClientCompany = '';

            // Trigger client selection handler
            this.onClientSelected();
        },
        async fetchAllClients() {
            try {
                const response = await axios.get('/api/clients/all');
                this.clients = response.data;
                this.filteredClients = [...this.clients];

                // Set initial client name if exists
                if (this.invoice.client_id) {
                    const selectedClient = this.clients.find(c => c.id === this.invoice.client_id);
                    if (selectedClient) {
                        this.selectedClientName = selectedClient.name;
                        this.clientSearch = selectedClient.name;
                    }
                }
            } catch (error) {
                console.error("Failed to fetch clients:", error);
                const toast = useToast();
                toast.error('Error fetching clients');
            }
        },
        async handleJobDelete(jobId) {
            if (this.$refs.dragAndDrop) {
                const toast = useToast();

                try {
                    // Call the API to delete the job and its related records
                    await axios.delete(`/jobs/${jobId}`);

                    // Remove the job from DragAndDrop component's jobs array
                    this.$refs.dragAndDrop.jobs = this.$refs.dragAndDrop.jobs.filter(job => job.id !== jobId);

                    // Also remove from updatedJobs if it exists there
                    this.updatedJobs = this.updatedJobs.filter(job => job.id !== jobId);

                    // Force reactivity update for DragAndDrop component
                    this.$refs.dragAndDrop.$forceUpdate();

                    // Clean up jobsWithPrices in OrderLines component
                    this.handleJobDeleted(jobId);

                    // Force reactivity update for OrderLines component
                    this.$nextTick(() => {
                        if (this.$refs.orderLines) {
                            this.$refs.orderLines.$forceUpdate();
                        }
                    });

                    toast.success('Job deleted successfully');
                } catch (error) {
                    console.error('Error deleting job:', error);
                    toast.error('Failed to delete job');
                }
            }
        },

        handleJobDeleted(jobId) {
            // Clean up jobsWithPrices in OrderLines component to prevent deleted jobs from reappearing
            if (this.$refs.orderLines) {
                this.$refs.orderLines.cleanupDeletedJob(jobId);
            }
        },
    },
    mounted() {
        document.addEventListener('click', (e) => {
            const dropdown = document.querySelector('.relative.w-60');
            if (dropdown && !dropdown.contains(e.target)) {
                this.showClientDropdown = false;
            }
        });
    },
    beforeUnmount() {
        document.removeEventListener('click', this.closeDropdown);
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

.relative {
    position: relative;
}

.absolute {
    position: absolute;
}

.z-10 {
    z-index: 10;
}

.max-h-60 {
    max-height: 15rem;
}

.overflow-auto {
    overflow: auto;
}

.hover\:bg-gray-100:hover {
    background-color: #f3f4f6;
}

.bg-gray-200 {
    background-color: #e5e7eb;
}

.cursor-pointer {
    cursor: pointer;
}

/* Scrollbar styling */
.overflow-auto::-webkit-scrollbar {
    width: 8px;
}

.overflow-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.overflow-auto::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

.overflow-auto::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>

<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <div class="header pt-10 pb-4">
                <div class="left mr-10">
                    <img src="/images/List.png" alt="UserLogo" class="image-icon" />
                </div>
                <div class="right">
                    <h1 class="page-title">Invoice</h1>
                    <h3 class="text-white">Invoice / Create New Invoice</h3>
                </div>
            </div>
            <div class="dark-gray client-form">
                <div class="form-container p15">
                    <form @submit.prevent="submitForm">
                        <div class="two-column-layout">
                            <div class="left-column">
                                <h2 class="sub-title">CLIENT DETAILS</h2>
                                <div class="form-group">
                                    <label for="invoice_title">Invoice Title:</label>
                                    <input type="text" v-model="invoice.invoice_title" id="invoice_title" class="text-gray-700" required>
                                </div>
                                <div class="form-group">
                                    <label for="client">Client:</label>
                                    <select v-model="invoice.client_id" @change="onClientSelected" id="client" class="text-gray-700" required>
                                        <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.name }}</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="client_email">Company:</label>
                                    <span id="client_email">{{ selectedClientCompany }}</span>
                                </div>
                                <div class="form-group">
                                    <label for="client_email">Contact:</label>
                                    <span id="client_email">{{ selectedClientPhone }}</span>
                                </div>

                                <div class="form-group">
                                    <label for="comment">Comment:</label>
                                    <textarea v-model="invoice.comment" id="comment" class="text-gray-700"></textarea>
                                </div>
                            </div>
                            <div class="right-column">
                                <h2 class="sub-title">SHIPPING DETAILS</h2>
                                <div class="form-group">
                                    <label for="start_date">Start Date:</label>
                                    <input type="date" v-model="invoice.start_date" id="start_date" class="text-gray-700" required>
                                </div>
                                <div class="form-group">
                                    <label for="end_date">End Date:</label>
                                    <input type="date" v-model="invoice.end_date" id="end_date" class="text-gray-700" required>
                                </div>
                            </div>
                        </div>
                        <!-- Submit Button -->
                        <div class="button-container mt-10">
                            <PrimaryButton type="submit">Create Invoice</PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div>
            <DragAndDrop ref="dragAndDrop"/>
        </div>
        <div>
            <ul>
                <li v-for="i in invoices">{{ i }}</li>
            </ul>
        </div>
    </MainLayout>
</template>

<script>
import axios from 'axios';
import MainLayout from "@/Layouts/MainLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import DragAndDrop from "@/Components/DragAndDrop.vue";

export default {
    name: "InvoiceForm",
    components: {DragAndDrop, MainLayout, PrimaryButton },
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

            // Push the job objects created in the DragAndDrop component to the jobs array
            for (const job of this.$refs.dragAndDrop.jobs) {
                console.log(job);
                this.invoice.jobs.push({
                    width: job.width,
                    height: job.height,
                    file: job.file,
                    // You can add other job-related properties here
                });
            }
            try {
                let response = await axios.post('/invoices', this.invoice); // Adjust this endpoint to your API route
                alert('Invoice created successfully!');
                // You might want to reset the form or navigate the user to another page
            } catch (error) {
                console.error("Failed to create invoice:", error);
                alert('Failed to create invoice. Please check your inputs and try again.');
            }
        },
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
.header{
    margin-left: 20px;
    display: flex;
    align-items: center;
}
.dark-gray {
    background-color: $dark-gray;
    justify-content: left;
    align-items: center;
    min-height: 50vh;
}

.client-form {
    width: 100%;
    max-width: 1000px;
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


</style>

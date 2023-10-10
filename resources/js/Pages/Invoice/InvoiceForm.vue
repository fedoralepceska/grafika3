<template>
    <div>
        <h1>Create Invoice</h1>
        <form @submit.prevent="submitForm">

            <!-- Start Date -->
            <div>
                <label for="start_date">Start Date:</label>
                <input type="date" v-model="invoice.start_date" id="start_date" required>
            </div>

            <!-- End Date -->
            <div>
                <label for="end_date">End Date:</label>
                <input type="date" v-model="invoice.end_date" id="end_date" required>
            </div>

            <!-- Client Dropdown -->
            <div>
                <label for="client">Client:</label>
                <select v-model="invoice.client_id" id="client" required>
                    <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.name }}</option>
                </select>
            </div>

            <!-- Invoice Title -->
            <div>
                <label for="invoice_title">Invoice Title:</label>
                <input type="text" v-model="invoice.invoice_title" id="invoice_title" required>
            </div>

            <!-- Comment -->
            <div>
                <label for="comment">Comment:</label>
                <textarea v-model="invoice.comment" id="comment"></textarea>
            </div>

            <!-- Submit Button -->
            <div>
                <button type="submit">Create Invoice</button>
            </div>

        </form>
    </div>
</template>

<script>
export default {
    name: "InvoiceForm",
    data() {
        return {
            invoice: {
                start_date: '',
                end_date: '',
                client_id: '',
                invoice_title: '',
                comment: ''
            },
            clients: []
        };
    },
    created() {
        // Fetch clients when component is created
        this.fetchClients();
    },
    methods: {
        async fetchClients() {
            try {
                let response = await axios.get('/clients'); // Adjust this endpoint to your API route
                this.clients = response.data;
            } catch (error) {
                console.error("Failed to fetch clients:", error);
            }
        },
        async submitForm() {
            this.invoice.status = "NOT_STARTED_YET";
            try {
                let response = await axios.post('/invoices', this.invoice); // Adjust this endpoint to your API route
                alert('Invoice created successfully!');
                // You might want to reset the form or navigate the user to another page
            } catch (error) {
                console.error("Failed to create invoice:", error);
                alert('Failed to create invoice. Please check your inputs and try again.');
            }
        }
    }
};
</script>

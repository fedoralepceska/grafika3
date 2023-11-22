
<template>
    <div class="container">
        <div class="invoices-container">
            <div v-for="invoice in latestInvoices" :key="invoice.id" class="box">
                <div class="invoice-card">
                    <div class="invoice-details">
                        <div class="header">
                            <span class="order-number">Order PO: #{{ invoice.id }}</span>
                            <button class="flex items-center p-1" @click="viewInvoice(invoice.id)">
                                <i class="fa fa-eye bg-gray-300 p-2 rounded" aria-hidden="true"></i>
                            </button>
                            <!-- Add other header details here -->
                        </div>
                        <div class="body">
                            <div class="info">
                                <p><strong>Order:</strong> #{{ invoice.id }}</p>
                                <!-- Add other invoice details here -->
                            </div>
                            <div class="info">
                                <p><strong>Created By:</strong> {{ invoice.user.name }}</p>
                                <!-- Add other invoice details here -->
                            </div>
                            <div class="info">
                                <p><strong>Client:</strong> {{ invoice.client.name }}</p>
                                <!-- Add other invoice details here -->
                            </div>
                        </div>
                        <div class="status" :style="{ background: statusColor(invoice.status) }">
                            <p>{{ invoice.status }}</p>
                        </div>
                    </div>
                </div>
                <div class="job-images">
                    <img v-for="job in invoice.jobs" :src="getImageUrl(invoice.id, job.id)">
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'LatestOrders',
    data() {
        return {
            latestInvoices: [],
            jobImages: []
        }
    },
    methods: {
        fetchLatestInvoices() {
            axios.get('/invoices/latest')
                .then(response => {
                    this.latestInvoices = response.data;
                    // Assuming each invoice has a 'jobs' array with images
                    this.jobImages = this.latestInvoices.flatMap(invoice => invoice.jobs.map(job => job.imageData));
                })
                .catch(error => console.error('Error fetching invoices:', error));
        },
        getImageUrl(invoiceId, id) {
            const invoice = this.latestInvoices.find(i => i.id === invoiceId);
            console.log(invoice);
            return `/storage/uploads/${invoice.jobs.find(j => j.id === id).file}`
        },
        statusColor(status) {
            switch(status) {
                case 'Not started yet':
                    return "#a36a03"
                case 'In progress':
                    return '#0073a9'
                case 'Completed':
                    return '#408a0b'
            }
        },
        viewInvoice(id) {
            this.$inertia.visit(`/invoices/${id}`);
        },
    },
    mounted() {
        this.fetchLatestInvoices();
    }
}
</script>

<style scoped lang="scss">
.container {
    display: flex;
    flex-direction: column;
    width: max-content;
}

.invoices-container {
    display: flex;
    flex-direction: column;
}

.invoice-card {
    align-items: center;
    background: $dark-gray;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    border-radius: 2px;
    overflow: hidden;
    width: max-content;
}

.invoice-details {
    width: 200px;
    flex-grow: 1;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: $white;
    padding: 12px;
}

.order-number {
    font-weight: bold;
}

.info p, .status p {
    margin: 0.25rem 0;
}

.job-images {
    display: flex;
    align-items: center;
    justify-content: center;
}

.job-images img {
    width: 200px; /* Adjust as needed */
    height: 120px; /* Adjust as needed */
    margin: 0 1rem;
}
.box {
    display: flex;
    flex-direction: row;
    margin-bottom: 1rem;
}

.body {
    padding: 1rem;
    color: $white;
}

.status {
    color: $white;
    padding: 0.5rem;
}
</style>

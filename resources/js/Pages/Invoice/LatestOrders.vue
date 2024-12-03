
<template>
    <div class="container">
        <div class="invoices-container">
            <div v-for="invoice in latestInvoices" :key="invoice.id" class="box">
                <div class="invoice-card">
                    <div class="invoice-details">
                        <div class="header">
                            <span class="order-number">{{ invoice.invoice_title }}</span>
                            <!-- Add other header details here -->
                        </div>
                        <div class="body flex">
                            <div class="left">
                                <div class="info">
                                    <p><strong>Order:</strong> #{{ invoice.id }}</p>
                                    <!-- Add other invoice details here -->
                                </div>
                                <div class="info">
                                    <p><strong>Client:</strong> {{ invoice.client.name }}</p>
                                    <!-- Add other invoice details here -->
                                </div>
                                <div class="info">
                                    <p><strong>Created By:</strong> {{ invoice.user.name }}</p>
                                    <!-- Add other invoice details here -->
                                </div>
                            </div>
                            <div class="info">
                                <p><strong>Start Date:</strong> {{ invoice.start_date }}</p>
                                <p><strong>End Date:</strong> {{ invoice.end_date }}</p>
                            </div>
                        </div>
                        <div class="status flex" :style="{ background: statusColor(invoice.status) }">
                            <p>{{ invoice.status }}</p>
                            <button @click="viewInvoice(invoice.id)">
                                <i class="fa fa-eye bg-gray-50 p-2 rounded text-black" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="image-box">
                    <div class="job-images">
                        <img v-for="job in invoice.jobs" :src="getImageUrl(invoice.id, job.id)">
                    </div>
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
            axios.get('/orders/latest')
                .then(response => {
                    this.latestInvoices = response.data;
                    // Assuming each invoice has a 'jobs' array with images
                    this.jobImages = this.latestInvoices.flatMap(invoice => invoice.jobs.map(job => job.imageData));
                })
                .catch(error => console.error('Error fetching invoices:', error));
        },
        getImageUrl(invoiceId, id) {
            const invoice = this.latestInvoices.find(i => i.id === invoiceId);
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
            this.$inertia.visit(`/orders/${id}`);
        },
    },
    mounted() {
        this.fetchLatestInvoices();
    }
}
</script>

<style scoped lang="scss">
.container {
    width: 90%;
    display: flex;
    flex-direction: column;
    background-color: $dark-gray;
}

.invoices-container {
    display: flex;
    flex-direction: column;
    width: calc(100% - 20vh);
}

.invoice-card {
    align-items: center;
    background: $light-gray;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    border-radius: 2px;
    overflow: hidden;
    min-width: 50vh;
}

.invoice-details {
    min-width: 40vh;
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
}
.image-box{
    margin-left: 40px;
    background-color: $light-gray;
    overflow: auto;
    min-width: 200px;
    white-space: nowrap;
    padding: 10px;
}
.image-box img{
    padding: 5px;
}
.job-images img {
    height: 180px;
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
    justify-content: space-between;
}
</style>

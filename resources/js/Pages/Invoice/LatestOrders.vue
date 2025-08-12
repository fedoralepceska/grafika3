
<template>
    <div class="container">
        <div class="invoices-container">
            <div v-for="invoice in latestInvoices" :key="invoice.id" class="box" @click="selectInvoice(invoice)">
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
                            <button @click.stop="viewInvoice(invoice.id)">
                                <i class="fa fa-eye bg-gray-50 p-2 rounded text-black" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="image-box">
                    <div class="job-images">
                        <img v-for="job in invoice.jobs" :key="job.id" :src="getImageUrl(invoice.id, job.id)">
                    </div>
                </div>
            </div>
        </div>

        <aside class="order-sidebar">
            <div v-if="selectedInvoice" class="sidebar-content">
                <div class="sidebar-header">
                    <h3 class="sidebar-title">Order: #{{ selectedInvoice.id }}</h3>
                    <div class="mt-1 mb-1 px-2 rounded-full" :class="statusClass(selectedInvoice.status)">
                        {{ selectedInvoice.status }}
                    </div>
                </div>
                <div class="detail-info">
                    <div><strong>Title:</strong> {{ selectedInvoice.invoice_title }}</div>
                    <div><strong>Client:</strong> {{ selectedInvoice.client?.name }}</div>
                </div>
                <div class="detail-info">
                    <div><strong>Start Date:</strong> {{ selectedInvoice.start_date }}</div>
                    <div><strong>End Date:</strong> {{ selectedInvoice.end_date }}</div>
                </div>
            </div>
            <div v-else class="sidebar-placeholder">
                <i class="fa-solid fa-info-circle"></i>
                <p>Select an order to view details</p>
            </div>
        </aside>
    </div>
</template>

<script>
export default {
    name: 'LatestOrders',
    data() {
        return {
            latestInvoices: [],
            jobImages: [],
            selectedInvoice: null,
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
        selectInvoice(invoice) {
            this.selectedInvoice = invoice;
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
        statusClass(status) {
            switch (status) {
                case 'Not started yet':
                    return 'status-not-started';
                case 'In progress':
                    return 'status-in-progress';
                case 'Completed':
                    return 'status-completed';
                default:
                    return 'status-default';
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
    width: 100%;
    display: flex;
    flex-direction: row;
    background-color: $dark-gray;
}

.invoices-container {
    display: flex;
    flex-direction: column;
    flex: 1 1 auto;
    min-width: 0;
    padding-right: 12px;
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

/* Sidebar reservation */
.order-sidebar {
    flex: 0 0 500px;
    background-color: $dark-gray;
    border: 1px solid rgba(255, 255, 255, 0.45);
    box-shadow: -10px 0 10px rgba(0, 0, 0, 0.7);
    max-height: 100%;
    overflow-y: auto;
}

.sidebar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid $light-gray;
    background-color: $light-gray;
    padding: 0 0.4rem;
}

.sidebar-title {
    color: $white;
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0.2rem 0;
}

.sidebar-content {
    padding: 0.5rem;
}

.sidebar-placeholder {
    display: flex;
    height: 100%;
    min-height: 240px;
    align-items: center;
    justify-content: center;
    color: $ultra-light-gray;
    gap: 0.5rem;

    i { font-size: 1.2rem; }
    p { margin: 0; }
}

.detail-info {
    display: flex;
    flex-direction: row;
    justify-content: space-between;
    gap: 0.5rem;
    padding: 0.25rem 0.5rem;
    color: $white;

    strong { color: $ultra-light-gray; }
}

.status-not-started { background-color: #a36a03; color: $white; }
.status-in-progress { background-color: #0073a9; color: $white; }
.status-completed { background-color: #408a0b; color: $white; }
.status-default { background-color: $dark-gray; color: $white; }
</style>


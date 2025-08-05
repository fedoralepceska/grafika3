<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MainLayout from "@/Layouts/MainLayout.vue";
import { Head } from '@inertiajs/vue3';
import DashboardOrders from "@/Pages/Invoice/DashboardOrders.vue";
import StatusBox from "@/Components/StatusBox.vue";
</script>

<template>
    <Head title="Dashboard" />

    <MainLayout>
        <div class="status-boxes dark-gray">
            <StatusBox icon="fa-solid fa-gear fa-2xl" title="All jobs not shipped" number="0" color="#1497D5" />
            <StatusBox icon="fa-solid fa-cart-shopping fa-2xl" title="Entered today" :number="invoicesToday" color="#E6AE49" :border-color="dynamicColor2" :link="'/orders?start_date=' + getFormattedDate()"/>
            <StatusBox icon="fa-solid fa-truck fa-2xl" title="Shipping today" :number="invoicesShippingToday" color="#1497D5" :link="'/orders?end_date=' + getFormattedDate()"/>
            <StatusBox icon="fa-solid fa-truck fa-2xl" title="Shipping in 2 days" :number="invoicesShippingTomorrow" color="#E6AE49" :border-color="dynamicColor2" :link="'/orders?end_date=' + getFormattedDate(new Date(), true)"/>
            <StatusBox icon="fa-solid fa-triangle-exclamation fa-2xl" title="> 7 days old: NOT shipped" :number="invoicesSevenDays" color="#A53D3F" :border-color="dynamicColor" :link="'/orders?end_date=' + getFormattedDate()"/>
        </div>
        <div class="flex dark-gray" style="padding: 25px">
            <nav class="sidebar-menu dark-gray">
                <a href="/orders/create" class="menu-item">
                    <span class="fa-solid fa-plus"></span>
                    <span class="text">NEW ORDER</span>
                </a>
                <a href="/machines" class="menu-item">
                    <span class="fa-solid fa-gears"></span>
                    <span class="text">SELECT MACHINE</span>
                </a>
                <a href="/clients/create" class="menu-item">
                     <span class="fa-solid fa-plus"></span>
                     <span class="text">NEW CUSTOMER</span>
                </a>
                <a href="/orders" class="menu-item">
                    <span class="fa-regular fa-folder"></span>
                    <span class="text">OM DASHBOARD</span>
                </a>
                <a href="/production" class="menu-item">
                    <span class="fa-solid fa-circle-info"></span>
                    <span class="text">PRODUCTION DASHBOARD</span>
                </a>
            </nav>
            <div class="pl-10 flex-1">
                <DashboardOrders></DashboardOrders>
            </div>
        </div>

    </MainLayout>
</template>
<script>
export default {
    data() {
        return {
            invoicesToday: 0,
            invoicesShippingToday: 0,
            invoicesSevenDays: 0,
            invoicesShippingTomorrow: 0,
            dynamicColor: '2px solid #A53D3F',
            dynamicColor2: '2px solid #E6AE49',
        }
    },
    created() {
        this.fetchInvoicesToday();
        this.fetchInvoicesShippingToday();
        this.fetchInvoicesShippingTomorrow();
        this.fetchInvoicesSevenDays();
    },
    methods: {
        fetchInvoicesToday() {
            axios.get('/orders/today/count')
                .then(response => {
                    this.invoicesToday = response.data.count; // Adjust according to the response structure
                })
                .catch(error => {
                    console.error('Error fetching the number of today\'s invoices:', error);
                    // Handle the error appropriately
                });
        },
        fetchInvoicesShippingToday() {
            axios.get('/orders/end-date/count')
                .then(response => {
                    this.invoicesShippingToday = response.data.count; // Adjust according to the response structure
                })
                .catch(error => {
                    console.error('Error fetching the number of today\'s invoices:', error);
                    // Handle the error appropriately
                });
        },
        fetchInvoicesShippingTomorrow() {
            axios.get('/orders/tomorrow/count')
                .then(response => {
                    this.invoicesShippingTomorrow = response.data.count; // Adjust according to the response structure
                })
                .catch(error => {
                    console.error('Error fetching the number of today\'s invoices:', error);
                    // Handle the error appropriately
                });
        },
        fetchInvoicesSevenDays() {
            axios.get('/orders/seven-days/count')
                .then(response => {
                    this.invoicesSevenDays = response.data.count; // Adjust according to the response structure
                })
                .catch(error => {
                    console.error('Error fetching the number of today\'s invoices:', error);
                    // Handle the error appropriately
                });
        },
        getFormattedDate(date = new Date(), shouldAddDay = false) {
            const tomorrow = new Date(date);
            if (shouldAddDay) {
                tomorrow.setDate(tomorrow.getDate() + 1); // Add 1 day if needed
            }
            const year = tomorrow.getFullYear();
            const month = String(tomorrow.getMonth() + 1).padStart(2, '0'); // Pad with leading zero
            const day = String(tomorrow.getDate()).padStart(2, '0'); // Pad with leading zero

            return `${year}-${month}-${day}`;
        }
    }
}
</script>
<style scoped lang="scss">
.status-boxes {
    display: flex;
    justify-content: space-around;
    padding: 15px;
    width: 100%;
    flex-wrap: wrap;
    gap: 8px;

    /* Responsive adjustments for status boxes container */
    @media (max-width: 639px) {
        padding: 8px;
        gap: 4px;
        flex-direction: column;
    }

    @media (min-width: 640px) and (max-width: 1023px) {
        padding: 12px;
        gap: 6px;
        flex-wrap: wrap;
    }

    @media (min-width: 1024px) {
        padding: 15px;
        gap: 8px;
    }
}

.sidebar-menu {
    background-color: $background-color; /* Dark background */
    color: white;
    list-style: none;
    width: fit-content;
}

.dark-gray{
    background-color: $dark-gray;
}

.menu-item {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 10px 30px;
    text-decoration: none;
    color: white;
    background-color: $light-gray; /* Slightly lighter than the sidebar for contrast */
    height: 100px;
    margin-bottom: 20px;
    transition: all 0.3s ease;

    /* Responsive menu items */
    @media (max-width: 639px) {
        padding: 8px 20px;
        height: 80px;
        margin-bottom: 15px;
    }

    @media (min-width: 640px) and (max-width: 1023px) {
        padding: 10px 25px;
        height: 90px;
        margin-bottom: 18px;
    }
}

.items{
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.menu-item .text {
    font-size: 1em;
    text-transform: uppercase;
    letter-spacing: 0.05em;

    /* Responsive text sizing */
    @media (max-width: 639px) {
        font-size: 0.8em;
    }

    @media (min-width: 640px) and (max-width: 1023px) {
        font-size: 0.9em;
    }
}

/* Hover effect */
.menu-item:hover {
    background-color: $ultra-light-gray;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.text {
    text-align: center;
    margin-left: 10px;
}

.flex {
    display: flex;
    flex-direction: row;

    /* Responsive flex layout */
    @media (max-width: 1023px) {
        flex-direction: column;
    }
}

/* Responsive padding for the main content area */
@media (max-width: 639px) {
    .flex {
        padding: 15px !important;
    }
}

@media (min-width: 640px) and (max-width: 1023px) {
    .flex {
        padding: 20px !important;
    }
}

@media (min-width: 1024px) {
    .flex {
        padding: 25px !important;
    }
}

/* Responsive sidebar menu */
@media (max-width: 1023px) {
    .sidebar-menu {
        width: 100%;
        margin-bottom: 20px;
    }
}

/* Responsive content area */
@media (max-width: 1023px) {
    .px-12 {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
}

/* Adjustments for responsive design or specific styling might be necessary. */
</style>

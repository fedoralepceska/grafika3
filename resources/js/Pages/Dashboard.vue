<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MainLayout from "@/Layouts/MainLayout.vue";
import { Head } from '@inertiajs/vue3';
import LatestOrders from "@/Pages/Invoice/LatestOrders.vue";
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
            <div class="px-12">
                <LatestOrders></LatestOrders>
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
}

/* Hover effect */
.menu-item:hover {
    background-color: $ultra-light-gray;
}
.text {
    text-align: center;
    margin-left: 10px;
}

.flex {
    display: flex;
    flex-direction: row;
}

/* Adjustments for responsive design or specific styling might be necessary. */

</style>

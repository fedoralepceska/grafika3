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
            <StatusBox icon="fa-solid fa-cart-shopping fa-2xl" title="Entered today" :number="invoicesToday" color="#E6AE49" :border-color="dynamicColor2" />
            <StatusBox icon="fa-solid fa-truck fa-2xl" title="Shipping today" number="0" color="#1497D5" />
            <StatusBox icon="fa-solid fa-triangle-exclamation fa-2xl" title="> 7 days old: NOT shipped" number="0" color="#A53D3F" :border-color="dynamicColor"/>
        </div>
        <div class="flex dark-gray" style="padding: 25px">
            <nav class="sidebar-menu dark-gray">
                <a href="/invoices/create" class="menu-item">
                    <span class="fa-solid fa-plus"></span>
                    <span class="text">NEW ORDER</span>
                </a>
                <a href="#" class="menu-item">
                    <span class="fa-solid fa-gears"></span>
                    <span class="text">SELECT MACHINE</span>
                </a>
                <a href="/clients/create" class="menu-item">
                     <span class="fa-solid fa-plus"></span>
                     <span class="text">NEW CUSTOMER</span>
                </a>
                <a href="/invoices" class="menu-item">
                    <span class="fa-regular fa-folder"></span>
                    <span class="text">OM DASHBOARD</span>
                </a>
                <a href="#" class="menu-item">
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
            dynamicColor: '2px solid #A53D3F',
            dynamicColor2: '2px solid #E6AE49'
        }
    },
    created() {
        this.fetchInvoicesToday();
    },
    methods: {
        fetchInvoicesToday() {
            axios.get('/invoices/today/count') // Replace with your actual API endpoint
                .then(response => {
                    this.invoicesToday = response.data.count; // Adjust according to the response structure
                })
                .catch(error => {
                    console.error('Error fetching the number of today\'s invoices:', error);
                    // Handle the error appropriately
                });
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
    margin-left: 10px;
}

.flex {
    display: flex;
    flex-direction: row;
}

/* Adjustments for responsive design or specific styling might be necessary. */

</style>

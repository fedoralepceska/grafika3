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
            <StatusBox inline compact stacked icon="fa-solid fa-gear" title="All jobs not shipped" number="0" color="#1497D5" />
            <StatusBox inline compact stacked icon="fa-solid fa-cart-shopping" title="Entered today" :number="invoicesToday" color="#E6AE49" :border-color="dynamicColor2" :link="'/orders?start_date=' + getFormattedDate()"/>
            <StatusBox inline compact stacked icon="fa-solid fa-truck" title="Shipping today" :number="invoicesShippingToday" color="#1497D5" :link="'/orders?end_date=' + getFormattedDate()"/>
            <StatusBox inline compact stacked icon="fa-solid fa-truck" title="Shipping in 2 days" :number="invoicesShippingTomorrow" color="#E6AE49" :border-color="dynamicColor2" :link="'/orders?end_date=' + getFormattedDate(new Date(), true)"/>
            <StatusBox inline compact stacked icon="fa-solid fa-triangle-exclamation" title="> 7 days old: NOT shipped" :number="invoicesSevenDays" color="#A53D3F" :border-color="dynamicColor" :link="'/orders?end_date=' + getFormattedDate()"/>
        </div>
        <div class="quick-actions dark-gray" role="navigation" aria-label="Quick actions">
            <a href="/orders/create" class="menu-item compact" aria-label="Create new order">
                <span class="fa-solid fa-plus"></span>
                <span class="text">NEW ORDER</span>
            </a>
            <a href="/machines" class="menu-item compact" aria-label="Select machine">
                <span class="fa-solid fa-gears"></span>
                <span class="text">SELECT MACHINE</span>
            </a>
            <a href="/clients/create" class="menu-item compact" aria-label="Create new customer">
                <span class="fa-solid fa-plus"></span>
                <span class="text">NEW CUSTOMER</span>
            </a>
            <a href="/orders" class="menu-item compact" aria-label="Operations dashboard">
                <span class="fa-regular fa-folder"></span>
                <span class="text">OM DASHBOARD</span>
            </a>
            <a href="/production" class="menu-item compact" aria-label="Production dashboard">
                <span class="fa-solid fa-circle-info"></span>
                <span class="text">PRODUCTION DASHBOARD</span>
            </a>
        </div>
        <div class="orders-wrap dark-gray">
            <DashboardOrders />
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
     display: grid;
     grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
     gap: 12px;
     padding: 12px 16px;
     width: 100%;
     align-items: stretch;
 }

 .sidebar-menu {
     background-color: $background-color;
     color: white;
     list-style: none;
     display: flex;
     flex-direction: column;
     width: 280px;
     padding: 12px;
    
     gap: 12px;
 }

 /* Horizontal quick actions bar */
 .quick-actions {
     display: grid;
     grid-auto-flow: column;
     grid-auto-columns: max-content;
     gap: 12px;
     padding: 12px 16px;
     align-items: center;
     overflow-x: auto;
     scrollbar-width: thin;
 }

 .quick-actions .menu-item.compact {
     height: 48px;
     padding: 8px 14px;
     border-radius: 9999px;
     background-color: transparent;
     border: 1.5px solid rgba(255, 255, 255, 0.18);
     display: inline-flex;
     flex-direction: row;
     align-items: center;
     gap: 10px;
     box-shadow: none;
 }

 .quick-actions .menu-item.compact .text {
     font-size: 0.9em;
     letter-spacing: 0.03em;
 }

 .quick-actions .menu-item.compact:hover {
     background-color: rgba(255, 255, 255, 0.08);
     border-color: rgba(255, 255, 255, 0.26);
     transform: none;
     box-shadow: none;
 }

.orders-wrap { 
    padding: 10px 16px 24px; 
}

.dark-gray{
    background-color: $dark-gray;
}

 .menu-item {
     display: flex;
     flex-direction: column;
     justify-content: center;
     align-items: center;
     padding: 12px 24px;
     text-decoration: none;
     color: white;
     background-color: $light-gray;
     height: 100px;
     box-shadow: 0 1px 2px rgba(0, 0, 0, 0.15);
     transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
     cursor: pointer;

     @media (max-width: 639px) {
         height: 84px;
     }

     @media (min-width: 640px) and (max-width: 1023px) {
         height: 92px;
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

 /* Hover/focus/active effects */
 .menu-item:hover {
     background-color: $ultra-light-gray;
     transform: translateY(-2px);
     box-shadow: 0 6px 14px rgba(0, 0, 0, 0.25);
 }
 .menu-item:focus-visible {
     outline: 2px solid lighten($light-gray, 10%);
     outline-offset: 3px;
 }
 .menu-item:active {
     transform: translateY(0);
     box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
 }

.text {
    text-align: center;
    margin-left: 10px;
}

 .flex {
     display: flex;
     flex-direction: row;

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
         display: grid;
         grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
         gap: 12px;
     }
 }

 /* Respect reduced motion preferences */
 @media (prefers-reduced-motion: reduce) {
     .menu-item {
         transition: none;
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

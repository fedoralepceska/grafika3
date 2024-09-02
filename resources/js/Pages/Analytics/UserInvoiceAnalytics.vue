<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="analyticsInvoice" subtitle="dashboard" icon="dashboard.png" link="analytics/invoice"/>
            <UserInvoiceChart />
        </div>
    </MainLayout>
</template>

<script>
import UserInvoiceChart from "@/Pages/Analytics/UserInvoiceChart.vue";
import MainLayout from "@/Layouts/MainLayout.vue";
import Header from "@/Components/Header.vue";

export default {
    components: {
        UserInvoiceChart,
        Header,
        MainLayout
    },
};
</script>

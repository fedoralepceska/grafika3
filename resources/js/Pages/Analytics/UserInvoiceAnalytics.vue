<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="analyticsInvoice" subtitle="dashboard" icon="dashboard.png" link="analytics/invoice"/>
            <UserInvoiceChart />
            <ArticleInvoiceChart />
        </div>
    </MainLayout>
</template>

<script>
import UserInvoiceChart from "@/Pages/Analytics/UserInvoiceChart.vue";
import MainLayout from "@/Layouts/MainLayout.vue";
import Header from "@/Components/Header.vue";
import ArticleInvoiceChart from "@/Pages/Analytics/ArticleInvoiceChart.vue";

export default {
    components: {
        ArticleInvoiceChart,
        UserInvoiceChart,
        Header,
        MainLayout
    },
};
</script>

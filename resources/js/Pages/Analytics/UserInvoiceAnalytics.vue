<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="analyticsInvoice" subtitle="dashboard" icon="dashboard.png" link="analytics"/>
            <div class="dark-gray p-5 text-white">
                <div class="form-container light-gray">
                    <UserInvoiceChart />
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script>
import UserInvoiceChart from "@/Pages/Analytics/Charts/UserInvoiceChart.vue";
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
<style scoped lang="scss">
.dark-gray{
    background-color: $dark-gray;
}
.light-gray{
    background-color: $light-gray;
}
</style>

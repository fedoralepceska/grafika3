<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="analyticsInvoice" subtitle="dashboard" icon="dashboard.png" link="analytics"/>
            <div class="dark-gray p-5 text-white">
                <div class="form-container light-gray">
                    <UserInvoiceTimeSpent />
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script>
import MainLayout from "@/Layouts/MainLayout.vue";
import Header from "@/Components/Header.vue";
import UserInvoiceTimeSpent from "@/Pages/Analytics/Charts/UserInvoiceTimeSpent.vue";

export default {
    components: {
        UserInvoiceTimeSpent,
        Header,
        MainLayout
    },
};
</script>

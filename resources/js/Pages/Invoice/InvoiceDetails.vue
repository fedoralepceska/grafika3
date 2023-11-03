<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <div class="header pt-3 pb-4">
                <div class="left mr-3">
                    <img src="/images/List.png" alt="InvoiceLogo" class="image-icon" />
                </div>
                <div class="right">
                    <h1 class="page-title">{{ $t('invoice') }}</h1>
                    <h3 class="text-white">
                        <span class="green-text">{{ $t('invoice') }}</span> / {{ $t('ViewAllInvoices') }}
                    </h3>
                </div>
            </div>
            <div class="dark-gray p-2 text-white">
                <div class="form-container p-2">
                    <h2 class="sub-title">{{ $t('Invoice Details') }}</h2>
                    <div class="border p-2">
                        <div class="invoice-details">
                            <div class="invoice-title">{{ selectedInvoice.invoice_title }}</div>
                            <div class="bold">{{ selectedInvoice.client.name }}</div>
                            <div class="bold">#{{ selectedInvoice.id }}</div>
                            <div>
                                {{ $t('Start Date') }}: {{ selectedInvoice.start_date }}
                            </div>
                            <div>
                                {{ $t('End Date') }}: {{ selectedInvoice.end_date }}
                            </div>
                            <div>
                                {{ $t('Status') }}: <span :class="getStatusColorClass(selectedInvoice.status)">{{ selectedInvoice.status }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script>
import MainLayout from "@/Layouts/MainLayout.vue";

export default {

    components: { MainLayout },
    computed: {
        getStatusColorClass() {
            return (status) => {
                if (status === "Not started yet") {
                    return "orange";
                } else if (status === "In Progress") {
                    return "blue-text";
                } else if (status === "Completed") {
                    return "green-text";
                }
            };
        },
    },
    props: {
        selectedInvoice: Object,
    },
};
</script>

<style scoped lang="scss">
.orange {
    color: $orange;
}
.blue-text {
    color: $blue;
}
.bold {
    font-weight: bold;
}
.green-text {
    color: $green;
}
.header {
    display: flex;
    align-items: center;
}
.dark-gray {
    background-color: $dark-gray;
    justify-content: left;
    align-items: center;
    min-height: 20vh;
    min-width: 80vh;
}
.page-title {
    font-size: 24px;
    display: flex;
    align-items: center;
    color: $white;
}
.sub-title {
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    color: $white;
}

.image-icon {
    margin-left: 2px;
    max-width: 40px;
}
</style>

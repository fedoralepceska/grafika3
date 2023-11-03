<template>
    <MainLayout>
        <div class="pl-7 pr-7">

            <div class="header pt-3 pb-4">
                <div class="left mr-3">
                    <img src="/images/List.png" alt="InvoiceLogo" class="image-icon" />
                </div>
                <div class="right">
                    <h1 class="page-title">{{ $t('invoice') }}</h1>
                    <h3 class="text-white"> <span class="green-text">{{ $t('invoice') }}</span> / {{ $t('ViewAllInvoices') }}</h3>
                </div>
            </div>
            <div class="dark-gray p-2 text-white">
                <div class="form-container p-2 ">
                    <h2 class="sub-title">
                        {{ $t('listOfAllInvoices') }}
                    </h2>
                    <div class="border mb-1" v-for="invoice in invoices" :key="invoice.id">
                        <div class="bg-white text-black flex justify-between">
                            <div class="p-2 bold">{{invoice.invoice_title}}</div>
                            <button class="flex items-center p-1" @click="viewInvoice(invoice.id)">
                            <i class="fa fa-eye bg-gray-300 p-2 rounded" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div class="flex gap-40 p-2">
                            <div class="info">
                                <div>Order</div>
                                <div class="bold">#{{invoice.id}}</div>
                            </div>
                            <div class="info">
                                <div>Customer</div>
                                <div class="bold">{{clientNames[invoice.id]}}</div>
                            </div>
                            <div class="info">
                                <div>End Date</div>
                                <div  class="bold">{{invoice.end_date}}</div>
                            </div>
                            <div class="info">
                                <div>Created By</div>
                                <div  class="bold">{{ userNames[invoice.id] }}</div>
                            </div>
                            <div class="info">
                                <div>Status</div>
                                <div :class="getStatusColorClass(invoice.status)" class="bold" >{{invoice.status}}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script>
import { ref, onMounted } from "vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import axios from "axios";
import MainLayout from "@/Layouts/MainLayout.vue";
import { useRouter } from 'vue-router';
export default {
    components: { MainLayout, SecondaryButton, PrimaryButton },
    props: {
        invoices: Array,
    },
    setup(props) {
        const userNames = ref({});
        const clientNames = ref({});
        const router = useRouter();

        const viewInvoice = (id) => {
            router.push(`/invoices/${id}`);
        };

        const fetchUserNames = async () => {
            await Promise.all(
                props.invoices.map(async (invoice) => {
                    const response = await get_user(invoice);
                    userNames.value[invoice.id] = response.name;
                })
            );
        };
        const fetchClientNames = async () => {
            await Promise.all(
                props.invoices.map(async (invoice) => {
                    const response = await get_client(invoice);
                    clientNames.value[invoice.id] = response.name;
                })
            );
        };
        const get_client = async (invoice) => {
            const response = await axios.post("/get-client", { id: invoice.client_id });
            return response.data;
        };


        const get_user = async (invoice) => {
            const response = await axios.post("/get-user", { id: invoice.created_by });
            return response.data;
        };

        onMounted(() => {
            fetchUserNames();
            fetchClientNames();
        });



        return { userNames, clientNames,viewInvoice  };
    },
    methods: {
        getStatusColorClass(status) {
            if (status === "Not started yet") {
                return "orange";
            } else if (status === "In Progress") {
                return "blue-text";
            } else if (status === "Completed") {
                return "green-text";
            }
        },
    },
};
</script>
<style scoped lang="scss">
.orange{
    color: $orange;
}
.blue-text{
    color: $blue;
}
.bold{
    font-weight: bold;
}
.green-text{
    color: $green;
}
.blue{
    background-color: $blue;
}
.green{
    background-color: $green;
}
.green:hover{
    background-color: green;
}
.header{
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
.ultra-light-gray{
    background-color: $ultra-light-gray;
}
.page-title {
    font-size: 24px;
    display: flex;
    align-items: center;
    color: $white;
}
.sub-title{
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

.button-container{
    display: flex;
    justify-content: end;
}
</style>


<template>
    <MainLayout>
        <div class="pl-7 pr-7 flex">
            <div class="sidebar" v-if="isSidebarVisible">
                <button @click="toggleSidebar" class="close-sidebar">
                    <span class="mdi mdi-close"></span>
                </button>
            </div>
            <div class="left-column flex-1" style="width: 25%">
                <div class="flex justify-between">
                    <Header title="invoice2" subtitle="invoiceGeneration" icon="invoice.png" link="notInvoiced"/>
                    <div class="flex pt-4">
                        <div class="buttons pt-3 flex pr-2">
                            <input type="text" id="comment" class="rounded" v-model="comment" placeholder="Add Invoice Comment" style="width: 500px; height: 45px"/>
                        </div>
                        <div class="buttons pt-3">
                            <button class="btn comment-order" @click="toggleSpreadsheetMode">
                                {{ spreadsheetMode ?  'Edit' : 'Exit Edit Mode' }}
                                <i class="fa-regular fa-edit"></i>
                            </button>
                            <button  class="btn generate-invoice" @click="generateInvoice">Generate Invoice <i class="fa-solid fa-file-invoice-dollar"></i></button>
                        </div>
                    </div>
                </div>
                <div class="dark-gray p-5 text-white" v-for="(invoiceData, invoiceId) in invoiceData" :key="invoiceId">
                    <div class="form-container p-2 light-gray">
                        <div class="InvoiceDetails">
                            <div class="invoice-details flex gap-20 relative" >
                                <div class="invoice-title bg-white text-black bold p-3 ">{{ invoiceData?.invoice_title }}</div>
                                <div class="info">
                                    <div>Order</div>
                                    <div class="bold">#{{ invoiceData?.id }}</div>
                                </div>
                                <div class="info">
                                    <div>Customer</div>
                                    <div class="bold">{{ invoiceData.client }}</div>
                                </div>
                                <div class="info">
                                    <div>{{ $t('startDate') }}</div>
                                    <div class="bold">{{ invoiceData?.start_date }}</div>
                                </div>
                                <div class="info">
                                    <div>{{ $t('endDate') }}</div>
                                    <div class="bold">{{ invoiceData?.end_date }}</div>
                                </div>
                                <div class="info">
                                    <div>Created By</div>
                                    <div class="bold">{{ invoiceData.user }}</div>
                                </div>
                                <div class="info">
                                    <div>Status</div>
                                    <span class="bold green-text">{{ invoiceData.status }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-container  light-gray mt-2">
                        <div class="sub-title pl-2 ">{{$t('orderLines')}}</div>
                        <div v-for="(job, index) in invoiceData.jobs" v-if="spreadsheetMode">
                            <div class="jobDetails p-2">
                                <div class="border">
                                    <div class="flex gap-10 pb-3">
                                        <div class="invoice-title bg-white text-black bold p-3">
                                            #{{index+1}} {{job.name}}
                                        </div>
                                            <img :src="`/storage/uploads/${job.file}`" alt="Job Image" class="jobImg thumbnail"/>
                                        <div>{{job.file}}</div>
                                        <div>{{$t('height')}}: <span class="bold">{{job.height.toFixed(2)}}</span> </div>
                                        <div>{{$t('width')}}: <span class="bold">{{job.width.toFixed(2)}}</span> </div>
                                        <div>{{$t('quantity')}}: <span class="bold">{{job.quantity}}</span> </div>
                                        <div>{{$t('copies')}}: <span class="bold">{{job.copies}}</span> </div>
                                        <div>
                                            {{$t('material')}}:
                                            <span class="bold">
                                            <span v-if="job.large_material_id">{{ job.large_material?.name }}</span>
                                            <span v-else>{{ job?.small_material?.name }}</span>
                                         </span>
                                        </div>
                                        <div>{{$t('totalm')}}<sup>2</sup>: <span class="bold">{{(job.height * job.width / 1000).toFixed(2)}}</span></div>
                                    </div>
                                        <OrderJobDetails :job="job"/>
                                    <div class="jobInfo relative pt-3">
                                        <div class="jobShippingInfo">
                                            <div class=" bg-white text-black bold ">
                                                <div class="flex" style="align-items: center;">
                                                    <img src="/images/shipping.png" class="w-10 h-10 pr-1" alt="Shipping">
                                                    {{$t('Shipping')}}
                                                </div>
                                                <div class="ultra-light-gray p-2 text-white">
                                                    {{$t('shippingTo')}}: <span class="bold">{{job.shippingInfo}}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="jobPriceInfo absolute right-0 bottom-10 bg-white text-black bold">
                                            <div class="p-2">
                                                {{$t('salePrice')}}: <span class="bold">{{job.salePrice?.toFixed(2)}} ден.</span>
                                            </div>
                                        </div>
                                        <div class="jobPriceInfo absolute right-0 bottom-0 bg-white text-black bold">
                                            <div class="p-2">
                                                {{$t('jobPrice')}}: <span class="bold">{{job.totalPrice.toFixed(2)}} ден.</span>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-center" v-else>
                            <OrderSpreadsheet :invoice="invoiceData"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script>
import MainLayout from "@/Layouts/MainLayout.vue";
import axios from "axios";
import Toast, {useToast} from "vue-toastification";
import OrderJobDetails from "@/Pages/Invoice/OrderJobDetails.vue";
import OrderSpreadsheet from "@/Components/OrderSpreadsheet.vue";
import Header from "@/Components/Header.vue";

export default {
    components: {
        OrderSpreadsheet,
        OrderJobDetails,
        MainLayout,
        Header },
    props: {
        invoiceData: Object,
    },
    computed:{
        getStatusColorClass() {
            const invoiceStatus = this.invoiceData.status;
            if (invoiceStatus === "Not started yet") {
                return "orange-text";
            } else if (invoiceStatus === "In progress") {
                return "blue-text";
            } else if (invoiceStatus === "Completed") {
                return "green-text";
            }
        },
    },
    data() {
        return {
            showImagePopover: false,
            selectedJob: null,
            isSidebarVisible: false,
            spreadsheetMode:true,
            backgroundColor: null,
            openDialog: false
        }
    },
    methods: {
        toggleSidebar() {
            this.isSidebarVisible = !this.isSidebarVisible;
        },
        toggleSpreadsheetMode(){
            this.spreadsheetMode = !this.spreadsheetMode;
        },
        generatePdf(invoiceId) {
            window.open(`/orders/${invoiceId}/pdf`, '_blank');
        },
        async generateInvoice() {
            const toast = useToast();
            try {
                // Your array of order ids
                // These are the main orders, naming is strange
                const orderIds = Object.values(this.invoiceData).map(order => order.id);
                const comment = this.comment;
                // Send a POST request to your Laravel backend
                const response = await axios.post('/generate-invoice', {
                    orders: orderIds,
                    comment: comment
                });

                if (response.data.invoice_id) {
                    this.$inertia.visit(`/invoice/${response.data.invoice_id}`);
                }

                // Handle successful response here (if needed)
                toast.success('Invoice generated successfully');
            } catch (error) {
                // Handle errors here (if needed)
                console.log(error);
                toast.error('Error generating invoice!');
            }
        }
    },
};
</script>

<style scoped lang="scss">
.circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
}
.flexed{
    justify-content: center;
    align-items: center;
}
.popover-content[data-v-19f5b08d]{
    background-color: #2d3748;
}
.fa-close::before{
    color: white;
}
[type='checkbox']:checked{
    border: 1px solid white;
}
.orange-text {
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
.light-gray{
    background-color: $light-gray;
}
.ultra-light-gray{
    background-color: $ultra-light-gray;
}
.blue{
    background-color: $blue;
}
.green{
    background-color: $green;
}
.background{
    background-color: $background-color;
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
.sub-title {
    font-size: 20px;
    font-weight: bold;
    display: flex;
    align-items: center;
    color: $white;
}
.jobShippingInfo{
    max-width: 300px;
    border: 1px solid  ;
}
.jobPriceInfo{
    max-height: 40px;
    max-width: 30%;
}
.right{
    gap: 34.9rem;
}
.btn {
    margin-right: 4px;
    padding: 10px 15px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    border-radius: 2px;
}
.btn2{
    font-size: 14px;
    margin-right: 4px;
    padding: 7px 10px;
    border: none;
    cursor: pointer;
    color: white;
    background-color: $blue;
    border-radius: 2px;
}
.btns{
    position: absolute;
    top: -11px;
    right: 0;
    padding: 0;
}
.comment-order,{
    background-color: $blue;
    color: white;
}
.generate-invoice{
    background-color: $green;
    color: white;
}
.InvoiceDetails{
    border-bottom: 2px dashed lightgray;
}
.bt{
    font-size:45px ;
    cursor: pointer;
    padding: 0;
}
.popover {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1000; /* high z-index to be on top of other content */
}

.popover-content {
    width: 30%;
    background: white;
    padding: 20px;
    border-radius: 8px;
    position: relative;
}

.popover-close {
    position: absolute;
    top: 10px;
    right: 10px;
    color: black;
}

.right-column {
    background-color: $background-color;
    color: white;
    overflow-y: auto;
}

.hamburger {
    z-index: 2000;
    background-color: transparent;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #fff; /* Adjust the color to match your layout */
}

.sidebar {
    position: fixed;
    top: 0;
    right: 0;
    bottom: 0;
    width: 350px; /* Width of sidebar */
    background-color: $background-color; /* Sidebar background color */
    z-index: 1000; /* Should be below the overlay */
    overflow-y: auto;
    padding: 20px;
    border: 1px solid $white;
    border-right:none;
    border-radius: 4px 0 0 4px ;
}

.order-history {
    padding: 20px;
}

.close-sidebar {
    position: absolute;
    top: 10px;
    right: 10px;
    background: transparent;
    border: none;
    font-size: 24px;
    color: #fff; /* Adjust close button color */
    cursor: pointer;
}

.is-blurred {
    filter: blur(5px);
}

.content {
    transition: filter 0.3s; /* Smooth transition for the blur effect */
}

.history-subtitle {
    background-color: white;
    color: black;
    padding: 10px;
    margin-bottom: 10px;
    font-weight: bold;
}
.jobImg {
    width: 45px;
    height: 45px;
}
/*
spreadheet style
*/
table {
    width: 100%;
    border-collapse: collapse;
    border-spacing: 0;
    margin-bottom: 20px;
    border: 1px solid #ddd;
    border-radius: 5px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

table th, table td {
    padding: 10px;
    border: 1px solid #ddd;
    text-align: center;
}

table th {
    color: white;
    border-top: 1px solid #ddd;
    border-bottom: 1px solid #ddd;
    background-color: $ultra-light-gray;

}
</style>

<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <div class="header pt-3 pb-4">
                <div class="left mr-3">
                    <img src="/images/List.png" alt="InvoiceLogo" class="image-icon" />
                </div>
                <div class="flex  right">
                    <div class="heading">
                        <h1 class="page-title">{{ $t('invoice') }}</h1>
                        <h3 class="text-white">
                            <span class="green-text">{{ $t('invoice') }}</span> / {{ $t('InvoiceReview') }}
                        </h3>
                    </div>
                    <div class="buttons">
                        <button class="btn download-order">Download All Proofs <span class="mdi mdi-cloud-download"></span></button>
                        <button class="btn lock-order">Lock Order <span class="mdi mdi-lock"></span></button>
                        <button class="btn re-order">Re-Order <span class="mdi mdi-content-copy"></span></button>
                        <button class="btn go-to-steps">Go To Steps <span class="mdi mdi-arrow-right-bold-outline"></span> </button>
                    </div>
                </div>
            </div>
            <div class="dark-gray p-5 text-white">
                <div class="form-container p-2 light-gray">
                    <div class="border">
                        <div class="invoice-details flex gap-20">
                            <div class="invoice-title bg-white text-black bold p-3 ">{{ invoice?.invoice_title }}</div>
                            <div class="info">
                                <div>Order</div>
                                <div class="bold">#{{ invoice?.id }}</div>
                            </div>
                            <div class="info">
                                <div>Customer</div>
                                <!--Here we should implement get client by id since the invoice only stores the client id-->
                                <!--<div class="bold">{{ invoice?.client?.name }}</div>-->
                            </div>
                            <div class="info">
                                <div>{{ $t('Start Date') }}</div>
                                <div class="bold">{{ invoice?.start_date }}</div>
                            </div>
                            <div class="info">
                                <div>{{ $t('End Date') }}</div>
                                <div class="bold">{{ invoice?.end_date }}</div>
                            </div>
                            <div class="info">
                                <div>Created By</div>
                                <div></div>
                            </div>
                        </div>
                        <div class="info pl-2">
                            <div>{{ $t('Status') }}</div>
                            <div>
                                <span class="bold" :class="getStatusColorClass(invoice?.status)">{{ invoice?.status }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-container  light-gray mt-2">
                    <div class="sub-title pl-2 ">{{$t('OrderLines')}}</div>
                    <div class="jobDetails p-2">
                        <div v-for="(job,index) in invoice.jobs" class="border">
                           <div class=" flex gap-10">
                               <div class="invoice-title bg-white text-black bold pl-3 pr-3">
                                   #{{index+1}} {{job.name}}
                               </div>
                               <img :src="getImageUrl(job.id)" alt="Job Image" class="jobImg thumbnail" />
                               <div>{{job.file}}</div>
                               <div>{{$t('Height')}}: <span class="bold">{{job.height}}</span> </div>
                               <div>{{$t('Width')}}: <span class="bold">{{job.width}}</span> </div>
                               <div>{{$t('Quantity')}}: <span class="bold">{{job.quantity}}</span> </div>
                               <div>{{$t('Copies')}}: <span class="bold">{{job.copies}}</span> </div>
                           </div>
                            <div class="flex p-2 gap-10">
                                <div class="">
                                    {{$t('Material')}}:
                                    <span class="bold">
                                        <span v-if="job.materials">{{job.materials}}</span>
                                        <span v-else>{{job.materialsSmall}}</span>
                                     </span>
                                </div>
                                <div>{{$t('totalm')}}<sup>2</sup>: <span class="bold">{{job.height * job.width}}</span></div>
                            </div>

                            <div class="jobInfo flex justify-between">
                                <div class="jobShippingInfo">
                                    <div class=" bg-white text-black bold ">
                                        <div class="flex" style="align-items: center;">
                                            <img src="/images/shipping.png" class="w-10 h-10 pr-1" alt="Shipping">
                                            {{$t('Shipping')}}
                                        </div>
                                            <div class="ultra-light-gray p-2">
                                                {{$t('shippingTo')}}: <span class="bold">{{job.shippingInfo}}</span>
                                            </div>
                                    </div>
                                </div>
                                <div class="jobPriceInfo bg-white text-black bold">
                                    <div class="p-2">
                                    {{$t('jobPrice')}}: <span class="bold">{{job.pricePerUnit*job.quantity}}.ден</span>
                                    </div>
                                </div>
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
import axios from "axios";

export default {

    components: { MainLayout },
    computed: {
        getStatusColorClass() {
            console.log(this.invoice);
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
    methods:{
        getImageUrl(id) {
            return `/storage/uploads/${this.invoice.jobs.find(j => j.id === id).file}`
        }
    },
    props: {
        invoice: Object,
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
.light-gray{
    background-color: $light-gray;
}
.ultra-light-gray{
    background-color: $ultra-light-gray;
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
    max-width: 180px;
}
.image-icon {
    margin-left: 2px;
    max-width: 40px;
}
.right{
    gap: 34.5rem;
}
.btn {
    margin-right: 10px;
    padding: 10px 15px;
    border: none;
    cursor: pointer;
    font-weight: bold;
}

.lock-order, .download-order, .re-order{
    background-color: $blue;
    color: white;
}
.go-to-steps{
    background-color: $orange;
    color: white;
}

</style>

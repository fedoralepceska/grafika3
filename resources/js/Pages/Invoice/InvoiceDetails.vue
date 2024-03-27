<template>
    <MainLayout>
        <div class="pl-7 pr-7 flex">
            <div class="sidebar" v-if="isSidebarVisible">
                <!-- Your sidebar content goes here -->
                <button @click="toggleSidebar" class="close-sidebar">
                    <span class="mdi mdi-close"></span>
                </button>
                <div class="right-column">
                    <div class="order-history">
                        <OrderHistory :invoice="invoice"/>
                    </div>
                </div>
            </div>
            <div class="left-column flex-1" style="width: 25%">
                <div class="flex justify-between">
                    <Header title="invoice" subtitle="InvoiceReview" icon="List.png" link="orders"/>
                    <div class="flex pt-4">
                        <div class="buttons pt-3">
                            <button class="btn download-order" @click="downloadAllProofs">Download All Proofs <span class="mdi mdi-cloud-download"></span></button>
                            <button v-if="!invoice.LockedNote" class="btn"><AddLockNoteDialog :invoice="invoice"/></button>
                            <button v-if="invoice.LockedNote" class="btn lock-order" @click="unlockOrder(invoice.id)">Unlock Order <span class="mdi mdi-lock-open"></span></button>
                            <button class="btn re-order"  @click="reorder()">Re-Order <span class="mdi mdi-content-copy"></span></button>
                            <button class="btn go-to-steps" @click="navigateToAction()">Go To Steps <span class="mdi mdi-arrow-right-bold-outline"></span> </button>
                            <button v-if="!isSidebarVisible" @click="toggleSidebar" class="hamburger">
                                <span class="mdi mdi-menu"></span>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="flex pb-2 justify-end">
                    <label class="btn2"><span class="mdi mdi-image"></span> Revised Art Complete <input type="checkbox" class="blue border-white text-amber" v-model="revisedArtCompleteChecked"></label>
                    <label class="btn2"><span class="mdi mdi-fire"></span> RUSH <input type="checkbox" class="blue border-white text-amber" v-model="rushChecked"></label>
                    <label class="btn2"><span class="mdi mdi-pause"></span> ON HOLD <input type="checkbox" class="blue border-white text-amber" v-model="onHoldChecked"></label>
                    <label class="btn2"><span class="mdi mdi-thumb-up-outline"></span> Must Be Perfect <input type="checkbox" class="blue border-white text-amber" v-model="mustBePerfectChecked"></label>
                    <label class="btn2"><span class="mdi mdi-box-cutter"></span> Rip First <input type="checkbox" class="blue border-white text-amber" v-model="ripFirstChecked"></label>
                    <label class="btn2"><span class="mdi mdi-image"></span> Revised Art <input type="checkbox" class="blue border-white text-amber" v-model="revisedArtChecked"></label>
                    <label class="btn2"><span class="mdi mdi-image"></span> Additional Art <input type="checkbox" class="blue border-white text-amber" v-model="additionalArtChecked"></label>
                    <label class="btn2"><span class="mdi mdi-flag-outline"></span> Flags <input type="checkbox" class="blue border-white text-amber"></label>
                </div>
                <div class="dark-gray p-5 text-white">
                    <div class="flex gap-1">
                        <div v-if="invoice.perfect" class="ticket-note-perfect">Must Be Perfect</div>
                        <div v-if="invoice.onHold" class="ticket-note-hold">On Hold</div>
                        <div v-if="invoice.revisedArt" class="ticket-note-revisedArt">Revised Art</div>
                        <div v-if="invoice.ripFirst" class="ticket-note-ripFirst">Rip First</div>
                        <div v-if="invoice.revisedArtComplete" class="ticket-note-revisedArtComplete">Revised Art Complete</div>
                        <div v-if="invoice.rush" class="ticket-note-rush">Rush</div>
                        <div v-if="invoice.additionalArt" class="ticket-note-additionalArt">Additional Art</div>
                    </div>
                    <div class="form-container p-2 light-gray" :style="invoice.perfect ? { 'background-color': '#d88f0b' } : {}">
                        <div class="InvoiceDetails">
                            <div class="invoice-details flex gap-20 relative">
                                <div class="invoice-title bg-white text-black bold p-3 ">{{ invoice?.invoice_title }}</div>
                                <div class="info">
                                    <div>Order</div>
                                    <div class="bold">#{{ invoice?.id }}</div>
                                </div>
                                <div class="info">
                                    <div>Customer</div>
                                    <div class="bold">{{ invoice.client.name }}</div>
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
                                    <div class="bold">{{ invoice.user.name }}</div>
                                </div>
                                <div class="btns flex gap-2">
                                    <div class="bt"><i class="fa-regular fa-pen-to-square"></i></div>
                                    <div class="bt" @click="toggleSpreadsheetMode"
                                         :class="{'text-white': spreadsheetMode, 'green-text': !spreadsheetMode}"
                                    ><i class="fa-solid fa-table"></i></div>
                                    <div class="bt" @click="toggleJobProcessMode"
                                        :class="{'text-white': !jobProcessMode, 'green-text': jobProcessMode}"
                                    ><i class="fa-solid fa-list-check"></i></div>
                                    <div class="bt"><i class="fa-regular fa-eye"></i></div>
                                    <AddNoteDialog :invoice="invoice" ref="addNoteDialog" />
                                    <div class="bt"><i class="fa-regular fa-solid fa-file-pdf fa-sm" @click="generatePdf(invoice.id)"></i></div>
                                </div>
                                <AddNoteDialog v-if="openDialog" :invoice="invoice" ref="addNoteDialog" />
                            </div>
                            <div class="info pl-2">
                                <div>{{ $t('Status') }}</div>
                                <div>
<!--
                                    WE SHOULD BE CHECKING IF JOB STATUS IS COMPLETED TODO
-->
                                    <span class="bold" :class="getStatusColorClass">{{ invoice?.status }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-container  light-gray mt-2">
                        <div class="sub-title pl-2 ">{{$t('OrderLines')}}</div>
                        <div v-for="(job,index) in invoice.jobs" v-if="spreadsheetMode">
                            <div class="jobDetails p-2">
                                <div class="border">
                                    <div class=" flex gap-10">
                                        <div class="invoice-title bg-white text-black bold p-3">
                                            #{{index+1}} {{job.name}}
                                        </div>
                                        <button @click="toggleImagePopover(job)">
                                            <img :src="getImageUrl(job.id)" alt="Job Image" class="jobImg thumbnail"/>
                                        </button>
                                        <div v-if="showImagePopover" class="popover">
                                            <div class="popover-content bg-gray-700">
                                                <img :src="getImageUrl(selectedJob.id)" alt="Job Image" />
                                                <button @click="toggleImagePopover(null)" class="popover-close"><icon class="fa fa-close"/></button>
                                            </div>
                                        </div>
                                        <div>{{job.file}}</div>
                                        <div>{{$t('Height')}}: <span class="bold">{{job.height.toFixed(2)}}</span> </div>
                                        <div>{{$t('Width')}}: <span class="bold">{{job.width.toFixed(2)}}</span> </div>
                                        <div>{{$t('Quantity')}}: <span class="bold">{{job.quantity}}</span> </div>
                                        <div>{{$t('Copies')}}: <span class="bold">{{job.copies}}</span> </div>
                                    </div>
                                    <div class="flex p-2 gap-10">
                                        <div class="">
                                            {{$t('Material')}}:
                                            <span class="bold">
                                            <span v-if="job.large_material_id">{{ job.large_material?.name }}</span>
                                            <span v-else>{{ job?.small_material?.name }}</span>
                                         </span>
                                        </div>
                                        <div>{{$t('totalm')}}<sup>2</sup>: <span class="bold">{{(job.height * job.width / 1000).toFixed(2)}}</span></div>
                                    </div>
                                    <div v-if="jobProcessMode">
                                        <OrderJobDetails :job="job"/>
                                    </div>
                                    <div class="jobInfo relative">
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
                            <OrderSpreadsheet :invoice="invoice"/>
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
import {useToast} from "vue-toastification";
import OrderJobDetails from "@/Pages/Invoice/OrderJobDetails.vue";
import OrderSpreadsheet from "@/Components/OrderSpreadsheet.vue";
import Header from "@/Components/Header.vue";
import OrderHistory from "@/Pages/Invoice/OrderHistory.vue";
import AddNoteDialog from "@/Components/AddNoteDialog.vue";
import AddLockNoteDialog from "@/Components/AddLockNoteDialog.vue";

export default {
    components: {
        AddNoteDialog,
        AddLockNoteDialog,
        OrderHistory,
        OrderSpreadsheet,
        OrderJobDetails,
        MainLayout,
        Header },
    props: {
        invoice: Object,
    },
    data() {
        return {
            showImagePopover: false,
            selectedJob: null,
            isSidebarVisible: false,
            spreadsheetMode:true,
            jobProcessMode:false,
            backgroundColor: null,
            openDialog: false
        }
    },
    computed: {
        getStatusColorClass() {
            const invoiceStatus = this.invoice.status;
            if (invoiceStatus === "Not started yet") {
                return "orange-text";
            } else if (invoiceStatus === "In progress") {
                return "blue-text";
            } else if (invoiceStatus === "Completed") {
                return "green-text";
            }
        },
        mustBePerfectChecked: {
            get() {
                return this.invoice.perfect === 1
            },
            set(value) {
                this.backgroundColor = "#a36a03";
                this.invoice.perfect = value;
                axios.put(`/orders/${this.invoice.id}`, {
                    perfect: value,
                });
            }
        },
        onHoldChecked: {
            get() {
                return this.invoice.onHold === 1;
            },
            set(value) {
                this.invoice.onHold = value;
                axios.put(`/orders/${this.invoice.id}`, {
                    onHold: value,
                });
            }
        },
        ripFirstChecked: {
            get() {
                return this.invoice.ripFirst === 1
            },
            set(value) {
                this.backgroundColor = "#a36a03";
                this.invoice.ripFirst = value;
                axios.put(`/orders/${this.invoice.id}`, {
                    ripFirst: value,
                });
            }
        },
        revisedArtChecked: {
            get() {
                return this.invoice.revisedArt === 1
            },
            set(value) {
                this.backgroundColor = "#a36a03";
                this.invoice.revisedArt = value;
                axios.put(`/orders/${this.invoice.id}`, {
                    revisedArt: value,
                });
            }
        },
        revisedArtCompleteChecked: {
            get() {
                return this.invoice.revisedArtComplete === 1
            },
            set(value) {
                this.backgroundColor = "#a36a03";
                this.invoice.revisedArtComplete = value;
                axios.put(`/orders/${this.invoice.id}`, {
                    revisedArtComplete: value,
                });
            }
        },
        additionalArtChecked: {
            get() {
                return this.invoice.additionalArt === 1
            },
            set(value) {
                this.backgroundColor = "#a36a03";
                this.invoice.additionalArt = value;
                axios.put(`/orders/${this.invoice.id}`, {
                    additionalArt: value,
                });
            }
        },
        rushChecked: {
            get() {
                return this.invoice.rush === 1
            },
            set(value) {
                this.backgroundColor = "#a36a03";
                this.invoice.rush = value;
                axios.put(`/orders/${this.invoice.id}`, {
                    rush: value,
                });
            }
        },
    },
    methods: {
        getImageUrl(id) {
            return `/storage/uploads/${this.invoice.jobs.find(j => j.id === id).file}`
        },
        toggleImagePopover(job) {
            this.selectedJob = job;
            this.showImagePopover = !this.showImagePopover;
        },
        toggleSidebar() {
            this.isSidebarVisible = !this.isSidebarVisible;
        },
        toggleSpreadsheetMode(){
            this.spreadsheetMode = !this.spreadsheetMode;
        },
        toggleJobProcessMode(){
            this.jobProcessMode = !this.jobProcessMode;
        },
        async downloadAllProofs() {
            const toast = useToast();
            try {
                const response = await axios.get('/order/download', {
                    params: {
                        clientName: this.invoice.client.name,
                        invoiceId: this.invoice.id
                    },
                    responseType: 'blob' // important to handle binary data
                });

                // Create a new Blob object using the response data
                const fileURL = window.URL.createObjectURL(new Blob([response.data]));
                const fileLink = document.createElement('a');

                fileLink.href = fileURL;
                fileLink.setAttribute('download', `Invoice_${this.invoice.client.name}_${this.invoice.id}.zip`); // or any other name you want
                document.body.appendChild(fileLink);

                fileLink.click();

                fileLink.remove(); // Clean up
            } catch (error) {
                toast.error('There was an error downloading the files: ', error);
            }
        },
        generatePdf(invoiceId) {
            window.open(`/orders/${invoiceId}/pdf`, '_blank');
        },
        reorder() {
            const invoiceData = this.invoice;
            this.$inertia.visit('/orders/create', {
                data: {
                    invoiceData
                }
            });
        },
        navigateToAction(){
            const firstInProgressAction = this.invoice?.jobs
                .flatMap(job => job?.actions)
                .find(action => action?.status === 'In Progress' || action?.status === 'Not started yet');
            return this.$inertia.visit(`/actions/${firstInProgressAction?.name}`);
        },
        async unlockOrder(invoiceId) {
            try {
                const response = await axios.put('/orders/update-locked-note', {
                    id: invoiceId,
                    comment: null, // Set comment to null to unlock
                });

                if (response.status === 200) {
                    // Handle successful update (e.g., display success message)
                    let toast = useToast();
                    this.invoice.LockedNote = null;
                    toast.success('Order successfully unlocked.')
                } else {
                    // Handle errors (e.g., display error message)
                    let toast = useToast();
                    toast.error('Failed to unlock order:', response.data);
                }
            } catch (error) {
                // Handle unexpected errors
                console.error('Error unlocking order:', error);
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
.ticket-note-perfect {
    background-color: #d88f0b; /* Gold background */
    color: $white;
    font-weight: bold;
    text-transform: uppercase;
    border: 2px dashed white; /* Ticket-like dashed border */
    border-bottom: none;
    border-radius: 3px 3px 0 0 ;
    padding: 4px;
    text-align: center;
    width: max-content;
}
.ticket-note-hold {
    background-color: $red; /* Gold background */
    color: $white;
    font-weight: bold;
    text-transform: uppercase;
    border: 2px dashed white; /* Ticket-like dashed border */
    border-bottom: none;
    border-radius: 3px 3px 0 0 ;
    padding: 4px;
    text-align: center;
    width: max-content;
}
.ticket-note-ripFirst {
    background-color: $green; /* Gold background */
    color: $white;
    font-weight: bold;
    text-transform: uppercase;
    border: 2px dashed white; /* Ticket-like dashed border */
    border-bottom: none;
    border-radius: 3px 3px 0 0 ;
    padding: 4px;
    text-align: center;
    width: max-content;
}
.ticket-note-revisedArt {
    background-color: $blue; /* Gold background */
    color: $white;
    font-weight: bold;
    text-transform: uppercase;
    border: 2px dashed white; /* Ticket-like dashed border */
    border-bottom: none;
    border-radius: 3px 3px 0 0 ;
    padding: 4px;
    text-align: center;
    width: max-content;
}
.ticket-note-revisedArtComplete {
    background-color: $light-green; /* Gold background */
    color: $white;
    font-weight: bold;
    text-transform: uppercase;
    border: 2px dashed white; /* Ticket-like dashed border */
    border-bottom: none;
    border-radius: 3px 3px 0 0 ;
    padding: 4px;
    text-align: center;
    width: max-content;
}
.ticket-note-rush {
    background-color: skyblue; /* Gold background */
    color: $white;
    font-weight: bold;
    text-transform: uppercase;
    border: 2px dashed white; /* Ticket-like dashed border */
    border-bottom: none;
    border-radius: 3px 3px 0 0 ;
    padding: 4px;
    text-align: center;
    width: max-content;
}
.ticket-note-additionalArt {
    background-color: mediumpurple; /* Gold background */
    color: $white;
    font-weight: bold;
    text-transform: uppercase;
    border: 2px dashed white; /* Ticket-like dashed border */
    border-bottom: none;
    border-radius: 3px 3px 0 0 ;
    padding: 4px;
    text-align: center;
    width: max-content;
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
.lock-order, .download-order, .re-order{
    background-color: $blue;
    color: white;
}
.go-to-steps{
    background-color: $orange;
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
    height: 45px;
    margin: 0 1rem;
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

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
                    <Header title="statement" subtitle="currentReport" icon="invoice.png" link="statements"/>
                </div>
                <div class="dark-gray p-5 text-white">
                    <div class="report">
                        <div class="flexed">
                            <h1>
                                {{$t('currentReport')}} {{$t('Nr')}} {{certificate.id}} - {{certificate.date}}
                            </h1>
                        </div>
                        <div class="justify-end flex gap-3">
                            <button class="btn add-row">
                                Add new Statement <i class="fa fa-plus"></i>
                            </button>
                            <button class="btn add-row">
                                Add Item <i class="fa fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-container p-2 light-gray">
                        <div class="InvoiceDetails">
                            <div class="invoice-details flex gap-20 relative mb-2" >
                                <div class="invoice-title bg-white text-black bold p-3 ">{{ certificate.id }}/{{new Date(certificate.date).toLocaleDateString('en-US', { year: 'numeric'})}}</div>
                                <div class="info">
                                    <div>Statement</div>
                                    <div class="bold">#{{ certificate?.id }}</div>
                                </div>
                                <div class="info">
                                    <div>Bank</div>
                                    <div class="bold">{{certificate.bank}}</div>
                                </div>
                                <div class="info">
                                    <div>Bank Account</div>
                                    <div class="bold">{{certificate.bankAccount}}</div>
                                </div>
                                <div class="info">
                                    <div>Created By</div>
                                    <div class="bold">{{}}</div>
                                </div>

                                <div class="info">
                                    <div>Date</div>
                                    <span class="bold">{{ certificate.date }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <table>
                            <tr>
                                <th>ID</th>
                                <th>Statement</th>
                                <th>Item</th>
                                <th>Client</th>
                                <th>Expense</th>
                                <th>Income</th>
                                <th>Reference to</th>
                                <th>Comment</th>
                            </tr>
                            <tr>
                                <th></th>
                                <th>{{certificate.id}}</th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                            </tr>
                        </table>
                        <div class="flex justify-end">
                            <button class="btn create-order">
                                Save
                            </button>
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
        certificate: Object,
    },
    data() {
        return {
            isSidebarVisible: false,
            openDialog: false,
            item: {
                client_id: null,
                certificate_id: this.certificate.id,
                income: 0,
                expense: 0,
                code: '',
                reference_to: '',
                comment: ''
            },
            certificate: {
                date: null,
                bank: '',
                bankAccount: '',
            }
        }
    },
    methods: {
        toggleSidebar() {
            this.isSidebarVisible = !this.isSidebarVisible;
        },

        addItem() {
            const toast = useToast();
            axios.post('/item', {
                client_id: this.item.client_id,
                certificate_id: this.item.certificate_id,
                income: this.item.income,
                expense: this.item.expense,
                code: this.item.code,
                reference_to: this.item.reference_to,
                comment: this.item.comment
            })
                .then((response) => {
                    toast.success("Item added successfully.");
                })
                .catch((error) => {
                    toast.error("Error adding item!");
                });
        },

        addCertificate() {
            const toast = useToast();
            axios.post('/certificate', {
                date: this.certificate.date,
                bank: this.certificate.bank,
                bankAccount: this.certificate.bankAccount,
            })
                .then((response) => {
                    toast.success("Certificate added successfully.");
                })
                .catch((error) => {
                    toast.error("Error adding certificate!");
                });
        }
    },
};
</script>

<style scoped lang="scss">
.info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    justify-content: center;

}
.rounded{
    border-radius: 3px 3px 0px 0px;
}
.report{
    justify-content: right;
    gap: 25%;
}
.invoice{
    justify-content: center;
}
.circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
}
.flexed{
    font-size: 21px;
    display: flex;
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

.create-order{
    background-color: $green;
    color: white;
}
.btn {
    margin-bottom: 3px;
    padding: 10px 15px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    border-radius: 2px;
}

.add-row,{
    background-color: $blue;
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

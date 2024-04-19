<template>
    <MainLayout>
        <div class="Content pl-7 pr-7 flex">
            <div class="sidebar" v-if="isSidebarVisible">
                <button @click="toggleSidebar" class="close-sidebar">
                    <span class="mdi mdi-close"></span>
                </button>
            </div>
            <div class="left-column">
                <div class="flex justify-between">
                    <Header title="CardStatements" subtitle="CCS" icon="clientCard.png" link="cardStatements"/>
                </div>

                <div class="light-gray text-white bold p-2 round">
                    <div class="invoice flex gap-60 mb-2">
                        <div class="info flex">
                            <div class="client pr-11">
                                <div>Client</div>
                                <div class="mt-5">client.name</div>
                            </div>

                            <div class="date flex">
                                <div>
                                    <div>From</div>
                                    <div>
                                        <input type="date">
                                    </div>
                                </div>
                                <div>
                                    <div>To</div>
                                    <div>
                                        <input type="date">
                                    </div>
                                </div>
                            </div>
                            <div class="mt-10 pr-2 pl-16">
                                Total Balance:
                            </div>
                            <div>
                                <div>Requests</div>
                                <input type="text" class="rounded" disabled>
                            </div>
                            <div>
                                <div>Owes</div>
                                <input type="text"  class="rounded" disabled>
                            </div>
                            <div>
                                <div>Balance</div>
                                <input type="text"  class="rounded" disabled>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="dark-gray p-5 text-white" >
                    <div class="form-container p-2 light-gray">
                        <table>
                            <tr>
                                <th>Date</th>
                                <th>Document</th>
                                <th>Number</th>
                                <th>Incoming Invoice</th>
                                <th>Output Invoice</th>
                                <th>Statement - Income</th>
                                <th>Statement - Expense</th>
                                <th>Comment</th>
                            </tr>
                            <tr v-for="item in tableData">
                                <th>{{ item?.date }}</th>
                                <th>{{ item?.document }}</th>
                                <th>{{ item?.number }}</th>
                                <th>{{ item?.incoming_invoice }}</th>
                                <th>{{ item?.output_invoice }}</th>
                                <th>{{ item?.statement_income }}</th>
                                <th>{{ item?.statement_expense }}</th>
                                <th>{{ item?.comment }}</th>
                            </tr>
                        </table>
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
import UpdateDialogComment from "@/Components/UpdateDialogComment.vue";

export default {
    components: {
        OrderSpreadsheet,
        OrderJobDetails,
        MainLayout,
        Header,
        UpdateDialogComment},
    props: {
        cardStatement: Object,
        tableData: []
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
    },
};
</script>

<style scoped lang="scss">


.rounded{
    border-radius: 3px 3px 3px 3px;
    width: 140px;
}
.round{
    border-radius: 3px 3px 0 0;
}
.invoice{
    justify-content: space-around;
    align-items: center;
    width: 100%;
}
.left-column {
    width: 100%; /* Initially set to take up full width */
}
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
    width: 100%;
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

<template>
    <div class="d-flex justify-center align-center">
        <v-row>
            <v-dialog
                v-model="dialog"
                persistent
                max-width="1200"
                class="height"
                @keydown.esc="closeDialog"
            >
                <template v-slot:activator="{ props }">
                    <div v-bind="props" class="bt">
                        <button class="white inline-flex items-center px-4 py-2 border border-transparent white-hover rounded-md font-semibold text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-50"  @click="openAddContactForm(client)"><i class="fa-solid fa-circle-info"></i></button>
                    </div>
                </template>
                <v-card class="height background">
                    <v-card-title>
                        <span class="text-h5 text-white">{{ $t('additionalInfo') }}</span>
                    </v-card-title>
                    <div class="info">
                        <div v-if="showPriemnicaForm">
                            <table class="excel-table">
                                <thead>
                                <tr class="first-row">
                                    <th style="width: 20px">#</th>
                                    <th style="width: 45px;">{{$t('Nr')}}</th>
                                    <th>{{$t('Code')}}<div class="resizer" @mousedown="initResize($event, 2)"></div></th>
                                    <th>{{$t('articleName')}}<div class="resizer" @mousedown="initResize($event, 3)"></div></th>
                                    <th>{{$t('Qty')}}<div class="resizer" @mousedown="initResize($event, 4)"></div></th>
                                    <th>{{$t('price')}}<div class="resizer" @mousedown="initResize($event, 5)"></div></th>
                                    <th>{{$t('VAT')}}%<div class="resizer" @mousedown="initResize($event, 6)"></div></th>
                                    <th>{{$t('VAT')}}<div class="resizer" @mousedown="initResize($event, 6)"></div></th>
                                    <th>{{$t('price')}} {{$t('VAT')}}<div class="resizer" @mousedown="initResize($event, 7)"></div></th>
                                    <th>{{$t('Amount')}}<div class="resizer" @mousedown="initResize($event, 8)"></div></th>
                                    <th>{{$t('Tax')}}<div class="resizer" @mousedown="initResize($event, 9)"></div></th>
                                    <th>{{$t('Total')}}<div class="resizer" @mousedown="initResize($event, 10)"></div></th>
                                    <th>{{$t('comment')}}<div class="resizer" @mousedown="initResize($event, 11)"></div></th>
                                </tr>
                                </thead>
                                <tbody>
                                     <tr v-for="(p,index) in priem.articles" >
                                         <th>{{index+1}}</th>
                                         <th>{{p.id}}</th>
                                         <th>{{p.code}}</th>
                                         <th>{{p.name}}</th>
                                         <th>{{p.pivot.quantity}}</th>
                                         <th>{{p.purchase_price}}</th>
                                         <th>{{taxTypePercentage(p.tax_type)}}%</th>
                                         <th>{{calculateVAT(p.purchase_price, p.tax_type)}}</th>
                                         <th>{{priceWithVAT(p.purchase_price, p.tax_type)}}</th>
                                         <th>{{calculateAmount(p.pivot.quantity, p.purchase_price)}}</th>
                                         <th>{{calculateTax(p.purchase_price, p.tax_type, p.pivot.quantity)}}</th>
                                         <th>{{calculateTotal(p.purchase_price, p.tax_type, p.pivot.quantity)}}</th>
                                         <th>{{p.comment? p.comment:'/'}}</th>
                                     </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <v-card-actions class="flexSpace gap-4">
                        <v-spacer></v-spacer>
                        <div class="flex btns">
                            <SecondaryButton @click="closeDialog" class="red">Close</SecondaryButton>
                        </div>
                    </v-card-actions>
                </v-card>
            </v-dialog>
        </v-row>
    </div>
</template>

<script>
import VueMultiselect from 'vue-multiselect'
import SecondaryButton from "@/Components/buttons/SecondaryButton.vue";
import PrimaryButton from "@/Components/buttons/PrimaryButton.vue";
import {useToast} from "vue-toastification";
import axios from "axios";
import Tab from "@/Components/tabs/Tab.vue";
import AddContactDialog from "@/Components/AddContactDialog.vue";

export default {
    components: {
        AddContactDialog,
        Tab,
        PrimaryButton,
        SecondaryButton,
        VueMultiselect
    },
    data() {
        return {
            dialog: false,
            showPriemnicaForm: false,
        };
    },
    props: {
        priem: Object
    },
    methods: {
        openDialog() {
            this.dialog = true;
        },
        closeDialog() {
            this.dialog = false;
        },
        openAddContactForm() {
            this.showPriemnicaForm = true;
        },
        handleEscapeKey(event) {
            if (event.key === 'Escape') {
                this.closeDialog();
            }
        },
        taxTypePercentage(taxType) {
            switch (taxType) {
                case 1:
                    return 18;
                case 2:
                    return 5;
                case 3:
                    return 10;
                default:
                    return 0;
            }
        },
        formatNumber(number) {
            return Number(number).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        calculateVAT(purchasePrice, taxType) {
            const vatPercentage = this.taxTypePercentage(taxType);
            const vatAmount = (purchasePrice * vatPercentage) / 100;
            return this.formatNumber(vatAmount);
        },
        priceWithVAT(purchasePrice, taxType) {
            const vatAmount = this.calculateVAT(purchasePrice, taxType);
            const totalPrice = parseFloat(purchasePrice) + parseFloat(vatAmount);
            return this.formatNumber(totalPrice);
        },
        calculateAmount(quantity, purchasePrice) {
            const amount = quantity * purchasePrice;
            return this.formatNumber(amount);
        },
        calculateTax(purchasePrice, taxType, quantity) {
            const vatPercentage = this.taxTypePercentage(taxType);
            const vatAmountPerUnit = (purchasePrice * vatPercentage) / 100;
            const totalVatAmount = vatAmountPerUnit * quantity;
            return this.formatNumber(totalVatAmount);
        },
        calculateTotal(purchasePrice, taxType, quantity) {
            const totalAmount = parseFloat(this.calculateAmount(quantity, purchasePrice).replace(/,/g, ''));
            const totalTax = parseFloat(this.calculateTax(purchasePrice, taxType, quantity).replace(/,/g, ''));
            const total = totalAmount + totalTax;
            return this.formatNumber(total);
        }
    },
    mounted() {
        document.addEventListener('keydown', this.handleEscapeKey);
    },
};
</script>
<style src="vue-multiselect/dist/vue-multiselect.css"></style>
<style scoped lang="scss">
.info{
    padding: 10px 24px 15px;
}

.bt{
    margin: 12px 24px;
}
.height {
    height: calc(100vh - 100px);
}
.background {
    background-color: $light-gray;
}
.ultra-light-gray{
    background-color: $ultra-light-gray;
}
.flexSpace {
    display: flex;
    justify-content: space-between;
}
.orange {
    color: $orange;
}
.red{
    background-color: $red;
    color:white;
    border: none;
}

.white {
    background-color: $white;
    color: black;
    &-hover:hover {
        background-color: darken($white, 25%);
    }
}
table{
    color: white;
}
table th, table td{
    padding: 5px;
    width: 300px;
}
table th{
    background-color: $ultra-light-gray;
}
table td, table th{
    border-right: 1px solid $ultra-light-gray;
    border-left: 1px solid $ultra-light-gray;
}
</style>

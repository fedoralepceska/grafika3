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
                                <tr>
                                    <th style="width: 20px">#</th>
                                    <th style="width: 45px;">{{$t('Nr')}}</th>
                                    <th>{{$t('Code')}}<div class="resizer" @mousedown="initResize($event, 2)"></div></th>
                                    <th>{{$t('articleName')}}<div class="resizer" @mousedown="initResize($event, 3)"></div></th>
                                    <th>{{$t('Qty')}}<div class="resizer" @mousedown="initResize($event, 4)"></div></th>
                                    <th>{{$t('price')}}<div class="resizer" @mousedown="initResize($event, 5)"></div></th>
                                    <th>{{$t('VAT')}}%<div class="resizer" @mousedown="initResize($event, 6)"></div></th>
                                    <th>{{$t('price')}} {{$t('VAT')}}<div class="resizer" @mousedown="initResize($event, 7)"></div></th>
                                    <th>{{$t('Amount')}}<div class="resizer" @mousedown="initResize($event, 8)"></div></th>
                                    <th>{{$t('Tax')}}<div class="resizer" @mousedown="initResize($event, 9)"></div></th>
                                    <th>{{$t('Total')}}<div class="resizer" @mousedown="initResize($event, 10)"></div></th>
                                    <th>{{$t('comment')}}<div class="resizer" @mousedown="initResize($event, 11)"></div></th>
                                </tr>
                                </thead>
                                <tbody>
<!--                                <tr v-for="(row, index) in rows" :key="index">
                                    <td></td>
                                    <td>{{ index + 1 }}</td>
                                    <td><input v-model="row.code" type="text"></td>
                                    <td><input v-model="row.articleName" type="text"></td>
                                    <td><input v-model="row.qty" type="number"></td>
                                    <td><input v-model="row.price" type="number"></td>
                                    <td><input v-model="row.vat" type="number"></td>
                                    <td>{{ calculatePriceWithVAT(row) }}</td>
                                    <td>{{ calculateAmount(row) }}</td>
                                    <td>{{ calculateTax(row) }}</td>
                                    <td>{{ calculateTotal(row) }}</td>
                                    <td><input v-model="row.comment" type="text"></td>
                                </tr>-->
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
    height: calc(100vh - 300px);
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

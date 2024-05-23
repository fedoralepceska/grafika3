<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="receipt" subtitle="addNewReceipt" icon="Materials.png"/>
            <div class="dark-gray p-5">
                <div class="form-container p-2 light-gray">
                    <div class="flex gap-2">
                        <div class="border p-2 mb-2 mag">
                            <h2 class="text-white bold">
                                {{$t('warehouse')}}
                            </h2>
                            <div class="px-4 py-1">
                                <select class="text-gray-700 rounded" style="width: 40vh;"></select>
                            </div>
                            <div class="px-4 pb-1">
                                <input type="text" class="text-gray-700 rounded" style="width: 40vh;">
                            </div>
                            <div class="px-4 pb-1">
                                <input type="text" class="text-gray-700 rounded" style="width: 40vh;">
                            </div>
                        </div>
                        <div class="border p-2 mb-2 cl">
                            <h2 class="text-white bold">
                                {{$t('client')}}
                            </h2>
                            <div class="px-4 py-1">
                                <select class="text-gray-700 rounded" style="width: 72vh;"></select>
                            </div>
                            <div class="px-4 pb-1 gap-1 flex">
                                <input type="text" class="text-gray-700 rounded" style="width: 40vh;">
                                <input type="text" class="text-gray-700 rounded" style="width: 31.3vh;">
                            </div>
                            <div class="px-4 pb-1 gap-1 flex">
                                <input type="text" class="text-gray-700 rounded" style="width: 25vh;">
                                <input type="text" class="text-gray-700 rounded" style="width: 46.3vh;">
                            </div>
                        </div>
                    </div>
                    <h2 class="sub-title">
                        {{ $t('receiptDetails') }}
                    </h2>
                    <div class="button-container mb-2 gap-2">
                        <SecondaryButton @click="addRow" type="submit" class="white">{{ $t('NewRow') }}</SecondaryButton>
                        <SecondaryButton @click="deleteRow" class="red" type="submit">{{ $t('Delete') }}</SecondaryButton>
                    </div>
                    <form @submit.prevent="" class="flex gap-3 justify-center overflow-x-auto">
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
                            <tr v-for="(row, index) in rows" :key="index">
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
                            </tr>
                            </tbody>
                        </table>
                    </form>
                    <div class="button-container mt-10">
                        <PrimaryButton @click="addRow" type="submit">{{ $t('add') }}</PrimaryButton>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script>
import axios from 'axios';
import PrimaryButton from "@/Components/buttons/PrimaryButton.vue";
import MainLayout from "@/Layouts/MainLayout.vue";
import { useToast } from "vue-toastification";
import Header from "@/Components/Header.vue";
import SecondaryButton from "@/Components/buttons/SecondaryButton.vue";

export default {
    name: 'Create',
    components: {SecondaryButton, Header, MainLayout, PrimaryButton },
    data() {
        return {
            rows: [],
            startX: 0,
            startWidth: 0,
            columnIndex: -1,
        };
    },
    methods: {
        addRow() {
            this.rows.push({
                code: '',
                articleName: '',
                qty: 0,
                price: 0,
                vat: 0,
                comment: ''
            });
        },
        deleteRow() {
            if (this.rows.length > 0) {
                this.rows.pop();
            }
        },
        calculatePriceWithVAT(row) {
            return (row.price * (1 + row.vat / 100)).toFixed(2);
        },
        calculateAmount(row) {
            return (row.qty * row.price).toFixed(2);
        },
        calculateTax(row) {
            return (row.qty * row.price * (row.vat / 100)).toFixed(2);
        },
        calculateTotal(row) {
            return (row.qty * row.price * (1 + row.vat / 100)).toFixed(2);
        },
        initResize(event, index) {
            this.startX = event.clientX;
            this.startWidth = event.target.parentElement.offsetWidth;
            this.columnIndex = index;

            document.documentElement.addEventListener('mousemove', this.resizeColumn);
            document.documentElement.addEventListener('mouseup', this.stopResize);
        },
        resizeColumn(event) {
            const diffX = event.clientX - this.startX;
            const newWidth = this.startWidth + diffX;
            const ths = document.querySelectorAll('.excel-table th');

            if (this.columnIndex >= 0 && this.columnIndex < ths.length) {
                ths[this.columnIndex].style.width = `${newWidth}px`;
            }
        },
        stopResize() {
            document.documentElement.removeEventListener('mousemove', this.resizeColumn);
            document.documentElement.removeEventListener('mouseup', this.stopResize);
        }
    },
};
</script>

<style scoped lang="scss">
.mag {
    width: fit-content;
    border-radius: 3px;
}
.cl {
    border-radius: 3px;
}
.bold {
    font-weight: bolder;
}
fieldset {
    border: 1px solid #ffffff;
    border-radius: 3px;
    width: fit-content;
    padding-right: 35px;
}
legend {
    margin-left: 10px;
    color: white;
}
#taxA {
    width: 120px;
}
#taxA2 {
    width: 80px;
}
.green-text {
    color: $green;
}
.red{
    background-color: $red;
    color: white;
    border: none;
}
.red:hover{
    background-color: darkred;
}
.white:hover{
    background-color: lightgray;
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
}
.light-gray {
    background-color: $light-gray;
}
.client-form {
    width: 100%;
    max-width: 1000px;
    justify-content: left;
    align-items: center;
    min-height: 20vh;
    padding: 5px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}
.sub-title {
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    color: $white;
}
.form-group {
    display: flex;
    justify-content: right;
    align-items: center;
    width: 350px;
    margin-bottom: 10px;
    color: $white;
}
.label {
    flex: 1;
    text-align: left;
    margin-right: 20px;
}
.button-container {
    display: flex;
    justify-content: end;
}
.excel-table {
    border-collapse: collapse;
    width: 100%;
    color: white;
    table-layout: fixed;
}
.excel-table th,
.excel-table td {
    border: 1px solid #dddddd;
    padding: 4px;
    text-align: center;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
    position: relative;
}
.excel-table th {
    min-width: 50px;
}
.excel-table tr:nth-child(even) {
    background-color: $ultra-light-gray;
}
.resizer {
    width: 5px;
    height: 100%;
    position: absolute;
    right: 0;
    top: 0;
    cursor: col-resize;
    user-select: none;
    background-color: transparent;
}
</style>

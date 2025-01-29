<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <div class="flex justify-between">
                <Header title="warehouse" subtitle="warehouse" icon="warehouse.png" link="warehouse"/>
                <div class="flex pt-4">
                    <div class="flex gap-2 pt-3">
                        <button class="btn"><ViewWarehousesDialog :warehouse="warehouse"/></button>
                        <button class="btn"><AddWarehouseDialog :warehouse="warehouse" /></button>
                    </div>
                </div>
            </div>
            <div class="form-container p15">
                <div class="dark-gray p-5 text-white">
                    <div class="form-container p-2 light-gray overflow-x-auto">
                        <h2 class="sub-title">
                            {{ $t('wItems') }}
                        </h2>
                            <div class="pb-3">
                                <label class="pr-3">{{ $t('filterWarehouse') }}</label>
                                <select class="text-black rounded " v-model="selectedWarehouse" @change="fetchPriemnica">
                                    <option value="All" hidden>{{ $t('warehouse') }}</option>
                                    <option value="All">{{ $t('allWarehouses') }}</option>
                                    <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">
                                        {{ warehouse.name }}
                                    </option>
                                </select>
                            </div>


                        <table class="excel-table mb-3 ">
                            <thead>
                                <tr>
                                    <th style="width: 45px;">{{$t('Nr')}}</th>
                                    <th>{{$t('receiptId')}}<div class="resizer" @mousedown="initResize($event, 1)"></div></th>
                                    <th>{{$t('article')}}<div class="resizer" @mousedown="initResize($event, 1)"></div></th>
                                    <th>{{$t('warehouse')}}<div class="resizer" @mousedown="initResize($event, 2)"></div></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-for="(item, index) in priemnica.data" :key="index">
                                    <template v-for="(article, articleIndex) in item.articles" :key="articleIndex">
                                        <tr>
                                            <th>{{ index + 1 }}</th>
                                            <th>{{ article.pivot.priemnica_id }}</th>
                                            <th>{{ article.name }}</th>
                                            <th>{{ item.warehouse_name }}</th>
                                        </tr>
                                    </template>
                                </template>
                            </tbody>

</table>

                        <Pagination :pagination="priemnica" />
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
<script>
import MainLayout from "@/Layouts/MainLayout.vue";
import PrimaryButton from "@/Components/buttons/PrimaryButton.vue";
import SecondaryButton from "@/Components/buttons/SecondaryButton.vue";
import Pagination from "@/Components/Pagination.vue"
import Header from "@/Components/Header.vue";
import AddWarehouseDialog from "@/Components/AddWarehouseDialog.vue";
import ViewWarehousesDialog from "@/Components/ViewWarehousesDialog.vue";
import axios from "axios";


export default {
    components: {
        MainLayout,
        PrimaryButton,
        SecondaryButton,
        Pagination,
        Header,
        AddWarehouseDialog,
        ViewWarehousesDialog,
    },
    props: {
        warehouse: Object,
    },
    data() {
        return {
            startX: 0,
            startWidth: 0,
            columnIndex: -1,
            perPage: 20,
            priemnica: {},
            warehouses:[],
            selectedWarehouse: 'All',
        };
    },
    mounted() {
        this.fetchWarehouses();
        this.fetchPriemnica();
    },
    methods: {
        fetchWarehouses() {
            axios.get('/api/warehouses')
                .then(response => {
                    this.warehouses = response.data;
                })
                .catch(error => {
                    console.error('Error fetching warehouses:', error);
                });
        },
        async fetchPriemnica() {
            try {
                const response = await axios.get('/api/priemnica', {
                    params: { warehouse_id: this.selectedWarehouse }
                });
                this.priemnica = response.data;
            } catch (error) {
                console.error(error);
            }
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
.centered {
    display: flex;
    justify-content: center;
    align-items: center;
}
.filters{
    justify-content: space-between;
}
select{
    width: 240px;
}
.buttF{
    padding-top: 23.5px;
}
.btn {
    padding: 9px 12px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    border-radius: 2px;
}
.create-order1{
    background-color: $blue;
    color: white;
}
.centered {
    display: flex;
    justify-content: center;
    align-items: center;
}
.delete{
    border: none;
    color: white;
    background-color: $red;
}
.delete:hover{
    background-color: darkred;
}
.green-text{
    color: $green;
}
.blue{
    background-color: $blue;
    border: none;
    color: white;
}
.blue:hover{
    background-color: cornflowerblue;
}
.green{
    background-color: $green;
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
.light-gray{
    background-color: $light-gray;
}

.client-form {
    width: 100%;
    justify-content: left;
    align-items: center;
    min-height: 20vh;
    padding: 5px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
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
.form-group {
    display: flex;
    justify-content: right;
    align-items: center;
    width: 300px;
    margin-bottom: 10px;
    color: $white;
}

.label {
    flex: 1;
    text-align: left;
    margin-right: 20px;
}
.button-container{
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
.info {
    border: 2px solid white;
    min-width: 90vh;
    max-width: 100vh;
}
.contact-info {
    display: flex;
    flex-direction: row;
    align-items: center;
}
</style>

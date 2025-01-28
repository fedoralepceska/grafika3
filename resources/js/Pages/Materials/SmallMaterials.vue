<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="material" subtitle="materialList" icon="Materials.png" link="materials/small"/>
            <div class="form-container p15">
                <div class="dark-gray p-5 text-white">
                    <div class="form-container p-2 light-gray overflow-x-auto">
                        <h2 class="sub-title">
                            {{ $t('listOfSmallMaterials') }}
                        </h2>
                        <div class=" flex justify-between gap-4 pb-10">
                            <div class=" flex gap-4">
                                <div class="search flex gap-2">
                                    <input v-model="searchQuery" placeholder="Enter material name" class="text-black" style="width: 50vh; border-radius: 3px" @keyup.enter="searchMaterials" />
                                    <button class="btn create-order1" @click="searchMaterials">{{ $t('search') }}</button>
                                </div>
                                <div class="flex gap-2">
                                    <div class="status">
                                        <label class="pr-3">{{ $t('perPage') }}</label>
                                        <select v-model="filterStatus" class="text-black rounded" @change="fetchSmallMaterials" style="width: 80px" >
                                            <option value="20">20</option>
                                            <option value="40">40</option>
                                            <option value="100">100</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="button flex gap-3">
                                <button @click="printMaterials" class="btn create-order">
                                    {{ $t('printMaterials') }} <i class="fa-solid fa-print"></i>
                                </button>
                                <button @click="printAllMaterials" class="btn create-order2">
                                    {{ $t('printAllMaterials') }} <i class="fa-solid fa-print"></i>
                                </button>
                            </div>
                        </div>
                        <div>
                            <table class="excel-table mb-3">
                                <thead>
                                <tr>
                                    <th style="width: 45px;">{{$t('Nr')}}</th>
                                    <th>{{$t('material')}}<div class="resizer" @mousedown="initResize($event, 1)"></div></th>
                                    <th>{{$t('Quantity')}}<div class="resizer" @mousedown="initResize($event, 3)"></div></th>
                                    <th style="width: 80px;">{{$t('Unit')}}<div class="resizer" @mousedown="initResize($event, 2)"></div></th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="m in smallMaterials.data" :key="m.id">
                                    <th>{{ m?.id }}</th>
                                    <th>{{ m?.name }}</th>
                                    <th>{{ m?.quantity }}</th>
                                    <th v-if="m.article">{{ getUnit(m) }}</th>
                                    <th v-else>/</th>
                                </tr>
                                </tbody>
                            </table>
                            <Pagination :pagination="smallMaterials"/>
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
import Pagination from "@/Components/Pagination.vue";
import Header from "@/Components/Header.vue";

export default {
    components: {
        MainLayout,
        Pagination,
        Header
    },
    data() {
        return {
            startX: 0,
            startWidth: 0,
            columnIndex: -1,
            smallMaterials: {},
            searchQuery: '',
            filterStatus: 20,
            perPage: 20,
        };
    },
    mounted() {
        this.fetchSmallMaterials();
    },
    methods: {
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
        },
        async fetchSmallMaterials(page = 1) {
            const params = {
                page,
                per_page: this.filterStatus, // Use filterStatus for per page value
                search_query: this.searchQuery,
            };
            try {
                const response = await axios.get('/materials/small', { params });
                this.smallMaterials = response.data;
            } catch (error) {
                console.error('Error fetching small materials:', error);
            }
        },
        searchMaterials() {
            this.fetchSmallMaterials();
        },
        async printMaterials() {
            const params = {
                search_query: this.searchQuery,
                per_page: this.filterStatus,
            };
            const url = `/materials/pdf?${new URLSearchParams(params).toString()}`;
            window.open(url, '_blank');
        },
        async printAllMaterials() {
            const url = `/materials/all-pdf`;
            window.open(url, '_blank');
        },
        getUnit(material) {
            if (material) {
                if (material.article.in_meters) {
                    return 'meters';
                } else if (material.article.in_square_meters) {
                    return 'square meters';
                } else if (material.article.in_kilograms) {
                    return 'kilograms';
                } else if (material.article.in_pieces) {
                    return 'pieces';
                }
            }
            return '';
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
.create-order{
    background-color: $blue;
    color: white;
}
.create-order1{
    background-color: $blue;
    color: white;
}
.create-order2{
    background-color: $green;
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

<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="tradeArticles" subtitle="tradeWarehouseStock" icon="Materials.png" link="trade-articles" />
            <div class="form-container p15">
                <div class="dark-gray p-5 text-white">
                    <div class="form-container p-2 light-gray overflow-x-auto">
                        <h2 class="sub-title">
                            {{ $t('tradeWarehouseStock') }}
                        </h2>
                        
                        <!-- Summary Cards -->
                        <div class="summary-cards mb-4">
                            <div class="summary-card">
                                <div class="summary-title">Total Articles</div>
                                <div class="summary-value">{{ summary.total_articles || 0 }}</div>
                            </div>
                            <div class="summary-card">
                                <div class="summary-title">Total Value</div>
                                <div class="summary-value">{{ formatNumber(summary.total_value || 0) }} ден</div>
                            </div>
                            <div class="summary-card warning" v-if="summary.low_stock_articles > 0">
                                <div class="summary-title">Low Stock</div>
                                <div class="summary-value">{{ summary.low_stock_articles }}</div>
                            </div>
                        </div>

                        <div class="filters flex gap-10">
                            <!-- Warehouse Filter -->
                            <div class="search-container pb-2">
                                <div class="mr-1 ml-2">Warehouse</div>
                                <div class="ml-2">
                                    <select v-model="filters.warehouse_id" class="rounded text-black">
                                        <option value="All">All Warehouses</option>
                                        <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">
                                            {{ warehouse.name }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Search Filter -->
                            <div class="search-container pb-2">
                                <div class="mr-1 ml-2">Search</div>
                                <div class="ml-2">
                                    <input type="text" v-model="filters.search" placeholder="Code or Name" class="rounded text-black">
                                </div>
                            </div>

                            <!-- Sort Filter -->
                            <div class="search-container pb-2">
                                <div class="mr-1 ml-2">Sort By</div>
                                <div class="ml-2">
                                    <select v-model="filters.sort_by" class="rounded text-black">
                                        <option value="created_at">Date Added</option>
                                        <option value="article_name">Article Name</option>
                                        <option value="article_code">Article Code</option>
                                        <option value="quantity">Quantity</option>
                                        <option value="purchase_price">Purchase Price</option>
                                        <option value="selling_price">Selling Price</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Filter Button -->
                            <div class="buttF gap-3">
                                <button @click="applyFilter" class="btn create-order1">Filter</button>
                                <button @click="refreshSummary" class="btn create-order1">Refresh</button>
                                <button @click="showLowStock" class="btn orange">Low Stock</button>
                            </div>
                        </div>

                        <table class="excel-table">
                            <thead>
                            <tr>
                                <th style="width: 45px;">{{$t('Nr')}}</th>
                                <th>{{$t('warehouse')}}<div class="resizer" @mousedown="initResize($event, 1)"></div></th>
                                <th>{{$t('Code')}}<div class="resizer" @mousedown="initResize($event, 2)"></div></th>
                                <th>{{$t('articleName')}}<div class="resizer" @mousedown="initResize($event, 3)"></div></th>
                                <th>{{$t('Qty')}}<div class="resizer" @mousedown="initResize($event, 4)"></div></th>
                                <th>Purchase Price (.ден)<div class="resizer" @mousedown="initResize($event, 5)"></div></th>
                                <th>Selling Price (.ден)<div class="resizer" @mousedown="initResize($event, 6)"></div></th>
                                <th>Stock Value (.ден)<div class="resizer" @mousedown="initResize($event, 7)"></div></th>
                                <th>Date Added<div class="resizer" @mousedown="initResize($event, 8)"></div></th>
                                <th>{{$t('ACTIONS')}}</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="(article, index) in localTradeArticles" :key="article.id" :class="{ 'low-stock': article.quantity <= 10 }">
                                <th>{{ (tradeArticles.current_page - 1) * tradeArticles.per_page + index + 1 }}</th>
                                <th>{{ article.warehouse.name }}</th>
                                <th>{{ article.article.code }}</th>
                                <th class="text-left">{{ article.article.name }}</th>
                                <th :class="{ 'text-red': article.quantity <= 10 }">{{ formatNumber(article.quantity) }}</th>
                                <th>{{ formatNumber(article.purchase_price) }}</th>
                                <th>
                                    <span v-if="!article.editing_price">{{ formatNumber(article.selling_price || 0) }}</span>
                                    <input v-else v-model.number="article.new_selling_price" type="number" step="0.01" class="price-input">
                                </th>
                                <th>{{ formatNumber(article.quantity * (article.purchase_price || 0)) }}</th>
                                <th>{{ new Date(article.created_at).toLocaleDateString('en-GB') }}</th>
                                <th>
                                    <div class="centered flex gap-2">
                                        <button v-if="!article.editing_price" @click="editPrice(article)" class="btn blue">
                                            Edit Price
                                        </button>
                                        <button v-else @click="savePrice(article)" class="btn green">
                                            Save
                                        </button>
                                        <button v-if="article.editing_price" @click="cancelEdit(article)" class="btn delete">
                                            Cancel
                                        </button>
                                    </div>
                                </th>
                            </tr>
                            </tbody>
                        </table>
                        <Pagination :pagination="tradeArticles" />
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script>
import MainLayout from "@/Layouts/MainLayout.vue";
import Pagination from "@/Components/Pagination.vue";
import Header from "@/Components/Header.vue";
import axios from "axios";
import { useToast } from "vue-toastification";

export default {
    components: {
        MainLayout,
        Pagination,
        Header,
    },
    props: {
        tradeArticles: Object,
        warehouses: Array,
        filters: Object,
    },
    data() {
        return {
            localTradeArticles: this.tradeArticles?.data || [],
            summary: {},
            filters: {
                warehouse_id: this.filters?.warehouse_id || 'All',
                search: this.filters?.search || '',
                sort_by: this.filters?.sort_by || 'created_at',
                sort_order: this.filters?.sort_order || 'desc',
            },
            startX: 0,
            startWidth: 0,
            columnIndex: -1,
        };
    },
    setup() {
        const toast = useToast();
        return { toast };
    },
    mounted() {
        this.refreshSummary();
    },
    methods: {
        formatNumber(number) {
            return Number(number).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        applyFilter() {
            axios.get('/trade-articles', { params: this.filters })
                .then(response => {
                    this.localTradeArticles = response.data.data;
                })
                .catch(error => {
                    console.error('Error applying filters:', error);
                    this.toast.error('Error applying filters');
                });
        },
        refreshSummary() {
            axios.get('/trade-articles/summary')
                .then(response => {
                    this.summary = response.data;
                })
                .catch(error => {
                    console.error('Error fetching summary:', error);
                });
        },
        showLowStock() {
            axios.get('/trade-articles/low-stock')
                .then(response => {
                    this.localTradeArticles = response.data;
                    this.toast.info(`Found ${response.data.length} low stock articles`);
                })
                .catch(error => {
                    console.error('Error fetching low stock:', error);
                    this.toast.error('Error fetching low stock articles');
                });
        },
        editPrice(article) {
            article.editing_price = true;
            article.new_selling_price = article.selling_price || 0;
        },
        savePrice(article) {
            axios.put(`/trade-articles/${article.id}/selling-price`, {
                selling_price: article.new_selling_price
            })
            .then(response => {
                article.selling_price = article.new_selling_price;
                article.editing_price = false;
                this.toast.success('Selling price updated successfully');
            })
            .catch(error => {
                console.error('Error updating price:', error);
                this.toast.error('Error updating selling price');
            });
        },
        cancelEdit(article) {
            article.editing_price = false;
            article.new_selling_price = null;
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
        },
    },
};
</script>

<style scoped lang="scss">
$background-color: #1a2732;
$gray: #3c4e59;
$dark-gray: #2a3946;
$light-gray: #54606b;
$ultra-light-gray: #77808b;
$white: #ffffff;
$black: #000000;
$hover-color: #4a5a68;
$green: #408a0b;
$red: #9e2c30;
$blue: #3b82f6;
$orange: #f59e0b;

.centered {
    display: flex;
    justify-content: center;
    align-items: center;
}

.filters {
    justify-content: space-between;
}

select, input {
    width: 240px;
}

.buttF {
    padding-top: 23.5px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.btn {
    padding: 9px 12px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    border-radius: 2px;
}

.create-order1 {
    background-color: $blue;
    color: white;
}

.delete {
    border: none;
    color: white;
    background-color: $red;
}

.delete:hover {
    background-color: darkred;
}

.green {
    background-color: $green;
    color: white;
}

.green:hover {
    background-color: darkgreen;
}

.blue {
    background-color: $blue;
    border: none;
    color: white;
}

.blue:hover {
    background-color: #2563eb;
}

.orange {
    background-color: $orange;
    color: white;
}

.orange:hover {
    background-color: #d97706;
}

.dark-gray {
    background-color: $dark-gray;
    justify-content: left;
    align-items: center;
    min-height: 20vh;
    min-width: 80vh;
}

.light-gray {
    background-color: $light-gray;
}

.sub-title {
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    color: $white;
}

.summary-cards {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.summary-card {
    background-color: $dark-gray;
    border: 1px solid $gray;
    border-radius: 8px;
    padding: 15px;
    min-width: 150px;
    text-align: center;
}

.summary-card.warning {
    border-color: $orange;
    background-color: rgba($orange, 0.1);
}

.summary-title {
    font-size: 12px;
    color: $ultra-light-gray;
    margin-bottom: 5px;
}

.summary-value {
    font-size: 18px;
    font-weight: bold;
    color: $white;
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

.excel-table tr.low-stock {
    background-color: rgba($orange, 0.2);
}

.text-left {
    text-align: left !important;
}

.text-red {
    color: $red !important;
    font-weight: bold;
}

.price-input {
    width: 80px;
    padding: 2px 4px;
    border: 1px solid $gray;
    border-radius: 2px;
    background-color: $white;
    color: $black;
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
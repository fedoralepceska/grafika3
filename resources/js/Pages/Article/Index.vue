<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="article" subtitle="allArticles" icon="Materials.png" link="articles"/>
            <div class="form-container p15">
                <div class="dark-gray p-5 text-white">
                    <div class="form-container p-2 light-gray">
                        <h2 class="sub-title">
                            {{ $t('allArticles') }}
                        </h2>
                        <div class="controls-section">
                            <div class="controls-grid">
                                <div class="control-item">
                                    <label class="control-label">Search Articles</label>
                                    <input 
                                        type="text" 
                                        class="control-input" 
                                        v-model="search" 
                                        @keyup="fetchArticles(1)" 
                                        placeholder="Search by Article Code or Name">
                                </div>
                                <div class="control-item">
                                    <label class="control-label">{{ $t('articlesPerPage') }}</label>
                                    <select v-model="perPage" class="control-select" @change="fetchArticles(1)">
                                        <option value="20">{{ $t('twentyPerPage') }}</option>
                                        <option value="40">{{ $t('fortyPerPage') }}</option>
                                        <option value="60">{{ $t('sixtyPerPage') }}</option>
                                    </select>
                                </div>
                                <div class="control-item">
                                    <label class="control-label">{{ $t('VAT') }}</label>
                                    <select v-model="vatFilter" class="control-select" @change="fetchArticles(1)">
                                        <option value="">All VAT Types</option>
                                        <option value="1">DDV A (18%)</option>
                                        <option value="2">DDV B (5%)</option>
                                        <option value="3">DDV C (10%)</option>
                                    </select>
                                </div>
                                <div class="control-item">
                                    <label class="control-label">{{ $t('Unit') }}</label>
                                    <select v-model="unitFilter" class="control-select" @change="fetchArticles(1)">
                                        <option value="">All Units</option>
                                        <option value="meters">Meters (m)</option>
                                        <option value="kilograms">Kilograms (kg)</option>
                                        <option value="pieces">Pieces (pcs)</option>
                                        <option value="square_meters">Square Meters (m²)</option>
                                    </select>
                                </div>
                                <div class="control-item clear-btn-container">
                                    <button @click="clearFilters" class="clear-filters-btn">
                                        <svg class="clear-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                        Clear All
                                    </button>
                                </div>
                            </div>
                        </div>
                        <table class="excel-table">
                            <thead>
                            <tr>
                                <th></th>
                                <th>{{$t('Code')}}</th>
                                <th>{{$t('article')}}</th>
                                <th>{{$t('Unit')}}</th>
                                <th>{{$t('fprice')}}</th>
                                <th>{{$t('pprice')}}</th>
                                <th>{{$t('price')}}</th>
                                <th>{{$t('VAT')}}</th>
                                <th>{{$t('Barcode')}}</th>
                                <th>{{$t('comment')}}</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="article in articles.data" :key="article.id">
                                <th class="check">
                                    <input type="checkbox" class="rounded"
                                           v-model="selectedArticles"
                                           :value="article">
                                </th>
                                <th>{{ article.code }}</th>
                                <th>{{ article.name }}</th>
                                <th>{{getUnit(article)}}</th>
                                <th>{{ article.factory_price }}</th>
                                <th>{{ article.purchase_price }}</th>
                                <th>{{ article.price_1 }}</th>
                                <th>{{ getVAT(article)}}</th>
                                <th>{{ article.barcode }}</th>
                                <th>{{ article.comment }}</th>
                            </tr>
                            </tbody>
                        </table>
                        <div class="button-container mt-2 gap-2">
                            <div class="button-with-tooltip">
                                <SecondaryButton class="delete" disabled type="submit" @click="handleDelete">{{ $t('Delete') }}</SecondaryButton>
                                
                            </div>
                            <div class="button-with-tooltip">
                                <SecondaryButton 
                                    v-if="selectedArticles.length === 0"
                                    class="px-3 blue disabled-edit" 
                                    disabled>
                                    {{ $t('Edit') }}
                                </SecondaryButton>
                                <ArticleEdit v-else :article="selectedArticles[0]"/>
                                <div v-if="selectedArticles.length === 0" class="tooltip">
                                    Please select an article to edit
                                </div>
                            </div>
                            <PrimaryButton @click="navigateToArticles">{{ $t('addArticle') }}</PrimaryButton>
                        </div>
                        <Pagination :pagination="articles" @pagination-change-page="fetchArticles"/>
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
import Pagination from "@/Components/Pagination.vue";
import axios from "axios";
import Header from "@/Components/Header.vue";
import ArticleEdit from "@/Pages/Article/ArticleEdit.vue";
import { useToast } from "vue-toastification";

export default {
    components: {
        MainLayout,
        PrimaryButton,
        SecondaryButton,
        Pagination,
        Header,
        ArticleEdit
    },
    data() {
        return {
            search: '',
            perPage: 20,
            articles: {},
            selectedArticles: [],
            vatFilter: '',
            unitFilter: '',
        };
    },
    methods: {
        async fetchArticles(page = 1) {
            const params = {
                page,
                per_page: this.perPage,
                search: this.search,
                vat_filter: this.vatFilter,
                unit_filter: this.unitFilter
            };
            const response = await axios.get('/articles', { params });
            this.articles = response.data;
        },
        clearFilters() {
            this.vatFilter = '';
            this.unitFilter = '';
            this.search = '';
            this.fetchArticles(1);
        },
        getUnit(article) {
            if (article !== null) {
                // Check for both integer and string values to handle API data type variations
                if (article?.in_meters == 1) {
                    return 'm'
                }
                else if (article?.in_kilograms == 1) {
                    return 'kg'
                }
                else if (article?.in_pieces == 1) {
                    return 'pcs'
                }
                else if (article?.in_square_meters == 1) {
                    return 'm²'
                }
            }
            else {
                return '';
            }
        },
        getVAT(article) {
            if (article !== null) {
                if (article?.tax_type === "1") {
                    return 'DDV A (18%)'
                }
                else if (article?.tax_type === "2") {
                    return 'DDV B (5%)'
                }
                else if (article?.tax_type === "3") {
                    return 'DDV C (10%)'
                }
            }
            else {
                return '';
            }
        },
        async deleteArticle(article) {
            try {
                await axios.delete(`/articles/${article.id}`);
                this.fetchArticles();
            } catch (error) {
                console.error('Error deleting article:', error);
            }
        },
        navigateToArticles(){
            this.$inertia.visit(`/articles/create`);
        },
        handleDelete() {
            if (this.selectedArticles.length === 0) {
                return; // Tooltip will show the message
            }
            
            if (this.selectedArticles.length === 1) {
                this.deleteArticle(this.selectedArticles[0]);
            } else {
                // Handle multiple article deletion if needed
                const toast = useToast();
                toast.info(`Selected ${this.selectedArticles.length} articles. Multiple deletion not implemented yet.`);
            }
        },
    },

    mounted() {
        this.fetchArticles();
    }
};
</script>
<style scoped lang="scss">

#ArticleSearch{
    width: 380px;
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
}

.excel-table th, .excel-table td {
    border: 1px solid #dddddd;
    padding: 4px;
    text-align: center;
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

/* Compact Professional Controls Section */
.controls-section {
    padding: 5px;
    margin-bottom: 10px;
}

.controls-grid {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr auto;
    gap: 20px;
    align-items: end;
}

.control-item {
    display: flex;
    flex-direction: column;
}

.clear-btn-container {
    align-self: end;
}

.control-label {
    display: block;
    color: #ffffff;
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 8px;
    opacity: 0.9;
}

.control-input, 
.control-select {
    border: 2px solid rgba(255, 255, 255, 0.2);
    border-radius: 6px;
    background: rgba(255, 255, 255, 0.95);
    color: #333;
    font-size: 14px;
    transition: all 0.2s ease;
}

.control-select {
    cursor: pointer;
}

.control-input:focus, 
.control-select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.control-select:hover {
    border-color: rgba(255, 255, 255, 0.3);
}

.clear-filters-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 12px 16px;
    background: $red;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.clear-filters-btn:hover {
    background: darkred;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(239, 68, 68, 0.2);
}

/* Disabled Edit Button */
.disabled-edit {
    opacity: 0.6;
}

.disabled-edit:hover {
    background-color: $blue !important;
    transform: none !important;
}

/* Tooltip System */
.button-with-tooltip {
    position: relative;
    display: inline-block;
}

.tooltip {
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    margin-bottom: 8px;
    padding: 8px 12px;
    background: rgba(0, 0, 0, 0.9);
    color: white;
    font-size: 13px;
    border-radius: 6px;
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    z-index: 1000;
    pointer-events: none;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.tooltip::after {
    content: '';
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    border: 5px solid transparent;
    border-top-color: rgba(0, 0, 0, 0.9);
}

.button-with-tooltip:hover .tooltip {
    opacity: 1;
    visibility: visible;
    transform: translateX(-50%) translateY(-2px);
}

.clear-icon {
    width: 14px;
    height: 14px;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .controls-grid {
        grid-template-columns: 1fr 1fr 1fr;
        gap: 16px;
    }
    
    .clear-btn-container {
        grid-column: span 3;
        justify-self: end;
    }
}

@media (max-width: 768px) {
    .controls-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    
    .clear-btn-container {
        grid-column: span 1;
        justify-self: stretch;
    }
    
    .clear-filters-btn {
        justify-content: center;
    }
}
</style>

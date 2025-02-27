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
                        <div class="search-container p-3 flex">
                            <input type="text" class="text-black rounded" style="width: 320px" v-model="search" @keyup="fetchArticles" placeholder="Search by Article Code or Name">
                            <div class="centered mr-1 ml-4 ">{{ $t('articlesPerPage') }}</div>
                            <div class="ml-3">
                                <select v-model="perPage" class="rounded text-black" @change="fetchArticles">
                                    <option value="20">{{ $t('twentyPerPage') }}</option>
                                    <option value="40">{{ $t('fortyPerPage') }}</option>
                                    <option value="60">{{ $t('sixtyPerPage') }}</option>
                                </select>
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
                            <SecondaryButton class="delete" type="submit" @click="deleteArticle">{{ $t('Delete') }}</SecondaryButton>
                            <ArticleEdit :article="selectedArticles[0]"/>
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
        };
    },
    methods: {
        async fetchArticles(page = 1) {
            const params = {
                page,
                per_page: this.perPage,
                search: this.search
            };
            const response = await axios.get('/articles', { params });
            this.articles = response.data;
        },
        getUnit(article) {
            if (article !== null) {
                if (article?.in_meters === 1) {
                    return 'Meters'
                }
                else if (article?.in_kilograms === 1) {
                    return 'Kilograms'
                }
                else if (article?.in_pieces === 1) {
                    return 'Pieces'
                }
            }
            else {
                return '';
            }
        },
        getVAT(article) {
            if (article !== null) {
                if (article?.tax_type === 1) {
                    return 'DDV A (18%)'
                }
                else if (article?.tax_type === 2) {
                    return 'DDV B (5%)'
                }
                else if (article?.tax_type === 3) {
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
</style>

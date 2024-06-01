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
                            <input type="text" class="text-black rounded" id="ArticleSearch" v-model="search" @keyup="" placeholder="Search by Article Code or Name">
                            <div class="centered mr-1 ml-4 ">Articles per page</div>
                            <div class="ml-3">
                                <select v-model="perPage" class="rounded text-black" @change="">
                                    <option value="5">5 per page</option>
                                    <option value="10">10 per page</option>
                                    <option value="20">20 per page</option>
                                    <option value="0">Show All</option>
                                </select>
                            </div>
                        </div>
                        <table class="excel-table">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>{{$t('Code')}}</th>
                                    <th>{{$t('Article')}}</th>
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
                                <tr>
                                    <th class="check"><input type="checkbox"  class="rounded"></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                </tr>
                            </tbody>
                        </table>
                        <div class="button-container mt-2 gap-2">
                            <!-- TODO: add article as param to delete method -->
                            <SecondaryButton class="delete" type="submit" @click="deleteArticle">{{ $t('Delete') }}</SecondaryButton>
                            <SecondaryButton type="submit" class="blue"> {{ $t('Edit') }}</SecondaryButton>
                            <PrimaryButton type="submit">{{ $t('addArticle') }}</PrimaryButton>
                        </div>
<!--
                        <Pagination />
-->
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
import axios from "axios";
import AddContactDialog from "@/Components/AddContactDialog.vue";
import ViewContactsDialog from "@/Components/ViewContactsDialog.vue";
import Pagination from "@/Components/Pagination.vue"
import Header from "@/Components/Header.vue";
import UpdateClientDialog from "@/Components/UpdateClientDialog.vue";
import CardStatementUpdateDialog from "@/Components/CardStatementUpdateDialog.vue";

export default {
    components: {
        CardStatementUpdateDialog,
        UpdateClientDialog,
        ViewContactsDialog,
        AddContactDialog,
        MainLayout,
        PrimaryButton,
        SecondaryButton,
        Pagination,
        Header
    },
    props: {

    },
    data() {
        return {

        };
    },
    methods: {
        async deleteArticle(article) {
            try {
                await axios.delete(`/article/${article.id}`);
            } catch (error) {
                console.error('Error deleting article:', error);
            }
        },
    },
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

/* Alternate row color */
.excel-table tr:nth-child(even) {
    background-color: #f9f9f9;
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

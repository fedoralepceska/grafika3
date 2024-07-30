<template>
    <div class="d-flex justify-center align-center">
        <v-row>
            <v-dialog
                v-model="dialog"
                persistent="true"
                max-width="1250"
                class="height"
                @keydown.esc="closeDialog"
            >
                <template v-slot:activator="{ props }">
                    <div v-bind="props" class="bt">
                        <SecondaryButton type="submit" class="px-3 blue" @click="openEditForm(article)">Edit</SecondaryButton>
                    </div>
                </template>
                <v-card class="height background">
                    <v-card-title>
                        <span class="text-h5 text-white">Update Article</span>
                    </v-card-title>
                    <v-card-text>
                        <div v-if="showEditForm">
                            <form @submit.prevent="updateArticle" class="flex gap-3 justify-center">
                                <fieldset>
                                    <legend>{{ $t('GeneralInfo') }}</legend>
                                    <div class="form-group gap-4">
                                        <label for="code">{{ $t('Code') }}:</label>
                                        <input type="text" id="code" v-model="article.code" class="text-gray-700 rounded" required>
                                    </div>
                                    <div class="form-group gap-4">
                                        <label for="name">{{ $t('name') }}:</label>
                                        <input type="text" id="name" v-model="article.name" class="text-gray-700 rounded" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="tax" class="mr-4 width100">{{ $t('VAT') }}:</label>
                                        <select v-model="article.tax_type" class="text-gray-700 rounded" id="taxA">
                                            <option value="DDV A">DDV A</option>
                                            <option value="DDV B">DDV B</option>
                                            <option value="DDV C">DDV C</option>
                                        </select>
                                        <input type="text" disabled class="rounded text-gray-500" id="taxA2" :placeholder="placeholderText">
                                    </div>
                                    <div class="form-group gap-4">
                                        <label for="type">{{ $t('Product') }}/{{ $t('Service') }}:</label>
                                        <select v-model="article.type" class="text-gray-700 rounded" >
                                            <option value="product">{{ $t('Product') }}</option>
                                            <option value="service">{{ $t('Service') }}</option>
                                        </select>
                                    </div>
                                    <div class="form-group gap-4">
                                        <label for="barcode">{{ $t('Barcode') }}:</label>
                                        <input type="text" v-model="article.barcode" class="text-gray-700 rounded" >
                                    </div>
                                    <div class="form-group gap-4">
                                        <label for="comment">{{ $t('comment') }}:</label>
                                        <input type="text" id="comment" v-model="article.comment" class="text-gray-700 rounded" >
                                    </div>
                                </fieldset>
                                <fieldset>
                                    <legend>{{$t('additionalInfo')}}</legend>
                                    <div class="form-group gap-4">
                                        <label for="height">{{ $t('height') }}:</label>
                                        <input type="text" v-model="article.height" class="text-gray-700 rounded" >
                                    </div>
                                    <div class="form-group gap-4">
                                        <label for="width">{{ $t('width') }}:</label>
                                        <input type="text" v-model="article.width" class="text-gray-700 rounded" >
                                    </div>
                                    <div class="form-group gap-4">
                                        <label for="length">{{ $t('length') }}:</label>
                                        <input type="text" v-model="article.length" class="text-gray-700 rounded" >
                                    </div>
                                    <div class="form-group gap-4">
                                        <label for="weight">{{ $t('weight') }}:</label>
                                        <input type="text" v-model="article.weight" class="text-gray-700 rounded" >
                                    </div>
                                    <div class="form-group gap-4">
                                        <label for="color">{{ $t('color') }}:</label>
                                        <input type="text" v-model="article.color" class="text-gray-700 rounded" >
                                    </div>
                                    <div class="form-group gap-4">
                                        <label for="unit" class="mr-12">{{ $t('Format') }}:</label>
                                        <select v-model="article.format_type" class="text-gray-700 rounded" >
                                            <option value="2">{{ $t('Large') }}</option>
                                            <option value="1">{{ $t('Small') }}</option>
                                        </select>
                                    </div>
                                    <div class="form-group gap-4">
                                        <label for="unit" class="mr-12">{{ $t('Unit') }}:</label>
                                        <label><input type="checkbox" v-model="article.in_kilograms" value="kilogram" class="rounded"> {{ $t('Kg') }}</label>
                                        <label><input type="checkbox" v-model="article.in_meters" value="meters" class="rounded"> {{ $t('M') }}</label>
                                        <label><input type="checkbox" v-model="article.in_pieces" value="pieces" class="rounded"> {{ $t('Pcs') }}</label>
                                    </div>
                                </fieldset>
                                <fieldset>
                                    <legend>{{$t('pricelist')}}</legend>
                                    <div class="form-group gap-4">
                                        <label for="fprice">{{ $t('fprice') }}:</label>
                                        <input type="number" v-model="article.factory_price" class="text-gray-700 rounded" >
                                    </div>
                                    <div class="form-group gap-4">
                                        <label for="pprice">{{ $t('pprice') }}:</label>
                                        <input type="number" v-model="article.purchase_price" class="text-gray-700 rounded" >
                                    </div>
                                    <div class="form-group gap-4">
                                        <label for="price">{{ $t('price') }}:</label>
                                        <input type="number" v-model="article.price_1" class="text-gray-700 rounded" >
                                    </div>
                                </fieldset>
                            </form>
                        </div>
                    </v-card-text>
                    <v-card-actions class="flexSpace gap-4">
                        <v-spacer></v-spacer>
                        <SecondaryButton @click="closeDialog" class="red ">Close</SecondaryButton>
                        <SecondaryButton @click="updateArticle()" class="green">Update Article</SecondaryButton>
                    </v-card-actions>
                </v-card>
            </v-dialog>
        </v-row>
    </div>
</template>

<script>
import axios from 'axios';
import PrimaryButton from "@/Components/buttons/PrimaryButton.vue";
import MainLayout from "@/Layouts/MainLayout.vue";
import { useToast } from "vue-toastification";
import Header from "@/Components/Header.vue";
import SecondaryButton from "@/Components/buttons/SecondaryButton.vue";

export default {
    name: 'ArticleEdit',
    components: {SecondaryButton, Header, MainLayout, PrimaryButton },
    data() {
        return {
            dialog: false,
            noteComment: '',
            selectedOption: null,
            actionOptions: [],
            jobs: [],
            showEditForm: false,
            selectedArticleId: null,
        }
    },
    props: {
        article: Object
    },
    computed: {
        placeholderText() {
            switch (this.article.tax_type) {
                case 1:
                    return "18%";
                case 2:
                    return "5%";
                case 3:
                    return "10%";
                default:
                    return "";
            }
        }
    },
    methods: {
        openDialog() {
            this.dialog = true;
        },
        closeDialog() {
            this.dialog = false;
        },
        openEditForm(article) {
            this.selectedArticleId = article.id;
            this.showEditForm = true;
        },
        async updateArticle() {
            const toast = useToast();
            try {
                const response = await axios.put(`/articles/${this.article.id}`, this.article);
                toast.success(response.data.message);
                this.closeDialog();
            } catch (error) {
                toast.error("Error updating article!");
                console.error(error);
            }
        }
    },
};
</script>

<style scoped lang="scss">

.blue{
    background-color: $blue;
    border: none;
    color: white;
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
#taxA{
    width: 120px;
}
#taxA2{
    width: 80px;
}
.green-text{
    color: $green;
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

}
.light-gray{
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

.sub-title{
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
.button-container{
    display: flex;
    justify-content: end;
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
#taxA{
    width: 120px;
}
#taxA2{
    width: 80px;
}
.green-text{
    color: $green;
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

}
.light-gray{
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

.sub-title{
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
.button-container{
    display: flex;
    justify-content: end;
}
.form-group {
    display: flex;
    justify-content: left;
    align-items: center;
    width: 350px;
    color: $white;
    padding-left: 10px;
}
.width100 {
    width: 150px;
}
select{
    color: black;
    width: 225px;
    border-radius: 3px;
}
.type{
    display: flex;
    justify-content: space-evenly;
}
.label-input-group {
    display: flex;
    flex-direction: column;
}
input {
    margin: 0 !important;
}
.btn {
    padding: 9px 12px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    border-radius: 2px;
    background-color: $blue;
    margin-top: 12px;
}

.height {
    height: 100vh;
}
.background {
    background-color: $light-gray;
}
.flexSpace {
    display: flex;
    justify-content: space-around;
}
input {
    margin: 12px 0;
}
.red {
    background-color: $red;
    color: white;
    border: none;
}
.green {
    background-color: $green;
    color: white;
    border: none;
}
.redBackground{
    background-color: $red;
}
.greenBackground{
    background-color: $green;
}
.bt{
    margin: 12px 12px;
}

</style>

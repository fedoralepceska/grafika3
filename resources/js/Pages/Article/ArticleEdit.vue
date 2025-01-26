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
                        <SecondaryButton type="submit" class="px-3 blue" @click="openEditForm(article)">{{ $t('Edit') }}</SecondaryButton>
                    </div>
                </template>
                <v-card class="height background">
                    <v-card-title>
                        <span class="text-h5 text-white">{{ $t('updateArticle') }}</span>
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
                                    <div class="form-group gap-4">
                                        <label for="tax" class="mr-4 width100">{{ $t('VAT') }}:</label>
                                        <select v-model="article.tax_type" class="text-gray-700 rounded" id="taxA">
                                            <option value="1">DDV A</option>
                                            <option value="2">DDV B</option>
                                            <option value="3">DDV C</option>
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
                                        <label>
                                            <input type="checkbox" v-model="articleUnits.in_kilograms" @change="selectUnit('in_kilograms')" class="rounded"> {{ $t('Kg') }}
                                        </label>
                                        <label>
                                            <input type="checkbox" v-model="articleUnits.in_meters" @change="selectUnit('in_meters')" class="rounded"> {{ $t('M') }}
                                        </label>
                                        <label>
                                            <input type="checkbox" v-model="articleUnits.in_pieces" @change="selectUnit('in_pieces')" class="rounded"> {{ $t('Pcs') }}
                                        </label>
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
                        <SecondaryButton @click="closeDialog" class="red ">{{ $t('close') }}</SecondaryButton>
                        <SecondaryButton @click="updateArticle()" class="green">{{ $t('updateArticle') }}</SecondaryButton>
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
            articleUnits: {
                in_kilograms: false,
                in_meters: false,
                in_pieces: false,
                in_square_meters: false,
            },
        }
    },
    props: {
        article: Object
    },
    computed: {
        placeholderText() {
            switch (this.article.tax_type) {
                case '1':
                    return "18%";
                case '2':
                    return "5%";
                case '3':
                    return "10%";
                default:
                    return "";
            }
        }
    },

    methods: {
        initializeCheckboxValues() {
            if (this.article){
            this.articleUnits.in_kilograms = this.article.in_kilograms===1;
            this.articleUnits.in_meters = this.article.in_meters===1;
            this.articleUnits.in_pieces = this.article.in_pieces===1;
            this.articleUnits.in_square_meters = this.article.in_square_meters===1;
            }
        },
        openDialog() {
            this.dialog = true;
        },
        closeDialog() {
            this.dialog = false;
        },
        openEditForm(article) {
            this.selectedArticleId = article.id;
            this.showEditForm = true;
            this.initializeCheckboxValues();
        },
        selectUnit(selectedUnit) {
            // Reset all units to false
            this.articleUnits.in_kilograms = false;
            this.articleUnits.in_meters = false;
            this.articleUnits.in_pieces = false;

            // Set the selected unit to true
            this.articleUnits[selectedUnit] = true;
        },
        async updateArticle() {
            const toast = useToast();
            try {
                this.article.in_kilograms = this.articleUnits.in_kilograms ? 1 : null;
                this.article.in_meters = this.articleUnits.in_meters ? 1 : null;
                this.article.in_pieces = this.articleUnits.in_pieces ? 1 : null;


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

.height {
    height: 100vh;
}

.background {
    background-color: $light-gray;
}

.form-container {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    justify-content: space-between;
}

fieldset {
    border: 1px solid #ffffff;
    border-radius: 5px;
    padding: 20px;
    min-width: 400px;
    background-color: rgba(255, 255, 255, 0.1);
}

legend {
    color: white;
    padding: 0 10px;
    font-weight: bold;
}

.form-group {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
    color: white;
}

.form-group label {
    width: 150px; /* Set a fixed width for label alignment */
    margin-right: 10px;
    font-weight: 600;
    text-align: right;
}

.form-group input,
.form-group select {
    padding: 8px;
    width: calc(100% - 160px); /* Adjust width based on label width */
    box-sizing: border-box;
    border: 1px solid #ccc;
    border-radius: 4px;
    color: black;
    background-color: white;
}

.input-combo {
    display: flex;
    gap: 10px;
    align-items: center;
}

#taxA {
    width: 100px;
}

#taxA2 {
    width: 80px;
}

.blue {
    background-color: $blue;
    border: none;
    color: white;
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

.bt {
    margin: 12px 12px;
}

input[type="checkbox"]:checked {
    background-color: #007bff;
    border-color: #007bff;
}


</style>

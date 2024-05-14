<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="article" subtitle="addNewArticle" icon="Materials.png"/>
            <div class="dark-gray p-5">
                <div class="form-container p-2 light-gray">
                    <h2 class="sub-title">
                        {{ $t('articleDetails') }}
                    </h2>
                    <form @submit.prevent="" class="flex gap-3 justify-center">
                        <fieldset>
                            <legend>{{ $t('GeneralInfo') }}</legend>
                            <div class="form-group gap-4">
                                <label for="name">{{ $t('Code') }}:</label>
                                <input type="text" id="code" class="text-gray-700 rounded" required>
                            </div>
                            <div class="form-group gap-4">
                                <label for="name">{{ $t('name') }}:</label>
                                <input type="text" id="name" class="text-gray-700 rounded" required>
                            </div>
                            <div class="form-group">
                                <label for="tax" class="mr-4 width100">{{ $t('Tax') }}:</label>
                                <select v-model="selectedOption" class="text-gray-700 rounded" id="taxA">
                                    <option value="DDV A">DDV A</option>
                                    <option value="DDV B">DDV B</option>
                                    <option value="DDV C">DDV C</option>
                                </select>
                                <input type="text" disabled class="rounded text-gray-500" id="taxA2" :placeholder="placeholderText">
                            </div>
                            <div class="form-group gap-4">
                                <label for="type">{{ $t('Product') }}/{{ $t('Service') }}:</label>
                                <select  class="text-gray-700 rounded" >
                                    <option value="product">{{ $t('Product') }}</option>
                                    <option value="service">{{ $t('Service') }}</option>
                                </select>
                            </div>
                            <div class="form-group gap-4">
                                <label for="barcode">{{ $t('Barcode') }}:</label>
                                <input type="text" class="text-gray-700 rounded" >
                            </div>
                            <div class="form-group gap-4">
                                <label for="comment">{{ $t('comment') }}:</label>
                                <input type="text" id="comment" class="text-gray-700 rounded" >
                            </div>
                        </fieldset>
                        <fieldset>
                            <legend>{{$t('additionalInfo')}}</legend>
                            <div class="form-group gap-4">
                                <label for="height">{{ $t('height') }}:</label>
                                <input type="text" class="text-gray-700 rounded" >
                            </div>
                            <div class="form-group gap-4">
                                <label for="width">{{ $t('width') }}:</label>
                                <input type="text"  class="text-gray-700 rounded" >
                            </div>
                            <div class="form-group gap-4">
                                <label for="length">{{ $t('length') }}:</label>
                                <input type="text"  class="text-gray-700 rounded" >
                            </div>
                            <div class="form-group gap-4">
                                <label for="weight">{{ $t('weight') }}:</label>
                                <input type="text"  class="text-gray-700 rounded" >
                            </div>
                            <div class="form-group gap-4">
                                <label for="color">{{ $t('color') }}:</label>
                                <input type="text"  class="text-gray-700 rounded" >
                            </div>
                            <div class="form-group gap-4">
                                <label for="unit" class="mr-12">{{ $t('Unit') }}:</label>
                                <label><input type="checkbox" value="kilogram" class="rounded"> {{ $t('Kg') }}</label>
                                <label><input type="checkbox" value="meters" class="rounded"> {{ $t('M') }}</label>
                                <label><input type="checkbox" value="pieces" class="rounded"> {{ $t('Pcs') }}</label>
                            </div>
                        </fieldset>
                        <fieldset>
                            <legend>{{$t('pricelist')}}</legend>
                            <div class="form-group gap-4">
                                <label for="fprice">{{ $t('fprice') }}:</label>
                                <input type="text" class="text-gray-700 rounded" >
                            </div>
                            <div class="form-group gap-4">
                                <label for="pprice">{{ $t('pprice') }}:</label>
                                <input type="text"  class="text-gray-700 rounded" >
                            </div>
                            <div class="form-group gap-4">
                                <label for="price">{{ $t('price') }}:</label>
                                <input type="text"  class="text-gray-700 rounded" >
                            </div>
                        </fieldset>


                        <!-- Other form fields... -->
                    </form>
                    <div class="button-container mt-10">
                        <PrimaryButton type="submit">{{ $t('addArticle') }}</PrimaryButton>
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

export default {
    name: 'Create',
    components: {Header, MainLayout, PrimaryButton },
    data() {
        return {
            selectedOption: 'DDV A'
        }
    },
    computed:{
        placeholderText: function () {
            switch (this.selectedOption) {
                case "DDV A":
                    return "18%";
                case "DDV B":
                    return "5%";
                case "DDV C":
                    return "10%";
                default:
                    return "";
            }
        }
    },
    methods: {

    },
};
</script>

<style scoped lang="scss">

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


</style>

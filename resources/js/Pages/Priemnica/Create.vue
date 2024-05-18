<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="receipt" subtitle="addNewReceipt" icon="Materials.png"/>
            <div class="dark-gray p-5">
                <div class="flex gap-2">
                    <div class="border p-2 mb-2 mag">
                        <h2 class="text-white bold">
                            {{$t('warehouse')}}
                        </h2>
                        <div class="px-4 py-1">
                            <select  class="text-gray-700 rounded" style="width: 40vh;" >
                            </select>
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
                            <select  class="text-gray-700 rounded" style="width: 72vh;" >
                            </select>
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

                <div class="form-container p-2 light-gray">
                    <h2 class="sub-title">
                        {{ $t('receiptDetails') }}
                    </h2>
                    <form @submit.prevent="" class="flex gap-3 justify-center">

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
.mag{
    width: fit-content;
    border-radius: 3px;
}
.cl{
    border-radius: 3px;
}
.bold{
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

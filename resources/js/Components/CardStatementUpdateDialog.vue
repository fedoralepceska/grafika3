<template>
    <div class="d-flex justify-center align-center">
        <v-row>
            <v-dialog
                v-model="dialog"
                persistent
                max-width="1000"
                class="height"
            >
                <template v-slot:activator="{ props }">
                    <div v-bind="props" class="bt">
                        <button class="px-3" @click="openCardForm()"><i class="fa-solid fa-gear"></i></button>
                    </div>
                </template>
                <v-card class="height background">
                    <v-card-title>
                        <span class="text-h5 text-white">Update Client Card Statement</span>
                    </v-card-title>
                    <v-card-text>
                        <div v-if="showUpdateCardForm" class="flex">
                            <div class="content gap-20">
                                <div class="personal">
                                    <fieldset>
                                        <legend>Personal Information</legend>
                                    <div class="form-group">
                                        <label for="name" class="mr-4 width100 ">Name</label>
                                        <input type="text" class="rounded text-gray-700">
                                    </div>
                                    <div class="form-group">
                                        <label for="function" class="mr-4 width100 ">Function</label>
                                        <input type="text" class="rounded text-gray-700">
                                    </div>
                                    <div class="form-group">
                                        <label for="phone" class="mr-4 width100 ">Phone</label>
                                        <input type="text" class="rounded text-gray-700" >
                                    </div>
                                    <div class="form-group">
                                        <label for="fax" class="mr-4 width100 ">Fax</label>
                                        <input type="text" class="rounded text-gray-700" >
                                    </div>
                                    <div class="form-group">
                                        <label for="mobile" class="mr-4 width100 ">Mobile</label>
                                        <input type="text" class="rounded text-gray-700" >
                                    </div>
                                    </fieldset>
                                </div>
                                <div class="financial">
                                    <fieldset>
                                        <legend>Financial Information</legend>
                                    <div class="form-group">
                                        <label for="edb" class="mr-4 width100 ">EDB</label>
                                        <input type="text" class="rounded text-gray-700">
                                    </div>
                                    <div class="form-group">
                                        <label for="account" class="mr-4 width100 ">Account</label>
                                        <input type="text" class="rounded text-gray-700">
                                    </div>
                                    <div class="form-group">
                                        <label for="bank" class="mr-4 width100 ">Bank</label>
                                        <input type="text" class="rounded text-gray-700" >
                                    </div>
                                    </fieldset>
                                    <fieldset>
                                        <legend>Additional Information</legend>
                                        <div class="form-group">
                                            <label for="bank" class="mr-4 width100 ">Initial Statement</label>
                                            <input type="text" class="rounded text-gray-700">
                                        </div>
                                        <div class="form-group">
                                            <label for="bank" class="mr-4 width100 ">Initial Balance</label>
                                            <input type="text" class="rounded text-gray-700" >
                                        </div>
                                        <div class="form-group">
                                            <label for="bank" class="mr-4 width100 ">Credit Limit</label>
                                            <input type="text" class="rounded text-gray-700" >
                                        </div>
                                        <div class="form-group">
                                            <label for="bank" class="mr-4 width100 ">Payment Deadline</label>
                                            <input type="text" class="rounded text-gray-700" >
                                        </div>
                                    </fieldset>

                                </div>
                            </div>
                        </div>
                    </v-card-text>
                    <v-card-actions class="flexSpace gap-4">
                        <v-spacer></v-spacer>
                        <SecondaryButton @click="closeDialog" class="red ">Close</SecondaryButton>
                        <SecondaryButton @click="updateCard()" class="green">Update Card Statement</SecondaryButton>
                    </v-card-actions>
                </v-card>
            </v-dialog>
        </v-row>
    </div>
</template>

<script>
import VueMultiselect from 'vue-multiselect'
import SecondaryButton from "@/Components/buttons/SecondaryButton.vue";
import PrimaryButton from "@/Components/buttons/PrimaryButton.vue";
import {useToast} from "vue-toastification";
import axios from "axios";

export default {
    components: {
        PrimaryButton,
        SecondaryButton,
        VueMultiselect
    },
    data() {
        return {
            dialog: false,
            showUpdateCardForm: false,
        };
    },
    methods: {
        openDialog() {
            this.dialog = true;
        },
        closeDialog() {
            this.dialog = false;
        },
        openCardForm() {
            this.showUpdateCardForm = true;
        },
        async updateCard(){

        }
    },
};
</script>
<style src="vue-multiselect/dist/vue-multiselect.css"></style>
<style scoped lang="scss">
.personal{
    width: 400px;
}
.financial{
    width: 480px;
}
fieldset {
    border: 1px solid #ffffff;
    border-radius: 3px;
}
legend {
   margin-left: 10px;
    color: white;
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
.bt{
    margin: 12px 24px;
}
.content{
    display: flex;
    justify-content: space-around;
}
.height {
    height: calc(100vh - 20px);
}
.background {
    background-color: $light-gray;
}
.flexSpace {
    display: flex;
    justify-content: space-around;
}
.orange {
    color: $orange;
}

input {
    margin: 12px 0;
}
.red{
    background-color: $red;
    color:white;
    border: none;
}
.green{
    background-color: $green;
    color: white;
    border: none;
}
</style>

<template>
    <div class="d-flex justify-center align-center">
        <v-row>
            <v-dialog
                v-model="dialog"
                persistent
                max-width="700"
                class="height"
            >
                <template v-slot:activator="{ props }">
                    <div v-bind="props" class="bt">
                        <button class="btn" @click="openAddCertificateForm">Add Statement <i class="fa fa-plus"></i></button>
                    </div>
                </template>
                <v-card class="height background">
                    <v-card-title>
                        <span class="text-h5 text-white">Add New Statement</span>
                    </v-card-title>
                    <v-card-text>
                        <div v-if="showAddCertificateForm">
                            <form>
                                <div>
                                    <div class="dateStyle">
                                        <label for="date" class="mr-4  ">Date</label>
                                        <input type="date" class="rounded text-black" v-model="certificate.date" >
                                    </div>
                                </div>
                                <div class="border p-1">
                                    <div class="form-group">
                                        <label for="Bank" class="mr-4 width100 ">Bank</label>
                                        <input type="text" class="rounded text-black" v-model="certificate.bank" >
                                    </div>
                                    <div class="form-group">
                                        <label for="bankAcc" class="mr-4 width100 ">Account</label>
                                        <input type="text" class="rounded text-gray-700" v-model="certificate.bankAccount">
                                    </div>
                                </div>
                            </form>
                        </div>
                    </v-card-text>
                    <v-card-actions class="flexSpace gap-4">
                        <v-spacer></v-spacer>
                        <SecondaryButton @click="closeDialog" class="red">Close</SecondaryButton>
                        <SecondaryButton @click="addCertificate()" class="green">Save Certificate</SecondaryButton>
                    </v-card-actions>
                </v-card>
            </v-dialog>
        </v-row>
    </div>
</template>

<script>
import SecondaryButton from "@/Components/buttons/SecondaryButton.vue";
import PrimaryButton from "@/Components/buttons/PrimaryButton.vue";
import { useToast } from "vue-toastification";
import axios from "axios";

export default {
    components: {
        PrimaryButton,
        SecondaryButton
    },
    data() {
        return {
            dialog: false,
            showAddCertificateForm: false,
            certificate: {
                date: null,
                bank: '',
                bankAccount: '',
            }
        };
    },
    methods: {
        openDialog() {
            this.dialog = true;
        },
        closeDialog() {
            this.dialog = false;
        },
        openAddCertificateForm() {
            this.showAddCertificateForm = true;
        },
        async addCertificate() {
            const toast = useToast();
            axios.post('/certificate', {
                date: this.certificate.date,
                bank: this.certificate.bank,
                bankAccount: this.certificate.bankAccount,
            })
                .then((response) => {
                    toast.success("Certificate added successfully.");
                })
                .catch((error) => {
                    toast.error("Error adding certificate!");
                });
        }
    }
};
</script>

<style scoped lang="scss">
.dateStyle{
    display: flex;
    justify-content: end;
    align-items: center;
    color: $white;
    margin-bottom: 5px;
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
    padding: 10px 15px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    border-radius: 2px;
    background-color: $blue;
    color: white;
}

.height {
    height: 50vh;
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
</style>

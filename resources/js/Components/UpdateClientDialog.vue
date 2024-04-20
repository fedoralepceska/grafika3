<template>
    <div class="d-flex justify-center align-center">
        <v-row>
            <v-dialog
                v-model="dialog"
                persistent
                max-width="500"
                class="height"
                @keydown.esc="closeDialog"
            >
                <template v-slot:activator="{ props }">
                    <div v-bind="props" class="bt">
                        <button class="px-3" @click="openAddContactForm(client)"><i class="fa-solid fa-gear"></i></button>
                    </div>
                </template>
                <v-card class="height background">
                    <v-card-title>
                        <span class="text-h5 text-white">Update Client</span>
                    </v-card-title>
                    <v-card-text>
                        <div v-if="showUpdateClientForm">
                            <form class="flex">
                                <div class="form-group">
                                    <label for="client" class="mr-4 width100 ">Client Name</label>
                                    <input type="text" class="rounded text-gray-700" v-model="client.name">
                                </div>
                                <div class="form-group">
                                    <label for="city" class="mr-4 width100 ">City</label>
                                    <input type="text" class="rounded text-gray-700" v-model="client.city">
                                </div>
                                <div class="form-group">
                                    <label for="address" class="mr-4 width100 ">Address</label>
                                    <input type="text" class="rounded text-gray-700" v-model="client.address">
                                </div>
                            </form>
                        </div>
                    </v-card-text>
                    <v-card-actions class="flexSpace gap-4">
                        <v-spacer></v-spacer>
                        <SecondaryButton @click="closeDialog" class="red ">Close</SecondaryButton>
                        <SecondaryButton @click="updateContact()" class="green">Update Client</SecondaryButton>
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
            noteComment: '',
            selectedOption: null,
            actionOptions: [],
            jobs: [],
            showUpdateClientForm: false,
            selectedClientId: null,
        };
    },
    props: {
        client: Object
    },
    methods: {
        openDialog() {
            this.dialog = true;
        },
        closeDialog() {
            this.dialog = false;
        },
        openAddContactForm(client) {
            this.selectedClientId = client.id;
            this.showUpdateClientForm = true;
        },
        async updateContact() {
            const toast = useToast();
            try {
                const response = await axios.put(`/clients/${this.client.id}`, {
                    name: this.client.name,
                    city: this.client.city,
                    address: this.client.address
                });
                toast.success(response.data.message);
                this.closeDialog();
            } catch (error) {
                toast.error("Error updating client!");
                console.error(error);
            }
        },
        handleEscapeKey(event) {
            if (event.key === 'Escape') {
                this.closeDialog();
            }
        }
    },
    mounted() {
        document.addEventListener('keydown', this.handleEscapeKey);
    },
};
</script>
<style src="vue-multiselect/dist/vue-multiselect.css"></style>
<style scoped lang="scss">
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
.height {
    height: calc(100vh - 300px);
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
.flex {
    display: flex;
    flex-direction: column;
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

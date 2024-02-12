<template>
    <v-row>
        <v-dialog
            v-model="dialog"
            persistent
            max-width="500"
            class="height"
        >
            <template v-slot:activator="{ props }">
                <div v-bind="props" class="bt">
                    <PrimaryButton @click="openAddContactForm(client)">New Contact</PrimaryButton>
                </div>
            </template>
            <v-card class="height background">
                <v-card-title>
                    <span class="text-h5 text-white">Add new contact</span>
                </v-card-title>
                <v-card-text>
                    <div v-if="showAddContactForm">
                        <form class="flex">
                            <input type="text" v-model="newContact.name" placeholder="Contact Name" required>
                            <input type="text" v-model="newContact.phone" placeholder="Contact Email" required>
                            <input type="email" v-model="newContact.email" placeholder="Contact Phone" required>
                        </form>
                    </div>
                </v-card-text>
                <v-card-actions class="flexSpace gap-4">
                    <v-spacer></v-spacer>
                    <SecondaryButton @click="closeDialog" class="red ">Close</SecondaryButton>
                    <SecondaryButton @click="saveContact()" class="green">Save Contact</SecondaryButton>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-row>
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
            showAddContactForm: false,
            newContact: {
                name: '',
                phone: '',
                email: '',
            },
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
            this.showAddContactForm = true;
        },
        async saveContact() {
            const toast = useToast();
            try {
                const response = await axios.post(`/clients/${this.selectedClientId}/contact`, this.newContact);
                this.showAddContactForm = false; // Hide the form after saving
                toast.success('Contact saved successfully!')
                this.closeDialog();
                window.location.reload();
            } catch (error) {
                toast.error('Error saving contact:', error);
            }
        },
    },
};
</script>
<style src="vue-multiselect/dist/vue-multiselect.css"></style>
<style scoped lang="scss">
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

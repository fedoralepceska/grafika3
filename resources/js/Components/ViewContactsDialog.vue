<template>
    <div class="d-flex justify-center align-center">
    <v-row>
        <v-dialog
            v-model="dialog"
            persistent
            max-width="1200"
            class="height"
            @keydown.esc="closeDialog"
        >
            <template v-slot:activator="{ props }">
                <div v-bind="props" class="bt">
                    <button class="white inline-flex items-center px-4 py-2 border border-transparent white-hover rounded-md font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-50"  @click="openAddContactForm(client)">
                        {{ $t('viewContacts') }}
                    </button>
                </div>
            </template>
            <v-card class="height background">
                <v-card-title>
                    <span class="text-h5 text-white">{{ $t('contactsFor') }} {{client.name}}</span>
                </v-card-title>
                <div class="info">
                    <div v-if="showAddContactForm">
                            <table >
                                <tr>
                                    <th></th>
                                    <th>{{ $t('name') }}</th>
                                    <th>{{ $t('email') }}</th>
                                    <th>{{ $t('phone') }}</th>
                                    <th>{{ $t('delete') }}</th>
                                </tr>
                                <tr v-for="(contact,index) in client.contacts">
                                    <td>#{{index+1}}</td>
                                    <td>{{contact.name}}</td>
                                    <td>{{contact.email}}</td>
                                    <td>{{contact.phone}}</td>
                                    <td style="text-align: center">
                                        <button @click="deleteContact(client, contact)" class="h-10 ml-2 red-text"><i class="fa-solid fa-trash-can"></i></button>
                                    </td>
                                </tr>
                                <tr class="ultra-light-gray">
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>

                                </tr>
                            </table>
                    </div>
                </div>
                <v-card-actions class="flexSpace gap-4">
                    <v-spacer></v-spacer>
                    <div class="flex btns">
                    <SecondaryButton @click="closeDialog" class="red">{{ $t('close') }}</SecondaryButton>
                    <AddContactDialog :client="client"/>
                    </div>
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
import Tab from "@/Components/tabs/Tab.vue";
import AddContactDialog from "@/Components/AddContactDialog.vue";

export default {
    components: {
        AddContactDialog,
        Tab,
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
        async deleteContact(client, contact) {
            try {
                await axios.delete(`/clients/${client.id}/contacts/${contact.id}`);
                // Remove the contact from the clients.contacts array
                const contactIndex = client.contacts.findIndex((c) => c.id === contact.id);
                if (contactIndex !== -1) {
                    client.contacts.splice(contactIndex, 1);
                }
            } catch (error) {
                console.error('Error deleting client:', error);
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
.info{
    padding: 10px 24px 15px;
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
.ultra-light-gray{
    background-color: $ultra-light-gray;
}
.flexSpace {
    display: flex;
    justify-content: space-between;
}
.orange {
    color: $orange;
}
.red{
    background-color: $red;
    color:white;
    border: none;
}

.white {
    background-color: $white;
    color: black;
    &-hover:hover {
        background-color: darken($white, 25%);
    }
}
table{
    color: white;
}
table th, table td{
    padding: 5px;
    width: 300px;
}
table th{
    background-color: $ultra-light-gray;
}
table td, table th{
    border-right: 1px solid $ultra-light-gray;
    border-left: 1px solid $ultra-light-gray;
}
</style>

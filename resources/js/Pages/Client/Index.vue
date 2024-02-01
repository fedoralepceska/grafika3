<template>
    <MainLayout>
        <div class="pl-7 pr-7">
                    <div class="header pt-3 pb-4">
                        <div class="left mr-3">
                            <img src="/images/UserLogo.png" alt="UserLogo" class="image-icon" />
                        </div>
                        <div class="right">
                            <h1 class="page-title">{{ $t('client') }}</h1>
                            <h3 class="text-white"> <span class="green-text">{{ $t('client') }}</span> / {{ $t('allClients') }}</h3>
                        </div>
                    </div>
                <div class="form-container p15">
                    <div class="dark-gray p-5 text-white">
                        <div class="form-container p-2 light-gray">
                            <h2 class="sub-title">
                                {{ $t('allClients') }}
                            </h2>
                            <table>
                                <thead>
                                <tr>
                                    <th>Client Name</th>
                                    <th>Address</th>
                                    <th>City</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                <template v-for="client in clients">
                                    <tr>
                                        <td @click="toggleRow(client.id)" class="company">
                                            <i v-if="clientExpanded === client.id" class="fa-solid fa-chevron-up"></i>
                                            <i v-else class="fa-solid fa-chevron-down"></i>
                                            {{ client.name }}
                                        </td>
                                        <td class="company">
                                            {{ client.address }}
                                        </td>
                                        <td class="company">
                                            {{ client.city }}
                                        </td>
                                        <td class="centered">
                                            <SecondaryButton @click="deleteClient(client)" class="delete">Delete</SecondaryButton>
                                            <AddContactDialog :client="client"/>
                                        </td>
                                    </tr>
                                    <tr v-if="clientExpanded === client.id && client.contacts.length">
                                        <td :colspan="editMode ? 4 : 3">
                                            <div class="contact-info" v-for="(contact,index) in client.contacts" :key="contact.id">
                                                <div class="info mb-1">
                                                    <div class="bg-white text-black pl-1"><strong>Contact #{{index+1}}</strong></div>
                                                    <div class="pl-2">Contact Name: <strong>{{ contact.name }}</strong></div>
                                                    <div class="pl-2">Contact Phone: <strong> {{ contact.phone }}</strong></div>
                                                    <div class="pl-2">Contact E-mail: <strong> {{ contact.email }}</strong></div>
                                                </div>
                                                <SecondaryButton @click="deleteContact(client, contact)" class="delete h-10 ml-2">Delete</SecondaryButton>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                </tbody>
                            </table>

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

export default {
    components: {
        AddContactDialog,
        MainLayout,
        PrimaryButton,
        SecondaryButton
    },
    props: {
        clients: Array, // Pass the list of materials as a prop
    },
    data() {
        return {
            editMode: false,
            clientExpanded: null,
        };
    },
    methods: {
        toggleRow(id) {
            if (this.clientExpanded === id) {
                this.clientExpanded = null;
            } else {
                this.clientExpanded = id;
            }
        },
        async deleteClient(client) {
            const confirmed = confirm('Are you sure you want to delete this client?');
            if (!confirmed) {
                return;
            }

            try {
                await axios.delete(`/clients/${client.id}`);
                // Remove the client from the clients array
                const index = this.clients.findIndex((m) => m.id === client.id);
                if (index !== -1) {
                    this.clients.splice(index, 1);
                }
            } catch (error) {
                console.error('Error deleting client:', error);
            }
            window.location.reload();
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
    }
};
</script>

<style scoped lang="scss">
.centered{
    text-align: center;
    display: flex;
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
}
.green{
    background-color: $green;
}
.green:hover{
    background-color: green;
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
table {
    width: 100%;
    border-collapse: collapse;
    border-spacing: 0;
    margin-bottom: 20px;
    border: 1px solid #ddd;
    border-radius: 5px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

table th, table td {
    padding: 10px;
    text-align: left;
    border: 1px solid #ddd;

}

table th {
    font-weight: bold;
    color: white;
    border-top: 1px solid #ddd;
    border-bottom: 1px solid #ddd;
    background-color: $ultra-light-gray;
}
.company {
    cursor: pointer;
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

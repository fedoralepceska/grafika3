<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="client" subtitle="allClients" icon="UserLogo.png" link="clients"/>
            <div class="form-container p15">
                <div class="dark-gray p-5 text-white">
                    <div class="form-container p-2 light-gray">
                        <h2 class="sub-title">
                            {{ $t('allClients') }}
                        </h2>
                        <div class="search-container p-3 flex">
                            <input type="text" class="text-black rounded" v-model="search" @keyup="fetchClients" placeholder="Search by clients name">
                            <div class="centered mr-1 ml-4 ">Clients per page</div>
                            <div class="ml-3">
                                <select v-model="perPage" class="rounded text-black" @change="fetchClients">
                                    <option value="5">5 per page</option>
                                    <option value="10">10 per page</option>
                                    <option value="20">20 per page</option>
                                    <option value="0">Show All</option>
                                </select>
                            </div>
                        </div>
                        <table style="text-align: center">
                            <thead>
                            <tr>
                                <th>Client Name</th>
                                <th>Address</th>
                                <th>City</th>
                                <th>Contacts</th>
                                <th>New Contact</th>
                                <th>Delete</th>
                            </tr>
                            </thead>
                            <tbody>

                            <template v-for="client in fetchedClients.data" :key="client.id">
                                <tr>
                                    <td class="company">
                                        {{ client.name }}
                                    </td>
                                    <td class="company">
                                        {{ client.address }}
                                    </td>
                                    <td class="company">
                                        {{ client.city }}
                                    </td>
                                    <td>
                                        <div class="centered">
                                            <ViewContactsDialog :client="client"/>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="centered">
                                            <AddContactDialog :client="client"/>
                                        </div>
                                    </td>
                                    <td>
                                        <SecondaryButton @click="deleteClient(client)" class="delete">Delete</SecondaryButton>
                                    </td>
                                </tr>
                            </template>
                            </tbody>
                        </table>
                        <Pagination :pagination="clients"/>
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
import ViewContactsDialog from "@/Components/ViewContactsDialog.vue";
import Pagination from "@/Components/Pagination.vue"
import Header from "@/Components/Header.vue";

export default {
    components: {
        ViewContactsDialog,
        AddContactDialog,
        MainLayout,
        PrimaryButton,
        SecondaryButton,
        Pagination,
        Header
    },
    props: {
        clients: Array, // Pass the list of materials as a prop
    },
    data() {
        return {
            editMode: false,
            clientExpanded: null,
            search: '',
            fetchedClients: [],
            perPage: 10,
        };
    },
    methods: {
        async fetchClients(page = 1) {
            const params = {
                page,
                ...(this.search ? { search: this.search } : {}), // Include search parameter if available
                per_page: this.perPage,
            };
            const response = await axios.get('/clients', { params });
            this.fetchedClients = response.data;
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

    },
    mounted() {
        this.fetchClients();
    }
};
</script>

<style scoped lang="scss">
.centered {
    display: flex;
    justify-content: center;
    align-items: center;
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

table, table td {
    padding: 10px;
    text-align: center;
}
table td, table th{
    border-right: 1px solid #ddd;
    border-left: 1px solid #ddd;
}

table th {
    padding: 10px 5px 10px 5px;
    font-weight: bold;
    color: white;
    border: none;
    background-color: $ultra-light-gray;
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

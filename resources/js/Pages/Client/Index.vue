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
                            <div class="centered mr-1 ml-4 ">{{ $t('clientsPerPage') }}</div>
                            <div class="ml-3">
                                <select v-model="perPage" class="rounded text-black" @change="fetchClients">
                                    <option value="5">{{ $t('fivePerPage') }}</option>
                                    <option value="10">{{ $t('tenPerPage') }}</option>
                                    <option value="20">{{ $t('twentyPerPage') }}</option>
                                    <option value="0">{{ $t('showAll') }}</option>
                                </select>
                            </div>
                        </div>
                        <table style="text-align: center">
                            <thead>
                            <tr>
                                <th>{{ $t('company') }}</th>
                                <th>{{ $t('address') }}</th>
                                <th>{{ $t('city') }}</th>
                                <th>{{ $t('contacts') }}</th>
                                <th>{{ $t('newContact') }}</th>
                                <th>{{ $t('update') }}</th>
                                <th>{{ $t('cardStatement') }}</th>
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
                                        <div class="centered">
                                        <UpdateClientDialog :client="client"/>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="centered">
                                        <CardStatementUpdateDialog :client_id="client.id" @dialogOpened="fetchClientData"/>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            </tbody>
                        </table>
                        <ClientPagination
                            :pagination="fetchedClients"
                            @page-changed="handlePageChange"
                        />
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
import ClientPagination from "@/Components/ClientPagination.vue";
import Header from "@/Components/Header.vue";
import UpdateClientDialog from "@/Components/UpdateClientDialog.vue";
import CardStatementUpdateDialog from "@/Components/CardStatementUpdateDialog.vue";

export default {
    components: {
        CardStatementUpdateDialog,
        UpdateClientDialog,
        ViewContactsDialog,
        AddContactDialog,
        MainLayout,
        PrimaryButton,
        SecondaryButton,
        ClientPagination,
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
            fetchedClients: {
                data: [],
                current_page: 1,
                last_page: 1,
                per_page: 10,
                total: 0
            },
            perPage: 10,
        };
    },
    methods: {
        async fetchClients(page = 1) {
            try {
                const params = {
                    page,
                    search: this.search,
                    per_page: this.perPage,
                };

                const response = await axios.get('/clients', { params });
                this.fetchedClients = response.data;
            } catch (error) {
                console.error('Error fetching clients:', error);
            }
        },
        handlePageChange(page) {
            this.fetchClients(page);
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
        fetchClientData() {
        }
    },
    watch: {
        search(newVal) {
            this.fetchClients(1); // Reset to first page when search changes
        },
        perPage(newVal) {
            this.fetchClients(1); // Reset to first page when perPage changes
        }
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

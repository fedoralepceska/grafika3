<template>
    <MainLayout>
        <div class="pl-7 pr-7">
                    <div class="header pt-10 pb-4">
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

                            <div class="flex justify-end">
                                <button @click="toggleEditMode" class="bg-white rounded text-black py-2 px-5 m-1 ">{{ editMode ? 'Exit Edit Mode' : 'Edit Mode' }}</button>
                                <button @click="saveChanges()" v-if="editMode" class="blue rounded text-white py-2 px-5 m-1">Save Changes<v-icon class="mdi mdi-check"></v-icon></button>
                            </div>

                            <table>
                                <thead>
                                <tr>
                                    <th>Name</th>
                                </tr>
                                </thead>
                                <tbody>
                                <template v-for="client in clients">
                                    <tr>
                                        <td @mouseover="toggleRow(client.id)">{{ client.name }}</td>
                                        <td class="centered">
                                            <SecondaryButton @click="deleteClient(client)" class="delete">Delete</SecondaryButton>
                                        </td>
                                    </tr>
                                    <tr v-if="clientExpanded === client.id && client.contacts.length">
                                        <td :colspan="editMode ? 4 : 3">
                                            <div class="contact-info">
                                                <div v-for="contact in client.contacts" :key="contact.id">
                                                    <div><strong>Contact Name:</strong> {{ contact.name }}</div>
                                                    <div><strong>Contact Phone:</strong> {{ contact.phone }}</div>
                                                    <div><strong>Contact E-mail:</strong> {{ contact.email }}</div>
                                                </div>
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
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import axios from "axios";

export default {
    components: {
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
            clientExpanded: null
        };
    },
    methods: {
        toggleEditMode() {
            this.editMode = !this.editMode;
            if (this.editMode) {
                this.clients.forEach((client) => {
                    if (!client.editablePhone) {
                        client.editablePhone = client.phone;
                    }
                    if (!client.editableEmail) {
                        client.editableEmail = client.email;
                    }
                });
            }
        },
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
        async saveChanges() {
            if (!this.editMode) {
                // Exit edit mode
                return;
            }

            // Handle the save changes action
            const promises = this.clients.map(async (client) => {
                if (
                    client.editablePhone !== client.phone ||
                    client.editableEmail !== client.email
                ) {
                    try {
                        const response = await axios.put(`/clients/${client.id}`, {
                            phone: client.editablePhone,
                            email: client.editableEmail,
                        });
                        // Update the material with the response data
                        client.phone = response.data.phone;
                        client.email = response.data.email;
                    } catch (error) {
                        console.error('Error updating client:', error);
                    }
                }
            });

            await Promise.all(promises);

            // Reset the editable fields and exit edit mode
            this.clients.forEach((client) => {
                client.editablePhone = null;
                client.editableEmail = null;
            });
            this.editMode = false;
            window.location.reload();
        },
    }
};
</script>

<style scoped lang="scss">
.centered{
    text-align: center;
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

}
</style>

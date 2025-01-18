<template>
    <v-row>
        <v-dialog
            v-model="dialog"
            persistent
            max-width="1200"
            class="height"
            @keydown.esc="closeDialog"
        >
            <template v-slot:activator="{ props }">
                <button v-bind="props" class="btn lock-order">View Banks</button>
            </template>
            <v-card class="height background">
                <v-card-title>
                    <span class="text-h5 text-white">List of all the Banks</span>
                </v-card-title>
                <v-card-text>
                    <div>
                        <table class="excel-table">
                            <thead>
                                <tr>
                                    <td>{{$t('Nr')}}</td>
                                    <td>Name</td>
                                    <td>Account</td>
                                    <td>Actions</td>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(bank,index) in banks" :key="index">
                                    <td>{{index+1}}</td>
                                    <td>
                                        <input v-if="editMode && editingBank?.id === bank.id" 
                                            v-model="editingBank.name" 
                                            type="text" 
                                            class="edit-input"
                                        />
                                        <span v-else>{{bank.name}}</span>
                                    </td>
                                    <td>
                                        <input v-if="editMode && editingBank?.id === bank.id" 
                                            v-model="editingBank.address" 
                                            type="text" 
                                            class="edit-input"
                                        />
                                        <span v-else>{{bank.address}}</span>
                                    </td>
                                    <td class="actions">
                                        <template v-if="editMode && editingBank?.id === bank.id">
                                            <SecondaryButton @click="saveChanges" class="green">
                                                Save
                                            </SecondaryButton>
                                            <SecondaryButton @click="cancelEdit" class="red">
                                                Cancel
                                            </SecondaryButton>
                                        </template>
                                        <template v-else>
                                            <SecondaryButton @click="startEdit(bank)" class="blue">
                                                Edit
                                            </SecondaryButton>
                                            <SecondaryButton @click="deleteBank(bank)" class="red">
                                                Delete
                                            </SecondaryButton>
                                        </template>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                    </div>
                </v-card-text>
                <v-card-actions class="flexSpace gap-4">
                    <v-spacer></v-spacer>
                    <SecondaryButton @click="closeDialog" class="red">
                        Close
                    </SecondaryButton>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-row>
</template>

<script>
import VueMultiselect from 'vue-multiselect'
import SecondaryButton from "@/Components/buttons/SecondaryButton.vue";
import {useToast} from "vue-toastification";

export default {
    components: {
        SecondaryButton,
        VueMultiselect
    },
    data() {
        return {
            dialog: false,
            banks: [],
            editMode: false,
            editingBank: null,
        };
    },

    methods: {
        fetchBanks() {
            axios.get('/api/banks')
                .then((response) => {
                    this.banks = response.data;
                })
                .catch((error) => {
                    console.error('Error fetching banks:', error);
                });
        },
        deleteBank(bank) {
            const toast = useToast();
            axios.delete(`/api/banks/${bank.id}`)
                .then((response) => {
                    this.banks = this.banks.filter((b) => b.id !== bank.id);
                    toast.success('Bank deleted successfully!');
                })
                .catch((error) => {
                    console.error('Error deleting bank:', error);
                    toast.error('Failed to delete bank!');
                });
        },
        openDialog() {
            this.dialog = true;
        },
        closeDialog() {
            this.dialog = false;
        },
        handleEscapeKey(event) {
            if (event.key === 'Escape') {
                this.closeDialog();
            }
        },
        startEdit(bank) {
            this.editMode = true;
            this.editingBank = { ...bank };
        },
        cancelEdit() {
            this.editMode = false;
            this.editingBank = null;
        },
        saveChanges() {
            const toast = useToast();
            axios.put(`/api/banks/${this.editingBank.id}`, {
                name: this.editingBank.name,
                address: this.editingBank.address,
            })
                .then((response) => {
                    const index = this.banks.findIndex(b => b.id === this.editingBank.id);
                    if (index !== -1) {
                        this.banks[index] = response.data.bank;
                    }
                    this.editMode = false;
                    this.editingBank = null;
                    toast.success('Bank updated successfully!');
                })
                .catch((error) => {
                    console.error('Error updating bank:', error);
                    toast.error('Failed to update bank!');
                });
        },
    },
    mounted() {
        document.addEventListener('keydown', this.handleEscapeKey);
        this.fetchBanks();
    },
};
</script>
<style src="vue-multiselect/dist/vue-multiselect.css"></style>
<style scoped lang="scss">
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
    height: calc(100vh - 300px);
}
.form-group {
    display: flex;
    justify-content: left;
    align-items: center;
    width: 450px;
    color: $white;
    padding-bottom: 12px;
}
.width100 {
    width: 120px;
}
.background {
    background-color: $light-gray;
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
.red:hover{
    background-color: darkred;
}
.green{
    background-color: $green;
    color: white;
    border: none;
}
.excel-table {
    border-collapse: collapse;
    width: 100%;
    color: white;
}

.excel-table th, .excel-table td {
    border: 1px solid #dddddd;
    padding: 4px;
    text-align: center;
}

.edit-input {
    width: 100%;
    padding: 4px 8px;
    border: 1px solid $blue;
    border-radius: 4px;
    background-color: white;
    color: black;
}

.actions {
    display: flex;
    gap: 8px;
    justify-content: center;
}

.blue {
    background-color: $blue;
    color: white;
    border: none;
}

.blue:hover {
    background-color: darken($blue, 10%);
}

.orange {
    background-color: $orange;
    color: white;
    border: none;
}

.orange:hover {
    background-color: darken($orange, 10%);
}

</style>

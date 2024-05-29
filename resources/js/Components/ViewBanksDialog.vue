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
                                    <td>Delete</td>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(bank,index) in banks" :key="index">
                                    <td>{{index+1}}</td>
                                    <td>{{bank.name}}</td>
                                    <td>{{bank.address}}</td>
                                    <td><SecondaryButton @click="deleteBank(bank)" class="red">
                                        Delete
                                    </SecondaryButton></td>
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
            console.log('Deleting bank with ID:', bank.id);
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
        }
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

</style>

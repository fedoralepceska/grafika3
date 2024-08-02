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
                <button v-bind="props" class="btn lock-order">View Machines</button>
            </template>
            <v-card class="height background">
                <v-card-title>
                    <span class="text-h5 text-white">List of all the Machines</span>
                </v-card-title>
                <v-card-text>
                    <div class="mb-6">
                        <span class="text-h5 text-white">Machines - Cut</span>
                        <table class="excel-table">
                            <thead>
                            <tr>
                                <td>{{$t('Nr')}}</td>
                                <td>Name</td>
                                <td>Actions</td>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="(machineCut,index) in machinesCut" :key="index">
                                <td>{{index+1}}</td>
                                <td class="w-3/4">{{machineCut.name}}</td>
                                <td><SecondaryButton @click="deleteMachine(machineCut)" class="red">
                                    Delete
                                </SecondaryButton></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <div>
                        <span class="text-h5 text-white">Machines - Print</span>
                        <table class="excel-table">
                            <thead>
                            <tr>
                                <td>{{$t('Nr')}}</td>
                                <td>Name</td>
                                <td>Actions</td>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="(machinePrint,index) in machinesPrint" :key="index">
                                <td>{{index+1}}</td>
                                <td class="w-3/4">{{machinePrint.name}}</td>
                                <td><SecondaryButton @click="deleteMachine(machinePrint)" class="red">
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
            machinesCut: [],
            machinesPrint: [],
        };
    },
    methods: {
        fetchMachines() {
            axios.get('/get-machines')
                .then((response) => {
                    this.machinesCut = response.data.machinesCut;
                    this.machinesPrint = response.data.machinesPrint;
                })
                .catch((error) => {
                    console.error('Error fetching machines:', error);
                });
        },
        deleteMachine(machine) {
            const toast = useToast();
            axios.delete(`/machines/${machine.id}`)
                .then((response) => {
                    this.fetchMachines();
                    toast.success('Machine deleted successfully!');
                })
                .catch((error) => {
                    toast.error('Failed to delete machine!');
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
        this.fetchMachines();
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

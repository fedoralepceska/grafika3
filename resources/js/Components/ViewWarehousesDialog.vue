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
                <button v-bind="props" class="btn lock-order">{{ $t('viewWarehouses') }}</button>
            </template>
            <v-card class="height background">
                <v-card-title>
                    <span class="text-h5 text-white">{{ $t('listOfAllTheWarehouses') }}</span>
                </v-card-title>
                <v-card-text>
                    <div>
                        <table class="excel-table">
                            <thead>
                            <tr>
                                <td>{{$t('Nr')}}</td>
                                <td>{{ $t('name') }}</td>
                                <td>{{ $t('address') }}</td>
                                <td>{{ $t('phone') }}</td>
                                <td>{{ $t('delete') }}</td>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="(warehouse,index) in warehouses" :key="index">
                                <td>{{index+1}}</td>
                                <td>{{warehouse.name}}</td>
                                <td>{{warehouse.address}}</td>
                                <td>{{ warehouse.phone }}</td>
                                <td><SecondaryButton @click="deleteWarehouse(warehouse)" class="red">
                                    {{ $t('delete') }}
                                </SecondaryButton></td>
                            </tr>
                            </tbody>
                        </table>

                    </div>
                </v-card-text>
                <v-card-actions class="flexSpace gap-4">
                    <v-spacer></v-spacer>
                    <SecondaryButton @click="closeDialog" class="red">
                        {{ $t('close') }}
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
            warehouses: [],
        };
    },

    methods: {
        fetchWarehouses() {
            axios.get('/api/warehouses')
                .then((response) => {
                    this.warehouses = response.data;
                })
                .catch((error) => {
                    console.error('Error fetching warehouses:', error);
                });
        },
        deleteWarehouse(warehouse) {
            const toast = useToast();
            axios.delete(`/api/warehouse/${warehouse.id}`)
                .then((response) => {
                    this.warehouses = this.warehouses.filter((w) => w.id !== warehouse.id);
                    toast.success('Warehouse deleted successfully!');
                    this.$inertia.visit('/warehouse');
                })
                .catch((error) => {
                    toast.error('Failed to delete warehouse!');
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
        this.fetchWarehouses();
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

<template>
    <div class="d-flex justify-center align-center">
        <v-row>
            <v-dialog
                v-model="dialog"
                persistent
                width="800"
                class="height"
                @keydown.esc="closeDialog"
            >
                <template v-slot:activator="{ props }">
                    <div v-bind="props" class="bt">
                        <button  class="btn" @click="openAddIncomingInvoice">Add Incoming Invoice <i class="fa fa-plus"></i></button>
                    </div>
                </template>
                <v-card class="height background">
                    <v-card-title>
                        <span class="text-h5 text-white">Add New Incoming Invoice</span>
                    </v-card-title>
                    <v-card-text>
                        <div v-if="showAddIncomingInvoiceForm">
                            <form>
                                <div class="border p-1">
                                    <div class="client">
                                        <div class="flex items-center text-white">
                                            <div class="form-group">
                                                <label for="client" class="mr-4 width100">Client</label>
                                                <select v-model="newInvoice.client_id">
                                                    <option v-for="client in uniqueClients" :key="client.id" :value="client.id" class="text-gray-700">{{ client.name }}</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="date" class="pl-8 mr-11">Date</label>
                                                <input type="date" class="rounded text-black" v-model="newInvoice.date">
                                            </div>
                                        </div>
                                        <div class="flex items-center text-white">
                                            <div class="form-group">
                                                <label for="address" class="mr-4 width100">Address</label>
                                                <input type="text" disabled id="address" class="text-gray-700 rounded" placeholder="s">
                                            </div>
                                        </div>
                                        <div class="flex items-center text-white">
                                            <div class="form-group">
                                                <label for="phone" class="mr-4 width100">Phone/Fax</label>
                                                <input type="text" disabled id="phone" class="text-gray-700 rounded"  placeholder="s">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="warehouse mt-8">
                                        <div class="flex items-center text-white">
                                            <div class="form-group">
                                                <label for="warehouse" class="mr-4 width100">Warehouse</label>
                                                <select>
<!--
                                                    TODO
-->
                                                </select>
                                            </div>
                                        </div>
                                        <div class="flex items-center text-white">
                                            <div class="form-group">
                                                <label for="cost" class="mr-4 width100">Cost Type</label>
                                                <select>
                                                    <!--
                                                                                                        TODO
                                                    -->
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="cost" class="pl-8 mr-4">Bill Type</label>
                                                <select>
                                                    <!--
                                                                                                        TODO
                                                    -->
                                                </select>
                                            </div>
                                        </div>
                                        <div class="flex items-center text-white">
                                            <div class="form-group">
                                                <label for="desc" class="mr-4 width100">Description</label>
                                                <input type="text" id="desc" class="text-gray-700 rounded" v-model="newInvoice.description">
                                            </div>
                                        </div>
                                        <div class="flex items-center text-white">
                                            <div class="form-group">
                                                <label for="desc" class="mr-4 width100">Comment</label>
                                                <input type="text" id="comm" class="text-gray-700 rounded" v-model="newInvoice.comment">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="border mt-1 p-1">
                                    <div class="flex items-center text-white">
                                        <div class="form-group">
                                            <label for="tax" class="mr-4 width100">Amount TAX A</label>
                                            <input type="number" id="taxA" class="text-gray-700 rounded">
                                            <input type="text" id="taxA2" disabled class="rounded text-gray-500" :placeholder="'18' + '.' + '00'">
                                        </div>
                                    </div>
                                    <div class="flex items-center text-white">
                                        <div class="form-group">
                                            <label for="tax" class="mr-4 width100">Amount TAX B</label>
                                            <input type="number" id="taxA" class="text-gray-700 rounded">
                                            <input type="text" id="taxA2" disabled class="rounded text-gray-500" :placeholder="'5' + '.' + '00'">
                                        </div>
                                        <div class="form-group">
                                            <label for="amount" class=" pl-8 mr-4 width100 price">Amount:</label>
                                            <input type="number" class="text-gray-700 rounded" v-model="newInvoice.amount">
                                        </div>
                                    </div>
                                    <div class="flex items-center text-white">
                                        <div class="form-group">
                                            <label for="tax" class="mr-4 width100">Amount TAX C</label>
                                            <input type="number" id="taxA" class="text-gray-700 rounded">
                                            <input type="text" id="taxA2" disabled class="rounded text-gray-500" :placeholder="'10' + '.' + '00'">
                                        </div>
                                        <div class="form-group">
                                            <label for="amount" class=" pl-8 mr-4 width100 price">Tax:</label>
                                            <input type="number" class="text-gray-700 rounded" v-model="newInvoice.tax">
                                        </div>
                                    </div>
                                    <div class="flex items-center text-white">
                                        <div class="form-group">
                                            <label for="tax" class="mr-4 width100">Amount TAX C</label>
                                            <input type="number" id="taxA" class="text-gray-700 rounded">
                                            <input type="text" id="taxA2" disabled class="rounded text-gray-500" :placeholder="'00' + '.' + '00'">
                                        </div>
                                        <div>
                                            <div class="form-group">
                                                <label for="amount" class=" pl-8 mr-4 width100 price">Total:</label>
                                                <input type="number" class="text-gray-700 rounded">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </v-card-text>
                    <v-card-actions class="flexSpace gap-4">
                        <v-spacer></v-spacer>
                        <SecondaryButton @click="closeDialog" class="red">Close</SecondaryButton>
                        <SecondaryButton @click="addIncomingInvoice()" class="green">Save Incoming Invoice</SecondaryButton>
                    </v-card-actions>
                </v-card>
            </v-dialog>
        </v-row>
    </div>
</template>

<script>
import SecondaryButton from "@/Components/buttons/SecondaryButton.vue";
import PrimaryButton from "@/Components/buttons/PrimaryButton.vue";
import { useToast } from "vue-toastification";
import axios from "axios";

export default {
    components: {
        PrimaryButton,
        SecondaryButton
    },
    data() {
        return {
            dialog: false,
            showAddIncomingInvoiceForm: false,
            uniqueClients:[],
            newInvoice:{
                client_id: null,
                description: '',
                comment: '',
                amount: 0.0,
                tax: 0.0,
                date: null
            },
        };
    },
    props: {
        incomingInvoice: Object,
    },
    methods: {
        openDialog() {
            this.dialog = true;
        },
        closeDialog() {
            this.dialog = false;
        },
        openAddIncomingInvoice() {
            this.showAddIncomingInvoiceForm = true;
        },
        async addIncomingInvoice() {
            const toast = useToast();
            try {
                const response = await axios.post('/incomingInvoice', this.newInvoice);
                toast.success('Incoming invoice added successfully!');
                this.closeDialog();
            } catch (error) {
                toast.error('Error adding incoming invoice: ' + error.message);
            }
        },
        async fetchUniqueClients() {
            try {
                const response = await axios.get('/unique-clients');
                this.uniqueClients = response.data;
            } catch (error) {
                console.error(error);
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
        this.fetchUniqueClients()

    },
};
</script>

<style scoped lang="scss">
.price{
    font-weight: bolder;
}
#taxA{
    width: 120px;
}
#taxA2{
    width: 80px;
}
.dateStyle{
    display: flex;
    justify-content: end;
    align-items: center;
    color: $white;
    margin-bottom: 5px;
}
.form-group {
    display: flex;
    justify-content: left;
    align-items: center;
    width: 350px;
    color: $white;
    padding-left: 10px;
}
.width100 {
    width: 150px;
}
.type{
    display: flex;
    justify-content: space-evenly;
}
.label-input-group {
    display: flex;
    flex-direction: column;
}
input {
    margin: 0 !important;
}
select{
    color: black;
    width: 225px;
    border-radius: 3px;
}
.btn {
    padding: 9px 12px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    border-radius: 2px;
    background-color: $blue;
    margin-top: 12px;
}

.height {
    height: 100vh;
}
.background {
    background-color: $light-gray;
}
.flexSpace {
    display: flex;
    justify-content: space-around;
}
input {
    margin: 12px 0;
}
.red {
    background-color: $red;
    color: white;
    border: none;
}
.green {
    background-color: $green;
    color: white;
    border: none;
}
.redBackground{
    background-color: $red;
}
.greenBackground{
    background-color: $green;
}
</style>

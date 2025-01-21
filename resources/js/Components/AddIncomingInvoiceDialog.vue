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
                                                <input type="text" disabled id="address" class="text-gray-700 rounded" :value="selectedClientDetails.address">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="warehouse mt-8">
                                        <div class="flex items-center text-white">
                                            <div class="form-group">
                                                <label for="warehouse" class="mr-4 width100">Warehouse</label>
                                                <select v-model="newInvoice.warehouse">
                                                    <option value="">Select Warehouse</option>
                                                    <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.name" class="text-gray-700">
                                                        {{ warehouse.name }}
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="flex items-center text-white">
                                            <div class="form-group">
                                                <label for="cost" class="mr-4 width100">Cost Type</label>
                                                <select v-model="newInvoice.cost_type">
                                                    <option value="">Select Cost Type</option>
                                                    <option v-for="type in costTypes" :key="type.id" :value="type.id">{{ type.name }}</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="cost" class="pl-8 mr-4">Bill Type</label>
                                                <select v-model="newInvoice.billing_type">
                                                    <option value="">Select Bill Type</option>
                                                    <option v-for="type in billTypes" :key="type.id" :value="type.id">{{ type.name }}</option>
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
                                            <input type="number" id="taxA" class="text-gray-700 rounded" v-model.number="taxAmounts.taxA">
                                            <input type="text" id="taxA2" disabled class="rounded text-gray-500" :placeholder="'18' + '.' + '00'">
                                        </div>
                                    </div>
                                    <div class="flex items-center text-white">
                                        <div class="form-group">
                                            <label for="tax" class="mr-4 width100">Amount TAX B</label>
                                            <input type="number" id="taxA" class="text-gray-700 rounded" v-model.number="taxAmounts.taxB">
                                            <input type="text" id="taxA2" disabled class="rounded text-gray-500" :placeholder="'5' + '.' + '00'">
                                        </div>
                                        <div class="form-group">
                                            <label for="amount" class=" pl-8 mr-4 width100 price">Amount:</label>
                                            <input type="text" class="text-gray-700 rounded" :value="calculatedAmount" disabled>
                                        </div>
                                    </div>
                                    <div class="flex items-center text-white">
                                        <div class="form-group">
                                            <label for="tax" class="mr-4 width100">Amount TAX C</label>
                                            <input type="number" id="taxA" class="text-gray-700 rounded" v-model.number="taxAmounts.taxC">
                                            <input type="text" id="taxA2" disabled class="rounded text-gray-500" :placeholder="'10' + '.' + '00'">
                                        </div>
                                        <div class="form-group">
                                            <label for="amount" class=" pl-8 mr-4 width100 price">Tax:</label>
                                            <input type="text" class="text-gray-700 rounded" :value="calculatedTax" disabled>
                                        </div>
                                    </div>
                                    <div class="flex items-center text-white">
                                        <div class="form-group">
                                            <label for="tax" class="mr-4 width100">Amount TAX D</label>
                                            <input type="number" id="taxA" class="text-gray-700 rounded" v-model.number="taxAmounts.taxD">
                                            <input type="text" id="taxA2" disabled class="rounded text-gray-500" :placeholder="'00' + '.' + '00'">
                                        </div>
                                        <div>
                                            <div class="form-group">
                                                <label for="amount" class=" pl-8 mr-4 width100 price">Total:</label>
                                                <input type="text" class="text-gray-700 rounded" :value="calculatedTotal" disabled>
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
    props: {
        incomingInvoice: Object,
        costTypes: {
            type: Array,
            required: true,
            default: () => []
        },
        billTypes: {
            type: Array,
            required: true,
            default: () => []
        }
    },
    data() {
        return {
            dialog: false,
            showAddIncomingInvoiceForm: false,
            uniqueClients:[],
            warehouses: [],
            taxAmounts: {
                taxA: 0,
                taxB: 0,
                taxC: 0,
                taxD: 0
            },
            taxRates: {
                taxA: 18,
                taxB: 5,
                taxC: 10,
                taxD: 0
            },
            newInvoice:{
                client_id: null,
                warehouse: '',
                cost_type: null,
                billing_type: null,
                description: '',
                comment: '',
                amount: 0.0,
                tax: 0.0,
                date: null
            },
            selectedClientDetails: {
                address: '',
            },
        };
    },
    computed: {
        calculatedAmount() {
            return this.formatNumber(this.newInvoice.amount);
        },
        calculatedTax() {
            return this.formatNumber(this.newInvoice.tax);
        },
        calculatedTotal() {
            return this.formatNumber(this.newInvoice.total);
        }
    },
    watch: {
        'newInvoice.client_id': {
            async handler(newClientId) {
                if (newClientId) {
                    await this.fetchClientDetails(newClientId);
                } else {
                    this.selectedClientDetails = {
                        address: '',
                    };
                }
            }
        },
        taxAmounts: {
            deep: true,
            handler() {
                const amount = Object.values(this.taxAmounts).reduce((sum, amount) => sum + (amount || 0), 0);
                const tax = Object.entries(this.taxAmounts).reduce((sum, [key, amount]) => {
                    const rate = this.taxRates[key] / 100;
                    return sum + ((amount || 0) * rate);
                }, 0);
                
                this.newInvoice.amount = amount;
                this.newInvoice.tax = tax;
                this.newInvoice.total = amount + tax;
            }
        }
    },
    methods: {
        formatNumber(value) {
            const num = Number(value);
            return num.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        },
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
                const payload = {
                    client_id: this.newInvoice.client_id,
                    warehouse: this.newInvoice.warehouse,
                    cost_type: this.newInvoice.cost_type,
                    billing_type: this.newInvoice.billing_type,
                    description: this.newInvoice.description,
                    comment: this.newInvoice.comment,
                    amount: this.newInvoice.amount,
                    tax: this.newInvoice.tax,
                    date: this.newInvoice.date
                };

                const response = await axios.post('/incomingInvoice', payload);
                toast.success('Incoming invoice added successfully!');
                this.closeDialog();
                this.resetForm();
            } catch (error) {
                toast.error('Error adding incoming invoice: ' + error.message);
            }
        },
        resetForm() {
            this.newInvoice = {
                client_id: null,
                warehouse: '',
                cost_type: null,
                billing_type: null,
                description: '',
                comment: '',
                amount: 0.0,
                tax: 0.0,
                date: null
            };
            this.taxAmounts = {
                taxA: 0,
                taxB: 0,
                taxC: 0,
                taxD: 0
            };
            this.selectedClientDetails = {
                address: '',
            };
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
        },
        async fetchClientDetails(clientId) {
            try {
                const response = await axios.get(`/client-details/${clientId}`);
                this.selectedClientDetails = {
                    address: response.data.address,
                };
            } catch (error) {
                console.error('Error fetching client details:', error);
                const toast = useToast();
                toast.error('Error fetching client details');
            }
        },
        async fetchWarehouses() {
            try {
                const response = await axios.get('/api/warehouses');
                this.warehouses = response.data;
            } catch (error) {
                console.error('Error fetching warehouses:', error);
                const toast = useToast();
                toast.error('Error fetching warehouses');
            }
        },
        handleTaxInput(type, event) {
            const value = event.target.value.replace(/,/g, "");
            if (value === "") {
                this.taxAmounts[type] = 0;
                return;
            }
            const numericValue = parseFloat(value) || 0;
            this.taxAmounts[type] = numericValue;
        }
    },
    mounted() {
        document.addEventListener('keydown', this.handleEscapeKey);
        this.fetchUniqueClients();
        this.fetchWarehouses();
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

<template>
    <div class="d-flex justify-center align-center">
        <v-row>
            <v-dialog
                v-model="dialog"
                persistent
                max-width="700"
                class="height"
                @keydown.esc="closeDialog"
            >
                <template v-slot:activator="{ props }">
                    <div v-bind="props" class="bt">
                        <button class="btn" @click="openAddItemForm">Add Item <i class="fa fa-plus"></i></button>
                    </div>
                </template>
                <v-card class="height background">
                    <v-card-title>
                        <span class="text-h5 text-white">Add New Item</span>
                    </v-card-title>
                    <v-card-text>
                        <div v-if="showAddItemForm">
                            <form>
                            <div class="border p-1 d-flex justify-center">
                                <div class="flex items-center text-white">
                                    <div class="form-group">
                                        <label for="statement" class="mr-4 width100">Statement ID</label>
                                        <input type="text" disabled id="statement" class="text-gray-700 rounded"  :placeholder="certificate.id_per_bank">
                                    </div>
                                </div>
                                <div class="flex text-white">
                                    <div class="flex items-center">
                                        <div class="form-group">
                                            <label for="bank" class="mr-4 width100">Bank Account</label>
                                            <input type="text" disabled id="bank" class="text-gray-700 rounded"  :placeholder="certificate.bank">
                                        </div>
                                    </div>
                                    <div class="flex items-center">
                                        <div class="form-group">
                                            <input type="text" disabled id="bankAcc" class="text-gray-700 rounded"  :placeholder="certificate.bankAccount">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="border p-1 justify-center mt-1">
                                <div class="flex items-center text-white">
                                    <div class="form-group">
                                        <label for="client" class="mr-4 width100">Client</label>
                                        <select v-model="newItem.client_id">
                                            <option v-for="client in clients" :key="client.id" :value="client.id" class="text-gray-700">{{ client.name }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="flex items-center text-white">
                                    <div class="form-group">
                                        <label for="address" class="mr-4 width100">Address</label>
                                        <input type="text" disabled id="address" class="text-gray-700 rounded"  :placeholder="getClient?.address">
                                     </div>
                                </div>
                                <div class="flex items-center text-white">
                                    <div class="form-group">
                                        <label for="phone" class="mr-4 width100">Phone/Fax</label>
                                        <input type="text" disabled id="phone" class="text-gray-700 rounded"  :placeholder="getClient?.phone">
                                    </div>
                                </div>
                                <div class="flex items-center text-white">
                                    <div class="form-group">
                                        <label for="account" class="mr-4 width100">Account</label>
                                        <input type="text" disabled id="account" class="text-gray-700 rounded" :placeholder="getClient?.client_card_statement?.account">
                                    </div>
                                </div>
                            </div>
                            <div class="border p-1 mt-1">
                                <div class="type">
                                    <div class="label-input-group">
                                        <label class="text-white">Income</label>
                                        <input
                                            type="text"
                                            class="rounded"
                                            :value="formattedIncome"
                                            @input="handleInputIncome"
                                            placeholder="Expense"
                                            required
                                        />
                                    </div>
                                    <div class="label-input-group">
                                        <label class="text-white">Expense</label>
                                        <input
                                            type="text"
                                            class="rounded"
                                            :value="formattedExpense"
                                            @input="handleInputExpense"
                                            placeholder="Expense"
                                            required
                                        />
                                    </div>
                                </div>
                                <div class="p-2">
                                    <div class="pb-8 redBackground"></div>
                                </div>
                                <div class="form-group">
                                    <label for="code" class="mr-4 width100 r">Code</label>
                                    <input type="text" class="rounded text-black" v-model="newItem.code" >
                                </div>
                                <div class="form-group">
                                    <label for="reference" class="mr-4 width100 r">Reference To</label>
                                    <input type="text" class="rounded text-black max-width" v-model="newItem.reference_to">

                                </div>
                                <div class="form-group">
                                    <label for="comment" class="mr-4 width100 r">Comment</label>
                                    <input type="text" class="rounded text-black" v-model="newItem.comment" >
                                </div>
                            </div>
                            </form>
                        </div>
                    </v-card-text>
                    <v-card-actions class="flexSpace gap-4">
                        <v-spacer></v-spacer>
                        <SecondaryButton @click="closeDialog" class="red">Close</SecondaryButton>
                        <SecondaryButton @click="saveItem()" class="green">Save Item</SecondaryButton>
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
            showAddItemForm: false,
            clients:[],
            newItem: {
                client_id: null,
                certificate_id: this.certificate.id,
                income: 0,
                expense: 0,
                code: '',
                reference_to: '',
                comment: ''
            },
            rawIncome: "",
            rawExpense: "", // Raw value without formatting
            timeout: null,  // Reference for delay timeout
        };
    },
    props: {
        certificate: Object,
    },
    computed: {
        getClient() {
            return this.clients.find(c => c.id === this.newItem?.client_id);
        },
        formattedExpense() {
            // Format the rawExpense value with commas
            if (this.rawExpense === "") return "";
            const value = parseFloat(this.rawExpense.replace(/,/g, "")) || 0;
            return value.toLocaleString("en-US");
        },
        formattedIncome() {
            // Format the rawExpense value with commas
            if (this.rawIncome === "") return "";
            const value = parseFloat(this.rawIncome.replace(/,/g, "")) || 0;
            return value.toLocaleString("en-US");
        },
    },
    methods: {
        openDialog() {
            this.dialog = true;
        },
        closeDialog() {
            this.dialog = false;
        },
        openAddItemForm() {
            this.showAddItemForm = true;
        },
        async saveItem() {
            const toast = useToast();
            try {
                const response = await axios.post('/item', this.newItem);
                toast.success('Item added successfully!');
                this.closeDialog();
                this.$inertia.visit(`/statements/${this.certificate.id}`);
            } catch (error) {
                toast.error('Error adding item: ' + error.message);
            }
        },
        fetchClients() {
            axios.get('/api/clients') // Adjust the URL to your endpoint
                .then(response => {
                    this.clients = response.data;
                })
                .catch(error => {
                    console.error('Error fetching clients:', error);
                });
        },
        handleEscapeKey(event) {
            if (event.key === 'Escape') {
                this.closeDialog();
            }
        },
        handleInputExpense(event) {
            const input = event.target.value.replace(/,/g, ""); // Remove commas

            // Ensure the input is numeric
            if (isNaN(input)) return;

            // Update the raw value
            this.rawExpense = input;
            this.newItem.expense = input;

            // Clear the previous timeout
            if (this.timeout) clearTimeout(this.timeout);

            // Add `.00` after 1 second of inactivity
            this.timeout = setTimeout(() => {
                const value = parseFloat(this.rawExpense.replace(/,/g, "")) || 0;
                this.rawExpense = value.toFixed(2); // Update rawExpense with .00
            }, 1000);
        },
        handleInputIncome(event) {
            const input = event.target.value.replace(/,/g, ""); // Remove commas

            // Ensure the input is numeric
            if (isNaN(input)) return;

            // Update the raw value
            this.rawIncome = input;
            this.newItem.income = input;

            // Clear the previous timeout
            if (this.timeout) clearTimeout(this.timeout);

            // Add `.00` after 1 second of inactivity
            this.timeout = setTimeout(() => {
                const value = parseFloat(this.rawIncome.replace(/,/g, "")) || 0;
                this.rawIncome = value.toFixed(2); // Update rawExpense with .00
            }, 1000);
        },
    },
    mounted() {
        document.addEventListener('keydown', this.handleEscapeKey);
        this.fetchClients()
    },
};
</script>

<style scoped lang="scss">
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
select{
    color: black;
    width: 225px;
    border-radius: 3px;
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

<template>
    <div class="d-flex justify-center align-center">
        <v-row>
            <v-dialog
                v-model="dialog"
                persistent
                width="500"
                class="height"
                @keydown.esc="closeDialog"
            >
                <template v-slot:activator="{ props }">
                    <div v-bind="props" class="bt">
                        <button class="btn" @click="openAddCertificateForm">Add Statement <i class="fa fa-plus"></i></button>
                    </div>
                </template>
                <v-card class="height background">
                    <v-card-title>
                        <span class="text-h5 text-white">Add New Statement</span>
                    </v-card-title>
                    <v-card-text>
                        <div v-if="showAddCertificateForm">
                            <form>
                                <div>
                                    <div class="dateStyle">
                                        <label for="date" class="mr-4">Date</label>
                                        <input type="date" class="rounded text-black" v-model="certificate.date">
                                    </div>
                                </div>
                                <div class="border p-1">
                                    <div class="form-group pb-3">
                                        <label for="Bank" class="mr-4 width100">Bank</label>
                                        <select v-model="selectedBank" @change="setSelectedBank" class="text-black rounded" style="width: 31.8vh">
                                            <option value="" class="text-black">Select Bank</option>
                                            <option v-for="bank in banks" :key="bank.id" :value="bank" class="text-black">{{ bank.name }}</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="bankAcc" class="mr-4 width100">Account</label>
                                        <input type="text" class="rounded text-gray-700" v-model="certificate.bankAccount" :disabled="!selectedBank">
                                    </div>
                                </div>
                            </form>
                        </div>
                    </v-card-text>
                    <v-card-actions class="flexSpace gap-4">
                        <v-spacer></v-spacer>
                        <SecondaryButton @click="closeDialog" class="red">Close</SecondaryButton>
                        <SecondaryButton @click="addCertificate()" class="green">Save Statement</SecondaryButton>
                    </v-card-actions>
                </v-card>
            </v-dialog>
        </v-row>
    </div>
</template>


<script>
import SecondaryButton from "@/Components/buttons/SecondaryButton.vue";
import { useToast } from "vue-toastification";
import axios from "axios";

export default {
    components: {
        SecondaryButton
    },
    data() {
        return {
            dialog: false,
            showAddCertificateForm: false,
            certificate: {
                date: null,
                bank: '',
                bankAccount: '',
            },
            banks: [],
            selectedBank: null
        };
    },
    methods: {
        setSelectedBank() {
            if (this.selectedBank) {
                this.certificate.bankAccount = this.selectedBank.address;
            } else {
                this.certificate.bankAccount = '';
            }
        },
        async fetchBanks() {
            try {
                const response = await axios.get('/api/banks');
                this.banks = response.data;
            } catch (error) {
                console.error('Error fetching banks:', error);
            }
        },
        openDialog() {
            this.dialog = true;
        },
        closeDialog() {
            this.dialog = false;
        },
        openAddCertificateForm() {
            this.showAddCertificateForm = true;
        },
        async addCertificate() {
            if (!this.selectedBank) {
                const toast = useToast();
                toast.error("Please select a bank!");
                return;
            }

            const toast = useToast();
            try {
                const response = await axios.post('/certificate', {
                    date: this.certificate.date,
                    bank: this.selectedBank.name, // Use bank name from selected object
                    bankAccount: this.certificate.bankAccount,
                });

                toast.success("Certificate added successfully.");
                // Reset form data
                this.certificate.date = null;
                this.selectedBank = null;
                this.certificate.bankAccount = '';

                setTimeout(() => {
                    window.location.reload();
                }, 1000); // Adding a slight delay before reload to ensure the toast message is displayed

            } catch (error) {
                console.error('Error adding certificate:', error);
                toast.error("Error adding certificate!");
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
        this.fetchBanks();
    },
};
</script>

<style scoped lang="scss">
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
    height: 50vh;
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

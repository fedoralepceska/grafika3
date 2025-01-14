<template>
    <v-row>
        <v-dialog
            v-model="dialog"
            persistent
            max-width="400"
            class="height"
            @keydown.esc="closeDialog"
        >
            <template v-slot:activator="{ props }">
                <button v-bind="props" class="btn lock-order">Add Bank</button>
            </template>
            <v-card class="height background">
                <v-card-title>
                    <span class="text-h5 text-white">Add New Bank</span>
                </v-card-title>
                <v-card-text>
                    <div>
                        <div class="form-group">
                            <label for="name" class="text-white width100">Bank Name</label>
                            <input type="text" id="name" class="rounded text-black" v-model="newBank.name" @input="checkDuplicate">
                        </div>
                        <div class="form-group" >
                            <label for="address" class="text-white width100 ">Bank Account</label>
                            <input type="text"  id="address" class="rounded text-black" v-model="newBank.address" @input="checkDuplicate">
                        </div>
                        <div v-if="isDuplicate" class="error-message">
                            This bank with this account already exists!
                        </div>
                    </div>
                </v-card-text>
                <v-card-actions class="flexSpace gap-4">
                    <v-spacer></v-spacer>
                    <SecondaryButton @click="closeDialog" class="red">
                        Close
                    </SecondaryButton>
                    <SecondaryButton @click="saveData" class="green" :disabled="isDuplicate || !isValid">
                       Add
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
            newBank: {
                name: '',
                address: '',
            },
            existingBanks: [],
            isDuplicate: false,
        };
    },
    computed: {
        isValid() {
            return this.newBank.name.trim() !== '' && this.newBank.address.trim() !== '';
        }
    },
    props: {
        bank: Object
    },
    methods: {
        openDialog() {
            this.dialog = true;
        },
        closeDialog() {
            this.dialog = false;
            this.newBank.name = '';
            this.newBank.address = '';
            this.isDuplicate = false;
        },
        async fetchBanks() {
            try {
                const response = await axios.get('/api/banks');
                this.existingBanks = response.data;
            } catch (error) {
                console.error('Error fetching banks:', error);
            }
        },
        checkDuplicate() {
            if (this.newBank.name && this.newBank.address) {
                this.isDuplicate = this.existingBanks.some(bank => 
                    bank.name.toLowerCase() === this.newBank.name.toLowerCase() && 
                    bank.address.toLowerCase() === this.newBank.address.toLowerCase()
                );
            } else {
                this.isDuplicate = false;
            }
        },
        saveData() {
            if (this.isDuplicate) return;

            const toast = useToast();
            axios.post('/api/banks', this.newBank)
                .then((response) => {
                    this.dialog = false;
                    toast.success('Bank created successfully!');

                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                })
                .catch((error) => {
                    console.error('Error creating bank:', error);
                    toast.error('Failed to create bank!');
                });
        },
        handleEscapeKey(event) {
            if (event.key === 'Escape') {
                this.closeDialog();
            }
        }
    },
    async mounted() {
        document.addEventListener('keydown', this.handleEscapeKey);
        await this.fetchBanks();
    },
    beforeUnmount() {
        document.removeEventListener('keydown', this.handleEscapeKey);
    }
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
    height: calc(100vh - 400px);
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
.green{
    background-color: $green;
    color: white;
    border: none;

    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
}
.error-message {
    color: $red;
    margin-top: 5px;
    font-size: 0.9em;
}
</style>

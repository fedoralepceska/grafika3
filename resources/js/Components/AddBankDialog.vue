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
                            <input type="text" id="name" class="rounded text-black" v-model="newBank.name">
                        </div>
                        <div class="form-group" >
                            <label for="address" class="text-white width100 ">Bank Account</label>
                            <input type="text"  id="address" class="rounded text-black" v-model="newBank.address">
                        </div>

                    </div>
                </v-card-text>
                <v-card-actions class="flexSpace gap-4">
                    <v-spacer></v-spacer>
                    <SecondaryButton @click="closeDialog" class="red">
                        Close
                    </SecondaryButton>
                    <SecondaryButton @click="saveData" class="green">
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
            newBank:{
            name: '',
            address:'',
            }
        };
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
        },
        saveData() {
            const toast = useToast();
            axios.post('/api/banks', this.newBank)
                .then((response) => {
                    this.dialog = false;
                    toast.success('Bank created successfully!');

                    setTimeout(() => {
                        window.location.reload();
                    }, 1000); // Adding a slight delay before reload to ensure the toast message is displayed

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
    mounted() {
        document.addEventListener('keydown', this.handleEscapeKey);
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
}
</style>

<template>
    <v-row>
        <v-dialog
            v-model="dialog"
            persistent
            max-width="500"
            class="height"
        >
            <template v-slot:activator="{ props }">
                <button v-bind="props" class="btn lock-order">Lock Order <span class="mdi mdi-lock"></span></button>
            </template>
            <v-card class="height background">
                <v-card-title>
                    <span class="text-h5 text-white">Lock Order</span>
                </v-card-title>
                <v-card-text>
                    <textarea v-model="lockNote" style="width: 450px"/>
                </v-card-text>
                <v-card-actions class="flexSpace gap-4">
                    <v-spacer></v-spacer>
                    <SecondaryButton @click="closeDialog" class="red">
                        Close
                    </SecondaryButton>
                    <SecondaryButton @click="saveData" class="green">
                        Lock
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
            lockNote: '',
        };
    },
    props: {
        invoice: Object
    },
    async beforeMount() {
        this.lockNote = this.invoice.LockedNote;
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
            axios.put('/orders/update-locked-note', {
                id: this.invoice.id,
                comment: this.lockNote,
            }).then(response => {
                this.invoice.LockedNote = this.lockNote;
                toast.success('Order successfully locked.');
            }).catch(error => {
                toast.error('Error locking order!');
            });
            this.closeDialog();
        },
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

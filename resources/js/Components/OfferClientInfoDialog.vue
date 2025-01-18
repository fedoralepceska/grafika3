<template>
    <div class="d-flex justify-center align-center">
        <v-row>
            <v-dialog
                v-model="dialog"
                persistent
                max-width="1200"
                class="height"
                @keydown.esc="closeDialog"
            >
                <template v-slot:activator="{ props }">
                    <div v-bind="props" class="bt">
                        <button class="white inline-flex items-center px-4 py-2 border border-transparent white-hover rounded-md font-semibold text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-50"  @click="getDetails"><i class="fa-solid fa-circle-info"></i></button>
                    </div>
                </template>
                <v-card class="height background">
                    <v-card-title>
                        <span class="text-h5 text-white">{{ $t('additionalInfo') }}</span>
                    </v-card-title>
                    <div class="info">
                            <table class="excel-table">
                            </table>
                    </div>
                    <v-card-actions class="flexSpace gap-4">
                        <v-spacer></v-spacer>
                        <div class="flex btns">
                            <SecondaryButton @click="closeDialog" class="red">Close</SecondaryButton>
                        </div>
                    </v-card-actions>
                </v-card>
            </v-dialog>
        </v-row>
    </div>
</template>

<script>
import VueMultiselect from 'vue-multiselect'
import SecondaryButton from "@/Components/buttons/SecondaryButton.vue";
import PrimaryButton from "@/Components/buttons/PrimaryButton.vue";
import {useToast} from "vue-toastification";
import axios from "axios";
import Tab from "@/Components/tabs/Tab.vue";
import AddContactDialog from "@/Components/AddContactDialog.vue";

export default {
    name: 'OfferClientInfoDialog',
    components: {
        AddContactDialog,
        Tab,
        PrimaryButton,
        SecondaryButton,
        VueMultiselect
    },
    data() {
        return {
            dialog: false,
        };
    },
    props: {
        offer_client_id: Number
    },
    methods: {
        async openDialog() {
            this.dialog = true;
            await this.getDetails();
        },
        closeDialog() {
            this.dialog = false;
        },
        handleEscapeKey(event) {
            if (event.key === 'Escape') {
                this.closeDialog();
            }
        },
        async getDetails() {
            const response = await axios.get(`/offer-client/details/${this.$props.offer_client_id}`);
            console.log(response);
        }
    },
     async mounted() {
        document.addEventListener('keydown', this.handleEscapeKey);
        if (this.dialog) {
            await this.getDetails();
        }
    },
};
</script>
<style src="vue-multiselect/dist/vue-multiselect.css"></style>
<style scoped lang="scss">
.info{
    padding: 10px 24px 15px;
}

.bt{
    margin: 12px 24px;
}
.height {
    height: calc(100vh - 100px);
}
.background {
    background-color: $light-gray;
}
.ultra-light-gray{
    background-color: $ultra-light-gray;
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

.white {
    background-color: $white;
    color: black;
    &-hover:hover {
        background-color: darken($white, 25%);
    }
}
table{
    color: white;
}
table th, table td{
    padding: 5px;
    width: 300px;
}
table th{
    background-color: $ultra-light-gray;
}
table td, table th{
    border-right: 1px solid $ultra-light-gray;
    border-left: 1px solid $ultra-light-gray;
}
</style>

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
                <DangerButton v-bind="props" class="lock-order" @click="openDialog">Decline Offer</DangerButton>
            </template>
            <v-card class="height background">
                <v-card-title>
                    <span class="text-h5 text-white">Reason for Declining</span>
                </v-card-title>
                <v-card-text>
                    <div>
                        <div class="form-group">
                            <label for="description" class="text-white width100">Description</label>
                            <textarea id="description" class="rounded text-black" v-model="declineReason"/>
                        </div>
                    </div>
                </v-card-text>
                <v-card-actions class="flexSpace gap-4">
                    <v-spacer></v-spacer>
                    <SecondaryButton @click="closeDialog" class="red">
                        Cancel
                    </SecondaryButton>
                    <SecondaryButton @click="submitDecline" class="green">
                        Done
                    </SecondaryButton>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-row>
</template>

<script>
import SecondaryButton from "@/Components/buttons/SecondaryButton.vue";
import { useToast } from "vue-toastification";
import DangerButton from "@/Components/DangerButton.vue";

export default {
    name: 'DeclineOfferDialog',
    components: {
        DangerButton,
        SecondaryButton
    },
    data() {
        return {
            dialog: false,
            declineReason: ''
        };
    },
    props: {
        offer_client_id: Number
    },
    methods: {
        openDialog() {
            this.dialog = true;
        },
        closeDialog() {
            this.dialog = false;
            this.declineReason = '';  // Clear the input when closing
        },
        submitDecline() {
            if (!this.declineReason) {
                useToast().error('Please provide a reason for declining the offer.');
                return;
            }

            // Example of sending the reason to the backend
            axios.post('/offer-client/accept', {
                description: this.declineReason,
                offer_client: this.$props.offer_client_id,
                accept: false
            }).then((response) => {
                    this.dialog = false;
                    useToast().success('Offer declined successfully!');
                    window.location.reload();
                })
                .catch((error) => {
                    useToast().error('Failed to decline offer.');
                });
        }
    }
};
</script>

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
    height: calc(100vh - 350px);
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

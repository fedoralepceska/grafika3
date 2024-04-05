<template>
    <div class="d-flex justify-center align-center">
        <v-row>
            <v-dialog
                v-model="dialog"
                persistent
                width="700"
                class="height"
            >
                <template v-slot:activator="{ props }">
                    <div v-bind="props" class="bt">
                        <button class="btn" @click="openAddCommentForm()">Update Comment <i class="fa-regular fa-comment"></i></button>
                    </div>
                </template>
                <v-card class="height background">
                    <v-card-title>
                        <span class="text-h5 text-white">Update Invoice Comment</span>
                    </v-card-title>
                    <v-card-text>
                        <div v-if="showUpdateCommentForm">
                            <form class="flex">
                                <div class="form-group">
                                    <label for="client" class="mr-4 width100 ">Invoice Comment</label>
                                    <input type="text" class="rounded text-gray-700 " v-model="updatedComment" :placeholder="invoice[0].faktura_comment">
                                </div>
                            </form>
                        </div>
                    </v-card-text>
                    <v-card-actions class="flexSpace gap-4">
                        <v-spacer></v-spacer>
                        <SecondaryButton @click="closeDialog" class="red ">Close</SecondaryButton>
                        <SecondaryButton @click="updateComment()" class="green">Update Comment</SecondaryButton>
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

export default {
    components: {
        PrimaryButton,
        SecondaryButton,
        VueMultiselect
    },
    data() {
        return {
            dialog: false,
            showUpdateCommentForm: false,
            updatedComment: '',
        };
    },
    props: {
        invoice: Object
    },
    methods: {
        openDialog() {
            this.dialog = true;
        },
        closeDialog() {
            this.dialog = false;
        },
        openAddCommentForm() {
            this.showUpdateCommentForm = true;
        },
        async updateComment() {
            try {
                const response = await axios.put(`/invoice/${this.invoice[0].fakturaId}/update-comment`, {
                    comment: this.updatedComment
                });
                this.showUpdateCommentForm = false;
                this.dialog = false;

                this.invoice[0].faktura_comment = this.updatedComment;

                const toast = useToast();
                toast.success(response.data.message);


            } catch (error) {
                const toast = useToast();
                toast.error('Failed to update comment');
                console.error(error);
            }
        },

    },
};
</script>
<style src="vue-multiselect/dist/vue-multiselect.css"></style>
<style scoped lang="scss">
.form-group {
    display: flex;
    justify-content: left;
    align-items: center;
    width: 750px;
    color: $white;
    padding-left: 10px;
}
.width100 {
    width: 200px;
}
.btn{
    margin-right: 4px;
    padding: 12px 15px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    border-radius: 2px;
    background-color: $blue;
    color: white;
}
.bt{
    margin: 12px 24px;
}

.background {
    background-color: $light-gray;
}
.flexSpace {
    display: flex;
    justify-content: space-around;
}
.orange {
    color: $orange;
}
.flex {
    display: flex;
    flex-direction: column;
}
input {
    margin: 12px 0;
    width:400px;
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

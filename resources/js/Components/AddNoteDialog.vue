<template>
    <v-row>
        <v-dialog
            v-model="dialog"
            persistent
            max-width="500"
            class="height"
        >
            <template v-slot:activator="{ props }">
                <div v-bind="props" class="bt">
                    <i class="fa-regular fa-note-sticky"></i>
                </div>
            </template>
            <v-card class="height background">
                <v-card-title>
                    <span class="text-h5 text-white">Sticky Note</span>
                </v-card-title>
                <v-card-text>
                    <textarea v-model="noteComment" style="width: 450px"/>
                    <VueMultiselect
                        :searchable="false"
                        v-model="selectedOption"
                        :options="actionOptions"
                        :multiple="true"
                        placeholder="Select Actions">
                        <template v-slot:option="{ option }">
                            {{ option }}
                            {{ $t('actions.' + option) }}
                        </template>
                    </VueMultiselect>
                </v-card-text>
                <v-card-actions class="flexSpace">
                    <v-spacer></v-spacer>
                    <SecondaryButton @click="closeDialog">
                        Close
                    </SecondaryButton>
                    <SecondaryButton @click="saveData">
                        Save
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
            noteComment: '',
            selectedOption: null,
            actionOptions: [],
        };
    },
    props: {
        invoice: Object
    },
    async beforeMount() {
        this.noteComment = this.invoice.comment;
        await this.generateActionOptions();
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
            axios.put('/invoices/update-note-flag', {
                id: this.invoice.id,
                selectedActions: this.selectedOption,
                comment: this.noteComment,
            }).then(response => {
                toast.success('Actions successfully updated!');
            }).catch(error => {
                toast.error(error);
            });
            this.closeDialog();
        },
        async generateActionOptions() {
            const jobIds = this.invoice.jobs?.map(job => job?.id);
            const jobs = await axios.post('/get-jobs-by-ids', {
                jobs: jobIds,
            })
            console.log(jobs);
            const actions = [];
            // Iterate through each job in the invoice
            jobs.data.jobs.forEach(job => {
                // Iterate through each action in the job
                job?.actions?.forEach(action => {
                    if (!actions.includes(action.name)) {
                        actions.push(action.name);
                    }
                });
            });
            this.actionOptions = actions;
        },
    },
};
</script>
<style src="vue-multiselect/dist/vue-multiselect.css"></style>
<style scoped lang="scss">
.bt{
    font-size:45px ;
    cursor: pointer;
    padding: 12px;
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
</style>

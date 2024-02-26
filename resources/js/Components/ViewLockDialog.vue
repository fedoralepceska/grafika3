<template>
    <v-row>
        <v-dialog
            v-model="dialog"
            persistent
            min-height="200"
            max-height="250"
            max-width="500"
            class="height"
        >
            <template v-slot:activator="{ props }">
                <div v-bind="props" class="bt">
                    <span class="mdi mdi-lock" style="font-size: 32px; color: #9ca3af"></span>
                </div>
            </template>
            <v-card class="height background">
                <v-card-title>
                    <span class="text-h5 text-white">Order is Locked!</span>
                </v-card-title>
                <v-card-text>
                    <div class="text-white">
                        {{invoice.LockedNote}}
                    </div>
                </v-card-text>
                <v-card-actions class="flexSpace gap-4">
                    <v-spacer></v-spacer>
                    <SecondaryButton @click="closeDialog" class="red">
                        Close
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
            jobs: []
        };
    },
    props: {
        invoice: Object
    },
    async beforeMount() {
        this.noteComment = this.invoice.comment;
        await this.generateActionOptions();
    },
    computed: {
        areNotesAdded() {
            return this.jobs.find(j => j?.actions.some(a => a?.hasNote === 1));
        },
    },
    methods: {
        openDialog() {
            this.dialog = true;
        },
        closeDialog() {
            this.dialog = false;
        },
        async generateActionOptions() {
            const jobIds = this.invoice.jobs?.map(job => job?.id);
            const jobs = await axios.post('/get-jobs-by-ids', {
                jobs: jobIds,
            })
            const actions = [];
            this.jobs = jobs.data.jobs;
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

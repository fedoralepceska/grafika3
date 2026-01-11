<template>
    <v-row>
        <v-dialog
            v-model="dialog"
            persistent
            max-width="1000"
            scrollable
            class="height"
            @keydown.esc="closeDialog"
        >
            <template v-slot:activator="{ props }">
                <div v-bind="props" class="bt">
                    <i class="fa-regular fa-note-sticky" :class="{ orange: areNotesAdded }"></i>
                </div>
            </template>
            <v-card class="dialog-card background">
                <v-card-title>
                    <span class="text-h5 text-white">Sticky Note</span>
                </v-card-title>
                <v-card-text>
                    <div class="field">
                        <label class="label">Order comment</label>
                        <textarea 
                            v-model="noteComment" 
                            class="textarea" 
                            placeholder="Write a short note shown on selected actions"
                            maxlength="256"
                            @input="handleTextareaInput"
                        />
                        <div class="hint">
                            This note appears on the actions you select below.
                            <span class="char-count" :class="{ 'char-count--warning': (noteComment || '').length > 200 }">
                                {{ (noteComment || '').length }}/256 characters
                            </span>
                        </div>
                    </div>

                    <div class="field mt-3">
                        <label class="label">Select where this note should appear</label>
                        <input type="text" class="input search" v-model="filterText" placeholder="Filter actions by name (e.g. Print, Cut)"/>
                        <div class="jobs-grid">
                            <div v-for="(job, idx) in jobs" :key="job.id" class="job-card">
                                <div class="job-card__header">
                                    <div class="job-title">#{{ idx + 1 }} {{ job.name || ('Job ' + job.id) }}</div>
                                    <div class="job-actions">
                                        <button class="btn-mini" @click.prevent="selectAllForJob(job)">Select all</button>
                                        <button class="btn-mini" @click.prevent="clearAllForJob(job)">Clear</button>
                                    </div>
                                </div>
                                <div class="job-card__body">
                                    <button
                                        v-for="action in getFilteredActions(job)"
                                        :key="job.id + '-' + action.name"
                                        @click.prevent="togglePair(job.id, action.name)"
                                        :class="['chip', isSelected(job.id, action.name) ? 'chip--selected' : '', action.hasNote ? 'chip--preset' : '']"
                                        :title="action.hasNote ? 'Currently active on this action' : ''"
                                    >
                                        <span class="chip__label">{{ action.name }}</span>
                                        <span v-if="isSelected(job.id, action.name)" class="chip__tick">✓</span>
                                    </button>
                                    <div v-if="getFilteredActions(job).length === 0" class="no-actions">No actions match the filter</div>
                                </div>
                            </div>
                        </div>
                        <div class="summary">
                            Selected {{ selectedPairs.length }} action(s) across {{ selectedJobsCount }} job(s)
                            <button class="btn-link" @click.prevent="clearAll">Clear all</button>
                        </div>
                    </div>
                </v-card-text>
                <v-card-actions class="flexSpace gap-4">
                    <v-spacer></v-spacer>
                    <SecondaryButton @click="closeDialog" class="red">
                        Close
                    </SecondaryButton>
                    <SecondaryButton @click="saveData" class="green">
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
            selectedPairs: [],
            pairOptions: [],
            jobs: [],
            filterText: ''
        };
    },
    props: {
        invoice: Object
    },
    async beforeMount() {
        this.noteComment = this.invoice.comment || '';
        await this.generateActionOptions();
    },
    computed: {
        areNotesAdded() {
            return this.jobs?.some(j => j?.actions?.some(a => !!a?.hasNote));
        },
    },
    methods: {
        openDialog() {
            this.dialog = true;
        },
        closeDialog() {
            this.dialog = false;
        },
        isSelected(jobId, actionName) {
            return this.selectedPairs.some(p => p.jobId === jobId && p.actionName === actionName);
        },
        togglePair(jobId, actionName) {
            const idx = this.selectedPairs.findIndex(p => p.jobId === jobId && p.actionName === actionName);
            if (idx >= 0) {
                this.selectedPairs.splice(idx, 1);
            } else {
                this.selectedPairs.push({ key: `${jobId}-${actionName}`, label: `#${this.findJobIndex(jobId) + 1} ${this.findJobName(jobId)} — ${actionName}`, jobId, actionName });
            }
        },
        findJobIndex(jobId) {
            return this.jobs.findIndex(j => j.id === jobId);
        },
        findJobName(jobId) {
            const j = this.jobs.find(j => j.id === jobId);
            return j?.name || `Job ${jobId}`;
        },
        selectAllForJob(job) {
            const actions = this.getFilteredActions(job);
            actions.forEach(a => {
                if (!this.isSelected(job.id, a.name)) {
                    this.selectedPairs.push({ key: `${job.id}-${a.name}`, label: `#${this.findJobIndex(job.id) + 1} ${this.findJobName(job.id)} — ${a.name}`, jobId: job.id, actionName: a.name });
                }
            });
        },
        clearAllForJob(job) {
            this.selectedPairs = this.selectedPairs.filter(p => p.jobId !== job.id);
        },
        clearAll() {
            this.selectedPairs = [];
        },
        getFilteredActions(job) {
            const ft = this.filterText.trim().toLowerCase();
            const actions = job?.actions || [];
            if (!ft) return actions;
            return actions.filter(a => (a?.name || '').toLowerCase().includes(ft));
        },
        saveData() {
            const toast = useToast();
            axios.put('/orders/update-note-flag', {
                id: this.invoice.id,
                selectedPairs: this.selectedPairs.map(p => ({ job_id: p.jobId, action_name: p.actionName })),
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
            const jobs = await axios.post('/get-jobs-by-ids', { jobs: jobIds });
            this.jobs = jobs.data.jobs || [];

            const options = [];
            const preselected = [];

            this.jobs.forEach((job, idx) => {
                const jobLabel = `#${idx + 1} ${job.name || 'Job ' + job.id}`;
                job?.actions?.forEach(action => {
                    const option = {
                        key: `${job.id}-${action.name}`,
                        label: `${jobLabel} — ${action.name}`,
                        jobId: job.id,
                        actionName: action.name,
                    };
                    options.push(option);
                    if (action?.hasNote === 1 || action?.hasNote === true) {
                        preselected.push(option);
                    }
                });
            });

            this.pairOptions = options;
            this.selectedPairs = preselected;
        },
        handleEscapeKey(event) {
            if (event.key === 'Escape') {
                this.closeDialog();
            }
        },
        handleTextareaInput(event) {
            // Ensure we don't exceed 256 characters
            if (event.target.value.length > 256) {
                this.noteComment = event.target.value.substring(0, 256);
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
.bt{
    font-size:35px ;
    cursor: pointer;
    padding: 12px;
}
.height {
    /* v-dialog wrapper sizing */
    height: auto;
    width: 100%;
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

.dialog-card {
    max-height: calc(100vh - 120px);
    overflow: auto;
}

/* New UI styles */
.field {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.label {
    color: white;
    font-weight: bold;
}
.hint {
    font-size: 12px;
    color: #cbd5e1;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.char-count {
    font-weight: 500;
}
.char-count--warning {
    color: #f59e0b;
}
.textarea {
    width: 100%;
    min-height: 80px;
    border-radius: 6px;
    border: 1px solid #4b5563;
    background: #1f2937;
    color: white;
    padding: 8px 10px;
}
.input.search {
    width: 100%;
    border-radius: 6px;
    border: 1px solid #4b5563;
    background: #111827;
    color: white;
    padding: 6px 10px;
}
.jobs-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 12px;
    margin-top: 10px;
}
.job-card {
    border: 1px solid #4b5563;
    border-radius: 8px;
    background: #111827;
}
.job-card__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 10px;
    border-bottom: 1px solid #374151;
}
.job-title {
    color: white;
    font-weight: 600;
}
.job-actions {
    display: flex;
    gap: 6px;
}
.btn-mini {
    font-size: 11px;
    background: #374151;
    color: white;
    border: none;
    border-radius: 4px;
    padding: 3px 6px;
    cursor: pointer;
}
.job-card__body {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    padding: 10px;
}
.chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 8px;
    border-radius: 999px;
    border: 1px solid #4b5563;
    color: white;
    background: #1f2937;
    cursor: pointer;
}
.chip--selected {
    border-color: $green;
    background: rgba(16, 185, 129, 0.15);
}
.chip--preset {
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.2);
}
.chip__tick {
    color: $green;
}
.no-actions {
    color: #9ca3af;
    font-size: 12px;
}
.summary {
    margin-top: 8px;
    color: #9ca3af;
    display: flex;
    align-items: center;
    gap: 8px;
}
.btn-link {
    background: transparent;
    border: none;
    color: #60a5fa;
    cursor: pointer;
    text-decoration: underline;
}
.custom-tag {
    background: #1f2937;
    color: white;
    border: 1px solid #374151;
    border-radius: 999px;
    padding: 2px 8px;
    margin: 2px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.custom-tag .remove {
    cursor: pointer;
}
</style>

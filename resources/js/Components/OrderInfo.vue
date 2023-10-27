<template>
    <div class="light-gray p-2">
        <div>
            <label class="text-white">{{ $t('syncJobs') }}</label>
            <Multiselect
                v-model="selectedJobs"
                :options="jobs.map((job, index) => ({ valueProp: index + 1, label: index + 1, name: job }))"
                mode="multiple"
                :close-on-select="false"
                :group-select="false"
                :groups="false"
                track-by="label"
                placeholder="Select Jobs"
            />

        </div>
    </div>
    <div class="order-info-box">
        <div class="form-group mt-2 p-2 text-black sameRow">
            <label class="label-fixed-width">{{ $t('machineP') }}</label>
            <select v-model="selectedMachinePrint" class="select-fixed-width">
                <option v-for="machine in machinesPrint" :key="machine" :value="machine">
                    {{ $t(`machinePrint.${machine}`) }}
                </option>
            </select>
        </div>

        <div class="form-group mt-2 p-2 text-black sameRow">
            <label class="label-fixed-width">{{ $t('machineC') }}</label>
            <select v-model="selectedMachineCut" class="select-fixed-width">
                <option v-for="machine in machinesCut" :key="machine" :value="machine">
                    {{ $t(`machineCut.${machine}`) }}
                </option>
            </select>
        </div>

        <div class="form-group mt-2 p-2 text-black sameRow">
            <label class="label-fixed-width">{{ $t('material') }}</label>
            <select v-model="selectedMaterial" class="select-fixed-width">
                <option v-for="material in materials" :key="material" :value="material">
                    {{ $t(`materials.${material}`) }}
                </option>
            </select>
        </div>

        <div class="form-group mt-2 p-2 text-black sameRow">
            <label class="label-fixed-width">{{ $t('materialSmallFormat') }}</label>
            <select v-model="selectedMaterialSmall" class="select-fixed-width">
                <option v-for="material in materialsSmall" :key="material" :value="material">
                    {{ $t(`materialsSmall.${material}`) }}
                </option>
            </select>
        </div>

        <div v-for="(action, index) in actions" :key="index">
            <div class="form-group mt-2 p-2 text-black sameRow">
                <label class="label-fixed-width">{{ $t('action') }} {{ index + 1 }}</label>
                <select v-model="action.selectedAction" class="select-fixed-width">
                    <option v-for="actionOption in actionOptions" :key="actionOption" :value="actionOption">
                        {{ $t(`actions.${actionOption}`) }}
                    </option>
                </select>
                <button class="addBtn" @click="addAction"><span class="mdi mdi-plus-circle"></span></button>
                <button v-if="index > 0" class="removeBtn" @click="removeAction(index)"><span class="mdi mdi-minus-circle"></span></button>
            </div>
        </div>

        <div class="button-container">
            <PrimaryButton class="mt-5" @click="syncAll">
                {{ numberOfSelectedJobs ? $t('syncJobs') : $t('syncAllJobs') }}
            </PrimaryButton>
        </div>
    </div>
</template>

<script>
import { useI18n } from 'vue-i18n';
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { useToast } from "vue-toastification";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import Multiselect from '@vueform/multiselect'
import "@vueform/multiselect/themes/default.css";

export default {
    name: "OrderInfo",
    components: { Multiselect,SecondaryButton, PrimaryButton },
    props: {
        jobs: Array
    },
    data() {
        return {
            selectedMaterial: '',
            selectedMaterialSmall: '',
            selectedMachineCut: '',
            selectedMachinePrint: '',
            selectedAction: '',
            selectedJobs: [],
            idMapping: {},
            actions: [{}],
            actionOptions: this.generateActionOptions(),
            materials: this.generateMaterials(),
            materialsSmall: this.generateMaterialsSmall(),
            machinesPrint: this.generateMachinesPrint(),
            machinesCut: this.generateMachinesCut()
        }
    },
    mounted() {
        this.jobs.forEach((job, index) => {
            this.idMapping[index + 1] = job.id;
        });
        console.log(this.idMapping);
    },
    computed: {
        numberOfSelectedJobs() {
            if (!this.selectedJobs.length) {
                return false;
            }
            else if (this.selectedJobs.length === this.jobs.length) {
                return false;
            }
            else {
                return true;
            }
        }
    },
    methods: {
        generateMachinesPrint() {
            const materials = [];
            for (let i = 1; i <= 10; i++) {
                materials.push(`Machine print ${i}`);
            }
            return materials;
        },
        generateMachinesCut() {
            const materials = [];
            for (let i = 1; i <= 2; i++) {
                materials.push(`Machine cut ${i}`);
            }
            return materials;
        },
        generateMaterials() {
            const materials = [];
            for (let i = 1; i <= 28; i++) {
                materials.push(`Material ${i}`);
            }
            return materials;
        },
        generateMaterialsSmall() {
            const materials = [];
            for (let i = 1; i <= 34; i++) {
                materials.push(`Material small ${i}`);
            }
            return materials;
        },
        generateActionOptions() {
            const actions = [];
            for (let i = 1; i <= 28; i++) {
                actions.push(`Action ${i}`);
            }
            return actions;
        },
        addAction() {
            this.actions.push({});
        },
        removeAction(index) {
            this.actions.splice(index, 1);
        },
        syncAll() {
            const toast = useToast();

            // Define the array of job IDs to sync
            let jobIdsToSync;

            if (this.selectedJobs.length > 0) {
                // Sync only selected jobs if there are selected jobs
                jobIdsToSync = this.selectedJobs.map(customId => this.idMapping[customId.id]);
            } else {
                // Sync all jobs if no jobs are selected
                jobIdsToSync = this.jobs.map(job => job.id);
            }

            // Create jobsWithActions based on the selected job IDs
            const jobsWithActions = jobIdsToSync.map(job => {
                const actions = this.actions.map(action => {
                    return {
                        action_id: action.selectedAction,
                        status: 'Not started yet'
                    };
                });
                return {
                    job_id: job,
                    actions: actions,
                };
            });
            axios.post('/sync-all-jobs', {
                selectedMaterial: this.selectedMaterial,
                selectedMachinePrint: this.selectedMachinePrint,
                selectedMachineCut: this.selectedMachineCut,
                selectedMaterialsSmall: this.selectedMaterialSmall,
                jobs: jobIdsToSync,
                jobsWithActions: jobsWithActions,
            })
                .then(response => {
                    toast.success(`Successfully synced ${jobIdsToSync.length} jobs!`);
                    axios.post('/get-jobs-by-ids', {
                        jobs: jobIdsToSync,
                    })
                        .then(response => {
                            this.$emit('jobs-updated', response.data.jobs);
                        })
                        .catch(error => {
                            toast.error("Couldn't fetch updated jobs");
                        });
                })
                .catch(error => {
                    toast.error("Couldn't sync jobs");
                });
        }
    }
};
</script>

<style scoped lang="scss">
.light-gray {
    background-color: $light-gray;
}

.sameRow {
    display: flex;
    align-items: center;
    margin-bottom: 1rem;
}

.form-group label {
    margin-right: 1rem;
    color: $white;
}

.addBtn {
    color: $light-green;
    margin-left: 8px;
    cursor: pointer;
}

.removeBtn {
    color: #c95050;
    margin-left: 7px;
    cursor: pointer;
}

.button-container {
    display: flex;
    justify-content: flex-end;
    margin:5px;
    padding: 5px;
}

.label-fixed-width {
    width: 11rem;
}

.select-fixed-width {
    width: 15rem;
}

input, select, .multiselect {
    height: 36px;
    min-height: 26px;
}

</style>

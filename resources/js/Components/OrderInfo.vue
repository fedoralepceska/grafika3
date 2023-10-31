<template>
    <div class="light-gray p-2">
        <div>
            <label class="text-white">{{ $t('syncJobs') }}</label>
            <div>
                <label>{{ $t('syncJobs') }}</label><br>
                <select v-model="selectedJobs" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" multiple>
                    <option v-for="(job, index) in jobs" :key="index" :value="job">
                        #{{ index + 1 }}
                    </option>
                </select>
            </div>

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
            <select v-model="selectedMaterial" :disabled="selectedMaterialSmall !== ''" class="select-fixed-width">
                <option v-for="material in materials" :key="material" :value="material">
                    {{ $t(`materials.${material}`) }}
                </option>
            </select>
        </div>

        <div class="form-group mt-2 p-2 text-black sameRow">
            <label class="label-fixed-width">{{ $t('materialSmallFormat') }}</label>
            <select v-model="selectedMaterialSmall" :disabled="selectedMaterial !== ''" class="select-fixed-width">
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

        <div class="form-group mt-2 p-2 text-black sameRow">
            <label class="label-fixed-width">{{ $t('Quantity') }}</label>
            <input type="number" v-model="quantity">
        </div>
        <div class="form-group mt-2 p-2 text-black sameRow">
            <label class="label-fixed-width">{{ $t('Copies') }}</label>
            <input type="number" v-model="copies">
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
            quantity: 0,
            copies: 0,
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
        jobIds() {
          return this.jobs.map(j => j.id);
        },
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
            console.log(this.$props.jobs);
            // Get all job ids
            const jobIds = this.jobs.map(job => job.id);

            // Create jobsWithActions for all jobs
            const jobsWithActions = jobIds.map(jobId => {
                const actions = this.actions.map(action => ({
                    action_id: action.selectedAction,
                    status: 'Not started yet'
                }));

                return {
                    job_id: jobId,
                    actions: actions
                };
            });

            console.log(this.selectedMaterial, this.selectedMaterialSmall);

            axios.post('/sync-all-jobs', {
                selectedMaterial: this.selectedMaterial,
                selectedMachinePrint: this.selectedMachinePrint,
                selectedMachineCut: this.selectedMachineCut,
                selectedMaterialsSmall: this.selectedMaterialSmall,
                quantity: this.quantity,
                copies: this.copies,
                jobs: jobIds,
                jobsWithActions: jobsWithActions,
            })
                .then(response => {
                    toast.success(`Successfully synced ${jobIds.length} jobs!`);
                    axios.post('/get-jobs-by-ids', {
                        jobs: jobIds,
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

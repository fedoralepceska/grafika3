<template>
    <div class="light-gray p-2">
        <label class="block mb-2 mt-2 text-sm font-medium text-gray-900 dark:text-white">{{ $t('syncJobs') }}</label>
        <div class="card flex justify-content-center pt-2 w-25">
            <MultiSelect v-model="selectedJobs" display="chip" :options="jobs.map((job, index) => ({ id: index+1, name: job }))" optionLabel="id" placeholder="Select Jobs"
                         :maxSelectedLabels="3" class="dark-gray w-full md:w-20rem" />
        </div>
    </div>
    <div class="order-info-box">
        <div class="ultra-light-blue mt-3">
            <div class="sub-title blue p-1 pl-3 text-white">{{$t('PRINT')}}</div>
            <div class="form-group mt-2 p-2 text-black">
                <label>{{ $t('machineP') }}</label><br>
                <select v-model="selectedMachinePrint">
                    <option v-for="machine in machinesPrint" :key="machine" :value="machine">
                        {{ $t(`machinePrint.${machine}`) }}
                    </option>
                </select>
            </div>
        </div>

        <div class="ultra-light-red mt-3">
            <div class="sub-title red p-1 pl-3 text-white">{{$t('CUT')}}</div>
            <div class="form-group mt-2 p-2 text-black">
                <label>{{ $t('machineC') }}</label><br>
                <select v-model="selectedMachineCut">
                    <option v-for="machine in machinesCut" :key="machine" :value="machine">
                        {{ $t(`machineCut.${machine}`) }}
                    </option>
                </select>
            </div>
        </div>

        <div class="ultra-light-orange mt-3">
            <div class="sub-title orange p-1 pl-3 text-white">{{$t('MATERIALS')}}</div>
            <div class="form-group mt-2 p-2 text-black">
                <label>{{ $t('material') }}</label><br>
                <select v-model="selectedMaterial">
                    <option v-for="material in materials" :key="material" :value="material">
                        {{ $t(`materials.${material}`) }}
                    </option>
                </select>
            </div>
        </div>

        <div class="ultra-light-orange mt-3">
            <div class="sub-title orange p-1 pl-3 text-white">{{$t('MATERIALS')}}</div>
            <div class="form-group mt-2 p-2 text-black">
                <label>{{ $t('materialSmallFormat') }}</label><br>
                <select v-model="selectedMaterialSmall">
                    <option v-for="material in materialsSmall" :key="material" :value="material">
                        {{ $t(`materialsSmall.${material}`) }}
                    </option>
                </select>
            </div>
        </div>

        <div class="ultra-light-green mt-3">
            <h2 class="sub-title green p-1 pl-3 text-white" >{{$t('ACTIONS')}}</h2>
            <div v-for="(action, index) in actions" :key="index">
                <div class="form-group mt-2 p-2 text-black">
                    <label>{{ $t('action') }} {{ index + 1 }}</label><br>
                    <select v-model="action.selectedAction">
                        <option v-for="actionOption in actionOptions" :key="actionOption" :value="actionOption">
                            {{ $t(`actions.${actionOption}`) }}
                        </option>
                    </select>
                    <button class="addBtn" @click="addAction">+</button>
                    <button v-if="index > 0" class="removeBtn"
                    @click="removeAction(index)">-</button>
                </div>
            </div>
        </div>

        <div class="button-container">
        <PrimaryButton class="mt-5" @click="syncAll">{{ numberOfSelectedJobs ? $t('syncJobs') : $t('syncAllJobs') }}</PrimaryButton>
        </div>
    </div>
</template>

<script>
import { useI18n } from 'vue-i18n';
import PrimaryButton from "@/Components/PrimaryButton.vue";
import {useToast} from "vue-toastification";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import MultiSelect from 'primevue/multiselect';
import 'primevue/resources/themes/lara-dark-blue/theme.css'; // Import PrimeVue theme CSS



export default {
    name: "OrderInfo",
    components: {SecondaryButton, PrimaryButton,MultiSelect },
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
            actions: [{}], // Start with an empty action
            actionOptions: this.generateActionOptions(),
            materials: this.generateMaterials(),
            materialsSmall: this.generateMaterialsSmall(),
            machinesPrint: this.generateMachinesPrint(),
            machinesCut: this.generateMachinesCut()
        }
    },
    computed: {
        // logic which helps in displaying the right button label
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
            this.actions.push({}); // Add a new empty action
        },
        removeAction(index) {
            // Remove the action at the specified index
            this.actions.splice(index, 1);
        },
        syncAll() {
            const toast = useToast();

            // Define the array of job IDs to sync
            let jobIdsToSync;

            if (this.selectedJobs.length > 0) {
                // Sync only selected jobs if there are selected jobs
                jobIdsToSync = this.selectedJobs;
            } else {
                // Sync all jobs if no jobs are selected
                jobIdsToSync = this.jobs.map(job => job.id);
            }

            // Create jobsWithActions based on the selected job IDs
            const jobsWithActions = jobIdsToSync.map(jobId => {
                const actions = this.actions.map(action => {
                    return {
                        action_id: action.selectedAction,
                        status: 'Not started yet', // Default status
                    };
                });

                return {
                    job_id: jobId,
                    actions: actions,
                };
            });

            // Perform the synchronization
            axios.post('/sync-all-jobs', {
                selectedMaterial: this.selectedMaterial,
                selectedMachinePrint: this.selectedMachinePrint,
                selectedMachineCut: this.selectedMachineCut,
                selectedMaterialsSmall: this.selectedMaterialSmall,
                jobs: jobIdsToSync,
                jobsWithActions: jobsWithActions,
            })
                .then(response => {
                    // Handle the response
                    toast.success(`Successfully synced ${jobIdsToSync.length} jobs!`);
                    axios.post('/get-jobs-by-ids', {
                        jobs: jobIdsToSync,
                    })
                        .then(response => {
                            // Handle the response with the updated jobs
                            this.$emit('jobs-updated', response.data.jobs);

                            // You can emit these updated jobs or handle them as needed
                        })
                        .catch(error => {
                            // Handle errors
                            toast.error("Couldn't fetch updated jobs");
                        });
                })
                .catch(error => {
                    // Handle errors
                    toast.error("Couldn't sync jobs");
                });
        }
    }
}
</script>

<style scoped lang="scss">

.ultra-light-green {
    background-color: rgba(121, 173, 84, 0.2);
}
.ultra-light-orange{
    background-color: rgba(199,165,103,0.2);
}
.ultra-light-blue{
    background-color: rgba(102,171,203,0.2);
}
.ultra-light-red{
    background-color: rgba(196,128,130,0.2);
}
.green{
    background-color: $green;
}

.orange{
    background-color: $orange;
}
.blue{
    background-color: $blue;
}
.light-green{
    background-color: $light-green;
}
.dark-gray{
    background-color: $dark-gray;
}
.red{
    background-color: $red;
}
.light-gray{
    background-color: $light-gray;
}
.addBtn{
    color: $light-green;
    margin:5px;
    padding: 2px 7px;
    border: 2px $green solid;
}
.removeBtn{
    color: red;
    margin:5px;
    padding: 2px 7px;
    border: 2px $red solid;
}
.sub-title{
    width: 100%;
    margin: 0;
}
.form-group label {
    margin-bottom: 10px;
    color: $white;
}
.sameRow {
    display: flex;
    flex-direction: row;
}
.button {
    align-content: center;
    align-self: center;
    border-radius: 75%;
}
.sameColumn {
    display: flex;
    flex-direction: column;
}
.button-container{
    display: flex;
    justify-content: flex-end;
}
</style>

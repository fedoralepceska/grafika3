<template>
    <div class="order-info-box">

        <div class="ultra-light-blue mt-3">
            <div class="sub-title blue p-1 pl-3 text-white">{{$t('PRINT')}}</div>
            <div class="form-group mt-2 p-2">
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
            <div class="form-group mt-2 p-2">
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
            <div class="form-group mt-2 p-2">
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
            <div class="form-group mt-2 p-2">
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
                <div class="form-group mt-2 p-2">
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
        <PrimaryButton class="mt-5" @click="syncAll">Sync All</PrimaryButton>
        </div>
    </div>
</template>

<script>
import { useI18n } from 'vue-i18n';
import PrimaryButton from "@/Components/PrimaryButton.vue";
import {useToast} from "vue-toastification";
import SecondaryButton from "@/Components/SecondaryButton.vue";
export default {
    name: "OrderInfo",
    components: {SecondaryButton, PrimaryButton},
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
            actions: [{}], // Start with an empty action
            actionOptions: this.generateActionOptions(),
            materials: this.generateMaterials(),
            materialsSmall: this.generateMaterialsSmall(),
            machinesPrint: this.generateMachinesPrint(),
            machinesCut: this.generateMachinesCut()
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

            const jobsWithActions = this.jobs.map(job => {
                const actions = this.actions.map(action => {
                    return {
                        action_id: action.selectedAction,
                        status: 'Not started yet', // Default status
                    };
                });

                return {
                    job_id: job.id,
                    actions: actions,
                };
            });

            axios.post('/sync-all-jobs',
                {
                selectedMaterial: this.selectedMaterial,
                selectedMachinePrint: this.selectedMachinePrint,
                selectedMachineCut: this.selectedMachineCut,
                selectedMaterialsSmall: this.selectedMaterialSmall,
                jobs: this.jobs.map(job => job.id),
                jobsWithActions: jobsWithActions
                })
                .then(response => {
                    // Handle the response
                    toast.success(`Successfully synced ${this.jobs.length} jobs!`);
                    axios.post('/get-jobs-by-ids', {
                        jobs: this.jobs.map(job => job.id)
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
.red{
    background-color: $red;
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

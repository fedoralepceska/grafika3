<template>
    <div v-if="!$props.shipping">
        <div class="light-gray p-2">
            <div>
                <div class="text-white">{{ $t('syncJobs') }}</div>
                <div>
                    <VueMultiselect
                        :searchable="false"
                        v-model="selectedJobs"
                        :options="formattedJobOptions"
                        :multiple="true"
                        label="title"
                        track-by="value"
                        :close-on-select="true"
                        :show-labels="false"
                        placeholder="Select Jobs">
                    </VueMultiselect>
                </div>
            </div>
        </div>
        <div class="order-info-box">
            <div class="form-group mt-2 p-2 text-black sameRow">
                <label class="label-fixed-width">{{ $t('Name') }}</label>
                <input type="text" v-model="name" class="select-fixed-width">
            </div>
            <div class="form-group mt-2 p-2 text-black sameRow">
                <label class="label-fixed-width">{{ $t('machineP') }}</label>
                <select v-model="selectedMachinePrint" class="select-fixed-width">
                    <option v-for="machine in machinesPrint" :key="machine.id" :value="machine.name">
                        {{ machine.name }}
                    </option>
                </select>
            </div>

            <div class="form-group mt-2 p-2 text-black sameRow">
                <label class="label-fixed-width">{{ $t('machineC') }}</label>
                <select v-model="selectedMachineCut" class="select-fixed-width">
                    <option v-for="machine in machinesCut" :key="machine.id" :value="machine.name">
                        {{ machine.name }}
                    </option>
                </select>
            </div>

            <div class="form-group mt-2 p-2 text-black sameRow">
                <label class="label-fixed-width">{{ $t('material') }}</label>
                <select v-model="selectedMaterial" :disabled="selectedMaterialSmall !== ''" class="select-fixed-width">
                    <option v-for="material in largeMaterials" :key="material.id" :value="material" :disabled="material.disabled">
                        <template v-if="material.type === 'category'">
                            <img v-if="material.icon" :src="`/storage/icons/${material.icon}`" alt="icon" style="width: 18px; height: 18px; margin-right: 4px;" />
                            [{{ $t('category') }}] {{ material.name }}
                        </template>
                        <template v-else>
                            {{ material.name }}
                        </template>
                    </option>
                </select>
                <button v-if="selectedMaterial !== ''" @click="clearSelection('selectedMaterial')" class="removeBtn"><span class="mdi mdi-minus-circle"></span></button>
            </div>

            <div class="form-group mt-2 p-2 text-black sameRow">
                <label class="label-fixed-width">{{ $t('materialSmallFormat') }}</label>
                <select v-model="selectedMaterialSmall" :disabled="selectedMaterial !== ''" class="select-fixed-width">
                    <option v-for="material in materialsSmall" :key="material.id" :value="material" :disabled="material.disabled">
                        <template v-if="material.type === 'category'">
                            <img v-if="material.icon" :src="`/storage/icons/${material.icon}`" alt="icon" style="width: 18px; height: 18px; margin-right: 4px;" />
                            [{{ $t('category') }}] {{ material.name }}
                        </template>
                        <template v-else>
                            {{ material.name }}
                        </template>
                    </option>
                </select>
                <button v-if="selectedMaterialSmall !== ''" @click="clearSelection('selectedMaterialSmall')" class="removeBtn"><span class="mdi mdi-minus-circle"></span></button>

            </div>

            <div v-for="(action, index) in actions" :key="index">
                <div class="form-group mt-2 p-2 text-black sameRow">
                    <label class="label-fixed-width">{{ $t('action') }} {{ index + 1 }}</label>
                    <select v-model="action.selectedAction" class="select-fixed-width">
                        <option v-for="actionOption in refinements" :key="actionOption" :value="actionOption">
                            {{ actionOption.name }}
                        </option>
                    </select>
                    <button class="addBtn" @click="addAction"><span class="mdi mdi-plus-circle"></span></button>
                    <button v-if="index > 0" class="removeBtn" @click="removeAction(index)"><span class="mdi mdi-minus-circle"></span></button>
                </div>
                <div v-if="action.selectedAction?.isMaterialized" class="form-group mt-2 p-2 text-black sameRow">
                    <label class="label-fixed-width">{{ $t('actionQuantity') }}</label>
                    <input type="number" min="0" v-model="action.quantity">
                    <span class="text-white ml-2">{{ getUnit(action.selectedAction) }}</span>
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

            <div class="button-container rowButtons">
                <PrimaryButton class="mt-5" @click="syncAll">
                    {{ $t('syncAllJobs') }}
                </PrimaryButton>
                <PrimaryButton class="mt-5" @click="syncAll">
                    {{ $t('syncJobs') }}
                </PrimaryButton>
            </div>
        </div>
    </div>
    <div v-else>
        <div class="sameColumn">
            <span class="text-white">Add the shipping information here:</span>
            <textarea v-model="shippingDetails"></textarea>
        </div>
        <div>
            <label class="text-white">{{ $t('syncJobs') }}</label>
            <div>
                <VueMultiselect
                    :searchable="false"
                    v-model="selectedJobs"
                    :options="formattedJobOptions"
                    :multiple="true"
                    label="title"
                    track-by="value"
                    :close-on-select="true"
                    :show-labels="false"
                    placeholder="Select Jobs">
                </VueMultiselect>
            </div>
        </div>
        <div class="button-container rowButtons">
            <PrimaryButton class="mt-5" @click="syncAllWithShipping">
                {{ $t('syncAllJobs') }}
            </PrimaryButton>
            <PrimaryButton class="mt-5" @click="syncAllWithShipping">
                {{ $t('syncJobs') }}
            </PrimaryButton>
        </div>
    </div>
</template>

<script>
import { useI18n } from 'vue-i18n';
import PrimaryButton from "@/Components/buttons/PrimaryButton.vue";
import { useToast } from "vue-toastification";
import SecondaryButton from "@/Components/buttons/SecondaryButton.vue";
import store from '../orderStore.js';
import VueMultiselect from 'vue-multiselect'
import axios from "axios";
import Checkbox from "@/Components/inputs/Checkbox.vue";
import CatalogSelector from './CatalogSelector.vue';

export default {
    name: "OrderInfo",
    components: {Checkbox, VueMultiselect, SecondaryButton, PrimaryButton, CatalogSelector },
    props: {
        jobs: Array,
        shippingDetails: String,
        shipping: Boolean,
        isCatalog: {
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
            selectedMaterial: '',
            selectedMaterialSmall: '',
            selectedMachineCut: '',
            selectedMachinePrint: '',
            shippingDetails: '',
            selectedAction: '',
            quantity: 1,
            copies: 1,
            name: '',
            selectedJobs: [],
            actions: [{}],
            actionOptions: this.generateActionOptions(),
            largeMaterials: [],
            materialsSmall: [],
            machinesPrint: [],
            machinesCut: [],
            refinements: this.getRefinements()
        }
    },
    computed: {
        formattedJobOptions() {
            return this.jobs?.map((job, index) => ({ value: job.id, title: `#${index + 1}` }));
        }
    },
    async mounted() {
        // Fetch large and small materials for dropdowns
        const [largeRes, smallRes] = await Promise.all([
            axios.get('/api/materials/large-dropdown'),
            axios.get('/api/materials/small-dropdown')
        ]);
        this.largeMaterials = largeRes.data;
        this.materialsSmall = smallRes.data;
        this.fetchMachines();
    },
    methods: {
        fetchMachines() {
            axios.get('/get-machines')
                .then((response) => {
                    this.machinesCut = response.data.machinesCut;
                    this.machinesPrint = response.data.machinesPrint;
                })
                .catch((error) => {
                    console.error('Error fetching machines:', error);
                });
        },
        async getRefinements() {
            const response = await axios.get('/refinements/all');
            this.refinements = response.data;
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
        clearSelection(fieldName) {
            this[fieldName] = ''; // Reset the selected value to an empty string
        },
        syncAll() {
            const toast = useToast();
            let jobIds;
            // Get all job ids
            if (this.selectedJobs.length) {
                jobIds = this.selectedJobs.map(job => job.value)
            }
            else {
                jobIds = this.jobs?.map(job => job?.id);
            }

            // Create jobsWithActions for all jobs
            const jobsWithActions = jobIds.map(jobId => {
                const actions = this.actions.map(action => ({
                    action_id: action.selectedAction,
                    quantity: action.quantity,
                    status: 'Not started yet'
                }));

                return {
                    job_id: jobId,
                    actions: actions
                };
            });

            if (this.selectedJobs.length) {
                const filteredJobs = this.jobs.filter(job =>
                    this.selectedJobs.some(selectedJob => selectedJob.value === job.id)
                );
                jobIds = filteredJobs.filter(j => j.isPlaceholder).map(j => j.id);
            }
            else {
                jobIds = this.jobs.filter(j => j.isPlaceholder).map(j => j.id);
            }
            if (jobIds.length) {
                axios.post('/sync-jobs-with-machine', {
                    jobs: jobIds,
                    selectedMachinePrint: this.selectedMachinePrint,
                })
                    .then(response => {
                        toast.success(`Successfully synced ${jobIds.length} jobs!`);
                        jobIds = this.jobs.map(job => job.id);
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
                        if (error.response && error.response.data.message) {
                            // Specific error message from the backend
                            toast.error(error.response.data.message);
                        } else {
                            // Generic error message
                            toast.error("Couldn't sync jobs");
                        }
                    });
            } else {
                if (this.selectedJobs.length) {
                    jobIds = this.selectedJobs.filter(j => !j.isPlaceholder).map(j => j.value);
                }
                else {
                    jobIds = this.jobs.filter(j => !j.isPlaceholder).map(j => j.id);
                }
                axios.post('/sync-all-jobs', {
                    selectedMaterial: this.selectedMaterial.id,
                    selectedMachinePrint: this.selectedMachinePrint,
                    selectedMachineCut: this.selectedMachineCut,
                    selectedMaterialsSmall: this.selectedMaterialSmall.id,
                    quantity: this.quantity,
                    copies: this.copies,
                    shipping: store.state.shippingDetails,
                    name: this.name,
                    jobs: jobIds,
                    jobsWithActions: jobsWithActions,
                })
                    .then(response => {
                        toast.success(`Successfully synced ${jobIds.length} jobs!`);
                        jobIds = this.jobs.map(job => job.id);
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
                        if (error.response && error.response.data.message) {
                            // Specific error message from the backend
                            toast.error(error.response.data.message);
                        } else {
                            // Generic error message
                            toast.error("Couldn't sync jobs");
                        }
                    });
            }
        },
        syncAllWithShipping() {
            const toast = useToast();
            let jobIds;
            // Get all job ids
            if (this.selectedJobs.length) {
                jobIds = this.selectedJobs.map(job => job.value)
            }
            else {
                jobIds = this.jobs.map(job => job.id);
            }

            axios.post('/sync-jobs-shipping', {
                shipping: this.shippingDetails,
                jobs: jobIds,
            })
                .then(response => {
                    toast.success(`Successfully synced ${jobIds.length} jobs!`);
                    jobIds = this.jobs.map(job => job.id)
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
        },
        getUnit(refinement) {
            const small = refinement?.small_material;
            const large = refinement?.large_format_material;

            if (small !== null || large !== null) {
                if (small?.article?.in_meters === 1 || large?.article?.in_meters === 1) {
                    return 'meters'
                }
                else if (small?.article?.in_kilograms === 1 || large?.article?.in_kilograms === 1) {
                    return 'kilograms'
                }
                else if (small?.article?.in_pieces === 1 || large?.article?.in_pieces === 1) {
                    return 'pieces'
                }
            }
            else {
                return '';
            }
        },
        handleCatalogJobs(jobs) {
            this.$emit('catalog-jobs-created', jobs);
        }
    }
};
</script>
<style src="vue-multiselect/dist/vue-multiselect.css"></style>
<style>
.multiselect__tag {
    background-color: #408a0b;
}
.multiselect__option--highlight{
    background-color: #408a0b;
}
.multiselect__option--selected.multiselect__option--highlight{
    background-color: indianred;
}
</style>
    <style scoped lang="scss">
    .light-gray {
        background-color: $light-gray;
    }
    .sameRow {
        display: flex;
        align-items: center;
        margin-bottom: 1rem;
    }

    .sameColumn {
        display: flex;
        flex-direction: column;
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

    .rowButtons {
        display: flex;
        flex-direction: row;
        justify-content: right;
        gap: 10px;
    }
    </style>

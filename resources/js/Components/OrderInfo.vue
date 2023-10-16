<template>
    <div class="order-info-box">
        <div class="dropdowns">
            <div class="form-group">
                <label>{{ $t('material') }}</label><br>
                <select v-model="selectedMaterial">
                    <option v-for="material in materials" :key="material" :value="material">
                        {{ $t(`materials.${material}`) }}
                    </option>
                </select>
            </div>
        </div>

        <div>
            <div v-for="(action, index) in actions" :key="index">
                <div class="form-group">
                    <label>{{ $t('action') }} {{ index + 1 }}</label><br>
                    <select v-model="action.selectedAction">
                        <option v-for="actionOption in actionOptions" :key="actionOption" :value="actionOption">
                            {{ $t(`actions.${actionOption}`) }}
                        </option>
                    </select>
                </div>
            </div>
        </div>
        <SecondaryButton class="btn" @click="addAction">+</SecondaryButton>
        <PrimaryButton class="mt-5" @click="syncAll">Sync All</PrimaryButton>
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
            selectedAction: '',
            actions: [{}], // Start with an empty action
            actionOptions: this.generateActionOptions(),
            materials: this.generateMaterials()
        }
    },
    methods: {
        generateMaterials() {
            const materials = [];
            for (let i = 1; i <= 28; i++) {
                materials.push(`Material ${i}`);
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
        syncAll() {
            const toast = useToast();
            axios.post('/sync-all-jobs',
                {
                selectedMaterial: this.selectedMaterial,
                jobs: this.jobs.map(job => job.id)
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
.form-group label {
    margin-bottom: 10px;
    color: $white;
}
.sameRow {
    display: flex;
    flex-direction: row;
}
.btn {
    margin-top: 20px;
    align-content: center;
    align-self: center;
}
.sameColumn {
    display: flex;
    flex-direction: column;
}
</style>

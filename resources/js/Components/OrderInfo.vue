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

        <PrimaryButton class="mt-5" @click="syncAll">Sync All</PrimaryButton>
    </div>
</template>

<script>
import { useI18n } from 'vue-i18n';
import PrimaryButton from "@/Components/PrimaryButton.vue";
import {useToast} from "vue-toastification";
export default {
    name: "OrderInfo",
    components: {PrimaryButton},
    props: {
        jobs: Array
    },
    data() {
        return {
            selectedMaterial: '',
            selectedAction: '',
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
</style>

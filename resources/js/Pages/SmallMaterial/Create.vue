<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="material" subtitle="addNewMaterialSmall" icon="Materials.png" link="materials-small-format"/>

            <div class="dark-gray p-5">
                <div class="form-container p-2 light-gray">
                    <h2 class="sub-title">
                        {{ $t('materialDetails') }}
                    </h2>
                    <form @submit.prevent="addMaterial">
                        <!-- Form fields for Name and Quantity -->
                        <div class="form-group gap-4">
                            <label for="name">{{ $t('name') }}:</label>
                            <input type="text" id="name" class="text-gray-700" v-model="material.name" required>
                        </div>
                        <div class="form-group gap-4">
                            <label for="width">{{ $t('width') }}:</label>
                            <input type="number" id="width" class="text-gray-700" v-model="material.width" required>
                        </div>
                        <div class="form-group gap-4">
                            <label for="height">{{ $t('height') }}:</label>
                            <input type="number" id="height" class="text-gray-700" v-model="material.height" required>
                        </div>
                        <div class="form-group gap-4">
                            <label for="quantity">{{ $t('quantity') }}:</label>
                            <input type="number" id="quantity" class="text-gray-700" v-model="material.quantity" required>
                        </div>
                        <div class="form-group gap-4">
                            <label for="format">{{ $t('format') }}:</label>
                            <select v-model="material.small_format_material_id" class="select-fixed-width text-black">
                                <option v-for="format in formatsSF" class="text-black" :key="format" :value="format.id">
                                    {{ format.name }}
                                </option>
                            </select>
                        </div>
                        <!-- Other form fields... -->
                        <div class="button-container mt-10">
                            <PrimaryButton type="submit">{{ $t('addMaterial') }}</PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script>
import axios from 'axios';
import PrimaryButton from "@/Components/buttons/PrimaryButton.vue";
import MainLayout from "@/Layouts/MainLayout.vue";
import { useToast } from "vue-toastification";
import Header from "@/Components/Header.vue";

export default {
    name: 'Create',
    components: { MainLayout, PrimaryButton, Header },
    data() {
        return {
            material: {
                name: '',
                quantity: 0,
                width: 0.0,
                height: 0.0,
                small_format_material_id: ''
            },
            formatsSF: []
        };
    },
    async beforeMount() {
        await this.getSFMaterials();
    },
    methods: {
        addMaterial() {
            const toast = useToast();
            axios.defaults.baseURL = "http://127.0.0.1:8000";
            axios
                .post('/materials-small', this.material)
                .then((response) => {
                    toast.success("Material added successfully.");
                    this.$inertia.visit('/materials-small-format');
                })
                .catch((error) => {
                    toast.error("Error adding material.");
                });
        },
        async getSFMaterials() {
            axios.defaults.baseURL = "http://127.0.0.1:8000";
            const response = await axios.get('/get-sf-materials');
            this.formatsSF = response.data;
            return response.data;
        }
    },
};
</script>

<style scoped lang="scss">
.green-text{
    color: $green;
}
.header{
    display: flex;
    align-items: center;
}
.dark-gray {
    background-color: $dark-gray;
    justify-content: left;
    align-items: center;
    min-height: 20vh;

}
.light-gray{
    background-color: $light-gray;
}

.client-form {
    width: 100%;
    max-width: 1000px;
    justify-content: left;
    align-items: center;
    min-height: 20vh;
    padding: 5px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

.sub-title{
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    color: $white;
}

.form-group {
    display: flex;
    justify-content: right;
    align-items: center;
    width: 350px;
    margin-bottom: 10px;
    color: $white;
}

.label {
    flex: 1;
    text-align: left;
    margin-right: 20px;
}
.button-container{
    display: flex;
    justify-content: end;
}
.dimension {
    margin-bottom: 10px;
}
.select-fixed-width {
    width: 12.5rem;
}
</style>

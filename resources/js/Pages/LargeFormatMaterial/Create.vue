<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="material" subtitle="addNewMaterialLarge" icon="Materials.png"/>
            <div class="dark-gray p-5">
                <div class="form-container p-2 light-gray">
                    <h2 class="sub-title">
                        {{ $t('materialDetails') }}
                    </h2>
                    <form @submit.prevent="addMaterial">
                        <!-- Form fields for Name and Quantity -->
                        <div class="form-group gap-4">
                            <label for="name">{{ $t('name') }}:</label>
                            <input type="text" id="name" class="text-gray-700 rounded" v-model="material.name" required>
                        </div>
                        <div class="form-group gap-4">
                            <label for="width">{{ $t('width') }}:</label>
                            <input type="text" id="width" class="text-gray-700 rounded" v-model="material.width" required>
                        </div>
                        <div class="form-group gap-4">
                            <label for="height">{{ $t('height') }}:</label>
                            <input type="text" id="height" class="text-gray-700 rounded" v-model="material.height" required>
                        </div>
                        <div class="form-group gap-4">
                            <label for="quantity">{{ $t('quantity') }}:</label>
                            <input type="number" id="quantity" class="text-gray-700 rounded" v-model="material.quantity" required>
                        </div>
                        <div class="form-group gap-4">
                            <label for="price_per_unit">{{ $t('pricePerUnit') }}:</label>
                            <input type="number" id="price_per_unit" class="text-gray-700 rounded" v-model="material.price_per_unit" required>
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
    components: {Header, MainLayout, PrimaryButton },
    data() {
        return {
            material: {
                name: '',
                width: 0.0,
                height: 0.0,
                quantity: 0,
                price_per_unit: 0
            },
        };
    },
    methods: {
        addMaterial() {
            const toast = useToast();
            axios
                .post('/materials-large', this.material)
                .then((response) => {
                    // Handle successful response
                    toast.success("Material added successfully.");
                    this.$inertia.visit('/materials-large');
                })
                .catch((error) => {
                    // Handle errors, including validation errors
                    console.error('Error adding material:', error);
                    toast.error("Error adding material.");
                });
        },
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
    display: flex-end;
}


</style>

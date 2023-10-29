<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <div class="header pt-10 pb-4">
                <div class="left mr-3">
                    <img src="/images/Materials.png" alt="MaterialLogo" class="image-icon" />
                </div>
                <div class="right">
                    <h1 class="page-title">{{ $t('material') }}</h1>
                    <h3 class="text-white"> <span class="green-text">{{ $t('material') }}</span> / {{ $t('addNewMaterialSmall') }}</h3>
                </div>
            </div>
            <div class="dark-gray p-5">
                <div class="form-container p-2 light-gray">
                    <h2 class="sub-title">
                        {{ $t('materialDetails') }}
                    </h2>
                    <form @submit.prevent="addMaterial">
                        <!-- Form fields for Name and Quantity -->
                        <div class="form-group">
                            <label for="name">{{ $t('name') }}:</label>
                            <input type="text" id="name" class="text-gray-700" v-model="material.name" required>
                        </div>
                        <div class="form-group">
                            <label for="quantity">{{ $t('quantity') }}:</label>
                            <input type="number" id="quantity" class="text-gray-700" v-model="material.quantity" required>
                        </div>
                        <div class="form-group">
                            <label for="price_per_unit">{{ $t('pricePerUnit') }}:</label>
                            <input type="number" id="price_per_unit" class="text-gray-700" v-model="material.price_per_unit" required>
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
import PrimaryButton from "@/Components/PrimaryButton.vue";
import MainLayout from "@/Layouts/MainLayout.vue";
import { useToast } from "vue-toastification";

export default {
    name: 'Create',
    components: { MainLayout, PrimaryButton },
    data() {
        return {
            material: {
                name: '',
                quantity: 0,
                price_per_unit: 0
            },
        };
    },
    methods: {
        addMaterial() {
            const toast = useToast();
            axios.defaults.baseURL = "http://127.0.0.1:8000";
            axios
                .post('/materials', this.material)
                .then((response) => {
                    // Handle successful response
                    console.log('Material added successfully:', response.data);
                    toast.success("Material added successfully.");
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
    width: 80vh;
}
.light-gray{
    background-color: $light-gray;
}

.client-form {
    width: 100%;
    max-width: 100px;
    justify-content: left;
    align-items: center;
    min-height: 20vh;
    padding: 5px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}
.page-title {
    font-size: 24px;
    display: flex;
    align-items: center;
    color: $white;
}
.sub-title{
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    color: $white;
}

.image-icon {
    margin-left: 2px;
    max-width: 40px;
}
.form-group {
    display: flex;
    justify-content: right;
    align-items: center;
    width: 300px;
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


</style>
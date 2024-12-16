<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="Offer" subtitle="Create New Offer" icon="List.png" link="offers" />

            <div class="dark-gray p-2 text-white">
                <div class="form-container p-2">
                    <h2 class="sub-title">Offer Creation</h2>

                    <form @submit.prevent="submit" class="space-y-6 w-full rounded-lg">
                        <!-- Basic Information -->
                        <div class="grid grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <div>
                                    <label class="text-white">Name</label>
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        class="w-full mt-1 rounded"
                                        required
                                    />
                                </div>

                                <div>
                                    <label class="text-white">Description</label>
                                    <textarea
                                        v-model="form.description"
                                        class="w-full mt-1 rounded"
                                        rows="4"
                                        required
                                    ></textarea>
                                </div>
                                <div>
                                    <label class="text-white">Catalog Items</label>
                                    <VueMultiselect
                                        :searchable="true"
                                        v-model="form.selectedCatalogItems"
                                        :options="catalogItemsForOffer"
                                        :multiple="true"
                                        label="name"
                                        placeholder="Select Catalog Items"
                                    >
                                        <template v-slot:option="{ option }">
                                            {{option.name}}
                                        </template>
                                    </VueMultiselect>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label class="text-white">Price 1</label>
                                    <input
                                        v-model.number="form.price1"
                                        type="number"
                                        step="0.01"
                                        class="w-full mt-1 rounded"
                                        required
                                    />
                                </div>

                                <div>
                                    <label class="text-white">Price 2</label>
                                    <input
                                        v-model.number="form.price2"
                                        type="number"
                                        step="0.01"
                                        class="w-full mt-1 rounded"
                                        required
                                    />
                                </div>

                                <div>
                                    <label class="text-white">Price 3</label>
                                    <input
                                        v-model.number="form.price3"
                                        type="number"
                                        step="0.01"
                                        class="w-full mt-1 rounded"
                                        required
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-4">
                            <button type="submit" class="btn btn-primary">
                                Create Offer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script>
import MainLayout from '@/Layouts/MainLayout.vue';
import Header from '@/Components/Header.vue';
import { useToast } from 'vue-toastification';
import VueMultiselect from 'vue-multiselect'
import axios from 'axios';

export default {
    name: 'Create',
    components: {
        MainLayout,
        Header,
        VueMultiselect
    },
    data() {
        return {
            form: {
                name: '',
                description: '',
                price1: 0,
                price2: 0,
                price3: 0,
                selectedCatalogItems: []
            },
            catalogItemsForOffer: []
        };
    },
    async mounted() {
        await this.fetchCatalogItemsForOffer();
    },
    methods: {
        async fetchCatalogItemsForOffer() {
            try {
                const response = await axios.get('/catalog_items/offer');
                this.catalogItemsForOffer = Object.values(response.data).map(item => ({
                    id: item.id,
                    name: item.name
                }));
            } catch (error) {
                console.error('Error fetching catalog items:', error);
            }
        },
        async submit() {
            const toast = useToast();

            try {
                const formData = {
                    ...this.form,
                    selectedCatalogItems: this.form.selectedCatalogItems.map(item => item.id)
                };

                const response = await axios.post('/offer', formData);
                toast.success('Offer created successfully');
                this.$inertia.visit(route('offer.index'));
            } catch (error) {
                if (error.response?.data?.errors) {
                    Object.values(error.response.data.errors).forEach(errors => {
                        errors.forEach(error => toast.error(error));
                    });
                } else {
                    toast.error('An error occurred while creating the offer');
                }
            }
        }
    }
};
</script>
<style src="vue-multiselect/dist/vue-multiselect.css"></style>
<style>
    .multiselect__tag {
        background-color: #81c950;
    }
    .multiselect__option--highlight{
        background-color: #81c950;
    }
    .multiselect__option--selected.multiselect__option--highlight{
        background-color: indianred;
    }
</style>
<style scoped lang="scss">
.form-container {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    justify-content: space-between;
}
.dark-gray {
    background-color: $dark-gray;
    justify-content: left;
    align-items: center;
    min-height: 20vh;
    min-width: 80vh;
}
.sub-title {
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    color: $white;
}
.btn {
    padding: 9px 12px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    border-radius: 2px;
    background-color: $green;
}
.option, input, option, select, textarea {
    color: black;
}
</style>

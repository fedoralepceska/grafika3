<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="Catalog" subtitle="createNewCatalogItem" icon="List.png" link="catalog"/>
        </div>

        <div class="p-4">
            <form @submit.prevent="submit" class="space-y-6 bg-gray-800 p-6 rounded-lg">
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
                            <label class="text-white">Machine Print</label>
                            <select
                                v-model="form.machinePrint"
                                class="w-full mt-1 rounded"
                            >
                                <option value="">Select Machine</option>
                                <option v-for="machine in machinesPrint"
                                        :key="machine.id"
                                        :value="machine.name">
                                    {{ machine.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="text-white">Machine Cut</label>
                            <select
                                v-model="form.machineCut"
                                class="w-full mt-1 rounded"
                            >
                                <option value="">Select Machine</option>
                                <option v-for="machine in machinesCut"
                                        :key="machine.id"
                                        :value="machine.name">
                                    {{ machine.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="text-white">Large Format Material</label>
                            <select
                                v-model="form.large_material_id"
                                class="w-full mt-1 rounded"
                                :disabled="form.small_material_id !== null"
                            >
                                <option value="">Select Material</option>
                                <option v-for="material in largeMaterials"
                                        :key="material.id"
                                        :value="material.id">
                                    {{ material.article.name }} ({{ material.article.code }})
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="text-white">Small Format Material</label>
                            <select
                                v-model="form.small_material_id"
                                class="w-full mt-1 rounded"
                                :disabled="form.large_material_id !== null"
                            >
                                <option value="">Select Material</option>
                                <option v-for="material in smallMaterials"
                                        :key="material.id"
                                        :value="material.id">
                                    {{ material.article?.name }} ({{ material.article?.code }})
                                </option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-white">Quantity</label>
                                <input
                                    v-model="form.quantity"
                                    type="number"
                                    min="1"
                                    class="w-full mt-1 rounded"
                                    required
                                />
                            </div>
                            <div>
                                <label class="text-white">Copies</label>
                                <input
                                    v-model="form.copies"
                                    type="number"
                                    min="1"
                                    class="w-full mt-1 rounded"
                                    required
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions Section -->
                <div class="mt-6">
                    <h3 class="text-white text-lg font-semibold mb-4">Actions</h3>
                    <div class="space-y-4">
                        <div v-for="(action, index) in form.actions" :key="index"
                             class="flex items-center space-x-4 bg-gray-700 p-4 rounded">
                            <div class="flex-1">
                                <select
                                    :value="action.selectedAction"
                                    @input="(e) => handleActionSelect(e, action)"
                                    class="w-full rounded"
                                    required
                                >
                                    <option value="">Select Action</option>
                                    <option v-for="availableAction in availableActions"
                                            :key="availableAction.id"
                                            :value="availableAction.id"
                                            :selected="action.selectedAction === availableAction.id"
                                    >
                                        {{ availableAction.name }}
                                    </option>
                                </select>
                            </div>
                            <div v-if="action.showQuantity" class="w-32">
                                <input
                                    v-model="action.quantity"
                                    type="number"
                                    min="0"
                                    class="w-full rounded"
                                    placeholder="Quantity"
                                    required
                                />
                            </div>
                            <button
                                type="button"
                                @click="removeAction(index)"
                                class="text-red-500 hover:text-red-700"
                            >
                                <span class="mdi mdi-delete"></span>
                            </button>
                        </div>

                        <button
                            type="button"
                            @click="addAction"
                            class="text-green-500 hover:text-green-700"
                        >
                            <span class="mdi mdi-plus-circle"></span> Add Action
                        </button>
                    </div>
                </div>

                <div class="flex justify-end space-x-4">
                    <Link :href="route('catalog.index')" class="btn btn-secondary">
                        Cancel
                    </Link>
                    <button type="submit" class="btn btn-primary">
                        Create Catalog Item
                    </button>
                </div>
            </form>
        </div>
    </MainLayout>
</template>

<script>
import MainLayout from '@/Layouts/MainLayout.vue';
import Header from '@/Components/Header.vue';
import { Link } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';

export default {
    components: {
        MainLayout,
        Header,
        Link
    },

    props: {
        actions: Array,
        largeMaterials: Array,
        smallMaterials: Array,
        machinesPrint: Array,
        machinesCut: Array,
    },

    data() {
        return {
            form: {
                name: '',
                machinePrint: '',
                machineCut: '',
                large_material_id: null,
                small_material_id: null,
                quantity: 1,
                copies: 1,
                actions: []
            }
        }
    },

    computed: {
        availableActions() {
            return this.actions.filter(action => {
                return !this.form.actions.some(selectedAction =>
                    selectedAction.selectedAction === action.id
                )
            })
        }
    },

    methods: {
        addAction() {
            this.form.actions.push({
                selectedAction: '',
                quantity: 0,
                showQuantity: false
            })
        },

        handleActionSelect(event, action) {
            action.selectedAction = event.target.value;
            this.handleActionChange(action);
        },

        handleActionChange(action) {
            console.log('Action changed:', action);
            if (!action.selectedAction) {
                action.showQuantity = false;
                return;
            }

            console.log('Selected action:', action.selectedAction);
            const selectedAction = this.actions.find(a => a.id === parseInt(action.selectedAction));
            console.log('Found action:', selectedAction);

            if (!selectedAction?.name) {
                console.error('Selected action has no name:', selectedAction);
                return;
            }

            action.action_id = {
                id: selectedAction.id,
                name: selectedAction.name
            };
            action.status = 'Not started yet';
            action.showQuantity = selectedAction?.isMaterialized ?? false;

            if (!action.showQuantity) {
                action.quantity = 0;
            }
            console.log('Final action state:', action);
        },

        removeAction(index) {
            this.form.actions.splice(index, 1)
        },

        async submit() {
            const toast = useToast();

            try {
                console.log('form', this.form);
                const formData = {
                    ...this.form,
                    actions: this.form.actions.map(action => ({
                        id: action.action_id.id,
                        quantity: action.quantity
                    }))
                };

                const response = await axios.post(route('catalog.store'), formData);
                toast.success('Catalog item created successfully');
                this.$inertia.visit(route('catalog.index'));
            } catch (error) {
                if (error.response?.data?.errors) {
                    Object.values(error.response.data.errors).forEach(errors => {
                        errors.forEach(error => toast.error(error));
                    });
                } else {
                    toast.error('An error occurred while creating the catalog item');
                }
            }
        }
    },

    mounted() {
        this.addAction(); // Add first action row by default
    }
}
</script>

<style scoped>
.btn {
    @apply px-4 py-2 rounded-lg font-semibold;
}

.btn-primary {
    @apply bg-green-600 text-white hover:bg-green-700;
}

.btn-secondary {
    @apply bg-gray-600 text-white hover:bg-gray-700;
}
</style>

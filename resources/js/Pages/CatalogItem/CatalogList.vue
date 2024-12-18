<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header
                title="Catalog"
                subtitle="All Catalog Items"
                icon="List.png"
                link="catalog"
                buttonText="Create New Item"
            />


        <div class="dark-gray p-2 text-white">
            <div class="form-container p-2 ">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="sub-title">Catalog Items</h2>
                </div>
                <div>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search by name..."
                        class="rounded p-2 bg-gray-700 text-white"
                        @input="fetchCatalogItems"
                    />
                </div>

                <table class="w-full text-left border-collapse">
                    <thead>
                    <tr class="bg-gray-700 text-white">
                        <th class="p-4">Name</th>
                        <th class="p-4">Machine Print</th>
                        <th class="p-4">Machine Cut</th>
                        <th class="p-4">Material</th>
                        <th class="p-4 text-center">Actions</th>
                        <th class="p-4">Options</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr
                        v-for="item in catalogItems"
                        :key="item.id"
                        class="bg-gray-800 text-white border-t border-gray-700"
                    >
                        <td class="p-4">{{ item.name }}</td>
                        <td class="p-4">{{ item.machinePrint }}</td>
                        <td class="p-4">{{ item.machineCut }}</td>
                        <td class="p-4">
                            {{ item.material || 'N/A' }}
                        </td>
                        <td class="p-4 text-center">
                            <button
                                @click="openActionsDialog(item)"
                                class="btn-info"
                            >
                                <i class="fas fa-info-circle"></i>
                            </button>
                        </td>
                        <td class="p-4 text-center">
                            <div class="flex space-x-2">
                                <button @click="openEditDialog(item)" class="btn btn-secondary">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
<!--                                <button @click="deleteCatalogItem(item.id)" class="btn btn-danger">-->
<!--                                    <i class="fas fa-trash"></i>-->
<!--                                </button>-->
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <div class="flex w-full  justify-between items-center mt-4">
                    <button
                        :disabled="!pagination.links?.prev"
                        @click="fetchCatalogItems(pagination.links?.prev)"
                        class="btn btn-secondary"
                    >
                        Previous
                    </button>
                    <span class="text-white">
                        Page {{ pagination.current_page }} of {{ pagination.total_pages }}
                    </span>
                    <button
                        :disabled="!pagination.links?.next"
                        @click="fetchCatalogItems(pagination.links?.next)"
                        class="btn btn-secondary"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>
        </div>
        <!-- Actions Dialog -->
        <div v-if="selectedItem" class="modal-backdrop">
            <div class="modal">
                <div class="modal-header">
                    <h2>{{ selectedItem.name }} Actions</h2>
                    <button @click="closeActionsDialog" class="close-button">&times;</button>
                </div>
                <div class="modal-body">
                    <div v-if="selectedItem.actions && selectedItem.actions.length">
                        <ul>
                            <li v-for="(action, index) in selectedItem.actions" :key="index" class="option">
                                {{ action.action_id.name }}
                            </li>
                        </ul>
                    </div>
                    <div v-else>
                        <p>No actions available for this item.</p>
                    </div>
                </div>
            </div>
        </div>
        <!-- Edit Dialog -->
        <div v-if="showEditDialog" class="modal-backdrop">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Edit Catalog Item</h2>
                    <button @click="closeEditDialog" class="close-button">&times;</button>
                </div>
                <div class="modal-body">
                    <form @submit.prevent="updateCatalogItem" class="space-y-6">
                        <!-- Basic Information -->
                        <div class="grid grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <div>
                                    <label class="text-white">Name</label>
                                    <input v-model="editForm.name" type="text" class="w-full mt-1 rounded" required />
                                </div>

                                <div>
                                    <label class="text-white">Machine Print</label>
                                    <select v-model="editForm.machinePrint" class="w-full mt-1 rounded">
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
                                    <select v-model="editForm.machineCut" class="w-full mt-1 rounded">
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
                                    <select v-model="editForm.large_material_id"
                                            class="w-full mt-1 rounded"
                                            :disabled="editForm.small_material_id !== null">

                                        <option v-for="material in largeMaterials"
                                                :key="material.id"
                                                :value="material.id">
                                            {{ material.article?.name }} ({{ material.article?.code }})
                                        </option>
                                    </select>
                                    <button v-if="editForm.large_material_id"
                                            @click.prevent="clearLargeMaterial"
                                            class="text-sm text-red-500">
                                        Clear Selection
                                    </button>
                                </div>

                                <div>
                                    <label class="text-white">Small Format Material</label>
                                    <select v-model="editForm.small_material_id"
                                            class="w-full mt-1 rounded"
                                            :disabled="editForm.large_material_id !== null">
                                        <option v-for="material in smallMaterials"
                                                :key="material.id"
                                                :value="material.id">
                                            {{ material.article?.name }} ({{ material.article?.code }})
                                        </option>
                                    </select>
                                    <button v-if="editForm.small_material_id"
                                            @click.prevent="clearSmallMaterial"
                                            class="text-sm text-red-500">
                                        Clear Selection
                                    </button>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-white">Quantity</label>
                                        <input v-model="editForm.quantity" type="number" min="1"
                                               class="w-full mt-1 rounded" required />
                                    </div>
                                    <div>
                                        <label class="text-white">Copies</label>
                                        <input v-model="editForm.copies" type="number" min="1"
                                               class="w-full mt-1 rounded" required />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Actions Section -->
                        <div class="mt-6">
                            <h3 class="text-white text-lg font-semibold mb-4">Actions</h3>
                            <div class="space-y-4">
                                <div v-for="(action, index) in editForm.actions" :key="index"
                                     class="flex items-center space-x-4 bg-gray-700 p-4 rounded">
                                    <div class="flex-1">
                                        <select v-model="action.selectedAction" class="w-full rounded"
                                                @change="handleActionChange(action)" required>
                                            <option value="">Select Action</option>
                                            <option v-for="availableAction in availableActionsForEdit(action)"
                                                    :key="availableAction.id"
                                                    :value="availableAction.id">
                                                {{ availableAction.name }}
                                            </option>
                                        </select>
                                    </div>
                                    <div v-if="action.showQuantity" class="w-32">
                                        <input v-model="action.quantity" type="number" min="0"
                                               class="w-full rounded" placeholder="Quantity" required />
                                    </div>
                                    <button type="button" @click="removeAction(index)"
                                            class="text-red-500 hover:text-red-700">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>

                                <button type="button" @click="addAction"
                                        class="text-green-500 hover:text-green-700">
                                    <i class="fas fa-plus-circle"></i> Add Action
                                </button>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-4">
                            <button type="submit" class="btn btn-primary">
                                Update Catalog Item
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script>
import MainLayout from "@/Layouts/MainLayout.vue";
import Header from "@/Components/Header.vue";
import { Link } from "@inertiajs/vue3";
import { useToast } from "vue-toastification";

export default {
    components: {
        MainLayout,
        Header,
        Link,
    },
    props: {
        catalogItems: Array,
        pagination: Object,
    },
    data() {
        return {
            searchQuery: "",
            selectedItem: null,
            showEditDialog: false,
            editForm: {
                id: null,
                name: '',
                machinePrint: '',
                machineCut: '',
                large_material_id: null,
                small_material_id: null,
                quantity: 1,
                copies: 1,
                actions: [],
                is_for_offer: false,
                is_for_sales: true,
                category: ''
            },
            // New data properties for dropdowns
            machinesPrint: [],
            machinesCut: [],
            largeMaterials: [],
            smallMaterials: [],
            actions: [], // Available actions from dorabotka table
        };
    },
    methods: {
        // Fetch catalog items when search term changes or pagination is triggered
        fetchCatalogItems() {
            this.$inertia.get(route('catalog.index'), {
                search: this.searchQuery,
                page: this.pagination.current_page,
                per_page: this.pagination.per_page,
            });
        },

        // Open the actions dialog for a selected item
        openActionsDialog(item) {
            this.selectedItem = item;
        },

        // Close the actions dialog
        closeActionsDialog() {
            this.selectedItem = null;
        },

        // Delete catalog item
        async deleteCatalogItem(id) {
            if (!confirm("Are you sure you want to delete this catalog item?")) {
                return;
            }

            try {
                await axios.delete(route("catalog.destroy", id));
                this.$inertia.reload();
            } catch (error) {
                console.error("Failed to delete catalog item.", error);
            }
        },

        openEditDialog(item) {
            this.editForm = {
                id: item.id,
                name: item.name,
                machinePrint: item.machinePrint,
                machineCut: item.machineCut,
                large_material_id: null,
                small_material_id: null,
                quantity: item.quantity,
                copies: item.copies,
                actions: item.actions.map(action => ({
                    selectedAction: action.action_id.id,
                    quantity: action.quantity,
                    showQuantity: action.quantity !== null,
                    action_id: action.action_id
                })),
                is_for_offer: item.is_for_offer,
                is_for_sales: item.is_for_sales,
                category: item.category
            };

            if (item.largeMaterial) {
                this.editForm.large_material_id = parseInt(item.largeMaterial);
            }
            if (item.smallMaterial) {
                this.editForm.small_material_id = parseInt(item.smallMaterial);
            }

            this.showEditDialog = true;
        },

        closeEditDialog() {
            this.showEditDialog = false;
            this.editForm = {
                id: null,
                name: '',
                machinePrint: '',
                machineCut: '',
                large_material_id: null,
                small_material_id: null,
                quantity: 1,
                copies: 1,
                actions: [],
                is_for_offer: false,
                is_for_sales: true,
                category: ''
            };
        },

        clearLargeMaterial() {
            this.editForm.large_material_id = null;
        },

        clearSmallMaterial() {
            this.editForm.small_material_id = null;
        },

        availableActionsForEdit(currentAction) {
            return this.actions.filter(action => {
                return !this.editForm.actions.some(selectedAction =>
                    selectedAction.selectedAction === action.id &&
                    selectedAction !== currentAction
                );
            });
        },

        async updateCatalogItem() {
            const toast = useToast();

            try {
                const formData = {
                    ...this.editForm,
                    actions: this.editForm.actions.map(action => ({
                        id: action.selectedAction,
                        quantity: action.quantity,
                        isMaterialized: this.actions.find(a => a.id === action.selectedAction).isMaterialized,
                    }))
                };

                await axios.put(`/catalog/${this.editForm.id}`, formData);
                toast.success('Catalog item updated successfully');
                this.closeEditDialog();
                this.fetchCatalogItems(); // Refresh the list
            } catch (error) {
                if (error.response?.data?.errors) {
                    Object.values(error.response.data.errors).forEach(errors => {
                        errors.forEach(error => toast.error(error));
                    });
                } else {
                    toast.error('An error occurred while updating the catalog item');
                }
            }
        },

        async loadFormData() {
            try {
                // Fetch machines, materials, and actions data
                const [machinesPrintRes, machinesCutRes, materialsRes, actionsRes] = await Promise.all([
                    axios.get('/get-machines-print'),
                    axios.get('/get-machines-cut'),
                    axios.get('/get-materials'),
                    axios.get('/get-actions')
                ]);

                this.machinesPrint = machinesPrintRes.data;
                this.machinesCut = machinesCutRes.data;
                this.largeMaterials = materialsRes.data.largeMaterials;
                this.smallMaterials = materialsRes.data.smallMaterials;
                this.actions = actionsRes.data;
            } catch (error) {
                console.error('Error loading form data:', error);
                const toast = useToast();
                toast.error('Failed to load form data');
            }
        },

        addAction() {
            this.editForm.actions.push({
                selectedAction: '',
                quantity: 0,
                showQuantity: false
            });
        },

        removeAction(index) {
            this.editForm.actions.splice(index, 1);
        },

        handleActionChange(action) {
            if (!action.selectedAction) {
                action.showQuantity = false;
                return;
            }
            const selectedAction = this.actions.find(a => a.id === parseInt(action.selectedAction));
            action.showQuantity = selectedAction?.isMaterialized ?? false;
            if (!action.showQuantity) {
                action.quantity = 0;
            }
        },
    },
    mounted() {
        this.loadFormData();
    },
};
</script>

<style scoped lang="scss">
.btn {
    padding: 9px 12px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    border-radius: 2px;
    background-color: $green;
    color: white;
}
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
.sub-title{
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    color: $white;
}
table {
    width: 100%;
    border-collapse: collapse;
}
thead th {
    padding: 10px;

    color: white;
    background-color: $gray;
}
tbody td {
    padding: 10px;
    border-top: 1px solid #2d3748;
}
.btn {
    padding: 8px 12px;
    border-radius: 4px;
    font-weight: bold;
}
.btn-secondary {
    background-color: #4a5568;
    color: white;
}
.btn-secondary:disabled {
    background-color: #2d3748;
    cursor: not-allowed;
}
.btn-danger {
    background-color: #e53e3e;
    color: white;
}

.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.modal {
    background-color: #1a202c;
    width: 500px;
    max-height: 80%;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.2rem;
    border-bottom: 1px solid #4a5568;
    color: white;
}

.close-button {
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
}

.modal-body {
    padding: 1rem;
}

.close-button:hover {
    color: #e53e3e;
}

.option {
    color: white;
}

.modal-content {
    background-color: $dark-gray;
    width: 80%;
    max-width: 1000px;
    max-height: 90vh;
    overflow-y: auto;
    border-radius: 8px;
    padding: 20px;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;

    h2 {
        color: white;
        font-size: 1.5rem;
    }
}

.close-button {
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;

    &:hover {
        color: $red;
    }
}
</style>

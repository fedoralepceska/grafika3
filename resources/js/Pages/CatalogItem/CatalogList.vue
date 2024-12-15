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
                        <th class="p-4">Actions</th>
<!--                        <th class="p-4">Options</th>-->
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
                        <td class="p-4">
                            <button
                                @click="openActionsDialog(item)"
                                class="btn btn-info"
                            >
                                <i class="fas fa-info-circle"></i>
                            </button>
                        </td>
<!--                        <td class="p-4">-->
<!--                            <div class="flex space-x-2">-->
<!--                                <Link-->
<!--                                    :href="route('catalog.edit', item.id)"-->
<!--                                    class="btn btn-secondary"-->
<!--                                >-->
<!--                                    Edit-->
<!--                                </Link>-->
<!--                                <button-->
<!--                                    @click="deleteCatalogItem(item.id)"-->
<!--                                    class="btn btn-danger"-->
<!--                                >-->
<!--                                    Delete-->
<!--                                </button>-->
<!--                            </div>-->
<!--                        </td>-->
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
    </MainLayout>
</template>

<script>
import MainLayout from "@/Layouts/MainLayout.vue";
import Header from "@/Components/Header.vue";
import { Link } from "@inertiajs/vue3";

export default {
    components: {
        MainLayout,
        Header,
        Link,
    },
    props: {
        catalogItems: Array,  // Accept catalogItems as an array
        pagination: Object,   // Accept pagination data
    },
    data() {
        return {
            searchQuery: "",
            selectedItem: null, // Store selected item for actions dialog
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
    },
};
</script>

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
</style>

<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="Catalog" subtitle="All Catalog Items" icon="List.png" link="catalog.create" buttonText="Create New Item" />
        </div>

        <div class="p-4">
            <div class="bg-gray-800 p-6 rounded-lg">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-white text-lg font-semibold">Catalog Items</h2>
                    <div>
                        <input
                            v-model="searchQuery"
                            @input="fetchCatalogItems"
                            type="text"
                            placeholder="Search by name..."
                            class="rounded p-2"
                        />
                    </div>
                </div>
{{catalogItems.data}}
                <table class="w-full text-left border-collapse">
                    <thead>
                    <tr class="bg-gray-700 text-white">
                        <th class="p-4">Name</th>
                        <th class="p-4">Machine Print</th>
                        <th class="p-4">Machine Cut</th>
                        <th class="p-4">Large Material</th>
                        <th class="p-4">Small Material</th>
                        <th class="p-4">Actions</th>
                        <th class="p-4">Options</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr
                        v-for="item in catalogItems.data"
                        :key="item.id"
                        class="bg-gray-800 text-white border-t border-gray-700"
                    >
                        <td class="p-4">{{ item.name }}</td>
                        <td class="p-4">{{ item.machinePrint }}</td>
                        <td class="p-4">{{ item.machineCut }}</td>
                        <td class="p-4">
                            {{ item.large_material?.article?.name || 'N/A' }}
                        </td>
                        <td class="p-4">
                            {{ item.small_material?.article?.name || 'N/A' }}
                        </td>
                        <td class="p-4">
                            <ul>
                                <li
                                    v-for="action in item.actions"
                                    :key="action.action_id.id"
                                >
                                    {{ action.action_id.name }} -
                                    Status: {{ action.status }} -
                                    Quantity: {{ action.quantity || 'N/A' }}
                                </li>
                            </ul>
                        </td>
                        <td class="p-4">
                            <div class="flex space-x-2">
                                <Link
                                    :href="route('catalog.edit', item.id)"
                                    class="btn btn-secondary"
                                >
                                    Edit
                                </Link>
                                <button
                                    @click="deleteCatalogItem(item.id)"
                                    class="btn btn-danger"
                                >
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <div class="flex justify-between items-center mt-4">
                    <button
                        :disabled="!catalogItems.links.prev"
                        @click="fetchCatalogItems(catalogItems.links.prev)"
                        class="btn btn-secondary"
                    >
                        Previous
                    </button>
                    <span class="text-white">
                        Page {{ catalogItems.meta.current_page }} of {{ catalogItems.meta.last_page }}
                    </span>
                    <button
                        :disabled="!catalogItems.links.next"
                        @click="fetchCatalogItems(catalogItems.links.next)"
                        class="btn btn-secondary"
                    >
                        Next
                    </button>
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
        catalogItems: Object,
    },
    data() {
        return {
            catalogItems: {
                data: [],
                meta: {
                    current_page: 1,
                    last_page: 1,
                },
                links: {
                    prev: null,
                    next: null,
                },
            },
            searchQuery: "",
        };
    },

    methods: {
        async fetchCatalogItems(url = route("catalog.index")) {
            const toast = useToast();

            try {
                const response = await axios.get(url, {
                    params: { search: this.searchQuery },
                });
                this.catalogItems = response.data;
            } catch (error) {
                toast.error("Failed to fetch catalog items.");
            }
        },
        async deleteCatalogItem(id) {
            const toast = useToast();

            if (!confirm("Are you sure you want to delete this catalog item?")) {
                return;
            }

            try {
                await axios.delete(route("catalog.destroy", id));
                toast.success("Catalog item deleted successfully.");
                this.fetchCatalogItems();
            } catch (error) {
                toast.error("Failed to delete catalog item.");
            }
        },
    },
    mounted() {
        this.fetchCatalogItems();
    },
};
</script>

<style scoped>
table {
    width: 100%;
    border-collapse: collapse;
}
thead th {
    padding: 10px;
    background-color: #2d3748;
    color: white;
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
</style>

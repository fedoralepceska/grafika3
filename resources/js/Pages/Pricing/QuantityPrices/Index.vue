<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <div class="flex justify-between align-center">
                <Header
                    title="Pricing"
                    subtitle="Quantity-Based Prices"
                    icon="Price.png"
                    link="quantity-prices"
                    buttonText="Add New Price Range"
                />
                <div class="flex align-center py-5">
                    <Link
                        :href="route('quantity-prices.create')"
                        class="btn create-order2"
                    >
                        Add New Price Range
                    </Link>
                </div>
            </div>

            <div class="dark-gray p-2 text-white">
                <div class="form-container p-2">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="sub-title">Quantity-Based Prices</h2>
                    </div>

                    <!-- Search Bar -->
                    <div class="mb-4">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search by catalog item or client name..."
                            class="rounded p-2 bg-gray-700 text-white w-full"
                            @input="handleSearch"
                        />
                    </div>

                    <!-- Prices Table -->
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-700 text-white">
                                <th class="p-4">Catalog Item</th>
                                <th class="p-4">Client</th>
                                <th class="p-4">Price Ranges</th>
                                <th class="p-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="price in prices.data"
                                :key="`${price.catalog_item.id}-${price.client.id}`"
                                class="bg-gray-800 text-white border-t border-gray-700"
                            >
                                <td class="p-4">{{ price.catalog_item.name }}</td>
                                <td class="p-4">{{ price.client.name }}</td>
                                <td class="p-4">
                                    <div class="text-sm">
                                        <div class="font-semibold">{{ price.price_count }} range(s)</div>
                                        <div class="text-gray-300">{{ price.ranges_summary }}</div>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <div class="flex space-x-2">
                                        <Link
                                            :href="route('quantity-prices.view', [price.catalog_item.id, price.client.id])"
                                            class="btn btn-secondary"
                                        >
                                            <i class="fas fa-eye"></i> View
                                        </Link>
                                        <Link
                                            :href="route('quantity-prices.edit-group', [price.catalog_item.id, price.client.id])"
                                            class="btn btn-secondary"
                                        >
                                            <i class="fas fa-edit"></i> Edit
                                        </Link>
                                        <button
                                            @click="confirmDelete(price)"
                                            class="btn btn-danger"
                                        >
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div class="mt-4 flex justify-between items-center">
                        <button
                            :disabled="!prices.prev_page_url"
                            @click="changePage(prices.current_page - 1)"
                            class="btn btn-secondary"
                        >
                            Previous
                        </button>
                        <span class="text-white">
                            Page {{ prices.current_page }} of {{ prices.last_page }}
                        </span>
                        <button
                            :disabled="!prices.next_page_url"
                            @click="changePage(prices.current_page + 1)"
                            class="btn btn-secondary"
                        >
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="modal-backdrop">
            <div class="modal">
                <div class="modal-header">
                    <h2>Confirm Delete</h2>
                    <button @click="showDeleteModal = false" class="close-button">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete all quantity prices for <strong>{{ selectedPrice?.catalog_item?.name }}</strong> and <strong>{{ selectedPrice?.client?.name }}</strong>?</p>
                    <div class="mt-4 flex justify-end space-x-2">
                        <button @click="showDeleteModal = false" class="btn btn-secondary">
                            Cancel
                        </button>
                        <button @click="deletePrice" class="btn btn-danger">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script>
import debounce from 'lodash.debounce';
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
        prices: Object
    },

    data() {
        return {
            searchQuery: '',
            showDeleteModal: false,
            selectedPrice: null
        };
    },

    methods: {
        handleSearch: debounce(function() {
            this.$inertia.get(route('quantity-prices.index'), {
                search: this.searchQuery
            }, {
                preserveState: true,
                preserveScroll: true
            });
        }, 300),

        changePage(page) {
            this.$inertia.get(route('quantity-prices.index'), {
                page: page,
                search: this.searchQuery
            }, {
                preserveState: true,
                preserveScroll: true
            });
        },

        confirmDelete(price) {
            this.selectedPrice = price;
            this.showDeleteModal = true;
        },

        async deletePrice() {
            try {
                await this.$inertia.delete(route('quantity-prices.destroy-group', [this.selectedPrice.catalog_item.id, this.selectedPrice.client.id]));
                this.showDeleteModal = false;
                this.selectedPrice = null;
                useToast().success('Quantity prices deleted successfully');
            } catch (error) {
                useToast().error('Failed to delete quantity prices');
            }
        }
    }
};
</script>

<style scoped lang="scss">
.dark-gray {
    background-color: $dark-gray;
    min-height: 20vh;
    min-width: 80vh;
}

.form-container {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.sub-title {
    font-size: 20px;
    font-weight: bold;
    color: $white;
}

.btn {
    padding: 8px 12px;
    border-radius: 4px;
    font-weight: bold;
    cursor: pointer;
    transition: background-color 0.2s;

    &.btn-secondary {
        background-color: $light-gray;
        color: $white;

        &:hover {
            background-color: lighten($light-gray, 10%);
        }

        &:disabled {
            background-color: darken($light-gray, 20%);
            cursor: not-allowed;
        }
    }

    &.btn-danger {
        background-color: $red;
        color: $white;

        &:hover {
            background-color: darken($red, 10%);
        }
    }

    &.create-order2 {
        background-color: $green;
        color: $white;
        padding: 10px 20px;
        border-radius: 4px;
        font-weight: bold;
        text-decoration: none;
        display: inline-block;

        &:hover {
            background-color: darken($green, 10%);
        }
    }
}

.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.modal {
    background-color: $dark-gray;
    border-radius: 8px;
    width: 400px;
    max-width: 90%;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid $light-gray;

    h2 {
        color: $white;
        margin: 0;
    }
}

.modal-body {
    padding: 1rem;
    color: $white;
}

.close-button {
    background: none;
    border: none;
    color: $white;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0;
    line-height: 1;

    &:hover {
        color: $red;
    }
}
</style> 
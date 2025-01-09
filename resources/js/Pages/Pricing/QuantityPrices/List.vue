<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header
                title="Quantity-Based Prices"
                subtitle="Manage prices based on quantity ranges"
                icon="Price.png"
            />

            <div class="dark-gray p-2 text-white">
                <!-- Search and Add Button -->
                <div class="flex justify-between items-center mb-4">
                    <div class="flex-1 max-w-md">
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search by item or client name..."
                            class="form-input"
                            @input="debouncedSearch"
                        />
                    </div>
                    <Link
                        :href="route('quantity-prices.create')"
                        class="btn btn-primary ml-4"
                    >
                        Add Quantity Price
                    </Link>
                </div>

                <!-- Prices Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-dark-gray">
                        <thead>
                            <tr class="border-b border-gray-700">
                                <th class="px-4 py-2 text-left">Catalog Item</th>
                                <th class="px-4 py-2 text-left">Client</th>
                                <th class="px-4 py-2 text-left">Quantity Range</th>
                                <th class="px-4 py-2 text-left">Default Price</th>
                                <th class="px-4 py-2 text-left">Range Price</th>
                                <th class="px-4 py-2 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="price in quantityPrices"
                                :key="price.id"
                                class="border-b border-gray-700 hover:bg-gray-700"
                            >
                                <td class="px-4 py-2">
                                    {{ price.catalog_item.name }}
                                </td>
                                <td class="px-4 py-2">
                                    {{ price.client.name }}
                                </td>
                                <td class="px-4 py-2">
                                    {{ formatQuantityRange(price) }}
                                </td>
                                <td class="px-4 py-2">
                                    {{ formatPrice(price.catalog_item.price) }}
                                </td>
                                <td class="px-4 py-2">
                                    {{ formatPrice(price.price) }}
                                </td>
                                <td class="px-4 py-2">
                                    <div class="flex space-x-2">
                                        <Link
                                            :href="route('quantity-prices.edit', price.id)"
                                            class="text-blue-500 hover:text-blue-400"
                                        >
                                            Edit
                                        </Link>
                                        <button
                                            @click="confirmDelete(price)"
                                            class="text-red-500 hover:text-red-400"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="quantityPrices.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-gray-400">
                                    No quantity-based prices found.
                                    <Link
                                        :href="route('quantity-prices.create')"
                                        class="text-blue-500 hover:text-blue-400 ml-2"
                                    >
                                        Add one now
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-4 flex justify-between items-center" v-if="pagination">
                    <button
                        :disabled="pagination.current_page === 1"
                        @click="changePage(pagination.current_page - 1)"
                        class="btn btn-secondary"
                    >
                        Previous
                    </button>
                    <span class="text-white">
                        Page {{ pagination.current_page }} of {{ pagination.total_pages }}
                    </span>
                    <button
                        :disabled="pagination.current_page === pagination.total_pages"
                        @click="changePage(pagination.current_page + 1)"
                        class="btn btn-secondary"
                    >
                        Next
                    </button>
                </div>

                <!-- Delete Confirmation Modal -->
                <Modal v-if="showDeleteModal" @close="showDeleteModal = false">
                    <template #title>
                        Confirm Delete
                    </template>
                    <template #content>
                        Are you sure you want to delete the quantity-based price for
                        <strong>{{ selectedPrice?.catalog_item.name }}</strong>
                        ({{ formatQuantityRange(selectedPrice) }})
                        for client
                        <strong>{{ selectedPrice?.client.name }}</strong>?
                        This action cannot be undone.
                    </template>
                    <template #footer>
                        <button
                            @click="showDeleteModal = false"
                            class="btn btn-secondary mr-2"
                        >
                            Cancel
                        </button>
                        <button
                            @click="deletePrice"
                            class="btn btn-danger"
                            :disabled="isDeleting"
                        >
                            {{ isDeleting ? 'Deleting...' : 'Delete' }}
                        </button>
                    </template>
                </Modal>
            </div>
        </div>
    </MainLayout>
</template>

<script>
import MainLayout from '@/Layouts/MainLayout.vue';
import Header from '@/Components/Header.vue';
import { Link } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';
import { useToast } from 'vue-toastification';
import debounce from 'lodash.debounce';

export default {
    components: {
        MainLayout,
        Header,
        Link,
        Modal,
        Pagination
    },

    props: {
        quantityPrices: {
            type: Array,
            required: true
        },
        pagination: {
            type: Object,
            default: null
        },
        filters: {
            type: Object,
            default: () => ({})
        }
    },

    data() {
        return {
            search: this.filters.search || '',
            showDeleteModal: false,
            selectedPrice: null,
            isDeleting: false
        };
    },

    methods: {
        formatPrice(price) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(price);
        },

        formatQuantityRange(price) {
            if (!price) return '';

            if (!price.quantity_from) {
                return `Up to ${price.quantity_to} units`;
            }

            if (!price.quantity_to) {
                return `${price.quantity_from} units and above`;
            }

            return `${price.quantity_from} to ${price.quantity_to} units`;
        },

        confirmDelete(price) {
            this.selectedPrice = price;
            this.showDeleteModal = true;
        },

        async deletePrice() {
            if (!this.selectedPrice) return;

            this.isDeleting = true;
            try {
                await this.$inertia.delete(
                    route('quantity-prices.destroy', this.selectedPrice.id)
                );
                useToast().success('Quantity price deleted successfully');
                this.showDeleteModal = false;
            } catch (error) {
                useToast().error('Failed to delete quantity price');
            } finally {
                this.isDeleting = false;
                this.selectedPrice = null;
            }
        },

        debouncedSearch: debounce(function () {
            this.$inertia.get(
                route('quantity-prices.index'),
                { search: this.search },
                { preserveState: true }
            );
        }, 300),

        changePage(page) {
            this.$inertia.get(
                route('quantity-prices.index'),
                {
                    search: this.search,
                    page: page,
                    per_page: this.pagination.per_page
                },
                { preserveState: true }
            );
        }
    }
};
</script>

<style scoped lang="scss">
.dark-gray {
    background-color: $dark-gray;
    min-height: 20vh;
}

.form-input {
    width: 100%;
    padding: 0.5rem;
    border-radius: 4px;
    background-color: $light-gray;
    color: $white;
    border: 1px solid $ultra-light-gray;

    &:focus {
        outline: none;
        border-color: $green;
    }
}

.btn {
    padding: 0.5rem 1rem;
    border-radius: 4px;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.2s;

    &.btn-primary {
        background-color: $green;
        color: $white;

        &:hover:not(:disabled) {
            background-color: darken($green, 10%);
        }
    }

    &.btn-secondary {
        background-color: $light-gray;
        color: $white;

        &:hover:not(:disabled) {
            background-color: darken($light-gray, 10%);
        }
    }

    &.btn-danger {
        background-color: $red;
        color: $white;

        &:hover:not(:disabled) {
            background-color: darken($red, 10%);
        }

        &:disabled {
            background-color: darken($red, 30%);
            cursor: not-allowed;
        }
    }
}

table {
    width: 100%;
    border-collapse: collapse;

    th {
        font-weight: 500;
        text-align: left;
    }

    td, th {
        padding: 0.75rem 1rem;
    }

    tbody tr {
        &:hover {
            background-color: rgba($light-gray, 0.1);
        }
    }
}
</style>
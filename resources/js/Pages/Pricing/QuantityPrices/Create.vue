<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header
                title="Pricing"
                subtitle="Quantity-Based Prices"
                icon="Price.png"
                link="quantity-prices"
                buttonText="Back to List"
            />

            <div class="dark-gray p-2 text-white">
                <div class="form-container p-2">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="sub-title">Create New Quantity Prices</h2>
                        <Link
                            :href="route('quantity-prices.index')"
                            class="btn btn-secondary"
                        >
                            <i class="fas fa-arrow-left"></i> Back
                        </Link>
                    </div>

                    <form @submit.prevent="submitForm" class="space-y-6">
                        <!-- Catalog Item and Client Selection -->
                        <div class="bg-gray-700 p-4 rounded">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-2">Catalog Item</label>
                                    <div class="relative">
                                        <input
                                            v-model="catalogItemSearch"
                                            type="text"
                                            placeholder="Search catalog items..."
                                            class="w-full p-2 bg-gray-600 text-white rounded"
                                            @input="filterCatalogItems"
                                            @focus="showCatalogDropdown = true"
                                        />
                                        <div v-if="showCatalogDropdown && filteredCatalogItems.length > 0" class="absolute z-10 w-full bg-gray-600 border border-gray-500 rounded-b max-h-60 overflow-y-auto">
                                            <div
                                                v-for="item in filteredCatalogItems"
                                                :key="item.id"
                                                @click="selectCatalogItem(item)"
                                                class="p-2 hover:bg-gray-500 cursor-pointer"
                                            >
                                                {{ item.name }} ({{ formatPrice(item.price) }})
                                            </div>
                                        </div>
                                    </div>
                                    <div v-if="selectedCatalogItem" class="mt-2 text-sm text-green-400">
                                        Selected: {{ selectedCatalogItem.name }}
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-2">Client</label>
                                    <div class="relative">
                                        <input
                                            v-model="clientSearch"
                                            type="text"
                                            placeholder="Search clients..."
                                            class="w-full p-2 bg-gray-600 text-white rounded"
                                            @input="filterClients"
                                            @focus="showClientDropdown = true"
                                        />
                                        <div v-if="showClientDropdown && filteredClients.length > 0" class="absolute z-10 w-full bg-gray-600 border border-gray-500 rounded-b max-h-60 overflow-y-auto">
                                            <div
                                                v-for="client in filteredClients"
                                                :key="client.id"
                                                @click="selectClient(client)"
                                                class="p-2 hover:bg-gray-500 cursor-pointer"
                                            >
                                                {{ client.name }}
                                            </div>
                                        </div>
                                    </div>
                                    <div v-if="selectedClient" class="mt-2 text-sm text-green-400">
                                        Selected: {{ selectedClient.name }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Price Ranges -->
                        <div class="bg-gray-700 p-4 rounded">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold">Quantity Price Ranges</h3>
                                <button
                                    type="button"
                                    @click="addRange"
                                    class="btn btn-secondary"
                                    :disabled="!canAddRanges"
                                >
                                    <i class="fas fa-plus"></i> Add Range
                                </button>
                            </div>

                            <div v-if="!canAddRanges" class="text-center py-4 text-yellow-400">
                                Please select both a catalog item and a client before adding price ranges.
                            </div>

                            <div v-else-if="form.ranges.length === 0" class="text-center py-4 text-gray-400">
                                No price ranges defined. Click "Add Range" to get started.
                            </div>

                            <div v-else class="space-y-4">
                                <div
                                    v-for="(range, index) in form.ranges"
                                    :key="index"
                                    class="bg-gray-800 p-4 rounded"
                                >
                                    <div class="flex justify-between items-center mb-3">
                                        <h4 class="font-medium">Range {{ index + 1 }}</h4>
                                        <button
                                            type="button"
                                            @click="removeRange(index)"
                                            class="btn btn-danger"
                                        >
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                    
                                    <div class="grid grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium mb-1">From Quantity</label>
                                            <input
                                                v-model.number="range.quantity_from"
                                                type="number"
                                                min="0"
                                                class="w-full p-2 bg-gray-600 text-white rounded"
                                                placeholder="Leave empty for 'up to'"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium mb-1">To Quantity</label>
                                            <input
                                                v-model.number="range.quantity_to"
                                                type="number"
                                                min="0"
                                                class="w-full p-2 bg-gray-600 text-white rounded"
                                                placeholder="Leave empty for 'and above'"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium mb-1">Price per Unit</label>
                                            <input
                                                v-model.number="range.price"
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                class="w-full p-2 bg-gray-600 text-white rounded"
                                                placeholder="0.00"
                                                required
                                            />
                                        </div>
                                    </div>
                                    
                                    <div class="mt-2 text-sm text-gray-400">
                                        {{ getRangeDescription(range) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex justify-end space-x-2">
                            <Link
                                :href="route('quantity-prices.index')"
                                class="btn btn-secondary"
                            >
                                Cancel
                            </Link>
                            <button
                                type="submit"
                                class="btn btn-primary"
                                :disabled="isSubmitting || !canSubmit"
                            >
                                <i v-if="isSubmitting" class="fas fa-spinner fa-spin"></i>
                                {{ isSubmitting ? 'Creating...' : 'Create Price Ranges' }}
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
import { Link } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';

export default {
    components: {
        MainLayout,
        Header,
        Link
    },

    props: {
        catalogItems: Array,
        clients: Array
    },

    data() {
        return {
            isSubmitting: false,
            form: {
                catalog_item_id: '',
                client_id: '',
                ranges: []
            },
            selectedCatalogItem: null,
            selectedClient: null,
            catalogItemSearch: '',
            clientSearch: '',
            showCatalogDropdown: false,
            showClientDropdown: false,
            filteredCatalogItems: [],
            filteredClients: []
        };
    },

    computed: {
        canAddRanges() {
            return this.selectedCatalogItem && this.selectedClient;
        },
        canSubmit() {
            return this.canAddRanges && this.form.ranges.length > 0;
        }
    },

    mounted() {
        this.filteredCatalogItems = this.catalogItems;
        this.filteredClients = this.clients;
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', this.handleClickOutside);
    },

    beforeUnmount() {
        document.removeEventListener('click', this.handleClickOutside);
    },

    methods: {
        formatPrice(price) {
            return new Intl.NumberFormat('mk-MK', {
                style: 'currency',
                currency: 'MKD'
            }).format(price);
        },

        getRangeDescription(range) {
            if (range.quantity_from === null && range.quantity_to === null) {
                return 'Please set at least one boundary';
            }
            if (range.quantity_from === null) {
                return `Up to ${range.quantity_to} units`;
            }
            if (range.quantity_to === null) {
                return `${range.quantity_from} units and above`;
            }
            return `${range.quantity_from} to ${range.quantity_to} units`;
        },

        selectCatalogItem(item) {
            this.selectedCatalogItem = item;
            this.form.catalog_item_id = item.id;
            this.catalogItemSearch = item.name;
            this.showCatalogDropdown = false;
        },

        selectClient(client) {
            this.selectedClient = client;
            this.form.client_id = client.id;
            this.clientSearch = client.name;
            this.showClientDropdown = false;
        },

        filterCatalogItems() {
            if (!this.catalogItemSearch) {
                this.filteredCatalogItems = this.catalogItems;
            } else {
                this.filteredCatalogItems = this.catalogItems.filter(item =>
                    item.name.toLowerCase().includes(this.catalogItemSearch.toLowerCase())
                );
            }
        },

        filterClients() {
            if (!this.clientSearch) {
                this.filteredClients = this.clients;
            } else {
                this.filteredClients = this.clients.filter(client =>
                    client.name.toLowerCase().includes(this.clientSearch.toLowerCase())
                );
            }
        },

        handleClickOutside(event) {
            if (!event.target.closest('.relative')) {
                this.showCatalogDropdown = false;
                this.showClientDropdown = false;
            }
        },

        addRange() {
            if (!this.canAddRanges) {
                useToast().error('Please select both a catalog item and a client first');
                return;
            }
            
            this.form.ranges.push({
                quantity_from: null,
                quantity_to: null,
                price: null
            });
        },

        removeRange(index) {
            this.form.ranges.splice(index, 1);
        },

        async submitForm() {
            // Validate form
            if (!this.canAddRanges) {
                useToast().error('Please select both a catalog item and a client');
                return;
            }

            if (this.form.ranges.length === 0) {
                useToast().error('At least one price range is required');
                return;
            }

            // Validate each range
            for (let i = 0; i < this.form.ranges.length; i++) {
                const range = this.form.ranges[i];
                if (range.price === null || range.price <= 0) {
                    useToast().error(`Range ${i + 1}: Price is required and must be greater than 0`);
                    return;
                }
                if (range.quantity_from === null && range.quantity_to === null) {
                    useToast().error(`Range ${i + 1}: At least one quantity boundary must be set`);
                    return;
                }
                if (range.quantity_from !== null && range.quantity_to !== null && range.quantity_from >= range.quantity_to) {
                    useToast().error(`Range ${i + 1}: "From" quantity must be less than "To" quantity`);
                    return;
                }
            }

            this.isSubmitting = true;

            try {
                await this.$inertia.post(route('quantity-prices.store'), this.form);
                useToast().success('Quantity prices created successfully');
            } catch (error) {
                useToast().error('Failed to create quantity prices');
            } finally {
                this.isSubmitting = false;
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

    &.btn-primary {
        background-color: $blue;
        color: $white;

        &:hover {
            background-color: darken($blue, 10%);
        }

        &:disabled {
            background-color: darken($blue, 20%);
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
}
</style> 
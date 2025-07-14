<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header
                :title="isEditing ? 'editClientPrice' : 'createClientPrice'"
                :subtitle="isEditing ? `Update price for ${clientPrice?.catalog_item?.name} - ${clientPrice?.client?.name}` : $t('addNewClientSpecificPrice')"
                icon="Price.png"
                link="client-prices"
            />

            <div class="dark-gray p-5 text-white">
                <div class="form-container p-5 light-gray">
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Catalog Item Selection -->
                        <div class="form-group" v-if="!isEditing">
                            <label class="form-label">{{$t('catalogItem')}}</label>
                            <div class="relative">
                                <input
                                    v-model="catalogItemSearch"
                                    type="text"
                                    :placeholder="$t('searchCatalogItem')"
                                    class="form-input pr-10"
                                    @input="filterCatalogItems"
                                    @focus="showCatalogItemDropdown = true"
                                    @blur="setTimeout(() => showCatalogItemDropdown = false, 200)"
                                />
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                                
                                <!-- Catalog Item Dropdown -->
                                <div v-if="showCatalogItemDropdown && filteredCatalogItems.length > 0" 
                                     class="absolute z-50 w-full mt-1 bg-gray-700 border border-gray-600 rounded-md shadow-lg max-h-60 overflow-auto">
                                    <div
                                        v-for="item in filteredCatalogItems"
                                        :key="item.id"
                                        @click="selectCatalogItem(item)"
                                        class="px-4 py-2 hover:bg-gray-600 cursor-pointer text-white"
                                    >
                                        <div class="font-medium">{{ item.name }}</div>
                                        <div class="text-sm text-gray-300">{{$t('default')}}: {{ formatPrice(item.price) }}</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Selected Catalog Item Display -->
                            <div v-if="selectedCatalogItem" class="mt-2 p-2 bg-gray-600 rounded">
                                <div class="text-sm text-gray-300">Selected:</div>
                                <div class="font-medium">{{ selectedCatalogItem.name }}</div>
                                <div class="text-sm text-gray-400">{{$t('default')}}: {{ formatPrice(selectedCatalogItem.price) }}</div>
                            </div>
                        </div>

                        <!-- Client Selection -->
                        <div class="form-group" v-if="!isEditing">
                            <label class="form-label">{{$t('client')}}</label>
                            <div class="relative">
                                <input
                                    v-model="clientSearch"
                                    type="text"
                                    :placeholder="$t('searchClient')"
                                    class="form-input pr-10"
                                    @input="filterClients"
                                    @focus="showClientDropdown = true"
                                    @blur="setTimeout(() => showClientDropdown = false, 200)"
                                />
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                                
                                <!-- Client Dropdown -->
                                <div v-if="showClientDropdown && filteredClients.length > 0" 
                                     class="absolute z-50 w-full mt-1 bg-gray-700 border border-gray-600 rounded-md shadow-lg max-h-60 overflow-auto">
                                    <div
                                        v-for="client in filteredClients"
                                        :key="client.id"
                                        @click="selectClient(client)"
                                        class="px-4 py-2 hover:bg-gray-600 cursor-pointer text-white"
                                    >
                                        <div class="font-medium">{{ client.name }}</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Selected Client Display -->
                            <div v-if="selectedClient" class="mt-2 p-2 bg-gray-600 rounded">
                                <div class="text-sm text-gray-300">Selected:</div>
                                <div class="font-medium">{{ selectedClient.name }}</div>
                            </div>
                        </div>

                        <!-- Edit Mode Display -->
                        <div v-if="isEditing" class="form-group">
                            <div class="bg-gray-700 p-4 rounded-lg">
                                <h3 class="text-lg font-semibold mb-3">Editing Price For:</h3>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="form-label text-gray-300">Catalog Item:</label>
                                        <div class="text-white font-medium">{{ clientPrice?.catalog_item?.name }}</div>
                                        <div class="text-gray-400 text-sm">Default: {{ formatPrice(clientPrice?.catalog_item?.price) }}</div>
                                    </div>
                                    <div>
                                        <label class="form-label text-gray-300">Client:</label>
                                        <div class="text-white font-medium">{{ clientPrice?.client?.name }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Price Input -->
                        <div class="form-group">
                            <label class="form-label">{{$t('clientSpecificPrice')}}</label>
                            <input
                                v-model="form.price"
                                type="number"
                                step="0.01"
                                min="0"
                                class="form-input"
                                required
                            />
                            <small class="text-gray-400">
                                {{$t('overrideDefaultPriceMessage')}}
                            </small>
                        </div>

                        <!-- Error Message -->
                        <div v-if="error" class="text-red-500 mb-4">
                            {{ error }}
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end">
                            <button type="submit" class="btn btn-primary">
                                {{ isEditing ? $t('update') : $t('create') }} {{$t('clientPrice')}}
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

export default {
    components: {
        MainLayout,
        Header
    },

    props: {
        clientPrice: {
            type: Object,
            default: null
        },
        catalogItems: {
            type: Array,
            required: true
        },
        clients: {
            type: Array,
            required: true
        }
    },

    data() {
        return {
            form: {
                catalog_item_id: this.clientPrice?.catalog_item_id || '',
                client_id: this.clientPrice?.client_id || '',
                price: this.clientPrice?.price || ''
            },
            error: null,
            // Search functionality
            catalogItemSearch: '',
            clientSearch: '',
            showCatalogItemDropdown: false,
            showClientDropdown: false,
            filteredCatalogItems: [],
            filteredClients: [],
            selectedCatalogItem: null,
            selectedClient: null
        };
    },

    computed: {
        isEditing() {
            return !!this.clientPrice;
        }
    },

    mounted() {
        // Initialize filtered arrays
        this.filteredCatalogItems = this.catalogItems;
        this.filteredClients = this.clients;
        
        // If editing, set the selected items
        if (this.isEditing && this.clientPrice) {
            this.selectedCatalogItem = this.clientPrice.catalog_item;
            this.selectedClient = this.clientPrice.client;
        }
    },

    methods: {
        formatPrice(price) {
            return new Intl.NumberFormat('mk-MK', {
                style: 'currency',
                currency: 'MKD'
            }).format(price);
        },

        filterCatalogItems() {
            if (!this.catalogItemSearch.trim()) {
                this.filteredCatalogItems = this.catalogItems;
            } else {
                this.filteredCatalogItems = this.catalogItems.filter(item =>
                    item.name.toLowerCase().includes(this.catalogItemSearch.toLowerCase())
                );
            }
        },

        filterClients() {
            if (!this.clientSearch.trim()) {
                this.filteredClients = this.clients;
            } else {
                this.filteredClients = this.clients.filter(client =>
                    client.name.toLowerCase().includes(this.clientSearch.toLowerCase())
                );
            }
        },

        selectCatalogItem(item) {
            this.selectedCatalogItem = item;
            this.form.catalog_item_id = item.id;
            this.catalogItemSearch = item.name;
            this.showCatalogItemDropdown = false;
        },

        selectClient(client) {
            this.selectedClient = client;
            this.form.client_id = client.id;
            this.clientSearch = client.name;
            this.showClientDropdown = false;
        },

        async submit() {
            // Validate that both catalog item and client are selected
            if (!this.isEditing && (!this.form.catalog_item_id || !this.form.client_id)) {
                this.error = 'Please select both a catalog item and a client.';
                return;
            }

            try {
                if (this.isEditing) {
                    await this.$inertia.put(
                        route('client-prices.update', this.clientPrice.id),
                        this.form
                    );
                } else {
                    await this.$inertia.post(route('client-prices.store'), this.form);
                }

                useToast().success(
                    `Client price ${this.isEditing ? 'updated' : 'created'} successfully`
                );
            } catch (error) {
                if (error.response?.data?.message) {
                    this.error = error.response.data.message;
                } else {
                    useToast().error(
                        `Failed to ${this.isEditing ? 'update' : 'create'} client price`
                    );
                }
            }
        }
    }
};
</script>

<style scoped lang="scss">
.light-gray{
    background-color: $light-gray;
}
.dark-gray {
    background-color: $dark-gray;
    min-height: 20vh;
    min-width: 80vh;
}

.form-container {
    max-width: 600px;
    margin: 0 auto;
}

.form-group {
    margin-bottom: 1rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    color: $white;
    font-weight: 500;
}

.form-select,
.form-input {
    width: 100%;
    padding: 0.5rem;
    border-radius: 4px;
    background-color: $dark-gray;
    color: $white;
    border: 1px solid $ultra-light-gray;

    &:focus {
        outline: none;
        border-color: $green;
    }

    &:disabled {
        background-color: darken($light-gray, 10%);
        cursor: not-allowed;
    }
}

.relative {
    position: relative;
}

.absolute {
    position: absolute;
}

.inset-y-0 {
    top: 0;
    bottom: 0;
}

.right-0 {
    right: 0;
}

.flex {
    display: flex;
}

.items-center {
    align-items: center;
}

.pr-3 {
    padding-right: 0.75rem;
}

.pr-10 {
    padding-right: 2.5rem;
}

.mt-1 {
    margin-top: 0.25rem;
}

.mt-2 {
    margin-top: 0.5rem;
}

.z-50 {
    z-index: 50;
}

.max-h-60 {
    max-height: 15rem;
}

.overflow-auto {
    overflow: auto;
}

.px-4 {
    padding-left: 1rem;
    padding-right: 1rem;
}

.py-2 {
    padding-top: 0.5rem;
    padding-bottom: 0.5rem;
}

.hover\:bg-gray-600:hover {
    background-color: #4b5563;
}

.cursor-pointer {
    cursor: pointer;
}

.text-sm {
    font-size: 0.875rem;
    line-height: 1.25rem;
}

.font-medium {
    font-weight: 500;
}

.text-gray-300 {
    color: #d1d5db;
}

.text-gray-400 {
    color: #9ca3af;
}

.border-gray-600 {
    border-color: #4b5563;
}

.shadow-lg {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

.rounded-md {
    border-radius: 0.375rem;
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

        &:hover {
            background-color: darken($green, 10%);
        }
    }
}

small {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.875rem;
}
</style>

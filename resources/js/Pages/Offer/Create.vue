<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="Offer" subtitle="Create New Offer" icon="List.png" link="offers" />

            <div class="dark-gray p-2 text-white">
                <div class="form-container p-2">
                    <h2 class="sub-title">Offer Creation</h2>

                    <form @submit.prevent="submit" class="space-y-6 w-full rounded-lg">
                    <!-- Basic Information -->
                        <div class="grid grid-cols-1 gap-6">
                            <!-- Client Selection, Name, Description -->
                            <div class="grid grid-cols-2 gap-6">
                                <div class="space-y-4">
                        <div>
                                        <label class="text-white">Client</label>
                            <select
                                v-model="form.client_id"
                                            class="w-full mt-1 rounded text-black"
                                required
                            >
                                <option value="" disabled>Select a client</option>
                                <option v-for="client in clients" :key="client.id" :value="client.id">
                                    {{ client.name }}
                                </option>
                            </select>
                        </div>

                                    <div>
                                        <label class="text-white">Name</label>
                                        <input
                                            v-model="form.name"
                                            type="text"
                                            class="w-full mt-1 rounded text-black"
                                            required
                                        />
                                    </div>

                                    <div>
                                        <label class="text-white">Description</label>
                                        <textarea
                                            v-model="form.description"
                                            class="w-full mt-1 rounded text-black"
                                            rows="3"
                                            placeholder="Enter offer description"
                                        ></textarea>
                                    </div>

                        <div>
                                        <label class="text-white">Validity (Days)</label>
                            <input
                                            v-model.number="form.validity_days"
                                type="number"
                                min="1"
                                            class="w-full mt-1 rounded text-black"
                                required
                                        />
                                    </div>
                        </div>

                                <div class="space-y-4">
                        <div>
                                        <label class="text-white">Production Start Date</label>
                            <input
                                v-model="form.production_start_date"
                                type="date"
                                            class="w-full mt-1 rounded text-black"
                                required
                                        />
                        </div>

                        <div>
                                        <label class="text-white">Production End Date</label>
                            <input
                                v-model="form.production_end_date"
                                type="date"
                                            class="w-full mt-1 rounded text-black"
                                required
                                        />
                                    </div>

                                    <div>
                                        <label class="text-white">Price 1</label>
                                        <input
                                            v-model.number="form.price1"
                                            type="number"
                                            step="0.01"
                                            class="w-full mt-1 rounded text-black"
                                            required
                                        />
                                    </div>

                                    <div>
                                        <label class="text-white">Price 2</label>
                                        <input
                                            v-model.number="form.price2"
                                            type="number"
                                            step="0.01"
                                            class="w-full mt-1 rounded text-black"
                                            required
                                        />
                                    </div>

                                    <div>
                                        <label class="text-white">Price 3</label>
                                        <input
                                            v-model.number="form.price3"
                                            type="number"
                                            step="0.01"
                                            class="w-full mt-1 rounded text-black"
                                            required
                                        />
                                    </div>
                        </div>
                    </div>

                            <!-- Selected Items Summary -->
                            <div v-if="form.catalog_items.length > 0" class="bg-gray-800 p-4 rounded-lg">
                                <h3 class="text-white font-medium mb-3">Selected Items ({{ form.catalog_items.length }})</h3>
                                <div class="space-y-3">
                                    <div v-for="item in form.catalog_items" :key="item.id" class="bg-gray-700 p-3 rounded-lg">
                                        <div class="flex justify-between items-start">
                                            <div class="flex-1">
                                                <h4 class="text-white font-medium">{{ item.name }}</h4>
                                                <div class="mt-2 space-y-2">
                                                    <div>
                                                        <label class="text-gray-300 text-sm">Quantity</label>
                                    <input
                                                            v-model.number="item.quantity"
                                                            type="number"
                                                            min="1"
                                                            class="w-24 mt-1 rounded text-black"
                                                            required
                                                        />
                                                    </div>
                                                    <div>
                                                        <label class="text-gray-300 text-sm">Custom Description</label>
                                                        <textarea
                                                            v-model="item.description"
                                                            class="w-full mt-1 rounded text-black"
                                                            rows="2"
                                                            placeholder="Optional custom description for this item"
                                                        ></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                            <button
                                                @click="removeItem(item)"
                                                class="text-red-400 hover:text-red-300 transition-colors"
                                                title="Remove Item"
                                            >
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Catalog Items Section -->
                            <div class="w-full">
                                <label class="text-white">Catalog Items</label>
                                <div class="catalog-tabs">
                                    <!-- Search Input -->
                                    <div class="px-4 py-2 border-b border-gray-700">
                                        <div class="relative">
                                            <input
                                                v-model="searchQuery"
                                                type="text"
                                                placeholder="Search items..."
                                                class="w-full px-3 py-2 pl-10 bg-gray-700 border border-gray-600 rounded text-white placeholder-gray-400 focus:border-green-500 focus:outline-none"
                                            />
                                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                        </div>
                                    </div>
                                    <div class="flex justify-between border-b border-gray-700">
                                        <div class="flex">
                                            <button 
                                                type="button"
                                                @click="activeTab = 'large'"
                                                :class="['tab-button', activeTab === 'large' ? 'active' : '']"
                                            >
                                                Large Format Materials
                                            </button>
                                            <button 
                                                type="button"
                                                @click="activeTab = 'small'"
                                                :class="['tab-button', activeTab === 'small' ? 'active' : '']"
                                            >
                                                Small Format Materials
                                            </button>
                                        </div>
                                        <div class="flex items-center mr-4 space-x-2">
                                            <button 
                                                type="button"
                                                @click="viewMode = 'list'"
                                                :class="['view-toggle-btn', viewMode === 'list' ? 'active' : '']"
                                                title="List View"
                                            >
                                                <i class="fas fa-list"></i>
                                            </button>
                                            <button 
                                                type="button"
                                                @click="viewMode = 'card'"
                                                :class="['view-toggle-btn', viewMode === 'card' ? 'active' : '']"
                                                title="Card View"
                                            >
                                                <i class="fas fa-th-large"></i>
                                            </button>
                            </div>
                        </div>
                    </div>

                                <div class="catalog-items-container">
                                    <!-- Large Format Materials Tab -->
                                    <div v-if="activeTab === 'large'" class="space-y-2">
                                        <div v-if="largeMaterialItems.length === 0" class="empty-state">
                                            No large format materials available
                                        </div>
                                        
                                        <!-- List View -->
                                        <div v-if="viewMode === 'list'" class="space-y-2">
                                            <div v-for="item in filteredLargeMaterialItems" 
                                                :key="item.id" 
                                                class="catalog-item"
                                                @click="toggleItemSelection(item)"
                                            >
                                                <input
                                                    type="checkbox"
                                                    :value="item.id"
                                                    v-model="selectedItems"
                                                    class="h-4 w-4 rounded border-gray-300 checkbox-green"
                                                    @click.stop
                                                />
                                                <div class="catalog-item-details">
                                                    <div class="catalog-item-name">{{ item.name }}</div>
                                                    <div class="catalog-item-material">
                                                        Material: {{ item.large_material?.name || 'N/A' }}
                                                    </div>
                                                </div>
                                                <div class="catalog-item-price">
                                                    {{ item.price ? `€${item.price}` : 'Price not set' }}
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Card View -->
                                        <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 2xl:grid-cols-8 gap-3">
                                            <div v-for="item in filteredLargeMaterialItems" 
                                                :key="item.id" 
                                                class="catalog-card"
                                                @click="toggleItemSelection(item)"
                                            >
                                                <div class="catalog-card-image">
                                                    <input
                                                        type="checkbox"
                                                        :value="item.id"
                                                        v-model="selectedItems"
                                                        class="absolute top-2 left-2 h-4 w-4 rounded border-gray-300 checkbox-green z-10"
                                                        @click.stop
                                                    />
                                                    <div v-if="isPlaceholder(item.file)" class="w-full h-full no-image">
                                                        NO IMAGE
                                                    </div>
                                                    <img 
                                                        v-else
                                                        :src="getFileUrl(item.file)"
                                                        :alt="item.name"
                                                        class="w-full h-full object-cover"
                                                    />
                                                </div>
                                                <div class="p-2">
                                                    <h3 class="font-medium text-gray-900 text-sm mb-1 truncate">{{ item.name }}</h3>
                                                    <p class="text-xs text-gray-500 mb-1 truncate">
                                                        Material: {{ item.large_material?.name || 'N/A' }}
                                                    </p>
                                                    <div class="text-xs font-medium text-gray-900">
                                                        {{ item.price ? `€${item.price}` : 'Price not set' }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Small Format Materials Tab -->
                                    <div v-if="activeTab === 'small'" class="space-y-2">
                                        <div v-if="smallMaterialItems.length === 0" class="empty-state">
                                            No small format materials available
                                        </div>
                                        
                                        <!-- List View -->
                                        <div v-if="viewMode === 'list'" class="space-y-2">
                                            <div v-for="item in filteredSmallMaterialItems" 
                                                :key="item.id" 
                                                class="catalog-item"
                                                @click="toggleItemSelection(item)"
                                            >
                                                <input
                                                    type="checkbox"
                                                    :value="item.id"
                                                    v-model="selectedItems"
                                                    class="h-4 w-4 rounded border-gray-300 checkbox-green"
                                                    @click.stop
                                                />
                                                <div class="catalog-item-details">
                                                    <div class="catalog-item-name">{{ item.name }}</div>
                                                    <div class="catalog-item-material">
                                                        Material: {{ item.small_material?.name || 'N/A' }}
                                                    </div>
                                                </div>
                                                <div class="catalog-item-price">
                                                    {{ item.price ? `€${item.price}` : 'Price not set' }}
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Card View -->
                                        <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 2xl:grid-cols-8 gap-3">
                                            <div v-for="item in filteredSmallMaterialItems" 
                                                :key="item.id" 
                                                class="catalog-card"
                                                @click="toggleItemSelection(item)"
                                            >
                                                <div class="catalog-card-image">
                                                    <input
                                                        type="checkbox"
                                                        :value="item.id"
                                                        v-model="selectedItems"
                                                        class="absolute top-2 left-2 h-4 w-4 rounded border-gray-300 checkbox-green z-10"
                                                        @click.stop
                                                    />
                                                    <div v-if="isPlaceholder(item.file)" class="w-full h-full no-image">
                                                        NO IMAGE
                                                    </div>
                                                    <img 
                                                        v-else
                                                        :src="getFileUrl(item.file)"
                                                        :alt="item.name"
                                                        class="w-full h-full object-cover"
                                                    />
                                                </div>
                                                <div class="p-2">
                                                    <h3 class="font-medium text-gray-900 text-sm mb-1 truncate">{{ item.name }}</h3>
                                                    <p class="text-xs text-gray-500 mb-1 truncate">
                                                        Material: {{ item.small_material?.name || 'N/A' }}
                                                    </p>
                                                    <div class="text-xs font-medium text-gray-900">
                                                        {{ item.price ? `€${item.price}` : 'Price not set' }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                </div>
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
import { useForm } from '@inertiajs/vue3';
import '@fortawesome/fontawesome-free/css/all.css';

export default {
    name: 'Create',
    components: {
        MainLayout,
        Header
    },

    props: {
        catalogItems: Array,
        clients: Array
    },

    data() {
        return {
            selectedItems: [],
            searchQuery: '',
            form: useForm({
                name: '',
                client_id: '',
                description: '',
                validity_days: 30,
                production_start_date: '',
                production_end_date: '',
                price1: 0,
                price2: 0,
                price3: 0,
                catalog_items: []
            }),
            activeTab: 'large',
            viewMode: 'list'
        };
    },

    computed: {
        filteredLargeMaterialItems() {
            return this.largeMaterialItems.filter(item => 
                item.name.toLowerCase().includes(this.searchQuery.toLowerCase())
            );
        },
        filteredSmallMaterialItems() {
            return this.smallMaterialItems.filter(item => 
                item.name.toLowerCase().includes(this.searchQuery.toLowerCase())
            );
        },
        largeMaterialItems() {
            return this.catalogItems.filter(item => item.large_material && !item.small_material);
        },
        smallMaterialItems() {
            return this.catalogItems.filter(item => item.small_material && !item.large_material);
        }
    },

    methods: {
        getFileUrl(file) {
            return file && file !== 'placeholder.jpeg'
                ? `/storage/uploads/${file}`
                : '/storage/uploads/placeholder.jpeg';
        },
        isPlaceholder(file) {
            return !file || file === 'placeholder.jpeg';
        },
        toggleItemSelection(item) {
            const index = this.form.catalog_items.findIndex(i => i.id === item.id);
            if (index === -1) {
                this.form.catalog_items.push({
                    id: item.id,
                    name: item.name,
                    quantity: 1,
                    description: item.description || ''
                });
                this.selectedItems.push(item.id);
            } else {
                this.form.catalog_items.splice(index, 1);
                const selectedIndex = this.selectedItems.indexOf(item.id);
                if (selectedIndex !== -1) {
                    this.selectedItems.splice(selectedIndex, 1);
                }
            }
        },
        removeItem(item) {
            const index = this.form.catalog_items.findIndex(i => i.id === item.id);
            if (index !== -1) {
                this.form.catalog_items.splice(index, 1);
                const selectedIndex = this.selectedItems.indexOf(item.id);
                if (selectedIndex !== -1) {
                    this.selectedItems.splice(selectedIndex, 1);
                }
            }
        },
        submit() {
            this.form.post(route('offers.store'));
        }
    }
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

/* New styles for the tabbed interface */
.catalog-tabs {
    @apply bg-gray-800 rounded-t-lg;
}

.tab-button {
    @apply px-4 py-2 text-sm font-medium transition-colors duration-200;
    &.active {
        @apply text-green-500 border-b-2 border-green-500;
    }
    &:not(.active) {
        @apply text-gray-400 hover:text-white;
    }
}

.catalog-items-container {
    @apply bg-white rounded-b-lg p-3 overflow-y-auto;
    min-height: 400px;
    max-height: calc(100vh - 400px);
    width: 100%;
    
    .grid {
        @apply gap-3;
        width: 100%;
    }
}

.catalog-item {
    @apply flex items-center space-x-3 p-2 hover:bg-gray-50 rounded transition-colors duration-200 cursor-pointer;
    
    &:hover {
        @apply bg-gray-50;
    }
}

.catalog-item-details {
    @apply flex-1;
}

.catalog-item-name {
    @apply font-medium text-gray-900;
}

.catalog-item-material {
    @apply text-sm text-gray-500;
}

.catalog-item-price {
    @apply text-sm text-gray-500 font-medium;
}

.empty-state {
    @apply text-center py-8 text-gray-500;
}
.button-color {
    background-color: $green;
}
/* View toggle buttons */
.view-toggle-btn {
    @apply px-2 py-1 text-sm rounded-md transition-colors duration-200;
    &.active {
        @apply button-color text-white;
    }
    &:not(.active) {
        @apply text-gray-400 hover:text-white hover:bg-gray-700;
    }
}

/* Card styles */
.catalog-card {
    @apply bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200 transition-all duration-200 cursor-pointer;
    
    &:hover {
        @apply shadow-lg transform scale-[1.02];
    }

    .catalog-card-image {
        @apply relative w-full overflow-hidden bg-gray-100;
        aspect-ratio: 4/3;
        
        img {
            @apply w-full h-full object-contain transition-transform duration-200;
            background-color: white;
        }
        
        &:hover img {
            @apply transform scale-110;
        }

        &.no-image {
            @apply flex items-center justify-center text-gray-400 text-xs;
        }
    }
}

/* Image thumbnail styles */
.thumbnail-container {
    @apply relative overflow-hidden;
    
    img {
        @apply transition-all duration-300;
    }
    
    &:hover img {
        @apply transform scale-110;
    }
}

/* Checkbox styles */
.checkbox-green {
    &:checked {
        background-color: $green;
        border-color: $green;
    }
    &:focus {
        box-shadow: 0 0 0 2px rgba($green, 0.3);
    }
}
</style>

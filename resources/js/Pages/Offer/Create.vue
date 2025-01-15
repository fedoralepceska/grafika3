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
                            <!-- Name and Description -->
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

                            <!-- Catalog Items Section -->
                            <div class="w-full">
                                <label class="text-white">Catalog Items</label>
                                <div class="catalog-tabs">
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
                                            <div v-for="item in largeMaterialItems" 
                                                 :key="item.id" 
                                                 class="catalog-item"
                                                 @click="toggleItemSelection(item)"
                                            >
                                                <input
                                                    type="checkbox"
                                                    :value="item"
                                                    v-model="form.selectedCatalogItems"
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
                                                    {{ item.price ? `$${item.price}` : 'Price not set' }}
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Card View -->
                                        <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 2xl:grid-cols-8 gap-3">
                                            <div v-for="item in largeMaterialItems" 
                                                 :key="item.id" 
                                                 class="catalog-card"
                                                 @click="toggleItemSelection(item)"
                                            >
                                                <div class="catalog-card-image">
                                                    <input
                                                        type="checkbox"
                                                        :value="item"
                                                        v-model="form.selectedCatalogItems"
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
                                                        {{ item.price ? `$${item.price}` : 'Price not set' }}
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
                                            <div v-for="item in smallMaterialItems" 
                                                 :key="item.id" 
                                                 class="catalog-item"
                                                 @click="toggleItemSelection(item)"
                                            >
                                                <input
                                                    type="checkbox"
                                                    :value="item"
                                                    v-model="form.selectedCatalogItems"
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
                                                    {{ item.price ? `$${item.price}` : 'Price not set' }}
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Card View -->
                                        <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 2xl:grid-cols-8 gap-3">
                                            <div v-for="item in smallMaterialItems" 
                                                 :key="item.id" 
                                                 class="catalog-card"
                                                 @click="toggleItemSelection(item)"
                                            >
                                                <div class="catalog-card-image">
                                                    <input
                                                        type="checkbox"
                                                        :value="item"
                                                        v-model="form.selectedCatalogItems"
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
                                                        {{ item.price ? `$${item.price}` : 'Price not set' }}
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
import { useToast } from 'vue-toastification';
import VueMultiselect from 'vue-multiselect'
import axios from 'axios';
import '@fortawesome/fontawesome-free/css/all.css';

export default {
    name: 'Create',
    components: {
        MainLayout,
        Header,
        VueMultiselect
    },
    props: {
        catalogItems: {
            type: Array,
            required: true
        }
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
            activeTab: 'large',
            viewMode: 'list'
        };
    },
    computed: {
        largeMaterialItems() {
            return this.catalogItems.filter(item => item.large_material_id && !item.small_material_id);
        },
        smallMaterialItems() {
            return this.catalogItems.filter(item => item.small_material_id && !item.large_material_id);
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
            const index = this.form.selectedCatalogItems.findIndex(i => i.id === item.id);
            if (index === -1) {
                this.form.selectedCatalogItems.push(item);
            } else {
                this.form.selectedCatalogItems.splice(index, 1);
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

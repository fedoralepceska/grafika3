<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header 
                title="Offers" 
                subtitle="All Offers" 
                icon="List.png" 
                link="offers"
                buttonText="Create New Offer"
                :showButton="true"
                buttonLink="offer.create"
            />

            <div class="dark-gray p-2 text-white">
                <div class="form-container p-2">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="sub-title">Offers Dashboard</h2>
                        <div class="flex items-center space-x-4">
                            <div class="relative">
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search offers..."
                                    class="pl-10 pr-4 py-2 rounded-md bg-gray-700 text-white placeholder-gray-400 border border-gray-600 focus:border-green-500 focus:outline-none focus:ring-1 focus:ring-green-500 w-64 transition-colors duration-200"
                                    @input="debouncedSearch"
                                />
                            </div>
                            <div class="flex items-center space-x-2">
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

                    <!-- List View -->
                    <div v-if="viewMode === 'list'" class="space-y-2">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-700 text-white">
                                    <th class="p-4">Name</th>
                                    <th class="p-4">Description</th>
                                    <th class="p-4">Price 1</th>
                                    <th class="p-4">Price 2</th>
                                    <th class="p-4">Price 3</th>
                                    <th class="p-4">Items Count</th>
                                    <th class="p-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="offer in offers" 
                                    :key="offer.id"
                                    class="bg-gray-800 text-white border-t border-gray-700"
                                >
                                    <td class="p-4">{{ offer.name }}</td>
                                    <td class="p-4">
                                        <div class="max-w-xs truncate">{{ offer.description }}</div>
                                    </td>
                                    <td class="p-4">{{ formatPrice(offer.price1) }}</td>
                                    <td class="p-4">{{ formatPrice(offer.price2) }}</td>
                                    <td class="p-4">{{ formatPrice(offer.price3) }}</td>
                                    <td class="p-4">{{ offer.catalogItems?.length || 0 }}</td>
                                    <td class="p-4 text-center">
                                        <div class="flex justify-left space-x-2">
                                            <button 
                                                @click="viewOffer(offer)"
                                                class="btn-info px-2 py-1 rounded"
                                                title="View Details"
                                            >
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button 
                                                @click="confirmDelete(offer)"
                                                class="btn-danger px-2 py-1 rounded"
                                                title="Delete Offer"
                                            >
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Card View -->
                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        <div v-for="offer in offers" 
                             :key="offer.id" 
                             class="offer-card"
                        >
                            <div class="p-4">
                                <div class="flex justify-between items-start mb-3">
                                    <h3 class="font-medium text-lg text-white">{{ offer.name }}</h3>
                                    <div class="flex space-x-2">
                                        <button 
                                            @click="viewOffer(offer)"
                                            class="btn-info px-2 py-1 rounded"
                                            title="View Details"
                                        >
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button 
                                            @click="confirmDelete(offer)"
                                            class="btn-danger px-2 py-1 rounded"
                                            title="Delete Offer"
                                        >
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="text-gray-300 text-sm mb-4 h-12 overflow-hidden">
                                    {{ offer.description }}
                                </div>
                                <div class="grid grid-cols-3 gap-2 mb-4">
                                    <div class="text-center p-2 bg-gray-700 rounded">
                                        <div class="text-xs text-gray-400">Price 1</div>
                                        <div class="text-sm font-medium">{{ formatPrice(offer.price1) }}</div>
                                    </div>
                                    <div class="text-center p-2 bg-gray-700 rounded">
                                        <div class="text-xs text-gray-400">Price 2</div>
                                        <div class="text-sm font-medium">{{ formatPrice(offer.price2) }}</div>
                                    </div>
                                    <div class="text-center p-2 bg-gray-700 rounded">
                                        <div class="text-xs text-gray-400">Price 3</div>
                                        <div class="text-sm font-medium">{{ formatPrice(offer.price3) }}</div>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between text-sm text-gray-400">
                                    <span>Items: {{ offer.catalogItems?.length || 0 }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div class="flex justify-between items-center mt-4">
                        <button
                            :disabled="!pagination.links?.prev"
                            @click="fetchOffers(pagination.current_page - 1)"
                            class="btn btn-secondary"
                        >
                            Previous
                        </button>
                        <span class="text-white">
                            Page {{ pagination.current_page }} of {{ pagination.total_pages }}
                        </span>
                        <button
                            :disabled="!pagination.links?.next"
                            @click="fetchOffers(pagination.current_page + 1)"
                            class="btn btn-secondary"
                        >
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Offer Dialog -->
        <div v-if="selectedOffer" class="modal-backdrop">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Offer Details</h2>
                    <button @click="closeViewDialog" class="close-button">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="space-y-4">
                        <div>
                            <h3 class="text-white font-medium mb-2">Basic Information</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <div class="text-gray-400">Name</div>
                                    <div class="text-white">{{ selectedOffer.name }}</div>
                                </div>
                                <div>
                                    <div class="text-gray-400">Description</div>
                                    <div class="text-white">{{ selectedOffer.description }}</div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-white font-medium mb-2">Pricing</h3>
                            <div class="grid grid-cols-3 gap-4">
                                <div class="bg-gray-700 p-3 rounded">
                                    <div class="text-gray-400 text-sm">Price 1</div>
                                    <div class="text-white font-medium">{{ formatPrice(selectedOffer.price1) }}</div>
                                </div>
                                <div class="bg-gray-700 p-3 rounded">
                                    <div class="text-gray-400 text-sm">Price 2</div>
                                    <div class="text-white font-medium">{{ formatPrice(selectedOffer.price2) }}</div>
                                </div>
                                <div class="bg-gray-700 p-3 rounded">
                                    <div class="text-gray-400 text-sm">Price 3</div>
                                    <div class="text-white font-medium">{{ formatPrice(selectedOffer.price3) }}</div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-white font-medium mb-2">Catalog Items</h3>
                            <div v-if="selectedOffer.catalogItems && selectedOffer.catalogItems.length > 0" 
                                 class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div v-for="item in selectedOffer.catalogItems" 
                                     :key="item.id"
                                     class="bg-gray-700 p-4 rounded-lg flex flex-col"
                                >
                                    <div class="space-y-2">
                                        <h4 class="text-white font-medium">{{ item.name }}</h4>
                                        <div class="text-sm text-gray-300">
                                            Price: {{ formatPrice(item.price) }}
                                        </div>
                                        <div v-if="item.large_material" class="text-sm text-gray-300">
                                            Large Material: {{ item.large_material.name }}
                                        </div>
                                        <div v-if="item.small_material" class="text-sm text-gray-300">
                                            Small Material: {{ item.small_material.name }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-gray-400 text-center py-4">
                                No catalog items associated with this offer.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="modal-backdrop">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Confirm Delete</h2>
                    <button @click="closeDeleteModal" class="close-button">&times;</button>
                </div>
                <div class="modal-body">
                    <p class="text-gray-300 mb-4">
                        Are you sure you want to delete the offer "{{ offerToDelete?.name }}"?
                    </p>
                    <div class="flex justify-end space-x-3">
                        <button 
                            @click="closeDeleteModal"
                            class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 transition-colors"
                        >
                            Cancel
                        </button>
                        <button 
                            @click="deleteOffer"
                            class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition-colors"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script>
import MainLayout from '@/Layouts/MainLayout.vue';
import Header from '@/Components/Header.vue';
import { useToast } from 'vue-toastification';
import '@fortawesome/fontawesome-free/css/all.css';

export default {
    name: 'Index',
    components: {
        MainLayout,
        Header
    },
    props: {
        offers: {
            type: Array,
            required: true
        },
        pagination: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            searchQuery: '',
            viewMode: 'list',
            selectedOffer: null,
            showDeleteModal: false,
            offerToDelete: null,
            searchTimeout: null
        };
    },
    methods: {
        formatPrice(price) {
            if (!price) return '€0.00';
            return new Intl.NumberFormat('de-DE', {
                style: 'currency',
                currency: 'EUR'
            }).format(price);
        },
        debouncedSearch() {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.fetchOffers(1); // Reset to first page when searching
            }, 300); // 300ms delay
        },
        async fetchOffers(page = 1) {
            await this.$inertia.get(route('offer.index'), {
                search: this.searchQuery,
                page: page,
                per_page: this.pagination.per_page
            }, {
                preserveState: true,
                preserveScroll: true,
                only: ['offers', 'pagination']
            });
        },
        viewOffer(offer) {
            this.selectedOffer = offer;
        },
        closeViewDialog() {
            this.selectedOffer = null;
        },
        confirmDelete(offer) {
            this.offerToDelete = offer;
            this.showDeleteModal = true;
        },
        closeDeleteModal() {
            this.showDeleteModal = false;
            this.offerToDelete = null;
        },
        async deleteOffer() {
            try {
                await axios.delete(route('offer.destroy', this.offerToDelete.id));
                const toast = useToast();
                toast.success('Offer deleted successfully');
                this.closeDeleteModal();
                this.fetchOffers(this.pagination.current_page);
            } catch (error) {
                const toast = useToast();
                toast.error('Failed to delete offer');
                console.error('Error deleting offer:', error);
            }
        }
    },
    beforeUnmount() {
        clearTimeout(this.searchTimeout); // Clean up the timeout
    }
};
</script>

<style scoped lang="scss">
.form-container {
    display: flex;
    flex-direction: column;
    gap: 20px;
    width: 100%;
}

.dark-gray {
    background-color: $dark-gray;
    min-height: 20vh;
    width: 100%;
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
    @apply px-3 py-2 rounded-md font-medium transition-colors duration-200;
    
    &.btn-info {
        @apply bg-blue-600 hover:bg-blue-700 text-white;
    }
    
    &.btn-danger {
        @apply bg-red-600 hover:bg-red-700 text-white;
    }
}

.btn-secondary {
    background-color: #4a5568;
}

.btn-info {
    background-color: $ultra-light-gray;
    color: white;
}

.btn-danger {
    background-color: #e53e3e;
    color: white;
}

/* View toggle buttons */
.view-toggle-btn {
    @apply p-2 text-sm rounded-md transition-colors duration-200;
    &.active {
        background-color: $green;
        @apply text-white;
    }
    &:not(.active) {
        @apply text-gray-400 hover:text-white hover:bg-gray-700;
    }
}

/* Card styles */
.offer-card {
    @apply bg-gray-800 rounded-lg shadow-md overflow-hidden transition-all duration-200;
    
    &:hover {
        @apply shadow-lg transform scale-[1.01];
    }
}

/* Modal styles */
.modal-backdrop {
    @apply fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4;
}

.modal-content {
    @apply bg-gray-800 rounded-lg shadow-xl w-full;
    max-width: 28rem; /* equivalent to max-w-md */
}

.modal-header {
    @apply flex justify-between items-center p-4 bg-gray-800 border-b border-gray-700;
}

.modal-header h2 {
    @apply text-xl font-bold text-white;
}

.close-button {
    @apply text-gray-400 hover:text-white text-2xl font-bold bg-transparent border-none cursor-pointer transition-colors;
}

.modal-body {
    @apply p-6;
}

/* View toggle buttons */
.view-toggle-btn {
    @apply p-2 text-sm rounded-md transition-colors duration-200;
    &.active {
        background-color: $green;
        @apply text-white;
    }
    &:not(.active) {
        @apply text-gray-400 hover:text-white hover:bg-gray-700;
    }
}

/* Table styles */
table {
    @apply w-full border-collapse;
    
    th, td {
        @apply p-4 text-left;
    }
    
    thead tr {
        @apply bg-gray-700;
    }
    
    tbody tr {
        @apply border-t border-gray-700;
        
        &:hover {
            @apply bg-gray-700;
        }
    }
}

.btn {
    @apply px-3 py-2 rounded-md font-medium transition-colors duration-200;
    
    &.btn-info {
        @apply bg-blue-600 hover:bg-blue-700 text-white;
    }
    
    &.btn-danger {
        @apply bg-red-600 hover:bg-red-700 text-white;
    }
}

/* Search input focus ring color */
input:focus {
    box-shadow: 0 0 0 2px rgba(129, 201, 80, 0.2); /* Using your $green color with opacity */
}
</style> 
<template>
    <div class="catalog-container">
        <TabsWrapperSwitch>
            <TabV2 title="Small Material">
                <div class="search-container mb-4">
                    <input
                        v-model="searchTerm"
                        type="text"
                        placeholder="Search catalog items..."
                        class="search-input w-full p-2 border rounded"
                        @input="fetchCatalogItems"
                    />
                </div>
                <div
                    v-for="item in catalogItemsSmall"
                    :key="item.id"
                    class="catalog-item flex items-center"
                >
                    <div class="item-content flex-grow flex items-center">
                        <div class="item-details flex-grow">
                            <div class="item-header flex items-center">
                                <span class="item-name mr-4">{{ item.name }}</span>
                                <button
                                    @click="openItemDetails(item)"
                                    class="info-button"
                                    title="View Item Details"
                                >
                                    <i class="fas fa-info-circle"></i>
                                </button>
                            </div>
                        </div>
                        <div class="item-select">
                            <button
                                @click="selectItem(item)"
                                class="select-circle-button"
                                :class="{ 'selected': selectedItems.includes(item.id) }"
                            >
                                <i
                                    :class="selectedItems.includes(item.id)
                                    ? 'fas fa-times'
                                    : 'fas fa-arrow-right'"
                                ></i>
                            </button>
                        </div>
                    </div>
                </div>
            </TabV2>
            <TabV2 title="Large Material">
                <div class="search-container mb-4">
                    <input
                        v-model="searchTerm"
                        type="text"
                        placeholder="Search catalog items..."
                        class="search-input w-full p-2 border rounded"
                        @input="fetchCatalogItems"
                    />
                </div>
                <div
                    v-for="item in catalogItemsLarge"
                    :key="item.id"
                    class="catalog-item flex items-center"
                >
                    <div class="item-content flex-grow flex items-center">
                        <div class="item-details flex-grow">
                            <div class="item-header flex items-center">
                                <span class="item-name mr-4">{{ item.name }}</span>
                                <button
                                    @click="openItemDetails(item)"
                                    class="info-button"
                                    title="View Item Details"
                                >
                                    <i class="fas fa-info-circle"></i>
                                </button>
                            </div>
                        </div>
                        <div class="item-select">
                            <button
                                @click="selectItem(item)"
                                class="select-circle-button"
                                :class="{ 'selected': selectedItems.includes(item.id) }"
                            >
                                <i
                                    :class="selectedItems.includes(item.id)
                                    ? 'fas fa-times'
                                    : 'fas fa-arrow-right'"
                                ></i>
                            </button>
                        </div>
                    </div>
                </div>
            </TabV2>
        </TabsWrapperSwitch>
        <div class="catalog-grid">
        </div>

        <!-- Pagination Controls -->
        <div class="pagination-container flex justify-between items-center m-2">
            <button
                @click="prevPage"
                :disabled="currentPage === 1"
                class="pagination-button"
            >
                Previous
            </button>
            <span class="page-info">
                Page {{ currentPage }} of {{ totalPages }}
            </span>
            <button
                @click="nextPage"
                :disabled="currentPage === totalPages"
                class="pagination-button"
            >
                Next
            </button>
        </div>

        <!-- Item Details Modal -->
        <div v-if="selectedItemDetails" class="modal-backdrop">
            <div class="modal">
                <div class="modal-header">
                    <h2>Details for <span style="font-weight: bolder" class="uppercase">"{{ selectedItemDetails.name }}"</span></h2>
                    <button @click="closeItemDetails" class="close-button">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="detail-section">
                        <h3>Item Specifications</h3>
                        <div class="detail-row">
                            <span class="detail-label">Machine Print:</span>
                            <span class="detail-value">{{ selectedItemDetails.machinePrint }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Machine Cut:</span>
                            <span class="detail-value">{{ selectedItemDetails.machineCut }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Large Material ID:</span>
                            <span class="detail-value">{{ selectedItemDetails.largeMaterial }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Small Material ID:</span>
                            <span class="detail-value">{{ selectedItemDetails.smallMaterial || 'N/A' }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Quantity:</span>
                            <span class="detail-value">{{ selectedItemDetails.quantity }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Copies:</span>
                            <span class="detail-value">{{ selectedItemDetails.copies }}</span>
                        </div>
                    </div>

                    <div class="detail-section" v-if="selectedItemDetails.actions && selectedItemDetails.actions.length">
                        <h3>Actions</h3>
                        <div
                            v-for="(action, index) in selectedItemDetails.actions"
                            :key="index"
                            class="action-item"
                        >
                            <span>{{ action.action_id.name }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="button-container" v-if="selectedItems.length > 0">
            <button @click="createJobs" class="create-jobs-button">
                Create Jobs ({{ selectedItems.length }})
            </button>
        </div>
    </div>
</template>

<script>
import axios from 'axios';
import { useToast } from "vue-toastification";
import TabV2 from "@/Components/tabs/TabV2.vue";
import TabsWrapperSwitch from "@/Components/tabs/TabsWrapperSwitch.vue";

export default {
    name: "CatalogSelector",
    components: { TabV2, TabsWrapperSwitch },
    data() {
        return {
            catalogItemsSmall: [],
            catalogItemsLarge: [],
            selectedItems: [],
            searchTerm: '',
            selectedItemDetails: null,
            currentPage: 1,
            itemsPerPage: 10,
            totalPages: 0
        }
    },

    methods: {
        async fetchCatalogItems() {
            try {
                const response = await axios.get('/catalog-items', {
                    params: {
                        page: this.currentPage,
                        per_page: this.itemsPerPage,
                        search: this.searchTerm
                    }
                });

                this.catalogItemsSmall = response.data.data.filter(c => c.smallMaterial !== null);
                this.catalogItemsLarge = response.data.data.filter(c => c.largeMaterial !== null);
                this.totalPages = response.data.pagination.total_pages;
            } catch (error) {
                console.error('Error fetching catalog items:', error.response?.data || error);
                const toast = useToast();
                toast.error("Failed to fetch catalog items");
            }
        },

        selectItem(item) {
            const index = this.selectedItems.indexOf(item.id);
            if (index === -1) {
                this.selectedItems.push(item.id);
            } else {
                this.selectedItems.splice(index, 1);
            }
        },

        openItemDetails(item) {
            this.selectedItemDetails = item;
        },

        closeItemDetails() {
            this.selectedItemDetails = null;
        },

        async createJobs() {
            const toast = useToast();
            // Combine both small and large catalog items
            const allCatalogItems = [...this.catalogItemsSmall, ...this.catalogItemsLarge];
            const selectedCatalogItems = allCatalogItems.filter(item =>
                this.selectedItems.includes(item.id)
            );

            try {
                const jobs = await Promise.all(selectedCatalogItems.map(async (item) => {
                    const formattedActions = item.actions.map(action => ({
                        id: action.action_id.id,
                        name: action.action_id.name,
                        status: action.status,
                        quantity: action.quantity
                    }));

                    const response = await axios.post('/jobs', {
                        fromCatalog: true,
                        machinePrint: item.machinePrint,
                        machineCut: item.machineCut,
                        large_material_id: item.largeMaterial,
                        small_material_id: item.smallMaterial,
                        name: item.name,
                        quantity: item.quantity,
                        copies: item.copies,
                        actions: formattedActions
                    });

                    return response.data.job;
                }));

                this.$emit('jobs-created', jobs);
                toast.success('Jobs created from catalog');
                this.selectedItems = [];
            } catch (error) {
                console.error('Error creating jobs:', error.response?.data || error);
                toast.error(error.response?.data?.error || 'Failed to create jobs');
            }
        },

        prevPage() {
            if (this.currentPage > 1) {
                this.currentPage--;
                this.fetchCatalogItems();
            }
        },

        nextPage() {
            if (this.currentPage < this.totalPages) {
                this.currentPage++;
                this.fetchCatalogItems();
            }
        }
    },

    mounted() {
        this.fetchCatalogItems();
    },

    watch: {
        searchTerm() {
            this.currentPage = 1;
            this.fetchCatalogItems();
        }
    }
}
</script>

<style scoped lang="scss">
$background-color: #1a2732;
$gray: #3c4e59;
$dark-gray: #2a3946;
$light-gray: #54606b;
$ultra-light-gray: #77808b;
$white: #ffffff;
$black: #000000;
$green: #408a0b;
$light-green: #81c950;
$blue: #0073a9;
$red: #9e2c30;
$orange: #a36a03;

.catalog-container {
    background-color: $light-gray;
    min-height: fit-content;
    padding: 2px;
}

.search-input {
    background-color: $dark-gray;
    border-color: $gray;
    color: $white;
}

.catalog-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 0.2rem;
}

.catalog-item {
    background-color: $dark-gray;
    border: 1px solid $gray;
    padding: 0.3rem 0.3rem 0.3rem 0.6rem;
    transition: all 0.2s ease;

}

.item-content {
    gap: 1rem;
}

.item-header {
    display: flex;
    align-items: center;
    font-weight: bold;
    color: $white;
}

.info-button {
    background: none;
    border: none;
    color: $light-gray;
    font-size: 1.2rem;
    cursor: pointer;
    transition: color 0.2s ease;

    &:hover {
        color: $green;
    }
}

.select-circle-button {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background-color: $gray;
    color: $white;
    display: flex;
    justify-content: center;
    align-items: center;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;

    &.selected {
        background-color: $red;
    }

    &:hover {
        background-color: $green;
    }

    i {
        font-size: 1rem;
    }
}

.pagination-container {
    background-color: $dark-gray;
    padding: 0.3rem;
    border-radius: 4px;
}

.pagination-button {
    background-color: $light-gray;
    color: $white;
    border: none;
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.2s ease;

    //&:disabled {
    //    background-color: $gray;
    //    cursor: not-allowed;
    //}

    &:hover:not(:disabled) {
        background-color: $light-green;
    }
}

.page-info {
    color: $white;
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
    background-color: $light-gray;
    width: 500px;
    max-height: 80%;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid $gray;
    color: $white;
}

.close-button {
    background: none;
    border: none;
    color: $white;
    font-size: 1.5rem;
    cursor: pointer;
}

.modal-body {
    padding: 1rem;
}

.detail-section {
    margin-bottom: 1.5rem;
    color: $white;
}

.detail-section h3 {
    border-bottom: 1px solid $gray;
    padding-bottom: 0.5rem;
    margin-bottom: 1rem;
    color: white;
}

.action-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    padding: 0.5rem;
    background-color: $gray;
    border-radius: 4px;
}

.button-container {
    margin-top: 1rem;
    display: flex;
    justify-content: flex-end;
}

.create-jobs-button {
    background-color: #408a0b;
    color: $white;
    border: none;
    padding: 0.4rem 1rem;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.2s ease;

}
</style>

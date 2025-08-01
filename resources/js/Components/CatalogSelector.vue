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
                                <div class="jobImg-container">
                                    <img
                                        v-if="!isPlaceholder(item.id)"
                                        :src="getImageUrl(item.id)"
                                        alt="Job Image"
                                        class="jobImg thumbnail"
                                        @click.stop="openImageModal(item.id)"
                                        style="cursor: pointer;"
                                    />
                                    <div v-else class="jobImg thumbnail no-image">
                                        NO IMAGE
                                    </div>
                                </div>
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
                                <div class="jobImg-container">
                                    <img
                                        v-if="!isPlaceholder(item.id)"
                                        :src="getImageUrl(item.id)"
                                        alt="Job Image"
                                        class="jobImg thumbnail"
                                        @click.stop="openImageModal(item.id)"
                                        style="cursor: pointer;"
                                    />
                                    <div v-else class="jobImg thumbnail no-image">
                                        NO IMAGE
                                    </div>
                                </div>
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

        <!-- Add this new modal component at the end of the template section, just before closing </div> -->
        <div v-if="showImageModal" class="image-modal-backdrop" @click="closeImageModal">
            <div class="image-modal-content" @click.stop>
                <button class="image-modal-close" @click="closeImageModal">
                    <i class="fas fa-times"></i>
                </button>
                <img 
                    :src="selectedImageUrl" 
                    alt="Full size image" 
                    class="image-modal-img"
                />
            </div>
        </div>

        <QuestionsModal
          :visible="questionsModalVisible"
          :questionsByCatalogItem="questionsModalData.questionsByCatalogItem"
          :catalogItems="questionsModalData.catalogItems"
          :isCreatingJobs="isCreatingJobs"
          @submit-answers="handleQuestionsSubmit"
          @close="questionsModalVisible = false"
        />
    </div>
</template>

<script>
import axios from 'axios';
import { useToast } from "vue-toastification";
import TabV2 from "@/Components/tabs/TabV2.vue";
import TabsWrapperSwitch from "@/Components/tabs/TabsWrapperSwitch.vue";
import QuestionsModal from './QuestionsModal.vue';

export default {
    name: "CatalogSelector",
    components: { TabV2, TabsWrapperSwitch, QuestionsModal },
    props: {
        clientId: {
            type: [Number, String],
            default: null
        }
    },
    data() {
        return {
            catalogItemsSmall: [],
            catalogItemsLarge: [],
            selectedItems: [],
            searchTerm: '',
            selectedItemDetails: null,
            currentPage: 1,
            itemsPerPage: 10,
            totalPages: 0,
            showImageModal: false,
            selectedImageUrl: null,
            questionsModalVisible: false,
            questionsModalData: { questionsByCatalogItem: {}, catalogItems: [] },
            questionsModalAnswers: null,
            isCreatingJobs: false,
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

                // Include catalog items with either individual materials or category assignments
                this.catalogItemsSmall = response.data.data.filter(c => 
                    c.smallMaterial !== null || c.small_material_category_id !== null
                );
                this.catalogItemsLarge = response.data.data.filter(c => 
                    c.largeMaterial !== null || c.large_material_category_id !== null
                );
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
            
            if (!this.clientId) {
                toast.error('Please select a client first');
                return;
            }

            // Combine both small and large catalog items, removing duplicates
            const allCatalogItems = [...this.catalogItemsSmall, ...this.catalogItemsLarge];
            const uniqueCatalogItems = allCatalogItems.filter((item, index, self) => 
                index === self.findIndex(i => i.id === item.id)
            );
            const selectedCatalogItems = uniqueCatalogItems.filter(item =>
                this.selectedItems.includes(item.id)
            );

            // Check if any selected catalog items require questions
            const catalogItemIds = selectedCatalogItems.map(item => item.id);
            try {
                const questionsResponse = await axios.post('/jobs/questions-for-catalog-items', {
                    catalog_item_ids: catalogItemIds
                });
                if (questionsResponse.data.shouldAsk) {
                    // Show questions modal
                    this.questionsModalData = {
                        questionsByCatalogItem: questionsResponse.data.questionsByCatalogItem,
                        catalogItems: selectedCatalogItems
                    };
                    this.questionsModalVisible = true;
                    return;
                }
            } catch (error) {
                toast.error('Failed to check questions requirement');
                return;
            }

            try {
                const jobs = await Promise.all(selectedCatalogItems.map(async (item) => {
                    const formattedActions = item.actions.map(action => ({
                        id: action.action_id.id,
                        name: action.action_id.name,
                        status: action.status,
                        quantity: action.quantity
                    }));

                    // Handle material vs category assignments  
                    let largeMaterialData = item.largeMaterial;
                    let smallMaterialData = item.smallMaterial;
                    
                    // If item has category assignments, send those instead
                    if (item.large_material_category_id) {
                        largeMaterialData = 'cat_' + item.large_material_category_id;
                    }
                    if (item.small_material_category_id) {
                        smallMaterialData = 'cat_' + item.small_material_category_id;
                    }

                    const response = await axios.post('/jobs', {
                        fromCatalog: true,
                        machinePrint: item.machinePrint,
                        machineCut: item.machineCut,
                        large_material_id: largeMaterialData,
                        small_material_id: smallMaterialData,
                        name: item.name,
                        quantity: item.quantity || 1, // Default to 1 if not set
                        copies: item.copies || 1, // Default to 1 if not set
                        actions: formattedActions,
                        client_id: this.clientId,
                        catalog_item_id: item.id
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

        async handleQuestionsSubmit(answers) {
            // answers is now { [catalogItemId]: { [questionId]: answerText } }
            this.questionsModalVisible = false;
            this.questionsModalAnswers = answers;
            const toast = useToast();
            const allCatalogItems = [...this.catalogItemsSmall, ...this.catalogItemsLarge];
            const uniqueCatalogItems = allCatalogItems.filter((item, index, self) => 
                index === self.findIndex(i => i.id === item.id)
            );
            const selectedCatalogItems = uniqueCatalogItems.filter(item =>
                this.selectedItems.includes(item.id)
            );
            this.isCreatingJobs = true;
            try {
                const jobs = await Promise.all(selectedCatalogItems.map(async (item) => {
                    const formattedActions = item.actions.map(action => ({
                        id: action.action_id.id,
                        name: action.action_id.name,
                        status: action.status,
                        quantity: action.quantity
                    }));
                    // Handle material vs category assignments  
                    let largeMaterialData = item.largeMaterial;
                    let smallMaterialData = item.smallMaterial;
                    
                    // If item has category assignments, send those instead
                    if (item.large_material_category_id) {
                        largeMaterialData = 'cat_' + item.large_material_category_id;
                    }
                    if (item.small_material_category_id) {
                        smallMaterialData = 'cat_' + item.small_material_category_id;
                    }

                    const payload = {
                        fromCatalog: true,
                        machinePrint: item.machinePrint,
                        machineCut: item.machineCut,
                        large_material_id: largeMaterialData,
                        small_material_id: smallMaterialData,
                        name: item.name,
                        quantity: item.quantity || 1,
                        copies: item.copies || 1,
                        actions: formattedActions,
                        client_id: this.clientId,
                        catalog_item_id: item.id
                    };
                    if (this.questionsModalAnswers && this.questionsModalAnswers[item.id]) {
                        payload.question_answers = this.questionsModalAnswers[item.id];
                    }
                    const response = await axios.post('/jobs', payload);
                    return response.data.job;
                }));
                this.$emit('jobs-created', jobs);
                toast.success('Jobs created from catalog');
                this.selectedItems = [];
            } catch (error) {
                console.error('Error creating jobs:', error.response?.data || error);
                toast.error(error.response?.data?.error || 'Failed to create jobs');
            } finally {
                this.isCreatingJobs = false;
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
        },
        getImageUrl(id) {
            const items = [...this.catalogItemsSmall, ...this.catalogItemsLarge];
            const catalog = items.find(j => j.id === id);

            return catalog && catalog.file !== 'placeholder.jpeg'
                ? `/storage/uploads/${catalog.file}`
                : '/storage/uploads/placeholder.jpeg';
        },

        isPlaceholder(id) {
            const items = [...this.catalogItemsSmall, ...this.catalogItemsLarge];
            const catalog = items.find(j => j.id === id);
            return !catalog?.file || catalog.file === 'placeholder.jpeg';
        },

        openImageModal(itemId) {
            this.selectedImageUrl = this.getImageUrl(itemId);
            this.showImageModal = true;
            // Prevent body scrolling when modal is open
            document.body.style.overflow = 'hidden';
        },

        closeImageModal() {
            this.showImageModal = false;
            this.selectedImageUrl = null;
            // Restore body scrolling
            document.body.style.overflow = 'auto';
        },
    },

    mounted() {
        this.fetchCatalogItems();
    },

    watch: {
        searchTerm() {
            this.currentPage = 1;
            this.fetchCatalogItems();
        }
    },

    // Clean up when component is destroyed
    beforeUnmount() {
        // Ensure body scrolling is restored when component is destroyed
        document.body.style.overflow = 'auto';
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
.jobImg-container {
    margin: 0 1rem;
}

.jobImg {
    width: 60px;
    height: 60px;
    display: flex;
    object-fit: cover;
    border-radius: 4px;
    transition: all 0.3s ease;
    position: relative;
    z-index: 1;
}

.no-image {
    background-color: $dark-gray;
    border: 1px dashed $gray;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    color: $ultra-light-gray;
    text-align: center;
}

.thumbnail {
    &:hover {
        transform: scale(3);
        box-shadow: 0 0 10px rgba(0,0,0,0.5);
        position: relative;
        z-index: 1000;
    }
}

.image-modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.8);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.image-modal-content {
    position: relative;
    max-width: 90vw;
    max-height: 90vh;
    background-color: $dark-gray;
    padding: 1rem;
    border-radius: 8px;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
}

.image-modal-close {
    position: absolute;
    top: -1.5rem;
    right: -1.5rem;
    background-color: $red;
    color: white;
    border: none;
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background-color 0.2s ease;

    &:hover {
        background-color: darken($red, 10%);
    }
}

.image-modal-img {
    max-width: 100%;
    max-height: calc(90vh - 2rem);
    object-fit: contain;
}
</style>

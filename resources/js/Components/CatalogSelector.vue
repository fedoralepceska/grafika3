<template>
    <div class="catalog-container">
        <div class="search-container mb-4">
            <input
                v-model="searchTerm"
                type="text"
                placeholder="Search catalog items..."
                class="search-input w-full p-2 border rounded"
            />
        </div>

        <div class="catalog-grid">
            <div
                v-for="item in filteredItems"
                :key="item.id"
                class="catalog-item"
            >
                <div class="item-content">
                    <div class="item-header">
                        <span>{{ item.name }}</span>
                        <button
                            @click="openItemDetails(item)"
                            class="info-button"
                            title="View Item Details"
                        >
                            <i class="fas fa-info-circle"></i>
                        </button>
                    </div>
                    <div class="item-actions">
                        <button
                            @click="selectItem(item)"
                            :class="['select-button', { 'selected': selectedItems.includes(item.id) }]"
                        >
                            {{ selectedItems.includes(item.id) ? 'Deselect' : 'Select' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Item Details Modal -->
        <div v-if="selectedItemDetails" class="modal-backdrop">
            <div class="modal">
                <div class="modal-header">
                    <h2>{{ selectedItemDetails.name }} Details</h2>
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
                        <h3>Associated Actions</h3>
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
                Create Jobs ({{ selectedItems.length }} selected)
            </button>
        </div>
    </div>
</template>

<script>
import axios from 'axios';
import { useToast } from "vue-toastification";

export default {
    name: "CatalogSelector",
    data() {
        return {
            catalogItems: [],
            selectedItems: [],
            searchTerm: '',
            selectedItemDetails: null
        }
    },

    computed: {
        filteredItems() {
            if (!this.searchTerm) return this.catalogItems;

            return this.catalogItems.filter(item =>
                item.name.toLowerCase().includes(this.searchTerm.toLowerCase())
            );
        }
    },

    async mounted() {
        await this.fetchCatalogItems();
    },

    methods: {
        async fetchCatalogItems() {
            try {
                const response = await axios.get('/catalog-items');
                this.catalogItems = response.data;
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
            const selectedCatalogItems = this.catalogItems.filter(item =>
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
                toast.error('Failed to create jobs from catalog');
            }
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
    padding: 1rem;

}

.search-input {
    background-color: $dark-gray;
    border-color: $gray;
    color: $white;
}

.catalog-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1rem;
}

.catalog-item {
    background-color: $dark-gray;
    border: 1px solid $gray;

    transition: all 0.2s ease;

    &:hover {
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
}

.item-content {
    padding: 1rem;
    display: flex;
    flex-direction: column;
    height: 100%;
}

.item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    font-weight: bold;
    color: $white;
}

.info-button {
    background: none;
    border: none;
    color: $blue;
    font-size: 1.2rem;
    cursor: pointer;
    transition: color 0.2s ease;

    &:hover {
        color: $light-green;
    }
}

.item-details {
    flex-grow: 1;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    color: white;
}

.detail-label {
    font-weight: 500;
}

.item-actions {
    margin-top: 1rem;
}

.select-button {
    width: 100%;
    padding: 0.5rem;
    background-color: $gray;
    color: $white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.2s ease;

    &.selected {
        background-color: $green;
    }

    &:hover {
        background-color: $light-green;
    }
}

.button-container {
    margin-top: 1rem;
    display: flex;
    justify-content: flex-end;
}

.create-jobs-button {
    background-color: $blue;
    color: $white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.2s ease;

    &:hover {
        background-color: $light-green;
    }
}

// Modal Styles
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

.action-status {
    color:white;
}
</style>

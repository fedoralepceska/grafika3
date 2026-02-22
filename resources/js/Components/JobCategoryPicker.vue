<template>
    <div v-if="visible" class="modal-backdrop">
        <div class="modal">
            <div class="modal-header">
                <h2>Select Materials from Categories</h2>
                <button @click="closeModal" class="close-button">&times;</button>
            </div>
            <div class="modal-body">
                <div class="category-selections">
                    <div 
                        v-for="(categoryData, catalogItemId) in categoriesData" 
                        :key="catalogItemId"
                        class="catalog-item-section"
                    >
                        <h3 class="catalog-item-title">{{ categoryData.catalogItemName }}</h3>
                        
                        <div 
                            v-for="categoryInfo in categoryData.categories" 
                            :key="categoryInfo.categoryId"
                            class="category-section"
                        >
                            <h4 class="category-title">
                                {{ categoryInfo.categoryName }} 
                                <span class="material-type">({{ categoryInfo.materialType }})</span>
                            </h4>
                            
                            <div class="articles-grid">
                                <div 
                                    v-for="article in categoryInfo.articles" 
                                    :key="article.id"
                                    class="article-card"
                                    :class="{ 
                                        'selected': isArticleSelected(catalogItemId, categoryInfo.categoryId, article.id),
                                        'low-stock': article.current_stock < categoryInfo.requiredQuantity,
                                        'no-stock': article.current_stock === 0
                                    }"
                                    @click="selectArticle(catalogItemId, categoryInfo.categoryId, article)"
                                >
                                    <div class="article-header">
                                        <span class="article-name">{{ article.name }}</span>
                                        <div class="selection-indicator">
                                            <i v-if="isArticleSelected(catalogItemId, categoryInfo.categoryId, article.id)" 
                                               class="fas fa-check-circle"></i>
                                        </div>
                                    </div>
                                    
                                    <div class="article-details">
                                        <div class="stock-info">
                                            <span class="stock-label">Stock:</span>
                                            <span class="stock-value" :class="{
                                                'low-stock-text': article.current_stock < categoryInfo.requiredQuantity,
                                                'no-stock-text': article.current_stock === 0
                                            }">
                                                {{ article.current_stock }} {{ article.unit || 'units' }}
                                            </span>
                                        </div>
                                        
                                        <div class="required-info">
                                            <span class="required-label">Required:</span>
                                            <span class="required-value">{{ categoryInfo.requiredQuantity }} {{ article.unit || 'units' }}</span>
                                        </div>
                                    </div>
                                    
                                    <div v-if="article.current_stock === 0" class="stock-warning">
                                        Out of Stock
                                    </div>
                                    <div v-else-if="article.current_stock < categoryInfo.requiredQuantity" class="stock-warning">
                                        Insufficient Stock
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button @click="closeModal" class="cancel-button">Cancel</button>
                <button 
                    @click="confirmSelection" 
                    class="confirm-button"
                    :disabled="!allCategoriesSelected || isProcessing"
                >
                    {{ isProcessing ? 'Creating Jobs...' : 'Create Jobs' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';
import { useToast } from "vue-toastification";

export default {
    name: "JobCategoryPicker",
    props: {
        visible: {
            type: Boolean,
            default: false
        },
        categoriesData: {
            type: Object,
            default: () => ({})
        },
        clientId: {
            type: [Number, String],
            required: true
        }
    },
    data() {
        return {
            selectedArticles: {}, // { catalogItemId: { categoryId: articleId } }
            isProcessing: false
        }
    },
    computed: {
        allCategoriesSelected() {
            for (const catalogItemId in this.categoriesData) {
                const categoryData = this.categoriesData[catalogItemId];
                for (const categoryInfo of categoryData.categories) {
                    const key = `${catalogItemId}_${categoryInfo.categoryId}`;
                    if (!this.selectedArticles[key]) {
                        return false;
                    }
                }
            }
            return true;
        }
    },
    methods: {
        selectArticle(catalogItemId, categoryId, article) {
            // Prevent selection of out-of-stock articles
            if (article.current_stock === 0) {
                return;
            }
            
            const key = `${catalogItemId}_${categoryId}`;
            
            // Create a new object to ensure reactivity
            const newSelections = { ...this.selectedArticles };
            newSelections[key] = {
                articleId: article.id,
                article: article
            };
            this.selectedArticles = newSelections;
        },
        
        isArticleSelected(catalogItemId, categoryId, articleId) {
            const key = `${catalogItemId}_${categoryId}`;
            return this.selectedArticles[key]?.articleId === articleId;
        },
        
        async confirmSelection() {
            if (!this.allCategoriesSelected || this.isProcessing) return;
            
            this.isProcessing = true;
            
            try {
                // Build the article selections for all catalog items
                const categorySelections = {};
                
                for (const catalogItemId in this.categoriesData) {
                    const categoryData = this.categoriesData[catalogItemId];
                    
                    for (const categoryInfo of categoryData.categories) {
                        const key = `${catalogItemId}_${categoryInfo.categoryId}`;
                        const selection = this.selectedArticles[key];
                        if (selection) {
                            categorySelections[categoryInfo.categoryId] = selection.articleId;
                        }
                    }
                }
                
                // Emit the selections back to parent instead of creating jobs here
                this.$emit('selection-complete', categorySelections);
                
            } catch (error) {
                console.error('Error processing category selections:', error);
                const toast = useToast();
                toast.error('Failed to process category selections');
            } finally {
                this.isProcessing = false;
            }
        },
        
        closeModal() {
            this.selectedArticles = {};
            this.isProcessing = false;
            this.$emit('close');
        }
    },
    watch: {
        visible(newVal) {
            if (newVal) {
                // Auto-select the first available article for each category when modal opens
                const initialSelections = {};
                for (const catalogItemId in this.categoriesData) {
                    const categoryData = this.categoriesData[catalogItemId];
                    for (const categoryInfo of categoryData.categories) {
                        const availableArticle = categoryInfo.articles.find(article => 
                            article.current_stock >= categoryInfo.requiredQuantity
                        ) || categoryInfo.articles[0]; // Fallback to first article if none have sufficient stock
                        
                        if (availableArticle) {
                            const key = `${catalogItemId}_${categoryInfo.categoryId}`;
                            initialSelections[key] = {
                                articleId: availableArticle.id,
                                article: availableArticle
                            };
                        }
                    }
                }
                // Set all selections at once to trigger reactivity
                this.selectedArticles = initialSelections;
            } else {
                // Reset selections when modal closes
                this.selectedArticles = {};
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
$yellow: #f1c40f;

.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.modal {
    background-color: $light-gray;
    width: 90%;
    max-width: 1000px;
    max-height: 90%;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid $gray;
    color: $white;
    
    h2 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: bold;
    }
}

.close-button {
    background: none;
    border: none;
    color: $white;
    font-size: 2rem;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    
    &:hover {
        color: $red;
    }
}

.modal-body {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
}

.catalog-item-section {
    margin-bottom: 2rem;
    
    &:last-child {
        margin-bottom: 0;
    }
}

.catalog-item-title {
    color: $white;
    font-size: 1.3rem;
    font-weight: bold;
    margin: 0 0 1rem 0;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid $green;
}

.category-section {
    margin-bottom: 1.5rem;
    
    &:last-child {
        margin-bottom: 0;
    }
}

.category-title {
    color: $white;
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0 0 1rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.material-type {
    color: $ultra-light-gray;
    font-size: 0.9rem;
    font-weight: normal;
}

.articles-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1rem;
}

.article-card {
    background-color: $dark-gray;
    border: 2px solid $gray;
    border-radius: 8px;
    padding: 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
    
    &:hover {
        border-color: $light-green;
        transform: translateY(-2px);
    }
    
    &.selected {
        border-color: $green;
        background-color: rgba(64, 138, 11, 0.1);
    }
    
    &.low-stock {
        border-color: $orange;
        background-color: rgba(163, 106, 3, 0.1);
    }
    
    &.no-stock {
        border-color: $red;
        background-color: rgba(158, 44, 48, 0.1);
        opacity: 0.7;
        cursor: not-allowed;
        
        &:hover {
            border-color: $red;
            transform: none;
        }
    }
}

.article-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.article-name {
    color: $white;
    font-weight: 600;
    font-size: 1rem;
}

.selection-indicator {
    color: $green;
    font-size: 1.2rem;
}

.article-details {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.stock-info, .required-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.stock-label, .required-label {
    color: $ultra-light-gray;
    font-size: 0.9rem;
}

.stock-value, .required-value {
    color: $white;
    font-weight: 500;
    font-size: 0.9rem;
}

.low-stock-text {
    color: $orange !important;
}

.no-stock-text {
    color: $red !important;
}

.stock-warning {
    margin-top: 0.5rem;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    text-align: center;
    font-size: 0.8rem;
    font-weight: 600;
    background-color: $red;
    color: $white;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    padding: 1.5rem;
    border-top: 1px solid $gray;
}

.cancel-button, .confirm-button {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 4px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.cancel-button {
    background-color: $gray;
    color: $white;
    
    &:hover {
        background-color: $ultra-light-gray;
    }
}

.confirm-button {
    background-color: $green;
    color: $white;
    
    &:hover:not(:disabled) {
        background-color: $light-green;
    }
    
    &:disabled {
        background-color: $gray;
        cursor: not-allowed;
        opacity: 0.6;
    }
}
</style>
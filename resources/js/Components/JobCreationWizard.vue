<template>
    <div v-if="visible" class="modal-backdrop">
        <div class="modal">
            <div class="modal-header">
                <div class="header-content">
                    <h2>Job Creation</h2>
                    <p class="header-subtitle">Configure materials and production requirements</p>
                </div>
                <button @click="closeWizard" class="close-button">&times;</button>
            </div>
            
            <!-- Loading State -->
            <div v-if="isLoading" class="loading-container">
                <div class="spinner"></div>
                <p>Loading configuration data...</p>
            </div>
            
            <!-- Progress Indicator -->
            <div v-else-if="hasCategories || hasQuestions" class="progress-bar">
                <div class="progress-steps">
                    <div 
                        v-if="hasCategories"
                        class="progress-step"
                        :class="{ 
                            'active': currentStep >= 1, 
                            'completed': currentStep > 1 
                        }"
                    >
                        <div class="step-indicator">
                            <div class="step-number">
                                <i v-if="currentStep > 1" class="fas fa-check"></i>
                                <span v-else>1</span>
                            </div>
                            <div class="step-info">
                                <div class="step-label">Material Selection</div>
                                <div class="step-description">Choose materials from inventory</div>
                            </div>
                        </div>
                    </div>
                    <div v-if="hasCategories && hasQuestions" class="progress-connector" :class="{ 'completed': currentStep > 1 }"></div>
                    <div 
                        v-if="hasQuestions"
                        class="progress-step"
                        :class="{ 
                            'active': currentStep >= (hasCategories ? 2 : 1), 
                            'completed': currentStep > (hasCategories ? 2 : 1) 
                        }"
                    >
                        <div class="step-indicator">
                            <div class="step-number">
                                <span>{{ hasCategories ? 2 : 1 }}</span>
                            </div>
                            <div class="step-info">
                                <div class="step-label">Production Requirements</div>
                                <div class="step-description">Specify job requirements</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div v-if="!isLoading" class="modal-body">
                <!-- Step 1: Material Selection -->
                <div v-if="currentStep === 1" class="wizard-step">
                    <div class="step-content">
                        <div class="category-selections">
                            <div 
                                v-for="catalogItem in catalogItems" 
                                :key="catalogItem.id"
                                class="catalog-item-section"
                            >
                                <!-- Show categories if this catalog item has them -->
                                <div v-if="categoriesData[catalogItem.id]">
                                    <div 
                                        v-for="categoryInfo in categoriesData[catalogItem.id].categories" 
                                        :key="categoryInfo.categoryId"
                                        class="category-section"
                                    >
                                        <div class="category-header">
                                            <div class="category-title-group">
                                                <h4 class="category-title">{{ categoryInfo.categoryName }}</h4>
                                                <span class="category-meta">
                                                    <span class="catalog-name">{{ catalogItem.name }}</span>
                                                    <span class="separator">•</span>
                                                    <span class="material-type">{{ categoryInfo.materialType }}</span>
                                                </span>
                                            </div>
                                            <div class="required-badge">
                                                Required: {{ categoryInfo.requiredQuantity }} {{ categoryInfo.articles[0]?.unit || 'units' }}
                                            </div>
                                        </div>
                                        
                                        <div class="articles-table">
                                            <div class="table-header">
                                                <div class="col-select"></div>
                                                <div class="col-name">Material Name</div>
                                                <div class="col-stock">Available Stock</div>
                                                <div class="col-status">Status</div>
                                            </div>
                                            <div 
                                                v-for="article in categoryInfo.articles" 
                                                :key="article.id"
                                                class="table-row"
                                                :class="{ 
                                                    'selected': isArticleSelected(catalogItem.id, categoryInfo.categoryId, article.id),
                                                    'insufficient': article.current_stock < categoryInfo.requiredQuantity && article.current_stock > 0,
                                                    'unavailable': article.current_stock === 0
                                                }"
                                                @click="selectArticle(catalogItem.id, categoryInfo.categoryId, article)"
                                            >
                                                <div class="col-select">
                                                    <div class="selection-indicator">
                                                        <i v-if="isArticleSelected(catalogItem.id, categoryInfo.categoryId, article.id)" 
                                                           class="fas fa-check-circle"></i>
                                                        <i v-else class="far fa-circle"></i>
                                                    </div>
                                                </div>
                                                <div class="col-name">
                                                    <span class="article-name">{{ article.name }}</span>
                                                </div>
                                                <div class="col-stock">
                                                    <span class="stock-value" :class="{
                                                        'stock-low': article.current_stock < categoryInfo.requiredQuantity && article.current_stock > 0,
                                                        'stock-none': article.current_stock === 0
                                                    }">
                                                        {{ article.current_stock }} {{ article.unit || 'units' }}
                                                    </span>
                                                </div>
                                                <div class="col-status">
                                                    <span v-if="article.current_stock === 0" class="status-badge unavailable">
                                                        Out of Stock
                                                    </span>
                                                    <span v-else-if="article.current_stock < categoryInfo.requiredQuantity" class="status-badge insufficient">
                                                        Insufficient
                                                    </span>
                                                    <span v-else class="status-badge available">
                                                        Available
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Show message if catalog item has no categories -->
                                <div v-else class="no-categories-message">
                                    <i class="fas fa-info-circle"></i>
                                    <div>
                                        <h5>{{ catalogItem.name }}</h5>
                                        <p>No material selection required for this catalog item.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Step 2: Questions (or Step 1 if no categories) -->
                <div v-if="(currentStep === 2 && hasCategories) || (currentStep === 1 && !hasCategories && hasQuestions)" class="wizard-step">
                    <div class="step-content">
                        <div class="questions-container">
                            <div 
                                v-for="catalogItem in questionsData.catalogItems" 
                                :key="catalogItem.id"
                                class="catalog-questions-section"
                            >
                                <div class="section-header">
                                    <h4 class="section-title">{{ catalogItem.name }}</h4>
                                    <span class="question-count">
                                        {{ questionsData.questionsByCatalogItem[catalogItem.id]?.length || 0 }} 
                                        {{ (questionsData.questionsByCatalogItem[catalogItem.id]?.length || 0) === 1 ? 'requirement' : 'requirements' }}
                                    </span>
                                </div>
                                
                                <div v-if="questionsData.questionsByCatalogItem[catalogItem.id] && questionsData.questionsByCatalogItem[catalogItem.id].length" class="questions-list">
                                    <div 
                                        v-for="question in questionsData.questionsByCatalogItem[catalogItem.id]"
                                        :key="question.id"
                                        class="question-item"
                                    >
                                        <label class="question-label">
                                            <input 
                                                type="checkbox" 
                                                v-model="questionAnswers[catalogItem.id][question.id]" 
                                                class="question-checkbox" 
                                            />
                                            <span class="question-text">{{ question.question }}</span>
                                        </label>
                                    </div>
                                </div>
                                <div v-else class="no-questions-message">
                                    <i class="fas fa-check-circle"></i>
                                    <span>No additional requirements for this item.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div v-if="!isLoading" class="modal-footer">
                <button 
                    v-if="currentStep > 1" 
                    @click="previousStep" 
                    class="back-button"
                    :disabled="isCreatingJobs"
                >
                    Back
                </button>
                
                <button @click="closeWizard" class="cancel-button" :disabled="isCreatingJobs">
                    Cancel
                </button>
                
                <button 
                    @click="nextStep" 
                    class="next-button"
                    :disabled="!canProceed || isCreatingJobs"
                >
                    {{ getNextButtonText() }}
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';
import { useToast } from "vue-toastification";

export default {
    name: "JobCreationWizard",
    props: {
        visible: {
            type: Boolean,
            default: false
        },
        catalogItems: {
            type: Array,
            default: () => []
        },
        clientId: {
            type: [Number, String],
            required: true
        }
    },
    data() {
        return {
            currentStep: 1,
            categoriesData: {},
            questionsData: { questionsByCatalogItem: {}, catalogItems: [] },
            selectedArticles: {},
            questionAnswers: {},
            hasCategories: false,
            hasQuestions: false,
            isCreatingJobs: false,
            isLoading: false
        }
    },
    computed: {
        canProceed() {
            if (this.currentStep === 1) {
                // Check if all categories have selections
                return this.allCategoriesSelected;
            } else if (this.currentStep === 2) {
                // Check if required questions are answered
                return this.allRequiredQuestionsAnswered;
            } else if (this.currentStep === 3) {
                return true;
            }
            return false;
        },
        
        allCategoriesSelected() {
            if (!this.hasCategories) return true;
            
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
        },
        
        allRequiredQuestionsAnswered() {
            if (!this.hasQuestions) return true;
            
            // For now, we'll assume all questions are optional
            // You can add required field checking here if needed
            return true;
        }
    },
    methods: {
        async initializeWizard() {
            if (!this.catalogItems.length) return;
            
            this.isLoading = true;
            
            try {
                const catalogItemIds = this.catalogItems.map(item => item.id);
                
                // Single unified API call to get all wizard data
                const response = await axios.post('/jobs/wizard-data-for-catalog-items', {
                    catalog_item_ids: catalogItemIds,
                    quantity: 1,
                    copies: 1
                });
                
                // Set all data from single response
                this.hasCategories = response.data.hasCategories;
                this.categoriesData = response.data.categoriesData;
                this.hasQuestions = response.data.hasQuestions;
                this.questionsData = {
                    questionsByCatalogItem: response.data.questionsByCatalogItem,
                    catalogItems: response.data.catalogItems
                };
                
                // Initialize selections
                if (this.hasCategories) {
                    this.initializeCategorySelections();
                }
                
                if (this.hasQuestions) {
                    this.initializeQuestionAnswers();
                }
                
                // Set initial step based on what we have
                if (this.hasCategories) {
                    this.currentStep = 1;
                } else if (this.hasQuestions) {
                    this.currentStep = 1; // Questions become step 1 when no categories
                } else {
                    // No categories or questions, create jobs directly
                    this.createJobs();
                    return;
                }
                
            } catch (error) {
                console.error('Error loading wizard data:', error);
                const toast = useToast();
                toast.error('Failed to load wizard data');
                this.closeWizard();
            } finally {
                this.isLoading = false;
            }
        },
        
        initializeCategorySelections() {
            const initialSelections = {};
            for (const catalogItemId in this.categoriesData) {
                const categoryData = this.categoriesData[catalogItemId];
                for (const categoryInfo of categoryData.categories) {
                    const availableArticle = categoryInfo.articles.find(article => 
                        article.current_stock >= categoryInfo.requiredQuantity
                    ) || categoryInfo.articles[0];
                    
                    if (availableArticle) {
                        const key = `${catalogItemId}_${categoryInfo.categoryId}`;
                        initialSelections[key] = {
                            articleId: availableArticle.id,
                            article: availableArticle
                        };
                    }
                }
            }
            this.selectedArticles = initialSelections;
        },
        
        initializeQuestionAnswers() {
            const answers = {};
            for (const catalogItemId in this.questionsData.questionsByCatalogItem) {
                answers[catalogItemId] = {};
                const questions = this.questionsData.questionsByCatalogItem[catalogItemId];
                questions.forEach(question => {
                    answers[catalogItemId][question.id] = false; // Checkbox default to false
                });
            }
            this.questionAnswers = answers;
        },
        
        selectArticle(catalogItemId, categoryId, article) {
            if (article.current_stock === 0) return;
            
            console.log('Selecting article:', { catalogItemId, categoryId, articleId: article.id });
            
            const key = `${catalogItemId}_${categoryId}`;
            const newSelections = { ...this.selectedArticles };
            newSelections[key] = {
                articleId: article.id,
                article: article
            };
            this.selectedArticles = newSelections;
            
            console.log('Updated selections:', this.selectedArticles);
        },
        
        isArticleSelected(catalogItemId, categoryId, articleId) {
            const key = `${catalogItemId}_${categoryId}`;
            return this.selectedArticles[key]?.articleId === articleId;
        },
        
        nextStep() {
            if (this.currentStep === 1 && this.hasCategories) {
                // Move to questions if they exist, otherwise create jobs
                if (this.hasQuestions) {
                    this.currentStep = 2;
                } else {
                    this.createJobs();
                }
            } else if ((this.currentStep === 2 && this.hasCategories && this.hasQuestions) || 
                       (this.currentStep === 1 && !this.hasCategories && this.hasQuestions)) {
                // Create jobs (we're on the questions step)
                this.createJobs();
            }
        },
        
        previousStep() {
            if (this.currentStep === 2 && this.hasCategories) {
                this.currentStep = 1;
            }
            // If we're on step 1 (questions only), we can't go back
        },
        
        getNextButtonText() {
            // If we're on the last step (either step 1 with questions only, or step 2 with both)
            const isLastStep = (this.currentStep === 1 && !this.hasCategories && this.hasQuestions) ||
                              (this.currentStep === 1 && this.hasCategories && !this.hasQuestions) ||
                              (this.currentStep === 2);
            
            if (isLastStep) {
                return this.isCreatingJobs ? 'Creating...' : 'Create Jobs';
            }
            return 'Next';
        },
        
        async createJobs() {
            this.isCreatingJobs = true;
            const toast = useToast();
            
            try {
                const jobs = await Promise.all(this.catalogItems.map(async (item) => {
                    const formattedActions = item.actions?.map(action => ({
                        id: action.action_id?.id || action.id,
                        name: action.action_id?.name || action.name,
                        status: action.status || 'Not started yet',
                        quantity: action.quantity || 1
                    })) || [];

                    const payload = {
                        fromCatalog: true,
                        machinePrint: item.machinePrint,
                        machineCut: item.machineCut,
                        large_material_id: item.large_material_id,
                        small_material_id: item.small_material_id,
                        name: item.name,
                        quantity: item.quantity || 1,
                        copies: item.copies || 1,
                        actions: formattedActions,
                        client_id: this.clientId,
                        catalog_item_id: item.id
                    };

                    // Add category selections
                    if (this.hasCategories) {
                        const categorySelections = {};
                        for (const catalogItemId in this.categoriesData) {
                            if (catalogItemId == item.id) {
                                const categoryData = this.categoriesData[catalogItemId];
                                for (const categoryInfo of categoryData.categories) {
                                    const key = `${catalogItemId}_${categoryInfo.categoryId}`;
                                    const selection = this.selectedArticles[key];
                                    if (selection) {
                                        categorySelections[categoryInfo.categoryId] = selection.articleId;
                                    }
                                }
                            }
                        }
                        payload.category_article_selections = categorySelections;
                    }

                    // Add question answers (only checked ones)
                    if (this.hasQuestions && this.questionAnswers[item.id]) {
                        const result = {};
                        const questions = this.questionsData.questionsByCatalogItem[item.id] || [];
                        for (const q of questions) {
                            if (this.questionAnswers[item.id][q.id]) {
                                result[q.id] = {
                                    question: q.question,
                                    answer: true // Just mark that the question was answered
                                };
                            }
                        }
                        // Only add if there are checked answers
                        if (Object.keys(result).length > 0) {
                            payload.question_answers = result;
                        }
                    }

                    const response = await axios.post('/jobs', payload);
                    return response.data.job;
                }));

                this.$emit('jobs-created', jobs);
                toast.success(`${jobs.length} job(s) created successfully`);
                this.closeWizard();
                
            } catch (error) {
                console.error('Error creating jobs:', error);
                toast.error(error.response?.data?.message || 'Failed to create jobs');
            } finally {
                this.isCreatingJobs = false;
            }
        },
        
        closeWizard() {
            this.currentStep = 1;
            this.selectedArticles = {};
            this.questionAnswers = {};
            this.isCreatingJobs = false;
            this.$emit('close');
        }
    },
    watch: {
        visible(newVal) {
            if (newVal) {
                this.initializeWizard();
            }
        }
    }
}
</script>

<style scoped lang="scss">
// Using system colors
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

.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.6);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.modal {
    background-color: $light-gray;
    width: 95%;
    max-width: 1400px;
    max-height: 90vh;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem 2rem;
    border-bottom: 2px solid $gray;
    background-color: $dark-gray;
    
    .header-content {
        h2 {
            margin: 0 0 0.25rem 0;
            font-size: 1.5rem;
            font-weight: 600;
            color: $white;
        }
        
        .header-subtitle {
            margin: 0;
            font-size: 0.875rem;
            color: $ultra-light-gray;
            font-weight: 400;
        }
    }
}

.close-button {
    background: none;
    border: none;
    color: $ultra-light-gray;
    font-size: 1.75rem;
    cursor: pointer;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    transition: all 0.2s;
    
    &:hover {
        background-color: $gray;
        color: $white;
    }
}

.loading-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 5rem 2rem;
    gap: 1.5rem;
    min-height: 400px;
    background-color: $background-color;
    
    .spinner {
        width: 48px;
        height: 48px;
        border: 4px solid $gray;
        border-top: 4px solid $blue;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }
    
    p {
        color: $white;
        font-size: 0.95rem;
        margin: 0;
        font-weight: 500;
    }
}

.progress-bar {
    padding: 2rem 2rem 1.5rem;
    background-color: $dark-gray;
    border-bottom: 1px solid $gray;
}

.progress-steps {
    display: flex;
    align-items: flex-start;
    justify-content: center;
    max-width: 900px;
    margin: 0 auto;
    gap: 2rem;
}

.progress-step {
    flex: 1;
    
    .step-indicator {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .step-number {
        width: 48px;
        height: 48px;
        min-width: 48px;
        border-radius: 50%;
        background-color: $gray;
        border: 2px solid $gray;
        color: $ultra-light-gray;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 1.125rem;
        transition: all 0.3s ease;
    }
    
    .step-info {
        flex: 1;
    }
    
    .step-label {
        font-size: 0.95rem;
        font-weight: 600;
        color: $ultra-light-gray;
        margin-bottom: 0.25rem;
    }
    
    .step-description {
        font-size: 0.8125rem;
        color: $ultra-light-gray;
        opacity: 0.7;
    }
    
    &.active {
        .step-number {
            background-color: $blue;
            border-color: $blue;
            color: $white;
            box-shadow: 0 0 0 4px rgba(0, 115, 169, 0.2);
        }
        
        .step-label {
            color: $white;
        }
        
        .step-description {
            color: $white;
            opacity: 0.8;
        }
    }
    
    &.completed {
        .step-number {
            background-color: $green;
            border-color: $green;
            color: $white;
        }
        
        .step-label {
            color: $white;
        }
    }
}

.progress-connector {
    flex: 0 0 80px;
    height: 2px;
    background-color: $gray;
    margin-top: 24px;
    position: relative;
    
    &.completed {
        background-color: $green;
    }
}

.modal-body {
    flex: 1;
    overflow-y: auto;
    background-color: $background-color;
}

.wizard-step {
    height: 100%;
}

.step-content {
    padding: 2rem;
}

// Material Selection Styles
.category-selections {
    .catalog-item-section {
        margin-bottom: 2rem;
        
        &:last-child {
            margin-bottom: 0;
        }
    }
    
    .category-section {
        background-color: $dark-gray;
        border: 1px solid $gray;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        overflow: hidden;
        
        &:last-child {
            margin-bottom: 0;
        }
    }
    
    .category-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.25rem 1.5rem;
        background-color: $gray;
        border-bottom: 1px solid lighten($gray, 5%);
    }
    
    .category-title-group {
        flex: 1;
    }
    
    .category-title {
        margin: 0 0 0.375rem 0;
        font-size: 1rem;
        font-weight: 600;
        color: $white;
    }
    
    .category-meta {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.8125rem;
        color: $ultra-light-gray;
        
        .catalog-name {
            font-weight: 500;
        }
        
        .separator {
            color: $ultra-light-gray;
            opacity: 0.5;
        }
        
        .material-type {
            opacity: 0.8;
        }
    }
    
    .required-badge {
        padding: 0.375rem 0.75rem;
        background-color: rgba(0, 115, 169, 0.15);
        color: lighten($blue, 30%);
        border-radius: 4px;
        font-size: 0.8125rem;
        font-weight: 600;
        border: 1px solid rgba(0, 115, 169, 0.3);
    }
    
    .articles-table {
        .table-header {
            display: grid;
            grid-template-columns: 50px 1fr 180px 140px;
            gap: 1rem;
            padding: 0.875rem 1.5rem;
            background-color: $gray;
            border-bottom: 1px solid lighten($gray, 5%);
            font-size: 0.8125rem;
            font-weight: 600;
            color: $ultra-light-gray;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }
        
        .table-row {
            display: grid;
            grid-template-columns: 50px 1fr 180px 140px;
            gap: 1rem;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid $gray;
            cursor: pointer;
            transition: all 0.15s ease;
            align-items: center;
            background-color: $dark-gray;
            
            &:last-child {
                border-bottom: none;
            }
            
            &:hover:not(.unavailable) {
                background-color: lighten($dark-gray, 3%);
            }
            
            &.selected {
                background-color: rgba(129, 201, 80, 0.15) !important;
                border-left: 3px solid $light-green;
                padding-left: calc(1.5rem - 3px);
            }
            
            &.unavailable {
                opacity: 0.4;
                cursor: not-allowed;
            }
        }
        
        .col-select {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .selection-indicator {
            font-size: 1.25rem;
            color: $gray;
            transition: color 0.2s ease;
            
            i.fa-check-circle {
                color: $light-green;
            }
            
            i.fa-circle {
                color: $gray;
            }
        }
        
        .col-name {
            .article-name {
                font-size: 0.9375rem;
                font-weight: 500;
                color: $white;
            }
        }
        
        .col-stock {
            .stock-value {
                font-size: 0.9375rem;
                font-weight: 600;
                color: $white;
                
                &.stock-low {
                    color: $orange;
                }
                
                &.stock-none {
                    color: $red;
                }
            }
        }
        
        .col-status {
            .status-badge {
                display: inline-flex;
                align-items: center;
                padding: 0.375rem 0.75rem;
                border-radius: 4px;
                font-size: 0.8125rem;
                font-weight: 600;
                
                &.available {
                    background-color: rgba(64, 138, 11, 0.15);
                    color: $light-green;
                    border: 1px solid rgba(64, 138, 11, 0.3);
                }
                
                &.insufficient {
                    background-color: rgba(163, 106, 3, 0.15);
                    color: lighten($orange, 20%);
                    border: 1px solid rgba(163, 106, 3, 0.3);
                }
                
                &.unavailable {
                    background-color: rgba(158, 44, 48, 0.15);
                    color: lighten($red, 20%);
                    border: 1px solid rgba(158, 44, 48, 0.3);
                }
            }
        }
    }
}

.no-categories-message {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
    background-color: $dark-gray;
    border: 1px solid $gray;
    border-radius: 8px;
    
    i {
        font-size: 1.5rem;
        color: $blue;
    }
    
    h5 {
        margin: 0 0 0.25rem 0;
        font-size: 0.9375rem;
        font-weight: 600;
        color: $white;
    }
    
    p {
        margin: 0;
        font-size: 0.875rem;
        color: $ultra-light-gray;
    }
}

// Questions Styles
.questions-container {
    .catalog-questions-section {
        background-color: $dark-gray;
        border: 1px solid $gray;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        overflow: hidden;
        
        &:last-child {
            margin-bottom: 0;
        }
    }
    
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.25rem 1.5rem;
        background-color: $gray;
        border-bottom: 1px solid lighten($gray, 5%);
    }
    
    .section-title {
        margin: 0;
        font-size: 1rem;
        font-weight: 600;
        color: $white;
    }
    
    .question-count {
        padding: 0.375rem 0.75rem;
        background-color: rgba(0, 115, 169, 0.15);
        color: lighten($blue, 30%);
        border-radius: 4px;
        font-size: 0.8125rem;
        font-weight: 600;
        border: 1px solid rgba(0, 115, 169, 0.3);
    }
    
    .questions-list {
        padding: 0.5rem;
    }
    
    .question-item {
        margin-bottom: 0.5rem;
        
        &:last-child {
            margin-bottom: 0;
        }
    }
    
    .question-label {
        display: flex;
        align-items: center;
        gap: 0.875rem;
        padding: 1rem 1.25rem;
        background-color: $background-color;
        border: 1px solid $gray;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.15s ease;
        position: relative;
        
        &:hover {
            background-color: lighten($background-color, 3%);
            border-color: lighten($gray, 10%);
        }
        
        &:has(input:checked) {
            background-color: rgba(64, 138, 11, 0.1);
            border-color: $green;
        }
    }
    
    .question-checkbox {
        width: 22px;
        height: 22px;
        accent-color: #408a0b; // Use the actual hex value instead of SCSS variable
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s ease;
        
        &:focus {
            outline: 2px solid rgba(64, 138, 11, 0.3);
            outline-offset: 2px;
        }
    }
    
    .question-text {
        flex: 1;
        font-size: 0.9375rem;
        color: $white;
        line-height: 1.5;
        font-weight: 400;
    }
    
    .no-questions-message {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        padding: 2rem;
        color: $ultra-light-gray;
        font-size: 0.9375rem;
        
        i {
            font-size: 1.25rem;
            color: $green;
        }
    }
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.modal-footer {
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 1.5rem 2rem;
    border-top: 2px solid $gray;
    background-color: $dark-gray;
}

.back-button, .cancel-button, .next-button {
    padding: 0.75rem 1.5rem;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
    font-size: 0.9375rem;
    border: 1px solid;
}

.back-button {
    background-color: $gray;
    color: $white;
    border-color: lighten($gray, 10%);
    
    &:hover:not(:disabled) {
        background-color: lighten($gray, 5%);
        border-color: lighten($gray, 15%);
    }
    
    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
}

.cancel-button {
    background-color: $gray;
    color: $white;
    border-color: lighten($gray, 10%);
    
    &:hover:not(:disabled) {
        background-color: lighten($gray, 5%);
        border-color: lighten($gray, 15%);
    }
    
    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
}

.next-button {
    background-color: $blue;
    color: $white;
    border-color: $blue;
    margin-left: auto;
    
    &:hover:not(:disabled) {
        background-color: lighten($blue, 5%);
        border-color: lighten($blue, 5%);
    }
    
    &:disabled {
        background-color: $gray;
        border-color: $gray;
        cursor: not-allowed;
        opacity: 0.6;
    }
}
</style>
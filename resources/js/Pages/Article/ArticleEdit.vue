<template>
    <div class="d-flex justify-center align-center">
        <v-row>
            <v-dialog
                v-model="dialog"
                persistent="true"
                max-width="1250"
                class="height"
                @keydown.esc="closeDialog"
            >
                <template v-slot:activator="{ props }">
                    <div v-bind="props" class="bt">
                        <SecondaryButton type="submit" class="px-3 blue" @click="openEditForm(article)">{{ $t('Edit') }}</SecondaryButton>
                    </div>
                </template>
                <v-card class="height background">
                    <v-card-title>
                        <span class="text-h5 text-white">{{ $t('updateArticle') }}</span>
                    </v-card-title>
                    <v-card-text>
                        <div v-if="showEditForm">
                            <form @submit.prevent="updateArticle" class="flex gap-3 justify-center">
                                <fieldset>
                                    <legend>{{ $t('GeneralInfo') }}</legend>
                                    <div class="form-group gap-4">
                                        <label for="code">{{ $t('Code') }}:</label>
                                        <input type="text" id="code" v-model="article.code" class="text-gray-700 rounded" required>
                                    </div>
                                    <div class="form-group gap-4">
                                        <label for="name">{{ $t('name') }}:</label>
                                        <input type="text" id="name" v-model="article.name" class="text-gray-700 rounded" required>
                                    </div>
                                    <div class="form-group gap-4">
                                        <label for="tax" class="mr-4 width100">{{ $t('VAT') }}:</label>
                                        <select v-model="article.tax_type" class="text-gray-700 rounded" id="taxA">
                                            <option value="1">DDV A</option>
                                            <option value="2">DDV B</option>
                                            <option value="3">DDV C</option>
                                        </select>
                                        <input type="text" disabled class="rounded text-gray-500" id="taxA2" :placeholder="placeholderText">
                                    </div>
                                    <div class="form-group gap-4">
                                        <label for="type">{{ $t('Product') }}/{{ $t('Service') }}:</label>
                                        <select v-model="article.type" class="text-gray-700 rounded" >
                                            <option value="product">{{ $t('Product') }}</option>
                                            <option value="service">{{ $t('Service') }}</option>
                                        </select>
                                    </div>
                                    <div class="form-group gap-4">
                                        <label for="barcode">{{ $t('Barcode') }}:</label>
                                        <input type="text" v-model="article.barcode" class="text-gray-700 rounded" >
                                    </div>
                                    <div class="form-group gap-4">
                                        <label for="comment">{{ $t('comment') }}:</label>
                                        <input type="text" id="comment" v-model="article.comment" class="text-gray-700 rounded" >
                                    </div>
                                </fieldset>
                                <fieldset>
                                    <legend>{{$t('additionalInfo')}}</legend>
                                    <div class="form-group gap-4">
                                        <label for="height">{{ $t('height') }}:</label>
                                        <input type="text" v-model="article.height" class="text-gray-700 rounded" >
                                    </div>
                                    <div class="form-group gap-4">
                                        <label for="width">{{ $t('width') }}:</label>
                                        <input type="text" v-model="article.width" class="text-gray-700 rounded" >
                                    </div>
                                    <div class="form-group gap-4">
                                        <label for="length">{{ $t('length') }}:</label>
                                        <input type="text" v-model="article.length" class="text-gray-700 rounded" >
                                    </div>
                                    <div class="form-group gap-4">
                                        <label for="weight">{{ $t('weight') }}:</label>
                                        <input type="text" v-model="article.weight" class="text-gray-700 rounded" >
                                    </div>
                                    <div class="form-group gap-4">
                                        <label for="color">{{ $t('color') }}:</label>
                                        <input type="text" v-model="article.color" class="text-gray-700 rounded" >
                                    </div>
                                    <div class="form-group gap-4">
                                        <label for="unit" class="mr-12">{{ $t('Format') }}:</label>
                                        <select v-model="article.format_type" class="text-gray-700 rounded" @change="filterCategories">
                                            <option value="2">{{ $t('Large') }}</option>
                                            <option value="1">{{ $t('Small') }}</option>
                                            <option value="3">{{ $t('Other') }}</option>
                                        </select>
                                    </div>
                                    <div class="form-group gap-4">
                                        <label for="unit" class="mr-12">{{ $t('Unit') }}:</label>
                                        <label>
                                            <input type="checkbox" v-model="articleUnits.in_kilograms" @change="selectUnit('in_kilograms')" class="rounded"> {{ $t('Kg') }}
                                        </label>
                                        <label>
                                            <input type="checkbox" v-model="articleUnits.in_meters" @change="selectUnit('in_meters')" class="rounded"> {{ $t('M') }}
                                        </label>
                                        <label>
                                            <input type="checkbox" v-model="articleUnits.in_pieces" @change="selectUnit('in_pieces')" class="rounded"> {{ $t('Pcs') }}
                                        </label>
                                    </div>
                                    <!-- Category selection -->
                                    <div class="form-group gap-4">
                                        <label>Categories:</label>
                                        <div class="category-dropdown-container">
                                            <!-- Dropdown trigger -->
                                            <div 
                                                @click="toggleCategoryDropdown" 
                                                class="dropdown-trigger"
                                            >
                                                <span v-if="selectedCategoryNames.length === 0" class="placeholder-text">
                                                    Select categories...
                                                </span>
                                                <span v-else class="selected-text">
                                                    {{ selectedCategoryNames.length }} categories selected
                                                </span>
                                                <svg class="dropdown-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                </svg>
                                            </div>
                                            
                                            <!-- Dropdown content -->
                                            <div 
                                                v-show="showCategoryDropdown" 
                                                class="dropdown-content"
                                            >
                                                <!-- Search input -->
                                                <div class="search-container">
                                                    <input 
                                                        type="text" 
                                                        v-model="categorySearchQuery"
                                                        placeholder="Search categories..."
                                                        class="search-input"
                                                        @click.stop
                                                    >
                                                </div>
                                                
                                                <!-- Categories list -->
                                                <div class="categories-list">
                                                    <div v-if="filteredCategories.length === 0" class="no-categories">
                                                        No categories found
                                                    </div>
                                                    <div 
                                                        v-for="category in filteredCategories" 
                                                        :key="category.id" 
                                                        class="category-item"
                                                        @click="toggleCategorySelection(category.id)"
                                                    >
                                                        <input 
                                                            type="checkbox" 
                                                            :checked="selectedCategories.includes(category.id)"
                                                            class="category-checkbox"
                                                            @click.stop
                                                            @change="toggleCategorySelection(category.id)"
                                                        >
                                                        <label class="category-label">
                                                            <span v-if="category.icon" class="category-icon">
                                                                <img :src="`/storage/icons/${category.icon}`" alt="icon" />
                                                            </span>
                                                            {{ category.name }} 
                                                            <span class="category-type">({{ category.type }})</span>
                                                        </label>
                                                    </div>
                                                </div>
                                                
                                                <!-- Selected categories summary -->
                                                <div v-if="selectedCategoryNames.length > 0" class="selected-summary">
                                                    <div class="summary-title">Selected:</div>
                                                    <div class="selected-tags">
                                                        <span 
                                                            v-for="name in selectedCategoryNames.slice(0, 3)" 
                                                            :key="name"
                                                            class="selected-tag"
                                                        >
                                                            {{ name }}
                                                        </span>
                                                        <span 
                                                            v-if="selectedCategoryNames.length > 3"
                                                            class="more-tag"
                                                        >
                                                            +{{ selectedCategoryNames.length - 3 }} more
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </fieldset>
                                <fieldset>
                                    <legend>{{$t('pricelist')}}</legend>
                                    <div class="form-group gap-4">
                                        <label for="fprice">{{ $t('fprice') }}:</label>
                                        <input type="number" v-model="article.factory_price" class="text-gray-700 rounded" >
                                    </div>
                                    <div class="form-group gap-4">
                                        <label for="pprice">{{ $t('pprice') }}:</label>
                                        <input type="number" v-model="article.purchase_price" class="text-gray-700 rounded" >
                                    </div>
                                    <div class="form-group gap-4">
                                        <label for="price">{{ $t('price') }}:</label>
                                        <input type="number" v-model="article.price_1" class="text-gray-700 rounded" >
                                    </div>
                                </fieldset>
                            </form>
                        </div>
                    </v-card-text>
                    <v-card-actions class="flexSpace gap-4">
                        <v-spacer></v-spacer>
                        <SecondaryButton @click="closeDialog" class="red ">{{ $t('close') }}</SecondaryButton>
                        <SecondaryButton @click="updateArticle()" class="green">{{ $t('updateArticle') }}</SecondaryButton>
                    </v-card-actions>
                </v-card>
            </v-dialog>
        </v-row>
    </div>
</template>

<script>
import axios from 'axios';
import PrimaryButton from "@/Components/buttons/PrimaryButton.vue";
import MainLayout from "@/Layouts/MainLayout.vue";
import { useToast } from "vue-toastification";
import Header from "@/Components/Header.vue";
import SecondaryButton from "@/Components/buttons/SecondaryButton.vue";

export default {
    name: 'ArticleEdit',
    components: {SecondaryButton, Header, MainLayout, PrimaryButton },
    data() {
        return {
            dialog: false,
            noteComment: '',
            selectedOption: null,
            actionOptions: [],
            jobs: [],
            showEditForm: false,
            selectedArticleId: null,
            articleUnits: {
                in_kilograms: false,
                in_meters: false,
                in_pieces: false,
                in_square_meters: false,
            },
            allCategories: [],
            availableCategories: [],
            selectedCategories: [],
            showCategoryDropdown: false,
            categorySearchQuery: '',
            selectedCategoryNames: [],
            filteredCategories: [],
        }
    },
    props: {
        article: Object
    },
    mounted() {
        // Add click outside listener
        document.addEventListener('click', this.handleClickOutside);
    },
    beforeUnmount() {
        // Remove click outside listener
        document.removeEventListener('click', this.handleClickOutside);
    },
    computed: {
        placeholderText() {
            switch (this.article.tax_type) {
                case '1':
                    return "18%";
                case '2':
                    return "5%";
                case '3':
                    return "10%";
                default:
                    return "";
            }
        },
        filteredCategories() {
            return this.availableCategories.filter(category => 
                category.name.toLowerCase().includes(this.categorySearchQuery.toLowerCase())
            );
        },
        selectedCategoryNames() {
            return this.availableCategories
                .filter(cat => this.selectedCategories.includes(cat.id))
                .map(cat => cat.name);
        }
    },

    methods: {
        initializeCheckboxValues() {
            if (this.article){
            this.articleUnits.in_kilograms = this.article.in_kilograms===1;
            this.articleUnits.in_meters = this.article.in_meters===1;
            this.articleUnits.in_pieces = this.article.in_pieces===1;
            this.articleUnits.in_square_meters = this.article.in_square_meters===1;
            }
        },
        openDialog() {
            this.dialog = true;
        },
        closeDialog() {
            this.dialog = false;
        },
        openEditForm(article) {
            this.selectedArticleId = article.id;
            this.showEditForm = true;
            this.initializeCheckboxValues();
            this.fetchCategories();
            this.loadArticleCategories(article.id);
        },
        selectUnit(selectedUnit) {
            // Reset all units to false
            this.articleUnits.in_kilograms = false;
            this.articleUnits.in_meters = false;
            this.articleUnits.in_pieces = false;

            // Set the selected unit to true
            this.articleUnits[selectedUnit] = true;
        },
        async fetchCategories() {
            try {
                const response = await axios.get('/api/article-categories');
                this.allCategories = response.data;
                this.filterCategories();
            } catch (error) {
                console.error('Error fetching categories:', error);
            }
        },
        async loadArticleCategories(articleId) {
            try {
                const response = await axios.get(`/articles/${articleId}`);
                if (response.data.categories) {
                    this.selectedCategories = response.data.categories.map(cat => cat.id);
                }
            } catch (error) {
                console.error('Error loading article categories:', error);
            }
        },
        filterCategories() {
            const formatTypeMap = {
                '1': 'small',
                '2': 'large'
            };
            
            const categoryType = formatTypeMap[this.article.format_type];
            
            if (categoryType) {
                this.availableCategories = this.allCategories.filter(cat => 
                    cat.type === categoryType
                );
            } else {
                this.availableCategories = this.allCategories;
            }
            
            // Clear selected categories if they don't match current format type
            this.selectedCategories = this.selectedCategories.filter(categoryId => 
                this.availableCategories.some(cat => cat.id === categoryId)
            );
        },
        toggleCategoryDropdown() {
            this.showCategoryDropdown = !this.showCategoryDropdown;
            if (!this.showCategoryDropdown) {
                this.categorySearchQuery = '';
            }
        },
        toggleCategorySelection(categoryId) {
            if (this.selectedCategories.includes(categoryId)) {
                this.selectedCategories = this.selectedCategories.filter(id => id !== categoryId);
            } else {
                this.selectedCategories.push(categoryId);
            }
        },
        async updateArticle() {
            const toast = useToast();
            try {
                this.article.in_kilograms = this.articleUnits.in_kilograms ? 1 : null;
                this.article.in_meters = this.articleUnits.in_meters ? 1 : null;
                this.article.in_pieces = this.articleUnits.in_pieces ? 1 : null;

                // Add categories to the update payload
                this.article.categories = this.selectedCategories;

                const response = await axios.put(`/articles/${this.article.id}`, this.article);
                toast.success(response.data.message);
                this.closeDialog();
            } catch (error) {
                toast.error("Error updating article!");
                console.error(error);
            }
        },
        handleClickOutside(event) {
            if (this.showCategoryDropdown && !event.target.closest('.category-dropdown-container')) {
                this.showCategoryDropdown = false;
                this.categorySearchQuery = '';
            }
        },
    },
};
</script>

<style scoped lang="scss">

.height {
    height: 100vh;
}

.background {
    background-color: $light-gray;
}

.form-container {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    justify-content: space-between;
}

fieldset {
    border: 1px solid #ffffff;
    border-radius: 5px;
    padding: 20px;
    min-width: 400px;
    background-color: rgba(255, 255, 255, 0.1);
    position: relative;
    overflow: visible;
}

legend {
    color: white;
    padding: 0 10px;
    font-weight: bold;
}

.form-group {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
    color: white;
}

.form-group label {
    width: 150px; /* Set a fixed width for label alignment */
    margin-right: 10px;
    font-weight: 600;
    text-align: right;
}

.form-group input,
.form-group select {
    padding: 8px;
    width: calc(100% - 160px); /* Adjust width based on label width */
    box-sizing: border-box;
    border: 1px solid #ccc;
    border-radius: 4px;
    color: black;
    background-color: white;
}

.input-combo {
    display: flex;
    gap: 10px;
    align-items: center;
}

#taxA {
    width: 100px;
}

#taxA2 {
    width: 80px;
}

.blue {
    background-color: $blue;
    border: none;
    color: white;
}

.red {
    background-color: $red;
    color: white;
    border: none;
}

.green {
    background-color: $green;
    color: white;
    border: none;
}

.bt {
    margin: 12px 12px;
}

input[type="checkbox"]:checked {
    background-color: #007bff;
    border-color: #007bff;
}

.category-dropdown-container {
    position: relative;
    width: 100%;
}

.dropdown-trigger {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    background-color: white;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    min-height: 40px;
}

.dropdown-trigger:hover {
    border-color: #9ca3af;
}

.placeholder-text {
    color: #9ca3af;
    font-size: 14px;
}

.selected-text {
    color: #374151;
    font-size: 14px;
}

.dropdown-arrow {
    width: 16px;
    height: 16px;
    color: #9ca3af;
    flex-shrink: 0;
}

.dropdown-content {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    z-index: 10;
    margin-top: 4px;
    background-color: white;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    max-height: 200px;
    overflow: hidden;
    max-width: 100%;
}

.search-container {
    padding: 6px;
    width: 100%;
    border-bottom: 1px solid #e5e7eb;
}

.search-input {
    width: 100px;
    padding: 4px 6px;
    font-size: 11px;
    border: 1px solid #d1d5db;
    border-radius: 3px;
    color: black;
    outline: none;
}

.search-input:focus {
    border-color: #3b82f6;
}

.categories-list {
    max-height: 120px;
    overflow-y: auto;
}

.no-categories {
    padding: 12px;
    color: #9ca3af;
    font-size: 12px;
    text-align: center;
}

.category-item {
    display: flex;
    align-items: center;
    padding: 6px 8px;
    cursor: pointer;
    border-bottom: 1px solid #f3f4f6;
}

.category-item:hover {
    background-color: #f9fafb;
}

.category-item:last-child {
    border-bottom: none;
}

.category-checkbox {
    margin-right: 8px;
    cursor: pointer;
}

.category-label {
    font-size: 12px;
    color: #374151;
    cursor: pointer;
    display: flex;
    align-items: center;
    flex: 1;
}

.category-icon {
    margin-right: 6px;
    display: flex;
    align-items: center;
}

.category-icon img {
    width: 14px;
    height: 14px;
}

.category-type {
    font-size: 10px;
    color: #9ca3af;
    margin-left: 4px;
}

.selected-summary {
    padding: 6px;
    border-top: 1px solid #e5e7eb;
    background-color: #f9fafb;
}

.summary-title {
    font-size: 10px;
    color: #6b7280;
    margin-bottom: 4px;
}

.selected-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}

.selected-tag {
    display: inline-block;
    padding: 2px 6px;
    font-size: 10px;
    background-color: #dbeafe;
    color: #1e40af;
    border-radius: 3px;
}

.more-tag {
    display: inline-block;
    padding: 2px 6px;
    font-size: 10px;
    background-color: #e5e7eb;
    color: #6b7280;
    border-radius: 3px;
}

/* Custom scrollbar for category list */
.categories-list::-webkit-scrollbar {
    width: 6px;
}

.categories-list::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.categories-list::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.categories-list::-webkit-scrollbar-thumb:hover {
    background: #a1a1a1;
}

</style>

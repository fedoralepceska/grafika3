<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="article" subtitle="addNewArticle" icon="Materials.png" link="articles"/>
            <div class="dark-gray p-5">
                <div class="form-container p-2 light-gray">
                    <h2 class="sub-title">
                        {{ $t('articleDetails') }}
                    </h2>
                    <form @submit.prevent="createArticle" class="form-wrapper flex gap-3 justify-center">
                        <fieldset>
                            <legend>{{ $t('GeneralInfo') }}</legend>
                            <div class="form-group gap-4">
                                <label for="code">{{ $t('Code') }}:</label>
                                <input type="text" id="code" v-model="form.code" class="text-gray-700 rounded" required>
                            </div>
                            <div class="form-group gap-4">
                                <label for="name">{{ $t('name') }}:</label>
                                <input type="text" id="name" v-model="form.name" class="text-gray-700 rounded" required>
                            </div>
                            <div class="form-group">
                                <label for="tax" class="mr-4 width100">{{ $t('VAT') }}:</label>
                                <select v-model="form.selectedOption" class="text-gray-700 rounded" id="taxA">
                                    <option value="DDV A">DDV A</option>
                                    <option value="DDV B">DDV B</option>
                                    <option value="DDV C">DDV C</option>
                                </select>
                                <input type="text" disabled class="rounded text-gray-500" id="taxA2" :placeholder="placeholderText">
                            </div>
                            <div class="form-group gap-4">
                                <label for="type">{{ $t('Product') }}/{{ $t('Service') }}:</label>
                                <select v-model="form.type" class="text-gray-700 rounded" >
                                    <option value="product">{{ $t('Product') }}</option>
                                    <option value="service">{{ $t('Service') }}</option>
                                </select>
                            </div>
                            <div class="form-group gap-4">
                                <label for="barcode">{{ $t('Barcode') }}:</label>
                                <input type="text" v-model="form.barcode" class="text-gray-700 rounded" >
                            </div>
                            <div class="form-group gap-4">
                                <label for="comment">{{ $t('comment') }}:</label>
                                <input type="text" id="comment" v-model="form.comment" class="text-gray-700 rounded" >
                            </div>
                        </fieldset>
                        <fieldset>
                            <legend>{{$t('additionalInfo')}}</legend>
                            <div class="form-group gap-4">
                                <label for="height">{{ $t('height') }}:</label>
                                <input type="text" v-model="form.height" class="text-gray-700 rounded" >
                            </div>
                            <div class="form-group gap-4">
                                <label for="width">{{ $t('width') }}:</label>
                                <input type="text" v-model="form.width" class="text-gray-700 rounded" >
                            </div>
                            <div class="form-group gap-4">
                                <label for="length">{{ $t('length') }}:</label>
                                <input type="text" v-model="form.length" class="text-gray-700 rounded" >
                            </div>
                            <div class="form-group gap-4">
                                <label for="weight">{{ $t('weight') }}:</label>
                                <input type="text" v-model="form.weight" class="text-gray-700 rounded" >
                            </div>
                            <div class="form-group gap-4">
                                <label for="color">{{ $t('color') }}:</label>
                                <input type="text" v-model="form.color" class="text-gray-700 rounded" >
                            </div>
                            <div class="form-group gap-4">
                                <label for="unit" class="mr-12">{{ $t('Format') }}:</label>
                                <select v-model="form.format_type" class="text-gray-700 rounded" @change="filterCategories">
                                    <option value="2">{{ $t('Large') }}</option>
                                    <option value="1">{{ $t('Small') }}</option>
                                    <option value="3">{{ $t('Other') }}</option>
                                </select>
                            </div>
                            <div class="form-group gap-4">
                                <label for="unit" class="mr-12">{{ $t('Unit') }}:</label>
                                <select v-model="form.unit" class="text-gray-700 rounded" >
                                    <option value="kilogram">{{ $t('Kg') }}</option>
                                    <option value="pieces">{{ $t('Pcs') }}</option>
                                    <option value="meters">{{ $t('M') }}</option>
                                    <option value="square_meters">{{ $t('square_meters') }}</option>
                                </select>
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
                                                    :checked="form.categories.includes(category.id)"
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
                                <input type="text" v-model="form.fprice" class="text-gray-700 rounded" >
                            </div>
                            <div class="form-group gap-4">
                                <label for="pprice">{{ $t('pprice') }}:</label>
                                <input type="text" v-model="form.pprice" class="text-gray-700 rounded" >
                            </div>
                            <div class="form-group gap-4">
                                <label for="price">{{ $t('price') }}:</label>
                                <input type="text" v-model="form.price" class="text-gray-700 rounded" >
                            </div>
                        </fieldset>
                    </form>
                    <div class="button-container mt-10">
                        <PrimaryButton type="submit" @click="createArticle">{{ $t('addArticle') }}</PrimaryButton>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script>
import axios from 'axios';
import PrimaryButton from "@/Components/buttons/PrimaryButton.vue";
import MainLayout from "@/Layouts/MainLayout.vue";
import { useToast } from "vue-toastification";
import Header from "@/Components/Header.vue";

export default {
    name: 'Create',
    components: { Header, MainLayout, PrimaryButton },
    data() {
        return {
            form: {
                code: '',
                name: '',
                selectedOption: 'DDV A',
                type: 'product',
                barcode: '',
                comment: '',
                height: '',
                width: '',
                length: '',
                weight: '',
                color: '',
                format_type: '2',
                in_meters:'',
                in_square_meters: '',
                in_kilograms:'',
                in_pieces:'',
                unit: 'meters',
                fprice: '',
                pprice: '',
                price: '',
                categories: []
            },
            allCategories: [],
            availableCategories: [],
            showCategoryDropdown: false,
            categorySearchQuery: ''
        }
    },
    computed: {
        placeholderText() {
            switch (this.form.selectedOption) {
                case "DDV A":
                    return "18%";
                case "DDV B":
                    return "5%";
                case "DDV C":
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
                .filter(cat => this.form.categories.includes(cat.id))
                .map(cat => cat.name);
        }
    },
    async mounted() {
        await this.fetchArticleCount();
        await this.fetchCategories();
        this.filterCategories();
        // Add click outside listener
        document.addEventListener('click', this.handleClickOutside);
    },
    beforeUnmount() {
        // Remove click outside listener
        document.removeEventListener('click', this.handleClickOutside);
    },
    methods: {
        async fetchArticleCount() {
            try {
                const response = await axios.get('/articles/count');
                if (response.data) {
                    this.form.code = response.data.count + 1;
                }
            } catch (error) {
                console.error('Error fetching article count:', error);
            }
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
        filterCategories() {
            // Map format_type to category type
            const formatTypeMap = {
                '1': 'small',
                '2': 'large'
            };
            
            const categoryType = formatTypeMap[this.form.format_type];
            
            // Filter categories based on format type
            if (categoryType) {
                this.availableCategories = this.allCategories.filter(cat => 
                    cat.type === categoryType
                );
            } else {
                // For 'other' format type (3), show both large and small categories
                this.availableCategories = this.allCategories;
            }
            
            // Clear selected categories if they don't match current format type
            this.form.categories = this.form.categories.filter(categoryId => 
                this.availableCategories.some(cat => cat.id === categoryId)
            );
        },
        async createArticle() {
            try {
                switch (this.form.unit) {
                    case "meters": {
                        this.form.in_meters = true;
                        break;
                    }
                    case "square_meters": {
                        this.form.in_square_meters = true;
                        break;
                    }
                    case "pieces": {
                        this.form.in_pieces = true;
                        break;
                    }
                    case "kilogram": {
                        this.form.in_kilogram = true;
                        break;
                    }
                    default: {
                        break;
                    }
                }
                const response = await axios.post('/articles/create', this.form);
                const toast = useToast();
                // Clear form after successful submission
                toast.success('Item added successfully!');
                this.form = {
                    code: '',
                    name: '',
                    selectedOption: 'DDV A',
                    type: 'product',
                    barcode: '',
                    comment: '',
                    height: '',
                    width: '',
                    length: '',
                    weight: '',
                    color: '',
                    in_meters:'',
                    in_kilograms:'',
                    in_pieces:'',
                    in_square_meters: '',
                    format_type: '2',
                    fprice: '',
                    pprice: '',
                    price: '',
                    categories: []
                };
                await this.fetchArticleCount();
                this.$inertia.visit(`/articles`);
            } catch (error) {
                console.error(error);
                const toast = useToast();
                toast.error('Failed to add article');
            }
        },
        toggleCategoryDropdown() {
            this.showCategoryDropdown = !this.showCategoryDropdown;
            if (!this.showCategoryDropdown) {
                this.categorySearchQuery = '';
            } else {
                // Adjust dropdown position if needed
                this.$nextTick(() => {
                    const dropdown = document.querySelector('.dropdown-content');
                    const trigger = document.querySelector('.dropdown-trigger');
                    if (dropdown && trigger) {
                        const triggerRect = trigger.getBoundingClientRect();
                        const viewportHeight = window.innerHeight;
                        const dropdownHeight = 200; // max-height
                        
                        // Check if there's enough space below
                        if (triggerRect.bottom + dropdownHeight > viewportHeight) {
                            dropdown.style.top = 'auto';
                            dropdown.style.bottom = '100%';
                            dropdown.style.marginTop = '0';
                            dropdown.style.marginBottom = '4px';
                        } else {
                            dropdown.style.top = '100%';
                            dropdown.style.bottom = 'auto';
                            dropdown.style.marginTop = '4px';
                            dropdown.style.marginBottom = '0';
                        }
                    }
                });
            }
        },
        toggleCategorySelection(categoryId) {
            if (this.form.categories.includes(categoryId)) {
                this.form.categories = this.form.categories.filter(id => id !== categoryId);
            } else {
                this.form.categories.push(categoryId);
            }
        },
        handleClickOutside(event) {
            if (this.showCategoryDropdown && !event.target.closest('.category-dropdown-container')) {
                this.showCategoryDropdown = false;
                this.categorySearchQuery = '';
            }
        }
    },
};
</script>

<style scoped lang="scss">

fieldset {
    border: 1px solid #ffffff;
    border-radius: 3px;
    width: fit-content;
    padding-right: 35px;
    position: relative;
    overflow: visible;
}
legend {
    margin-left: 10px;
    color: white;
}
#taxA{
    width: 120px;
}
#taxA2{
    width: 80px;
}
.green-text{
    color: $green;
}
.header{
    display: flex;
    align-items: center;
}
.dark-gray {
    background-color: $dark-gray;
    justify-content: left;
    align-items: center;
    min-height: 20vh;

}
.light-gray{
    background-color: $light-gray;
}

.client-form {
    width: 100%;
    max-width: 1000px;
    justify-content: left;
    align-items: center;
    min-height: 20vh;
    padding: 5px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

.sub-title{
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    color: $white;
}

.form-group {
    display: flex;
    justify-content: right;
    align-items: center;
    width: 350px;
    margin-bottom: 10px;
    color: $white;
}

.label {
    flex: 1;
    text-align: left;
    margin-right: 20px;
}
.button-container{
    display: flex;
    justify-content: end;
}

.form-wrapper {
    position: relative;
    overflow: visible;
}

/* Custom dropdown styles */
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
    border-bottom: 1px solid #e5e7eb;
}

.search-input {
    width: 100%;
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

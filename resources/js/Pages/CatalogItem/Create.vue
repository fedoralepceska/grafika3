<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <div class="flex justify-between items-center mb-4">
                <Header title="Catalog" subtitle="createNewCatalogItem" icon="List.png" link="catalog"/>
            
            </div>

            <div class="dark-gray p-2 text-white">
                <div class="form-container p-2">
                    <h2 class="sub-title">{{ $t('catalogItemCreation') }}</h2>

                    <form @submit.prevent="submit" class="space-y-6 w-full rounded-lg">
                        <!-- Basic Information -->
                        <div class="grid grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <div>
                                    <label class="text-white">{{ $t('name') }}</label>
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        class="w-full mt-1 rounded"
                                        required
                                    />
                                </div>

                                <div>
                                    <label class="text-white">{{ $t('machineP') }}</label>
                                    <select
                                        v-model="form.machinePrint"
                                        class="w-full mt-1 rounded"
                                    >
                                        <option value="">{{ $t('selectMachine') }}</option>
                                        <option v-for="machine in machinesPrint"
                                                :key="machine.id"
                                                :value="machine.name">
                                            {{ machine.name }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label class="text-white">{{ $t('machineC') }}</label>
                                    <select
                                        v-model="form.machineCut"
                                        class="w-full mt-1 rounded"
                                    >
                                        <option value="">{{ $t('selectMachine') }}</option>
                                        <option v-for="machine in machinesCut"
                                                :key="machine.id"
                                                :value="machine.name">
                                            {{ machine.name }}
                                        </option>
                                    </select>
                                </div>
                                
                                <!-- Pricing Method Selection -->
                                <div class="mt-4 p-2 border-dashed border-2 border-gray-500">
                                    <label class="text-white block mb-2">{{ $t('pricingMethod') }}</label>
                                    <div class="space-y-2">
                                        <div class="flex items-center">
                                            <input
                                                type="radio"
                                                id="pricing_quantity"
                                                name="pricing_method"
                                                value="quantity"
                                                v-model="pricingMethod"
                                                class="mr-2"
                                            />
                                            <label for="pricing_quantity" class="text-white">{{ $t('priceByQuantity') }}</label>
                                        </div>
                                        <div class="flex items-center">
                                            <input
                                                type="radio"
                                                id="pricing_copies"
                                                name="pricing_method"
                                                value="copies"
                                                v-model="pricingMethod"
                                                class="mr-2"
                                            />
                                            <label for="pricing_copies" class="text-white">{{ $t('priceByCopies') }}</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label class="text-white">{{ $t('category') }}</label>
                                    <select
                                        v-model="form.category"
                                        class="w-full mt-1 rounded"
                                    >
                                        <option value="">{{ $t('selectCategory') }}</option>
                                        <option value="material">Large Material</option>
                                        <option value="small_format">Small Material</option>
                                        <option value="article">Article</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-white">{{ $t('subcategory') }} ({{ $t('optional for listing') }})</label>
                                    <div class="flex items-center gap-2">
                                        <MultiSelect
                                            v-model="form.subcategory_ids"
                                            :options="subcategories"
                                            label-key="name"
                                            value-key="id"
                                            :placeholder="$t('selectSubcategory')"
                                            :search-placeholder="$t('Search')"
                                        />
                                    
                                        <div class="flex flex-row items-center gap-5">
                                            <div class="p-2">
                                                <CreateSubcategoryDialog @created="handleSubcategoryCreated" />
                                            </div>
                                            <div>
                                                <ViewSubcategoriesDialog 
                                                    @updated="handleSubcategoryUpdated"
                                                    @deleted="handleSubcategoryDeleted"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Material selection fields hidden - now using articles from catalog item -->
                                <!--
                                <div>
                                    <label class="text-white">{{ $t('materialLargeFormat') }} - large format</label>
                                    <select
                                        v-model="form.large_material_id"
                                        class="w-full mt-1 rounded"
                                        :disabled="isLargeMaterialDisabled"
                                        @change="handleLargeMaterialChange"
                                    >
                                        <option value="">{{ $t('selectMaterial') }}</option>
                                        <option v-for="material in largeMaterials"
                                                :key="material.id"
                                                :value="material.id">
                                            <template v-if="material.type === 'category'">
                                                {{ material.name }}
                                            </template>
                                            <template v-else>
                                                {{ material.article?.name }} ({{ material.article?.code }})
                                            </template>
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label class="text-white">{{ $t('materialSmallFormat') }}</label>
                                    <select
                                        v-model="form.small_material_id"
                                        class="w-full mt-1 rounded"
                                        :disabled="isSmallMaterialDisabled"
                                        @change="handleSmallMaterialChange"
                                    >
                                        <option value="">{{ $t('selectMaterial') }}</option>
                                        <option v-for="material in smallMaterials"
                                                :key="material.id"
                                                :value="material.id">
                                            <template v-if="material.type === 'category'">
                                                {{ material.name }}
                                            </template>
                                            <template v-else>
                                                {{ material.article?.name }} ({{ material.article?.code }})
                                            </template>
                                        </option>
                                    </select>
                                </div>
                                -->

                                <div>
                                    <label class="text-white">{{ $t('defaultPrice') }}</label>
                                    <input
                                        v-model="form.price"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="w-full mt-1 rounded"
                                        required
                                        :disabled="isRabotnikComputed"
                                        :class="{ 'bg-gray-200 cursor-not-allowed': isRabotnikComputed }"
                                    />
                                </div>
                                <div class="flex flex-col p-2 border-dashed border-2 border-gray-500">
                                    <label class="text-white block mb-2">{{ $t('Additional options') }}</label>
                                    <div class="flex items-center gap-8">
                                        <div class="flex items-center">
                                            <Checkbox name="is_for_offer" v-model:checked="form.is_for_offer" />
                                            <label class="text-white ml-2">{{ $t('forOffer') }}</label>
                                        </div>
                                        <div class="flex items-center">
                                        <Checkbox name="is_for_sales" v-model:checked="form.is_for_sales" />
                                        <label class="text-white ml-2">{{ $t('forSales') }}</label>
                                        </div>
                                    </div>
                                    <div class="flex items-center mt-2">
                                        <Checkbox name="should_ask_questions" v-model:checked="form.should_ask_questions" />
                                        <label class="text-white ml-2">Ask questions before production</label>
                                    </div>
                                </div>
                                
                                <!-- Questions Selection -->
                                <div v-if="form.should_ask_questions" class="p-4 border-dashed border-2 border-gray-500 dark-gray">
                                    <h4 class="text-white text-md font-medium mb-4">Select Questions for This Item</h4>
                                    <div v-if="availableQuestions.length === 0" class="text-gray-400">
                                        No questions available
                                    </div>
                                    <div v-else class="space-y-2">
                                        <div v-for="question in availableQuestions" :key="question.id" class="flex items-center">
                                            <input
                                                type="checkbox"
                                                :id="`question-${question.id}`"
                                                :value="question.id"
                                                v-model="selectedQuestions"
                                                class="mr-3 h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded"
                                            />
                                            <label :for="`question-${question.id}`" class="text-white text-sm cursor-pointer">
                                                {{ question.question }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Articles Section -->
                        <div class="mt-6 p-4 border-dashed border-2 border-gray-500 ">
                            <h3 class="text-white text-lg font-semibold mb-4">{{ $t('componentArticles') }}</h3>

                            <!-- Products Section -->
                            <div class="mb-6">
                                <div class="flex justify-between items-center mb-4">
                                    <h4 class="text-white text-md font-medium">{{ $t('products') }}</h4>
                                    <button
                                        type="button"
                                        @click="addArticle('product')"
                                        class="text-green-500 hover:text-green-700"
                                    >
                                        <span class="mdi mdi-plus-circle"></span> {{ $t('addProduct') }}
                                    </button>
                                </div>
                                <div class="space-y-4">
                                    <div v-for="(article, index) in productArticles" :key="index"
                                         class="flex items-center space-x-4 light-gray p-4 rounded">
                                        <div class="flex-1">
                                            <label class="text-white mb-2 block">{{ $t('product') }}</label>
                                            <CatalogArticleSelect
                                                v-model="article.id"
                                                :type="'product'"
                                                @article-selected="handleArticleSelected($event, index, 'product')"
                                                class="w-full"
                                            />
                                        </div>
                                        <div class="w-32">
                                            <label class="text-white mb-2 block">{{ $t('quantity') }}{{ article.unitLabel ? ` (${article.unitLabel})` : '' }}</label>
                                            <input
                                                v-model="article.quantity"
                                                type="number"
                                                step="0.0001"
                                                class="w-full rounded option"
                                                required
                                            />
                                        </div>
                                        <button
                                            type="button"
                                            @click="removeArticle(index, 'product')"
                                            class="text-red-500 hover:text-red-700 mt-8"
                                        >
                                            <span class="mdi mdi-delete"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Services Section -->
                            <div>
                                <div class="flex justify-between items-center mb-4">
                                    <h4 class="text-white text-md font-medium">{{ $t('services') }}</h4>
                                    <button
                                        type="button"
                                        @click="addArticle('service')"
                                        class="text-green-500 hover:text-green-700"
                                    >
                                        <span class="mdi mdi-plus-circle"></span> {{ $t('addService') }}
                                    </button>
                                </div>
                                <div class="space-y-4">
                                    <div v-for="(article, index) in serviceArticles" :key="index"
                                         class="flex items-center space-x-4 light-gray p-4 rounded">
                                        <div class="flex-1">
                                            <label class="text-white mb-2 block">{{ $t('service') }}</label>
                                            <CatalogArticleSelect
                                                v-model="article.id"
                                                :type="'service'"
                                                @article-selected="handleArticleSelected($event, index, 'service')"
                                                class="w-full"
                                            />
                                        </div>
                                        <div class="w-32">
                                            <label class="text-white mb-2 block">{{ $t('quantity') }}{{ article.unitLabel ? ` (${article.unitLabel})` : '' }}</label>
                                            <input
                                                v-model="article.quantity"
                                                type="number"
                                                step="0.0001"
                                                class="w-full rounded option"
                                                required
                                            />
                                        </div>
                                        <button
                                            type="button"
                                            @click="removeArticle(index, 'service')"
                                            class="text-red-500 hover:text-red-700 mt-8"
                                        >
                                            <span class="mdi mdi-delete"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Cost Price Display -->
                            <div v-if="!isRabotnikComputed" class="mt-4 p-4 bg-gray-700 rounded">
                                <h4 class="text-white text-md font-medium mb-3">{{ $t('costSummary') }}</h4>
                                <div class="space-y-2">
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-300">{{ $t('productsCost') }}:</span>
                                        <span class="text-white">{{ displayProductsCost.toFixed(2) }} ден</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-300">{{ $t('servicesCost') }}:</span>
                                        <span class="text-white">{{ displayServicesCost.toFixed(2) }} ден</span>
                                    </div>
                                    <div class="flex justify-between items-center pt-2 border-t border-gray-600">
                                        <span class="text-white font-semibold">{{ $t('totalCostPrice') }}:</span>
                                        <span class="text-white font-semibold">{{ displayTotalCost.toFixed(2) }} ден</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- File/Template Upload and Description Section -->
                        <div class="grid grid-cols-2 gap-6 mt-6">
                            <!-- File Upload -->
                            <div>
                                <h4 class="text-white text-md font-medium mb-3">{{ $t('fileUpload') }}</h4>
                                <div
                                    class="upload-area compact"
                                    @dragover.prevent
                                    @drop.prevent="handleDrop"
                                    @click="triggerFileInput"
                                >
                                    <input
                                        type="file"
                                        id="file-input"
                                        class="hidden"
                                        @change="handleFileInput"
                                        accept=".pdf, .png, .jpg, .jpeg"
                                    />
                                    <div v-if="!previewUrl" class="placeholder-content compact">
                                        <div class="upload-icon">
                                            <span class="mdi mdi-cloud-upload text-2xl"></span>
                                        </div>
                                        <p class="upload-text">{{ $t('dragAndDrop') }}</p>
                                        <p class="upload-text-sub">{{ $t('orClickToBrowse') }}</p>
                                        <p class="file-types">{{ $t('supportedFormats') }}: PDF, PNG, JPG, JPEG</p>
                                    </div>
                                    <div v-else class="preview-container compact">
                                        <img
                                            v-if="isImage"
                                            :src="previewUrl"
                                            alt="Preview"
                                            class="preview-image compact"
                                        />
                                        <div v-else class="pdf-preview compact">
                                            <span class="mdi mdi-file-pdf text-2xl"></span>
                                            <span class="pdf-name">{{ fileName }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Template Upload -->
                            <div>
                                <h4 class="text-white text-md font-medium mb-3">{{ $t('templateFilePdfOnly') }}</h4>
                                <div
                                    class="upload-area compact"
                                    @dragover.prevent
                                    @drop.prevent="handleTemplateDrop"
                                    @click="triggerTemplateFileInput"
                                >
                                    <input
                                        type="file"
                                        id="template-file-input"
                                        class="hidden"
                                        @change="handleTemplateFileInput"
                                        accept=".pdf"
                                    />
                                    <div v-if="!templatePreviewUrl" class="placeholder-content compact">
                                        <div class="upload-icon">
                                            <span class="mdi mdi-cloud-upload text-2xl"></span>
                                        </div>
                                        <p class="upload-text">{{ $t('dragAndDropTemplatePdfHere') }}</p>
                                        <p class="upload-text-sub">{{ $t('orClickToBrowse') }}</p>
                                        <p class="file-types">{{ $t('supportedFormats') }}: PDF</p>
                                    </div>
                                    <div v-else class="preview-container compact">
                                        <div class="pdf-preview compact">
                                            <span class="mdi mdi-file-pdf text-2xl"></span>
                                            <span class="pdf-name">{{ templateFileName }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Description below, full width but short -->
                        <div class="mt-4">
                            <h4 class="text-white text-md font-medium mb-3">{{ $t('description') }}</h4>
                            <textarea
                                v-model="form.description"
                                class="w-full rounded description-textarea compact"
                                rows="4"
                                placeholder="Enter item description..."
                                style="min-height: 60px; max-height: 120px;"
                            ></textarea>
                        </div>

                        <!-- Actions Section -->
                        <div class="mt-6">
                            <h3 class="text-white text-lg font-semibold mb-4">{{ $t('ACTIONS') }}</h3>
                            <div class="space-y-4">
                                <div v-for="(action, index) in form.actions" :key="index"
                                     class="flex items-center space-x-4 light-gray p-4 rounded">
                                    <div class="flex-1">
                                        <select
                                            :value="action.selectedAction"
                                            @input="(e) => handleActionSelect(e, action)"
                                            class="w-full rounded option"
                                            :class="{ 'border-red-500': !action.selectedAction }"
                                            required
                                        >
                                            <option class="option" value="">Select Action</option>
                                            <option v-for="availableAction in availableActions"
                                                    :key="availableAction.id"
                                                    :value="availableAction.id"
                                                    :selected="action.selectedAction === availableAction.id"
                                                    class="option"
                                            >
                                                {{ availableAction.name }}
                                            </option>
                                        </select>
                                    </div>
                                    <div v-if="action.showQuantity" class="w-32">
                                        <input
                                            v-if="action.showQuantity"
                                            v-model="action.quantity"
                                            type="number"
                                            min="0"
                                            class="w-full rounded option"
                                            :class="{ 'border-red-500': action.showQuantity && (!action.quantity || action.quantity <= 0) }"
                                            placeholder="Quantity"
                                            required
                                        />
                                    </div>
                                    <button
                                        type="button"
                                        @click="removeAction(index)"
                                        class="text-red-500 hover:text-red-700"
                                    >
                                        <span class="mdi mdi-delete"></span>
                                    </button>
                                </div>

                                <button
                                    type="button"
                                    @click="addAction"
                                    class="text-green-500 hover:text-green-700"
                                >
                                    <span class="mdi mdi-plus-circle"></span> {{ $t('addAction') }}
                                </button>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-4">
                            <button type="submit" class="btn btn-primary">
                                {{ $t('createCatalogItem') }}
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
import { Link } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import Checkbox from '@/Components/inputs/Checkbox.vue';
import CatalogArticleSelect from '@/Components/CatalogArticleSelect.vue';
import MultiSelect from '@/Components/inputs/MultiSelect.vue';

import CreateSubcategoryDialog from '@/Components/CreateSubcategoryDialog.vue';
import ViewSubcategoriesDialog from '@/Components/ViewSubcategoriesDialog.vue';
import useRoleCheck from '@/Composables/useRoleCheck';
import { computed } from 'vue';

export default {
    components: {
        MainLayout,
        Header,
        Link,
        Checkbox,
        CatalogArticleSelect,
        MultiSelect,

        CreateSubcategoryDialog,
        ViewSubcategoriesDialog
    },

    setup() {
        const { isRabotnik } = useRoleCheck();
        
        const isRabotnikComputed = computed(() => isRabotnik.value);
        
        return {
            isRabotnikComputed
        };
    },

    props: {
        actions: Array,
        largeMaterials: Array,
        smallMaterials: Array,
        machinesPrint: Array,
        machinesCut: Array,
        availableQuestions: Array,
    },

    data() {
        return {
            form: {
                name: '',
                description: '',
                machinePrint: '',
                machineCut: '',
                large_material_id: null,
                small_material_id: null,
                quantity: 1,
                copies: 1,
                actions: [],
                is_for_offer: false,
                is_for_sales: true,
                category: '',
                file: null,
                price: '0',
                articles: [],
                template_file: null,
                subcategory_id: null, // legacy, not sent
                subcategory_ids: [],
                should_ask_questions: false
            },
            pricingMethod: 'quantity', // Default to quantity-based pricing
            productArticles: [],
            serviceArticles: [],
            categories: ['material', 'article', 'small_format'],
            previewUrl: null,
            fileName: '',
            calculatedCostPrice: 0,
            productsCost: 0,
            servicesCost: 0,
            templatePreviewUrl: null,
            templateFileName: '',
            subcategories: [],
            selectedQuestions: []
        }
    },

    computed: {
        availableActions() {
            return this.actions.filter(action => {
                return !this.form.actions.some(selectedAction =>
                    selectedAction.selectedAction === action.id
                )
            })
        },
        isImage() {
            return this.form.file && this.form.file.type?.startsWith("image/");
        },
        displayProductsCost() {
            return this.productArticles.reduce((total, article) => {
                return total + ((article.purchase_price || 0) * (article.quantity || 0));
            }, 0);
        },
        displayServicesCost() {
            return this.serviceArticles.reduce((total, article) => {
                return total + ((article.purchase_price || 0) * (article.quantity || 0));
            }, 0);
        },
        displayTotalCost() {
            return this.displayProductsCost + this.displayServicesCost;
        },
        isLargeMaterialDisabled() {
            // If category is 'small_format', disable large material
            if (this.form.category === 'small_format') {
                return true;
            }
            // If category is 'material', only allow large material
            if (this.form.category === 'material') {
                return false;
            }
            // If category is 'article', allow both but only one can be selected
            if (this.form.category === 'article') {
                return false;
            }
            // Default: disable if no category selected
            return !this.form.category;
        },
        isSmallMaterialDisabled() {
            // If category is 'material', disable small material
            if (this.form.category === 'material') {
                return true;
            }
            // If category is 'small_format', only allow small material
            if (this.form.category === 'small_format') {
                return false;
            }
            // If category is 'article', allow both but only one can be selected
            if (this.form.category === 'article') {
                return false;
            }
            // Default: disable if no category selected
            return !this.form.category;
        }
    },

    watch: {
        productArticles: {
            deep: true,
            handler() {
                this.calculateCostPrice();
            }
        },
        serviceArticles: {
            deep: true,
            handler() {
                this.calculateCostPrice();
            }
        },
        'form.should_ask_questions'(newValue) {
            if (!newValue) {
                // Clear questions when checkbox is unchecked
                this.selectedQuestions = [];
            }
        },
        'form.category'(newValue, oldValue) {
            // Clear materials when category changes
            if (newValue !== oldValue) {
                this.form.large_material_id = null;
                this.form.small_material_id = null;
            }
        }
    },

    methods: {
        addArticle(type) {
            const article = {
                id: null,
                quantity: 1,
                type: type
            };

            if (type === 'product') {
                this.productArticles.push(article);
            } else {
                this.serviceArticles.push(article);
            }
        },

        removeArticle(index, type) {
            if (type === 'product') {
                this.productArticles.splice(index, 1);
            } else {
                this.serviceArticles.splice(index, 1);
            }
            this.calculateCostPrice();
        },

        handleArticleSelected(article, index, type) {
            const targetArray = type === 'product' ? this.productArticles : this.serviceArticles;
            targetArray[index] = {
                ...targetArray[index],
                ...article,
                purchase_price: article.purchase_price || 0
            };
            this.calculateCostPrice();
        },

        calculateCostPrice() {
            this.productsCost = this.displayProductsCost;
            this.servicesCost = this.displayServicesCost;
            this.calculatedCostPrice = this.displayTotalCost;
        },

        addAction() {
            this.form.actions.push({
                selectedAction: '',
                quantity: 0,
                showQuantity: false,
                isMaterialized: null
            });
        },

        handleActionSelect(event, action) {
            action.selectedAction = event.target.value;
            this.handleActionChange(action);
        },

        handleActionChange(action) {
            if (!action.selectedAction) {
                action.showQuantity = false;
                return;
            }
            const selectedAction = this.actions.find(a => a.id === parseInt(action.selectedAction));

            if (!selectedAction?.name) {
                console.error('Selected action has no name:', selectedAction);
                return;
            }

            action.action_id = {
                id: selectedAction.id,
                name: selectedAction.name
            };
            action.status = 'Not started yet';
            action.showQuantity = selectedAction.isMaterialized ?? false;
            action.isMaterialized = selectedAction.isMaterialized;

            if (!action.showQuantity) {
                action.quantity = 0;
            }
        },

        removeAction(index) {
            this.form.actions.splice(index, 1)
        },

        async handleDrop(event) {
            const file = event.dataTransfer.files[0];
            this.processFile(file);
        },

        async handleFileInput(event) {
            const file = event.target.files[0];
            this.processFile(file);
        },

        processFile(file) {
            if (!file) return;

            this.form.file = file;
            this.fileName = file.name;

            if (file.type.startsWith("image/")) {
                this.isImage = true;
                this.previewUrl = URL.createObjectURL(file);
            } else if (file.type === "application/pdf") {
                this.isImage = false;
                this.previewUrl = "/storage/uploads/placeholder.jpeg";
            }
        },

        validateActions() {
            if (this.form.actions.length === 0) {
                return 'At least one action is required';
            }

            for (let i = 0; i < this.form.actions.length; i++) {
                const action = this.form.actions[i];
                if (!action.selectedAction) {
                    return 'All actions must have a selected type';
                }
                if (action.showQuantity && (!action.quantity || action.quantity <= 0)) {
                    return 'Quantity is required for materialized actions';
                }
            }
            return null;
        },

        handleTemplateDrop(event) {
            const file = event.dataTransfer.files[0];
            this.processTemplateFile(file);
        },

        handleTemplateFileInput(event) {
            const file = event.target.files[0];
            this.processTemplateFile(file);
        },

        processTemplateFile(file) {
            if (!file) return;
            if (file.type !== 'application/pdf') {
                const toast = useToast();
                toast.error('Only PDF files are allowed for templates');
                return;
            }
            this.form.template_file = file;
            this.templateFileName = file.name;
            this.templatePreviewUrl = true; // Just to show the preview container
        },

        triggerTemplateFileInput() {
            document.getElementById("template-file-input").click();
        },

        async submit() {
            const toast = useToast();

            // Validate pricing method selection
            if (!this.pricingMethod) {
                toast.error('Please select a pricing method');
                return;
            }

            // Combine products and services into articles array
            this.form.articles = [
                ...this.productArticles.map(article => ({
                    id: article.id,
                    quantity: article.quantity
                })),
                ...this.serviceArticles.map(article => ({
                    id: article.id,
                    quantity: article.quantity
                }))
            ];

            // Validate actions before submission
            const actionError = this.validateActions();
            if (actionError) {
                toast.error(actionError);
                return;
            }

            const formData = new FormData();

            if (this.form.large_material_id && this.form.large_material_id.toString().startsWith('cat_')) {
                formData.append('large_material_category_id', this.form.large_material_id.replace('cat_', ''));
                formData.append('large_material_id', '');
            } else {
                formData.append('large_material_id', this.form.large_material_id || '');
                formData.append('large_material_category_id', '');
            }

            if (this.form.small_material_id && this.form.small_material_id.toString().startsWith('cat_')) {
                formData.append('small_material_category_id', this.form.small_material_id.replace('cat_', ''));
                formData.append('small_material_id', '');
            } else {
                formData.append('small_material_id', this.form.small_material_id || '');
                formData.append('small_material_category_id', '');
            }

            Object.entries(this.form).forEach(([key, value]) => {
                if ([
                    'large_material_id',
                    'large_material_category_id', 
                    'small_material_id',
                    'small_material_category_id',
                    'actions', 
                    'file', 
                    'articles', 
                    'template_file', 
                    'should_ask_questions'
                ].includes(key)) return;

                if (value === null || value === 'null' || value === '') {
                    formData.append(key, '');
                } else {
                    formData.append(key, value);
                }
            });

            // Append should_ask_questions as integer
            formData.append('should_ask_questions', this.form.should_ask_questions ? 1 : 0);

            // Append pricing method
            formData.append('by_quantity', this.pricingMethod === 'quantity' ? 1 : 0);
            formData.append('by_copies', this.pricingMethod === 'copies' ? 1 : 0);

            // Append file
            if (this.form.file instanceof File) {
                formData.append('file', this.form.file);
            }

            // Append template file if it exists
            if (this.form.template_file instanceof File) {
                formData.append('template_file', this.form.template_file);
            }

            // Append actions
            this.form.actions.forEach((action, index) => {
                formData.append(`actions[${index}][id]`, action.selectedAction);
                formData.append(`actions[${index}][quantity]`, action.quantity || 0);
                formData.append(`actions[${index}][isMaterialized]`, action.isMaterialized === null ? '' : action.isMaterialized);
            });

            // Append articles
            this.form.articles.forEach((article, index) => {
                formData.append(`articles[${index}][id]`, article.id);
                formData.append(`articles[${index}][quantity]`, article.quantity);
            });

            try {
                // Append multiple subcategories as array
                if (Array.isArray(this.form.subcategory_ids)) {
                    this.form.subcategory_ids.forEach((id, idx) => {
                        formData.append(`subcategory_ids[${idx}]`, id);
                    });
                }

                const response = await axios.post(route('catalog.store'), formData, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                });

                // If should_ask_questions is enabled and questions are selected, save them
                if (this.form.should_ask_questions && this.selectedQuestions.length > 0) {
                    await axios.post(`/questions/catalog-item/${response.data.catalog_item.id}`, {
                        question_ids: this.selectedQuestions
                    });
                }

                toast.success('Catalog item created successfully');
                this.$inertia.visit(route('catalog.index'));
            } catch (error) {
                if (error.response?.data?.errors) {
                    Object.values(error.response.data.errors).forEach(errors => {
                        errors.forEach(error => toast.error(error));
                    });
                } else {
                    toast.error('An error occurred while creating the catalog item');
                }
            }
        },

        triggerFileInput() {
            document.getElementById("file-input").click();
        },

        async fetchSubcategories() {
            try {
                const response = await axios.get(route('subcategories.index'));
                this.subcategories = response.data;
            } catch (error) {
                const toast = useToast();
                toast.error(this.$t('errorFetchingSubcategories'));
            }
        },

        handleSubcategoryCreated(subcategory) {
            this.subcategories.push(subcategory);
        },

        handleSubcategoryUpdated(subcategory) {
            const index = this.subcategories.findIndex(s => s.id === subcategory.id);
            if (index !== -1) {
                this.subcategories[index] = subcategory;
            }
        },

        handleSubcategoryDeleted(subcategoryId) {
            this.subcategories = this.subcategories.filter(s => s.id !== subcategoryId);
            if (this.form.subcategory_id === subcategoryId) {
                this.form.subcategory_id = null;
            }
        },

        handleLargeMaterialChange() {
            // If category is 'article' and large material is selected, deselect small material
            if (this.form.category === 'article' && this.form.large_material_id) {
                this.form.small_material_id = null;
            }
        },

        handleSmallMaterialChange() {
            // If category is 'article' and small material is selected, deselect large material
            if (this.form.category === 'article' && this.form.small_material_id) {
                this.form.large_material_id = null;
            }
        },
    },

    mounted() {
        this.addAction(); // Add first action row by default
        this.fetchSubcategories();
    }
}
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

// Additional styles for inline questions selector
.dark-gray {
    background-color: #1f2937;
}
.light-gray{
    background-color: $light-gray;
}
.sub-title{
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

.option, input, option, select {
    color: black;
}
.preview-image {
    width: 100px;
    height: 100px;
}

.file-upload {
    width: 100%;
}

.description-textarea {
    background-color: $light-gray;
    border: 2px dashed $ultra-light-gray;
    padding: 1rem;
    min-height: 200px;
    resize: none;
    transition: all 0.3s ease;

    &.compact {
        min-height: 180px;
        padding: 0.75rem;
    }

    &:hover, &:focus {
        border-color: $green;
        outline: none;
        background-color: rgba($light-gray, 0.7);
    }

    &::placeholder {
        color: $ultra-light-gray;
    }
}

.upload-area {
    background-color: $light-gray;
    border: 2px dashed $ultra-light-gray;
    border-radius: 8px;
    padding: 2rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    min-height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;

    &.compact {
        padding: 1.5rem;
        min-height: 180px;
    }

    &:hover {
        border-color: $green;
        background-color: rgba($light-gray, 0.7);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
}

.placeholder-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    color: $white;

    &.compact {
        gap: 0.25rem;
    }
}

.upload-icon {
    color: $ultra-light-gray;
    margin-bottom: 1rem;

    .compact & {
        margin-bottom: 0.5rem;
    }
}

.upload-text {
    font-size: 1.1rem;
    font-weight: 500;

    .compact & {
        font-size: 1rem;
    }
}

.upload-text-sub {
    font-size: 0.9rem;
    color: $ultra-light-gray;

    .compact & {
        font-size: 0.8rem;
    }
}

.file-types {
    font-size: 0.8rem;
    color: $ultra-light-gray;
    margin-top: 1rem;

    .compact & {
        font-size: 0.7rem;
        margin-top: 0.5rem;
    }
}

.preview-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;

    &.compact {
        gap: 0.5rem;
    }
}

.preview-image {
    max-width: 200px;
    max-height: 200px;
    border-radius: 4px;
    object-fit: contain;

    &.compact {
        max-width: 120px;
        max-height: 120px;
    }
}

.pdf-preview {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    color: $white;

    &.compact {
        gap: 0.25rem;
    }

    .mdi-file-pdf {
        color: #ff4444;
    }

    .pdf-name {
        font-size: 0.9rem;
        word-break: break-all;
        max-width: 200px;
        text-align: center;

        .compact & {
            font-size: 0.8rem;
            max-width: 120px;
        }
    }
}

.file-upload-section,
.description-section,
.template-section {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.description-section {
    .description-textarea {
        flex: 1;
    }
}

</style>

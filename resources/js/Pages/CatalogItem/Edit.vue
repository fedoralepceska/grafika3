<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <div class="flex justify-between align-center">
                <Header
                    title="Edit Catalog Item"
                    :subtitle="catalogItem.name"
                    icon="List.png"
                    :link="route('catalog.index')"
                    buttonText="Back to Catalog"
                />
                <div class="flex align-center py-5">
                    <button @click="$inertia.visit(route('catalog.index'))" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Catalog
                    </button>
                </div>
            </div>

            <div class="light-gray p-6 text-white">
                <form @submit.prevent="updateCatalogItem" class="space-y-8">
                    <!-- Basic Information -->
                    <div class="grid grid-cols-2 gap-8">
                        <div class="space-y-6">
                            <div>
                                <label class="text-white block mb-2 font-semibold">{{ $t('name') }}</label>
                                <input v-model="editForm.name" type="text" class="w-full rounded dark-gray text-white border-gray-600" required />
                            </div>

                            <div>
                                <label class="text-white block mb-2 font-semibold">{{ $t('description') }}</label>
                                <textarea
                                    v-model="editForm.description"
                                    class="w-full rounded dark-gray text-white border-gray-600"
                                    rows="3"
                                    placeholder="Enter item description..."
                                ></textarea>
                            </div>

                            <div>
                                <label class="text-white block mb-2 font-semibold">{{ $t('machineP') }}</label>
                                <select v-model="editForm.machinePrint" class="w-full rounded dark-gray text-white border-gray-600">
                                    <option value="">{{ $t('selectMachine') }}</option>
                                    <option v-for="machine in machinesPrint"
                                            :key="machine.id"
                                            :value="machine.name">
                                        {{ machine.name }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="text-white block mb-2 font-semibold">{{ $t('machineC') }}</label>
                                <select v-model="editForm.machineCut" class="w-full rounded dark-gray text-white border-gray-600">
                                    <option value="">{{ $t('selectMachine') }}</option>
                                    <option v-for="machine in machinesCut"
                                            :key="machine.id"
                                            :value="machine.name">
                                        {{ machine.name }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="text-white block mb-2 font-semibold">{{ $t('category') }}</label>
                                <select
                                    v-model="editForm.category"
                                    class="w-full rounded dark-gray text-white border-gray-600"
                                >
                                    <option value="">{{ $t('selectCategory') }}</option>
                                    <option value="material">Material</option>
                                    <option value="article">Article</option>
                                    <option value="small_format">Small Format</option>
                                </select>
                            </div>

                            <div>
                                <label class="text-white block mb-2 font-semibold">{{ $t('subcategory') }}</label>
                                <select
                                    v-model="editForm.subcategory_id"
                                    class="w-full rounded dark-gray text-white border-gray-600"
                                >
                                    <option value="">{{ $t('selectSubcategory') }}</option>
                                    <option
                                        v-for="subcategory in subcategories"
                                        :key="subcategory.id"
                                        :value="subcategory.id"
                                    >
                                        {{ subcategory.name }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="text-white block mb-2 font-semibold">{{ $t('materialLargeFormat') }}</label>
                                <select v-model="editForm.large_material_id"
                                        class="w-full rounded dark-gray text-white border-gray-600"
                                        :disabled="editForm.small_material_id !== null">
                                    <option value="">{{ $t('selectMaterial') }}</option>
                                    <option v-for="material in largeMaterials"
                                            :key="material.id"
                                            :value="String(material.id)">
                                        {{ material.name }}
                                    </option>
                                </select>
                                <button v-if="editForm.large_material_id"
                                        @click.prevent="clearLargeMaterial"
                                        class="text-sm text-red-500 mt-1">
                                    {{ $t('clearSelection') }}
                                </button>
                            </div>

                            <div>
                                <label class="text-white block mb-2 font-semibold">{{ $t('materialSmallFormat') }}</label>
                                <select v-model="editForm.small_material_id"
                                        class="w-full rounded dark-gray text-white border-gray-600"
                                        :disabled="editForm.large_material_id !== null">
                                    <option value="">{{ $t('selectMaterial') }}</option>
                                    <option v-for="material in smallMaterials"
                                            :key="material.id"
                                            :value="String(material.id)">
                                        {{ material.name }}
                                    </option>
                                </select>
                                <button v-if="editForm.small_material_id"
                                        @click.prevent="clearSmallMaterial"
                                        class="text-sm text-red-500 mt-1">
                                    {{ $t('clearSelection') }}
                                </button>
                            </div>

                            <div>
                                <label class="text-white block mb-2 font-semibold">{{ $t('defaultPrice') }}</label>
                                <input
                                    v-model="editForm.price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="w-full rounded dark-gray text-white border-gray-600"
                                    required
                                />
                            </div>
                        </div>

                        <!-- Right column with file upload -->
                        <div class="space-y-6">
                            <!-- File Section -->
                            <div class="file-upload">
                                <h3 class="text-white text-lg font-semibold mb-4">{{ $t('fileUpload') }} (PNG, JPG, JPEG)</h3>
                                <div
                                    class="upload-area"
                                    @dragover.prevent
                                    @drop.prevent="handleDrop"
                                    @click="triggerFileInput"
                                >
                                    <input
                                        type="file"
                                        id="file-input"
                                        class="hidden"
                                        @change="handleFileInput"
                                        accept=".png, .jpg, .jpeg"
                                    />
                                    <div v-if="!previewUrl && !currentItemFile" class="placeholder-content">
                                        <div class="upload-icon">
                                            <span class="mdi mdi-cloud-upload text-4xl"></span>
                                        </div>
                                        <p class="upload-text">{{ $t('dragAndDrop') }}</p>
                                        <p class="upload-text-sub">{{ $t('orClickToBrowse') }}</p>
                                        <p class="file-types">{{ $t('supportedFormats') }}: PNG, JPG, JPEG</p>
                                    </div>
                                    <div v-else class="preview-container">
                                        <img
                                            :src="previewUrl || getFileUrl(catalogItem.file)"
                                            alt="Preview"
                                            class="preview-image"
                                        />
                                        <div class="update-image-text">
                                            <p class="text-sm text-gray-400">{{ $t('clickOrDragToUpdateImage') }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Template File Section -->
                            <div>
                                <h3 class="text-white text-lg font-semibold mb-4">{{ $t('templateFilePdfOnly') }}</h3>
                                <div
                                    class="upload-area"
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
                                    <div v-if="!currentTemplateFile && !editForm.template_file" class="placeholder-content">
                                        <div class="upload-icon">
                                            <span class="mdi mdi-cloud-upload text-4xl"></span>
                                        </div>
                                        <p class="upload-text">{{ $t('dragAndDropTemplatePdfHere') }}</p>
                                        <p class="upload-text-sub">{{ $t('orClickToBrowse') }}</p>
                                        <p class="file-types">{{ $t('supportedFormats') }}: PDF</p>
                                    </div>
                                    <div v-else class="preview-container">
                                        <div class="pdf-preview">
                                            <span class="mdi mdi-file-pdf text-4xl text-red-500"></span>
                                            <span class="pdf-name">{{ getTemplateFileName(currentTemplateFile || catalogItem.template_file) }}</span>
                                        </div>
                                        <div class="template-actions mt-2">
                                            <button type="button" class="text-red-500 hover:text-red-700" @click.stop="removeTemplate">
                                                <i class="fas fa-trash mr-1"></i> {{ $t('removeTemplate') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Additional Options -->
                            <div class="p-4 dark-gray border-dashed border-2 border-gray-500 rounded">
                                <label class="text-white block mb-3 font-semibold">{{ $t('Additional options') }}</label>
                                <div class="space-y-3">
                                    <div class="flex items-center">
                                        <input
                                            type="checkbox"
                                            id="is_for_offer"
                                            v-model="editForm.is_for_offer"
                                            class="rounded border-gray-300 text-indigo-600"
                                        />
                                        <label for="is_for_offer" class="text-white ml-2">{{ $t('forOffer') }}</label>
                                    </div>
                                    <div class="flex items-center">
                                        <input
                                            type="checkbox"
                                            id="is_for_sales"
                                            v-model="editForm.is_for_sales"
                                            class="rounded border-gray-300 text-indigo-600"
                                        />
                                        <label for="is_for_sales" class="text-white ml-2">{{ $t('forSales') }}</label>
                                    </div>
                                    <div class="flex items-center">
                                        <input
                                            type="checkbox"
                                            id="should_ask_questions"
                                            v-model="editForm.should_ask_questions"
                                            class="rounded border-gray-300 text-indigo-600"
                                        />
                                        <label for="should_ask_questions" class="text-white ml-2">Ask questions before production</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Pricing Method Selection -->
                            <div class="p-4 dark-gray border-dashed border-2 border-gray-500 rounded">
                                <label class="text-white block mb-3 font-semibold">{{ $t('pricingMethod') }}</label>
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

                            <!-- Questions Selection -->
                            <div v-if="editForm.should_ask_questions" class="p-4 border-dashed border-2 border-gray-500 dark-gray">
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

                    <!-- Component Articles Section -->
                    <div class="bg-gray-800 p-6 ">
                        <h3 class="text-white text-xl font-semibold mb-6">{{ $t('componentArticles') }}</h3>

                        <!-- Products Section -->
                        <div class="mb-8">
                            <div class="flex justify-between items-center mb-4">
                                <h4 class="text-white text-lg font-medium">{{ $t('products') }}</h4>
                                <button
                                    type="button"
                                    @click="addArticle('product')"
                                    class="text-green-500 hover:text-green-700 flex items-center"
                                >
                                    <span class="mdi mdi-plus-circle mr-1"></span> {{ $t('addProduct') }}
                                </button>
                            </div>
                            <div class="space-y-4">
                                <div v-for="(article, index) in productArticles" :key="index"
                                     class="flex items-center space-x-4 dark-gray p-4 rounded">
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
                                            min="0.01"
                                            step="0.01"
                                            class="w-full rounded dark-gray text-white border-gray-500"
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
                        <div class="mb-8">
                            <div class="flex justify-between items-center mb-4">
                                <h4 class="text-white text-lg font-medium">{{ $t('services') }}</h4>
                                <button
                                    type="button"
                                    @click="addArticle('service')"
                                    class="text-green-500 hover:text-green-700 flex items-center"
                                >
                                    <span class="mdi mdi-plus-circle mr-1"></span> {{ $t('addService') }}
                                </button>
                            </div>
                            <div class="space-y-4">
                                <div v-for="(article, index) in serviceArticles" :key="index"
                                     class="flex items-center space-x-4 dark-gray p-4 rounded">
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
                                            min="0.01"
                                            step="0.01"
                                            class="w-full rounded dark-gray text-white border-gray-500"
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
                        <div class="p-4 dark-gray">
                            <h4 class="text-white text-lg font-medium mb-3">{{ $t('costSummary') }}</h4>
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

                    <!-- Actions Section -->
                    <div class="bg-gray-800 p-6">
                        <h3 class="text-white text-xl font-semibold mb-6">{{ $t('ACTIONS') }}</h3>
                        <div class="space-y-4">
                            <div v-for="(action, index) in editForm.actions" :key="index"
                                 class="flex items-center space-x-4 bg-gray-700 p-4 ">
                                <div class="flex-1">
                                    <select v-model="action.selectedAction" 
                                            class="w-full rounded dark-gray text-white border-gray-500"
                                            @change="handleActionChange(action)" required>
                                        <option value="">Select Action</option>
                                        <option v-for="availableAction in availableActions(action)"
                                                :key="availableAction.id"
                                                :value="availableAction.id">
                                            {{ availableAction.name }}
                                        </option>
                                    </select>
                                </div>
                                <div v-if="action.showQuantity" class="w-32">
                                    <input v-model="action.quantity" type="number" min="0"
                                           class="w-full rounded dark-gray text-white border-gray-500" 
                                           placeholder="Quantity" required />
                                </div>
                                <button type="button" @click="removeAction(index)"
                                        class="text-red-500 hover:text-red-700">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>

                            <button type="button" @click="addAction"
                                    class="text-green-500 hover:text-green-700">
                                <i class="fas fa-plus-circle"></i> {{ $t('addAction') }}
                            </button>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div class="flex justify-end space-x-4 pt-6">
                        <button 
                            type="button" 
                            @click="$inertia.visit(route('catalog.index'))" 
                            class="btn btn-secondary"
                        >
                            {{ $t('cancel') }}
                        </button>
                        <button type="submit" class="btn btn-primary" :disabled="loading">
                            <i v-if="loading" class="fas fa-spinner fa-spin mr-2"></i>
                            {{ loading ? $t('Updating') : $t('updateCatalogItem') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </MainLayout>
</template>

<script>
import MainLayout from "@/Layouts/MainLayout.vue";
import Header from "@/Components/Header.vue";
import { useToast } from "vue-toastification";
import CatalogArticleSelect from "@/Components/CatalogArticleSelect.vue";


export default {
    components: {
        MainLayout,
        Header,
        CatalogArticleSelect
    },
    props: {
        catalogItem: Object,
        actions: Array,
        largeMaterials: Array,
        smallMaterials: Array,
        machinesPrint: Array,
        machinesCut: Array,
        subcategories: Array,
        availableQuestions: Array,
    },
    data() {
        return {
            loading: false,
            previewUrl: null,
            currentTemplateFile: null,
            removeTemplateFlag: false,
            pricingMethod: 'quantity',
            productArticles: [],
            serviceArticles: [],
            selectedQuestions: [],
            editForm: {
                id: this.catalogItem.id,
                name: this.catalogItem.name,
                description: this.catalogItem.description,
                machinePrint: this.catalogItem.machinePrint,
                machineCut: this.catalogItem.machineCut,
                large_material_id: this.catalogItem.large_material_id ? String(this.catalogItem.large_material_id) : null,
                small_material_id: this.catalogItem.small_material_id ? String(this.catalogItem.small_material_id) : null,
                price: this.catalogItem.price,
                actions: this.catalogItem.actions?.map(action => ({
                    selectedAction: action.action_id?.id || action.id,
                    quantity: action.quantity,
                    showQuantity: action.quantity !== null,
                    action_id: action.action_id || action,
                    isMaterialized: action.isMaterialized
                })) || [],
                is_for_offer: this.catalogItem.is_for_offer,
                is_for_sales: this.catalogItem.is_for_sales,
                file: null,
                template_file: this.catalogItem.template_file,
                subcategory_id: this.catalogItem.subcategory_id,
                category: this.catalogItem.category,
                should_ask_questions: Boolean(this.catalogItem.should_ask_questions)
            }
        };
    },
    computed: {
        displayProductsCost() {
            return this.productArticles.reduce((total, article) => {
                return total + (article.purchase_price || 0) * (article.quantity || 0);
            }, 0);
        },
        displayServicesCost() {
            return this.serviceArticles.reduce((total, article) => {
                return total + (article.purchase_price || 0) * (article.quantity || 0);
            }, 0);
        },
        displayTotalCost() {
            return this.displayProductsCost + this.displayServicesCost;
        },

    },
    watch: {
        'editForm.should_ask_questions'(newValue) {
            if (!newValue) {
                // Clear questions when checkbox is unchecked
                this.selectedQuestions = [];
            }
        }
    },
    mounted() {
        // Set pricing method based on the item's pricing flags
        if (this.catalogItem.by_copies) {
            this.pricingMethod = 'copies';
        } else {
            this.pricingMethod = 'quantity';
        }

        // Set current template file
        this.currentTemplateFile = this.catalogItem.template_file;

        // Initialize selected questions from backend data
        if (this.catalogItem.questions && this.catalogItem.questions.length > 0) {
            this.selectedQuestions = this.catalogItem.questions.map(q => q.id);
        }

        // Initialize product and service articles from existing articles
        if (this.catalogItem.articles && Array.isArray(this.catalogItem.articles)) {
            this.productArticles = this.catalogItem.articles
                .filter(article => article.type === 'product')
                .map(article => ({
                    id: article.id,
                    name: article.name,
                    type: article.type,
                    purchase_price: article.purchase_price,
                    unitLabel: article.unit_label,
                    quantity: article.quantity
                }));

            this.serviceArticles = this.catalogItem.articles
                .filter(article => article.type === 'service')
                .map(article => ({
                    id: article.id,
                    name: article.name,
                    type: article.type,
                    purchase_price: article.purchase_price,
                    unitLabel: article.unit_label,
                    quantity: article.quantity
                }));
        }
    },
    methods: {
        clearLargeMaterial() {
            this.editForm.large_material_id = null;
        },

        clearSmallMaterial() {
            this.editForm.small_material_id = null;
        },

        availableActions(currentAction) {
            return this.actions.filter(action => {
                return !this.editForm.actions.some(selectedAction =>
                    selectedAction.selectedAction === action.id &&
                    selectedAction !== currentAction
                );
            });
        },

        addAction() {
            this.editForm.actions.push({
                selectedAction: '',
                quantity: 0,
                showQuantity: false
            });
        },

        removeAction(index) {
            this.editForm.actions.splice(index, 1);
        },

        handleActionChange(action) {
            if (!action.selectedAction) {
                action.showQuantity = false;
                return;
            }
            const selectedAction = this.actions.find(a => a.id === parseInt(action.selectedAction));
            action.showQuantity = selectedAction?.isMaterialized ?? false;
            if (!action.showQuantity) {
                action.quantity = 0;
            }
        },

        getFileUrl(file) {
            return file && file !== 'placeholder.jpeg'
                ? `/storage/uploads/${file}`
                : '/storage/uploads/placeholder.jpeg';
        },

        handleDrop(event) {
            const file = event.dataTransfer.files[0];
            this.processFile(file);
        },

        handleFileInput(event) {
            const file = event.target.files[0];
            this.processFile(file);
        },

        processFile(file) {
            if (!file) return;

            const maxSize = 20 * 1024 * 1024;
            if (file.size > maxSize) {
                const toast = useToast();
                toast.error('File size must be less than 20MB');
                return;
            }

            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
            if (!allowedTypes.includes(file.type)) {
                const toast = useToast();
                toast.error('Only JPG, JPEG, and PNG files are allowed');
                return;
            }

            this.editForm.file = file;
            this.previewUrl = URL.createObjectURL(file);
        },

        triggerFileInput() {
            document.getElementById("file-input").click();
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

            const maxSize = 20 * 1024 * 1024;
            if (file.size > maxSize) {
                const toast = useToast();
                toast.error('Template file size must be less than 20MB');
                return;
            }

            if (file.type !== 'application/pdf') {
                const toast = useToast();
                toast.error('Only PDF files are allowed for templates');
                return;
            }

            this.editForm.template_file = file;
            this.currentTemplateFile = file.name;
            this.removeTemplateFlag = false;
            
            const toast = useToast();
            toast.success('Template file selected.');
        },

        triggerTemplateFileInput() {
            document.getElementById("template-file-input").click();
        },

        removeTemplate() {
            this.editForm.template_file = null;
            this.currentTemplateFile = null;
            this.removeTemplateFlag = true;
            
            const toast = useToast();
            toast.info('Template will be removed when you save.');
        },

        getTemplateFileName(path) {
            if (!path) return '';
            
            if (path.startsWith('http')) {
                const url = new URL(path);
                const filename = url.pathname.split('/').pop();
                return filename.replace(/^\d+_/, '');
            }
            
            return path.replace(/^\d+_/, '');
        },

        async updateCatalogItem() {
            const toast = useToast();
            
            if (!this.pricingMethod) {
                toast.error('Please select a pricing method');
                return;
            }

            this.loading = true;

            try {
                const formData = new FormData();
                formData.append('_method', 'PUT');

                // Handle large material/category
                if (this.editForm.large_material_id && this.editForm.large_material_id.startsWith('cat_')) {
                    formData.append('large_material_category_id', this.editForm.large_material_id.replace('cat_', ''));
                    formData.append('large_material_id', '');
                } else {
                    formData.append('large_material_id', this.editForm.large_material_id || '');
                    formData.append('large_material_category_id', '');
                }

                // Handle small material/category
                if (this.editForm.small_material_id && this.editForm.small_material_id.startsWith('cat_')) {
                    formData.append('small_material_category_id', this.editForm.small_material_id.replace('cat_', ''));
                    formData.append('small_material_id', '');
                } else {
                    formData.append('small_material_id', this.editForm.small_material_id || '');
                    formData.append('small_material_category_id', '');
                }

                // Append other form fields
                Object.entries(this.editForm).forEach(([key, value]) => {
                    if ([
                        'large_material_id', 'large_material_category_id',
                        'small_material_id', 'small_material_category_id',
                        'actions', 'file', 'template_file', 'should_ask_questions'
                    ].includes(key)) return;
                    
                    if (key === 'price') {
                        formData.append(key, Number(value));
                    } else {
                        formData.append(key, value);
                    }
                });

                formData.append('should_ask_questions', this.editForm.should_ask_questions ? 1 : 0);
                formData.append('by_quantity', this.pricingMethod === 'quantity' ? 1 : 0);
                formData.append('by_copies', this.pricingMethod === 'copies' ? 1 : 0);
                
                // Always send quantity and copies as 1 (hidden from user)
                formData.append('quantity', 1);
                formData.append('copies', 1);
                
                formData.append('actions', JSON.stringify(this.editForm.actions));

                // Combine product and service articles
                const articles = [
                    ...this.productArticles.map(article => ({
                        id: article.id,
                        quantity: article.quantity
                    })),
                    ...this.serviceArticles.map(article => ({
                        id: article.id,
                        quantity: article.quantity
                    }))
                ];

                // Append articles as JSON
                formData.append('articles', JSON.stringify(articles));

                if (this.editForm.file) {
                    formData.append('file', this.editForm.file);
                }

                if (this.editForm.template_file instanceof File) {
                    formData.append('template_file', this.editForm.template_file);
                }

                if (this.removeTemplateFlag) {
                    formData.append('remove_template', '1');
                }

                await axios.post(`/catalog/${this.editForm.id}`, formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                });

                // Update catalog item questions if should_ask_questions is enabled
                if (this.editForm.should_ask_questions && this.selectedQuestions.length > 0) {
                    try {
                        await axios.post(`/questions/catalog-item/${this.editForm.id}`, {
                            question_ids: this.selectedQuestions
                        });
                    } catch (questionError) {
                        console.warn('Failed to update questions, but catalog item was saved:', questionError);
                        toast.warning('Catalog item saved, but failed to update questions');
                    }
                } else if (!this.editForm.should_ask_questions) {
                    // Clear questions if checkbox is unchecked
                    try {
                        await axios.post(`/questions/catalog-item/${this.editForm.id}`, {
                            question_ids: []
                        });
                    } catch (questionError) {
                        console.warn('Failed to clear questions:', questionError);
                    }
                }

                toast.success('Catalog item updated successfully');
                this.$inertia.visit(route('catalog.index'));
            } catch (error) {
                if (error.response?.data?.errors) {
                    Object.values(error.response.data.errors).forEach(errors => {
                        errors.forEach(error => toast.error(error));
                    });
                } else {
                    toast.error('An error occurred while updating the catalog item');
                }
            } finally {
                this.loading = false;
            }
        },

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
        },

        handleArticleSelected(article, index, type) {
            const targetArray = type === 'product' ? this.productArticles : this.serviceArticles;
            targetArray[index] = {
                ...targetArray[index],
                ...article,
                purchase_price: article.purchase_price || 0
            };
        },


    },
};
</script>

<style scoped lang="scss">
.btn {
    padding: 9px 12px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.light-gray {
    background-color: $light-gray;
}

.btn-primary {
    background-color: $green;
    color: white;

    &:hover {
        background-color: #326d08;
    }

    &:disabled {
        background-color: #6b7280;
        cursor: not-allowed;
    }
}

.btn-secondary {
    background-color: #6b7280;
    color: white;

    &:hover {
        background-color: #4b5563;
    }
}

.dark-gray {
    background-color: #1f2937;
}

.upload-area {
    background-color: $dark-gray;
    border: 2px dashed #6b7280;
    border-radius: 8px;
    padding: 2rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;

    &:hover {
        border-color: #10b981;
        background-color: rgba(16, 185, 129, 0.1);
    }
}

.placeholder-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    color: #d1d5db;
}

.upload-icon {
    color: #9ca3af;
    margin-bottom: 1rem;
}

.upload-text {
    font-size: 1.1rem;
    font-weight: 500;
}

.upload-text-sub {
    font-size: 0.9rem;
    color: #9ca3af;
}

.file-types {
    font-size: 0.8rem;
    color: #9ca3af;
    margin-top: 1rem;
}

.preview-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
}

.preview-image {
    max-width: 200px;
    max-height: 200px;
    border-radius: 4px;
    object-fit: contain;
}

.pdf-preview {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    color: #d1d5db;

    .pdf-name {
        font-size: 0.9rem;
        word-break: break-all;
        max-width: 200px;
    }
}

.update-image-text {
    margin-top: 0.5rem;
    text-align: center;
    font-style: italic;
}

// Smooth transition for questions selector
.slide-fade-enter-active,
.slide-fade-leave-active {
    transition: all 0.3s ease;
}

.slide-fade-enter-from {
    opacity: 0;
    transform: translateY(-10px);
}

.slide-fade-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}
</style> 
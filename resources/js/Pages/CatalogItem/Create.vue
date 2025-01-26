<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="Catalog" subtitle="createNewCatalogItem" icon="List.png" link="catalog"/>

            <div class="dark-gray p-2 text-white">
                <div class="form-container p-2 ">
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
                                <div>
                                    <label class="text-white">{{ $t('category') }}</label>
                                    <select
                                        v-model="form.category"
                                        class="w-full mt-1 rounded"
                                    >
                                        <option value="">{{ $t('selectCategory') }}</option>
                                        <option v-for="category in categories"
                                                :key="category"
                                                :value="category"
                                        >
                                            {{ category.replace('_', ' ').split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ') }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label class="text-white">{{ $t('materialLargeFormat') }}</label>
                                    <select
                                        v-model="form.large_material_id"
                                        class="w-full mt-1 rounded"
                                        :disabled="form.small_material_id !== null"
                                    >
                                        <option value="">{{ $t('selectMaterial') }}</option>
                                        <option v-for="material in largeMaterials"
                                                :key="material.id"
                                                :value="material.id">
                                            {{ material.article?.name }} ({{ material.article?.code }})
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label class="text-white">{{ $t('materialSmallFormat') }}</label>
                                    <select
                                        v-model="form.small_material_id"
                                        class="w-full mt-1 rounded"
                                        :disabled="form.large_material_id !== null"
                                    >
                                        <option value="">{{ $t('selectMaterial') }}</option>
                                        <option v-for="material in smallMaterials"
                                                :key="material.id"
                                                :value="material.id">
                                            {{ material.article?.name }} ({{ material.article?.code }})
                                        </option>
                                    </select>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-white">{{ $t('quantity') }}</label>
                                        <input
                                            v-model="form.quantity"
                                            type="number"
                                            min="1"
                                            class="w-full mt-1 rounded"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label class="text-white">{{ $t('copies') }}</label>
                                        <input
                                            v-model="form.copies"
                                            type="number"
                                            min="1"
                                            class="w-full mt-1 rounded"
                                            required
                                        />
                                    </div>
                                </div>
                                <div>
                                    <label class="text-white">{{ $t('defaultPrice') }}</label>
                                    <input
                                        v-model="form.price"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="w-full mt-1 rounded"
                                        required
                                    />
                                </div>
                                <div class="grid grid-cols-2 gap-4 pt-9">
                                    <div>
                                        <Checkbox name="is_for_offer" v-model:checked="form.is_for_offer" />
                                        <label class="text-white ml-2">{{ $t('forOffer') }}</label>
                                    </div>
                                    <div>
                                        <Checkbox name="is_for_sales" v-model:checked="form.is_for_sales" />
                                        <label class="text-white ml-2">{{ $t('forSales') }}</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Articles Section -->
                        <div class="mt-6">
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
                                                min="0.01"
                                                step="0.01"
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
                                                min="0.01"
                                                step="0.01"
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
                            <div class="mt-4 p-4 bg-gray-700 rounded">
                                <h4 class="text-white text-md font-medium mb-3">{{ $t('costSummary') }}</h4>
                                <div class="space-y-2">
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-300">{{ $t('productsCost') }}:</span>
                                        <span class="text-white">€{{ displayProductsCost.toFixed(2) }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-300">{{ $t('servicesCost') }}:</span>
                                        <span class="text-white">€{{ displayServicesCost.toFixed(2) }}</span>
                                    </div>
                                    <div class="flex justify-between items-center pt-2 border-t border-gray-600">
                                        <span class="text-white font-semibold">{{ $t('totalCostPrice') }}:</span>
                                        <span class="text-white font-semibold">€{{ displayTotalCost.toFixed(2) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- File and Description Section -->
                        <div class="grid grid-cols-2 gap-6 mt-6">
                            <div class="file-upload">
                                <h3 class="text-white text-lg font-semibold mb-4">{{ $t('fileUpload') }}</h3>
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
                                        accept=".pdf, .png, .jpg, .jpeg"
                                    />
                                    <div v-if="!previewUrl" class="placeholder-content">
                                        <div class="upload-icon">
                                            <span class="mdi mdi-cloud-upload text-4xl"></span>
                                        </div>
                                        <p class="upload-text">{{ $t('dragAndDrop') }}</p>
                                        <p class="upload-text-sub">{{ $t('orClickToBrowse') }}</p>
                                        <p class="file-types">{{ $t('supportedFormats') }}: PDF, PNG, JPG, JPEG</p>
                                    </div>
                                    <div v-else class="preview-container">
                                        <img
                                            v-if="isImage"
                                            :src="previewUrl"
                                            alt="Preview"
                                            class="preview-image"
                                        />
                                        <div v-else class="pdf-preview">
                                            <span class="mdi mdi-file-pdf text-4xl"></span>
                                            <span class="pdf-name">{{ fileName }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="description-section">
                                <h3 class="text-white text-lg font-semibold mb-4">{{ $t('description') }}</h3>
                                <textarea
                                    v-model="form.description"
                                    class="w-full rounded description-textarea"
                                    rows="11"
                                    placeholder="Enter item description..."
                                ></textarea>
                            </div>
                        </div>

                        <!-- Template File Section -->
                        <div class="mt-6">
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
                                <div v-if="!templatePreviewUrl" class="placeholder-content">
                                    <div class="upload-icon">
                                        <span class="mdi mdi-cloud-upload text-4xl"></span>
                                    </div>
                                    <p class="upload-text">{{ $t('dragAndDropTemplatePdfHere') }}</p>
                                    <p class="upload-text-sub">{{ $t('orClickToBrowse') }}</p>
                                    <p class="file-types">{{ $t('supportedFormats') }}: PDF</p>
                                </div>
                                <div v-else class="preview-container">
                                    <div class="pdf-preview">
                                        <span class="mdi mdi-file-pdf text-4xl"></span>
                                        <span class="pdf-name">{{ templateFileName }}</span>
                                    </div>
                                </div>
                            </div>
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

export default {
    components: {
        MainLayout,
        Header,
        Link,
        Checkbox,
        CatalogArticleSelect
    },

    props: {
        actions: Array,
        largeMaterials: Array,
        smallMaterials: Array,
        machinesPrint: Array,
        machinesCut: Array,
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
                price: '',
                articles: [],
                template_file: null,
            },
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

            // Append basic fields
            Object.entries(this.form).forEach(([key, value]) => {
                if (key !== 'actions' && key !== 'file' && key !== 'articles' && key !== 'template_file') {
                    formData.append(key, value);
                }
            });

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
                const response = await axios.post(route('catalog.store'), formData, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                });

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
    },

    mounted() {
        this.addAction(); // Add first action row by default
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
    min-height: 250px;
    resize: none;
    transition: all 0.3s ease;

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

    &:hover {
        border-color: $green;
        background-color: rgba($light-gray, 0.7);
    }
}

.placeholder-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    color: $white;
}

.upload-icon {
    color: $ultra-light-gray;
    margin-bottom: 1rem;
}

.upload-text {
    font-size: 1.1rem;
    font-weight: 500;
}

.upload-text-sub {
    font-size: 0.9rem;
    color: $ultra-light-gray;
}

.file-types {
    font-size: 0.8rem;
    color: $ultra-light-gray;
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
    color: $white;

    .mdi-file-pdf {
        color: #ff4444;
    }

    .pdf-name {
        font-size: 0.9rem;
        word-break: break-all;
        max-width: 200px;
    }
}

</style>

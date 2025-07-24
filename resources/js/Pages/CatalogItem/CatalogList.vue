<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <div class="flex justify-between align-center">
                <Header
                    title="Catalog"
                    subtitle="All Catalog Items"
                    icon="List.png"
                    link="catalog"
                    buttonText="Create New Item"
                />
                <div class="flex align-center py-5">
                    <button @click="navigateToCatalogCreate" class="btn create-order2">
                        Create New Item
                    </button>
                </div>
            </div>

        <div class="dark-gray p-2 text-white">
            <div class="form-container p-2 ">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="sub-title">{{ $t('catalogItems') }}</h2>
                </div>
                <div class="flex gap-4 mb-4">
                    <div class="flex-1">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search by name, description or subcategory..."
                            class="rounded p-2 bg-gray-700 text-white w-full"
                            style="width: 420px;"
                            @input="debouncedSearch"
                            />
                    </div>
                    <div class="w-48">
                        <select
                            v-model="filters.category"
                            class="rounded p-2 bg-gray-700 text-white w-full"
                            @change="applyFilters"
                        >
                            <option value="">{{ $t('allCategories') }}</option>
                            <option v-for="category in categories"
                                    :key="category"
                                    :value="category"
                            >
                                {{ category.replace('_', ' ').split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ') }}
                            </option>
                        </select>
                    </div>
                    <div class="w-48">
                        <select
                            v-model="filters.subcategory_id"
                            class="rounded p-2 bg-gray-700 text-white w-full"
                            @change="applyFilters"
                        >
                            <option value="">{{ $t('allSubcategories') }}</option>
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
                        <button 
                            @click="clearFilters"
                            class="btn btn-clear px-4 "
                        >
                            {{ $t('clearFilters') }}
                        </button>
                    </div>
                </div>

                <table class="w-full text-left border-collapse">
                    <thead>
                    <tr class="bg-gray-700 text-white">
                        <th class="p-4">{{ $t('preview') }}</th>
                        <th class="p-4">{{ $t('name') }}</th>
                        <th class="p-4">{{ $t('template') }}</th>
                        <th class="p-4">{{ $t('subcategory') }}</th>
                        <th class="p-4">{{ $t('material') }}</th>
                        <th v-if="canViewPrice" class="p-4">{{ $t('defaultPrice') }}</th>
                        <th v-if="canViewPrice" class="p-4">{{ $t('clientPrices') }}</th>
                        <th v-if="canViewPrice" class="p-4">{{ $t('quantityPrices') }}</th>
                        <th class="p-4">{{ $t('options') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr
                        v-for="item in catalogItems"
                        :key="item.id"
                        class="bg-gray-800 text-white border-t border-gray-700"
                    >
                        <td class="p-4">
                            <div class="jobImg-container">
                                <img
                                    v-if="!isPlaceholder(item.file)"
                                    :src="getFileUrl(item.file)"
                                    alt="Item Preview"
                                    class="jobImg thumbnail"
                                    style="cursor: pointer;"
                                />
                                <div v-else class="jobImg thumbnail no-image">
                                    NO IMAGE
                                </div>
                            </div>
                        </td>
                        <td class="p-4">{{ item.name || 'N/A' }}</td>

                        <td class="p-4 relative">
                            <div v-if="item.template_file" class="template-container">
                                <span class="template-name">{{ getTemplateFileName(item.template_file) }}</span>
                                <div class="template-actions">
                                    <button
                                        @click="openTemplatePreview(item)"
                                        class="action-button text-blue-400 hover:text-blue-300"
                                        title="Preview Template"
                                    >
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <a
                                        @click.prevent="downloadTemplate(item)"
                                        href="#"
                                        class="action-button text-green-400 hover:text-green-300 ml-2"
                                        title="Download Template"
                                    >
                                        <i class="fas fa-download"></i>
                                    </a>
                                </div>
                            </div>
                            <span v-else class="text-gray-500">{{ $t('noTemplate') }}</span>
                        </td>
                        <td class="p-4">{{ item.subcategory_name || 'N/A' }}</td>
                        <td class="p-4">
                            {{ item.material || 'N/A' }}
                        </td>
                        <td v-if="canViewPrice" class="p-4">{{ formatPrice(item.price) }}</td>
                        <td v-if="canViewPrice" class="p-4">
                            <button @click="openClientPricesDialog(item)" class="btn btn-secondary">
                                <i class="fas fa-users"></i> {{ $t('manage') }}
                            </button>
                        </td>
                        <td v-if="canViewPrice" class="p-4">
                            <button @click="openQuantityPricesDialog(item)" class="btn btn-secondary">
                                <i class="fas fa-layer-group"></i> {{ $t('manage') }}
                            </button>
                        </td>

                        <td class="p-4 text-center">
                            <div class="flex space-x-2">
                                <button @click="openEditDialog(item)" class="btn btn-secondary">
                                    <i class="fas fa-edit"></i> {{ $t('Edit') }}
                                </button>
                                <button @click="deleteCatalogItem(item.id)" class="btn btn-danger">
                                    <i class="fas fa-trash"></i> {{ $t('Delete') }}
                                </button>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <div class="flex w-full  justify-between items-center mt-4">
                    <div class="flex gap-2">
                        <button
                            :disabled="!pagination.links?.prev"
                            @click="() => fetchCatalogItems(1)"
                            class="btn btn-secondary"
                            :class="{ 'opacity-50 cursor-not-allowed': !pagination.links?.prev }"
                        >
                        <i class="fas fa-angle-double-left"></i>
                        </button>
                        <button
                            :disabled="!pagination.links?.prev"
                            @click="() => fetchCatalogItems(pagination.current_page - 1)"
                            class="btn btn-secondary"
                            :class="{ 'opacity-50 cursor-not-allowed': !pagination.links?.prev }"
                        >
                            {{ $t('previous') }}
                        </button>
                    </div>
                    <span class="text-white">
                        {{ $t('page') }} {{ pagination.current_page }} {{ $t('of') }} {{ pagination.last_page }}
                    </span>
                    <div class="flex gap-2">
                        <button
                            :disabled="!pagination.links?.next"
                            @click="() => fetchCatalogItems(pagination.current_page + 1)"
                            class="btn btn-secondary"
                            :class="{ 'opacity-50 cursor-not-allowed': !pagination.links?.next }"
                        >
                            {{ $t('next') }}
                        </button>
                        <button
                            :disabled="!pagination.links?.next"
                            @click="() => fetchCatalogItems(pagination.last_page)"
                            class="btn btn-secondary"
                            :class="{ 'opacity-50 cursor-not-allowed': !pagination.links?.next }"
                        >
                            <i class="fas fa-angle-double-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        </div>
        <!-- Actions Dialog -->
        <div v-if="selectedItem" class="modal-backdrop">
            <div class="modal">
                <div class="modal-header">
                    <h2>{{ selectedItem.name }} {{ $t('ACTIONS') }}</h2>
                    <button @click="closeActionsDialog" class="close-button">&times;</button>
                </div>
                <div class="modal-body">
                    <div v-if="selectedItem.actions && selectedItem.actions.length">
                        <ul>
                            <li v-for="(action, index) in selectedItem.actions" :key="index" class="option">
                                {{ action.action_id.name }}
                            </li>
                        </ul>
                    </div>
                    <div v-else>
                        <p>{{ $t('noActionsAvailableForThisItem') }}.</p>
                    </div>
                </div>
            </div>
        </div>
        <!-- Edit Dialog -->
        <div v-if="showEditDialog" class="modal-backdrop">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Edit Catalog Item</h2>
                    <button @click="closeEditDialog" class="close-button">&times;</button>
                </div>
                <div class="modal-body">
                    <form @submit.prevent="updateCatalogItem" class="space-y-6">
                        <!-- Basic Information -->
                        <div class="grid grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <div>
                                    <label class="text-white">{{ $t('name') }}</label>
                                    <input v-model="editForm.name" type="text" class="w-full mt-1 rounded" required />
                                </div>

                                <div>
                                    <label class="text-white">{{ $t('description') }}</label>
                                    <textarea
                                        v-model="editForm.description"
                                        class="w-full mt-1 rounded"
                                        rows="3"
                                        placeholder="Enter item description..."
                                    ></textarea>
                                </div>

                                <div>
                                    <label class="text-white">{{ $t('machineP') }}</label>
                                    <select v-model="editForm.machinePrint" class="w-full mt-1 rounded">
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
                                    <select v-model="editForm.machineCut" class="w-full mt-1 rounded">
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
                                        v-model="editForm.category"
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

                                <div>
                                    <label class="text-white">{{ $t('subcategory') }}</label>
                                    <select
                                        v-model="editForm.subcategory_id"
                                        class="w-full mt-1 rounded"
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
                                    <label class="text-white">{{ $t('materialLargeFormat') }}</label>
                                    <select v-model="editForm.large_material_id"
                                            class="w-full mt-1 rounded"
                                            :disabled="editForm.small_material_id !== null">

                                        <option v-for="material in largeMaterials"
                                                :key="material.id"
                                                :value="String(material.id)"
                                                :disabled="material.disabled">
                                            <template v-if="material.type === 'category'">
                                                <img v-if="material.icon" :src="`/storage/icons/${material.icon}`" alt="icon" style="width: 18px; height: 18px; margin-right: 4px;" />
                                                [{{ $t('category') }}] {{ material.name }}
                                            </template>
                                            <template v-else>
                                                {{ material.name }}
                                            </template>
                                        </option>
                                    </select>
                                    <button v-if="editForm.large_material_id"
                                            @click.prevent="clearLargeMaterial"
                                            class="text-sm text-red-500">
                                        {{ $t('clearSelection') }}
                                    </button>
                                </div>

                                <div>
                                    <label class="text-white">{{ $t('materialSmallFormat') }}</label>
                                    <select v-model="editForm.small_material_id"
                                            class="w-full mt-1 rounded"
                                            :disabled="editForm.large_material_id !== null">
                                        <option v-for="material in smallMaterials"
                                                :key="material.id"
                                                :value="String(material.id)"
                                                :disabled="material.disabled">
                                            <template v-if="material.type === 'category'">
                                                <img v-if="material.icon" :src="`/storage/icons/${material.icon}`" alt="icon" style="width: 18px; height: 18px; margin-right: 4px;" />
                                                [{{ $t('category') }}] {{ material.name }}
                                            </template>
                                            <template v-else>
                                                {{ material.name }}
                                            </template>
                                        </option>
                                    </select>
                                    <button v-if="editForm.small_material_id"
                                            @click.prevent="clearSmallMaterial"
                                            class="text-sm text-red-500">
                                        {{ $t('clearSelection') }}
                                    </button>
                                </div>

                                <!-- <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-white">{{ $t('quantity') }}</label>
                                        <input v-model="editForm.quantity" type="number" min="1"
                                               class="w-full mt-1 rounded" required />
                                    </div>
                                    <div>
                                        <label class="text-white">{{ $t('copies') }}</label>
                                        <input v-model="editForm.copies" type="number" min="1"
                                               class="w-full mt-1 rounded" required />
                                    </div>
                                </div> -->
                                <div v-if="canViewPrice">
                                    <label class="text-white">{{ $t('defaultPrice') }}</label>
                                    <input
                                        v-model="editForm.price"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="w-full mt-1 rounded"
                                        required
                                    />
                                </div>
                            </div>

                            <!-- Right column with file upload -->
                            <div class="space-y-4">
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
                                            id="edit-file-input"
                                            class="hidden"
                                            @change="handleFileInput"
                                            accept=".pdf, .png, .jpg, .jpeg"
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
                                                v-if="isImage"
                                                :src="previewUrl || '/storage/uploads/placeholder.jpeg'"
                                                alt="Preview"
                                                class="preview-image"
                                            />
                                            <div v-else class="pdf-preview">
                                                <span class="mdi mdi-file-pdf text-4xl"></span>
                                                <span class="pdf-name">{{ fileName || currentItemFile }}</span>
                                            </div>
                                            <div class="update-image-text">
                                                <p class="text-sm text-ultra-light-gray">{{ $t('clickOrDragToUpdateImage') }}</p>
                                            </div>
                                        </div>
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
                                            id="edit-template-file-input"
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
                                                <span class="mdi mdi-file-pdf text-4xl"></span>
                                                <span class="pdf-name">{{ getTemplateFileName(currentTemplateFile) }}</span>
                                            </div>
                                            <div class="template-actions mt-2">
                                                <button type="button" class="text-red-500 hover:text-red-700" @click.stop="removeTemplate">
                                                    <i class="fas fa-trash mr-1"></i> {{ $t('removeTemplate') }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-2 border-dashed border-2 border-gray-500">
                                    <div>
                                        <label class="text-white block mb-2 font-bold">{{ $t('Additional options') }}</label>
                                    </div>
                                    <div class="flex items-center gap-8">
                                    <div>
                                        <input
                                            type="checkbox"
                                            id="is_for_offer"
                                            v-model="editForm.is_for_offer"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        />
                                        <label for="is_for_offer" class="text-white ml-2">{{ $t('forOffer') }}</label>
                                    </div>
                                    <div>
                                        <input
                                            type="checkbox"
                                            id="is_for_sales"
                                            v-model="editForm.is_for_sales"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        />
                                        <label for="is_for_sales" class="text-white ml-2">{{ $t('forSales') }}</label>
                                    </div>
                                    </div>
                                    <div class="col-span-2 mt-2">
                                        <input
                                            type="checkbox"
                                            id="should_ask_questions"
                                            v-model="editForm.should_ask_questions"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        />
                                        <label for="should_ask_questions" class="text-white ml-2">Ask questions before production</label>
                                    </div>
                                </div>

                                <!-- Pricing Method Selection -->
                                <div class="mt-4 p-2 border-dashed border-2 border-gray-500">
                                    <label class="text-white block mb-2 font-bold">{{ $t('pricingMethod') }}</label>
                                    <div class="space-y-2">
                                        <div class="flex items-center">
                                            <input
                                                type="radio"
                                                id="edit_pricing_quantity"
                                                name="edit_pricing_method"
                                                value="quantity"
                                                v-model="editPricingMethod"
                                                class="mr-2"
                                            />
                                            <label for="edit_pricing_quantity" class="text-white">{{ $t('priceByQuantity') }}</label>
                                        </div>
                                        <div class="flex items-center">
                                            <input
                                                type="radio"
                                                id="edit_pricing_copies"
                                                name="edit_pricing_method"
                                                value="copies"
                                                v-model="editPricingMethod"
                                                class="mr-2"
                                            />
                                            <label for="edit_pricing_copies" class="text-white">{{ $t('priceByCopies') }}</label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Questions Selection -->
                                <QuestionsSelector
                                    v-model="selectedQuestions"
                                    :should-ask-questions="editForm.should_ask_questions"
                                    :catalog-item-id="editForm.id"
                                />

                            </div>
                        </div>

                        <!-- Component Articles Section -->
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
                                                style="color: black;"
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
                                                style="color: black;"
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
                            <div v-if="canViewPrice" class="mt-4 p-4 bg-gray-700 rounded">
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

                        <!-- Actions Section -->
                        <div class="mt-6">
                            <h3 class="text-white text-lg font-semibold mb-4">{{ $t('ACTIONS') }}</h3>
                            <div class="space-y-4">
                                <div v-for="(action, index) in editForm.actions" :key="index"
                                     class="flex items-center space-x-4 bg-gray-700 p-4 rounded">
                                    <div class="flex-1">
                                        <select v-model="action.selectedAction" class="w-full rounded"
                                                @change="handleActionChange(action)" required>
                                            <option value="">Select Action</option>
                                            <option v-for="availableAction in availableActionsForEdit(action)"
                                                    :key="availableAction.id"
                                                    :value="availableAction.id">
                                                {{ availableAction.name }}
                                            </option>
                                        </select>
                                    </div>
                                    <div v-if="action.showQuantity" class="w-32">
                                        <input v-model="action.quantity" type="number" min="0"
                                               class="w-full rounded" placeholder="Quantity" required />
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

                        <div class="flex justify-end space-x-4">
                            <button type="submit" class="btn btn-primary">
                                {{ $t('updateCatalogItem') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Client Prices Dialog -->
        <div v-if="showClientPricesDialog" class="modal-backdrop">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>{{ $t('manageClientPrices') }} - {{ selectedItemForPrices?.name }}</h2>
                    <button @click="closeClientPricesDialog" class="close-button">&times;</button>
                </div>
                <div class="modal-body">
                    <!-- Add new client price form -->
                    <form @submit.prevent="saveClientPrice" class="mb-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-white">Client</label>
                                <select v-model="clientPriceForm.client_id" class="w-full mt-1 rounded" required>
                                    <option value="">Select Client</option>
                                    <option v-for="client in clients" :key="client.id" :value="client.id">
                                        {{ client.name }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="text-white">{{ $t('price') }}</label>
                                <input
                                    v-model="clientPriceForm.price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="w-full mt-1 rounded"
                                    required
                                />
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">{{ $t('addClientPrice') }}</button>
                        </div>
                    </form>

                    <!-- Client prices list -->
                    <div class="mt-6">
                        <h3 class="text-white text-lg font-semibold mb-4">{{ $t('currentClientPrices') }}</h3>
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-700">
                                    <th class="p-2 text-left text-white">{{ $t('client') }}</th>
                                    <th class="p-2 text-left text-white">{{ $t('price') }}</th>
                                    <th class="p-2 text-center text-white">{{ $t('ACTIONS') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="price in clientPrices" :key="price.id" class="border-t border-gray-700">
                                    <td class="p-2 text-white">{{ price.client.name }}</td>
                                    <td class="p-2 text-white">{{ formatPrice(price.price) }}</td>
                                    <td class="p-2 text-center">
                                        <button @click="deleteClientPrice(price.id)" class="btn btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <!-- Client Prices Pagination -->
                        <div class="flex justify-between items-center mt-4" v-if="clientPricesPagination.last_page > 1">
                            <button
                                :disabled="clientPricesPagination.current_page === 1"
                                @click="loadClientPrices(clientPricesPagination.current_page - 1)"
                                class="btn btn-secondary"
                            >
                                {{ $t('previous') }}
                            </button>
                            <span class="text-white">
                                {{ $t('page') }} {{ clientPricesPagination.current_page }} {{ $t('of') }} {{ clientPricesPagination.last_page }}
                            </span>
                            <button
                                :disabled="clientPricesPagination.current_page === clientPricesPagination.last_page"
                                @click="loadClientPrices(clientPricesPagination.current_page + 1)"
                                class="btn btn-secondary"
                            >
                                {{ $t('next') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quantity Prices Dialog -->
        <div v-if="showQuantityPricesDialog" class="modal-backdrop">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>{{ $t('manageQuantityPrices') }} - {{ selectedItemForPrices?.name }}</h2>
                    <button @click="closeQuantityPricesDialog" class="close-button">&times;</button>
                </div>
                <div class="modal-body">
                    <!-- Add new quantity price form -->
                    <form @submit.prevent="saveQuantityPrice" class="mb-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-white">{{ $t('client') }}</label>
                                <select
                                    v-model="quantityPriceForm.client_id"
                                    class="w-full mt-1 rounded"
                                    required
                                    @change="handleClientChange"
                                >
                                    <option value="">Select Client</option>
                                    <option v-for="client in clients" :key="client.id" :value="client.id">
                                        {{ client.name }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="text-white">{{ $t('price') }}</label>
                                <input
                                    v-model="quantityPriceForm.price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="w-full mt-1 rounded"
                                    required
                                />
                            </div>
                            <div>
                                <label class="text-white">{{ $t('quantityFrom') }}</label>
                                <input
                                    v-model="quantityPriceForm.quantity_from"
                                    type="number"
                                    min="1"
                                    class="w-full mt-1 rounded"
                                />
                            </div>
                            <div>
                                <label class="text-white">{{ $t('quantityTo') }}</label>
                                <input
                                    v-model="quantityPriceForm.quantity_to"
                                    type="number"
                                    min="1"
                                    class="w-full mt-1 rounded"
                                />
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">{{ $t('addQuantityPrice') }}</button>
                        </div>
                    </form>

                    <!-- Quantity prices list -->
                    <div class="mt-6">
                        <h3 class="text-white text-lg font-semibold mb-4">{{ $t('currentQuantityPrices') }}</h3>
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-700">
                                    <th class="p-2 text-left text-white">{{ $t('client') }}</th>
                                    <th class="p-2 text-left text-white">{{ $t('quantityRange') }}</th>
                                    <th class="p-2 text-left text-white">{{ $t('price') }}</th>
                                    <th class="p-2 text-center text-white">{{ $t('ACTIONS') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="price in quantityPrices" :key="price.id" class="border-t border-gray-700">
                                    <td class="p-2 text-white">{{ price.client.name }}</td>
                                    <td class="p-2 text-white">
                                        {{ price.quantity_from || '0' }} - {{ price.quantity_to || '∞' }}
                                    </td>
                                    <td class="p-2 text-white">{{ formatPrice(price.price) }}</td>
                                    <td class="p-2 text-center">
                                        <button @click="deleteQuantityPrice(price.id)" class="btn btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <!-- Quantity Prices Pagination -->
                        <div class="flex justify-between items-center mt-4" v-if="quantityPricesPagination.last_page > 1">
                            <button
                                :disabled="quantityPricesPagination.current_page === 1"
                                @click="loadQuantityPrices(quantityPricesPagination.current_page - 1)"
                                class="btn btn-secondary"
                            >
                                {{ $t('previous') }}
                            </button>
                            <span class="text-white">
                                {{ $t('page') }} {{ quantityPricesPagination.current_page }} {{ $t('of') }} {{ quantityPricesPagination.last_page }}
                            </span>
                            <button
                                :disabled="quantityPricesPagination.current_page === quantityPricesPagination.last_page"
                                @click="loadQuantityPrices(quantityPricesPagination.current_page + 1)"
                                class="btn btn-secondary"
                            >
                                {{ $t('next') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Template Preview Dialog -->
        <div v-if="showTemplatePreviewDialog" class="modal-backdrop" @click="closeTemplatePreview">
            <div class="modal-content" @click.stop>
                <div class="modal-header">
                    <h2 class="text-xl font-semibold">{{ $t('templatePreview') }}</h2>
                    <button @click="closeTemplatePreview" class="close-button">&times;</button>
                </div>
                <div class="modal-body">
                    <embed
                        :src="templatePreviewUrl"
                        type="application/pdf"
                        width="100%"
                        height="600px"
                    />
                </div>
            </div>
        </div>

        <!-- Delete Verification Dialog -->
        <div v-if="showDeleteDialog" class="modal-backdrop" @click="closeDeleteDialog">
            <div class="modal-content" @click.stop>
                <div class="modal-header">
                    <h2 class="text-xl font-semibold">Delete Catalog Item</h2>
                    <button @click="closeDeleteDialog" class="close-button">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="space-y-4">
                        <div>
                            <p class="text-white mb-4 text-center">
                                Are you sure you want to delete "{{ selectedItem?.name }}"?
                                <br>
                                <span class="text-sm text-gray-400">
                                    This will hide it from the catalog list, but it will still be available in existing offers.
                                </span>
                            </p>
                            <p class="text-white mb-4 text-center">
                                Please enter the 4-digit security code to confirm deletion.
                            </p>
                            
                            <!-- PIN Input Boxes -->
                            <div class="flex justify-center space-x-2 mb-4">
                                <input 
                                    ref="pin1"
                                    v-model="pin.digit1" 
                                    type="text" 
                                    maxlength="1"
                                    class="w-12 h-12 text-center text-xl bg-gray-600 border border-light-gray rounded-md text-white"
                                    @input="onPinInput(0)"
                                    @keydown="handleKeyDown($event, 0)"
                                />
                                <input 
                                    ref="pin2"
                                    v-model="pin.digit2" 
                                    type="text" 
                                    maxlength="1"
                                    class="w-12 h-12 text-center text-xl bg-gray-600 border border-light-gray rounded-md text-white"
                                    @input="onPinInput(1)"
                                    @keydown="handleKeyDown($event, 1)"
                                />
                                <input 
                                    ref="pin3"
                                    v-model="pin.digit3" 
                                    type="text" 
                                    maxlength="1"
                                    class="w-12 h-12 text-center text-xl bg-gray-600 border border-light-gray rounded-md text-white"
                                    @input="onPinInput(2)"
                                    @keydown="handleKeyDown($event, 2)"
                                />
                                <input 
                                    ref="pin4"
                                    v-model="pin.digit4" 
                                    type="text" 
                                    maxlength="1"
                                    class="w-12 h-12 text-center text-xl bg-gray-600 border border-light-gray rounded-md text-white"
                                    @input="onPinInput(3)"
                                    @keydown="handleKeyDown($event, 3)"
                                />
                            </div>
                            
                            <p v-if="deleteCodeError" class="text-red-500 text-sm mt-2 text-center">
                                {{ deleteCodeError }}
                            </p>
                        </div>

                        <div class="flex justify-end space-x-2">
                            <button
                                @click="closeDeleteDialog"
                                class="px-4 py-2 btn-secondary text-white"
                            >
                                Cancel
                            </button>
                            <button
                                @click="confirmDelete"
                                class="px-4 py-2 btn-danger text-white"
                            >
                                Delete Catalog Item
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script>
import MainLayout from "@/Layouts/MainLayout.vue";
import Header from "@/Components/Header.vue";
import { Link } from "@inertiajs/vue3";
import { useToast } from "vue-toastification";
import CatalogArticleSelect from "@/Components/CatalogArticleSelect.vue";
import QuestionsSelector from "@/Components/QuestionsSelector.vue";
import debounce from 'lodash.debounce';     
import { computed } from 'vue';

export default {
    components: {
        MainLayout,
        Header,
        Link,
        CatalogArticleSelect,
        QuestionsSelector
    },
    props: {
        catalogItems: Array,
        pagination: Object,
        canViewPrice: Boolean,
    },
    data() {
        return {
            searchQuery: "",
            debouncedSearch: null,
            selectedItem: null,
            showEditDialog: false,
            showClientPricesDialog: false,
            showQuantityPricesDialog: false,
            showTemplatePreview: false,
            templatePreviewUrl: null,
            selectedItemForPrices: null,
            clients: [],
            clientPrices: [],
            quantityPrices: [],
            clientPricesPagination: {
                current_page: 1,
                last_page: 1,
                total: 0,
                per_page: 10
            },
            clientPriceForm: {
                client_id: null,
                price: 0
            },
            quantityPriceForm: {
                client_id: null,
                quantity_from: null,
                quantity_to: null,
                price: 0
            },
            editForm: {
                id: null,
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
                price: 0,
                file: null,
                template_file: null,
                subcategory_id: null,
                should_ask_questions: false
            },
            productArticles: [],
            serviceArticles: [],
            previewUrl: null,
            currentTemplateFile: null,
            removeTemplateFlag: false,
            machinesPrint: [],
            machinesCut: [],
            largeMaterials: [],
            smallMaterials: [],
            actions: [],
            fileName: '',
            currentItemFile: null,
            isImage: true,
            categories: ['material', 'article', 'small_format'],
            quantityPricesPagination: {
                current_page: 1,
                total: 0,
                per_page: 5,
                last_page: 1
            },
            hoveredItemId: null,
            shouldShowPreviewOnTop: false,
            removeTemplateFlag: false,
            showTemplatePreviewDialog: false,
            subcategories: [],
            filters: {
                category: '',
                subcategory_id: ''
            },
            editPricingMethod: 'quantity', // Default to quantity-based pricing
            showDeleteDialog: false,
            deleteCodeError: '',
            pin: {
                digit1: '',
                digit2: '',
                digit3: '',
                digit4: ''
            },
            selectedQuestions: [],
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
    methods: {
        fetchCatalogItems(page) {
            this.$inertia.get(
                route('catalog.index'),
                {
                    search: this.searchQuery,
                    page: page || this.pagination.current_page,
                    category: this.filters.category,
                    subcategory_id: this.filters.subcategory_id
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    only: ['catalogItems', 'pagination']
                }
            );
        },

        openActionsDialog(item) {
            this.selectedItem = item;
        },

        closeActionsDialog() {
            this.selectedItem = null;
        },

        async deleteCatalogItem(id) {
            // Find the catalog item by ID
            const item = this.catalogItems.find(item => item.id === id);
            if (!item) return;
            
            this.selectedItem = item;
            this.deleteCodeError = '';
            this.resetPin();
            this.showDeleteDialog = true;
            
            // Focus the first input after the dialog is shown
            this.$nextTick(() => {
                this.$refs.pin1.focus();
            });
        },

        openEditDialog(item) {
            this.editForm = {
                id: item.id,
                name: item.name,
                description: item.description,
                machinePrint: item.machinePrint,
                machineCut: item.machineCut,
                large_material_id: item.large_material_id ? String(item.large_material_id) : null,
                small_material_id: item.small_material_id ? String(item.small_material_id) : null,
                quantity: item.quantity,
                copies: item.copies,
                category: item.category,
                price: item.price,
                actions: item.actions.map(action => ({
                    selectedAction: action.action_id.id,
                    quantity: action.quantity,
                    showQuantity: action.quantity !== null,
                    action_id: action.action_id,
                    isMaterialized: action.isMaterialized
                })),
                is_for_offer: item.is_for_offer,
                is_for_sales: item.is_for_sales,
                file: null,
                template_file: item.template_file,
                subcategory_id: item.subcategory_id,
                should_ask_questions: Boolean(item.should_ask_questions)
            };

            // Set pricing method based on the item's pricing flags
            if (item.by_copies) {
                this.editPricingMethod = 'copies';
            } else {
                this.editPricingMethod = 'quantity';
            }

            // Initialize product and service articles from existing articles
            this.productArticles = item.articles
                .filter(article => article.type === 'product')
                .map(article => ({
                    id: article.id,
                    name: article.name,
                    type: article.type,
                    purchase_price: article.purchase_price,
                    unitLabel: article.unit_label,
                    quantity: article.quantity
                }));

            this.serviceArticles = item.articles
                .filter(article => article.type === 'service')
                .map(article => ({
                    id: article.id,
                    name: article.name,
                    type: article.type,
                    purchase_price: article.purchase_price,
                    unitLabel: article.unit_label,
                    quantity: article.quantity
                }));

            if (item.file && item.file !== 'placeholder.jpeg') {
                this.previewUrl = `/storage/uploads/${item.file}`;
            }

            this.currentTemplateFile = item.template_file;
            this.removeTemplateFlag = false;

            this.showEditDialog = true;
        },

        closeEditDialog() {
            this.showEditDialog = false;
            this.editForm = {
                id: null,
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
                price: 0,
                file: null,
                template_file: null,
                subcategory_id: null,
                should_ask_questions: false
            };
            this.productArticles = [];
            this.serviceArticles = [];
            this.previewUrl = null;
            this.currentTemplateFile = null;
            this.removeTemplateFlag = false;
            this.editPricingMethod = 'quantity';
            this.selectedQuestions = [];
        },

        clearLargeMaterial() {
            this.editForm.large_material_id = null;
        },

        clearSmallMaterial() {
            this.editForm.small_material_id = null;
        },

        availableActionsForEdit(currentAction) {
            return this.actions.filter(action => {
                return !this.editForm.actions.some(selectedAction =>
                    selectedAction.selectedAction === action.id &&
                    selectedAction !== currentAction
                );
            });
        },

        async updateCatalogItem() {
            const toast = useToast();

            // Validate pricing method selection
            if (!this.editPricingMethod) {
                toast.error('Please select a pricing method');
                return;
            }

            try {
                const formData = new FormData();

                // Add method override for PUT request
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

                // Append all other form fields except material ids (already handled)
                Object.entries(this.editForm).forEach(([key, value]) => {
                    if ([
                        'large_material_id',
                        'large_material_category_id',
                        'small_material_id',
                        'small_material_category_id',
                        'actions',
                        'file',
                        'template_file',
                        'should_ask_questions'
                    ].includes(key)) return;
                    // Convert price to number if it's the price field
                    if (key === 'price') {
                        formData.append(key, Number(value));
                    } else {
                        formData.append(key, value);
                    }
                });

                // Append should_ask_questions as integer
                formData.append('should_ask_questions', this.editForm.should_ask_questions ? 1 : 0);

                // Append pricing method
                formData.append('by_quantity', this.editPricingMethod === 'quantity' ? 1 : 0);
                formData.append('by_copies', this.editPricingMethod === 'copies' ? 1 : 0);

                // Append actions as JSON
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

                // Append file if it exists
                if (this.editForm.file) {
                    formData.append('file', this.editForm.file);
                }

                // Append template file if it exists
                if (this.editForm.template_file instanceof File) {
                    formData.append('template_file', this.editForm.template_file);
                }

                // Handle template file removal
                if (this.removeTemplateFlag) {
                    formData.append('remove_template', '1');
                }

                await axios.post(`/catalog/${this.editForm.id}`, formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                });

                // Update catalog item questions if should_ask_questions is enabled
                if (this.editForm.should_ask_questions) {
                    await axios.post(`/questions/catalog-item/${this.editForm.id}`, {
                        question_ids: this.selectedQuestions
                    });
                }

                toast.success('Catalog item updated successfully');
                this.closeEditDialog();
                this.fetchCatalogItems();
            } catch (error) {
                if (error.response?.data?.errors) {
                    Object.values(error.response.data.errors).forEach(errors => {
                        errors.forEach(error => toast.error(error));
                    });
                } else {
                    toast.error('An error occurred while updating the catalog item');
                }
            }
        },

        async loadFormData() {
            try {
                // Fetch machines, actions, and subcategories data (materials are loaded in mounted())
                const [machinesPrintRes, machinesCutRes, actionsRes, subcategoriesRes] = await Promise.all([
                    axios.get('/get-machines-print'),
                    axios.get('/get-machines-cut'),
                    axios.get('/get-actions'),
                    axios.get(route('subcategories.index'))
                ]);

                this.machinesPrint = machinesPrintRes.data;
                this.machinesCut = machinesCutRes.data;
                this.actions = actionsRes.data;
                this.subcategories = subcategoriesRes.data;
            } catch (error) {
                console.error('Error loading form data:', error);
                const toast = useToast();
                toast.error('Failed to load form data');
            }
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
            this.fileName = file.name;

            if (file.type.startsWith("image/")) {
                this.isImage = true;
                this.previewUrl = URL.createObjectURL(file);
            }
        },

        triggerFileInput() {
            document.getElementById("edit-file-input").click();
        },

        isPlaceholder(file) {
            return !file || file === 'placeholder.jpeg';
        },

        getEditFileUrl(itemId) {
            return this.previewUrl || (this.currentItemFile && this.currentItemFile !== 'placeholder.jpeg'
                ? `/storage/uploads/${this.currentItemFile}`
                : '/storage/uploads/placeholder.jpeg');
        },

        formatPrice(price) {
            if (!price) return '0.00 ден';
            return new Intl.NumberFormat('mk-MK', {
                style: 'currency',
                currency: 'MKD'
            }).format(price);
        },

        async openClientPricesDialog(item) {
            this.selectedItemForPrices = item;
            try {
                const [clientsResponse] = await Promise.all([
                    axios.get('/api/clients/all'),
                ]);
                this.clients = clientsResponse.data;
                await this.loadClientPrices(1);
                this.showClientPricesDialog = true;
            } catch (error) {
                const toast = useToast();
                toast.error('Failed to load client prices');
                console.error('Error loading client prices:', error);
            }
        },

        async openQuantityPricesDialog(item) {
            this.selectedItemForPrices = item;
            try {
                const clientsResponse = await axios.get('/api/clients/all');
                this.clients = clientsResponse.data;
                this.quantityPrices = []; // Reset prices until client is selected
                this.showQuantityPricesDialog = true;
            } catch (error) {
                const toast = useToast();
                toast.error('Failed to load clients');
                console.error('Error loading clients:', error);
            }
        },

        async loadClientPrices(page = 1) {
            try {
                const response = await axios.get(`/catalog-items/${this.selectedItemForPrices.id}/client-prices`, {
                    params: { page }
                });
                this.clientPrices = response.data.data;
                this.clientPricesPagination = {
                    current_page: response.data.current_page,
                    total: response.data.total,
                    per_page: response.data.per_page,
                    last_page: response.data.last_page
                };
            } catch (error) {
                const toast = useToast();
                toast.error('Failed to load client prices');
                console.error('Error loading client prices:', error);
            }
        },

        async loadQuantityPrices(page = 1) {
            if (!this.quantityPriceForm.client_id || !this.selectedItemForPrices) return;

            try {
                const response = await axios.get(
                    `/catalog-items/${this.selectedItemForPrices.id}/clients/${this.quantityPriceForm.client_id}/quantity-prices`,
                    { params: { page } }
                );
                this.quantityPrices = response.data.data;
                this.quantityPricesPagination = {
                    current_page: response.data.current_page,
                    total: response.data.total,
                    per_page: response.data.per_page,
                    last_page: response.data.last_page
                };
            } catch (error) {
                console.error('Error details:', error.response?.data || error);
                const toast = useToast();
                toast.error('Failed to load quantity prices');
            }
        },

        async handleClientChange() {
            console.log('Client changed to:', this.quantityPriceForm.client_id);
            await this.loadQuantityPrices();
        },

        closeClientPricesDialog() {
            this.showClientPricesDialog = false;
            this.selectedItemForPrices = null;
            this.clientPriceForm = {
                client_id: null,
                price: 0
            };
        },

        closeQuantityPricesDialog() {
            this.showQuantityPricesDialog = false;
            this.selectedItemForPrices = null;
            this.quantityPrices = [];
            this.quantityPriceForm = {
                client_id: null,
                quantity_from: null,
                quantity_to: null,
                price: 0
            };
        },

        async saveClientPrice() {
            try {
                await axios.post('/client-prices', {
                    catalog_item_id: this.selectedItemForPrices.id,
                    ...this.clientPriceForm
                });
                await this.loadClientPrices(this.clientPricesPagination.current_page);
                this.clientPriceForm = {
                    client_id: null,
                    price: 0
                };
                const toast = useToast();
                toast.success('Client price saved successfully');
            } catch (error) {
                const toast = useToast();
                toast.error('Failed to save client price');
                console.error('Error saving client price:', error);
            }
        },

        async saveQuantityPrice() {
            try {
                await axios.post('/quantity-prices/single', {
                    catalog_item_id: this.selectedItemForPrices.id,
                    ...this.quantityPriceForm
                });
                await this.loadQuantityPrices(this.quantityPricesPagination.current_page);
                const currentClientId = this.quantityPriceForm.client_id;
                this.quantityPriceForm = {
                    client_id: currentClientId,
                    quantity_from: null,
                    quantity_to: null,
                    price: 0
                };
                const toast = useToast();
                toast.success('Quantity price saved successfully');
            } catch (error) {
                const toast = useToast();
                toast.error('Failed to save quantity price');
                console.error('Error saving quantity price:', error);
            }
        },

        async deleteClientPrice(priceId) {

            try {
                await axios.delete(`/client-prices/${priceId}`);
                await this.loadClientPrices(this.clientPricesPagination.current_page);
                const toast = useToast();
                toast.success('Client price deleted successfully');
            } catch (error) {
                const toast = useToast();
                toast.error('Failed to delete client price');
                console.error('Error deleting client price:', error);
            }
        },

        async deleteQuantityPrice(priceId) {
            try {
                if (!priceId) {
                    console.error('No price ID provided for deletion');
                    return;
                }
                
                const response = await axios.delete(`/quantity-prices/${priceId}`);
                
                if (response.status === 200 || response.status === 204) {
                    await this.loadQuantityPrices(this.quantityPricesPagination.current_page);
                    
                    const toast = useToast();
                    toast.success('Quantity price deleted successfully');
                } else {
                    throw new Error('Unexpected response status');
                }
            } catch (error) {
                console.error('Error deleting quantity price:', error);
                try {
                    await this.loadQuantityPrices(this.quantityPricesPagination.current_page);
                    const toast = useToast();
                    toast.success('Quantity price deleted successfully');
                } catch (reloadError) {
                    const toast = useToast();
                    toast.error('Failed to delete quantity price');
                }
            }
        },

        getTemplateFileName(path) {
            if (!path) return '';
            
            // Handle R2 URLs - extract filename from URL
            if (path.startsWith('http')) {
                const url = new URL(path);
                const filename = url.pathname.split('/').pop();
                // Remove timestamp prefix (numbers followed by underscore)
                return filename.replace(/^\d+_/, '');
            }
            
            // Handle local paths - remove timestamp prefix (numbers followed by underscore)
            return path.replace(/^\d+_/, '');
        },
        
        openTemplatePreview(item) {
            // Always use backend route for preview to handle authentication
            this.templatePreviewUrl = route('catalog.download-template', item.id);
            this.showTemplatePreviewDialog = true;
        },
        
        // Use backend route for all downloads to handle authentication
        downloadTemplate(item) {
            // Always use backend route for downloads
            window.open(route('catalog.download-template', item.id), '_blank');
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

            // Check file size (20MB = 20 * 1024 * 1024 bytes)
            const maxSize = 20 * 1024 * 1024;
            if (file.size > maxSize) {
                const toast = useToast();
                toast.error('Template file size must be less than 20MB');
                return;
            }

            // Check file type
            if (file.type !== 'application/pdf') {
                const toast = useToast();
                toast.error('Only PDF files are allowed for templates');
                return;
            }

            this.editForm.template_file = file;
            this.currentTemplateFile = file.name;
            this.removeTemplateFlag = false;
            
            const toast = useToast();
            toast.success('Template file selected. It will be uploaded when you save the catalog item.');
        },

        triggerTemplateFileInput() {
            document.getElementById("edit-template-file-input").click();
        },

        removeTemplate() {
            const toast = useToast();
            
            this.editForm.template_file = null;
            this.currentTemplateFile = null;
            this.removeTemplateFlag = true;
            
            toast.info('Template will be removed when you save the catalog item');
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
        navigateToCatalogCreate() {
            this.$inertia.visit(route('catalog.create'));
        },
        applyFilters() {
            this.fetchCatalogItems(1);
        },
        clearFilters() {
            this.filters = {
                category: '',
                subcategory_id: ''
            };
            this.fetchCatalogItems(1);
        },
        closeTemplatePreview() {
            this.showTemplatePreviewDialog = false;
            this.templatePreviewUrl = null;
        },

        closeDeleteDialog() {
            this.showDeleteDialog = false;
            this.selectedItem = null;
            this.deleteCodeError = '';
            this.resetPin();
        },

        resetPin() {
            this.pin = {
                digit1: '',
                digit2: '',
                digit3: '',
                digit4: ''
            };
        },

        onPinInput(index) {
            const pinRefs = [this.$refs.pin1, this.$refs.pin2, this.$refs.pin3, this.$refs.pin4];
            
            // Move to next input if current input has a value
            if (index < 3 && pinRefs[index].value) {
                pinRefs[index + 1].focus();
            }
            
            // Clear error when user starts typing again
            this.deleteCodeError = '';
        },

        handleKeyDown(event, index) {
            const pinRefs = [this.$refs.pin1, this.$refs.pin2, this.$refs.pin3, this.$refs.pin4];
            
            // Handle backspace to go to previous input
            if (event.key === 'Backspace') {
                if (index > 0 && !pinRefs[index].value) {
                    pinRefs[index - 1].focus();
                }
            }
            
            // Handle delete key
            if (event.key === 'Delete') {
                pinRefs[index].value = '';
            }
            
            // Handle arrow keys
            if (event.key === 'ArrowLeft' && index > 0) {
                pinRefs[index - 1].focus();
            }
            if (event.key === 'ArrowRight' && index < 3) {
                pinRefs[index + 1].focus();
            }
        },

        confirmDelete() {
            // Combine the PIN digits
            const enteredPin = `${this.pin.digit1}${this.pin.digit2}${this.pin.digit3}${this.pin.digit4}`;
            
            // Check if the verification code is correct (7412)
            if (enteredPin !== '7412') {
                this.deleteCodeError = 'Incorrect security code. Please try again.';
                this.resetPin();
                // Focus the first input after error
                this.$nextTick(() => {
                    this.$refs.pin1.focus();
                });
                return;
            }
            
            // If code is correct, proceed with deletion
            this.deleteCatalogItemConfirmed();
        },

        async deleteCatalogItemConfirmed() {
            const toast = useToast();
            
            if (!this.selectedItem) return;
            
            try {
                await axios.delete(route("catalog.destroy", this.selectedItem.id));
                
                toast.success('Catalog item deleted successfully');
                this.closeDeleteDialog();
                
                // Refresh the catalog items list
                this.fetchCatalogItems();
            } catch (error) {
                console.error('Error deleting catalog item:', error);
                toast.error('Failed to delete the catalog item. Please try again.');
            }
        },
    },
    async mounted() {
        // Initialize debounced search
        this.debouncedSearch = debounce(() => {
            this.fetchCatalogItems(1);
        }, 300);
        
        // Fetch large and small materials for dropdowns
        try {
            const [largeRes, smallRes] = await Promise.all([
                axios.get('/api/materials/large-dropdown'),
                axios.get('/api/materials/small-dropdown')
            ]);
            this.largeMaterials = largeRes.data;
            this.smallMaterials = smallRes.data;
        } catch (error) {
            console.error('Error fetching materials:', error);
            const toast = useToast();
            toast.error('Failed to load materials');
        }
        this.loadFormData();
    },
};
</script>

<style scoped lang="scss">
.btn {
    padding: 9px 12px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    border-radius: 2px;
    background-color: $green;
    color: white;
}
.create-order2 {
    background-color: $blue;
    color: white;
}
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
.sub-title{
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    color: $white;
}
table {
    width: 100%;
    border-collapse: collapse;
}
thead th {
    padding: 10px;

    color: white;
    background-color: $gray;
}
tbody td {
    padding: 10px;
    border-top: 1px solid #2d3748;
}
.btn {
    padding: 8px 12px;
    border-radius: 4px;
    font-weight: bold;
}
.btn-secondary {
    background-color: #4a5568;
    color: white;
}
.btn-clear{
    background-color: $light-gray;
    color: white;
}
.btn-clear:hover{
    background-color: $gray;
}
.btn-secondary:disabled {
    background-color: #2d3748;
    cursor: not-allowed;
}
.btn-danger {
    background-color: #e53e3e;
    color: white;
}

.btn-secondary {
    background-color: #4a5568;
    color: white;
}

.w-12 {
    width: 3rem;
}

.h-12 {
    height: 3rem;
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
    background-color: #1a202c;
    width: 500px;
    max-height: 80%;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.2rem;
    border-bottom: 1px solid #4a5568;
    color: white;
}

.close-button {
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
}

.close-button:hover {
    color: #e53e3e;
}

.modal-body {
    padding: 1rem;
}

.option {
    color: white;
}

.modal-content {
    background-color: $dark-gray;
    width: 80%;
    max-width: 1000px;
    max-height: 90vh;
    overflow-y: auto;
    border-radius: 8px;
    padding: 20px;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;

    h2 {
        color: white;
        font-size: 1.5rem;
    }
}

.file-upload {
    width: 100%;
    max-width: 400px;
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

.update-image-text {
    margin-top: 0.5rem;
    text-align: center;
    font-style: italic;
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

.template-container {
    display: flex;
    align-items: center;
    gap: 8px;
}

.template-name {
    color: white;
}

.template-actions {
    display: flex;
    align-items: center;
}

.action-button {
    padding: 4px;
    cursor: pointer;
    transition: transform 0.2s ease;

    &:hover {
        transform: scale(1.1);
    }
}

.template-preview,
.preview-top {
    display: none;
}
</style>

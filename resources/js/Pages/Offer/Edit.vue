<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="Offer" subtitle="Edit Offer" icon="List.png" link="offers" />

            <div class="dark-gray p-2 text-white mb-10">
                <div class="form-container p-2">
                    <h2 class="sub-title">Edit Offer</h2>

                    <!-- Progress Steps -->
                    <div class="w-full mb-3">
                        <div class="flex justify-between relative">
                            <div class="progress-bar absolute top-1/2 transform -translate-y-1/2 h-1 bg-gray-600" :style="{ width: '100%' }"></div>
                            <div class="progress-bar absolute top-1/2 transform -translate-y-1/2 h-1 bg-green transition-all duration-300" :style="{ width: progressWidth }"></div>
                            <div v-for="(step, index) in steps" :key="index" 
                                class="step-indicator pt-6 relative z-10 flex flex-col items-center">
                                <div :class="['w-8 h-8 rounded-full flex items-center justify-center transition-colors duration-300', 
                                    currentStep > index ? 'bg-green' : currentStep === index ? 'bg-green' : 'bg-gray-600']">
                                    <span class="text-white text-sm">{{ index + 1 }}</span>
                                </div>
                                <span class="mt-2 text-sm" :class="currentStep >= index ? 'text-white' : 'text-gray-400'">
                                    {{ step }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <form @submit.prevent="submit" class="space-y-6 w-full rounded-lg">
                        <!-- Navigation Buttons at the top -->
                        <div class="w-full flex justify-between pb-4 border-b border-gray-700">
                            <button 
                                type="button" 
                                v-if="currentStep > 0" 
                                @click="currentStep--"
                                :disabled="isSubmitting"
                                class="btn btn-secondary disabled:opacity-50 disabled:cursor-not-allowed">
                                Previous
                            </button>
                            <div class="flex-grow"></div>
                            <button 
                                v-if="currentStep < steps.length - 1" 
                                type="button"
                                @click="nextStep"
                                :disabled="isSubmitting"
                                class="btn btn-primary disabled:opacity-50 disabled:cursor-not-allowed">
                                Next
                            </button>
                            <button 
                                v-else 
                                type="submit"
                                :disabled="isSubmitting"
                                class="btn btn-primary disabled:opacity-50 disabled:cursor-not-allowed relative">
                                <span :class="{ 'opacity-0': isSubmitting }">Update Offer</span>
                                <div v-if="isSubmitting" class="absolute inset-0 flex items-center justify-center">
                                    <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                            </button>
                        </div>

                        <!-- Step 1: Basic Information -->
                        <div v-show="currentStep === 0">
                            <div class="grid grid-cols-1 gap-6">
                                <div class="grid grid-cols-2 gap-6">
                                    <div class="space-y-4">
                                        <div>
                                            <label class="text-white">Client <span class="text-red-500">*</span></label>
                                            <select
                                                v-model="form.client_id"
                                                class="w-full mt-1 rounded text-black"
                                                required
                                                @change="onClientSelect"
                                            >
                                                <option value="" disabled>Select a client</option>
                                                <option v-for="client in clients" :key="client.id" :value="client.id">
                                                    {{ client.name }}
                                                </option>
                                            </select>
                                        </div>
                                        <div v-if="form.client_id !== ''">
                                            <label for="contact" class="text-white">{{ $t('contact') }}: <span class="text-red-500">*</span></label>
                                            <select v-model="form.contact_id" id="contact" class="w-full mt-1 rounded text-black" required>
                                                <option v-for="contact in selectedClient?.contacts" :key="contact?.id" :value="contact?.id">
                                                    {{ contact?.name }} ({{ contact?.phone }})
                                                </option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="text-white">Name <span class="text-red-500">*</span></label>
                                            <input
                                                v-model="form.name"
                                                type="text"
                                                class="w-full mt-1 rounded text-black"
                                                required
                                            />
                                        </div>
                                        <div>
                                            <label class="text-white">Description</label>
                                            <textarea
                                                v-model="form.description"
                                                class="w-full mt-1 rounded text-black"
                                                rows="3"
                                                placeholder="Enter offer description"
                                            ></textarea>
                                        </div>
                                    </div>
                                    <div class="space-y-4">
                                        <div>
                                            <label class="text-white">Validity (Days) <span class="text-red-500">*</span></label>
                                            <input
                                                v-model.number="form.validity_days"
                                                type="number"
                                                min="1"
                                                class="w-full mt-1 rounded text-black"
                                                required
                                            />
                                        </div>
                                        <div>
                                            <label class="text-white">{{ $t('productionTime') }} <span class="text-red-500">*</span></label>
                                            <input
                                                v-model="form.production_time"
                                                type="text"
                                                class="w-full mt-1 rounded text-black"
                                                required
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Items Selection -->
                        <div v-show="currentStep === 1">
                            <!-- Selected Items Summary -->
                            <div v-if="form.catalog_items.length > 0" class="bg-gray-800 p-3 rounded-lg mb-4">
                                <div class="flex items-center justify-between mb-3">
                                    <h3 class="text-white text-sm font-medium">Selected Items</h3>
                                    <span class="bg-green px-2 py-0.5 rounded text-xs text-white">{{ form.catalog_items.length }} items</span>
                                </div>
                                <div class="divide-y divide-gray-700">
                                    <div v-for="(item, index) in form.catalog_items" :key="item.selection_id" class="py-2">
                                        <div class="flex items-center gap-4">
                                            <!-- Name and Description -->
                                            <div class="flex-1 min-w-0 pb-2">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-white text-sm font-medium">{{ index + 1 }}.</span>
                                                    <h4 class="text-white text-sm font-medium truncate">{{ item.name }}</h4>
                                                    <span v-if="item.isCustomItem" class="bg-blue-500 text-white text-xs px-2 py-0.5 rounded">Custom</span>
                                                    <input
                                                        v-model="item.description"
                                                        type="text"
                                                        class="flex-1 rounded bg-white border-gray-700 text-white text-sm py-1 px-2"
                                                        placeholder="Add description..."
                                                    />
                                                </div>
                                            </div>

                                            <!-- Quantity -->
                                            <div class="flex items-center gap-2 min-w-[120px] pb-2">
                                                <label class="text-gray-400 text-xs whitespace-nowrap">Qty:</label>
                                                <input
                                                    v-model.number="item.quantity"
                                                    type="number"
                                                    min="1"
                                                    class="w-20 rounded bg-white border-gray-700 text-white text-sm py-1 px-2"
                                                    required
                                                    @change="onQuantityChange(item)"
                                                    @keydown.enter.prevent="onQuantityChange(item)"
                                                    @keydown.right.enter.prevent="onQuantityChange(item)"
                                                />
                                            </div>

                                            <!-- Price -->
                                            <div class="flex items-center gap-2 min-w-[200px] pb-2">
                                                <label class="text-gray-400 text-xs whitespace-nowrap">Price:</label>
                                                <div class="relative flex-1">
                                                    <input
                                                        v-model.number="item.custom_price"
                                                        type="number"
                                                        min="0"
                                                        step="0.01"
                                                        class="w-full rounded bg-white border-gray-700 text-white text-sm py-1 px-2"
                                                        :class="{'border-green-500': item.isCustomPrice || item.isCustomItem}"
                                                        placeholder="Price per unit"
                                                        @input="handleCustomPriceChange(item)"
                                                        @keydown.enter.prevent="updatePrice(item)"
                                                    />
                                                    <div v-if="item.calculated_price" class="absolute -bottom-4 left-0 text-gray-400 text-xs whitespace-nowrap">
                                                        Total: {{ item.calculated_price }} ден 
                                                        <span v-if="item.isCustomPrice || item.isCustomItem" class="text-green-500">(Manual)</span>
                                                        <span v-else>({{ (item.calculated_price / item.quantity).toFixed(2) }} per unit)</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Remove Button -->
                                            <button
                                                @click="removeItem(item)"
                                                class="text-gray-400 hover:text-red-400 transition-colors p-1 pb-3"
                                                title="Remove Item"
                                            >
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Catalog Items Selection -->
                            <div class="w-full">
                                <div class="flex items-center justify-between mb-2">
                                    <div>
                                        <label class="text-white">Catalog Items</label>
                                        <p class="text-sm text-gray-400">
                                            You can add the same item multiple times with different quantities and descriptions.
                                        </p>
                                    </div>
                                    <button
                                        @click="openCustomItemModal"
                                        class="btn bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm"
                                    >
                                        <i class="fas fa-plus mr-2"></i>Add Custom Item
                                    </button>
                                </div>
                                <div class="catalog-tabs">
                                    <!-- Search Input -->
                                    <div class="px-4 py-2 border-b border-gray-700">
                                        <div class="relative">
                                            <input
                                                v-model="searchQuery"
                                                type="text"
                                                placeholder="Search items..."
                                                class="w-full px-3 py-2 pl-10 bg-gray-700 border border-gray-600 rounded text-white placeholder-gray-400 focus:border-green-500 focus:outline-none"
                                            />
                                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                        </div>
                                    </div>
                                    <div class="flex justify-between border-b border-gray-700">
                                        <div class="flex">
                                            <button
                                                type="button"
                                                @click="activeTab = 'large'"
                                                :class="['tab-button', activeTab === 'large' ? 'active' : '']"
                                            >
                                                Large Format Materials
                                            </button>
                                            <button
                                                type="button"
                                                @click="activeTab = 'small'"
                                                :class="['tab-button', activeTab === 'small' ? 'active' : '']"
                                            >
                                                Small Format Materials
                                            </button>
                                            <button
                                                type="button"
                                                @click="activeTab = 'article'"
                                                :class="['tab-button', activeTab === 'article' ? 'active' : '']"
                                            >
                                                Articles
                                            </button>
                                        </div>
                                        <div class="flex items-center mr-4 space-x-2">
                                            <button
                                                type="button"
                                                @click="viewMode = 'list'"
                                                :class="['view-toggle-btn', viewMode === 'list' ? 'active' : '']"
                                                title="List View"
                                            >
                                                <i class="fas fa-list"></i>
                                            </button>
                                            <button
                                                type="button"
                                                @click="viewMode = 'card'"
                                                :class="['view-toggle-btn', viewMode === 'card' ? 'active' : '']"
                                                title="Card View"
                                            >
                                                <i class="fas fa-th-large"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="catalog-items-container mb-2 bg-white">
                                    <!-- Large Format Materials Tab -->
                                    <div v-if="activeTab === 'large'" class="space-y-2">
                                        <div v-if="filteredLargeMaterialItems.length === 0" class="empty-state">
                                            No large format materials available
                                        </div>

                                        <!-- List View -->
                                        <div v-if="viewMode === 'list'" class="space-y-2">
                                            <div v-for="item in filteredLargeMaterialItems"
                                                :key="item.id"
                                                class="catalog-item"
                                                @click="toggleItemSelection(item)"
                                            >
                                                <input
                                                    type="checkbox"
                                                    :value="item.id"
                                                    :checked="isItemSelected(item.id)"
                                                    class="h-4 w-4 rounded border-gray-300 checkbox-green"
                                                    @click.stop
                                                />
                                                <div class="catalog-item-details">
                                                    <div class="catalog-item-name">{{ item.name }}</div>
                                                    <div class="catalog-item-material">
                                                        Material: {{ getMaterialDisplay(item) }}
                                                    </div>
                                                </div>
                                                <div class="catalog-item-price">
                                                    {{ item.price ? `${item.price} ден` : 'Price not set' }}
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Card View -->
                                        <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 2xl:grid-cols-8 gap-3">
                                            <div v-for="item in filteredLargeMaterialItems"
                                                :key="item.id"
                                                class="catalog-card"
                                                @click="toggleItemSelection(item)"
                                            >
                                                <div class="catalog-card-image">
                                                    <input
                                                        type="checkbox"
                                                        :value="item.id"
                                                        :checked="isItemSelected(item.id)"
                                                        class="absolute top-2 left-2 h-4 w-4 rounded border-gray-300 checkbox-green z-10"
                                                        @click.stop
                                                    />
                                                    <div v-if="isPlaceholder(item.file)" class="w-full h-full no-image">
                                                        NO IMAGE
                                                    </div>
                                                    <img
                                                        v-else
                                                        :src="getFileUrl(item.file)"
                                                        :alt="item.name"
                                                        class="w-full h-full object-cover"
                                                    />
                                                </div>
                                                <div class="p-2">
                                                    <h3 class="font-medium text-gray-900 text-sm mb-1 truncate">{{ item.name }}</h3>
                                                    <p class="text-xs text-gray-500 mb-1 truncate">
                                                        Material: {{ getMaterialDisplay(item) }}
                                                    </p>
                                                    <div class="text-xs font-medium text-gray-900">
                                                        {{ item.price ? `${item.price} ден` : 'Price not set' }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Small Format Materials Tab -->
                                    <div v-if="activeTab === 'small'" class="space-y-2">
                                        <div v-if="filteredSmallMaterialItems.length === 0" class="empty-state">
                                            No small format materials available
                                        </div>

                                        <!-- List View -->
                                        <div v-if="viewMode === 'list'" class="space-y-2">
                                            <div v-for="item in filteredSmallMaterialItems"
                                                :key="item.id"
                                                class="catalog-item"
                                                @click="toggleItemSelection(item)"
                                            >
                                                <input
                                                    type="checkbox"
                                                    :value="item.id"
                                                    :checked="isItemSelected(item.id)"
                                                    class="h-4 w-4 rounded border-gray-300 checkbox-green"
                                                    @click.stop
                                                />
                                                <div class="catalog-item-details">
                                                    <div class="catalog-item-name">{{ item.name }}</div>
                                                    <div class="catalog-item-material">
                                                        Material: {{ getMaterialDisplay(item) }}
                                                    </div>
                                                </div>
                                                <div class="catalog-item-price">
                                                    {{ item.price ? `${item.price} ден` : 'Price not set' }}
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Card View -->
                                        <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 2xl:grid-cols-8 gap-3">
                                            <div v-for="item in filteredSmallMaterialItems"
                                                :key="item.id"
                                                class="catalog-card"
                                                @click="toggleItemSelection(item)"
                                            >
                                                <div class="catalog-card-image">
                                                    <input
                                                        type="checkbox"
                                                        :value="item.id"
                                                        :checked="isItemSelected(item.id)"
                                                        class="absolute top-2 left-2 h-4 w-4 rounded border-gray-300 checkbox-green z-10"
                                                        @click.stop
                                                    />
                                                    <div v-if="isPlaceholder(item.file)" class="w-full h-full no-image">
                                                        NO IMAGE
                                                    </div>
                                                    <img
                                                        v-else
                                                        :src="getFileUrl(item.file)"
                                                        :alt="item.name"
                                                        class="w-full h-full object-cover"
                                                    />
                                                </div>
                                                <div class="p-2">
                                                    <h3 class="font-medium text-gray-900 text-sm mb-1 truncate">{{ item.name }}</h3>
                                                    <p class="text-xs text-gray-500 mb-1 truncate">
                                                        Material: {{ getMaterialDisplay(item) }}
                                                    </p>
                                                    <div class="text-xs font-medium text-gray-900">
                                                        {{ item.price ? `${item.price} ден` : 'Price not set' }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Articles Tab -->
                                    <div v-if="activeTab === 'article'" class="space-y-2">
                                        <div v-if="articleItems.length === 0" class="empty-state">
                                            No articles available
                                        </div>

                                        <!-- List View -->
                                        <div v-if="viewMode === 'list'" class="space-y-2">
                                            <div v-for="item in filteredArticleItems"
                                                :key="item.id"
                                                class="catalog-item"
                                                @click="toggleItemSelection(item)"
                                            >
                                                <input
                                                    type="checkbox"
                                                    :value="item.id"
                                                    :checked="isItemSelected(item.id)"
                                                    class="h-4 w-4 rounded border-gray-300 checkbox-green"
                                                    @click.stop
                                                />
                                                <div class="catalog-item-details">
                                                    <div class="catalog-item-name">{{ item.name }}</div>
                                                    <div class="catalog-item-material">
                                                        Articles: {{ getArticlesDisplay(item) }}
                                                    </div>
                                                </div>
                                                <div class="catalog-item-price">
                                                    {{ item.price ? `${item.price} ден` : 'Price not set' }}
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Card View -->
                                        <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 2xl:grid-cols-8 gap-3">
                                            <div v-for="item in filteredArticleItems"
                                                :key="item.id"
                                                class="catalog-card"
                                                @click="toggleItemSelection(item)"
                                            >
                                                <div class="catalog-card-image">
                                                    <input
                                                        type="checkbox"
                                                        :value="item.id"
                                                        :checked="isItemSelected(item.id)"
                                                        class="absolute top-2 left-2 h-4 w-4 rounded border-gray-300 checkbox-green z-10"
                                                        @click.stop
                                                    />
                                                    <div v-if="isPlaceholder(item.file)" class="w-full h-full no-image">
                                                        NO IMAGE
                                                    </div>
                                                    <img
                                                        v-else
                                                        :src="getFileUrl(item.file)"
                                                        :alt="item.name"
                                                        class="w-full h-full object-cover"
                                                    />
                                                </div>
                                                <div class="p-2">
                                                    <h3 class="font-medium text-gray-900 text-sm mb-1 truncate">{{ item.name }}</h3>
                                                    <p class="text-xs text-gray-500 mb-1 truncate">
                                                        Articles: {{ getArticlesDisplay(item) }}
                                                    </p>
                                                    <div class="text-xs font-medium text-gray-900">
                                                        {{ item.price ? `${item.price} ден` : 'Price not set' }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Custom Item Modal -->
                        <div v-if="showCustomItemModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                            <div class="bg-gray-800 rounded-lg p-6 w-full max-w-md mx-4">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-white text-lg font-semibold">Add Custom Item</h3>
                                    <button @click="closeCustomItemModal" class="text-gray-400 hover:text-white">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-white text-sm mb-2">Item Name <span class="text-red-500">*</span></label>
                                        <input
                                            v-model="customItem.name"
                                            type="text"
                                            class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded text-white placeholder-gray-400 focus:border-green-500 focus:outline-none"
                                            placeholder="Enter item name"
                                            required
                                        />
                                    </div>
                                    
                                    <div>
                                        <label class="block text-white text-sm mb-2">Description</label>
                                        <textarea
                                            v-model="customItem.description"
                                            rows="3"
                                            class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded text-white placeholder-gray-400 focus:border-green-500 focus:outline-none"
                                            placeholder="Enter item description"
                                        ></textarea>
                                    </div>
                                    
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-white text-sm mb-2">Quantity <span class="text-red-500">*</span></label>
                                            <input
                                                v-model.number="customItem.quantity"
                                                type="number"
                                                min="1"
                                                class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded text-white placeholder-gray-400 focus:border-green-500 focus:outline-none"
                                                placeholder="1"
                                                required
                                            />
                                        </div>
                                        
                                        <div>
                                            <label class="block text-white text-sm mb-2">Price per Unit <span class="text-red-500">*</span></label>
                                            <input
                                                v-model.number="customItem.price"
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded text-white placeholder-gray-400 focus:border-green-500 focus:outline-none"
                                                placeholder="0.00"
                                                required
                                            />
                                        </div>
                                    </div>
                                    
                                    <div class="flex justify-end space-x-2 pt-4">
                                        <button
                                            @click="closeCustomItemModal"
                                            class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700"
                                        >
                                            Cancel
                                        </button>
                                        <button
                                            @click="addCustomItem"
                                            :disabled="!customItem.name || !customItem.quantity || !customItem.price"
                                            class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            Add Item
                                        </button>
                                    </div>
                                </div>
                            </div>
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
import { useForm } from '@inertiajs/vue3';
import '@fortawesome/fontawesome-free/css/all.css';
import { useToast } from "vue-toastification";
import axios from 'axios';

export default {
    name: 'Edit',
    components: {
        MainLayout,
        Header
    },

    props: {
        offer: Object,
        catalogItems: Array,
        clients: Array
    },

    data() {
        return {
            isSubmitting: false,
            currentStep: 0,
            steps: ['Basic Information', 'Select Items'],
            selectedItems: [],
            searchQuery: '',
            form: useForm({
                id: this.offer.id,
                name: this.offer.name,
                client_id: this.offer.client_id,
                contact_id: this.offer.contact_id,
                description: this.offer.description,
                validity_days: this.offer.validity_days,
                production_time: this.offer.production_time,
                catalog_items: this.offer.catalog_items.map(item => ({
                    ...item,
                    selection_id: item.selection_id || Date.now() + Math.floor(Math.random() * 1000)
                }))
            }),
            activeTab: 'large',
            viewMode: 'grid',
            selectedClient: null,
            showCustomItemModal: false,
            customItem: {
                name: '',
                description: '',
                quantity: 1,
                price: 0,
            }
        };
    },

    mounted() {
        // Set the selected client when component is mounted
        this.selectedClient = this.clients.find(c => c.id === this.form.client_id);
    },

    computed: {
        filteredLargeMaterialItems() {
            return this.largeMaterialItems.filter(item =>
                item.name.toLowerCase().includes(this.searchQuery.toLowerCase())
            );
        },
        filteredSmallMaterialItems() {
            return this.smallMaterialItems.filter(item =>
                item.name.toLowerCase().includes(this.searchQuery.toLowerCase())
            );
        },
        largeMaterialItems() {
            return this.catalogItems.filter(item => 
                item.category === 'material'
            );
        },
        smallMaterialItems() {
            return this.catalogItems.filter(item => 
                item.category === 'small_format'
            );
        },
        articleItems() {
            return this.catalogItems.filter(item => 
                item.category === 'article' || 
                (item.articles && item.articles.length > 0)
            );
        },
        filteredArticleItems() {
            return this.articleItems.filter(item =>
                item.name.toLowerCase().includes(this.searchQuery.toLowerCase())
            );
        },
        progressWidth() {
            return `${(this.currentStep + 1) * (100 / this.steps.length)}%`;
        }
    },

    methods: {
        async submit() {
            if (this.isSubmitting) return;
            
            this.isSubmitting = true;
            const toast = useToast();
            
            try {
                await axios.put(`/offers/${this.form.id}`, this.form);
                toast.success('Offer updated successfully!');
                setTimeout(() => {
                    this.$inertia.visit('/offers');
                }, 1000);
            } catch (error) {
                console.error('Error updating offer:', error);
                toast.error('Failed to update offer!');
            } finally {
                this.isSubmitting = false;
            }
        },

        onClientSelect() {
            this.selectedClient = this.clients.find(c => c.id === this.form.client_id);
            // Update prices for all selected items when client changes
            this.form.catalog_items.forEach(item => this.updatePrice(item));
        },

        async updatePrice(item) {
            if (!this.form.client_id || !item.quantity) return;

            // For custom items, don't try to calculate price from API
            if (item.isCustomItem) {
                item.calculated_price = item.custom_price * item.quantity;
                return;
            }

            try {
                // Only calculate using API if there's no custom price 
                // or if the isCustomPrice flag is not set to true
                if (!item.isCustomPrice) {
                    const response = await axios.get('/calculate-price', {
                        params: {
                            catalog_item_id: item.id,
                            client_id: this.form.client_id,
                            quantity: item.quantity
                        }
                    });
                    
                    // Update with calculated price from server
                    const totalPrice = response.data.price;
                    item.calculated_price = totalPrice;
                    // Calculate per-unit price
                    const perUnitPrice = totalPrice / item.quantity;
                    item.custom_price = perUnitPrice;
                    
                    // Store the original price for comparison
                    item._originalPrice = perUnitPrice;
                    
                    console.log(`Auto-calculated price: ${perUnitPrice.toFixed(2)} per unit, total: ${totalPrice}`);
                } else {
                    // If custom price is set, just multiply by quantity
                    if (item.custom_price && item.quantity) {
                        item.calculated_price = item.custom_price * item.quantity;
                    }
                    console.log(`Using manual price: ${item.custom_price} per unit, total: ${item.calculated_price}`);
                }
            } catch (error) {
                console.error('Error calculating price:', error);
            }
        },

        handleCustomPriceChange(item) {
            if (item.custom_price !== null && item.custom_price !== undefined) {
                // Mark as custom price
                item.isCustomPrice = true;
                
                // Calculate the total price by multiplying the custom price per unit by quantity
                item.calculated_price = item.custom_price * item.quantity;
                
                // For custom items, always mark as custom price
                if (item.isCustomItem) {
                    item.isCustomPrice = true;
                }
                
                // Add a flag in console for debugging
                console.log(`Manual price set: ${item.custom_price} per unit, total: ${item.calculated_price}`);
            }
        },

        onQuantityChange(item) {
            if (item.isCustomPrice || item.isCustomItem) {
                // For manual prices or custom items, just recalculate the total based on the custom unit price
                if (item.custom_price && item.quantity) {
                    item.calculated_price = item.custom_price * item.quantity;
                }
                console.log(`Quantity changed with manual price: ${item.custom_price} per unit, total: ${item.calculated_price}`);
            } else {
                // For automatic prices, get a new calculation from the API
                this.updatePrice(item);
            }
        },

        nextStep() {
            if (this.currentStep === 0) {
                // Validate first step
                if (!this.form.client_id || !this.form.name || !this.form.validity_days || !this.form.production_time) {
                    const toast = useToast();
                    toast.error('Please fill in all required fields');
                    return;
                }
            }
            this.currentStep++;
        },

        toggleItemSelection(item) {
            // Create a unique ID for this selection
            const uniqueId = Date.now() + Math.floor(Math.random() * 1000);
            
            // Always create a new item entry with a unique selection ID
            this.form.catalog_items.push({
                id: item.id,
                selection_id: uniqueId,
                name: item.name,
                quantity: 1,
                description: item.description || '',
                custom_price: null,
                calculated_price: null,
                isCustomPrice: false,
                file: item.file,
                large_material: item.large_material,
                small_material: item.small_material,
                isCustomItem: false, // Indicate if it's a custom item
                articles: item.articles // Add articles to the item
            });
            
            // Add the unique selection ID to track this particular selection
            this.selectedItems.push(uniqueId);
            
            // Calculate price for the newly added item
            this.updatePrice(this.form.catalog_items[this.form.catalog_items.length - 1]);
            
            // Show success message
            const toast = useToast();
            toast.success(`${item.name} added to your selection`);
        },

        removeItem(item) {
            const index = this.form.catalog_items.findIndex(i => i.selection_id === item.selection_id);
            if (index !== -1) {
                this.form.catalog_items.splice(index, 1);
                const selectedIndex = this.selectedItems.indexOf(item.selection_id);
                if (selectedIndex !== -1) {
                    this.selectedItems.splice(selectedIndex, 1);
                }
            }
        },

        isItemSelected(itemId) {
            // We're allowing multiple selections of the same item now
            // This method is used for checkbox selection states
            return this.selectedItems.some(item => item.id === itemId);
        },

        getFileUrl(file) {
            return file && file !== 'placeholder.jpeg'
                ? `/storage/uploads/${file}`
                : '/storage/uploads/placeholder.jpeg';
        },

        isPlaceholder(file) {
            return !file || file === 'placeholder.jpeg';
        },

        formatPrice(price) {
            if (!price) return '0.00 ден';
            return new Intl.NumberFormat('de-DE', {
                style: 'currency',
                currency: 'EUR'
            }).format(price);
        },

        openCustomItemModal() {
            this.customItem = {
                name: '',
                description: '',
                quantity: 1,
                price: 0,
            };
            this.showCustomItemModal = true;
        },

        closeCustomItemModal() {
            this.showCustomItemModal = false;
        },

        addCustomItem() {
            if (!this.customItem.name || !this.customItem.quantity || !this.customItem.price) {
                const toast = useToast();
                toast.error('Please fill in all custom item details.');
                return;
            }

            const uniqueId = Date.now() + Math.floor(Math.random() * 1000);
            this.form.catalog_items.push({
                id: null, // No ID for custom items
                selection_id: uniqueId,
                name: this.customItem.name,
                quantity: this.customItem.quantity,
                description: this.customItem.description,
                custom_price: this.customItem.price,
                calculated_price: this.customItem.price * this.customItem.quantity,
                isCustomPrice: true,
                file: null, // No file for custom items
                large_material: null,
                small_material: null,
                isCustomItem: true,
                articles: [] // No articles for custom items
            });
            this.selectedItems.push(uniqueId);

            this.closeCustomItemModal();
            const toast = useToast();
            toast.success(`Custom item "${this.customItem.name}" added.`);
        },

        getMaterialDisplay(item) {
            // For articles, show all article names
            if (item.articles && item.articles.length > 0) {
                return item.articles.map(article => article.name).join(', ');
            }
            return 'N/A';
        },

        getArticlesDisplay(item) {
            if (item.articles && item.articles.length > 0) {
                return item.articles.map(article => `${article.name} (${article.quantity})`).join(', ');
            }
            return 'N/A';
        }
    }
};
</script>

<style scoped lang="scss">
.bg-green {
    background-color: $green;
}
.form-container {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
}
.dark-gray {
    background-color: $dark-gray;
    justify-content: left;
    align-items: center;
    min-height: 20vh;
    min-width: 80vh;
}
.sub-title {
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
.option, input, option, select, textarea {
    color: black;
}

/* New styles for the tabbed interface */
.catalog-tabs {
    @apply bg-gray-800 rounded-t-lg;
}

.tab-button {
    @apply px-4 py-2 text-sm font-medium transition-colors duration-200;
    &.active {
        @apply text-green-500 border-b-2 border-green-500;
    }
    &:not(.active) {
        @apply text-gray-400 hover:text-white;
    }
}

.catalog-items-container {
    @apply bg-white rounded-b-lg p-3 overflow-y-auto;
    min-height: 400px;
    max-height: calc(100vh - 400px);
    width: 100%;

    .grid {
        @apply gap-3;
        width: 100%;
    }
}

.catalog-item {
    @apply flex items-center space-x-3 p-2 hover:bg-gray-50 rounded transition-colors duration-200 cursor-pointer;

    &:hover {
        @apply bg-gray-50;
    }
}

.catalog-item-details {
    @apply flex-1;
}

.catalog-item-name {
    @apply font-medium text-gray-900;
}

/* Card styles */
.catalog-card {
    @apply bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200 transition-all duration-200 cursor-pointer;

    &:hover {
        @apply shadow-lg transform scale-[1.02];
    }

    .catalog-card-image {
        @apply relative w-full overflow-hidden bg-gray-100;
        aspect-ratio: 4/3;

        img {
            @apply w-full h-full object-contain transition-transform duration-200;
            background-color: white;
        }

        &:hover img {
            @apply transform scale-110;
        }

        .no-image {
            @apply flex items-center justify-center text-gray-400 text-xs;
        }
    }
}

/* View toggle buttons */
.view-toggle-btn {
    @apply px-2 py-1 text-sm rounded-md transition-colors duration-200;
    &.active {
        @apply bg-green text-white;
    }
    &:not(.active) {
        @apply text-gray-400 hover:text-white hover:bg-gray-700;
    }
}


.empty-state {
    @apply text-center py-8 text-gray-500;
}

.catalog-item-material {
    @apply text-sm text-gray-500;
}

.catalog-item-price {
    @apply text-sm text-gray-500 font-medium;
}

/* Checkbox styles */
.checkbox-green {
    &:checked {
        background-color: $green;
        border-color: $green;
    }
    &:focus {
        box-shadow: 0 0 0 2px rgba($green, 0.3);
    }
}
</style> 
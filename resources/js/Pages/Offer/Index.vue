<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <div class="flex justify-between align-center">
                <Header title="Offers List" subtitle="Manage Offers" icon="List.png" />
                <div class="flex align-center py-5">
                    <button @click="navigateToOfferCreate" class="btn create-order1">
                        Create New Offer
                    </button>
                </div>
            </div>

            <div class="dark-gray">
                <!-- Tabs -->
                <div class="tabs-container mb-6">
                    <div class="flex">
                        <button
                            v-for="tab in tabs"
                            :key="tab.value"
                            @click="switchTab(tab.value)"
                            :class="[
                                'tab-button',
                                currentTab === tab.value ? 'active' : ''
                            ]"
                        >
                            {{ tab.label }}
                            <span :class="['tab-count', `tab-count-${tab.value}`]">
                                {{ counts[tab.value] }}
                            </span>
                        </button>
                    </div>
                </div>
                
                <!-- Search and Filter Section -->
                <div class="filter-container mb-6">
                    <!-- Search Section -->
                    <div class="search-section mb-4">
                        <div class="flex items-center gap-2">
                            <div class="flex-1">
                                <input 
                                    v-model="searchQuery" 
                                    placeholder="Search by offer ID..." 
                                    class="w-full text-black px-4 py-2 rounded border border-gray-300 focus:outline-none focus:border-blue-500" 
                                    @keyup.enter="searchOffers"
                                />
                            </div>
                            <button class="btn create-order1 px-6" @click="searchOffers">
                                <i class="fa fa-search mr-2"></i>Search
                            </button>
                        </div>
                    </div>

                    <!-- Filters Section -->
                    <div class="filters-section bg-gray-800 p-4 rounded mb-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <!-- Client Filter with Dropdown Search -->
                            <div class="filter-group">
                                <label class="block text-sm mb-2">Client</label>
                                <div class="relative client-dropdown-container">
                                    <input
                                        type="text"
                                        v-model="clientSearch"
                                        @focus="showClientDropdown = true"
                                        @input="filterClients"
                                        placeholder="Search client..."
                                        class="w-full text-black px-2 py-1.5 rounded"
                                        @click.stop="showClientDropdown = true"
                                    />
                                    <div v-if="showClientDropdown"
                                         class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto"
                                         @click.stop>
                                        <div
                                            v-for="client in filteredClients"
                                            :key="client.id"
                                            @click.stop="selectClient(client)"
                                            class="px-4 py-2 hover:bg-gray-100 cursor-pointer text-gray-700"
                                            :class="{'bg-gray-200': filterClient === client.id}"
                                        >
                                            {{ client.name }}
                                        </div>
                                        <div v-if="filteredClients.length === 0" class="px-4 py-2 text-gray-500">
                                            No clients found
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Validity Days Filter -->
                            <div class="filter-group">
                                <label class="block text-sm mb-2">Validity Days</label>
                                <input 
                                    type="number" 
                                    v-model="filterValidityDays" 
                                    placeholder="Enter validity days" 
                                    class="w-full text-black px-2 py-1.5 rounded"
                                    min="1"
                                />
                            </div>

                            <!-- Date Order Filter -->
                            <div class="filter-group">
                                <label class="block text-sm mb-2">Date Order</label>
                                <select v-model="sortOrder" class="w-full text-black px-2 py-1.5 rounded">
                                    <option value="desc">Newest to Oldest</option>
                                    <option value="asc">Oldest to Newest</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex justify-between items-center mt-4">
                            <button @click="clearFilters" class="btn bg-gray-600 text-white px-6 hover:bg-gray-700">
                                <i class="fa fa-times mr-2"></i>Clear Filters
                            </button>
                            <button @click="applyFilter" class="btn create-order1 px-6">
                                <i class="fa fa-filter mr-2"></i>Apply Filters
                            </button>
                        </div>
                    </div>
                </div>

                <table class="w-full">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Client</th>
                            <th>Status</th>
                            <th>Validity Days</th>
                            <th>Items</th>
                            <th>Created At</th>
                            <th class="flex justify-center">Actions</th>
                            <th v-if="currentTab === 'declined'">Decline Reason</th>
                            <th>PDF</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="offer in offers.data" :key="offer.id">
                            <td>{{ offer.id }}</td>
                            <td>{{ offer.client }}</td>
                            <td>
                                <span :class="['status-badge', offer.status]">
                                    {{ offer.status }}
                                </span>
                            </td>
                            <td>{{ offer.validity_days }} days</td>
                            <td>
                                <button
                                    @click="viewItems(offer)"
                                    class="text-green hover:text-light-green"
                                >
                                    {{ offer.items_count }}
                                    <i class="fas fa-eye ml-1"></i>
                                </button>
                            </td>
                            <td>{{ formatDate(offer.created_at) }}</td>
                            <td class="space-x-2 flex justify-center">
                                <button
                                    v-if="currentTab === 'pending' || currentTab === 'declined'"
                                    @click="acceptOffer(offer)"
                                    class="px-2 py-1 btn-success text-white"
                                >
                                    Accept
                                    <i class="fas fa-check ml-1"></i>
                                </button>

                                <button
                                    v-if="currentTab === 'pending' || currentTab === 'accepted'"
                                    @click="openDeclineModal(offer)"
                                    class="px-2 py-1 btn-danger text-white"
                                >
                                    Decline
                                    <i class="fas fa-times ml-1"></i>
                                </button>

                                <button
                                    @click="openEditDialog(offer)"
                                    class="px-2 py-1 rounded bg-[#0073a9] text-white"
                                >
                                    Edit
                                    <i class="fas fa-edit ml-1"></i>
                                </button>
                            </td>
                            <td v-if="currentTab === 'declined'" class="relative">
                                <div class="flex justify-center items-center space-x-2">
                                    <span class="text-ultra-light-gray">
                                        {{ offer.decline_reason || 'No reason provided' }}
                                    </span>
                                    <button
                                        @click="openDeclineModal(offer)"
                                        class="text-light-gray hover:text-white"
                                    >
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                            </td>
                            <td class="px-4 py-2 text-center hover:text-black">
                                <a
                                    :href="route('offers.pdf', offer.id)"
                                    target="_blank"
                                    class="btn text-white hover:text-gray-400"
                                >
                                    <i class="fas fa-file-pdf " style="font-size: 25px"></i>
                                </a>
                            </td>
                            <td class="px-4 py-2 text-center hover:text-black">
                                <button
                                    @click="openDeleteDialog(offer)"
                                    class="text-red-500 hover:text-red-700"
                                >
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="mt-6 flex flex-col justify-between items-center">
                    <div class="flex space-x-2">
                        <Link
                            v-for="link in offers.links"
                            :key="link.label"
                            :href="link.url"
                            class="px-2 py-1 rounded"
                            :class="{
                                'bg-gray-800 text-white': link.active,
                                'text-gray-200 hover:text-white hover:bg-gray-600': !link.active,
                                'opacity-50 cursor-not-allowed': !link.url
                            }"
                            v-html="link.label"
                        />
                    </div>
                    <div class="text-xs text-gray-500 pt-1">
                        Showing {{ offers.from }} to {{ offers.to }} of {{ offers.total }} offers
                    </div>
                </div>
            </div>

            <!-- Items Modal -->
            <Modal :show="showItemsModal" @close="closeItemsModal">
                <div class="p-4 text-white bg-dark-gray background-color">
                    <!-- Modal Header -->
                    <div class="modal-header flex justify-between items-center mb-4 pb-2">
                        <div>
                            <h2 class="text-lg font-semibold">Offer Items</h2>
                            <p class="text-sm">Client: {{ selectedOffer?.client }}</p>
                        </div>
                        <button @click="closeItemsModal" class="text-light-gray hover:text-dark-gray">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <!-- Items Grid -->
                    <div v-if="selectedOffer" class="space-y-4">
                        <!-- View Toggle -->
                        <div class="view-toggle flex justify-end space-x-2 mb-2">
                            <button
                                @click="itemsViewMode = 'grid'"
                                :class="['px-2 py-1 rounded text-sm font-medium', itemsViewMode === 'grid' ? 'active' : '']"
                            >
                                <i class="fas fa-th-large mr-1"></i> Grid
                            </button>
                            <button
                                @click="itemsViewMode = 'list'"
                                :class="['px-2 py-1 rounded text-sm font-medium', itemsViewMode === 'list' ? 'active' : '']"
                            >
                                <i class="fas fa-list mr-1"></i> List
                            </button>
                        </div>

                        <!-- Grid View -->
                        <div v-if="itemsViewMode === 'grid'" class="grid grid-cols-2 gap-3">
                            <div v-for="item in selectedOffer.catalog_items"
                                :key="item.id"
                                class="item-card"
                            >
                                <!-- Item Image -->
                                <div class="relative aspect-w-4 aspect-h-3">
                                    <div v-if="isPlaceholder(item.file)"
                                        class="no-image w-full h-24 flex items-center justify-center text-xs"
                                    >
                                        NO IMAGE
                                    </div>
                                    <img v-else
                                        :src="getFileUrl(item.file)"
                                        :alt="item.name"
                                        class="w-full h-24 object-contain bg-white"
                                    >
                                </div>

                                <!-- Item Details -->
                                <div class="p-3 space-y-2">
                                    <div>
                                        <h3 class="item-name text-base font-medium truncate">{{ item.name }}</h3>
                                        <p class="item-type text-xs">
                                            {{ item.large_material ? 'Large Format' : 'Small Format' }} Material
                                        </p>
                                    </div>

                                    <div class="item-details grid grid-cols-2 gap-2 text-xs">
                                        <div>
                                            <span>Quantity: {{ item.quantity }}</span>
                                        </div>
                                        <div>
                                            <span>{{ item.custom_price ? `${item.custom_price} ден` : '-' }}</span>
                                        </div>
                                    </div>

                                    <!-- Descriptions -->
                                    <div class="descriptions space-y-1 pt-1">

                                        <div v-if="item.custom_description" class="text-xs">
                                            <p class="label">Description:</p>
                                            <p class="text line-clamp-2">{{ item.custom_description }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- List View -->
                        <div v-else class="space-y-2">
                            <div v-for="item in selectedOffer.catalog_items"
                                :key="item.id"
                                class="item-card"
                            >
                                <div class="p-3 flex items-start space-x-3">
                                    <!-- Item Image -->
                                    <div class="w-16 h-16 flex-shrink-0">
                                        <div v-if="isPlaceholder(item.file)"
                                            class="no-image w-full h-full flex items-center justify-center text-xs"
                                        >
                                            NO IMAGE
                                        </div>
                                        <img v-else
                                            :src="getFileUrl(item.file)"
                                            :alt="item.name"
                                            class="w-full h-full object-contain bg-white rounded"
                                        >
                                    </div>

                                    <!-- Item Details -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <h3 class="item-name text-base font-medium">{{ item.name }}</h3>
                                                <p class="item-type text-xs">
                                                    {{ item.large_material ? 'Large Format' : 'Small Format' }} Material
                                                </p>
                                            </div>
                                            <div class="item-details text-right text-xs">
                                                <p>
                                                    <span>Quantity: {{ item.quantity }}</span>
                                                </p>
                                                <p>
                                                    <span>{{ item.price ? `${item.price} ден` : '-' }}</span>
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Descriptions -->
                                        <div class="descriptions mt-2 space-y-1 text-xs">

                                            <div v-if="item.custom_description">
                                                <p class="label">Description:</p>
                                                <p class="text line-clamp-2">{{ item.custom_description }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Modal>

            <!-- Decline Reason Modal -->
            <Modal :show="showDeclineModal" @close="closeDeclineModal">
                <div class="p-4 background-color">
                    <div class="modal-header flex justify-between items-center mb-4 pb-2">
                        <div>
                            <h2 class="text-lg font-semibold">{{ isEditingDeclineReason ? 'Edit Decline Reason' : 'Decline Offer' }}</h2>
                            <p class="text-sm">Client: {{ selectedOffer?.client }}</p>
                        </div>
                        <button @click="closeDeclineModal" class="text-light-gray hover:text-white">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="space-y-4 background-color">
                        <div>
                            <label class="block text-white text-sm mb-2">
                                Reason (Optional)
                            </label>
                            <textarea
                                v-model="declineReason"
                                rows="3"
                                class="w-full px-3 py-2 bg-gray-600 border border-light-gray rounded-md text-white placeholder-ultra-light-gray focus:border-green focus:outline-none"
                                placeholder="Enter reason for declining..."
                            ></textarea>
                        </div>

                        <div class="flex justify-end space-x-2">
                            <button
                                @click="closeDeclineModal"
                                class="px-4 py-2 btn-danger text-white"
                            >
                                Cancel
                            </button>
                            <button
                                v-if="declineReason.trim()"
                                @click="confirmDecline"
                                class="px-4 py-2 btn-primary text-white"
                            >
                                {{ isEditingDeclineReason ? 'Update Reason' : 'Decline with Reason' }}
                            </button>
                            <button
                                v-else
                                @click="confirmDecline"
                                class="px-4 py-2 btn-primary text-white"
                            >
                                No Reason
                            </button>
                        </div>
                    </div>
                </div>
            </Modal>

            <!-- Edit Offer Dialog -->
            <Modal :show="showEditDialog" @close="closeEditDialog">
                <div class="p-4 background-color">
                    <div class="modal-header flex justify-between items-center mb-4 pb-2">
                        <div>
                            <h2 class="text-lg font-semibold">Edit Offer</h2>
                            <p class="text-sm">Client: {{ editForm.client?.name }}</p>
                        </div>
                        <button @click="closeEditDialog" class="text-red-700 hover:text-red-500">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="space-y-4 background-color">
                        <form @submit.prevent="updateOffer" class="space-y-6">
                            <!-- Basic Information -->
                            <div class="grid grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-white text-sm mb-2">Name</label>
                                    <input
                                        v-model="editForm.name"
                                        type="text"
                                        class="w-full px-3 py-2 bg-gray-600 border border-light-gray rounded-md text-white"
                                        required
                                    />
                                </div>

                                <div>
                                    <label class="block text-white text-sm mb-2">Client</label>
                                    <select
                                        v-model="editForm.client_id"
                                        class="w-full px-3 py-2 bg-gray-600 border border-light-gray rounded-md text-white"
                                        required
                                        @change="onClientSelect"
                                    >
                                        <option value="" disabled>Select a client</option>
                                        <option v-for="client in clients" :key="client.id" :value="client.id">
                                            {{ client.name }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-white text-sm mb-2">Contact</label>
                                    <select
                                        v-model="editForm.contact_id"
                                        class="w-full px-3 py-2 bg-gray-600 border border-light-gray rounded-md text-white"
                                        required
                                    >
                                        <option value="" disabled>Select a contact</option>
                                        <option v-for="contact in selectedClientContacts" :key="contact.id" :value="contact.id">
                                            {{ contact.name }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-white text-sm mb-2">Validity Days</label>
                                    <input
                                        v-model="editForm.validity_days"
                                        type="number"
                                        min="1"
                                        class="w-full px-3 py-2 bg-gray-600 border border-light-gray rounded-md text-white"
                                        required
                                    />
                                </div>

                                <div>
                                    <label class="block text-white text-sm mb-2">Production Time</label>
                                    <input
                                        v-model="editForm.production_time"
                                        type="text"
                                        class="w-full px-3 py-2 bg-gray-600 border border-light-gray rounded-md text-white"
                                    />
                                </div>

                                <div>
                                    <label class="block text-white text-sm mb-2">Description</label>
                                    <textarea
                                        v-model="editForm.description"
                                        rows="3"
                                        class="w-full px-3 py-2 bg-gray-600 border border-light-gray rounded-md text-white"
                                    ></textarea>
                                </div>
                            </div>

                            <!-- Catalog Items -->
                            <div class="mt-6">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-white text-lg font-semibold">Selected Items</h3>
                                    <button
                                        type="button"
                                        @click="openItemSelection"
                                        class="px-3 py-1 green rounded hover:bg-light-green"
                                    >
                                        <i class="fas fa-plus mr-1"></i> Add Item
                                    </button>
                                </div>

                                <!-- Selected Items Summary -->
                                <div v-if="editForm.catalog_items.length > 0" class="bg-gray-800 p-3 rounded-lg mb-4">
                                    <div class="flex items-center justify-between mb-3">
                                        <h3 class="text-white text-sm font-medium">Selected Items</h3>
                                        <span class="bg-green px-2 py-0.5 rounded text-xs text-white">{{ editForm.catalog_items.length }} items</span>
                                    </div>
                                    <div class="divide-y divide-gray-700">
                                        <div v-for="item in editForm.catalog_items" :key="item.selection_id" class="py-2">
                                            <div class="flex items-center gap-4 pb-2">
                                                <!-- Name and Description -->
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center gap-2">
                                                        <h4 class="text-white text-sm font-medium truncate">{{ item.name }}</h4>
                                                        <input
                                                            v-model="item.description"
                                                            type="text"
                                                            class="flex-1 rounded bg-white border-gray-700 text-black text-sm py-1 px-2"
                                                            placeholder="Add description..."
                                                        />
                                                    </div>
                                                </div>

                                                <!-- Quantity -->
                                                <div class="flex items-center gap-2 min-w-[120px]">
                                                    <label class="text-gray-400 text-xs whitespace-nowrap">Qty:</label>
                                                    <input
                                                        v-model.number="item.quantity"
                                                        type="number"
                                                        min="1"
                                                        class="w-20 rounded bg-white border-gray-700 text-black text-sm py-1 px-2"
                                                        required
                                                        @change="updatePrice(item)"
                                                        @keydown.enter.prevent="updatePrice(item)"
                                                        @keydown.right.enter.prevent="updatePrice(item)"
                                                    />
                                                </div>

                                                <!-- Price -->
                                                <div class="flex items-center gap-2 min-w-[200px]">
                                                    <label class="text-gray-400 text-xs whitespace-nowrap">Price:</label>
                                                    <div class="relative flex-1">
                                                        <input
                                                            :value="calculatedPrice(item)"
                                                            type="number"
                                                            min="0"
                                                            step="0.01"
                                                            class="w-full rounded bg-white border-gray-700 text-black text-sm py-1 px-2"
                                                            placeholder="Price per unit"
                                                            @keydown.enter.prevent="updatePrice(item)"
                                                            @keydown.right.enter.prevent="updatePrice(item)"
                                                        />
                                                        <div v-if="item.calculated_price" class="absolute -bottom-4 left-0 text-gray-400 text-xs whitespace-nowrap">
                                                            Total: {{ item.calculated_price }} ден ({{ (item.calculated_price / item.quantity).toFixed(2) }} per unit)
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Remove Button -->
                                                <button
                                                    @click="removeItem(item)"
                                                    class="text-gray-400 hover:text-red-400 transition-colors p-1"
                                                    title="Remove Item"
                                                >
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end space-x-4 pt-4">
                                <button
                                    type="button"
                                    @click="closeEditDialog"
                                    class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    class="px-4 py-2 bg-green text-white rounded hover:bg-light-green"
                                    :disabled="isSubmitting"
                                >
                                    <span v-if="!isSubmitting">Update Offer</span>
                                    <span v-else class="flex items-center">
                                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Updating...
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Modal>

            <!-- Item Selection Dialog -->
            <Modal :show="showItemSelection" @close="closeItemSelection">
                <div class="p-4 background-color">
                    <div class="modal-header flex justify-between items-center mb-4 pb-2">
                        <h2 class="text-lg font-semibold">Select Items</h2>
                        <button @click="closeItemSelection" class="text-red-700 hover:text-red-500">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="space-y-4">
                        <input
                            v-model="itemSearchQuery"
                            type="text"
                            placeholder="Search items..."
                            class="w-full px-3 py-2 bg-gray-600 border border-light-gray rounded-md text-white"
                        />

                        <div class="grid grid-cols-2 gap-4 max-h-96 overflow-y-auto">
                            <div
                                v-for="item in filteredCatalogItems"
                                :key="item.id"
                                class="bg-gray-700 p-4 rounded-lg cursor-pointer hover:bg-gray-600"
                                @click="selectItem(item)"
                            >
                                <div class="flex items-center space-x-4">
                                    <div class="w-12 h-12">
                                        <img
                                            v-if="!isPlaceholder(item.file)"
                                            :src="getFileUrl(item.file)"
                                            :alt="item.name"
                                            class="w-full h-full object-cover rounded"
                                        />
                                        <div v-else class="w-full h-full bg-gray-800 rounded flex items-center justify-center text-gray-500 text-xs">
                                            NO IMAGE
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="text-white font-medium">{{ item.name }}</h4>
                                        <p class="text-gray-400 text-sm">
                                            {{ item.large_material ? 'Large Format' : 'Small Format' }}
                                        </p>
                                        <p class="text-gray-400 text-sm">
                                            Base Price: {{ formatPrice(item.price) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Modal>

            <!-- Delete Verification Dialog -->
            <Modal :show="showDeleteDialog" @close="closeDeleteDialog">
                <div class="p-4 background-color">
                    <div class="modal-header flex justify-between items-center mb-4 pb-2">
                        <div>
                            <h2 class="text-lg font-semibold">Delete Offer</h2>
                            <p class="text-sm">Client: {{ selectedOffer?.client }}</p>
                        </div>
                        <button @click="closeDeleteDialog" class="text-red-500 hover:text-red-700">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="space-y-4 background-color">
                        <div>
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
                                Delete Offer
                            </button>
                        </div>
                    </div>
                </div>
            </Modal>
        </div>
    </MainLayout>
</template>

<script>
import MainLayout from '@/Layouts/MainLayout.vue';
import Header from '@/Components/Header.vue';
import Modal from '@/Components/Modal.vue';
import { Link } from '@inertiajs/vue3';
import {useToast} from "vue-toastification";
import axios from 'axios';

export default {
    components: {
        MainLayout,
        Header,
        Modal,
        Link
    },

    props: {
        offers: Object,
        filters: Object,
        counts: Object,
    },

    data() {
        return {
            showModal: false,
            showItemsModal: false,
            showDeclineModal: false,
            showEditDialog: false,
            showItemSelection: false,
            showDeleteDialog: false,
            selectedOffer: null,
            itemsViewMode: 'grid',
            currentTab: this.filters?.status || 'pending',
            declineReason: '',
            isEditingDeclineReason: false,
            itemSearchQuery: '',
            isSubmitting: false,
            editForm: {
                id: null,
                name: '',
                description: '',
                client_id: '',
                contact_id: '',
                validity_days: 30,
                production_time: '',
                catalog_items: []
            },
            selectedClient: null,
            tabs: [
                { label: 'Pending', value: 'pending' },
                { label: 'Accepted', value: 'accepted' },
                { label: 'Declined', value: 'declined' }
            ],
            deleteCodeError: '',
            pin: {
                digit1: '',
                digit2: '',
                digit3: '',
                digit4: ''
            },
            
            // Updated properties for search and filtering
            searchQuery: this.filters?.searchQuery || '',
            filterClient: this.filters?.filterClient || '',
            filterValidityDays: this.filters?.filterValidityDays || '',
            sortOrder: this.filters?.sortOrder || 'desc',
            
            // New properties for client search dropdown
            clientSearch: '',
            showClientDropdown: false,
            clients: [],
            filteredClients: [],
            selectedClientName: '',
        };
    },

    computed: {
        filteredOffers() {
            return this.offers.data.filter(offer => offer.status === this.currentTab);
        },
        selectedClientContacts() {
            if (!this.selectedClient) return [];
            return this.selectedClient.contacts || [];
        },
        filteredCatalogItems() {
            if (!this.itemSearchQuery) return this.catalogItems;
            const query = this.itemSearchQuery.toLowerCase();
            return this.catalogItems.filter(item =>
                item.name.toLowerCase().includes(query)
            );
        },
    },

    methods: {
        switchTab(status) {
            this.currentTab = status;
            this.visitWithFilters(true);
        },

        formatDate(date) {
            if (!date) return '-';
            return new Date(date).toLocaleDateString();
        },

        async viewOffer(offer) {
            this.$inertia.visit(route('offer.show', offer.id));
        },

        editOffer(offer) {
            this.$inertia.visit(route('offer.edit', offer.id));
        },

        navigateToOfferCreate() {
            this.$inertia.visit(route('offer.create'));
        },

        async viewItems(offer) {
            this.selectedOffer = offer;
            this.showItemsModal = true;
        },

        closeItemsModal() {
            this.showItemsModal = false;
            this.selectedOffer = null;
        },

        getFileUrl(file) {
            return file && file !== 'placeholder.jpeg'
                ? `/storage/uploads/${file}`
                : '/storage/uploads/placeholder.jpeg';
        },

        isPlaceholder(file) {
            return !file || file === 'placeholder.jpeg';
        },

        getOffersCount(status) {
            return this.offers.data.filter(offer => offer.status === status).length;
        },

        getTabCountClass(status) {
            const baseClasses = 'bg-opacity-20';
            switch (status) {
                case 'pending':
                    return `${baseClasses} bg-orange text-orange`;
                case 'accepted':
                    return `${baseClasses} bg-green text-green`;
                case 'declined':
                    return `${baseClasses} bg-red text-red`;
                default:
                    return '';
            }
        },

        async acceptOffer(offer) {
            const toast = useToast();
            try {
                await this.$inertia.patch(route('offers.update-status', offer.id), {
                    status: 'accepted'
                }, {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => {
                        toast.success('Offer has been accepted successfully.');
                    },
                    onError: () => {
                        toast.error('Failed to accept the offer. Please try again.');
                    }
                });
            } catch (error) {
                console.error('Error accepting offer:', error);
                toast.error('An unexpected error occurred.');
            }
        },

        openDeclineModal(offer) {
            this.selectedOffer = offer;
            this.isEditingDeclineReason = offer.status === 'declined';
            this.declineReason = offer.decline_reason || '';
            this.showDeclineModal = true;
        },

        closeDeclineModal() {
            this.showDeclineModal = false;
            this.selectedOffer = null;
            this.declineReason = '';
            this.isEditingDeclineReason = false;
            console.log(this.showDeclineModal)
        },

        async confirmDecline() {
            const toast = useToast();
            if (!this.selectedOffer) return;

            try {
                await this.$inertia.patch(route('offers.update-status', this.selectedOffer.id), {
                    status: 'declined',
                    decline_reason: this.declineReason.trim() || null
                }, {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => {
                        const message = this.declineReason.trim()
                            ? 'Offer has been declined with reason.'
                            : 'Offer has been declined without reason.';
                        toast.success(message);
                        this.closeDeclineModal();
                    },
                    onError: (errors) => {
                        if (errors.decline_reason) {
                            toast.error(errors.decline_reason);
                        } else {
                            toast.error('Failed to decline the offer. Please try again.');
                        }
                    }
                });
            } catch (error) {
                console.error('Error declining offer:', error);
                toast.error('An unexpected error occurred.');
            }
        },

        async openEditDialog(offer) {
            try {
                const response = await axios.get(`/offers/${offer.id}/edit`);
                const { offer: offerData, clients, catalogItems } = response.data;

                this.clients = clients;
                this.catalogItems = catalogItems;
                this.editForm = {
                    id: offerData.id,
                    name: offerData.name,
                    description: offerData.description,
                    client_id: offerData.client_id,
                    contact_id: offerData.contact_id,
                    validity_days: offerData.validity_days,
                    production_time: offerData.production_time,
                    catalog_items: offerData.catalog_items
                };

                this.selectedClient = this.clients.find(c => c.id === offerData.client_id);
                this.showEditDialog = true;
            } catch (error) {
                const toast = useToast();
                toast.error('Failed to load offer details');
                console.error('Error loading offer:', error);
            }
        },

        closeEditDialog() {
            this.showEditDialog = false;
            this.editForm = {
                id: null,
                name: '',
                description: '',
                client_id: '',
                contact_id: '',
                validity_days: 30,
                production_time: '',
                catalog_items: []
            };
        },

        openItemSelection() {
            this.itemSearchQuery = '';
            this.showItemSelection = true;
        },

        closeItemSelection() {
            this.showItemSelection = false;
            this.itemSearchQuery = '';
        },

        selectItem(item) {
            this.editForm.catalog_items.push({
                id: item.id,
                selection_id: Date.now(),
                name: item.name,
                quantity: 1,
                description: item.description || '',
                custom_price: item.price,
                calculated_price: null,
                file: item.file,
                large_material: item.large_material,
                small_material: item.small_material
            });
            this.closeItemSelection();
        },

        removeItem(item) {
            const index = this.editForm.catalog_items.findIndex(i => i.selection_id === item.selection_id);
            if (index !== -1) {
                this.editForm.catalog_items.splice(index, 1);
            }
        },

        onClientSelect() {
            this.selectedClient = this.clients.find(c => c.id === this.editForm.client_id);
            this.editForm.contact_id = '';
            // Update prices for all selected items
            this.editForm.catalog_items.forEach(item => this.updatePrice(item));
        },

        async updatePrice(item) {
            if (!this.editForm.client_id || !item.quantity) return;

            try {
                const response = await axios.get('/calculate-price', {
                    params: {
                        catalog_item_id: item.id,
                        client_id: this.editForm.client_id,
                        quantity: item.quantity,
                        custom_price: item.custom_price
                    }
                });

                if (item.custom_price) {
                    // If custom price is set, calculate total based on custom price
                    item.calculated_price = item.custom_price * item.quantity;
                } else {
                    // Otherwise use the calculated price from the server
                    item.calculated_price = response.data.price;
                    item.custom_price = response.data.price / item.quantity;
                }
            } catch (error) {
                console.error('Error calculating price:', error);
            }
        },

        async updateOffer() {
            if (this.isSubmitting) return;

            this.isSubmitting = true;
            const toast = useToast();

            try {
                await axios.put(`/offers/${this.editForm.id}`, this.editForm);
                toast.success('Offer updated successfully');
                this.closeEditDialog();
                this.$inertia.reload();
            } catch (error) {
                console.error('Error updating offer:', error);
                if (error.response?.data?.errors) {
                    Object.values(error.response.data.errors).forEach(errors => {
                        errors.forEach(error => toast.error(error));
                    });
                } else {
                    toast.error('Failed to update offer');
                }
            } finally {
                this.isSubmitting = false;
            }
        },

        formatPrice(price) {
            if (!price) return '0.00 ден';
            return new Intl.NumberFormat('de-DE', {
                style: 'currency',
                currency: 'EUR'
            }).format(price);
        },

        calculatedPrice(item) {
            return item.calculated_price ?? item.custom_price;
        },

        openDeleteDialog(offer) {
            this.selectedOffer = offer;
            this.deleteCodeError = '';
            this.resetPin();
            this.showDeleteDialog = true;
            // Focus the first input after the dialog is shown
            this.$nextTick(() => {
                this.$refs.pin1.focus();
            });
        },

        closeDeleteDialog() {
            this.showDeleteDialog = false;
            this.selectedOffer = null;
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
            this.deleteOffer();
        },

        async deleteOffer() {
            const toast = useToast();
            
            if (!this.selectedOffer) return;
            
            try {
                await axios.delete(route('offer.destroy', this.selectedOffer.id));
                
                toast.success('Offer deleted successfully.');
                this.closeDeleteDialog();
                
                // Refresh the page to update the offers list
                this.$inertia.reload();
            } catch (error) {
                console.error('Error deleting offer:', error);
                toast.error('Failed to delete the offer. Please try again.');
            }
        },
        
        // Add these new methods for search and filtering
        async fetchClients() {
            try {
                const response = await axios.get('/api/clients/all');
                this.clients = response.data;
                this.filteredClients = [...this.clients];
                
                // Set initial client name if exists
                if (this.filterClient) {
                    const selectedClient = this.clients.find(c => c.id === parseInt(this.filterClient));
                    if (selectedClient) {
                        this.selectedClientName = selectedClient.name;
                        this.clientSearch = selectedClient.name;
                    }
                }
            } catch (error) {
                console.error("Failed to fetch clients:", error);
                const toast = useToast();
                toast.error('Error fetching clients');
            }
        },
        
        filterClients() {
            if (!this.clientSearch) {
                this.filteredClients = [...this.clients];
                return;
            }

            this.filteredClients = this.clients.filter(client =>
                client.name.toLowerCase().includes(this.clientSearch.toLowerCase())
            );
        },
        
        selectClient(client) {
            this.filterClient = client.id;
            this.clientSearch = client.name;
            this.selectedClientName = client.name;
            this.showClientDropdown = false;
        },
        
        handleClickOutside(event) {
            // Get the dropdown element
            const dropdown = this.$el.querySelector('.client-dropdown-container');
            // Only close if click is outside the dropdown
            if (dropdown && !dropdown.contains(event.target)) {
                this.showClientDropdown = false;
            }
        },
        
        visitWithFilters(resetPage = false) {
            const params = {
                status: this.currentTab,
                searchQuery: this.searchQuery,
                filterClient: this.filterClient,
                filterValidityDays: this.filterValidityDays,
                sortOrder: this.sortOrder
            };

            // Only include page parameter if not resetting
            if (!resetPage) {
                const currentPage = new URL(window.location.href).searchParams.get('page');
                if (currentPage) {
                    params.page = currentPage;
                }
            }

            // Remove empty params
            Object.keys(params).forEach(key => {
                if (params[key] === '' || params[key] === null || params[key] === undefined) {
                    delete params[key];
                }
            });

            this.$inertia.get(route('offer.index'), params, {
                preserveState: true,
                preserveScroll: true,
                replace: true
            });
        },
        
        applyFilter() {
            this.visitWithFilters(true); // Reset to page 1 when applying filters
        },
        
        searchOffers() {
            this.visitWithFilters(true); // Reset to page 1 when searching
        },
        
        clearFilters() {
            this.searchQuery = '';
            this.filterClient = '';
            this.filterValidityDays = '';
            this.sortOrder = 'desc';
            this.clientSearch = '';
            this.selectedClientName = '';
            this.visitWithFilters(true);
        },
    },

    mounted() {
        this.fetchClients();
        
        // Add event listener for clicks outside the dropdown
        document.addEventListener('click', this.handleClickOutside);
    },
    
    beforeUnmount() {
        // Remove event listener when component is destroyed
        document.removeEventListener('click', this.handleClickOutside);
    },
};
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

.background-color {
    background-color: $background-color;
}
.green {
    color: $green;
}
.bg-green {
    background-color: $green;
}
.btn-success {
    background-color: $green;
    border-radius: 0.375rem;
}
.btn-danger {
    border-radius: 0.375rem;
    background-color: $red;
}
.btn-success:hover {
    background-color: darken($green, 5%);
}
.btn-danger:hover {
    background-color: darken($red, 5%);
}
.bg-blue{
    background-color: $blue;
}
.dark-gray {
    background-color: $dark-gray;
    padding: 1rem;
    margin-top: 1rem;
    margin-bottom: 1rem;
}
.btn {
    padding: 9px 12px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    border-radius: 2px;
}
.create-order1{
    background-color: $blue;
    color: white;
}
table {
    width: 100%;
    border-collapse: collapse;

    th, td {
        padding: 0.75rem;
        text-align: center;
        color: $white;
    }

    th {
        font-weight: 600;
        background-color: rgba($white, 0.1);
    }

    tr:hover td {
        background-color: rgba($white, 0.05);
    }
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;

    &.pending {
        background-color: rgba($orange, 0.2);
        color: $orange;
    }

    &.accepted {
        background-color: rgba($green, 0.2);
        color: $green;
    }

    &.declined {
        background-color: rgba($red, 0.2);
        color: $red;
    }
}

.btn-primary {
    padding: 0.5rem 1rem;
    background-color: $green;
    color: $white;
    border-radius: 0.375rem;

    &:hover {
        background-color: darken($green, 5%);
    }
}

// Modal Styles
.modal {
    background-color: $background-color
}
.modal-content {
    background-color: $dark-gray;
    border-radius: 0.5rem;
    max-width: 32rem;
    margin: 0 auto;
    box-shadow: 0 0 15px rgba($black, 0.3);
}
    .modal-header {
        border-bottom: 1px solid rgba($light-gray, 0.2);

        h2 {
            color: $white;
        }

        p {
            color: $ultra-light-gray;
        }
    }

    .view-toggle {
        button {
            &.active {
                background-color: $green;
                color: $white;
            }

            &:not(.active) {
                color: $ultra-light-gray;

                &:hover {
                    color: $white;
                }
            }
        }
    }

    .item-card {
        background-color: $gray;
        border: 1px solid rgba($light-gray, 0.1);
        border-radius: 0.375rem;
        transition: all 0.2s ease;

        &:hover {
            border-color: rgba($light-gray, 0.2);
            box-shadow: 0 2px 4px rgba($black, 0.1);
        }

        .no-image {
            background-color: $background-color;
            color: $ultra-light-gray;
        }

        .item-name {
            color: $white;
        }

        .item-type {
            color: $ultra-light-gray;
        }

        .item-details {
            color: $ultra-light-gray;

            span {
                color: $white;
            }
        }

        .descriptions {
            border-top: 1px solid rgba($light-gray, 0.1);

            .label {
                color: $ultra-light-gray;
            }

            .text {
                color: $white;
            }
        }

        img {
            background-color: $background-color;
        }
    }

.tabs-container {
    border-bottom: 1px solid rgba($light-gray, 0.2);
    margin: 0 -1rem 1.5rem -1rem;
    padding: 0 1rem;
}

.tab-button {
    position: relative;
    padding: 0.75rem 1.5rem;
    font-weight: 500;
    color: $ultra-light-gray;
    transition: all 0.2s ease;

    &:hover:not(.active) {
        color: $white;
    }

    &.active {
        color: $white;

        &:after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 2px;
            background-color: $green;
            border-radius: 2px 2px 0 0;
        }
    }

    .tab-label {
        display: inline-block;
        margin-right: 0.5rem;
    }

    .tab-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 1.5rem;
        height: 1.5rem;
        padding: 0 0.5rem;
        font-size: 0.75rem;
        font-weight: 500;
        border-radius: 0.75rem;

        &.tab-count-pending {
            background-color: rgba($orange, 0.15);
            color: $orange;
        }

        &.tab-count-accepted {
            background-color: rgba($green, 0.15);
            color: $green;
        }

        &.tab-count-declined {
            background-color: rgba($red, 0.15);
            color: $red;
        }
    }
}

.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.animate-spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

// Update pagination styles to match theme
.pagination-link {
    background-color: $dark-gray;
    border-color: $light-gray;
    color: $ultra-light-gray;

    &.active {
        background-color: $blue;
        color: $white;
        border-color: $blue;
    }

    &:not(.active):not(.disabled):hover {
        background-color: lighten($dark-gray, 5%);
        color: $white;
    }

    &.disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
}

.w-12 {
    width: 3rem;
}

.h-12 {
    height: 3rem;
}

input:focus {
    outline: none;
    border-color: #81c950;
    box-shadow: 0 0 0 2px rgba(129, 201, 80, 0.3);
}

/* Style for the active/focused input */
input:focus {
    border-color: #81c950;
}

/* Style for filled inputs */
input:not(:placeholder-shown) {
    border-color: #81c950;
}

.filter-container {
    margin-bottom: 1.5rem;
}

.search-section input {
    background-color: $white;
    border: 1px solid $light-gray;
    
    &:focus {
        border-color: $blue;
        box-shadow: 0 0 0 2px rgba($blue, 0.3);
    }
}

.filters-section {
    background-color: rgba($dark-gray, 0.5);
    border: 1px solid rgba($light-gray, 0.1);
    
    label {
        color: $ultra-light-gray;
    }
    
    select {
        background-color: $white;
        border: 1px solid $light-gray;
        
        &:focus {
            border-color: $blue;
            outline: none;
        }
    }
}

.filter-group {
    margin-bottom: 0.5rem;
}

.relative {
    position: relative;
}

.absolute {
    position: absolute;
    z-index: 10;
}

.max-h-60 {
    max-height: 15rem;
}

.overflow-auto {
    overflow: auto;
}

.hover\:bg-gray-100:hover {
    background-color: #f3f4f6;
}

.bg-gray-200 {
    background-color: #e5e7eb;
}

.cursor-pointer {
    cursor: pointer;
}

/* Scrollbar styling */
.overflow-auto::-webkit-scrollbar {
    width: 8px;
}

.overflow-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.overflow-auto::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

.overflow-auto::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>

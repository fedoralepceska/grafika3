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
                                    placeholder="Search by offer #..." 
                                    class="w-full text-black px-4 py-2 rounded border border-gray-300 focus:outline-none focus:border-blue-500" 
                                    @keyup.enter="searchOffers"
                                    @input="debouncedSearch"
                                />
                            </div>
                            <button class="btn create-order1 px-6" @click="searchOffers">
                                <i class="fa fa-search mr-2"></i>Search
                            </button>
                        </div>
                    </div>

                    <!-- Filters Section -->
                    <div class="filters-section bg-gray-800 p-4 rounded mb-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <!-- Year Filter -->
                            <div class="filter-group">
                                <label class="block text-sm mb-2">Year</label>
                                <select v-model="fiscalYear" @change="applyFilter" class="w-full text-black px-2 py-1.5 rounded">
                                    <option v-for="year in availableYears" :key="year" :value="year">{{ year }}</option>
                                </select>
                            </div>

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
                                    @input="applyFilter"
                                />
                            </div>

                            <!-- Date Order Filter -->
                            <div class="filter-group">
                                <label class="block text-sm mb-2">Date Order</label>
                                <select v-model="sortOrder" @change="applyFilter" class="w-full text-black px-2 py-1.5 rounded">
                                    <option value="desc">Newest to Oldest</option>
                                    <option value="asc">Oldest to Newest</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex justify-end items-center mt-4">
                            <button @click="clearFilters" class="btn bg-gray-600 text-white px-6 hover:bg-gray-700">
                                <i class="fa fa-times mr-2"></i>Clear Filters
                            </button>
                        </div>
                    </div>
                </div>

                <table class="w-full">
                    <thead>
                        <tr>
                            <th>Offer #</th>
                            <th>Client</th>
                            <th>Status</th>
                            <th>Validity Days</th>
                            <th>Created By</th>
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
                            <td>#{{ offer.offer_number }}/{{ offer.fiscal_year }}</td>
                            <td>{{ offer.client }}</td>
                            <td>
                                <span :class="['status-badge', offer.status]">
                                    {{ offer.status }}
                                </span>
                            </td>
                            <td>{{ offer.validity_days }} days</td>
                            <td>{{ offer.created_by_name || '-' }}</td>
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
                                    @click="editOffer(offer)"
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
                        <!-- First Page Button -->
                        <Link
                            :href="getPaginationUrl(offers.first_page_url)"
                            class="px-2 py-1 rounded"
                            :class="{
                                'bg-gray-800 text-white': offers.current_page === 1,
                                'text-gray-200 hover:text-white hover:bg-gray-600': offers.current_page !== 1,
                                'opacity-50 cursor-not-allowed': !offers.first_page_url
                            }"
                            :disabled="!offers.first_page_url"
                        >
                            <i class="fas fa-angle-double-left"></i>
                        </Link>

                        <Link
                            v-for="link in offers.links"
                            :key="link.label"
                            :href="getPaginationUrl(link.url)"
                            class="px-2 py-1 rounded"
                            :class="{
                                'bg-gray-800 text-white': link.active,
                                'text-gray-200 hover:text-white hover:bg-gray-600': !link.active,
                                'opacity-50 cursor-not-allowed': !link.url
                            }"
                            v-html="link.label"
                        />

                        <!-- Last Page Button -->
                        <Link
                            :href="getPaginationUrl(offers.last_page_url)"
                            class="px-2 py-1 rounded"
                            :class="{
                                'bg-gray-800 text-white': offers.current_page === offers.last_page,
                                'text-gray-200 hover:text-white hover:bg-gray-600': offers.current_page !== offers.last_page,
                                'opacity-50 cursor-not-allowed': !offers.last_page_url
                            }"
                            :disabled="!offers.last_page_url"
                        >
                            <i class="fas fa-angle-double-right"></i>
                        </Link>
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

            <!-- Item Selection Dialog -->
            <Modal :show="showItemSelection" @close="closeItemSelection">
                <div class="p-4 background-color">
                    <div class="modal-header flex justify-between items-center mb-4 pb-2">
                        <div>
                            <h2 class="text-lg font-semibold">Select Items</h2>
                            <p class="text-sm text-gray-400">
                                You can add the same item multiple times with different quantities and descriptions.
                            </p>
                        </div>
                        <button @click="closeItemSelection" class="text-red-700 hover:text-red-500">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="space-y-4">
                        <!-- Search Input -->
                        <div class="px-4 py-2 border-b border-gray-700">
                            <div class="relative">
                                <input
                                    v-model="itemSearchQuery"
                                    type="text"
                                    placeholder="Search items..."
                                    class="w-full px-3 py-2 pl-10 bg-gray-600 border border-gray-600 rounded text-white placeholder-gray-400 focus:border-green-500 focus:outline-none"
                                />
                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            </div>
                        </div>
                        
                        <!-- Tabs and View Toggle -->
                        <div class="flex justify-between border-b border-gray-700 item-selection-tabs">
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

                        <!-- Catalog Items Container -->
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
                                        :class="[
                                            'catalog-item', 
                                            { 'disabled-item': isItemAlreadyAssigned(item.id) }
                                        ]"
                                        @click="toggleItemSelection(item)"
                                    >
                                        <input
                                            type="checkbox"
                                            :value="item.id"
                                            :checked="isItemSelected(item.id)"
                                            :disabled="isItemAlreadyAssigned(item.id)"
                                            class="h-4 w-4 rounded border-gray-300 checkbox-green"
                                            @click.stop
                                        />
                                        <div class="catalog-item-details">
                                            <div class="catalog-item-name">{{ item.name }}</div>
                                            <div class="catalog-item-material">
                                                Material: {{ item.large_material?.name || 'N/A' }}
                                            </div>
                                            <div v-if="isItemAlreadyAssigned(item.id)" class="already-assigned text-orange-600 text-xs">
                                                Already assigned to this offer
                                            </div>
                                        </div>
                                        <div class="catalog-item-price">
                                            {{ item.price ? `${item.price} ден` : 'Price not set' }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Card View -->
                                <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                                    <div v-for="item in filteredLargeMaterialItems"
                                        :key="item.id"
                                        :class="[
                                            'catalog-card', 
                                            { 'disabled-card': isItemAlreadyAssigned(item.id) }
                                        ]"
                                        @click="toggleItemSelection(item)"
                                    >
                                        <div class="catalog-card-image">
                                            <input
                                                type="checkbox"
                                                :value="item.id"
                                                :checked="isItemSelected(item.id)"
                                                :disabled="isItemAlreadyAssigned(item.id)"
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
                                                Material: {{ item.large_material?.name || 'N/A' }}
                                            </p>
                                            <div class="text-xs font-medium text-gray-900">
                                                {{ item.price ? `${item.price} ден` : 'Price not set' }}
                                            </div>
                                            <div v-if="isItemAlreadyAssigned(item.id)" class="already-assigned text-orange-600 text-xs">
                                                Already assigned
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
                                        :class="[
                                            'catalog-item', 
                                            { 'disabled-item': isItemAlreadyAssigned(item.id) }
                                        ]"
                                        @click="toggleItemSelection(item)"
                                    >
                                        <input
                                            type="checkbox"
                                            :value="item.id"
                                            :checked="isItemSelected(item.id)"
                                            :disabled="isItemAlreadyAssigned(item.id)"
                                            class="h-4 w-4 rounded border-gray-300 checkbox-green"
                                            @click.stop
                                        />
                                        <div class="catalog-item-details">
                                            <div class="catalog-item-name">{{ item.name }}</div>
                                            <div class="catalog-item-material">
                                                Material: {{ item.small_material?.name || 'N/A' }}
                                            </div>
                                            <div v-if="isItemAlreadyAssigned(item.id)" class="already-assigned text-orange-600 text-xs">
                                                Already assigned to this offer
                                            </div>
                                        </div>
                                        <div class="catalog-item-price">
                                            {{ item.price ? `${item.price} ден` : 'Price not set' }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Card View -->
                                <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                                    <div v-for="item in filteredSmallMaterialItems"
                                        :key="item.id"
                                        :class="[
                                            'catalog-card', 
                                            { 'disabled-card': isItemAlreadyAssigned(item.id) }
                                        ]"
                                        @click="toggleItemSelection(item)"
                                    >
                                        <div class="catalog-card-image">
                                            <input
                                                type="checkbox"
                                                :value="item.id"
                                                :checked="isItemSelected(item.id)"
                                                :disabled="isItemAlreadyAssigned(item.id)"
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
                                                Material: {{ item.small_material?.name || 'N/A' }}
                                            </p>
                                            <div class="text-xs font-medium text-gray-900">
                                                {{ item.price ? `${item.price} ден` : 'Price not set' }}
                                            </div>
                                            <div v-if="isItemAlreadyAssigned(item.id)" class="already-assigned text-orange-600 text-xs">
                                                Already assigned
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Action buttons -->
                        <div class="flex justify-end space-x-2 pt-2 border-t border-gray-700">
                            <button @click="closeItemSelection" class="btn bg-gray-600 text-white">
                                Cancel
                            </button>
                            <button 
                                @click="assignSelectedItems" 
                                class="btn btn-success text-white" 
                                :disabled="selectedItems.length === 0"
                            >
                                Assign {{ selectedItems.length }} Item{{ selectedItems.length !== 1 ? 's' : '' }} to Offer
                            </button>
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
        availableYears: Array,
        currentFiscalYear: Number,
        clients: Array,
        validityDaysOptions: Array,
    },

    data() {
        return {
            showModal: false,
            showItemsModal: false,
            showDeclineModal: false,
            showDeleteDialog: false,
            selectedOffer: null,
            itemsViewMode: 'grid',
            currentTab: this.filters?.status || 'pending',
            declineReason: '',
            isEditingDeclineReason: false,
            itemSearchQuery: '',
            isSubmitting: false,
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
            fiscalYear: this.currentFiscalYear || new Date().getFullYear(),
            
            // New properties for client search dropdown
            clientSearch: '',
            showClientDropdown: false,
            clients: [],
            filteredClients: [],
            selectedClientName: '',
            
            // Debounce timer for search input
            searchDebounceTimer: null,
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
        // New computed properties for large/small material filtering
        largeMaterialItems() {
            return this.catalogItems.filter(item => item.large_material && !item.small_material);
        },
        smallMaterialItems() {
            return this.catalogItems.filter(item => item.small_material && !item.large_material);
        },
        filteredLargeMaterialItems() {
            return this.largeMaterialItems.filter(item =>
                item.name.toLowerCase().includes(this.itemSearchQuery.toLowerCase())
            );
        },
        filteredSmallMaterialItems() {
            return this.smallMaterialItems.filter(item =>
                item.name.toLowerCase().includes(this.itemSearchQuery.toLowerCase())
            );
        },
    },

    watch: {
        // Watch for client search input changes to handle clearing
        clientSearch(newValue) {
            if (newValue === '') {
                // If client search is cleared, also clear the client filter
                if (this.filterClient !== '') {
                    this.filterClient = '';
                    this.selectedClientName = '';
                    this.applyFilter();
                }
            }
        }
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
            this.$inertia.visit(route('offers.edit', offer.id));
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

        openItemSelection() {
            this.itemSearchQuery = '';
            this.selectedItems = [];
            this.activeTab = 'large';
            this.viewMode = 'grid';
            
            // Fetch all catalog items when opening the selection dialog
            this.fetchAllCatalogItems().then(() => {
                this.showItemSelection = true;
            });
        },

        closeItemSelection() {
            this.showItemSelection = false;
            this.itemSearchQuery = '';
            this.selectedItems = []; // Clear selections when closing
        },

        selectItem(item) {
            const newItem = {
                id: item.id,
                selection_id: Date.now(),
                name: item.name,
                quantity: 1,
                description: item.description || '',
                custom_price: item.price || 0,
                calculated_price: (item.price || 0),
                isCustomPrice: false,
                _originalPrice: item.price || 0,
                file: item.file,
                large_material: item.large_material,
                small_material: item.small_material
            };
            
            this.editForm.catalog_items.push(newItem);
            this.closeItemSelection();
            // Calculate the price for the newly added item
            this.updatePrice(newItem);
        },

        // New method to toggle item selection - similar to Create.vue
        toggleItemSelection(item) {
            const index = this.selectedItems.findIndex(i => i.id === item.id);
            if (index === -1) {
                // Add item to selected items (no need to check if already assigned)
                this.selectedItems.push(item);
            } else {
                this.selectedItems.splice(index, 1);
            }
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
                // Only calculate using API if there's no custom price 
                // or if the isCustomPrice flag is not set to true
                if (!item.isCustomPrice) {
                    const response = await axios.get('/calculate-price', {
                        params: {
                            catalog_item_id: item.id,
                            client_id: this.editForm.client_id,
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

        // Store original price when focusing the price input
        onPriceFocus(item) {
            // Store the original price to detect changes
            if (!item._originalPrice) {
                item._originalPrice = item.custom_price;
            }
        },
        
        // Handle price changes
        handlePriceChange(item) {
            // If price has been changed from original, mark as custom
            if (item._originalPrice !== item.custom_price) {
                item.isCustomPrice = true;
                
                // Calculate the total price based on custom price
                if (item.custom_price && item.quantity) {
                    item.calculated_price = item.custom_price * item.quantity;
                }
                
                console.log(`Manual price set: ${item.custom_price} per unit, total: ${item.calculated_price}`);
            } else {
                // Reset to auto-calculated if user reverts to original price
                item.isCustomPrice = false;
                this.updatePrice(item);
            }
        },
        
        // Existing handleCustomPriceChange method can be removed or kept as a fallback
        handleCustomPriceChange(item) {
            if (item.custom_price !== null && item.custom_price !== undefined) {
                // Mark as custom price
                item.isCustomPrice = true;
                
                // Calculate the total price by multiplying the custom price per unit by quantity
                item.calculated_price = item.custom_price * item.quantity;
                
                // Add a flag in console for debugging
                console.log(`Manual price set: ${item.custom_price} per unit, total: ${item.calculated_price}`);
            }
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
            this.applyFilter(); // Apply filter immediately when client is selected
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
                sortOrder: this.sortOrder,
                fiscal_year: this.fiscalYear
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
        
        debouncedSearch() {
            // Clear existing timer
            if (this.searchDebounceTimer) {
                clearTimeout(this.searchDebounceTimer);
            }
            
            // Set new timer for debounced search
            this.searchDebounceTimer = setTimeout(() => {
                this.searchOffers();
            }, 500); // 500ms delay
        },
        
        clearFilters() {
            this.searchQuery = '';
            this.filterClient = '';
            this.filterValidityDays = '';
            this.sortOrder = 'desc';
            this.clientSearch = '';
            this.selectedClientName = '';
            // Note: We don't reset fiscalYear - user can change it separately
            this.visitWithFilters(true);
        },
        
        getPaginationUrl(originalUrl) {
            if (!originalUrl) return null;
            
            // Parse the original URL to get the base and page parameter
            const url = new URL(originalUrl);
            
            // Add current filter parameters to the URL
            const params = {
                status: this.currentTab,
                searchQuery: this.searchQuery,
                filterClient: this.filterClient,
                filterValidityDays: this.filterValidityDays,
                sortOrder: this.sortOrder,
                fiscal_year: this.fiscalYear
            };
            
            // Add filter parameters to the URL
            Object.keys(params).forEach(key => {
                if (params[key] !== '' && params[key] !== null && params[key] !== undefined) {
                    url.searchParams.set(key, params[key]);
                }
            });
            
            return url.toString();
        },

        // Add a new method to fetch all catalog items
        async fetchAllCatalogItems() {
            try {
                const response = await axios.get('/catalog_items/offer'); // Use the existing endpoint
                this.catalogItems = response.data;
            } catch (error) {
                console.error('Error fetching catalog items:', error);
                const toast = useToast();
                toast.error('Failed to load catalog items');
            }
        },

        // Add new method to assign selected items to the offer
        assignSelectedItems() {
            if (this.selectedItems.length === 0) {
                const toast = useToast();
                toast.warning('Please select at least one item');
                return;
            }

            this.selectedItems.forEach(item => {
                // Always create a new item with a unique selection ID
                const newItem = {
                    id: item.id,
                    selection_id: Date.now() + Math.floor(Math.random() * 1000), // Ensure unique ID
                    name: item.name,
                    quantity: 1,
                    description: item.description || '',
                    custom_price: item.price || 0,
                    calculated_price: (item.price || 0) * 1, // Initial calculation based on price * quantity
                    isCustomPrice: false, // Start with automatic price calculation
                    _originalPrice: item.price || 0, // Store original price for comparison
                    file: item.file,
                    large_material: item.large_material,
                    small_material: item.small_material
                };
                
                this.editForm.catalog_items.push(newItem);
                
                // Calculate price for the new item
                this.updatePrice(newItem);
            });
            
            // Close the dialog and clear selections
            const toast = useToast();
            toast.success(`${this.selectedItems.length} item(s) added to the offer`);
            this.closeItemSelection();
        },

        isItemSelected(itemId) {
            // We're allowing multiple selections of the same item now
            // This method is used for checkbox selection states
            return this.selectedItems.some(item => item.id === itemId);
        },

        isItemAlreadyAssigned(itemId) {
            // Always return false to allow multiple uses of the same item
            return false;
        },

        onQuantityChange(item) {
            if (item.isCustomPrice) {
                // For manual prices, just recalculate the total based on the custom unit price
                if (item.custom_price && item.quantity) {
                    item.calculated_price = item.custom_price * item.quantity;
                }
                console.log(`Quantity changed with manual price: ${item.custom_price} per unit, total: ${item.calculated_price}`);
            } else {
                // For automatic prices, get a new calculation from the API
                this.updatePrice(item);
            }
        },
    },

    mounted() {
        this.fetchClients();
        this.fetchAllCatalogItems(); // Prefetch catalog items
        
        // Add event listener for clicks outside the dropdown
        document.addEventListener('click', this.handleClickOutside);
    },
    
    beforeUnmount() {
        // Remove event listener when component is destroyed
        document.removeEventListener('click', this.handleClickOutside);
        
        // Clear any pending search timer
        if (this.searchDebounceTimer) {
            clearTimeout(this.searchDebounceTimer);
        }
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

.text-xxs {
    font-size: 0.65rem;
}

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

/* Catalog browser specific styles for item selection */
.item-selection-tabs .tab-button {
    @apply px-4 py-2 text-sm font-medium transition-colors duration-200;
    &.active {
        @apply text-green-500 border-b-2 border-green-500;
    }
    &:not(.active) {
        @apply text-gray-400 hover:text-white;
    }
}

.catalog-items-container {
    @apply rounded-b-lg p-3 overflow-y-auto;
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

.catalog-item-material {
    @apply text-sm text-gray-500;
}

.catalog-item-price {
    @apply text-sm text-gray-500 font-medium;
}

.empty-state {
    @apply text-center py-8 text-gray-500;
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

.disabled-item {
    opacity: 0.6;
    cursor: not-allowed;
    pointer-events: none;
    background-color: #f0f0f0;
}

.disabled-card {
    opacity: 0.6;
    cursor: not-allowed;
    pointer-events: none;
    background-color: #f0f0f0;
}

.already-assigned {
    font-style: italic;
    font-size: 0.8rem;
    color: #e57c23;
}

.catalog-item.disabled-item .catalog-item-name {
    text-decoration: line-through;
}

.catalog-card.disabled-card h3 {
    text-decoration: line-through;
}
</style>


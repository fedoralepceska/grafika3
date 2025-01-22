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
                            @click="currentTab = tab.value"
                            :class="[
                                'tab-button',
                                currentTab === tab.value ? 'active' : ''
                            ]"
                        >
                            <span class="tab-label">{{ tab.label }}</span>
                            <span :class="['tab-count', `tab-count-${tab.value}`]">
                                {{ getOffersCount(tab.value) }}
                            </span>
                        </button>
                    </div>
                </div>

                <table class="w-full">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Status</th>
                            <th>Validity Days</th>
                            <th>Items</th>
                            <th>Created At</th>
                            <th class="flex justify-center">Actions</th>
                            <th v-if="currentTab === 'declined'">Decline Reason</th>
                            <th>PDF</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="offer in filteredOffers" :key="offer.id">
                            <!-- Client -->
                            <td>{{ offer.client }}</td>

                            <!-- Status -->
                            <td>
                                <span :class="['status-badge', offer.status]">
                                    {{ offer.status }}
                                </span>
                            </td>

                            <!-- Validity days -->
                            <td>{{ offer.validity_days }} days</td>

                            <!-- Items -->
                            <td>
                                <button
                                    @click="viewItems(offer)"
                                    class="text-green hover:text-light-green"
                                >
                                    {{ offer.items_count }}
                                    <i class="fas fa-eye ml-1"></i>
                                </button>
                            </td>

                            <!-- Created at -->
                            <td>{{ formatDate(offer.created_at) }}</td>

                            <!-- Actions -->
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
                        </tr>
                    </tbody>
                </table>
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
                                            <span>{{ item.price ? `€${item.price}` : '-' }}</span>
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
                                                    <span>{{ item.price ? `€${item.price}` : '-' }}</span>
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
                        <button @click="closeEditDialog" class="text-light-gray hover:text-white">
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
                                        class="px-3 py-1 bg-green text-white rounded hover:bg-light-green"
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
                                                    />
                                                </div>

                                                <!-- Price -->
                                                <div class="flex items-center gap-2 min-w-[200px]">
                                                    <label class="text-gray-400 text-xs whitespace-nowrap">Price:</label>
                                                    <div class="relative flex-1">
                                                        <input
                                                            v-model.number="item.custom_price"
                                                            type="number"
                                                            min="0"
                                                            step="0.01"
                                                            class="w-full rounded bg-white border-gray-700 text-black text-sm py-1 px-2"
                                                            placeholder="Price per unit"
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
                        <button @click="closeItemSelection" class="text-light-gray hover:text-white">
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
        offers: Array
    },

    data() {
        return {
            showModal: false,
            showItemsModal: false,
            showDeclineModal: false,
            showEditDialog: false,
            showItemSelection: false,
            selectedOffer: null,
            itemsViewMode: 'grid',
            currentTab: 'pending',
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
            clients: [],
            catalogItems: [],
            selectedClient: null,
            tabs: [
                { label: 'Pending', value: 'pending' },
                { label: 'Accepted', value: 'accepted' },
                { label: 'Declined', value: 'declined' }
            ]
        };
    },

    computed: {
        filteredOffers() {
            return this.offers.filter(offer => offer.status === this.currentTab);
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
        }
    },

    methods: {
        formatDate(date) {
            if (!date) return '-';
            const d = new Date(date);
            return d.toLocaleDateString('en-GB', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
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
            return this.offers.filter(offer => offer.status === status).length;
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
                        quantity: item.quantity
                    }
                });
                item.calculated_price = response.data.price;
                item.custom_price = response.data.price / item.quantity;
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
            if (!price) return '€0.00';
            return new Intl.NumberFormat('de-DE', {
                style: 'currency',
                currency: 'EUR'
            }).format(price);
        },

        navigateToOfferCreate(){
            this.$inertia.visit(`/offer/create`);
        },
    }
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
.dark-gray {
    background-color: $dark-gray;
    padding: 1rem;
    border-radius: 0.5rem;
    margin-top: 1rem;
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
</style>

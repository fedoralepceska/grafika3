<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="Offers List" subtitle="Manage Offers" icon="List.png">
                <Link :href="route('offers.create')" class="btn btn-primary">
                    Create New Offer
                </Link>
            </Header>

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
                            <th>Production Period</th>
                            <th>Items</th>
                            <th>Created At</th>
                            <th v-if="currentTab === 'pending'" class="flex justify-center">Actions</th>
                            <th v-if="currentTab === 'declined'">Decline Reason</th>
                            <th>PDF</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="offer in filteredOffers" :key="offer.id">
                            <td>{{ offer.client }}</td>
                            <td>
                                <span :class="['status-badge', offer.status]">
                                    {{ offer.status }}
                                </span>
                            </td>
                            <td>{{ offer.validity_days }} days</td>
                            <td>{{ formatDate(offer.production_start_date) }} - {{ formatDate(offer.production_end_date) }}</td>
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
                            <td v-if="currentTab === 'pending'" class="space-x-2 flex justify-center">
                                <button 
                                    @click="acceptOffer(offer)"
                                    class="px-2 py-1 btn-success text-white"
                                >
                                    Accept
                                    <i class="fas fa-check ml-1"></i>
                                </button>

                                <button 
                                    @click="openDeclineModal(offer)"
                                    class="px-2 py-1 btn-danger text-white"
                                >
                                    Decline
                                    <i class="fas fa-times ml-1"></i>
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
                            <td class="px-4 py-2 text-right space-x-2">
                                <button
                                    @click="acceptOffer(offer)"
                                    v-if="offer.status === 'pending'"
                                    class="btn btn-sm bg-green-500 hover:bg-green-600 text-white"
                                >
                                    Accept
                                </button>
                                <button
                                    @click="openDeclineModal(offer)"
                                    v-if="offer.status === 'pending'"
                                    class="btn btn-sm bg-red-500 hover:bg-red-600 text-white"
                                >
                                    Decline
                                </button>
                                <button
                                    @click="viewItems(offer)"
                                    class="btn btn-sm bg-blue-500 hover:bg-blue-600 text-white"
                                >
                                    Items
                                </button>
                                <a
                                    :href="route('offers.pdf', offer.id)"
                                    target="_blank"
                                    class="btn btn-sm bg-purple-500 hover:bg-purple-600 text-white"
                                >
                                    <i class="fas fa-file-pdf mr-1"></i>
                                    PDF
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
        </div>
    </MainLayout>
</template>

<script>
import MainLayout from '@/Layouts/MainLayout.vue';
import Header from '@/Components/Header.vue';
import Modal from '@/Components/Modal.vue';
import { Link } from '@inertiajs/vue3';

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
            selectedOffer: null,
            itemsViewMode: 'grid',
            currentTab: 'pending',
            declineReason: '',
            isEditingDeclineReason: false,
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
            try {
                await this.$inertia.patch(route('offers.update-status', offer.id), {
                    status: 'accepted'
                }, {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => {
                        this.$toast.success('Offer has been accepted successfully.');
                    },
                    onError: () => {
                        this.$toast.error('Failed to accept the offer. Please try again.');
                    }
                });
            } catch (error) {
                console.error('Error accepting offer:', error);
                this.$toast.error('An unexpected error occurred.');
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
        },

        async confirmDecline() {
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
                        this.$toast.success(message);
                        this.closeDeclineModal();
                    },
                    onError: (errors) => {
                        if (errors.decline_reason) {
                            this.$toast.error(errors.decline_reason);
                        } else {
                            this.$toast.error('Failed to decline the offer. Please try again.');
                        }
                    }
                });
            } catch (error) {
                console.error('Error declining offer:', error);
                this.$toast.error('An unexpected error occurred.');
            }
        }
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

</style> 
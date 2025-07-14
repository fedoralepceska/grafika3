<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header
                title="Pricing"
                subtitle="Client-Specific Prices"
                icon="Price.png"
                link="client-prices"
                buttonText="Add New Price"
            />

            <div class="dark-gray p-2 text-white">
                <div class="form-container p-2">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="sub-title">{{$t('clientPrices')}}</h2>
                    </div>

                    <!-- Search Bar -->
                    <div class="mb-4">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search by catalog item or client name..."
                            class="rounded p-2 bg-gray-700 text-white w-full"
                            @input="handleSearch"
                        />
                    </div>

                    <!-- Prices Table -->
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-700 text-white">
                                <th class="p-4">{{$t('catalogItem')}}</th>
                                <th class="p-4">{{$t('client')}}</th>
                                <th class="p-4">{{$t('defaultPrice')}}</th>
                                <th class="p-4">{{$t('customPrice')}}</th>
                                <th class="p-4">{{$t('ACTIONS')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="price in prices.data"
                                :key="price.id"
                                class="bg-gray-800 text-white border-t border-gray-700"
                            >
                                <td class="p-4">{{ price.catalog_item.name }}</td>
                                <td class="p-4">{{ price.client.name }}</td>
                                <td class="p-4">{{ formatPrice(price.catalog_item.price) }}</td>
                                <td class="p-4">{{ formatPrice(price.price) }}</td>
                                <td class="p-4">
                                    <div class="flex space-x-2">
                                        <Link
                                            :href="route('client-prices.edit', price.id)"
                                            class="btn btn-secondary"
                                        >
                                            <i class="fas fa-edit"></i> {{$t('Edit')}}
                                        </Link>
                                        <button
                                            @click="confirmDelete(price)"
                                            class="btn btn-danger"
                                        >
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div class="mt-4 flex justify-between items-center">
                        <button
                            :disabled="!prices.prev_page_url"
                            @click="changePage(prices.current_page - 1)"
                            class="btn btn-secondary"
                        >
                            {{$t('previous')}}
                        </button>
                        <span class="text-white">
                            {{$t('page')}} {{ prices.current_page }} {{$t('of')}} {{ prices.last_page }}
                        </span>
                        <button
                            :disabled="!prices.next_page_url"
                            @click="changePage(prices.current_page + 1)"
                            class="btn btn-secondary"
                        >
                            {{$t('next')}}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="modal-backdrop">
            <div class="modal">
                <div class="modal-header">
                    <h2>{{$t('confirmDelete')}}</h2>
                    <button @click="showDeleteModal = false" class="close-button">&times;</button>
                </div>
                <div class="modal-body">
                    <p>{{$t('areYouSureDeletePriceMessage')}}</p>
                    <div class="mt-4 flex justify-end space-x-2">
                        <button @click="showDeleteModal = false" class="btn btn-secondary">
                            {{$t('cancel')}}
                        </button>
                        <button @click="deletePrice" class="btn btn-danger">
                            {{$t('delete')}}
                        </button>
                    </div>
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

export default {
    components: {
        MainLayout,
        Header,
        Link
    },

    props: {
        prices: Object
    },

    data() {
        return {
            searchQuery: '',
            showDeleteModal: false,
            selectedPrice: null
        };
    },

    methods: {
        formatPrice(price) {
            return new Intl.NumberFormat('mk-MK', {
                style: 'currency',
                currency: 'MKD'
            }).format(price);
        },

        handleSearch: _.debounce(function() {
            this.$inertia.get(route('client-prices.index'), {
                search: this.searchQuery
            }, {
                preserveState: true,
                preserveScroll: true
            });
        }, 300),

        changePage(page) {
            this.$inertia.get(route('client-prices.index'), {
                page: page,
                search: this.searchQuery
            }, {
                preserveState: true,
                preserveScroll: true
            });
        },

        confirmDelete(price) {
            this.selectedPrice = price;
            this.showDeleteModal = true;
        },

        async deletePrice() {
            try {
                await this.$inertia.delete(route('client-prices.destroy', this.selectedPrice.id));
                this.showDeleteModal = false;
                this.selectedPrice = null;
                useToast().success('Price deleted successfully');
            } catch (error) {
                useToast().error('Failed to delete price');
            }
        }
    }
};
</script>

<style scoped lang="scss">
.dark-gray {
    background-color: $dark-gray;
    min-height: 20vh;
    min-width: 80vh;
}

.form-container {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.sub-title {
    font-size: 20px;
    font-weight: bold;
    color: $white;
}

.btn {
    padding: 8px 12px;
    border-radius: 4px;
    font-weight: bold;
    cursor: pointer;
    transition: background-color 0.2s;

    &.btn-secondary {
        background-color: $light-gray;
        color: $white;

        &:hover {
            background-color: lighten($light-gray, 10%);
        }

        &:disabled {
            background-color: darken($light-gray, 20%);
            cursor: not-allowed;
        }
    }

    &.btn-danger {
        background-color: $red;
        color: $white;

        &:hover {
            background-color: darken($red, 10%);
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

.modal {
    background-color: $dark-gray;
    border-radius: 8px;
    width: 400px;
    max-width: 90%;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid $light-gray;

    h2 {
        color: $white;
        margin: 0;
    }
}

.modal-body {
    padding: 1rem;
    color: $white;
}

.close-button {
    background: none;
    border: none;
    color: $white;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0;
    line-height: 1;

    &:hover {
        color: $red;
    }
}
</style>

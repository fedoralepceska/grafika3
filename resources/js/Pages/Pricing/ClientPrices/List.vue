<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header
                title="clientSpecificPrices"
                subtitle="manageCustomPrices"
                icon="Price.png"
            />

            <div class="dark-gray p-5 text-white">
                <!-- Search and Add Button -->
                <div class="flex justify-between items-center mb-4">
                    <div class="flex-1 max-w-md">
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search by item or client name..."
                            class="form-input"
                            @input="debouncedSearch"
                        />
                    </div>
                    <Link
                        :href="route('client-prices.create')"
                        class="btn btn-primary ml-4"
                    >
                        {{$t('addClientPrice')}}
                    </Link>
                </div>

                <!-- Prices Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-gray-800">
                        <thead>
                            <tr class="gray">
                                <th class="px-4 py-2 text-left">{{$t('catalogItem')}}</th>
                                <th class="px-4 py-2 text-left">{{$t('client')}}</th>
                                <th class="px-4 py-2 text-left">{{$t('defaultPrice')}}</th>
                                <th class="px-4 py-2 text-left">{{$t('customPrice')}}</th>
                                <th class="px-4 py-2 text-left">{{$t('ACTIONS')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="price in clientPrices"
                                :key="price.id"
                                class="border-b border-gray-700 hover:bg-gray-700"
                            >
                                <td class="px-4 py-2">
                                    {{ price.catalog_item.name }}
                                </td>
                                <td class="px-4 py-2">
                                    {{ price.client.name }}
                                </td>
                                <td class="px-4 py-2">
                                    {{ formatPrice(price.catalog_item.price) }}
                                </td>
                                <td class="px-4 py-2">
                                    {{ formatPrice(price.price) }}
                                </td>
                                <td class="px-4 py-2">
                                    <div class="flex space-x-2">
                                        <Link
                                            :href="route('client-prices.edit', price.id)"
                                            class="btn btn-secondary text-blue-500 hover:text-blue-400"
                                        >
                                            <i class="fas fa-edit"></i> {{$t('Edit')}}
                                        </Link>
<!--                                        <button-->
<!--                                            @click="confirmDelete(price)"-->
<!--                                            class="text-red-500 hover:text-red-400"-->
<!--                                        >-->
<!--                                            Delete-->
<!--                                        </button>-->
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="clientPrices.length === 0">
                                <td colspan="5" class="px-4 py-8 text-center text-gray-400">
                                    {{$t('noClientPricesFound')}}
                                    <Link
                                        :href="route('client-prices.create')"
                                        class="text-blue-500 hover:text-blue-400 ml-2"
                                    >
                                        {{$t('addOneNow')}}
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-4 flex justify-between items-center" v-if="pagination">
                    <div class="flex gap-2">
                        <button
                            :disabled="pagination.current_page === 1"
                            @click="changePage(1)"
                            class="btn btn-secondary"
                            :class="{ 'opacity-50 cursor-not-allowed': pagination.current_page === 1 }"
                        >
                            <i class="fas fa-angle-double-left"></i>
                        </button>
                        <button
                            :disabled="pagination.current_page === 1"
                            @click="changePage(pagination.current_page - 1)"
                            class="btn btn-secondary"
                            :class="{ 'opacity-50 cursor-not-allowed': pagination.current_page === 1 }"
                        >
                            {{$t('previous')}}
                        </button>
                    </div>
                    <span class="text-white">
                        {{$t('page')}} {{ pagination.current_page }} {{$t('of')}} {{ pagination.total_pages }}
                    </span>
                    <div class="flex gap-2">
                        <button
                            :disabled="pagination.current_page === pagination.total_pages"
                            @click="changePage(pagination.current_page + 1)"
                            class="btn btn-secondary"
                            :class="{ 'opacity-50 cursor-not-allowed': pagination.current_page === pagination.total_pages }"
                        >
                            {{$t('next')}}
                        </button>
                        <button
                            :disabled="pagination.current_page === pagination.total_pages"
                            @click="changePage(pagination.total_pages)"
                            class="btn btn-secondary"
                            :class="{ 'opacity-50 cursor-not-allowed': pagination.current_page === pagination.total_pages }"
                        >
                            <i class="fas fa-angle-double-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Delete Confirmation Modal -->
                <Modal v-if="showDeleteModal" @close="showDeleteModal = false">
                    <template #title>
                        {{$t('confirmDelete')}}
                    </template>
                    <template #content>
                        {{$t('areYouSureDeletePriceMessage')}}
                        <strong>{{ selectedPrice?.catalog_item.name }}</strong>
                        {{$t('forClient')}}
                        <strong>{{ selectedPrice?.client.name }}</strong>?
                        {{$t('thisActionCannotBeUndoneMessage')}}
                    </template>
                    <template #footer>
                        <button
                            @click="showDeleteModal = false"
                            class="btn btn-secondary mr-2"
                        >
                            {{$t('cancel')}}
                        </button>
                        <button
                            @click="deletePrice"
                            class="btn btn-danger"
                            :disabled="isDeleting"
                        >
                            {{ isDeleting ? $t('deleting') : $t('delete') }}
                        </button>
                    </template>
                </Modal>
            </div>
        </div>
    </MainLayout>
</template>

<script>
import MainLayout from '@/Layouts/MainLayout.vue';
import Header from '@/Components/Header.vue';
import { Link } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';
import { useToast } from 'vue-toastification';
import debounce from 'lodash.debounce';

export default {
    components: {
        MainLayout,
        Header,
        Link,
        Modal,
        Pagination
    },

    props: {
        clientPrices: {
            type: Array,
            required: true
        },
        pagination: {
            type: Object,
            default: null
        },
        filters: {
            type: Object,
            default: () => ({})
        }
    },

    data() {
        return {
            search: this.filters.search || '',
            showDeleteModal: false,
            selectedPrice: null,
            isDeleting: false
        };
    },

    methods: {
        formatPrice(price) {
            return new Intl.NumberFormat('mk-MK', {
                style: 'currency',
                currency: 'MKD'
            }).format(price);
        },

        confirmDelete(price) {
            this.selectedPrice = price;
            this.showDeleteModal = true;
        },

        async deletePrice() {
            if (!this.selectedPrice) return;

            this.isDeleting = true;
            try {
                await this.$inertia.delete(
                    route('client-prices.destroy', this.selectedPrice.id)
                );
                useToast().success('Client price deleted successfully');
                this.showDeleteModal = false;
            } catch (error) {
                useToast().error('Failed to delete client price');
            } finally {
                this.isDeleting = false;
                this.selectedPrice = null;
            }
        },

        debouncedSearch: debounce(function () {
            this.$inertia.get(
                route('client-prices.index'),
                { search: this.search },
                { preserveState: true }
            );
        }, 300),

        changePage(page) {
            this.$inertia.get(
                route('client-prices.index'),
                {
                    search: this.search,
                    page: page,
                    per_page: this.pagination.per_page
                },
                { preserveState: true }
            );
        }
    }
};
</script>

<style scoped lang="scss">

.gray {
    background-color: $gray;
}

.light-gray {
    background-color: $light-gray;
}

.dark-gray {
    background-color: $dark-gray;
    min-height: 20vh;
}

.form-input {
    width: 100%;
    padding: 0.5rem;
    border-radius: 4px;
    background-color: $light-gray;
    color: $white;
    border: 1px solid $ultra-light-gray;

    &:focus {
        outline: none;
        border-color: $green;
    }
}

.btn {
    padding: 0.5rem 1rem;
    border-radius: 4px;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.2s;

    &.btn-primary {
        background-color: $green;
        color: $white;

        &:hover:not(:disabled) {
            background-color: darken($green, 10%);
        }
    }

    &.btn-secondary {
        background-color: $dark-gray;
        color: $white;

        &:hover:not(:disabled) {
            background-color: darken($dark-gray, 10%);
        }
    }

    &.btn-danger {
        background-color: $red;
        color: $white;

        &:hover:not(:disabled) {
            background-color: darken($red, 10%);
        }

        &:disabled {
            background-color: darken($red, 30%);
            cursor: not-allowed;
        }
    }
}

table {
    width: 100%;
    border-collapse: collapse;

    th {
        font-weight: 500;
        text-align: left;
    }

    td, th {
        padding: 0.75rem 1rem;
    }

    tbody tr {
        &:hover {
            background-color: rgba($light-gray, 0.1);
        }
    }
}
</style>

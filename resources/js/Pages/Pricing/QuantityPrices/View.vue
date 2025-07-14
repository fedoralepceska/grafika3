<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header
                title="Pricing"
                subtitle="Quantity-Based Prices"
                icon="Price.png"
                link="quantity-prices"
                buttonText="Back to List"
            />

            <div class="dark-gray p-2 text-white">
                <div class="form-container p-2">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="sub-title">Quantity Prices for {{ catalogItem.name }} - {{ client.name }}</h2>
                        <div class="flex space-x-2">
                            <Link
                                :href="route('quantity-prices.edit-group', [catalogItem.id, client.id])"
                                class="btn btn-secondary"
                            >
                                <i class="fas fa-edit"></i> Edit
                            </Link>
                            <Link
                                :href="route('quantity-prices.index')"
                                class="btn btn-secondary"
                            >
                                <i class="fas fa-arrow-left"></i> Back
                            </Link>
                        </div>
                    </div>

                    <!-- Catalog Item and Client Info -->
                    <div class="bg-gray-700 p-4 rounded mb-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <h3 class="font-semibold text-lg mb-2">Catalog Item</h3>
                                <p class="text-gray-300">{{ catalogItem.name }}</p>
                                <p class="text-sm text-gray-400">Default Price: {{ formatPrice(catalogItem.price) }}</p>
                            </div>
                            <div>
                                <h3 class="font-semibold text-lg mb-2">Client</h3>
                                <p class="text-gray-300">{{ client.name }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Price Ranges Table -->
                    <div v-if="prices.length > 0">
                        <h3 class="font-semibold text-lg mb-4">Quantity Price Ranges</h3>
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-700 text-white">
                                    <th class="p-4">Quantity Range</th>
                                    <th class="p-4">Price per Unit</th>
                                    <th class="p-4">Total for Range</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="price in prices"
                                    :key="price.id"
                                    class="bg-gray-800 text-white border-t border-gray-700"
                                >
                                    <td class="p-4">{{ formatRange(price.quantity_from, price.quantity_to) }}</td>
                                    <td class="p-4">{{ formatPrice(price.price) }}</td>
                                    <td class="p-4">
                                        <span class="text-gray-300">
                                            {{ formatPrice(price.price) }} per unit
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- No Prices Message -->
                    <div v-else class="text-center py-8">
                        <div class="text-gray-400 text-lg mb-2">No quantity prices defined</div>
                        <p class="text-gray-500">This catalog item and client combination doesn't have any quantity-based pricing yet.</p>
                        <Link
                            :href="route('quantity-prices.edit-group', [catalogItem.id, client.id])"
                            class="btn btn-secondary mt-4"
                        >
                            <i class="fas fa-plus"></i> Add Price Ranges
                        </Link>
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

export default {
    components: {
        MainLayout,
        Header,
        Link
    },

    props: {
        catalogItem: Object,
        client: Object,
        prices: Array
    },

    methods: {
        formatPrice(price) {
            return new Intl.NumberFormat('mk-MK', {
                style: 'currency',
                currency: 'MKD'
            }).format(price);
        },

        formatRange(from, to) {
            if (from === null) {
                return `Up to ${to}`;
            }
            if (to === null) {
                return `${from}+`;
            }
            return `${from} - ${to}`;
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
    }
}
</style> 
<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header
                :title="isEditing ? 'Edit Quantity Price' : 'Create Quantity Price'"
                :subtitle="isEditing ? 'Update quantity-based price' : 'Add new quantity-based price'"
                icon="Price.png"
                link="quantity-prices"
            />

            <div class="dark-gray p-5 text-white">
                <div class="grid grid-cols-2 gap-8">
                    <!-- Left Side - Form -->
                    <div class="form-container light-gray p-5">
                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Catalog Item Selection -->
                            <div class="form-group" v-if="!isEditing">
                                <label class="form-label">Catalog Item</label>
                                <select
                                    v-model="form.catalog_item_id"
                                    class="form-select"
                                    required
                                    :disabled="isEditing"
                                >
                                    <option value="">Select Catalog Item</option>
                                    <option
                                        v-for="item in catalogItems"
                                        :key="item.id"
                                        :value="item.id"
                                    >
                                        {{ item.name }} (Default: {{ formatPrice(item.price) }})
                                    </option>
                                </select>
                            </div>

                            <!-- Client Selection -->
                            <div class="form-group" v-if="!isEditing">
                                <label class="form-label">Client</label>
                                <select
                                    v-model="form.client_id"
                                    class="form-select"
                                    required
                                    :disabled="isEditing"
                                >
                                    <option value="">Select Client</option>
                                    <option
                                        v-for="client in clients"
                                        :key="client.id"
                                        :value="client.id"
                                    >
                                        {{ client.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Quantity Ranges -->
                            <div class="space-y-4">
                                <div v-for="(range, index) in form.ranges" :key="index" class="p-4 border border-gray-600 rounded">
                                    <div class="flex justify-between items-center mb-4">
                                        <h3 class="text-lg font-semibold">Range {{ index + 1 }}</h3>
                                        <button
                                            type="button"
                                            class="text-red-500 hover:text-red-700"
                                            @click="removeRange(index)"
                                            v-if="form.ranges.length > 1"
                                        >
                                            Remove
                                        </button>
                                    </div>

                                    <div class="grid grid-cols-3 gap-4">
                                        <div class="form-group">
                                            <label class="form-label">From</label>
                                            <input
                                                v-model="range.quantity_from"
                                                type="number"
                                                min="0"
                                                class="form-input"
                                                :required="!range.quantity_to"
                                                @input="validateRanges"
                                                placeholder="Leave empty for 'up to'"
                                            />
                                        </div>

                                        <div class="form-group">
                                            <label class="form-label">To</label>
                                            <input
                                                v-model="range.quantity_to"
                                                type="number"
                                                min="0"
                                                class="form-input"
                                                :required="!range.quantity_from"
                                                @input="validateRanges"
                                                placeholder="Leave empty for 'and above'"
                                            />
                                        </div>

                                        <div class="form-group">
                                            <label class="form-label">Price</label>
                                            <input
                                                v-model="range.price"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                class="form-input"
                                                required
                                                placeholder="Price per unit"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- Add Range Button -->
                                <div class="flex justify-center">
                                    <button
                                        type="button"
                                        class="btn btn-secondary"
                                        style="color: white;"
                                        @click="addRange"
                                    >
                                        Add Another Range
                                    </button>
                                </div>
                            </div>

                            <!-- Error Message -->
                            <div v-if="error" class="text-red-500 mb-4">
                                {{ error }}
                            </div>

                            <!-- Submit Button -->
                            <div class="flex justify-end">
                                <button
                                    type="submit"
                                    class="btn btn-primary"
                                    :disabled="!!error"
                                >
                                    {{ isEditing ? 'Update' : 'Create' }} Price Ranges
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Right Side - Visual Preview -->
                    <div class="light-gray p-5">
                        <h2 class="text-xl font-bold mb-4">Price Range Preview</h2>

                        <!-- Selected Item and Client Info -->
                        <div v-if="form.catalog_item_id && form.client_id" class="mb-6 p-4 bg-[#2a3946] rounded">
                            <div class="mb-2">
                                <span class="text-gray-400">Catalog Item:</span>
                                <span class="ml-2">{{ selectedCatalogItem?.name }}</span>
                            </div>
                            <div>
                                <span class="text-gray-400">Client:</span>
                                <span class="ml-2">{{ selectedClient?.name }}</span>
                            </div>
                        </div>

                        <!-- Visual Range Representation -->
                        <div class="space-y-4">
                            <div v-for="(range, index) in sortedRanges" :key="index"
                                class="p-4 border border-gray-600 rounded hover:border-green-500 transition-colors">
                                <div class="flex justify-between items-center">
                                    <div class="flex-1">
                                        <div class="text-lg font-semibold mb-2">
                                            {{ getRangePreview(range) }}
                                        </div>
                                        <div class="text-green-500 font-bold">
                                            {{ formatPrice(range.price) }} per unit
                                        </div>
                                    </div>
                                    <!-- Visual bar representation -->
                                    <div class="w-2/5 h-6 bg-[#2a3946] rounded-full relative overflow-hidden">
                                        <div
                                            class="absolute inset-0 bg-green-500 opacity-50"
                                            :style="getRangeBarStyle(range, index)"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- No ranges message -->
                        <div v-if="!form.ranges.length" class="text-center text-gray-400 py-8">
                            Add ranges to see the preview
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script>
import MainLayout from '@/Layouts/MainLayout.vue';
import Header from '@/Components/Header.vue';
import { useToast } from 'vue-toastification';
import axios from 'axios';

export default {
    components: {
        MainLayout,
        Header
    },

    props: {
        quantityPrice: {
            type: Object,
            default: null
        },
        catalogItems: {
            type: Array,
            required: true
        },
        clients: {
            type: Array,
            required: true
        }
    },

    data() {
        return {
            form: {
                catalog_item_id: this.quantityPrice?.catalog_item_id || '',
                client_id: this.quantityPrice?.client_id || '',
                ranges: this.quantityPrice ? [
                    {
                        quantity_from: this.quantityPrice.quantity_from ?? '',
                        quantity_to: this.quantityPrice.quantity_to ?? '',
                        price: this.quantityPrice.price || ''
                    }
                ] : [
                    {
                        quantity_from: '',
                        quantity_to: '',
                        price: ''
                    }
                ]
            },
            error: null
        };
    },

    computed: {
        isEditing() {
            return !!this.quantityPrice;
        },

        selectedCatalogItem() {
            return this.catalogItems.find(item => item.id === this.form.catalog_item_id);
        },

        selectedClient() {
            return this.clients.find(client => client.id === this.form.client_id);
        },

        sortedRanges() {
            return [...this.form.ranges].sort((a, b) => {
                const fromA = Number(a.quantity_from) || 0;
                const fromB = Number(b.quantity_from) || 0;
                return fromA - fromB;
            });
        },

        maxQuantity() {
            return Math.max(
                ...this.form.ranges.map(range =>
                    Math.max(
                        Number(range.quantity_from) || 0,
                        Number(range.quantity_to) || (Number(range.quantity_from) ? Number(range.quantity_from) * 1.5 : 100)
                    )
                ),
                100 // Minimum scale
            );
        }
    },

    methods: {
        formatPrice(price) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(price);
        },

        addRange() {
            this.form.ranges.push({
                quantity_from: '',
                quantity_to: '',
                price: ''
            });
        },

        removeRange(index) {
            this.form.ranges.splice(index, 1);
            this.validateRanges();
        },

        getRangePreview(range) {
            if (!range.quantity_from && !range.quantity_to) {
                return null;
            }

            if (!range.quantity_from) {
                return `Up to ${range.quantity_to} units`;
            }

            if (!range.quantity_to) {
                return `${range.quantity_from} units and above`;
            }

            return `${range.quantity_from} to ${range.quantity_to} units`;
        },

        validateRanges() {
            this.error = null;

            // Sort ranges by quantity_from to check for overlaps
            const sortedRanges = [...this.form.ranges].sort((a, b) => {
                const fromA = Number(a.quantity_from) || 0;
                const fromB = Number(b.quantity_from) || 0;
                return fromA - fromB;
            });

            for (let i = 0; i < sortedRanges.length; i++) {
                const range = sortedRanges[i];
                const from = Number(range.quantity_from);
                const to = Number(range.quantity_to);

                // Require at least one boundary
                if (!range.quantity_from && !range.quantity_to) {
                    this.error = 'At least one quantity boundary must be set for each range';
                    return;
                }

                // If both are set, validate the range
                if (range.quantity_from && range.quantity_to) {
                    if (from >= to) {
                        this.error = 'The "from" quantity must be less than the "to" quantity';
                        return;
                    }
                }

                // Check for overlaps with next range
                if (i < sortedRanges.length - 1) {
                    const nextRange = sortedRanges[i + 1];
                    const nextFrom = Number(nextRange.quantity_from);

                    if (to && nextFrom && to >= nextFrom) {
                        this.error = 'Ranges cannot overlap';
                        return;
                    }
                }
            }
        },

        async submit() {
    try {
        this.validateRanges();
        if (this.error) {
            return;
        }

        if (this.isEditing) {
            // For editing a single price range
            const formData = {
                quantity_from: this.form.ranges[0].quantity_from || null,
                quantity_to: this.form.ranges[0].quantity_to || null,
                price: parseFloat(this.form.ranges[0].price)
            };
            
            await axios.post(`/price-per-quantity/${this.quantityPrice.id}`, formData);
            useToast().success('Price range updated successfully');
            window.location.href = '/quantity-prices';
        } else {
            // For creating multiple price ranges
            const formData = {
                catalog_item_id: this.form.catalog_item_id,
                client_id: this.form.client_id,
                ranges: this.form.ranges.map(range => ({
                    quantity_from: range.quantity_from || null,
                    quantity_to: range.quantity_to || null,
                    price: parseFloat(range.price)
                }))
            };
            
            await axios.post('/quantity-prices', formData);
            useToast().success(`Price range${this.form.ranges.length > 1 ? 's' : ''} created successfully`);
            window.location.href = '/quantity-prices';
        }
    } catch (error) {
        if (error.response?.data?.errors) {
            const messages = Object.values(error.response.data.errors).flat();
            this.error = messages[0];
        } else if (error.response?.data?.message) {
            this.error = error.response.data.message;
        } else {
            this.error = 'An unexpected error occurred';
        }
        useToast().error(this.error);
    }
},

        getRangeBarStyle(range, index) {
            const max = this.maxQuantity;
            const start = Number(range.quantity_from) || 0;
            const end = Number(range.quantity_to) || max;

            const startPercent = (start / max) * 100;
            const width = ((end - start) / max) * 100;

            return {
                left: `${startPercent}%`,
                width: `${width}%`,
                backgroundColor: `hsl(${120 + (index * 30)}, 70%, 50%)`
            };
        }
    },

    mounted() {
        this.validateRanges();
    }
};
</script>

<style scoped lang="scss">
.light-gray{
    background-color: $light-gray;
}
.dark-gray {
    background-color: $dark-gray;
    min-height: 20vh;
    margin-bottom: 2vh;
    min-width: 80vh;
}

.form-container {
    max-width: none;
}

.form-group {
    margin-bottom: 1rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    color: $white;
    font-weight: 500;
}

.form-select,
.form-input {
    width: 100%;
    padding: 0.5rem;
    border-radius: 4px;
    background-color: $dark-gray;
    color: $white;
    border: 1px solid $ultra-light-gray;

    &:focus {
        outline: none;
        border-color: $green;
    }

    &:disabled {
        background-color: darken($light-gray, 10%);
        cursor: not-allowed;
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

        &:disabled {
            background-color: darken($green, 10%);
            cursor: not-allowed;
        }
    }
}

small {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.875rem;
}

.btn-secondary {
    background-color: transparent;
    border: 1px solid $green;
    color: $green;

    &:hover {
        background-color: rgba($green, 0.1);
    }
}
</style>

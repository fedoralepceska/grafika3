<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header
                :title="isEditing ? 'Edit Quantity Price' : 'Create Quantity Price'"
                :subtitle="isEditing ? 'Update quantity-based price' : 'Add new quantity-based price'"
                icon="Price.png"
                link="quantity-prices"
            />

            <div class="dark-gray p-2 text-white">
                <div class="form-container p-2">
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

                        <!-- Quantity Range -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">Quantity From</label>
                                <input
                                    v-model="form.quantity_from"
                                    type="number"
                                    min="0"
                                    class="form-input"
                                    :required="!form.quantity_to"
                                    :disabled="form.quantity_to === 0"
                                    @input="validateRange"
                                />
                                <small class="text-gray-400">Leave empty for "up to" range</small>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Quantity To</label>
                                <input
                                    v-model="form.quantity_to"
                                    type="number"
                                    min="0"
                                    class="form-input"
                                    :required="!form.quantity_from"
                                    :disabled="form.quantity_from === 0"
                                    @input="validateRange"
                                />
                                <small class="text-gray-400">Leave empty for "and above" range</small>
                            </div>
                        </div>

                        <!-- Range Preview -->
                        <div class="form-group" v-if="rangePreview">
                            <label class="form-label">Range Preview</label>
                            <div class="p-2 bg-gray-700 rounded">
                                {{ rangePreview }}
                            </div>
                        </div>

                        <!-- Price Input -->
                        <div class="form-group">
                            <label class="form-label">Price for this Range</label>
                            <input
                                v-model="form.price"
                                type="number"
                                step="0.01"
                                min="0"
                                class="form-input"
                                required
                            />
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
                                {{ isEditing ? 'Update' : 'Create' }} Price Range
                            </button>
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
import { useToast } from 'vue-toastification';

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
                quantity_from: this.quantityPrice?.quantity_from ?? '',
                quantity_to: this.quantityPrice?.quantity_to ?? '',
                price: this.quantityPrice?.price || ''
            },
            error: null
        };
    },

    computed: {
        isEditing() {
            return !!this.quantityPrice;
        },

        rangePreview() {
            if (!this.form.quantity_from && !this.form.quantity_to) {
                return null;
            }

            if (!this.form.quantity_from) {
                return `Up to ${this.form.quantity_to} units`;
            }

            if (!this.form.quantity_to) {
                return `${this.form.quantity_from} units and above`;
            }

            return `${this.form.quantity_from} to ${this.form.quantity_to} units`;
        }
    },

    methods: {
        formatPrice(price) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(price);
        },

        validateRange() {
            this.error = null;

            // Convert to numbers for comparison
            const from = Number(this.form.quantity_from);
            const to = Number(this.form.quantity_to);

            // Require at least one boundary
            if (!this.form.quantity_from && !this.form.quantity_to) {
                this.error = 'At least one quantity boundary must be set';
                return;
            }

            // If both are set, validate the range
            if (this.form.quantity_from && this.form.quantity_to) {
                if (from >= to) {
                    this.error = 'The "from" quantity must be less than the "to" quantity';
                    return;
                }
            }
        },

        async submit() {
            this.validateRange();
            if (this.error) {
                return;
            }

            try {
                if (this.isEditing) {
                    await this.$inertia.put(
                        route('quantity-prices.update', this.quantityPrice.id),
                        this.form
                    );
                } else {
                    await this.$inertia.post(route('quantity-prices.store'), this.form);
                }

                useToast().success(
                    `Price range ${this.isEditing ? 'updated' : 'created'} successfully`
                );
            } catch (error) {
                if (error.response?.data?.message) {
                    this.error = error.response.data.message;
                } else {
                    useToast().error(
                        `Failed to ${this.isEditing ? 'update' : 'create'} price range`
                    );
                }
            }
        }
    },

    mounted() {
        this.validateRange();
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
    max-width: 600px;
    margin: 0 auto;
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
    background-color: $light-gray;
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
            background-color: darken($green, 30%);
            cursor: not-allowed;
        }
    }
}

small {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.875rem;
}
</style> 
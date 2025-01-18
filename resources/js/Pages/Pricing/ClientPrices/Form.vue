<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header
                :title="isEditing ? 'Edit Client Price' : 'Create Client Price'"
                :subtitle="isEditing ? 'Update client-specific price' : 'Add new client-specific price'"
                icon="Price.png"
                link="client-prices"
            />

            <div class="dark-gray p-5 text-white">
                <div class="form-container p-5 light-gray">
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

                        <!-- Price Input -->
                        <div class="form-group">
                            <label class="form-label">Client-Specific Price</label>
                            <input
                                v-model="form.price"
                                type="number"
                                step="0.01"
                                min="0"
                                class="form-input"
                                required
                            />
                            <small class="text-gray-400">
                                This price will override the default price for this client
                            </small>
                        </div>

                        <!-- Error Message -->
                        <div v-if="error" class="text-red-500 mb-4">
                            {{ error }}
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end">
                            <button type="submit" class="btn btn-primary">
                                {{ isEditing ? 'Update' : 'Create' }} Client Price
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
        clientPrice: {
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
                catalog_item_id: this.clientPrice?.catalog_item_id || '',
                client_id: this.clientPrice?.client_id || '',
                price: this.clientPrice?.price || ''
            },
            error: null
        };
    },

    computed: {
        isEditing() {
            return !!this.clientPrice;
        }
    },

    methods: {
        formatPrice(price) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(price);
        },

        async submit() {
            try {
                if (this.isEditing) {
                    await this.$inertia.put(
                        route('client-prices.update', this.clientPrice.id),
                        this.form
                    );
                } else {
                    await this.$inertia.post(route('client-prices.store'), this.form);
                }

                useToast().success(
                    `Client price ${this.isEditing ? 'updated' : 'created'} successfully`
                );
            } catch (error) {
                if (error.response?.data?.message) {
                    this.error = error.response.data.message;
                } else {
                    useToast().error(
                        `Failed to ${this.isEditing ? 'update' : 'create'} client price`
                    );
                }
            }
        }
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

        &:hover {
            background-color: darken($green, 10%);
        }
    }
}

small {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.875rem;
}
</style> 
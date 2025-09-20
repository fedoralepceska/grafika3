<template>
    <div class="trade-items-container">
        <div class="trade-items-header">
            <h4 class="section-title">Trade Items</h4>
            <button 
                v-if="!isAddingNew" 
                @click="startAddingNew" 
                class="btn btn-add"
                title="Add Trade Item"
            >
                <i class="fas fa-plus"></i> Add Item
            </button>
        </div>

        <!-- Add New Trade Item Form -->
        <div v-if="isAddingNew" class="add-item-form">
            <div class="form-header">
                <h5>Add New Trade Item</h5>
                <button @click="cancelAddNew" class="btn btn-cancel-small">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="form-grid">
                <div class="form-field">
                    <label>Article:</label>
                    <select v-model="newItem.article_id" class="form-select" @change="onArticleSelect">
                        <option value="">Select Article</option>
                        <option 
                            v-for="article in availableArticles" 
                            :key="article.id" 
                            :value="article.id"
                        >
                            {{ article.code ? `${article.code} - ` : '' }}{{ article.name }}
                        </option>
                    </select>
                </div>

                <div class="form-field">
                    <label>Quantity:</label>
                    <input 
                        v-model.number="newItem.quantity" 
                        type="number" 
                        min="1" 
                        class="form-input"
                        placeholder="Enter quantity"
                    />
                </div>

                <div class="form-field">
                    <label>Unit Price:</label>
                    <input 
                        v-model.number="newItem.unit_price" 
                        type="number" 
                        step="0.01" 
                        min="0" 
                        class="form-input"
                        placeholder="Enter unit price"
                    />
                </div>

                <div class="form-field">
                    <label>VAT Rate (%):</label>
                    <select v-model.number="newItem.vat_rate" class="form-select">
                        <option :value="0">0%</option>
                        <option :value="5">5%</option>
                        <option :value="10">10%</option>
                        <option :value="18">18%</option>
                    </select>
                </div>
            </div>

            <div class="form-totals" v-if="newItem.quantity && newItem.unit_price">
                <div class="total-item">
                    <span>Total Price:</span>
                    <strong>{{ formatPrice(newItem.quantity * newItem.unit_price) }} ден.</strong>
                </div>
                <div class="total-item">
                    <span>VAT Amount:</span>
                    <strong>{{ formatPrice((newItem.quantity * newItem.unit_price) * (newItem.vat_rate / 100)) }} ден.</strong>
                </div>
                <div class="total-item total-final">
                    <span>Total with VAT:</span>
                    <strong>{{ formatPrice((newItem.quantity * newItem.unit_price) * (1 + newItem.vat_rate / 100)) }} ден.</strong>
                </div>
            </div>

            <div class="form-actions">
                <button 
                    @click="saveNewItem" 
                    class="btn btn-save"
                    :disabled="!canSaveNewItem || isSaving"
                >
                    <i class="fas fa-save"></i> Save Item
                </button>
                <button @click="cancelAddNew" class="btn btn-cancel">
                    Cancel
                </button>
            </div>
        </div>

        <!-- Trade Items List -->
        <div class="trade-items-list" v-if="localTradeItems.length > 0">
            <div 
                v-for="item in localTradeItems" 
                :key="item.id" 
                class="trade-item-card"
            >
                <div class="item-header">
                    <div class="item-title">
                        <span class="article-code" v-if="item.article_code">Article Code: {{ item.article_code }}</span>
                        <span class="article-name">{{ item.article_name }}</span>
                    </div>
                    <div class="item-actions">
                        <button 
                            v-if="!isEditing[item.id]" 
                            @click="startEditing(item)" 
                            class="btn btn-edit-small"
                            title="Edit Item"
                        >
                            <i class="fas fa-edit"></i>
                        </button>
                        <button 
                            v-if="isEditing[item.id]" 
                            @click="saveItem(item)" 
                            class="btn btn-save-small"
                            :disabled="isSaving"
                            title="Save Changes"
                        >
                            <i class="fas fa-save"></i>
                        </button>
                        <button 
                            v-if="isEditing[item.id]" 
                            @click="cancelEdit(item)" 
                            class="btn btn-cancel-small"
                            title="Cancel"
                        >
                            <i class="fas fa-times"></i>
                        </button>
                        <button 
                            @click="deleteItem(item)" 
                            class="btn btn-delete"
                            title="Delete Item"
                        >
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>

                <div class="item-details">
                    <div class="detail-item">
                        <label>Quantity:</label>
                        <input 
                            v-if="isEditing[item.id]"
                            v-model.number="editForms[item.id].quantity"
                            type="number"
                            min="1"
                            class="form-input-small"
                        />
                        <span v-else class="detail-value">{{ item.quantity }}</span>
                    </div>

                    <div class="detail-item">
                        <label>Unit Price:</label>
                        <input 
                            v-if="isEditing[item.id]"
                            v-model.number="editForms[item.id].unit_price"
                            type="number"
                            step="0.01"
                            min="0"
                            class="form-input-small"
                        />
                        <span v-else class="detail-value">{{ formatPrice(getItemUnitPrice(item)) }} ден.</span>
                    </div>

                    <div class="detail-item">
                        <label>VAT Rate:</label>
                        <select 
                            v-if="isEditing[item.id]"
                            v-model.number="editForms[item.id].vat_rate"
                            class="form-select-small"
                        >
                            <option :value="0">0%</option>
                            <option :value="5">5%</option>
                            <option :value="10">10%</option>
                            <option :value="18">18%</option>
                        </select>
                        <span v-else class="detail-value">{{ item.vat_rate }}%</span>
                    </div>

                    <div class="detail-item">
                        <label>Total Price:</label>
                        <span class="detail-value total-price">{{ formatPrice(getItemTotalPrice(item)) }} ден.</span>
                    </div>

                    <div class="detail-item">
                        <label>VAT Amount:</label>
                        <span class="detail-value">{{ formatPrice(getItemVatAmount(item)) }} ден.</span>
                    </div>

                    <div class="detail-item">
                        <label>Total with VAT:</label>
                        <span class="detail-value total-final">{{ formatPrice(getItemTotalWithVat(item)) }} ден.</span>
                    </div>
                </div>
            </div>
        </div>

        <div v-else-if="!isAddingNew" class="no-items">
            <p>No trade items found. Click "Add Item" to add trade items to this invoice.</p>
        </div>

        <!-- Error Display -->
        <div v-if="error" class="error-message">
            {{ error }}
        </div>
    </div>
</template>

<script>
import axios from 'axios';
import { useToast } from 'vue-toastification';

export default {
    name: 'TradeItemsEdit',
    props: {
        tradeItems: {
            type: Array,
            default: () => []
        },
        fakturaId: {
            type: Number,
            required: true
        }
    },
    emits: ['trade-items-updated'],
    data() {
        return {
            localTradeItems: [...this.tradeItems],
            availableArticles: [],
            isAddingNew: false,
            isEditing: {},
            editForms: {},
            isSaving: false,
            error: null,
            newItem: {
                article_id: '',
                quantity: 1,
                unit_price: 0,
                vat_rate: 18
            },
            toast: useToast()
        };
    },
    computed: {
        canSaveNewItem() {
            return this.newItem.article_id && 
                   this.newItem.quantity > 0 && 
                   this.newItem.unit_price >= 0;
        }
    },
    watch: {
        tradeItems: {
            handler(newItems) {
                this.localTradeItems = [...newItems];
            },
            deep: true
        }
    },
    async mounted() {
        await this.loadAvailableArticles();
    },
    methods: {
        async loadAvailableArticles() {
            try {
                const response = await axios.get('/invoice/available-articles');
                this.availableArticles = response.data;
            } catch (error) {
                console.error('Error loading articles:', error);
                this.toast.error('Failed to load available articles');
            }
        },

        startAddingNew() {
            this.isAddingNew = true;
            this.newItem = {
                article_id: '',
                quantity: 1,
                unit_price: 0,
                vat_rate: 18
            };
            this.error = null;
        },

        cancelAddNew() {
            this.isAddingNew = false;
            this.newItem = {
                article_id: '',
                quantity: 1,
                unit_price: 0,
                vat_rate: 18
            };
            this.error = null;
        },

        onArticleSelect() {
            const selectedArticle = this.availableArticles.find(
                article => article.id == this.newItem.article_id
            );
            if (selectedArticle) {
                this.newItem.unit_price = selectedArticle.price || 0;
                // Set VAT rate based on tax type
                const vatRates = { 0: 0, 1: 18, 2: 5, 3: 10 };
                this.newItem.vat_rate = vatRates[selectedArticle.tax_type] || 18;
            }
        },

        async saveNewItem() {
            if (!this.canSaveNewItem || this.isSaving) return;

            try {
                this.isSaving = true;
                this.error = null;

                const response = await axios.post(
                    `/invoice/${this.fakturaId}/trade-items`,
                    this.newItem
                );

                if (response.data.trade_item) {
                    this.localTradeItems.push(response.data.trade_item);
                    this.$emit('trade-items-updated', this.localTradeItems);
                    this.toast.success('Trade item added successfully');
                    this.cancelAddNew();
                }

            } catch (error) {
                console.error('Error adding trade item:', error);
                this.error = error.response?.data?.message || 'Failed to add trade item';
                this.toast.error(this.error);
            } finally {
                this.isSaving = false;
            }
        },

        startEditing(item) {
            
            this.isEditing[item.id] = true;
            this.editForms[item.id] = {
                quantity: Number(item.quantity || 0),
                unit_price: Number(item.unit_price || 0),
                vat_rate: Number(item.vat_rate || 0)
            };
            
            this.error = null;
        },

        cancelEdit(item) {
            this.isEditing[item.id] = false;
            delete this.editForms[item.id];
            this.error = null;
        },

        async saveItem(item) {
            if (this.isSaving) return;

            try {
                this.isSaving = true;
                this.error = null;

                const response = await axios.put(
                    `/invoice/${this.fakturaId}/trade-items/${item.id}`,
                    this.editForms[item.id]
                );

                if (response.data.trade_item) {
                    const index = this.localTradeItems.findIndex(t => t.id === item.id);
                    if (index !== -1) {
                        this.localTradeItems.splice(index, 1, response.data.trade_item);
                    }
                    this.$emit('trade-items-updated', this.localTradeItems);
                    this.toast.success('Trade item updated successfully');
                    this.cancelEdit(item);
                }

            } catch (error) {
                console.error('Error updating trade item:', error);
                this.error = error.response?.data?.message || 'Failed to update trade item';
                this.toast.error(this.error);
            } finally {
                this.isSaving = false;
            }
        },

        async deleteItem(item) {
            if (!confirm('Are you sure you want to delete this trade item?')) {
                return;
            }

            try {
                this.error = null;

                await axios.delete(`/invoice/${this.fakturaId}/trade-items/${item.id}`);

                const index = this.localTradeItems.findIndex(t => t.id === item.id);
                if (index !== -1) {
                    this.localTradeItems.splice(index, 1);
                }
                this.$emit('trade-items-updated', this.localTradeItems);
                this.toast.success('Trade item deleted successfully');

            } catch (error) {
                console.error('Error deleting trade item:', error);
                this.error = error.response?.data?.message || 'Failed to delete trade item';
                this.toast.error(this.error);
            }
        },

        formatPrice(price) {
            if (!price && price !== 0) return '0.00';
            const result = typeof price === 'number' ? price.toFixed(2) : '0.00';
            return result;
        },

        getItemUnitPrice(item) {
            return Number(item.unit_price || 0);
        },

        getItemTotalPrice(item) {
            
            if (this.isEditing[item.id]) {
                const editForm = this.editForms[item.id];
                const total = Number(editForm.quantity || 0) * Number(editForm.unit_price || 0);
                return total;
            }
            
            // If stored total_price is 0 or missing, calculate from unit_price and quantity
            const storedTotal = Number(item.total_price || 0);
            
            if (storedTotal === 0 && item.unit_price && item.quantity) {
                const calculatedTotal = Number(item.quantity || 0) * Number(item.unit_price || 0);
                return calculatedTotal;
            }
            
            return storedTotal;
        },

        getItemVatAmount(item) {
            if (this.isEditing[item.id]) {
                const editForm = this.editForms[item.id];
                const totalPrice = Number(editForm.quantity || 0) * Number(editForm.unit_price || 0);
                const vatAmount = totalPrice * (Number(editForm.vat_rate || 0) / 100);
                return vatAmount;
            }
            
            // If stored vat_amount is 0 or missing, calculate from unit_price, quantity, and vat_rate
            const storedVatAmount = Number(item.vat_amount || 0);
            if (storedVatAmount === 0 && item.unit_price && item.quantity && item.vat_rate) {
                const totalPrice = Number(item.quantity || 0) * Number(item.unit_price || 0);
                const calculatedVatAmount = totalPrice * (Number(item.vat_rate || 0) / 100);
                return calculatedVatAmount;
            }
            
            return storedVatAmount;
        },

        getItemTotalWithVat(item) {
            if (this.isEditing[item.id]) {
                const editForm = this.editForms[item.id];
                const totalPrice = Number(editForm.quantity || 0) * Number(editForm.unit_price || 0);
                const vatAmount = totalPrice * (Number(editForm.vat_rate || 0) / 100);
                const totalWithVat = totalPrice + vatAmount;
                return totalWithVat;
            }
            
            // Calculate from individual components if stored totals are 0
            const totalPrice = this.getItemTotalPrice(item);
            const vatAmount = this.getItemVatAmount(item);
            const totalWithVat = Number(totalPrice) + Number(vatAmount);
            
            return totalWithVat;
        }
    }
};
</script>

<style scoped lang="scss">
// Color Variables from existing design
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

.trade-items-container {
    background: $dark-gray;
    border-radius: 3px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    border: 1px solid $light-gray;
}

.trade-items-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f5f5f5;
}

.section-title {
    color: $white;
    font-size: 18px;
    font-weight: 600;
    margin: 0;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 6px;

    &:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    i {
        font-size: 14px;
    }
}

.btn-add {
    background-color: $green;
    color: $white;

    &:hover:not(:disabled) {
        background-color: darken($green, 10%);
    }
}

.btn-edit-small, .btn-save-small, .btn-cancel-small {
    padding: 4px 8px;
    font-size: 12px;
    min-width: 32px;
    justify-content: center;

    i {
        font-size: 12px;
    }
}

.btn-edit-small {
    background-color: #3182ce;
    color: white;

    &:hover:not(:disabled) {
        background-color: #2c5aa0;
    }
}

.btn-save, .btn-save-small {
    background-color: #38a169;
    color: white;

    &:hover:not(:disabled) {
        background-color: #2f855a;
    }
}

.btn-cancel, .btn-cancel-small {
    background-color: #e53e3e;
    color: white;

    &:hover:not(:disabled) {
        background-color: #c53030;
    }
}

.btn-delete {
    padding: 4px 8px;
    background-color: #e53e3e;
    color: white;
    font-size: 12px;
    min-width: 32px;
    justify-content: center;

    &:hover:not(:disabled) {
        background-color: #c53030;
    }

    i {
        font-size: 12px;
    }
}

.add-item-form {
    background-color: rgba(white, 0.15);
    border: 1px solid $light-gray;
    border-radius: 3px;
    padding: 20px;
    margin-bottom: 20px;
}

.form-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;

    h5 {
        margin: 0;
        color: $white;
        font-size: 16px;
        font-weight: 600;
    }
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.form-field {
    display: flex;
    flex-direction: column;
    gap: 5px;

    label {
        font-weight: 600;
        color: $white;
        font-size: 14px;
    }
}

.form-input, .form-select {
    padding: 8px 12px;
    border: 1px solid $light-gray;
    border-radius: 3px;
    font-size: 14px;
    background-color: $white;
    color: $black;
    transition: border-color 0.2s ease;

    &:focus {
        outline: none;
        border-color: $light-green;
        box-shadow: 0 0 0 2px rgba($light-green, 0.3);
    }
}

.form-input-small, .form-select-small {
    padding: 4px 8px;
    border: 1px solid $light-gray;
    border-radius: 3px;
    font-size: 13px;
    background-color: $white;
    color: $black;
    width: 100%;

    &:focus {
        outline: none;
        border-color: $light-green;
        box-shadow: 0 0 0 2px rgba($light-green, 0.3);
    }
}

.form-totals {
    background-color: $white;
    border: 1px solid $light-gray;
    border-radius: 3px;
    padding: 15px;
    margin-bottom: 20px;
}

.total-item {
    display: flex;
    justify-content: space-between;
    padding: 5px 0;

    &.total-final {
        border-top: 1px solid #e2e8f0;
        margin-top: 10px;
        padding-top: 10px;
        font-size: 16px;
        color: greenyellow;
    }
}

.form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.trade-items-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.trade-item-card {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 15px;
    background-color: rgba(white, 0.15);
}

.item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.item-title {
    display: flex;
    flex-direction: column;
    gap: 4px;

    .article-code {
        font-size: 12px;
        color: $white;
        font-weight: 500;
    }

    .article-name {
        font-size: 16px;
        font-weight: 600;
        color: $white;
    }
}

.item-actions {
    display: flex;
    gap: 5px;
}

.item-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 5px;

    label {
        font-weight: 600;
        color: $white;
        font-size: 13px;
    }

    .detail-value {
        color: $white;
        font-size: 14px;

        &.total-price {
            font-weight: 600;
            color: #3182ce;
        }

        &.total-final {
            font-weight: 600;
            color: greenyellow;
            font-size: 15px;
        }
    }
}

.no-items {
    text-align: center;
    padding: 40px 20px;
    color: #718096;
    font-style: italic;
}

.error-message {
    background-color: #fed7d7;
    color: #c53030;
    padding: 10px 15px;
    border-radius: 4px;
    margin-top: 15px;
    border: 1px solid #feb2b2;
    font-size: 14px;
}

@media (max-width: 768px) {
    .form-grid, .item-details {
        grid-template-columns: 1fr;
    }

    .trade-items-header {
        flex-direction: column;
        gap: 15px;
        align-items: stretch;
    }

    .item-header {
        flex-direction: column;
        gap: 15px;
        align-items: stretch;
    }

    .item-actions {
        justify-content: center;
    }

    .form-actions {
        justify-content: center;
    }
}
</style>

<template>
    <div>
        <button @click="openDialog" class="btn edit-btn">
            <i class="fa fa-edit"></i>
        </button>

        <div v-if="isOpen" class="modal-backdrop" @click="closeDialog">
            <div class="modal-content background" @click.stop>
                <div class="modal-header">
                    <span class="text-h5 text-white">Edit Invoice #{{ invoice.incoming_number }}</span>
                    <button @click="closeDialog" class="close-btn">&times;</button>
                </div>
                <div class="modal-body">
                    <form @submit.prevent="updateInvoice" class="form-container">
                        <div class="left-section">
                            <!-- Basic Info Section -->
                            <div>
                                <div class="section-title">Basic Information</div>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label class="mr-4 width100">Invoice Number</label>
                                        <input type="text" v-model="form.incoming_number" class="form-input">
                                    </div>
                                    <div class="form-group">
                                        <label class="mr-4 width100">Date</label>
                                        <input type="date" v-model="form.date" class="form-input">
                                    </div>
                                    <div class="form-group">
                                        <label class="mr-4 width100">Client</label>
                                        <select v-model="form.client_id" class="form-input">
                                            <option v-for="client in clients" :key="client.id" :value="client.id">
                                                {{ client.name }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="mr-4 width100">Warehouse</label>
                                        <select v-model="form.warehouse" class="form-input">
                                            <option v-for="warehouse in warehouses" :key="warehouse" :value="warehouse">
                                                {{ warehouse }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Description Section -->
                            <div>
                                <div class="section-title">Additional Information</div>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label class="mr-4 width100">Description</label>
                                        <input type="text" v-model="form.description" class="form-input">
                                    </div>
                                    <div class="form-group">
                                        <label class="mr-4 width100">Comment</label>
                                        <input type="text" v-model="form.comment" class="form-input">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="right-section flex flex-col justify-between">
                            <!-- Types Section -->
                            <div>
                                <div class="section-title">Invoice Types</div>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label class="mr-4 width100">Cost Type</label>
                                        <select v-model="form.cost_type" class="form-input">
                                            <option v-for="type in costTypes" :key="type.id" :value="type.id">
                                                {{ type.name }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="mr-4 width100">Billing Type</label>
                                        <select v-model="form.billing_type" class="form-input">
                                            <option v-for="type in billTypes" :key="type.id" :value="type.id">
                                                {{ type.name }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gray-700 p-2 rounded-md">
                            <div class="section-title">Price and Tax Calculation</div>
                            <div class="tax-grid">
                                <div class="tax-section">
                                    <div class="tax-row">
                                        <label>Amount TAX A</label>
                                        <div class="tax-inputs">
                                            <input type="number" v-model.number="form.taxAmounts.taxA" class="amount-input">
                                            <input type="text" disabled class="rate-input" value="18.00">
                                        </div>
                                    </div>
                                    <div class="tax-row">
                                        <label>Amount TAX B</label>
                                        <div class="tax-inputs">
                                            <input type="number" v-model.number="form.taxAmounts.taxB" class="amount-input">
                                            <input type="text" disabled class="rate-input" value="5.00">
                                        </div>
                                    </div>
                                    <div class="tax-row">
                                        <label>Amount TAX C</label>
                                        <div class="tax-inputs">
                                            <input type="number" v-model.number="form.taxAmounts.taxC" class="amount-input">
                                            <input type="text" disabled class="rate-input" value="10.00">
                                        </div>
                                    </div>
                                    <div class="tax-row">
                                        <label>Amount TAX D</label>
                                        <div class="tax-inputs">
                                            <input type="number" v-model.number="form.taxAmounts.taxD" class="amount-input">
                                            <input type="text" disabled class="rate-input" value="00.00">
                                        </div>
                                    </div>
                                </div>

                                <div class="totals-section">
                                    <div class="total-row">
                                        <label>Amount:</label>
                                        <input type="text" :value="calculatedAmount" disabled class="total-input">
                                    </div>
                                    <div class="total-row">
                                        <label>Tax:</label>
                                        <input type="text" :value="calculatedTax" disabled class="total-input">
                                    </div>
                                    <div class="total-row">
                                        <label>Total:</label>
                                        <input type="text" :value="calculatedTotal" disabled class="total-input">
                                    </div>
                                </div>
                            </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            
                        </div>
                        <div class="modal-footer flex  justify-center gap-4">
                            <button type="button" @click="closeDialog" class="btn red">Cancel</button>
                            <button type="submit" class="btn green" :disabled="loading">
                                {{ loading ? 'Saving...' : 'Save Changes' }}
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, computed, watch } from 'vue'
import axios from 'axios'

export default {
    props: {
        invoice: {
            type: Object,
            required: true
        },
        costTypes: {
            type: Array,
            required: true
        },
        billTypes: {
            type: Array,
            required: true
        },
        warehouses: {
            type: Array,
            required: true
        },
        clients: {
            type: Array,
            required: true
        }
    },
    setup(props, { emit }) {
        const isOpen = ref(false)
        const loading = ref(false)
        const form = ref({
            incoming_number: '',
            client_id: null,
            warehouse: '',
            cost_type: null,
            billing_type: null,
            description: '',
            comment: '',
            date: '',
            taxAmounts: {
                taxA: 0,
                taxB: 0,
                taxC: 0,
                taxD: 0
            }
        })

        const taxRates = {
            taxA: 18,
            taxB: 5,
            taxC: 10,
            taxD: 0
        }

        const calculatedAmount = computed(() => {
            const amount = Object.values(form.value.taxAmounts).reduce((sum, amount) => sum + (amount || 0), 0);
            return formatNumber(amount);
        })

        const calculatedTax = computed(() => {
            const tax = Object.entries(form.value.taxAmounts).reduce((sum, [key, amount]) => {
                const rate = taxRates[key] / 100;
                return sum + ((amount || 0) * rate);
            }, 0);
            return formatNumber(tax);
        })

        const calculatedTotal = computed(() => {
            const amount = Object.values(form.value.taxAmounts).reduce((sum, amount) => sum + (amount || 0), 0);
            const tax = Object.entries(form.value.taxAmounts).reduce((sum, [key, amount]) => {
                const rate = taxRates[key] / 100;
                return sum + ((amount || 0) * rate);
            }, 0);
            return formatNumber(amount + tax);
        })

        const formatNumber = (value) => {
            const num = Number(value);
            return num.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        const calculateInitialTaxAmounts = (totalAmount, totalTax) => {
            const taxAmounts = {
                taxA: 0,
                taxB: 0,
                taxC: 0,
                taxD: 0
            };

            if (totalAmount > 0 && totalTax > 0) {
                const taxPercentage = (totalTax / totalAmount) * 100;
                const roundedPercentage = Math.round(taxPercentage * 100) / 100;

                // Match the tax percentage to determine which rate was used
                switch (roundedPercentage) {
                    case 18:
                        taxAmounts.taxA = totalAmount;
                        break;
                    case 5:
                        taxAmounts.taxB = totalAmount;
                        break;
                    case 10:
                        taxAmounts.taxC = totalAmount;
                        break;
                    case 0:
                        taxAmounts.taxD = totalAmount;
                        break;
                    default:
                        // If no exact match, try to split the amount based on the tax
                        if (totalTax > 0) {
                            let remainingTax = totalTax;
                            let remainingAmount = totalAmount;

                            // Try to match tax amounts starting with highest rate
                            if (remainingTax >= remainingAmount * 0.18) {
                                taxAmounts.taxA = Math.round((remainingTax / 0.18) * 100) / 100;
                                remainingAmount -= taxAmounts.taxA;
                                remainingTax -= taxAmounts.taxA * 0.18;
                            }
                            if (remainingTax >= remainingAmount * 0.10) {
                                taxAmounts.taxC = Math.round((remainingTax / 0.10) * 100) / 100;
                                remainingAmount -= taxAmounts.taxC;
                                remainingTax -= taxAmounts.taxC * 0.10;
                            }
                            if (remainingTax >= remainingAmount * 0.05) {
                                taxAmounts.taxB = Math.round((remainingTax / 0.05) * 100) / 100;
                                remainingAmount -= taxAmounts.taxB;
                                remainingTax -= taxAmounts.taxB * 0.05;
                            }
                            if (remainingAmount > 0) {
                                taxAmounts.taxD = remainingAmount;
                            }
                        }
                }
            }

            return taxAmounts;
        };

        const openDialog = () => {
            const totalAmount = parseFloat(props.invoice.amount.replace(/,/g, ''));
            const totalTax = parseFloat(props.invoice.tax.replace(/,/g, ''));
            
            // Calculate initial tax amounts based on the total amount and tax
            const initialTaxAmounts = calculateInitialTaxAmounts(totalAmount, totalTax);

            form.value = {
                incoming_number: props.invoice.incoming_number || '',
                client_id: Number(props.invoice.client_id),
                warehouse: props.invoice.warehouse,
                cost_type: props.invoice.cost_type_id,
                billing_type: props.invoice.billing_type_id,
                description: props.invoice.description,
                comment: props.invoice.comment,
                date: new Date(props.invoice.date).toISOString().split('T')[0],
                taxAmounts: initialTaxAmounts
            }
            isOpen.value = true
        }

        const closeDialog = () => {
            isOpen.value = false
        }

        const updateInvoice = async () => {
            loading.value = true
            try {
                const amount = Object.values(form.value.taxAmounts).reduce((sum, amount) => sum + (amount || 0), 0);
                const tax = Object.entries(form.value.taxAmounts).reduce((sum, [key, amount]) => {
                    const rate = taxRates[key] / 100;
                    return sum + ((amount || 0) * rate);
                }, 0);

                const payload = {
                    ...form.value,
                    amount: amount,
                    tax: tax
                };

                const response = await axios.put(`/incomingInvoice/${props.invoice.id}`, payload)
                emit('invoice-updated', response.data)
                closeDialog()
            } catch (error) {
                console.error('Error updating invoice:', error)
            } finally {
                loading.value = false
            }
        }

        return {
            isOpen,
            loading,
            form,
            openDialog,
            closeDialog,
            updateInvoice,
            calculatedAmount,
            calculatedTax,
            calculatedTotal
        }
    }
}
</script>

<style scoped lang="scss">
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

.modal-content {
    width: 1200px;
    background-color: $light-gray;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
}

.modal-header {
    padding-left: 0.5rem;
    font-weight: 600;
    font-size: 1.2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;

}

.modal-body {
    padding: 0.5rem;
    display: flex;
    gap: 1rem;
}

.modal-footer {
    padding: 1rem;
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    border-top: 1px solid #4a5568;
}

.close-btn {
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0.5rem;
}

.form-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    padding: 0;
}

.left-section {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.right-section {
    border-radius: 0.5rem;
}

.section-title {
    font-size: 1rem;
    font-weight: 600;
    color: $white;
    margin-bottom: 0.5rem;
    padding-bottom: 0.25rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.5rem;
}

.form-group {
    display: flex;
    align-items: center;
    color: $white;
    padding: 0.25rem;
    margin: 0;
}

.width100 {
    width: 150px;
    font-weight: 500;
}

.form-input {
    flex: 1;
    padding: 0.5rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 0.25rem;
    background-color: white;
    color: black;
    transition: all 0.2s;
    
    &:focus {
        border-color: $blue;
        box-shadow: 0 0 0 2px rgba($blue, 0.1);
        outline: none;
    }
}

select.form-input {
    width: 225px;
    cursor: pointer;
}

.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    cursor: pointer;
    font-weight: 600;
    border-radius: 0.25rem;
    transition: all 0.2s;
    
    &:hover {
        transform: translateY(-1px);
    }
    
    &:active {
        transform: translateY(0);
    }
}

.edit-btn {
    color: $gray;
    padding: 0.5rem;
    border-radius: 0.25rem;
}

.background {
    background-color: $light-gray;
}

.red {
    background-color: $red;
    color: white;
    border: none;
}

.green {
    background-color: $green;
    color: white;
    border: none;
}

.tax-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    background: none;
    padding: 0;
    margin: 0;
}

.tax-section {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.tax-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    color: white;
    
    label {
        min-width: 120px;
    }
}

.tax-inputs {
    display: flex;
    gap: 0.5rem;
}

.amount-input {
    width: 120px;
    color: black;
    padding: 0.25rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.25rem;
    text-align: right;
}

.rate-input {
    width: 60px;
    padding: 0.25rem;
    color:  $gray;
    border: 1px solid #e2e8f0;
    border-radius: 0.25rem;
    background-color: #f7f7f7;
    text-align: right;
}

.totals-section {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    justify-content: flex-start;
}

.total-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    color: white;
    
    label {
        min-width: 80px;
    }
}

.total-input {
    color: $gray;
    width: 200px;
    padding: 0.25rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.25rem;
    background-color: white;
    text-align: right;
    
    &:disabled {
        background-color: white;
    }
}
</style> 
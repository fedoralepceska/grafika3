<template>
    <div>
        <button @click="openDialog" class="btn edit-btn">
            <i class="fa fa-edit"></i>
        </button>

        <div v-if="isOpen" class="modal-backdrop" @click="closeDialog">
            <div class="modal-content background" @click.stop>
                <div class="modal-header modal-header--minimal">
                    <dl class="modal-meta" aria-label="Invoice context">
                        <div class="modal-meta__item">
                            <dt class="modal-meta__label">Row</dt>
                            <dd class="modal-meta__value">{{ listRowIndex != null ? listRowIndex : '—' }}</dd>
                        </div>
                        <div class="modal-meta__item">
                            <dt class="modal-meta__label">Invoice</dt>
                            <dd class="modal-meta__value">#{{ invoice.incoming_number }}</dd>
                        </div>
                        <div class="modal-meta__item modal-meta__item--client">
                            <dt class="modal-meta__label">Client</dt>
                            <dd class="modal-meta__value">{{ invoice.client_name || '—' }}</dd>
                        </div>
                    </dl>
                    <button type="button" @click="closeDialog" class="close-btn" aria-label="Close">&times;</button>
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
                                        <FinanceMaskedDateInput
                                            :key="'edit-date-' + dateInputKey"
                                            v-model="form.date"
                                            class="edit-date-wrap"
                                            input-class="form-input"
                                            variant="light"
                                            @submit="updateInvoice"
                                        />
                                    </div>
                                    <div class="form-group">
                                        <label class="mr-4 width100">Due date</label>
                                        <FinanceMaskedDateInput
                                            :key="'edit-due-' + dateInputKey"
                                            v-model="form.due_date"
                                            class="edit-date-wrap"
                                            input-class="form-input"
                                            variant="light"
                                            @submit="updateInvoice"
                                        />
                                    </div>
                                    <div class="form-group">
                                        <label class="mr-4 width100">Client</label>
                                        <select v-model="form.client_id" class="form-input">
                                            <option :value="null">Select client</option>
                                            <option v-for="client in clients" :key="client.id" :value="client.id">
                                                {{ client.name }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="mr-4 width100">Warehouse</label>
                                        <select v-model="form.warehouse" class="form-input">
                                            <option value="">Select warehouse</option>
                                            <option v-for="w in warehouseOptions" :key="w" :value="w">
                                                {{ w }}
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

                            <!-- Types Section -->
                            <div>
                                <div class="section-title">Invoice Types</div>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label class="mr-4 width100">Cost Type</label>
                                        <select v-model="form.cost_type" class="form-input">
                                            <option :value="null">Select cost type</option>
                                            <option v-for="type in costTypes" :key="type.id" :value="type.id">
                                                {{ type.name }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="form-group form-group--billing-type">
                                        <label class="mr-4 width100">Billing Type</label>
                                        <div class="billing-type-stack">
                                            <select v-model="form.billing_type" class="form-input">
                                                <option :value="null">Select bill type</option>
                                                <option v-for="type in billTypes" :key="type.id" :value="type.id">
                                                    {{ type.name }}
                                                </option>
                                            </select>
                                            <div
                                                v-if="form.billing_type === 2"
                                                class="faktura-hint"
                                                role="status"
                                            >
                                                <span v-if="invoice.billing_type_id === 2">
                                                    Current фактура number:
                                                    <strong class="faktura-hint__num">{{ invoice.faktura_counter }}</strong>
                                                </span>
                                                <span v-else>
                                                    Next available фактура number:
                                                    <strong class="faktura-hint__num">{{ nextFakturaCounter }}</strong>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="right-section edit-inv-right">
                            <div class="edit-inv-tax-panel">
                                <div class="section-title">Price and Tax Calculation</div>
                                <div class="tax-grid">
                                    <div class="tax-section">
                                        <div class="tax-row">
                                            <label class="tax-row__label" :for="'edit-tax-a-' + invoice.id">Amount TAX A</label>
                                            <input :id="'edit-tax-a-' + invoice.id" type="number" v-model.number="form.taxAmounts.taxA" step="any" class="amount-input">
                                            <input type="text" disabled class="rate-input" value="18.00" aria-label="Rate A %">
                                        </div>
                                        <div class="tax-row">
                                            <label class="tax-row__label" :for="'edit-tax-b-' + invoice.id">Amount TAX B</label>
                                            <input :id="'edit-tax-b-' + invoice.id" type="number" v-model.number="form.taxAmounts.taxB" step="any" class="amount-input">
                                            <input type="text" disabled class="rate-input" value="5.00" aria-label="Rate B %">
                                        </div>
                                        <div class="tax-row">
                                            <label class="tax-row__label" :for="'edit-tax-c-' + invoice.id">Amount TAX C</label>
                                            <input :id="'edit-tax-c-' + invoice.id" type="number" v-model.number="form.taxAmounts.taxC" step="any" class="amount-input">
                                            <input type="text" disabled class="rate-input" value="10.00" aria-label="Rate C %">
                                        </div>
                                        <div class="tax-row">
                                            <label class="tax-row__label" :for="'edit-tax-d-' + invoice.id">Amount TAX D</label>
                                            <input :id="'edit-tax-d-' + invoice.id" type="number" v-model.number="form.taxAmounts.taxD" step="any" class="amount-input">
                                            <input type="text" disabled class="rate-input" value="0.00" aria-label="Rate D %">
                                        </div>
                                    </div>

                                    <div class="totals-section">
                                        <div class="total-row">
                                            <label class="total-row__label">Amount:</label>
                                            <input type="text" :value="calculatedAmount" disabled class="total-input">
                                        </div>
                                        <div class="total-row">
                                            <label class="total-row__label">Tax:</label>
                                            <input type="text" :value="calculatedTax" disabled class="total-input">
                                        </div>
                                        <div class="total-row">
                                            <label class="total-row__label">Total:</label>
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
import { useToast } from 'vue-toastification'
import FinanceMaskedDateInput from '@/Components/Finance/FinanceMaskedDateInput.vue'

export default {
    components: {
        FinanceMaskedDateInput,
    },
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
        },
        /** Table index column (same numbering as the list when the dialog opens). */
        listRowIndex: {
            type: Number,
            default: null,
        },
    },
    setup(props, { emit }) {
        const toast = useToast()
        const isOpen = ref(false)
        const loading = ref(false)
        const nextFakturaCounter = ref(null)
        const dateInputKey = ref(0)
        const form = ref({
            incoming_number: '',
            client_id: null,
            warehouse: '',
            cost_type: null,
            billing_type: null,
            description: '',
            comment: '',
            date: '',
            due_date: '',
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

            // Amount with no tax (exempt / zero-rated) → band D (matches 0% branch below)
            if (totalAmount > 0 && totalTax === 0) {
                taxAmounts.taxD = totalAmount;
                return taxAmounts;
            }

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

        const extractInitialTaxAmounts = (invoice, totalAmount, totalTax) => {
            const hasStoredTaxBands = ['tax_a_amount', 'tax_b_amount', 'tax_c_amount', 'tax_d_amount']
                .some((key) => invoice?.[key] !== null && invoice?.[key] !== undefined);

            if (hasStoredTaxBands) {
                return {
                    taxA: Number(invoice?.tax_a_amount) || 0,
                    taxB: Number(invoice?.tax_b_amount) || 0,
                    taxC: Number(invoice?.tax_c_amount) || 0,
                    taxD: Number(invoice?.tax_d_amount) || 0,
                };
            }

            return calculateInitialTaxAmounts(totalAmount, totalTax);
        };

        const parseDisplayMoney = (val) => {
            const n = parseFloat(String(val ?? '').replace(/,/g, ''));
            return Number.isFinite(n) ? n : 0;
        };

        /**
         * Match `formatDateDdMmYyyy` in the table: exact `yyyy-mm-dd` uses that calendar day;
         * datetime strings (e.g. Laravel JSON `…T23:00:00.000000Z`) use local date from `Date`,
         * not the UTC date prefix — otherwise the modal is one day behind the table.
         */
        const invoiceDateToFormYmd = (raw) => {
            if (raw == null || raw === '') {
                return '';
            }
            const s = String(raw).trim();
            if (/^\d{4}-\d{2}-\d{2}$/.test(s)) {
                return s;
            }
            const d = new Date(raw);
            if (Number.isNaN(d.getTime())) {
                return '';
            }
            const y = d.getFullYear();
            const m = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${y}-${m}-${day}`;
        };

        const toNullableId = (v) => {
            if (v === null || v === undefined || v === '') {
                return null;
            }
            const n = Number(v);
            return Number.isFinite(n) ? n : null;
        };

        /** Include current row warehouse if it is not in the global list (renamed / legacy). */
        const warehouseOptions = computed(() => {
            const list = Array.isArray(props.warehouses) ? [...props.warehouses] : [];
            const cur = props.invoice?.warehouse;
            if (typeof cur === 'string' && cur !== '' && !list.includes(cur)) {
                list.push(cur);
            }
            return list;
        });

        /**
         * Prefill from `IncomingFakturaController@index` JSON: incoming_number, client_id,
         * warehouse, cost_type_id, billing_type_id, description, comment, date, amount, tax.
         */
        const openDialog = () => {
            const inv = props.invoice;
            const totalAmount = parseDisplayMoney(inv.amount);
            const totalTax = parseDisplayMoney(inv.tax);

            const initialTaxAmounts = extractInitialTaxAmounts(inv, totalAmount, totalTax);

            form.value = {
                incoming_number: inv.incoming_number != null ? String(inv.incoming_number) : '',
                client_id: toNullableId(inv.client_id),
                warehouse: inv.warehouse != null && inv.warehouse !== '' ? String(inv.warehouse) : '',
                cost_type: toNullableId(inv.cost_type_id),
                billing_type: toNullableId(inv.billing_type_id),
                description: inv.description != null ? String(inv.description) : '',
                comment: inv.comment != null ? String(inv.comment) : '',
                date: invoiceDateToFormYmd(inv.date),
                due_date: invoiceDateToFormYmd(inv.due_date),
                taxAmounts: initialTaxAmounts,
            };
            dateInputKey.value += 1;
            isOpen.value = true;
        };

        const closeDialog = () => {
            isOpen.value = false
        }

        const updateInvoice = async () => {
            if (!form.value.incoming_number || String(form.value.incoming_number).trim() === '') {
                toast.error('Please enter an invoice number');
                return;
            }
            loading.value = true
            try {
                const amount = Object.values(form.value.taxAmounts).reduce((sum, amount) => sum + (amount || 0), 0);
                const tax = Object.entries(form.value.taxAmounts).reduce((sum, [key, amount]) => {
                    const rate = taxRates[key] / 100;
                    return sum + ((amount || 0) * rate);
                }, 0);

                const { taxAmounts: _taxAmounts, ...fields } = form.value;
                const payload = {
                    ...fields,
                    amount,
                    tax,
                    tax_a_amount: Number(_taxAmounts.taxA) || 0,
                    tax_b_amount: Number(_taxAmounts.taxB) || 0,
                    tax_c_amount: Number(_taxAmounts.taxC) || 0,
                    tax_d_amount: Number(_taxAmounts.taxD) || 0,
                    date: fields.date || null,
                    due_date: fields.due_date || null,
                };

                const response = await axios.put(`/incomingInvoice/${props.invoice.id}`, payload)
                emit('invoice-updated', { id: props.invoice.id, response: response.data })
                closeDialog()
                toast.success('Invoice updated successfully');
            } catch (error) {
                console.error('Error updating invoice:', error)
                toast.error('Error updating invoice: ' + (error.message || 'Unknown error'));
            } finally {
                loading.value = false
            }
        }


        watch(() => form.value.billing_type, async (newValue, oldValue) => {
            if (newValue === 2 && oldValue !== 2) {
                try {
                    const response = await axios.get('/api/next-faktura-counter');
                    nextFakturaCounter.value = response.data.counter;
                } catch (error) {
                    console.error('Error fetching next faktura counter:', error);
                }
            }
        });

        return {
            isOpen,
            loading,
            form,
            nextFakturaCounter,
            dateInputKey,
            warehouseOptions,
            openDialog,
            closeDialog,
            updateInvoice,
            calculatedAmount,
            calculatedTax,
            calculatedTotal,
        }
    }
}
</script>

<style scoped lang="scss">
.modal-backdrop {
    position: fixed;
    inset: 0;
    background-color: rgba(0, 0, 0, 0.58);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 3050;
    padding: 16px;
}

.modal-content {
    width: min(1200px, calc(100vw - 24px));
    max-height: calc(100vh - 28px);
    background-color: #1a2332;
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.45);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.modal-header {
    padding: 14px 16px 12px;
    font-weight: 600;
    font-size: 1.1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    color: $white;
    gap: 12px;
}

.modal-header--minimal {
    align-items: flex-start;
}

.modal-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 16px 28px;
    margin: 0;
    min-width: 0;
    flex: 1;
}

.modal-meta__item {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 0;
}

.modal-meta__item--client {
    flex: 1 1 180px;
    min-width: min(100%, 200px);
}

.modal-meta__label {
    margin: 0;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: rgba(255, 255, 255, 0.52);
    line-height: 1.2;
}

.modal-meta__value {
    margin: 0;
    font-size: 15px;
    font-weight: 700;
    line-height: 1.25;
    color: rgba(255, 255, 255, 0.96);
    font-variant-numeric: tabular-nums;
}

.modal-meta__item--client .modal-meta__value {
    font-weight: 600;
    font-variant-numeric: normal;
    word-break: break-word;
}

.modal-body {
    padding: 14px 16px 10px;
    overflow-y: auto;
}

.modal-footer {
    padding: 12px 18px 16px;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.close-btn {
    background: rgba(255, 255, 255, 0.08);
    border: none;
    color: rgba(255, 255, 255, 0.85);
    width: 32px;
    height: 32px;
    border-radius: 10px;
    font-size: 22px;
    line-height: 1;
    cursor: pointer;
    transition: background 0.15s ease;
}

.close-btn:hover {
    background: rgba(255, 255, 255, 0.14);
}

.form-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    padding: 0;
}

.left-section {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.right-section {
    border-radius: 12px;
    min-width: 0;
}

.edit-inv-right {
    min-width: 0;
}

.edit-inv-tax-panel {
    background: rgba(55, 65, 81, 0.85);
    padding: 12px 14px 14px;
    border-radius: 10px;
    border: 1px solid rgba(255, 255, 255, 0.08);
}

.section-title {
    font-size: 1.05rem;
    font-weight: 700;
    color: $white;
    margin: 0 0 10px;
    padding-bottom: 8px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.12);
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 8px;
    padding: 0 2px;
}

.form-group {
    display: grid;
    grid-template-columns: 150px minmax(0, 1fr);
    align-items: center;
    color: $white;
    padding: 2px 0;
    margin: 0;
    gap: 12px;
}

.width100 {
    width: auto;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: rgba(255, 255, 255, 0.88);
    margin-right: 0 !important;
}

.form-group--billing-type {
    align-items: start;
}

.billing-type-stack {
    display: flex;
    flex-direction: column;
    gap: 10px;
    width: 100%;
    min-width: 0;
}

.faktura-hint {
    width: 100%;
    box-sizing: border-box;
    margin: 0;
    padding: 10px 12px;
    border-radius: 10px;
    background: rgba(59, 130, 246, 0.14);
    border: 1px solid rgba(96, 165, 250, 0.4);
    color: rgba(255, 255, 255, 0.92);
    font-size: 13px;
    font-weight: 600;
    line-height: 1.45;
    text-align: left;
}

.faktura-hint__num {
    margin-left: 6px;
    font-size: 15px;
    font-weight: 700;
    color: #fff;
    font-variant-numeric: tabular-nums;
}

.form-input {
    flex: 1;
    min-height: 36px;
    padding: 0 10px;
    border: 1px solid rgba(0, 0, 0, 0.14);
    border-radius: 10px;
    background-color: rgba(255, 255, 255, 0.98);
    color: #111827;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;

    &:focus {
        border-color: rgba(59, 130, 246, 0.75);
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
        outline: none;
    }
}

select.form-input {
    width: 100%;
    max-width: 100%;
    cursor: pointer;
}

.btn {
    min-height: 36px;
    padding: 0 18px;
    border: none;
    cursor: pointer;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    border-radius: 10px;
    transition: all 0.2s;

    &:hover {
        filter: brightness(1.08);
    }

    &:active {
        transform: scale(0.98);
    }
}

.edit-btn {
    color: $gray;
    min-height: 34px;
    min-width: 34px;
    padding: 0;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.08);
}

.background {
    background-color: #1a2332;
}

.red {
    background: #b71c1c;
    color: #fff;
    border: 1px solid rgba(0, 0, 0, 0.2);
    box-shadow: 0 1px 0 rgba(255, 255, 255, 0.12) inset;
}

.green {
    background: #1b5e20;
    color: #fff;
    border: 1px solid rgba(0, 0, 0, 0.15);
    box-shadow: 0 1px 0 rgba(255, 255, 255, 0.1) inset;
}

/* Stack tax bands + totals (was 2 columns — squeezed inputs). */
.tax-grid {
    display: flex;
    flex-direction: column;
    gap: 18px;
    background: rgba(32, 41, 58, 0.75);
    padding: 14px 12px 12px;
    margin: 0;
    border-radius: 10px;
    border: 1px solid rgba(255, 255, 255, 0.08);
}

.tax-section {
    display: flex;
    flex-direction: column;
    gap: 10px;
    min-width: 0;
}

/* Label | amount | rate — one row, no clipping */
.tax-row {
    display: grid;
    grid-template-columns: minmax(96px, 1fr) minmax(88px, 1.2fr) minmax(72px, 96px);
    gap: 10px 12px;
    align-items: center;
    color: white;
    min-width: 0;
}

.tax-row__label {
    margin: 0;
    font-size: 12px;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.88);
    line-height: 1.25;
    min-width: 0;
}

.amount-input {
    width: 100%;
    min-width: 0;
    box-sizing: border-box;
    min-height: 36px;
    color: #111827;
    padding: 6px 10px;
    border: 1px solid rgba(0, 0, 0, 0.14);
    border-radius: 8px;
    text-align: right;
    font-variant-numeric: tabular-nums;
    background: rgba(255, 255, 255, 0.98);
}

.rate-input {
    width: 100%;
    min-width: 0;
    box-sizing: border-box;
    min-height: 36px;
    padding: 6px 10px;
    color: #4b5563;
    font-size: 13px;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    border: 1px solid rgba(0, 0, 0, 0.12);
    border-radius: 8px;
    background-color: #f3f4f6;
    text-align: right;
}

.totals-section {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding-top: 4px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    min-width: 0;
}

.total-row {
    display: grid;
    grid-template-columns: minmax(88px, 120px) minmax(0, 1fr);
    gap: 12px;
    align-items: center;
    color: white;
    min-width: 0;
}

.total-row__label {
    margin: 0;
    font-size: 12px;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.88);
    text-align: left;
}

.total-input {
    color: #374151;
    width: 100%;
    min-width: 0;
    box-sizing: border-box;
    min-height: 36px;
    padding: 6px 12px;
    border: 1px solid rgba(0, 0, 0, 0.14);
    border-radius: 8px;
    background-color: rgba(255, 255, 255, 0.98);
    text-align: right;
    font-variant-numeric: tabular-nums;
    font-weight: 600;

    &:disabled {
        background-color: rgba(255, 255, 255, 0.98);
        color: #1f2937;
    }
}

.text-h5 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 700;
}

@media (max-width: 1100px) {
    .form-container {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 720px) {
    .form-group {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        gap: 6px;
    }

    .width100 {
        width: 100%;
    }

    .tax-row {
        grid-template-columns: 1fr 1fr;
        grid-template-rows: auto auto;
    }

    .tax-row__label {
        grid-column: 1 / -1;
    }

    .amount-input,
    .rate-input,
    .total-input {
        width: 100%;
    }

    .total-row {
        grid-template-columns: 1fr;
        gap: 6px;
    }
}
</style> 
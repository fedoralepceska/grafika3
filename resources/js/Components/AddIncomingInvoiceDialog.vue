<template>
    <v-dialog
        v-model="dialog"
        persistent
        max-width="800"
        class="inv-dialog-root"
        @keydown.esc="closeDialog"
    >
        <template #activator="{ props: activatorProps }">
            <div v-bind="activatorProps" class="inv-activator">
                <button type="button" class="inv-toolbar-btn">
                    <span>Add Incoming Invoice</span>
                    <i class="fa fa-plus inv-toolbar-btn__icon" aria-hidden="true" />
                </button>
            </div>
        </template>

        <v-card class="inv-card">
            <div class="inv-card__head">
                <h2 id="inv-dialog-title" class="inv-card__title">Add New Incoming Invoice</h2>
                <button type="button" class="inv-card__close" aria-label="Close" @click="closeDialog">
                    &times;
                </button>
            </div>

            <v-card-text class="inv-card__body">
                <form class="inv-form" @submit.prevent="addIncomingInvoice">
                    <section class="inv-section">
                        <div class="inv-field">
                            <label class="inv-label" for="inv-incoming-number">
                                Invoice nr
                                <span class="inv-required" aria-hidden="true">*</span>
                            </label>
                            <div class="inv-control">
                                <input
                                    id="inv-incoming-number"
                                    v-model="newInvoice.incoming_number"
                                    type="text"
                                    class="inv-input"
                                    required
                                    autocomplete="off"
                                />
                                <p v-if="validationError" class="inv-field-error" role="alert">Invoice number is required</p>
                            </div>
                        </div>
                    </section>

                    <section class="inv-section">
                        <div class="inv-field">
                            <label class="inv-label" for="inv-client">Client</label>
                            <div class="inv-control">
                                <select id="inv-client" v-model="newInvoice.client_id" class="inv-input inv-select">
                                    <option :value="null">Select client</option>
                                    <option v-for="client in uniqueClients" :key="client.id" :value="client.id">
                                        {{ client.name }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="inv-field">
                            <label class="inv-label">Date</label>
                            <div class="inv-control">
                                <FinanceMaskedDateInput
                                    v-model="newInvoice.date"
                                    class="inv-date-wrap"
                                    input-class="inv-input inv-input--date inv-date-text"
                                    variant="light"
                                />
                            </div>
                        </div>
                        <div class="inv-field">
                            <label class="inv-label">Due date</label>
                            <div class="inv-control">
                                <FinanceMaskedDateInput
                                    v-model="newInvoice.due_date"
                                    class="inv-date-wrap"
                                    input-class="inv-input inv-input--date inv-date-text"
                                    variant="light"
                                />
                            </div>
                        </div>
                        <div class="inv-field">
                            <label class="inv-label" for="inv-address">Address</label>
                            <div class="inv-control">
                                <input
                                    id="inv-address"
                                    type="text"
                                    class="inv-input inv-input--readonly"
                                    readonly
                                    :value="selectedClientDetails.address"
                                />
                            </div>
                        </div>
                    </section>

                    <section class="inv-section">
                        <div class="inv-field">
                            <label class="inv-label" for="inv-warehouse">Warehouse</label>
                            <div class="inv-control">
                                <select id="inv-warehouse" v-model="newInvoice.warehouse" class="inv-input inv-select">
                                    <option value="">Select warehouse</option>
                                    <option v-for="w in warehouses" :key="w.id" :value="w.name">
                                        {{ w.name }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="inv-field">
                            <label class="inv-label" for="inv-cost-type">Cost type</label>
                            <div class="inv-control">
                                <select id="inv-cost-type" v-model="newInvoice.cost_type" class="inv-input inv-select">
                                    <option :value="null">Select cost type</option>
                                    <option v-for="type in costTypes" :key="type.id" :value="type.id">
                                        {{ type.name }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="inv-field inv-field--bill-type">
                            <label class="inv-label" for="inv-bill-type">Bill type</label>
                            <div class="inv-control inv-control--stack">
                                <select id="inv-bill-type" v-model="newInvoice.billing_type" class="inv-input inv-select">
                                    <option :value="null">Select bill type</option>
                                    <option v-for="type in billTypes" :key="type.id" :value="type.id">
                                        {{ type.name }}
                                    </option>
                                </select>
                                <div v-if="newInvoice.billing_type === 2" class="inv-faktura-hint">
                                    Фактура бр: <strong>{{ nextFakturaCounter }}</strong>
                                </div>
                            </div>
                        </div>
                        <div class="inv-field">
                            <label class="inv-label" for="inv-description">Description</label>
                            <div class="inv-control">
                                <input id="inv-description" v-model="newInvoice.description" type="text" class="inv-input" />
                            </div>
                        </div>
                        <div class="inv-field">
                            <label class="inv-label" for="inv-comment">Comment</label>
                            <div class="inv-control">
                                <input id="inv-comment" v-model="newInvoice.comment" type="text" class="inv-input" />
                            </div>
                        </div>
                    </section>

                    <section
                        class="inv-section inv-section--tax"
                        @focusin="onTaxSectionFocusIn"
                        @focusout="onTaxSectionFocusOut"
                    >
                        <div class="inv-field">
                            <label class="inv-label" for="inv-tax-a">Amount tax A</label>
                            <div class="inv-control inv-control--inline">
                                <input
                                    id="inv-tax-a"
                                    v-model.number="taxAmounts.taxA"
                                    type="number"
                                    step="any"
                                    class="inv-input inv-input--tax"
                                />
                                <input type="text" class="inv-input inv-input--rate" readonly tabindex="-1" value="18.00" />
                            </div>
                        </div>
                        <div class="inv-field">
                            <label class="inv-label" for="inv-tax-b">Amount tax B</label>
                            <div class="inv-control inv-control--inline inv-control--wrap">
                                <span class="inv-inline-group">
                                    <input
                                        id="inv-tax-b"
                                        v-model.number="taxAmounts.taxB"
                                        type="number"
                                        step="any"
                                        class="inv-input inv-input--tax"
                                    />
                                    <input type="text" class="inv-input inv-input--rate" readonly tabindex="-1" value="5.00" />
                                </span>
                                <span class="inv-inline-group inv-inline-group--summary">
                                    <span class="inv-mini-label">Amount</span>
                                    <input
                                        id="inv-calc-amount"
                                        type="text"
                                        class="inv-input inv-input--readonly inv-input--summary"
                                        readonly
                                        tabindex="0"
                                        :value="calculatedAmount"
                                    />
                                </span>
                            </div>
                        </div>
                        <div class="inv-field">
                            <label class="inv-label" for="inv-tax-c">Amount tax C</label>
                            <div class="inv-control inv-control--inline inv-control--wrap">
                                <span class="inv-inline-group">
                                    <input
                                        id="inv-tax-c"
                                        v-model.number="taxAmounts.taxC"
                                        type="number"
                                        step="any"
                                        class="inv-input inv-input--tax"
                                    />
                                    <input type="text" class="inv-input inv-input--rate" readonly tabindex="-1" value="10.00" />
                                </span>
                                <span class="inv-inline-group inv-inline-group--summary">
                                    <span class="inv-mini-label">Tax</span>
                                    <input
                                        id="inv-calc-tax"
                                        type="text"
                                        class="inv-input inv-input--readonly inv-input--summary"
                                        readonly
                                        tabindex="0"
                                        :value="calculatedTax"
                                    />
                                </span>
                            </div>
                        </div>
                        <div class="inv-field">
                            <label class="inv-label" for="inv-tax-d">Amount tax D</label>
                            <div class="inv-control inv-control--inline inv-control--wrap">
                                <span class="inv-inline-group">
                                    <input
                                        id="inv-tax-d"
                                        v-model.number="taxAmounts.taxD"
                                        type="number"
                                        step="any"
                                        class="inv-input inv-input--tax"
                                    />
                                    <input type="text" class="inv-input inv-input--rate" readonly tabindex="-1" value="0.00" />
                                </span>
                                <span class="inv-inline-group inv-inline-group--summary">
                                    <span class="inv-mini-label">Total</span>
                                    <input
                                        id="inv-calc-total"
                                        type="text"
                                        class="inv-input inv-input--readonly inv-input--summary inv-input--summary-total"
                                        readonly
                                        tabindex="0"
                                        :value="calculatedTotal"
                                    />
                                </span>
                            </div>
                        </div>

                        <div
                            class="inv-amount-accent"
                            :class="{
                                'inv-amount-accent--bases': taxAccent === 'bases',
                                'inv-amount-accent--totals': taxAccent === 'totals',
                            }"
                            aria-hidden="true"
                        />
                    </section>
                </form>
            </v-card-text>

            <div class="inv-card__actions">
                <button type="button" class="inv-footer-btn inv-footer-btn--close" @click="closeDialog">Close</button>
                <button type="button" class="inv-footer-btn inv-footer-btn--save" @click="addIncomingInvoice">
                    Save incoming invoice
                </button>
            </div>
        </v-card>
    </v-dialog>
</template>

<script>
import { useToast } from 'vue-toastification';
import axios from 'axios';
import FinanceMaskedDateInput from '@/Components/Finance/FinanceMaskedDateInput.vue';

const TAX_BASE_IDS = ['inv-tax-a', 'inv-tax-b', 'inv-tax-c', 'inv-tax-d'];
const SUMMARY_IDS = ['inv-calc-amount', 'inv-calc-tax', 'inv-calc-total'];

export default {
    components: {
        FinanceMaskedDateInput,
    },
    emits: ['invoice-added'],
    props: {
        incomingInvoice: {
            type: Object,
            default: null,
        },
        costTypes: {
            type: Array,
            required: true,
            default: () => [],
        },
        billTypes: {
            type: Array,
            required: true,
            default: () => [],
        },
    },
    data() {
        return {
            dialog: false,
            validationError: false,
            uniqueClients: [],
            warehouses: [],
            nextFakturaCounter: null,
            taxAmounts: {
                taxA: 0,
                taxB: 0,
                taxC: 0,
                taxD: 0,
            },
            taxRates: {
                taxA: 18,
                taxB: 5,
                taxC: 10,
                taxD: 0,
            },
            newInvoice: {
                incoming_number: '',
                client_id: null,
                warehouse: '',
                cost_type: null,
                billing_type: null,
                description: '',
                comment: '',
                amount: 0,
                tax: 0,
                total: 0,
                date: null,
                due_date: null,
            },
            selectedClientDetails: {
                address: '',
            },
            /** 'bases' = tax band inputs, 'totals' = readonly summary fields (mirrors add-item green/red) */
            taxAccent: null,
        };
    },
    computed: {
        calculatedAmount() {
            return this.formatNumber(this.newInvoice.amount);
        },
        calculatedTax() {
            return this.formatNumber(this.newInvoice.tax);
        },
        calculatedTotal() {
            return this.formatNumber(this.newInvoice.total);
        },
    },
    watch: {
        dialog(open) {
            if (open) {
                this.fetchUniqueClients();
                this.fetchWarehouses();
            } else {
                this.resetForm();
            }
        },
        'newInvoice.billing_type': {
            async handler(newValue) {
                if (newValue === 2) {
                    try {
                        const response = await axios.get('/api/next-faktura-counter');
                        this.nextFakturaCounter = response.data.counter;
                    } catch (error) {
                        console.error('Error fetching next faktura counter:', error);
                    }
                } else {
                    this.nextFakturaCounter = null;
                }
            },
        },
        'newInvoice.client_id': {
            async handler(newClientId) {
                if (newClientId) {
                    await this.fetchClientDetails(newClientId);
                } else {
                    this.selectedClientDetails = { address: '' };
                }
            },
        },
        taxAmounts: {
            deep: true,
            handler() {
                const amount = Object.values(this.taxAmounts).reduce((sum, val) => sum + (Number(val) || 0), 0);
                const tax = Object.entries(this.taxAmounts).reduce((sum, [key, val]) => {
                    const rate = this.taxRates[key] / 100;
                    return sum + (Number(val) || 0) * rate;
                }, 0);

                this.newInvoice.amount = amount;
                this.newInvoice.tax = tax;
                this.newInvoice.total = amount + tax;
            },
        },
    },
    methods: {
        formatNumber(value) {
            const num = Number(value);
            return num.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        },
        closeDialog() {
            this.dialog = false;
        },
        onTaxSectionFocusIn(e) {
            const id = e.target?.id;
            if (TAX_BASE_IDS.includes(id)) {
                this.taxAccent = 'bases';
            } else if (SUMMARY_IDS.includes(id)) {
                this.taxAccent = 'totals';
            }
        },
        onTaxSectionFocusOut() {
            this.$nextTick(() => {
                const el = document.activeElement;
                const id = el?.id;
                if (TAX_BASE_IDS.includes(id)) {
                    this.taxAccent = 'bases';
                } else if (SUMMARY_IDS.includes(id)) {
                    this.taxAccent = 'totals';
                } else {
                    this.taxAccent = null;
                }
            });
        },
        async addIncomingInvoice() {
            const toast = useToast();

            if (!this.newInvoice.incoming_number || this.newInvoice.incoming_number.trim() === '') {
                this.validationError = true;
                toast.error('Please enter an invoice number');
                return;
            }

            this.validationError = false;
            try {
                const payload = {
                    incoming_number: this.newInvoice.incoming_number.trim(),
                    client_id: this.newInvoice.client_id,
                    warehouse: this.newInvoice.warehouse,
                    cost_type: this.newInvoice.cost_type,
                    billing_type: this.newInvoice.billing_type,
                    description: this.newInvoice.description,
                    comment: this.newInvoice.comment,
                    amount: this.newInvoice.amount,
                    tax: this.newInvoice.tax,
                    tax_a_amount: Number(this.taxAmounts.taxA) || 0,
                    tax_b_amount: Number(this.taxAmounts.taxB) || 0,
                    tax_c_amount: Number(this.taxAmounts.taxC) || 0,
                    tax_d_amount: Number(this.taxAmounts.taxD) || 0,
                    date: this.newInvoice.date,
                    due_date: this.newInvoice.due_date,
                };

                const response = await axios.post('/incomingInvoice', payload);
                toast.success('Incoming invoice added successfully!');
                this.$emit('invoice-added', response.data);
                this.closeDialog();
            } catch (error) {
                toast.error('Error adding incoming invoice: ' + (error.message || 'Unknown error'));
            }
        },
        resetForm() {
            this.validationError = false;
            this.taxAccent = null;
            this.newInvoice = {
                incoming_number: '',
                client_id: null,
                warehouse: '',
                cost_type: null,
                billing_type: null,
                description: '',
                comment: '',
                amount: 0,
                tax: 0,
                total: 0,
                date: null,
                due_date: null,
            };
            this.taxAmounts = {
                taxA: 0,
                taxB: 0,
                taxC: 0,
                taxD: 0,
            };
            this.selectedClientDetails = { address: '' };
            this.nextFakturaCounter = null;
        },
        async fetchUniqueClients() {
            try {
                const response = await axios.get('/unique-clients');
                this.uniqueClients = response.data;
            } catch (error) {
                console.error(error);
            }
        },
        async fetchClientDetails(clientId) {
            try {
                const response = await axios.get(`/client-details/${clientId}`);
                this.selectedClientDetails = {
                    address: response.data.address,
                };
            } catch (error) {
                console.error('Error fetching client details:', error);
                const toast = useToast();
                toast.error('Error fetching client details');
            }
        },
        async fetchWarehouses() {
            try {
                const response = await axios.get('/api/warehouses');
                this.warehouses = response.data;
            } catch (error) {
                console.error('Error fetching warehouses:', error);
                const toast = useToast();
                toast.error('Error fetching warehouses');
            }
        },
    },
};
</script>

<style scoped lang="scss">
.inv-activator {
    display: inline-flex;
    align-items: center;
}

.inv-toolbar-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 34px;
    padding: 0 14px;
    border: none;
    border-radius: 10px;
    background-color: $blue;
    color: $white;
    font-size: 13px;
    font-weight: 700;
    line-height: 1.2;
    cursor: pointer;
    white-space: nowrap;
    transition: filter 0.15s ease, transform 0.08s ease;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.12);
}

.inv-toolbar-btn:hover {
    filter: brightness(1.07);
}

.inv-toolbar-btn:active {
    transform: scale(0.98);
}

.inv-toolbar-btn__icon {
    font-size: 11px;
    opacity: 0.92;
}

.inv-card {
    border-radius: 12px !important;
    overflow: hidden;
    background: #1a2332 !important;
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.45) !important;
}

.inv-card__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    padding: 16px 18px 12px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.inv-card__title {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 700;
    color: $white;
    line-height: 1.3;
}

.inv-card__close {
    flex-shrink: 0;
    width: 32px;
    height: 32px;
    margin: -4px -6px 0 0;
    border: none;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.08);
    color: rgba(255, 255, 255, 0.85);
    font-size: 22px;
    line-height: 1;
    cursor: pointer;
    transition: background 0.15s ease;
}

.inv-card__close:hover {
    background: rgba(255, 255, 255, 0.14);
}

.inv-card__body {
    --inv-label-w: 148px;
    padding: 14px 16px 10px !important;
    color: rgba(255, 255, 255, 0.9);
    max-height: min(78vh, 720px);
    overflow-y: auto;
}

.inv-form {
    display: flex;
    flex-direction: column;
    gap: 0;
}

.inv-section {
    margin-bottom: 12px;
    padding: 12px 14px 14px;
    border: 1px solid rgba(255, 255, 255, 0.22);
    border-radius: 12px;
    background: rgba(0, 0, 0, 0.12);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
}

.inv-section:last-of-type {
    margin-bottom: 0;
}

.inv-section--tax {
    padding-bottom: 12px;
}

.inv-field {
    display: flex;
    flex-direction: row;
    align-items: flex-start;
    gap: 12px 16px;
    margin-bottom: 10px;
    min-width: 0;
}

.inv-field:last-child {
    margin-bottom: 0;
}

.inv-field--bill-type .inv-control--stack {
    align-items: flex-start;
}

.inv-label {
    flex: 0 0 var(--inv-label-w);
    max-width: 42%;
    margin: 0;
    padding-top: 9px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: rgba(255, 255, 255, 0.88);
    line-height: 1.25;
}

.inv-required {
    color: #fca5a5;
    margin-left: 2px;
}

.inv-control {
    flex: 1 1 auto;
    min-width: 0;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
}

.inv-control--inline {
    gap: 10px;
}

.inv-control--wrap {
    align-items: flex-end;
}

.inv-control--stack {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
}

.inv-inline-group {
    display: inline-flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
}

.inv-inline-group--summary {
    margin-left: 4px;
    padding-left: 12px;
    border-left: 1px solid rgba(255, 255, 255, 0.14);
    gap: 6px;
}

.inv-mini-label {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: rgba(255, 255, 255, 0.55);
    min-width: 44px;
}

.inv-input {
    width: 100%;
    max-width: 300px;
    min-height: 36px;
    padding: 0 10px;
    border: 1px solid rgba(0, 0, 0, 0.14);
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.98);
    color: #111827;
    font-size: 14px;
    box-sizing: border-box;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.inv-input:focus {
    outline: none;
    border-color: rgba(59, 130, 246, 0.75);
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}

.inv-input--readonly {
    cursor: default;
    background: rgba(245, 245, 245, 0.98);
    color: #374151;
}

.inv-input--readonly:focus {
    border-color: rgba(239, 68, 68, 0.45);
    box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.15);
}

.inv-date-wrap {
    width: 100%;
    max-width: 200px;
    min-width: 0;
}

.inv-date-text {
    width: 100%;
}

.inv-input--date {
    max-width: none;
}

.inv-input--tax {
    width: 120px;
    max-width: 120px;
}

.inv-input--rate {
    width: 72px;
    max-width: 72px;
    min-height: 36px;
    text-align: center;
    font-size: 13px;
    color: #6b7280;
    cursor: default;
}

.inv-input--summary {
    width: 140px;
    max-width: 140px;
    font-weight: 600;
}

.inv-input--summary-total {
    font-weight: 700;
}

.inv-select {
    cursor: pointer;
}

.inv-faktura-hint {
    margin-top: 4px;
    padding: 8px 12px;
    border-radius: 10px;
    font-size: 13px;
    color: rgba(255, 255, 255, 0.92);
    background: rgba(0, 0, 0, 0.35);
    border: 1px solid rgba(255, 255, 255, 0.12);
    max-width: 320px;
}

.inv-field-error {
    margin: 6px 0 0;
    font-size: 12px;
    font-weight: 600;
    color: #fca5a5;
    width: 100%;
}

.inv-amount-accent {
    width: 100%;
    height: 16px;
    margin-top: 14px;
    border-radius: 8px;
    background: rgba(25, 30, 40, 0.95);
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-sizing: border-box;
    transition: background 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}

.inv-amount-accent--bases {
    border-color: rgba(34, 197, 94, 0.35);
    background: linear-gradient(90deg, rgba(22, 101, 52, 0.95), rgba(34, 197, 94, 0.88));
    box-shadow: inset 0 -2px 0 rgba(0, 0, 0, 0.2);
}

.inv-amount-accent--totals {
    border-color: rgba(248, 113, 113, 0.35);
    background: linear-gradient(90deg, rgba(127, 29, 29, 0.95), rgba(220, 38, 38, 0.9));
    box-shadow: inset 0 -2px 0 rgba(0, 0, 0, 0.2);
}

.inv-card__actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 10px;
    padding: 12px 18px 16px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.inv-footer-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 36px;
    padding: 0 18px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    cursor: pointer;
    border: 1px solid transparent;
    transition: filter 0.15s ease, transform 0.08s ease;
}

.inv-footer-btn:active {
    transform: scale(0.98);
}

.inv-footer-btn--close {
    background: #b71c1c;
    color: #fff;
    border-color: rgba(0, 0, 0, 0.2);
    box-shadow: 0 1px 0 rgba(255, 255, 255, 0.12) inset;
}

.inv-footer-btn--close:hover {
    filter: brightness(1.08);
}

.inv-footer-btn--save {
    background: #1b5e20;
    color: #fff;
    border-color: rgba(0, 0, 0, 0.15);
    box-shadow: 0 1px 0 rgba(255, 255, 255, 0.1) inset;
}

.inv-footer-btn--save:hover {
    filter: brightness(1.08);
}

@media (max-width: 640px) {
    .inv-card__body {
        --inv-label-w: 0px;
    }

    .inv-field {
        flex-direction: column;
        align-items: stretch;
        gap: 6px;
    }

    .inv-label {
        flex: none;
        max-width: none;
        width: 100%;
        padding-top: 0;
    }

    .inv-control,
    .inv-control--inline,
    .inv-control--wrap {
        width: 100%;
    }

    .inv-inline-group--summary {
        margin-left: 0;
        padding-left: 0;
        border-left: none;
        width: 100%;
    }

    .inv-input,
    .inv-input--tax,
    .inv-input--rate,
    .inv-input--summary {
        width: 100%;
        max-width: none;
    }

    .inv-date-wrap {
        max-width: none;
    }
}
</style>

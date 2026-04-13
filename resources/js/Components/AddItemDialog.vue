<template>
    <v-dialog
        v-model="dialog"
        persistent
        max-width="720"
        class="add-item-dialog-root"
        @keydown.esc="closeDialog"
    >
        <template #activator="{ props: activatorProps }">
            <div v-bind="activatorProps" class="add-item-activator">
                <button type="button" class="add-item-btn">
                    <span>Add item</span>
                    <i class="fa fa-plus add-item-btn__icon" aria-hidden="true" />
                </button>
            </div>
        </template>

        <v-card class="add-item-card">
            <div class="add-item-card__head">
                <h2 class="add-item-card__title">Add New Item</h2>
                <button type="button" class="add-item-card__close" aria-label="Close" @click="closeDialog">
                    &times;
                </button>
            </div>

            <v-card-text class="add-item-card__body">
                <form class="add-item-form" @submit.prevent="saveItem">
                    <!-- Statement / bank (read-only) -->
                    <section class="add-item-section">
                        <div class="add-item-field">
                            <label class="add-item-label" for="add-item-stmt">Statement #</label>
                            <div class="add-item-control">
                                <input
                                    id="add-item-stmt"
                                    type="text"
                                    class="add-item-input add-item-input--disabled add-item-input--stmt"
                                    disabled
                                    :value="String(certificate?.id_per_bank ?? '')"
                                />
                            </div>
                        </div>
                        <div class="add-item-field">
                            <label class="add-item-label" for="add-item-bank">Bank</label>
                            <div class="add-item-control add-item-control--inline">
                                <input
                                    id="add-item-bank"
                                    type="text"
                                    class="add-item-input add-item-input--disabled add-item-input--bank-name"
                                    disabled
                                    :value="certificate?.bank || ''"
                                />
                                <input
                                    id="add-item-bank-acc"
                                    type="text"
                                    class="add-item-input add-item-input--disabled add-item-input--bank-acc"
                                    disabled
                                    :value="certificate?.bankAccount || ''"
                                />
                            </div>
                        </div>
                    </section>

                    <!-- Client -->
                    <section class="add-item-section add-item-section--client-block">
                        <div class="add-item-field add-item-field--client">
                            <label class="add-item-label" for="add-item-client-trigger">Client</label>
                            <div class="add-item-control add-item-control--client">
                                <FinanceClientSearchSelect
                                    id="add-item-client-trigger"
                                    v-model="newItem.client_id"
                                    variant="pick"
                                    empty-label="Select client"
                                    label=""
                                    :clients="clients"
                                />
                            </div>
                        </div>
                        <div class="add-item-field">
                            <label class="add-item-label" for="add-item-address">Address</label>
                            <div class="add-item-control">
                                <input
                                    id="add-item-address"
                                    type="text"
                                    class="add-item-input add-item-input--disabled"
                                    disabled
                                    :value="getClient?.address || ''"
                                />
                            </div>
                        </div>
                        <div class="add-item-field">
                            <label class="add-item-label" for="add-item-phone">Phone / fax</label>
                            <div class="add-item-control">
                                <input
                                    id="add-item-phone"
                                    type="text"
                                    class="add-item-input add-item-input--disabled"
                                    disabled
                                    :value="getClient?.phone || ''"
                                />
                            </div>
                        </div>
                        <div class="add-item-field">
                            <label class="add-item-label" for="add-item-account">Account</label>
                            <div class="add-item-control">
                                <input
                                    id="add-item-account"
                                    type="text"
                                    class="add-item-input add-item-input--disabled"
                                    disabled
                                    :value="getClient?.client_card_statement?.account || ''"
                                />
                            </div>
                        </div>
                    </section>

                    <!-- Income / expense -->
                    <section class="add-item-section add-item-section--amounts">
                        <div class="add-item-amounts">
                            <div class="add-item-amount-col">
                                <label class="add-item-label add-item-label--stacked" for="add-item-income">Income</label>
                                <input
                                    id="add-item-income"
                                    type="text"
                                    class="add-item-input"
                                    :value="formattedIncome"
                                    inputmode="decimal"
                                    placeholder="0"
                                    @focus="onAmountFieldFocus('income')"
                                    @blur="onAmountFieldBlur"
                                    @input="handleInputIncome"
                                />
                            </div>
                            <div class="add-item-amount-col">
                                <label class="add-item-label add-item-label--stacked" for="add-item-expense">Expense</label>
                                <input
                                    id="add-item-expense"
                                    type="text"
                                    class="add-item-input"
                                    :value="formattedExpense"
                                    inputmode="decimal"
                                    placeholder="0"
                                    @focus="onAmountFieldFocus('expense')"
                                    @blur="onAmountFieldBlur"
                                    @input="handleInputExpense"
                                />
                            </div>
                        </div>
                        <div
                            class="add-item-amount-accent"
                            :class="{
                                'add-item-amount-accent--income': amountAccent === 'income',
                                'add-item-amount-accent--expense': amountAccent === 'expense',
                            }"
                            aria-hidden="true"
                        />
                    </section>

                    <!-- Code, reference, comment -->
                    <section class="add-item-section add-item-section--last">
                        <div class="add-item-field">
                            <label class="add-item-label" for="add-item-code">Code</label>
                            <div class="add-item-control">
                                <input id="add-item-code" v-model="newItem.code" type="text" class="add-item-input" />
                            </div>
                        </div>
                        <div class="add-item-field">
                            <label class="add-item-label" for="add-item-ref">Reference to</label>
                            <div class="add-item-control">
                                <input
                                    id="add-item-ref"
                                    v-model="newItem.reference_to"
                                    type="text"
                                    class="add-item-input"
                                />
                            </div>
                        </div>
                        <div class="add-item-field">
                            <label class="add-item-label" for="add-item-comment">Comment</label>
                            <div class="add-item-control">
                                <input id="add-item-comment" v-model="newItem.comment" type="text" class="add-item-input" />
                            </div>
                        </div>
                    </section>
                </form>
            </v-card-text>

            <div class="add-item-card__actions">
                <button type="button" class="add-item-footer-btn add-item-footer-btn--close" @click="closeDialog">
                    Close
                </button>
                <button type="button" class="add-item-footer-btn add-item-footer-btn--save" @click="saveItem">
                    Save item
                </button>
            </div>
        </v-card>
    </v-dialog>
</template>

<script>
import { useToast } from 'vue-toastification';
import axios from 'axios';
import FinanceClientSearchSelect from '@/Components/Finance/FinanceClientSearchSelect.vue';

export default {
    components: { FinanceClientSearchSelect },
    props: {
        certificate: {
            type: Object,
            required: true,
        },
    },
    data() {
        return {
            dialog: false,
            clients: [],
            newItem: {
                client_id: null,
                certificate_id: this.certificate.id,
                income: 0,
                expense: 0,
                code: '',
                reference_to: '-',
                comment: '',
            },
            rawIncome: '',
            rawExpense: '',
            timeout: null,
            /** 'income' | 'expense' | null — drives bar under amounts (which amount field has focus) */
            amountAccent: null,
        };
    },
    computed: {
        getClient() {
            return this.clients.find((c) => c.id === this.newItem?.client_id);
        },
        formattedExpense() {
            if (this.rawExpense === '') {
                return '';
            }
            const value = parseFloat(String(this.rawExpense).replace(/,/g, '')) || 0;
            return value.toLocaleString('en-US');
        },
        formattedIncome() {
            if (this.rawIncome === '') {
                return '';
            }
            const value = parseFloat(String(this.rawIncome).replace(/,/g, '')) || 0;
            return value.toLocaleString('en-US');
        },
    },
    watch: {
        dialog(open) {
            if (!open) {
                this.resetForm();
            }
        },
    },
    mounted() {
        this.fetchClients();
    },
    methods: {
        resetForm() {
            this.newItem = {
                client_id: null,
                certificate_id: this.certificate.id,
                income: 0,
                expense: 0,
                code: '',
                reference_to: '-',
                comment: '',
            };
            this.rawIncome = '';
            this.rawExpense = '';
            if (this.timeout) {
                clearTimeout(this.timeout);
                this.timeout = null;
            }
            this.amountAccent = null;
        },
        onAmountFieldFocus(which) {
            this.amountAccent = which;
        },
        onAmountFieldBlur() {
            this.$nextTick(() => {
                const el = document.activeElement;
                if (el?.id === 'add-item-income') {
                    this.amountAccent = 'income';
                } else if (el?.id === 'add-item-expense') {
                    this.amountAccent = 'expense';
                } else {
                    this.amountAccent = null;
                }
            });
        },
        closeDialog() {
            this.dialog = false;
        },
        async saveItem() {
            const toast = useToast();
            if (!this.newItem.client_id) {
                toast.error('Please select a client.');
                return;
            }
            const income = parseFloat(String(this.rawIncome).replace(/,/g, '')) || 0;
            const expense = parseFloat(String(this.rawExpense).replace(/,/g, '')) || 0;
            const payload = {
                ...this.newItem,
                income,
                expense,
            };
            try {
                await axios.post('/item', payload);
                toast.success('Item added successfully.');
                this.closeDialog();
                this.$inertia.visit(`/statements/${this.certificate.id}`);
            } catch (error) {
                toast.error('Error adding item: ' + (error.response?.data?.message || error.message));
            }
        },
        fetchClients() {
            axios
                .get('/api/clients')
                .then((response) => {
                    this.clients = response.data;
                })
                .catch((error) => {
                    console.error('Error fetching clients:', error);
                });
        },
        handleInputExpense(event) {
            const input = event.target.value.replace(/,/g, '');
            if (input !== '' && Number.isNaN(Number(input))) {
                return;
            }
            this.rawExpense = input;
            this.newItem.expense = input === '' ? 0 : parseFloat(input) || 0;
            if (this.timeout) {
                clearTimeout(this.timeout);
            }
            this.timeout = setTimeout(() => {
                const value = parseFloat(String(this.rawExpense).replace(/,/g, '')) || 0;
                this.rawExpense = value.toFixed(2);
            }, 1000);
        },
        handleInputIncome(event) {
            const input = event.target.value.replace(/,/g, '');
            if (input !== '' && Number.isNaN(Number(input))) {
                return;
            }
            this.rawIncome = input;
            this.newItem.income = input === '' ? 0 : parseFloat(input) || 0;
            if (this.timeout) {
                clearTimeout(this.timeout);
            }
            this.timeout = setTimeout(() => {
                const value = parseFloat(String(this.rawIncome).replace(/,/g, '')) || 0;
                this.rawIncome = value.toFixed(2);
            }, 1000);
        },
    },
};
</script>

<style scoped lang="scss">
.add-item-dialog-root :deep(.v-overlay__content) {
    overflow: visible;
}

.add-item-activator {
    display: inline-flex;
    align-items: center;
    align-self: center;
}

.add-item-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 34px;
    padding: 0 12px;
    border: none;
    border-radius: 8px;
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

.add-item-btn:hover {
    filter: brightness(1.07);
}

.add-item-btn:active {
    transform: scale(0.98);
}

.add-item-btn__icon {
    font-size: 11px;
    opacity: 0.92;
}

.add-item-card {
    border-radius: 12px !important;
    overflow: visible;
    background: #1a2332 !important;
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.45) !important;
}

.add-item-card__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    padding: 16px 18px 12px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.add-item-card__title {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 700;
    color: $white;
    line-height: 1.3;
}

.add-item-card__close {
    flex-shrink: 0;
    width: 32px;
    height: 32px;
    margin: -4px -6px 0 0;
    border: none;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.08);
    color: rgba(255, 255, 255, 0.85);
    font-size: 22px;
    line-height: 1;
    cursor: pointer;
    transition: background 0.15s ease;
}

.add-item-card__close:hover {
    background: rgba(255, 255, 255, 0.14);
}

.add-item-card__body {
    --add-item-label-w: 148px;
    padding: 14px 16px 10px !important;
    color: rgba(255, 255, 255, 0.9);
    max-height: none;
    overflow: visible;
}

.add-item-form {
    display: flex;
    flex-direction: column;
    gap: 0;
}

.add-item-section {
    margin-bottom: 12px;
    padding: 12px 14px 14px;
    border: 1px solid rgba(255, 255, 255, 0.22);
    border-radius: 12px;
    background: rgba(0, 0, 0, 0.12);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
}

.add-item-section--last {
    margin-bottom: 0;
}

.add-item-section--amounts {
    padding-bottom: 12px;
}

.add-item-field {
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 12px 16px;
    margin-bottom: 10px;
    min-width: 0;
}

.add-item-field:last-child {
    margin-bottom: 0;
}

.add-item-label {
    flex: 0 0 var(--add-item-label-w);
    max-width: 42%;
    margin: 0;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: rgba(255, 255, 255, 0.88);
    line-height: 1.25;
    text-align: left;
}

.add-item-control {
    flex: 1 1 auto;
    min-width: 0;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
}

.add-item-control--inline {
    gap: 10px;
}

.add-item-input {
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

.add-item-input--stmt {
    width: 88px;
    max-width: 88px;
    flex: 0 0 auto;
}

.add-item-input--bank-name {
    max-width: min(240px, 100%);
    flex: 1 1 160px;
}

.add-item-input--bank-acc {
    max-width: min(220px, 100%);
    flex: 1 1 160px;
}

.add-item-input:focus {
    outline: none;
    border-color: rgba(59, 130, 246, 0.75);
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}

.add-item-input--disabled {
    opacity: 0.92;
    cursor: not-allowed;
    background: rgba(245, 245, 245, 0.98);
    color: #374151;
}

.add-item-section--client-block {
    position: relative;
    z-index: 6;
}

.add-item-field--client :deep(.fc-cs) {
    width: 100%;
    max-width: 300px;
}

.add-item-field--client :deep(.fc-cs__dropdown) {
    z-index: 30;
}

.add-item-label--stacked {
    flex: none;
    max-width: none;
    width: 100%;
    text-align: center;
    margin-bottom: 2px;
}

.add-item-section--amounts .add-item-amount-col {
    margin-bottom: 0;
}

.add-item-amounts {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-around;
    gap: 20px 32px;
    padding: 4px 8px 0;
}

.add-item-amount-col {
    flex: 1 1 140px;
    max-width: 220px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    min-width: 0;
}

.add-item-amount-col .add-item-input {
    max-width: 200px;
    width: 100%;
    text-align: center;
}

.add-item-amount-accent {
    width: 100%;
    height: 16px;
    margin-top: 14px;
    border-radius: 8px;
    background: rgba(25, 30, 40, 0.95);
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-sizing: border-box;
    transition: background 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}

.add-item-amount-accent--income {
    border-color: rgba(34, 197, 94, 0.35);
    background: linear-gradient(90deg, rgba(22, 101, 52, 0.95), rgba(34, 197, 94, 0.88));
    box-shadow: inset 0 -2px 0 rgba(0, 0, 0, 0.2);
}

.add-item-amount-accent--expense {
    border-color: rgba(248, 113, 113, 0.35);
    background: linear-gradient(90deg, rgba(127, 29, 29, 0.95), rgba(220, 38, 38, 0.9));
    box-shadow: inset 0 -2px 0 rgba(0, 0, 0, 0.2);
}

.add-item-card__actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 10px;
    padding: 12px 18px 16px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.add-item-footer-btn {
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

.add-item-footer-btn:active {
    transform: scale(0.98);
}

.add-item-footer-btn--close {
    background: #b71c1c;
    color: #fff;
    border-color: rgba(0, 0, 0, 0.2);
    box-shadow: 0 1px 0 rgba(255, 255, 255, 0.12) inset;
}

.add-item-footer-btn--close:hover {
    filter: brightness(1.08);
}

.add-item-footer-btn--save {
    background: #1b5e20;
    color: #fff;
    border-color: rgba(0, 0, 0, 0.15);
    box-shadow: 0 1px 0 rgba(255, 255, 255, 0.1) inset;
}

.add-item-footer-btn--save:hover {
    filter: brightness(1.08);
}

@media (max-width: 560px) {
    .add-item-card__body {
        --add-item-label-w: 0px;
    }

    .add-item-field {
        flex-direction: column;
        align-items: stretch;
        gap: 6px;
    }

    .add-item-label {
        flex: none;
        max-width: none;
        width: 100%;
    }

    .add-item-control,
    .add-item-control--inline {
        width: 100%;
    }

    .add-item-input,
    .add-item-input--stmt,
    .add-item-input--bank-name,
    .add-item-input--bank-acc {
        width: 100%;
        max-width: none;
    }

    .add-item-amounts {
        justify-content: center;
    }
}
</style>

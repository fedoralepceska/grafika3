<template>
    <v-dialog
        v-model="dialog"
        persistent
        max-width="440"
        class="add-certificate-dialog-root"
        @keydown.esc="closeDialog"
    >
        <template #activator="{ props: activatorProps }">
            <div v-bind="activatorProps" class="add-statement-activator">
                <button type="button" class="add-statement-btn">
                    <span>Add Statement</span>
                    <i class="fa fa-plus add-statement-btn__icon" aria-hidden="true" />
                </button>
            </div>
        </template>

        <v-card class="add-cert-card">
            <div class="add-cert-card__head">
                <h2 id="add-cert-title" class="add-cert-card__title">Add statement</h2>
                <button type="button" class="add-cert-card__close" aria-label="Close" @click="closeDialog">
                    &times;
                </button>
            </div>

            <v-card-text class="add-cert-card__body">
                <form class="add-cert-form" @submit.prevent="addCertificate">
                    <div class="add-cert-field">
                        <label class="add-cert-label" for="add-cert-date">Date</label>
                        <div class="add-cert-date-wrap">
                            <input
                                id="add-cert-date"
                                v-model="dateDisplay"
                                type="text"
                                class="add-cert-input add-cert-date-text"
                                placeholder="dd/mm/yyyy"
                                inputmode="numeric"
                                autocomplete="off"
                                title="Click to open calendar or type dd/mm/yyyy"
                                @click="openNativeDatePicker"
                                @blur="commitDateDisplay"
                            />
                            <!-- Anchors native picker below the text field (not visible; opened via showPicker from text click) -->
                            <input
                                ref="nativeDateInput"
                                type="date"
                                class="add-cert-date-anchor"
                                :value="certificate.date || ''"
                                tabindex="-1"
                                aria-hidden="true"
                                @change="onNativeDatePicked"
                            />
                        </div>
                    </div>

                    <div class="add-cert-field">
                        <label class="add-cert-label" for="add-cert-bank">Bank</label>
                        <select
                            id="add-cert-bank"
                            v-model="selectedBankId"
                            class="add-cert-input"
                            @change="setSelectedBank"
                        >
                            <option value="">Select bank</option>
                            <option v-for="bank in banks" :key="bank.id" :value="bank.id">{{ bank.name }}</option>
                        </select>
                    </div>

                    <div class="add-cert-field">
                        <label class="add-cert-label" for="add-cert-account">Bank account</label>
                        <input
                            id="add-cert-account"
                            v-model="certificate.bankAccount"
                            type="text"
                            class="add-cert-input"
                            :readonly="!!selectedBankId"
                            :disabled="!selectedBankId"
                            :placeholder="selectedBankId ? '' : 'Choose a bank first'"
                            autocomplete="off"
                        />
                    </div>
                </form>
            </v-card-text>

            <div class="add-cert-card__actions">
                <button type="button" class="add-cert-btn add-cert-btn--muted" @click="closeDialog">
                    Cancel
                </button>
                <button type="button" class="add-cert-btn add-cert-btn--primary" @click="addCertificate">
                    Save statement
                </button>
            </div>
        </v-card>
    </v-dialog>
</template>

<script>
import { useToast } from 'vue-toastification';
import axios from 'axios';
import { parseDdMmYyyyToIso, formatDateDdMmYyyy } from '@/utils/financeFilters';

export default {
    data() {
        return {
            dialog: false,
            certificate: {
                date: null,
                bank: '',
                bankAccount: '',
            },
            banks: [],
            selectedBankId: '',
            /** Visible date as dd/mm/yyyy; API uses certificate.date yyyy-mm-dd */
            dateDisplay: '',
        };
    },
    computed: {
        selectedBank() {
            const id = this.selectedBankId;
            if (id === '' || id == null) {
                return null;
            }
            return this.banks.find((b) => String(b.id) === String(id)) || null;
        },
    },
    watch: {
        dialog(open) {
            if (!open) {
                this.resetFormPartial();
            } else {
                this.syncDateDisplayFromCertificate();
            }
        },
    },
    mounted() {
        this.fetchBanks();
    },
    methods: {
        resetFormPartial() {
            this.certificate.date = null;
            this.dateDisplay = '';
            this.selectedBankId = '';
            this.certificate.bankAccount = '';
        },
        syncDateDisplayFromCertificate() {
            if (!this.certificate.date) {
                this.dateDisplay = '';
                return;
            }
            const formatted = formatDateDdMmYyyy(this.certificate.date);
            this.dateDisplay = formatted === 'N/A' ? '' : formatted;
        },
        commitDateDisplay() {
            const raw = (this.dateDisplay || '').trim();
            if (!raw) {
                this.certificate.date = null;
                return;
            }
            const iso = parseDdMmYyyyToIso(raw);
            if (!iso) {
                const toast = useToast();
                toast.error('Use date as dd/mm/yyyy (e.g. 14/01/2026).');
                this.syncDateDisplayFromCertificate();
                return;
            }
            this.certificate.date = iso;
            this.dateDisplay = formatDateDdMmYyyy(iso);
        },
        onNativeDatePicked(event) {
            const v = event.target.value;
            if (!v) {
                return;
            }
            this.certificate.date = v;
            this.dateDisplay = formatDateDdMmYyyy(v);
        },
        openNativeDatePicker() {
            const el = this.$refs.nativeDateInput;
            if (!el) {
                return;
            }
            try {
                if (typeof el.showPicker === 'function') {
                    el.showPicker();
                } else {
                    el.click();
                }
            } catch (_) {
                el.click();
            }
        },
        setSelectedBank() {
            const bank = this.selectedBank;
            if (bank) {
                this.certificate.bankAccount = bank.address;
            } else {
                this.certificate.bankAccount = '';
            }
        },
        async fetchBanks() {
            try {
                const response = await axios.get('/api/banks');
                this.banks = response.data;
            } catch (error) {
                console.error('Error fetching banks:', error);
            }
        },
        closeDialog() {
            this.dialog = false;
        },
        async addCertificate() {
            const toast = useToast();
            this.commitDateDisplay();
            const bank = this.selectedBank;
            if (!bank) {
                toast.error('Please select a bank.');
                return;
            }
            if (!this.certificate.date) {
                toast.error('Please enter a date as dd/mm/yyyy.');
                return;
            }

            try {
                await axios.post('/certificate', {
                    date: this.certificate.date,
                    bank: bank.name,
                    bankAccount: this.certificate.bankAccount,
                });

                toast.success('Certificate added successfully.');
                this.resetFormPartial();
                this.closeDialog();

                setTimeout(() => {
                    window.location.reload();
                }, 800);
            } catch (error) {
                console.error('Error adding certificate:', error);
                toast.error('Error adding certificate.');
            }
        },
    },
};
</script>

<style scoped lang="scss">
.add-statement-activator {
    display: inline-flex;
    align-items: center;
    align-self: center;
}

.add-statement-btn {
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

.add-statement-btn:hover {
    filter: brightness(1.07);
}

.add-statement-btn:active {
    transform: scale(0.98);
}

.add-statement-btn__icon {
    font-size: 11px;
    opacity: 0.92;
}

/* Modal card — match finance dark panels */
.add-cert-card {
    border-radius: 12px !important;
    overflow: hidden;
    background: #1a2332 !important;
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.45) !important;
}

.add-cert-card__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    padding: 16px 18px 12px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.add-cert-card__title {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 700;
    color: $white;
    line-height: 1.3;
}

.add-cert-card__close {
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

.add-cert-card__close:hover {
    background: rgba(255, 255, 255, 0.14);
}

.add-cert-card__body {
    padding: 16px 18px 8px !important;
    color: rgba(255, 255, 255, 0.9);
}

.add-cert-form {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.add-cert-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
    min-width: 0;
}

.add-cert-date-wrap {
    position: relative;
    width: 100%;
    min-width: 0;
    overflow: visible;
}

.add-cert-date-text {
    width: 100%;
}

/* Thin invisible strip under the text field: anchors native picker below the input without blocking fields below */
.add-cert-date-anchor {
    position: absolute;
    left: 0;
    right: 0;
    top: 100%;
    width: 100%;
    height: 6px;
    margin: 0;
    padding: 0;
    border: 0;
    opacity: 0;
    pointer-events: none;
    cursor: pointer;
    font-size: 16px;
    box-sizing: border-box;
}

.add-cert-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: rgba(255, 255, 255, 0.55);
}

.add-cert-input {
    width: 100%;
    min-height: 38px;
    padding: 0 10px;
    border: 1px solid rgba(255, 255, 255, 0.14);
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.96);
    color: #111827;
    font-size: 14px;
    box-sizing: border-box;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.add-cert-input:focus {
    outline: none;
    border-color: rgba(59, 130, 246, 0.65);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
}

.add-cert-input:disabled {
    opacity: 0.55;
    cursor: not-allowed;
    background: rgba(255, 255, 255, 0.75);
}

/* Filled from bank record — not editable */
.add-cert-input:read-only:not(:disabled) {
    cursor: default;
    background: rgba(241, 245, 249, 0.98);
    color: #1e293b;
    border-color: rgba(255, 255, 255, 0.12);
}

.add-cert-card__actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 8px;
    padding: 12px 18px 16px;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.add-cert-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 34px;
    padding: 0 14px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: filter 0.15s ease, background 0.15s ease, border-color 0.15s ease;
}

.add-cert-btn--muted {
    border: 1px solid rgba(255, 255, 255, 0.22);
    background: rgba(255, 255, 255, 0.06);
    color: rgba(255, 255, 255, 0.9);
}

.add-cert-btn--muted:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.3);
}

.add-cert-btn--primary {
    border: 1px solid transparent;
    background: $blue;
    color: $white;
}

.add-cert-btn--primary:hover {
    filter: brightness(1.06);
}
</style>

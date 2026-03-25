<template>
    <div class="card-statement-dialog-wrap">
        <v-dialog
            v-model="dialog"
            max-width="1000"
            :scrim="true"
            class="cs-dialog-root"
            @keydown.esc="onCancel"
        >
            <template #activator="{ props: actProps }">
                <button
                    type="button"
                    class="cs-trigger"
                    v-bind="actProps"
                    :aria-label="$t('cardStatementModalTitle')"
                >
                    <i class="fa-solid fa-file-invoice" aria-hidden="true"></i>
                </button>
            </template>

            <v-card class="cs-card" rounded="xl" elevation="12">
                <div class="cs-card__header">
                    <div class="cs-card__title-block">
                        <h2 class="cs-card__title">{{ $t('cardStatementModalTitle') }}</h2>
                        <p class="cs-card__hint">{{ $t('cardStatementModalHint') }}</p>
                    </div>
                    <button
                        type="button"
                        class="cs-card__close"
                        :aria-label="$t('close')"
                        @click="onCancel"
                    >
                        <i class="fa fa-times" aria-hidden="true"></i>
                    </button>
                </div>

                <v-card-text class="cs-card__body pa-0">
                    <div v-if="loading" class="cs-loading">
                        <i class="fa fa-spinner fa-spin" aria-hidden="true"></i>
                    </div>
                    <form v-else class="cs-form" @submit.prevent="updateCard">
                        <div class="cs-content">
                            <div class="cs-personal">
                                <fieldset class="cs-fieldset">
                                    <legend>{{ $t('cardStatementPersonal') }}</legend>
                                    <div class="cs-form-group">
                                        <label for="ccs-name" class="cs-label-inline">{{ $t('name') }}</label>
                                        <input id="ccs-name" v-model="draft.name" type="text" class="rounded text-gray-700 cs-input-grow">
                                    </div>
                                    <div class="cs-form-group">
                                        <label for="ccs-fn" class="cs-label-inline">{{ $t('cardStatementFunction') }}</label>
                                        <input id="ccs-fn" v-model="draft.functionInfo" type="text" class="rounded text-gray-700 cs-input-grow">
                                    </div>
                                    <div class="cs-form-group">
                                        <label for="ccs-phone" class="cs-label-inline">{{ $t('phone') }}</label>
                                        <input id="ccs-phone" v-model="draft.phone" type="text" class="rounded text-gray-700 cs-input-grow">
                                    </div>
                                    <div class="cs-form-group">
                                        <label for="ccs-fax" class="cs-label-inline">{{ $t('cardStatementFax') }}</label>
                                        <input id="ccs-fax" v-model="draft.fax" type="text" class="rounded text-gray-700 cs-input-grow">
                                    </div>
                                    <div class="cs-form-group">
                                        <label for="ccs-mobile" class="cs-label-inline">{{ $t('cardStatementMobile') }}</label>
                                        <input id="ccs-mobile" v-model="draft.mobile_phone" type="text" class="rounded text-gray-700 cs-input-grow">
                                    </div>
                                </fieldset>
                            </div>
                            <div class="cs-financial">
                                <fieldset class="cs-fieldset">
                                    <legend>{{ $t('cardStatementFinancial') }}</legend>
                                    <div class="cs-form-group">
                                        <label for="ccs-edb" class="cs-label-inline">{{ $t('cardStatementEdb') }}</label>
                                        <input id="ccs-edb" v-model="draft.edb" type="text" class="rounded text-gray-700 cs-input-grow">
                                    </div>
                                    <div class="cs-form-group">
                                        <label for="ccs-acc" class="cs-label-inline">{{ $t('cardStatementAccount') }}</label>
                                        <input id="ccs-acc" v-model="draft.account" type="text" class="rounded text-gray-700 cs-input-grow">
                                    </div>
                                    <div class="cs-form-group">
                                        <label for="ccs-bank" class="cs-label-inline">{{ $t('cardStatementBank') }}</label>
                                        <input id="ccs-bank" v-model="draft.bank" type="text" class="rounded text-gray-700 cs-input-grow">
                                    </div>
                                </fieldset>
                                <fieldset class="cs-fieldset cs-fieldset--follow">
                                    <legend>{{ $t('cardStatementAdditional') }}</legend>
                                    <div class="cs-form-group">
                                        <label for="ccs-is" class="cs-label-inline">{{ $t('cardStatementInitialStatement') }}</label>
                                        <input id="ccs-is" v-model="draft.initial_statement" type="text" class="rounded text-gray-700 cs-input-grow">
                                    </div>
                                    <div class="cs-form-group">
                                        <label for="ccs-ic" class="cs-label-inline">{{ $t('cardStatementInitialBalance') }}</label>
                                        <input id="ccs-ic" v-model="draft.initial_cash" type="text" class="rounded text-gray-700 cs-input-grow">
                                    </div>
                                    <div class="cs-form-group">
                                        <label for="ccs-cl" class="cs-label-inline">{{ $t('cardStatementCreditLimit') }}</label>
                                        <input id="ccs-cl" v-model="draft.credit_limit" type="text" class="rounded text-gray-700 cs-input-grow">
                                    </div>
                                    <div class="cs-form-group">
                                        <label for="ccs-pd" class="cs-label-inline">{{ $t('cardStatementPaymentDeadline') }}</label>
                                        <input id="ccs-pd" v-model="draft.payment_deadline" type="text" class="rounded text-gray-700 cs-input-grow">
                                    </div>
                                </fieldset>
                            </div>
                        </div>
                    </form>
                </v-card-text>

                <v-card-actions class="cs-card__actions">
                    <button type="button" class="cs-btn cs-btn--danger" :disabled="saving" @click="onCancel">
                        {{ $t('close') }}
                    </button>
                    <button
                        type="button"
                        class="cs-btn cs-btn--primary"
                        :disabled="saving || loading"
                        @click="updateCard"
                    >
                        {{ saving ? '…' : $t('cardStatementSave') }}
                    </button>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script>
import { useToast } from 'vue-toastification';
import axios from 'axios';

function emptyDraft(clientId) {
    return {
        name: '',
        functionInfo: '',
        phone: '',
        fax: '',
        mobile_phone: '',
        edb: '',
        account: '',
        bank: '',
        initial_statement: '',
        initial_cash: '',
        credit_limit: '',
        payment_deadline: '',
        client_id: clientId,
    };
}

export default {
    props: {
        client_id: Number,
    },
    emits: ['dialogOpened'],
    data() {
        return {
            dialog: false,
            loading: false,
            saving: false,
            draft: emptyDraft(this.client_id),
        };
    },
    watch: {
        dialog(open) {
            if (open) {
                this.loadDraft();
            }
        },
        client_id(id) {
            if (id != null) {
                this.draft.client_id = id;
            }
        },
    },
    mounted() {
        document.addEventListener('keydown', this.handleEscapeKey);
    },
    beforeUnmount() {
        document.removeEventListener('keydown', this.handleEscapeKey);
    },
    methods: {
        handleEscapeKey(event) {
            if (event.key === 'Escape' && this.dialog) {
                this.onCancel();
            }
        },
        onCancel() {
            if (this.saving) return;
            this.dialog = false;
        },
        normalizeRow(row) {
            const d = emptyDraft(this.client_id);
            if (!row || typeof row !== 'object' || Array.isArray(row)) {
                return d;
            }
            const keys = Object.keys(d);
            keys.forEach((k) => {
                if (row[k] !== undefined && row[k] !== null) {
                    d[k] = row[k];
                }
            });
            d.client_id = this.client_id;
            return d;
        },
        async loadDraft() {
            this.loading = true;
            try {
                const response = await axios.get(`/client_card_statement/${this.client_id}`);
                const row = response.data;
                if (row && typeof row === 'object' && !Array.isArray(row) && Object.keys(row).length > 0) {
                    this.draft = this.normalizeRow(row);
                } else {
                    this.draft = emptyDraft(this.client_id);
                }
            } catch (e) {
                console.error(e);
                this.draft = emptyDraft(this.client_id);
            } finally {
                this.loading = false;
                this.$emit('dialogOpened');
            }
        },
        async updateCard() {
            if (this.saving || this.loading) return;
            const toast = useToast();
            this.saving = true;
            try {
                const payload = { ...this.draft, client_id: this.client_id };
                const response = await axios.post('/client_card_statement', payload);
                toast.success(response.data?.message || 'Client card statement saved successfully.');
                this.dialog = false;
            } catch (error) {
                toast.error('Error saving client card statement!');
                console.error(error);
            } finally {
                this.saving = false;
            }
        },
    },
};
</script>

<style scoped lang="scss">
.card-statement-dialog-wrap {
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.cs-trigger {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    padding: 0;
    border: 1px solid rgba(255, 255, 255, 0.14);
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.06);
    color: rgba(255, 255, 255, 0.85);
    cursor: pointer;
    transition:
        background 0.15s ease,
        border-color 0.15s ease,
        color 0.15s ease;
}

.cs-trigger:hover {
    background: rgba(59, 130, 246, 0.22);
    border-color: rgba(59, 130, 246, 0.45);
    color: $white;
}

.cs-trigger:focus-visible {
    outline: 2px solid rgba(96, 165, 250, 0.8);
    outline-offset: 2px;
}

.cs-trigger i {
    font-size: 15px;
}

.cs-card {
    background-color: $light-gray !important;
    border: 1px solid rgba(0, 0, 0, 0.12);
    color: $white;
    overflow: hidden;
}

.cs-card__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    padding: 20px 20px 12px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.12);
}

.cs-card__title {
    margin: 0;
    font-size: 1.15rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    line-height: 1.25;
}

.cs-card__hint {
    margin: 6px 0 0;
    font-size: 0.8125rem;
    line-height: 1.45;
    color: rgba(255, 255, 255, 0.65);
    max-width: 640px;
}

.cs-card__close {
    flex-shrink: 0;
    width: 36px;
    height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: none;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.08);
    color: rgba(255, 255, 255, 0.8);
    cursor: pointer;
    transition: background 0.15s ease, color 0.15s ease;
}

.cs-card__close:hover {
    background: rgba(255, 255, 255, 0.14);
    color: $white;
}

.cs-card__body {
    padding: 0 !important;
    max-height: calc(100vh - 140px);
    overflow-y: auto;
}

.cs-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 200px;
    font-size: 28px;
    color: rgba(255, 255, 255, 0.7);
}

.cs-form {
    padding: 16px 20px 12px;
}

/* Original side‑by‑side layout: personal | financial (+ additional below) */
.cs-content {
    display: flex;
    flex-direction: row;
    justify-content: space-around;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 20px;
}

.cs-personal {
    width: 400px;
    max-width: 100%;
    flex-shrink: 0;
}

.cs-financial {
    width: 480px;
    max-width: 100%;
    flex-shrink: 0;
}

.cs-fieldset {
    border: 1px solid #ffffff;
    border-radius: 3px;
    padding: 12px 10px 8px;
    margin: 0;
    min-width: 0;
}

.cs-fieldset--follow {
    margin-top: 16px;
}

.cs-fieldset legend {
    margin-left: 10px;
    padding: 0 6px;
    color: $white;
    font-size: 0.95rem;
    font-weight: 600;
}

.cs-form-group {
    display: flex;
    justify-content: flex-start;
    align-items: center;
    width: 100%;
    max-width: 350px;
    color: $white;
    padding-left: 10px;
    box-sizing: border-box;
}

.cs-label-inline {
    flex: 0 0 150px;
    width: 150px;
    margin-right: 16px;
    text-align: left;
    font-size: 0.875rem;
}

.cs-input-grow {
    flex: 1;
    min-width: 0;
    margin: 12px 0;
    padding: 6px 10px;
    border: 1px solid rgba(0, 0, 0, 0.15);
}

.cs-card__actions {
    padding: 16px 20px 20px !important;
    border-top: 1px solid rgba(255, 255, 255, 0.12);
    justify-content: flex-end !important;
    gap: 10px !important;
    flex-wrap: wrap;
}

.cs-btn {
    min-height: 42px;
    padding: 0 18px;
    border-radius: 10px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: opacity 0.15s ease, filter 0.15s ease;
}

.cs-btn:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.cs-btn--danger {
    background-color: $red;
    color: $white;
}

.cs-btn--danger:hover:not(:disabled) {
    filter: brightness(1.08);
}

.cs-btn--primary {
    background-color: $green;
    color: $white;
}

.cs-btn--primary:hover:not(:disabled) {
    filter: brightness(1.08);
}
</style>

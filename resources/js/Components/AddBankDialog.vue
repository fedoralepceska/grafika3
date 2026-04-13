<template>
    <v-dialog
        v-model="dialog"
        persistent
        max-width="480"
        class="bank-dlg-root"
        @keydown.esc="closeDialog"
    >
        <template #activator="{ props: activatorProps }">
            <div v-bind="activatorProps" class="bank-dlg-activator">
                <button type="button" class="bank-dlg-toolbar-btn">
                    <span>Add Bank</span>
                    <i class="fa fa-plus bank-dlg-toolbar-btn__icon" aria-hidden="true" />
                </button>
            </div>
        </template>

        <v-card class="bank-dlg-card">
            <div class="bank-dlg-card__head">
                <h2 id="add-bank-title" class="bank-dlg-card__title">Add New Bank</h2>
                <button type="button" class="bank-dlg-card__close" aria-label="Close" @click="closeDialog">
                    &times;
                </button>
            </div>

            <v-card-text class="bank-dlg-card__body">
                <form class="bank-dlg-form" @submit.prevent="saveData">
                    <section class="bank-dlg-section">
                        <div class="bank-dlg-field">
                            <label class="bank-dlg-label" for="add-bank-name">Bank name</label>
                            <div class="bank-dlg-control">
                                <input
                                    id="add-bank-name"
                                    v-model="newBank.name"
                                    type="text"
                                    class="bank-dlg-input"
                                    autocomplete="organization"
                                    @input="checkDuplicate"
                                />
                            </div>
                        </div>
                        <div class="bank-dlg-field">
                            <label class="bank-dlg-label" for="add-bank-account">Bank account</label>
                            <div class="bank-dlg-control">
                                <input
                                    id="add-bank-account"
                                    v-model="newBank.address"
                                    type="text"
                                    class="bank-dlg-input"
                                    autocomplete="off"
                                    @input="checkDuplicate"
                                />
                            </div>
                        </div>
                        <p v-if="isDuplicate" class="bank-dlg-error" role="alert">
                            This bank with this account already exists.
                        </p>
                    </section>
                </form>
            </v-card-text>

            <div class="bank-dlg-card__actions">
                <button type="button" class="bank-dlg-footer-btn bank-dlg-footer-btn--close" @click="closeDialog">
                    Close
                </button>
                <button
                    type="button"
                    class="bank-dlg-footer-btn bank-dlg-footer-btn--save"
                    :disabled="isDuplicate || !isValid"
                    @click="saveData"
                >
                    Add
                </button>
            </div>
        </v-card>
    </v-dialog>
</template>

<script>
import { useToast } from 'vue-toastification';

export default {
    props: {
        bank: {
            type: Object,
            default: null,
        },
    },
    data() {
        return {
            dialog: false,
            newBank: {
                name: '',
                address: '',
            },
            existingBanks: [],
            isDuplicate: false,
        };
    },
    computed: {
        isValid() {
            return this.newBank.name.trim() !== '' && this.newBank.address.trim() !== '';
        },
    },
    watch: {
        dialog(open) {
            if (open) {
                this.fetchBanks();
            } else {
                this.newBank = { name: '', address: '' };
                this.isDuplicate = false;
            }
        },
    },
    methods: {
        closeDialog() {
            this.dialog = false;
        },
        async fetchBanks() {
            try {
                const response = await axios.get('/api/banks');
                this.existingBanks = response.data;
            } catch (error) {
                console.error('Error fetching banks:', error);
            }
        },
        checkDuplicate() {
            if (this.newBank.name && this.newBank.address) {
                this.isDuplicate = this.existingBanks.some(
                    (b) =>
                        b.name.toLowerCase() === this.newBank.name.toLowerCase() &&
                        b.address.toLowerCase() === this.newBank.address.toLowerCase(),
                );
            } else {
                this.isDuplicate = false;
            }
        },
        saveData() {
            if (this.isDuplicate) {
                return;
            }

            const toast = useToast();
            axios
                .post('/api/banks', this.newBank)
                .then(() => {
                    this.dialog = false;
                    toast.success('Bank created successfully!');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                })
                .catch((error) => {
                    console.error('Error creating bank:', error);
                    toast.error('Failed to create bank!');
                });
        },
    },
};
</script>

<style scoped lang="scss">
.bank-dlg-activator {
    display: inline-flex;
    align-items: center;
}

.bank-dlg-toolbar-btn {
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

.bank-dlg-toolbar-btn:hover {
    filter: brightness(1.07);
}

.bank-dlg-toolbar-btn:active {
    transform: scale(0.98);
}

.bank-dlg-toolbar-btn__icon {
    font-size: 11px;
    opacity: 0.92;
}

.bank-dlg-card {
    border-radius: 12px !important;
    overflow: hidden;
    background: #1a2332 !important;
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.45) !important;
}

.bank-dlg-card__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    padding: 16px 18px 12px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.bank-dlg-card__title {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 700;
    color: $white;
    line-height: 1.3;
}

.bank-dlg-card__close {
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

.bank-dlg-card__close:hover {
    background: rgba(255, 255, 255, 0.14);
}

.bank-dlg-card__body {
    --bank-dlg-label-w: 132px;
    padding: 14px 18px 8px !important;
    color: rgba(255, 255, 255, 0.9);
    max-height: min(60vh, 420px);
    overflow-y: auto;
}

.bank-dlg-section {
    padding: 12px 14px 14px;
    border: 1px solid rgba(255, 255, 255, 0.22);
    border-radius: 12px;
    background: rgba(0, 0, 0, 0.12);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
}

.bank-dlg-field {
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 12px 16px;
    margin-bottom: 10px;
    min-width: 0;
}

.bank-dlg-field:last-of-type {
    margin-bottom: 0;
}

.bank-dlg-label {
    flex: 0 0 var(--bank-dlg-label-w);
    max-width: 42%;
    margin: 0;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: rgba(255, 255, 255, 0.88);
    line-height: 1.25;
}

.bank-dlg-control {
    flex: 1 1 auto;
    min-width: 0;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
}

.bank-dlg-input {
    width: 100%;
    max-width: 280px;
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

.bank-dlg-input:focus {
    outline: none;
    border-color: rgba(59, 130, 246, 0.75);
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}

.bank-dlg-error {
    margin: 12px 0 0;
    padding: 0;
    font-size: 13px;
    font-weight: 600;
    color: #fca5a5;
}

.bank-dlg-card__actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 10px;
    padding: 12px 18px 16px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.bank-dlg-footer-btn {
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
    transition: filter 0.15s ease, transform 0.08s ease, opacity 0.15s ease;
}

.bank-dlg-footer-btn:active:not(:disabled) {
    transform: scale(0.98);
}

.bank-dlg-footer-btn:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}

.bank-dlg-footer-btn--close {
    background: #b71c1c;
    color: #fff;
    border-color: rgba(0, 0, 0, 0.2);
    box-shadow: 0 1px 0 rgba(255, 255, 255, 0.12) inset;
}

.bank-dlg-footer-btn--close:hover:not(:disabled) {
    filter: brightness(1.08);
}

.bank-dlg-footer-btn--save {
    background: #1b5e20;
    color: #fff;
    border-color: rgba(0, 0, 0, 0.15);
    box-shadow: 0 1px 0 rgba(255, 255, 255, 0.1) inset;
}

.bank-dlg-footer-btn--save:hover:not(:disabled) {
    filter: brightness(1.08);
}

@media (max-width: 520px) {
    .bank-dlg-card__body {
        --bank-dlg-label-w: 0px;
    }

    .bank-dlg-field {
        flex-direction: column;
        align-items: stretch;
        gap: 6px;
    }

    .bank-dlg-label {
        flex: none;
        max-width: none;
        width: 100%;
    }

    .bank-dlg-input {
        max-width: none;
    }
}
</style>

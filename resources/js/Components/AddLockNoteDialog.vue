<template>
    <button type="button" class="btn lock-order" @click="openDialog">
        Lock Order <span class="mdi mdi-lock"></span>
    </button>

    <Teleport to="body">
        <div
            v-if="dialog"
            class="lock-modal-overlay"
            role="presentation"
            @click="closeDialog"
        >
            <div
                class="lock-modal"
                role="dialog"
                aria-modal="true"
                aria-labelledby="lock-order-title"
                @click.stop
            >
                <div class="lock-modal__header">
                    <div>
                        <p class="lock-modal__eyebrow">{{ hasLockNote ? 'Order locked' : 'Lock order' }}</p>
                        <h3 id="lock-order-title" class="lock-modal__title">
                            {{ invoice?.invoice_title || `Order #${invoice?.order_number || invoice?.id}` }}
                        </h3>
                    </div>
                    <button type="button" class="lock-modal__close" aria-label="Close" @click="closeDialog">
                        <i class="fa fa-times" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="lock-modal__body">
                    <div class="lock-modal__info">
                        <div class="lock-modal__info-item">
                            <span class="lock-modal__label">Order</span>
                            <strong>#{{ invoice?.order_number || invoice?.id }}</strong>
                        </div>
                        <div class="lock-modal__info-item">
                            <span class="lock-modal__label">Status</span>
                            <strong>{{ hasLockNote ? 'Locked' : 'Unlocked' }}</strong>
                        </div>
                    </div>

                    <label class="lock-modal__field" for="lock-note">
                        <span class="lock-modal__field-label">Lock reason</span>
                        <textarea
                            id="lock-note"
                            v-model="lockNote"
                            class="lock-modal__textarea"
                            rows="6"
                            maxlength="1200"
                            placeholder="Explain why this order should not be invoiced."
                        />
                    </label>

                    <div class="lock-modal__hint">
                        Locked orders stay visible, but they cannot be selected for invoicing until you unlock them.
                    </div>
                </div>

                <div class="lock-modal__footer">
                    <button type="button" class="lock-modal__btn lock-modal__btn--ghost" @click="closeDialog">
                        Close
                    </button>
                    <button
                        v-if="hasLockNote"
                        type="button"
                        class="lock-modal__btn lock-modal__btn--danger"
                        :disabled="submitting"
                        @click="unlockOrder"
                    >
                        <i class="mdi mdi-lock-open-variant-outline" aria-hidden="true"></i>
                        <span>Unlock</span>
                    </button>
                    <button
                        type="button"
                        class="lock-modal__btn lock-modal__btn--primary"
                        :disabled="submitting || !trimmedLockNote"
                        @click="saveData"
                    >
                        <i class="mdi mdi-lock-outline" aria-hidden="true"></i>
                        <span>{{ hasLockNote ? 'Save lock' : 'Lock order' }}</span>
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script>
import axios from 'axios';
import { useToast } from 'vue-toastification';

export default {
    props: {
        invoice: {
            type: Object,
            required: true,
        },
    },
    emits: ['updated'],
    data() {
        return {
            dialog: false,
            lockNote: '',
            submitting: false,
        };
    },
    computed: {
        trimmedLockNote() {
            return (this.lockNote || '').trim();
        },
        hasLockNote() {
            return !!(this.invoice?.LockedNote && String(this.invoice.LockedNote).trim());
        },
    },
    watch: {
        dialog(open) {
            if (open) {
                this.syncFromInvoice();
                window.addEventListener('keydown', this.handleEscapeKey);
            } else {
                window.removeEventListener('keydown', this.handleEscapeKey);
            }
        },
    },
    beforeUnmount() {
        window.removeEventListener('keydown', this.handleEscapeKey);
    },
    methods: {
        syncFromInvoice() {
            this.lockNote = this.invoice?.LockedNote || '';
        },
        openDialog() {
            this.syncFromInvoice();
            this.dialog = true;
        },
        closeDialog() {
            if (this.submitting) {
                return;
            }
            this.dialog = false;
        },
        handleEscapeKey(event) {
            if (event.key === 'Escape') {
                this.closeDialog();
            }
        },
        async updateLock(comment, successMessage) {
            const toast = useToast();
            this.submitting = true;
            try {
                await axios.put('/orders/update-locked-note', {
                    id: this.invoice.id,
                    comment,
                });
                this.invoice.LockedNote = comment;
                this.$emit('updated', { id: this.invoice.id, LockedNote: comment });
                toast.success(successMessage);
                this.dialog = false;
            } catch (error) {
                toast.error('Failed to update order lock.');
            } finally {
                this.submitting = false;
            }
        },
        async saveData() {
            if (!this.trimmedLockNote) {
                return;
            }
            await this.updateLock(this.trimmedLockNote, 'Order successfully locked.');
        },
        async unlockOrder() {
            await this.updateLock(null, 'Order successfully unlocked.');
        },
    },
};
</script>

<style scoped lang="scss">
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 42px;
    padding: 0 16px;
    border: none;
    border-radius: 4px;
    background: linear-gradient(180deg, rgba(2, 132, 199, 0.95) 0%, rgba(3, 105, 161, 0.95) 100%);
    color: $white;
    font-size: 14px;
    font-weight: 700;
    line-height: 1;
    cursor: pointer;
    transition: filter 0.15s ease, background 0.15s ease, border-color 0.15s ease;
}

.btn:hover {
    filter: brightness(1.02);
}

.lock-modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 4000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    background: rgba(15, 23, 42, 0.7);
    backdrop-filter: blur(4px);
}

.lock-modal {
    width: min(620px, 100%);
    border-radius: 18px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    background: linear-gradient(180deg, rgba(30, 41, 59, 0.98) 0%, rgba(15, 23, 42, 0.98) 100%);
    color: $white;
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.38);
    overflow: hidden;
}

.lock-modal__header,
.lock-modal__footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 18px 22px;
}

.lock-modal__header {
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.lock-modal__eyebrow {
    margin: 0 0 6px;
    color: rgba(148, 163, 184, 0.92);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.lock-modal__title {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 700;
    line-height: 1.35;
}

.lock-modal__close {
    width: 38px;
    height: 38px;
    border: 0;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.08);
    color: rgba(255, 255, 255, 0.86);
    cursor: pointer;
    transition: background 0.15s ease;
}

.lock-modal__close:hover {
    background: rgba(255, 255, 255, 0.14);
}

.lock-modal__body {
    padding: 22px;
}

.lock-modal__info {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 18px;
}

.lock-modal__info-item {
    padding: 12px 14px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.08);
}

.lock-modal__label,
.lock-modal__field-label {
    display: block;
    margin-bottom: 6px;
    color: rgba(148, 163, 184, 0.92);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.lock-modal__textarea {
    width: 100%;
    min-height: 140px;
    resize: vertical;
    border: 1px solid rgba(148, 163, 184, 0.28);
    border-radius: 14px;
    background: rgba(15, 23, 42, 0.72);
    color: $white;
    padding: 14px 16px;
    font-size: 14px;
    line-height: 1.5;
}

.lock-modal__textarea:focus {
    outline: none;
    border-color: rgba(96, 165, 250, 0.8);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.18);
}

.lock-modal__hint {
    margin-top: 12px;
    color: rgba(191, 219, 254, 0.88);
    font-size: 12px;
    line-height: 1.45;
}

.lock-modal__footer {
    justify-content: flex-end;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.lock-modal__btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 40px;
    padding: 0 16px;
    border-radius: 10px;
    border: 1px solid transparent;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: filter 0.15s ease, background 0.15s ease, opacity 0.15s ease;
}

.lock-modal__btn:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.lock-modal__btn--ghost {
    background: rgba(255, 255, 255, 0.07);
    border-color: rgba(255, 255, 255, 0.14);
    color: rgba(255, 255, 255, 0.9);
}

.lock-modal__btn--danger {
    background: rgba(220, 38, 38, 0.18);
    border-color: rgba(248, 113, 113, 0.35);
    color: #fecaca;
}

.lock-modal__btn--primary {
    background: $green;
    color: $white;
}

@media (max-width: 640px) {
    .lock-modal-overlay {
        padding: 14px;
    }

    .lock-modal__info {
        grid-template-columns: 1fr;
    }

    .lock-modal__footer {
        flex-wrap: wrap;
    }

    .lock-modal__btn {
        flex: 1 1 100%;
    }
}
</style>

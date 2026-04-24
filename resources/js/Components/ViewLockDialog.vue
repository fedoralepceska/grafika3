<template>
    <button type="button" class="lock-pill" @click="openDialog">
        <i class="mdi mdi-lock-outline" aria-hidden="true"></i>
        <span>Locked</span>
    </button>

    <Teleport to="body">
        <div
            v-if="dialog"
            class="lock-view-overlay"
            role="presentation"
            @click="closeDialog"
        >
            <div
                class="lock-view-modal"
                role="dialog"
                aria-modal="true"
                aria-labelledby="lock-view-title"
                @click.stop
            >
                <div class="lock-view-modal__header">
                    <div>
                        <p class="lock-view-modal__eyebrow">Order lock</p>
                        <h3 id="lock-view-title" class="lock-view-modal__title">
                            {{ invoice?.invoice_title || `Order #${invoice?.order_number || invoice?.id}` }}
                        </h3>
                    </div>
                    <button type="button" class="lock-view-modal__close" aria-label="Close" @click="closeDialog">
                        <i class="fa fa-times" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="lock-view-modal__body">
                    <div class="lock-view-modal__state">
                        <i class="mdi mdi-lock-check-outline" aria-hidden="true"></i>
                        <span>This order is locked and cannot be invoiced.</span>
                    </div>

                    <div class="lock-view-modal__note">
                        {{ invoice?.LockedNote || 'No lock note provided.' }}
                    </div>
                </div>

                <div class="lock-view-modal__footer">
                    <button type="button" class="lock-view-modal__btn lock-view-modal__btn--ghost" @click="closeDialog">
                        Close
                    </button>
                    <button
                        type="button"
                        class="lock-view-modal__btn lock-view-modal__btn--danger"
                        :disabled="submitting"
                        @click="unlockOrder"
                    >
                        <i class="mdi mdi-lock-open-variant-outline" aria-hidden="true"></i>
                        <span>Unlock</span>
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
            submitting: false,
        };
    },
    watch: {
        dialog(open) {
            if (open) {
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
        openDialog() {
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
        async unlockOrder() {
            const toast = useToast();
            this.submitting = true;
            try {
                await axios.put('/orders/update-locked-note', {
                    id: this.invoice.id,
                    comment: null,
                });
                this.invoice.LockedNote = null;
                this.$emit('updated', { id: this.invoice.id, LockedNote: null });
                toast.success('Order successfully unlocked.');
                this.dialog = false;
            } catch (error) {
                toast.error('Failed to unlock order.');
            } finally {
                this.submitting = false;
            }
        },
    },
};
</script>

<style scoped lang="scss">
.lock-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    min-height: 28px;
    padding: 0 10px;
    border: 1px solid rgba(248, 113, 113, 0.35);
    border-radius: 999px;
    background: rgba(239, 68, 68, 0.12);
    color: #fecaca;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    cursor: pointer;
    transition: background 0.15s ease, border-color 0.15s ease;
}

.lock-pill:hover {
    background: rgba(239, 68, 68, 0.18);
    border-color: rgba(252, 165, 165, 0.6);
}

.lock-view-overlay {
    position: fixed;
    inset: 0;
    z-index: 4000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    background: rgba(15, 23, 42, 0.72);
    backdrop-filter: blur(4px);
}

.lock-view-modal {
    width: min(560px, 100%);
    border-radius: 18px;
    overflow: hidden;
    background: linear-gradient(180deg, rgba(30, 41, 59, 0.98) 0%, rgba(15, 23, 42, 0.98) 100%);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: $white;
    box-shadow: 0 24px 56px rgba(0, 0, 0, 0.34);
}

.lock-view-modal__header,
.lock-view-modal__footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 18px 22px;
}

.lock-view-modal__header {
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.lock-view-modal__eyebrow {
    margin: 0 0 6px;
    color: rgba(148, 163, 184, 0.92);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.lock-view-modal__title {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 700;
}

.lock-view-modal__close {
    width: 38px;
    height: 38px;
    border: 0;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.08);
    color: rgba(255, 255, 255, 0.88);
    cursor: pointer;
}

.lock-view-modal__body {
    padding: 22px;
}

.lock-view-modal__state {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 16px;
    padding: 10px 12px;
    border-radius: 12px;
    background: rgba(239, 68, 68, 0.12);
    border: 1px solid rgba(248, 113, 113, 0.2);
    color: #fecaca;
    font-weight: 600;
}

.lock-view-modal__note {
    white-space: pre-wrap;
    line-height: 1.6;
    padding: 16px;
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.08);
    color: rgba(255, 255, 255, 0.92);
}

.lock-view-modal__footer {
    justify-content: flex-end;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.lock-view-modal__btn {
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
}

.lock-view-modal__btn:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.lock-view-modal__btn--ghost {
    background: rgba(255, 255, 255, 0.07);
    border-color: rgba(255, 255, 255, 0.14);
    color: rgba(255, 255, 255, 0.92);
}

.lock-view-modal__btn--danger {
    background: rgba(220, 38, 38, 0.18);
    border-color: rgba(248, 113, 113, 0.35);
    color: #fecaca;
}
</style>

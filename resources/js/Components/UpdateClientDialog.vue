<template>
    <div class="update-client-wrap">
        <v-dialog
            v-model="dialog"
            max-width="480"
            :scrim="true"
            class="update-client-dialog-root"
            @keydown.esc="onCancel"
        >
            <template #activator="{ props: actProps }">
                <button
                    type="button"
                    class="uc-trigger"
                    v-bind="actProps"
                    :aria-label="$t('updateClientModalTitle')"
                >
                    <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                </button>
            </template>

            <v-card class="uc-card" rounded="xl" elevation="12">
                <div class="uc-card__header">
                    <div class="uc-card__title-block">
                        <h2 class="uc-card__title">{{ $t('updateClientModalTitle') }}</h2>
                        <p class="uc-card__hint">{{ $t('updateClientModalHint') }}</p>
                    </div>
                    <button
                        type="button"
                        class="uc-card__close"
                        :aria-label="$t('close')"
                        @click="onCancel"
                    >
                        <i class="fa fa-times" aria-hidden="true"></i>
                    </button>
                </div>

                <v-card-text class="uc-card__body pa-0">
                    <p v-if="client" class="uc-client-pill">{{ client.name }}</p>

                    <form class="uc-form" @submit.prevent="updateContact">
                        <div class="uc-field">
                            <label class="uc-label" for="uc-name">{{ $t('company') }}</label>
                            <input
                                id="uc-name"
                                v-model="draft.name"
                                type="text"
                                class="uc-input"
                                autocomplete="organization"
                            />
                        </div>
                        <div class="uc-field">
                            <label class="uc-label" for="uc-city">{{ $t('city') }}</label>
                            <input
                                id="uc-city"
                                v-model="draft.city"
                                type="text"
                                class="uc-input"
                                autocomplete="address-level2"
                            />
                        </div>
                        <div class="uc-field">
                            <label class="uc-label" for="uc-address">{{ $t('address') }}</label>
                            <input
                                id="uc-address"
                                v-model="draft.address"
                                type="text"
                                class="uc-input"
                                autocomplete="street-address"
                            />
                        </div>
                    </form>
                </v-card-text>

                <v-card-actions class="uc-card__actions">
                    <button type="button" class="uc-btn uc-btn--ghost" @click="onCancel">
                        {{ $t('close') }}
                    </button>
                    <button
                        type="button"
                        class="uc-btn uc-btn--primary"
                        :disabled="saving"
                        @click="updateContact"
                    >
                        {{ saving ? '…' : $t('updateClientSave') }}
                    </button>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script>
import { useToast } from 'vue-toastification';
import axios from 'axios';

export default {
    props: {
        client: Object,
    },
    data() {
        return {
            dialog: false,
            saving: false,
            draft: {
                name: '',
                city: '',
                address: '',
            },
        };
    },
    watch: {
        dialog(open) {
            if (open && this.client) {
                this.draft = {
                    name: this.client.name ?? '',
                    city: this.client.city ?? '',
                    address: this.client.address ?? '',
                };
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
        async updateContact() {
            if (!this.client || this.saving) return;
            const toast = useToast();
            try {
                this.saving = true;
                const response = await axios.put(`/clients/${this.client.id}`, {
                    name: this.draft.name,
                    city: this.draft.city,
                    address: this.draft.address,
                });
                Object.assign(this.client, {
                    name: this.draft.name,
                    city: this.draft.city,
                    address: this.draft.address,
                });
                toast.success(response.data.message || 'Updated');
                this.dialog = false;
            } catch (error) {
                toast.error('Error updating client!');
                console.error(error);
            } finally {
                this.saving = false;
            }
        },
    },
};
</script>

<style scoped lang="scss">
.update-client-wrap {
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.uc-trigger {
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
    transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease, transform 0.12s ease;
}

.uc-trigger:hover {
    background: rgba(59, 130, 246, 0.22);
    border-color: rgba(59, 130, 246, 0.45);
    color: $white;
}

.uc-trigger:focus-visible {
    outline: 2px solid rgba(96, 165, 250, 0.8);
    outline-offset: 2px;
}

.uc-trigger i {
    font-size: 15px;
}

:deep(.update-client-dialog-root .v-overlay__content) {
    width: 100%;
    max-width: 480px;
}

.uc-card {
    background-color: $light-gray !important;
    border: 1px solid rgba(0, 0, 0, 0.12);
    color: $white;
    overflow: hidden;
}

.uc-card__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    padding: 20px 20px 12px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.uc-card__title {
    margin: 0;
    font-size: 1.15rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    line-height: 1.25;
}

.uc-card__hint {
    margin: 6px 0 0;
    font-size: 0.8125rem;
    line-height: 1.45;
    color: rgba(255, 255, 255, 0.55);
    max-width: 340px;
}

.uc-card__close {
    flex-shrink: 0;
    width: 36px;
    height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: none;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.06);
    color: rgba(255, 255, 255, 0.7);
    cursor: pointer;
    transition: background 0.15s ease, color 0.15s ease;
}

.uc-card__close:hover {
    background: rgba(255, 255, 255, 0.12);
    color: $white;
}

.uc-card__body {
    padding: 16px 20px 8px !important;
}

.uc-client-pill {
    display: inline-block;
    margin: 0 0 16px;
    padding: 6px 12px;
    font-size: 0.8125rem;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.92);
    background: rgba(74, 222, 128, 0.12);
    border: 1px solid rgba(74, 222, 128, 0.28);
    border-radius: 999px;
}

.uc-form {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.uc-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.uc-label {
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: rgba(255, 255, 255, 0.5);
}

.uc-input {
    width: 100%;
    box-sizing: border-box;
    padding: 11px 14px;
    font-size: 0.9375rem;
    line-height: 1.4;
    color: #0f172a;
    background: #f8fafc;
    border: 1px solid rgba(15, 23, 42, 0.12);
    border-radius: 10px;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.uc-input:hover {
    border-color: rgba(15, 23, 42, 0.2);
}

.uc-input:focus {
    outline: none;
    border-color: rgba(59, 130, 246, 0.65);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
}

.uc-card__actions {
    padding: 16px 20px 20px !important;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    justify-content: flex-end !important;
    gap: 10px !important;
    flex-wrap: wrap;
}

.uc-btn {
    min-height: 42px;
    padding: 0 18px;
    border-radius: 10px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: opacity 0.15s ease, transform 0.12s ease, background 0.15s ease;
}

.uc-btn:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.uc-btn--ghost {
    background: rgba(255, 255, 255, 0.08);
    color: rgba(255, 255, 255, 0.88);
}

.uc-btn--ghost:hover:not(:disabled) {
    background: rgba(255, 255, 255, 0.14);
}

.uc-btn--primary {
    background-color: $green;
    color: $white;
    border: none;
    box-shadow: none;
}

.uc-btn--primary:hover:not(:disabled) {
    background-color: $green;
    filter: brightness(1.08);
}
</style>

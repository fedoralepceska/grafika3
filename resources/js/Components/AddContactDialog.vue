<template>
    <div class="add-contact-wrap">
        <v-dialog
            v-model="dialog"
            max-width="720"
            :scrim="true"
            class="add-contact-dialog-root"
            @keydown.esc="closeDialog"
        >
            <template #activator="{ props: actProps }">
                <button type="button" class="ac-open-btn" v-bind="actProps">
                    {{ $t('newContact') }}
                </button>
            </template>

            <v-card class="ac-card" rounded="xl" elevation="12">
                <div class="ac-card__header">
                    <div class="ac-card__title-block">
                        <h2 class="ac-card__title">{{ $t('addNewContact') }}</h2>
                        <p class="ac-card__hint">{{ $t('addContactModalHint') }}</p>
                    </div>
                    <button
                        type="button"
                        class="ac-card__close"
                        :aria-label="$t('close')"
                        @click="closeDialog"
                    >
                        <i class="fa fa-times" aria-hidden="true"></i>
                    </button>
                </div>

                <v-card-text class="ac-card__body pa-0">
                    <DataTableShell class="ac-table-shell">
                        <template #header>
                            <tr>
                                <th>{{ $t('name') }}</th>
                                <th>{{ $t('email') }}</th>
                                <th>{{ $t('phone') }}</th>
                            </tr>
                        </template>
                        <tr>
                            <td>
                                <input
                                    v-model.trim="newContact.name"
                                    type="text"
                                    class="ac-input"
                                    :placeholder="$t('name')"
                                />
                            </td>
                            <td>
                                <input
                                    v-model.trim="newContact.email"
                                    type="email"
                                    class="ac-input"
                                    :placeholder="$t('email')"
                                />
                            </td>
                            <td>
                                <input
                                    v-model.trim="newContact.phone"
                                    type="text"
                                    class="ac-input"
                                    :placeholder="$t('phone')"
                                />
                            </td>
                        </tr>
                    </DataTableShell>
                </v-card-text>

                <v-card-actions class="ac-card__actions">
                    <button type="button" class="ac-btn ac-btn--danger" :disabled="saving" @click="closeDialog">
                        {{ $t('close') }}
                    </button>
                    <button type="button" class="ac-btn ac-btn--primary" :disabled="saving" @click="saveContact">
                        {{ saving ? '…' : $t('saveContact') }}
                    </button>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script>
import DataTableShell from '@/Components/DataTableShell.vue';
import { useToast } from 'vue-toastification';
import axios from 'axios';

export default {
    components: {
        DataTableShell,
    },
    props: {
        client: Object,
    },
    data() {
        return {
            dialog: false,
            saving: false,
            newContact: {
                name: '',
                phone: '',
                email: '',
            },
        };
    },
    watch: {
        dialog(open) {
            if (open && this.client) {
                this.newContact = { name: '', phone: '', email: '' };
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
                this.closeDialog();
            }
        },
        closeDialog() {
            if (this.saving) return;
            this.dialog = false;
        },
        async saveContact() {
            const toast = useToast();
            if (!this.client?.id) return;
            if (!this.newContact.name || !this.newContact.email || !this.newContact.phone) {
                toast.error(this.$t('addContactValidation'));
                return;
            }
            this.saving = true;
            try {
                await axios.post(`/clients/${this.client.id}/contact`, this.newContact);
                toast.success(this.$t('addContactSuccess'));
                this.dialog = false;
                window.location.reload();
            } catch (error) {
                toast.error(this.$t('addContactError'));
                console.error(error);
            } finally {
                this.saving = false;
            }
        },
    },
};
</script>

<style scoped lang="scss">
.add-contact-wrap {
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.ac-open-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 40px;
    padding: 0 16px;
    border: none;
    border-radius: 10px;
    background-color: $green;
    color: $white;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    cursor: pointer;
    transition: filter 0.15s ease;
}

.ac-open-btn:hover {
    filter: brightness(1.08);
}

.ac-open-btn:focus-visible {
    outline: 2px solid rgba(96, 165, 250, 0.9);
    outline-offset: 2px;
}

.ac-card {
    background-color: $light-gray !important;
    border: 1px solid rgba(0, 0, 0, 0.12);
    color: $white;
    overflow: hidden;
}

.ac-card__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    padding: 20px 20px 12px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.12);
}

.ac-card__title {
    margin: 0;
    font-size: 1.15rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    line-height: 1.25;
}

.ac-card__hint {
    margin: 6px 0 0;
    font-size: 0.8125rem;
    line-height: 1.45;
    color: rgba(255, 255, 255, 0.65);
    max-width: 560px;
}

.ac-card__close {
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

.ac-card__close:hover {
    background: rgba(255, 255, 255, 0.14);
    color: $white;
}

.ac-card__body {
    padding: 16px 20px !important;
}

.ac-table-shell :deep(.data-table-body td) {
    vertical-align: middle;
}

.ac-table-shell :deep(.data-table-head th) {
    white-space: nowrap;
}

.ac-input {
    width: 100%;
    box-sizing: border-box;
    min-width: 0;
    padding: 10px 12px;
    font-size: 0.9375rem;
    line-height: 1.35;
    color: #0f172a;
    background: #f8fafc;
    border: 1px solid rgba(15, 23, 42, 0.12);
    border-radius: 8px;
}

.ac-input:focus {
    outline: none;
    border-color: rgba(59, 130, 246, 0.65);
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}

.ac-card__actions {
    padding: 16px 20px 20px !important;
    border-top: 1px solid rgba(255, 255, 255, 0.12);
    justify-content: flex-end !important;
    gap: 10px !important;
    flex-wrap: wrap;
}

.ac-btn {
    min-height: 42px;
    padding: 0 18px;
    border-radius: 10px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: opacity 0.15s ease, filter 0.15s ease;
}

.ac-btn:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.ac-btn--danger {
    background-color: $red;
    color: $white;
}

.ac-btn--danger:hover:not(:disabled) {
    filter: brightness(1.08);
}

.ac-btn--primary {
    background-color: $green;
    color: $white;
}

.ac-btn--primary:hover:not(:disabled) {
    filter: brightness(1.08);
}
</style>

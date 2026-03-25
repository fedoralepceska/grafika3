<template>
    <div class="view-contacts-wrap">
        <v-dialog
            v-model="dialog"
            max-width="960"
            :scrim="true"
            class="vc-dialog-root"
            @keydown.esc="closeDialog"
        >
            <template #activator="{ props: actProps }">
                <button type="button" class="vc-trigger" v-bind="actProps" :aria-label="$t('viewContacts')">
                    {{ $t('viewContacts') }}
                </button>
            </template>

            <v-card class="vc-card" rounded="xl" elevation="12">
                <div class="vc-card__header">
                    <div class="vc-card__title-block">
                        <h2 class="vc-card__title">{{ $t('contactsFor') }} {{ client?.name }}</h2>
                        <p class="vc-card__hint">{{ $t('viewContactsModalHint') }}</p>
                    </div>
                    <button
                        type="button"
                        class="vc-card__close"
                        :aria-label="$t('close')"
                        @click="closeDialog"
                    >
                        <i class="fa fa-times" aria-hidden="true"></i>
                    </button>
                </div>

                <v-card-text class="vc-card__body pa-0">
                    <DataTableShell class="vc-table-shell">
                        <template #header>
                            <tr>
                                <th class="vc-col-num">#</th>
                                <th>{{ $t('name') }}</th>
                                <th>{{ $t('email') }}</th>
                                <th>{{ $t('phone') }}</th>
                                <th class="vc-actions-header">{{ $t('delete') }}</th>
                            </tr>
                        </template>
                        <tr v-if="!contactRows.length" class="vc-row-empty">
                            <td colspan="5" class="vc-empty-cell">
                                {{ $t('noContactsYet') }}
                            </td>
                        </tr>
                        <tr v-for="(contact, index) in contactRows" :key="contact.id ?? index">
                            <td class="vc-col-num">{{ index + 1 }}</td>
                            <td>{{ contact.name }}</td>
                            <td>{{ contact.email }}</td>
                            <td>{{ contact.phone }}</td>
                            <td class="vc-actions-cell">
                                <button
                                    type="button"
                                    class="vc-delete-btn"
                                    :aria-label="$t('delete')"
                                    @click="deleteContact(client, contact)"
                                >
                                    <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                                </button>
                            </td>
                        </tr>
                    </DataTableShell>
                </v-card-text>

                <v-card-actions class="vc-card__actions">
                    <AddContactDialog :client="client" />
                    <div class="vc-spacer" />
                    <button type="button" class="vc-btn vc-btn--danger" @click="closeDialog">
                        {{ $t('close') }}
                    </button>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script>
import DataTableShell from '@/Components/DataTableShell.vue';
import AddContactDialog from '@/Components/AddContactDialog.vue';
import { useToast } from 'vue-toastification';
import axios from 'axios';

export default {
    components: {
        AddContactDialog,
        DataTableShell,
    },
    props: {
        client: Object,
    },
    data() {
        return {
            dialog: false,
        };
    },
    computed: {
        contactRows() {
            return this.client?.contacts && Array.isArray(this.client.contacts) ? this.client.contacts : [];
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
            this.dialog = false;
        },
        async deleteContact(client, contact) {
            const toast = useToast();
            try {
                await axios.delete(`/clients/${client.id}/contacts/${contact.id}`);
                const contactIndex = client.contacts.findIndex((c) => c.id === contact.id);
                if (contactIndex !== -1) {
                    client.contacts.splice(contactIndex, 1);
                }
                toast.success(this.$t('contactDeletedSuccess'));
            } catch (error) {
                console.error('Error deleting contact:', error);
                toast.error(this.$t('contactDeletedError'));
            }
        },
    },
};
</script>

<style scoped lang="scss">
.view-contacts-wrap {
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.vc-trigger {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 40px;
    padding: 0 16px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.92);
    color: #0f172a;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    cursor: pointer;
    transition: background 0.15s ease, filter 0.15s ease;
}

.vc-trigger:hover {
    filter: brightness(0.96);
}

.vc-trigger:focus-visible {
    outline: 2px solid rgba(96, 165, 250, 0.9);
    outline-offset: 2px;
}

.vc-card {
    background-color: $light-gray !important;
    border: 1px solid rgba(0, 0, 0, 0.12);
    color: $white;
    overflow: hidden;
}

.vc-card__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    padding: 20px 20px 12px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.12);
}

.vc-card__title {
    margin: 0;
    font-size: 1.15rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    line-height: 1.25;
}

.vc-card__hint {
    margin: 6px 0 0;
    font-size: 0.8125rem;
    line-height: 1.45;
    color: rgba(255, 255, 255, 0.65);
    max-width: 640px;
}

.vc-card__close {
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

.vc-card__close:hover {
    background: rgba(255, 255, 255, 0.14);
    color: $white;
}

.vc-card__body {
    padding: 16px 20px !important;
    max-height: min(65vh, 520px);
    overflow-y: auto;
}

.vc-table-shell :deep(th.vc-actions-header),
.vc-table-shell :deep(td.vc-actions-cell) {
    text-align: center;
    width: 4.5rem;
}

.vc-table-shell :deep(th.vc-col-num),
.vc-table-shell :deep(td.vc-col-num) {
    width: 3rem;
    text-align: center;
    font-variant-numeric: tabular-nums;
}

.vc-row-empty .vc-empty-cell {
    text-align: center;
    color: rgba(255, 255, 255, 0.55);
    font-size: 0.9375rem;
    padding: 24px 16px !important;
}

.vc-delete-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    padding: 0;
    border: none;
    border-radius: 8px;
    background: rgba(220, 38, 38, 0.15);
    color: #fecaca;
    cursor: pointer;
    transition: background 0.15s ease, color 0.15s ease;
}

.vc-delete-btn:hover {
    background: rgba(220, 38, 38, 0.35);
    color: $white;
}

.vc-card__actions {
    padding: 16px 20px 20px !important;
    border-top: 1px solid rgba(255, 255, 255, 0.12);
    align-items: center !important;
    flex-wrap: wrap;
    gap: 10px !important;
}

.vc-card__actions :deep(.add-contact-wrap) {
    margin: 0;
}

.vc-spacer {
    flex: 1;
    min-width: 8px;
}

.vc-btn {
    min-height: 42px;
    padding: 0 18px;
    border-radius: 10px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: filter 0.15s ease;
}

.vc-btn--danger {
    background-color: $red;
    color: $white;
}

.vc-btn--danger:hover {
    filter: brightness(1.08);
}
</style>

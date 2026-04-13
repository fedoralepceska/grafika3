<template>
    <v-dialog
        v-model="dialog"
        persistent
        max-width="960"
        class="bank-dlg-root bank-dlg-root--wide"
        @keydown.esc="closeDialog"
    >
        <template #activator="{ props: activatorProps }">
            <div v-bind="activatorProps" class="bank-dlg-activator">
                <button type="button" class="bank-dlg-toolbar-btn">
                    <span>View Banks</span>
                </button>
            </div>
        </template>

        <v-card class="bank-dlg-card bank-dlg-card--wide">
            <div class="bank-dlg-card__head">
                <h2 id="view-banks-title" class="bank-dlg-card__title">Banks</h2>
                <button type="button" class="bank-dlg-card__close" aria-label="Close" @click="closeDialog">
                    &times;
                </button>
            </div>

            <v-card-text class="bank-dlg-card__body bank-dlg-card__body--table">
                <div class="bank-dlg-table-wrap">
                    <table class="bank-dlg-table">
                        <thead>
                            <tr>
                                <th scope="col">{{ $t('Nr') }}</th>
                                <th scope="col">Name</th>
                                <th scope="col">Account</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(bank, index) in banks" :key="bank.id ?? index">
                                <td>{{ index + 1 }}</td>
                                <td>
                                    <input
                                        v-if="editMode && editingBank?.id === bank.id"
                                        v-model="editingBank.name"
                                        type="text"
                                        class="bank-dlg-table-input"
                                    />
                                    <span v-else>{{ bank.name }}</span>
                                </td>
                                <td>
                                    <input
                                        v-if="editMode && editingBank?.id === bank.id"
                                        v-model="editingBank.address"
                                        type="text"
                                        class="bank-dlg-table-input"
                                    />
                                    <span v-else>{{ bank.address }}</span>
                                </td>
                                <td class="bank-dlg-actions">
                                    <template v-if="editMode && editingBank?.id === bank.id">
                                        <button type="button" class="bank-dlg-mini bank-dlg-mini--save" @click="saveChanges">
                                            Save
                                        </button>
                                        <button type="button" class="bank-dlg-mini bank-dlg-mini--close" @click="cancelEdit">
                                            Cancel
                                        </button>
                                    </template>
                                    <template v-else>
                                        <button type="button" class="bank-dlg-mini bank-dlg-mini--edit" @click="startEdit(bank)">
                                            Edit
                                        </button>
                                        <button
                                            type="button"
                                            class="bank-dlg-mini bank-dlg-mini--danger"
                                            :disabled="!bank.id"
                                            @click="deleteBank(bank)"
                                        >
                                            Delete
                                        </button>
                                    </template>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </v-card-text>

            <div class="bank-dlg-card__actions">
                <button type="button" class="bank-dlg-footer-btn bank-dlg-footer-btn--close" @click="closeDialog">
                    Close
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
            banks: [],
            editMode: false,
            editingBank: null,
        };
    },
    watch: {
        dialog(open) {
            if (open) {
                this.fetchBanks();
            }
            this.cancelEdit();
        },
    },
    methods: {
        fetchBanks() {
            axios
                .get('/api/banks')
                .then((response) => {
                    this.banks = response.data;
                })
                .catch((error) => {
                    console.error('Error fetching banks:', error);
                });
        },
        deleteBank(bank) {
            const toast = useToast();
            axios
                .delete(`/api/banks/${bank.id}`)
                .then(() => {
                    this.banks = this.banks.filter((b) => b.id !== bank.id);
                    toast.success('Bank deleted successfully!');
                })
                .catch((error) => {
                    console.error('Error deleting bank:', error);
                    toast.error('Failed to delete bank!');
                });
        },
        closeDialog() {
            this.dialog = false;
            this.cancelEdit();
        },
        startEdit(bank) {
            this.editMode = true;
            this.editingBank = { ...bank };
        },
        cancelEdit() {
            this.editMode = false;
            this.editingBank = null;
        },
        saveChanges() {
            const toast = useToast();
            axios
                .put(`/api/banks/${this.editingBank.id}`, {
                    name: this.editingBank.name,
                    address: this.editingBank.address,
                })
                .then((response) => {
                    const index = this.banks.findIndex((b) => b.id === this.editingBank.id);
                    if (index !== -1) {
                        this.banks[index] = response.data.bank;
                    }
                    this.cancelEdit();
                    toast.success('Bank updated successfully!');
                })
                .catch((error) => {
                    console.error('Error updating bank:', error);
                    toast.error('Failed to update bank!');
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

.bank-dlg-card {
    border-radius: 12px !important;
    overflow: hidden;
    background: #1a2332 !important;
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.45) !important;
}

.bank-dlg-card--wide {
    max-height: min(90vh, 720px);
    display: flex;
    flex-direction: column;
}

.bank-dlg-card--wide :deep(.v-card-text) {
    flex: 1 1 auto;
    min-height: 0;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.bank-dlg-card__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    padding: 16px 18px 12px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    flex-shrink: 0;
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

.bank-dlg-card__body--table {
    padding: 12px 16px 8px !important;
    flex: 1 1 auto;
    min-height: 0;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.bank-dlg-table-wrap {
    flex: 1 1 auto;
    min-height: 0;
    overflow: auto;
    border: 1px solid rgba(255, 255, 255, 0.18);
    border-radius: 12px;
    background: rgba(0, 0, 0, 0.2);
}

.bank-dlg-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    color: rgba(255, 255, 255, 0.92);
    font-size: 13px;
}

.bank-dlg-table thead th {
    position: sticky;
    top: 0;
    z-index: 1;
    padding: 10px 12px;
    text-align: center;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: rgba(255, 255, 255, 0.65);
    background: rgba(30, 38, 52, 0.98);
    border-bottom: 1px solid rgba(255, 255, 255, 0.12);
}

.bank-dlg-table tbody td {
    padding: 8px 12px;
    text-align: center;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    vertical-align: middle;
}

.bank-dlg-table tbody tr:last-child td {
    border-bottom: none;
}

.bank-dlg-table tbody tr:hover td {
    background: rgba(255, 255, 255, 0.03);
}

.bank-dlg-table-input {
    width: 100%;
    min-width: 0;
    max-width: 100%;
    padding: 6px 10px;
    border: 1px solid rgba(0, 0, 0, 0.14);
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.98);
    color: #111827;
    font-size: 13px;
    box-sizing: border-box;
}

.bank-dlg-table-input:focus {
    outline: none;
    border-color: rgba(59, 130, 246, 0.75);
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}

.bank-dlg-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    justify-content: center;
    align-items: center;
}

.bank-dlg-mini {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 30px;
    padding: 0 12px;
    border: none;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    cursor: pointer;
    transition: filter 0.15s ease, opacity 0.15s ease, transform 0.08s ease;
}

.bank-dlg-mini:active:not(:disabled) {
    transform: scale(0.98);
}

.bank-dlg-mini:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.bank-dlg-mini--edit {
    background: $blue;
    color: $white;
}

.bank-dlg-mini--edit:hover:not(:disabled) {
    filter: brightness(1.08);
}

.bank-dlg-mini--save {
    background: #1b5e20;
    color: $white;
}

.bank-dlg-mini--save:hover:not(:disabled) {
    filter: brightness(1.08);
}

.bank-dlg-mini--close {
    background: rgba(255, 255, 255, 0.12);
    color: rgba(255, 255, 255, 0.92);
    border: 1px solid rgba(255, 255, 255, 0.18);
}

.bank-dlg-mini--close:hover:not(:disabled) {
    background: rgba(255, 255, 255, 0.18);
}

.bank-dlg-mini--danger {
    background: #b71c1c;
    color: $white;
}

.bank-dlg-mini--danger:hover:not(:disabled) {
    filter: brightness(1.08);
}

.bank-dlg-card__actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 10px;
    padding: 12px 18px 16px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    flex-shrink: 0;
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
    transition: filter 0.15s ease, transform 0.08s ease;
}

.bank-dlg-footer-btn:active:not(:disabled) {
    transform: scale(0.98);
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
</style>

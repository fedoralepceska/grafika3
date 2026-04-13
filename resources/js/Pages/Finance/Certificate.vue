<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <div class="page-shell">
                <div class="flex justify-between">
                    <Header title="statement" subtitle="currentReport" icon="invoice.png" link="statements"/>
                </div>

                <div class="dark-gray p-5 text-white">
                    <div class="statement-hero">
                        <div class="statement-title-block">
                            <div class="statement-kicker">Bank Statement</div>
                            <h1 class="statement-title">Statement #{{ certificate.id_per_bank }}/{{ certificate.fiscal_year }}</h1>
                            <p class="statement-subtitle">{{ formatDateDisplay(certificate.date) }} · {{ certificate.bank }}</p>
                        </div>

                        <div class="page-actions">
                            <button class="toolbar-button" :class="{ active: isEditMode }" @click="toggleEditMode">
                                {{ isEditMode ? 'Exit Item Edit' : 'Edit Items' }}
                            </button>
                            <button
                                class="toolbar-button"
                                :class="{ active: isStatementEditMode, success: isStatementEditMode && hasStatementChanges }"
                                @click="toggleStatementEditMode"
                            >
                                {{ isStatementEditMode ? (hasStatementChanges ? 'Save Statement' : 'Exit Statement Edit') : 'Edit Statement' }}
                            </button>
                            <AddCertificateDialog />
                            <AddItemDialog :certificate="certificate" />
                        </div>
                    </div>

                    <div class="summary-grid">
                        <div class="summary-card summary-card--wide">
                            <div class="summary-card-head">
                                <span class="summary-label">Statement Overview</span>
                                <span class="summary-chip">#{{ certificate.id_per_bank }}</span>
                            </div>

                            <div class="detail-grid">
                                <div class="detail-field">
                                    <span class="detail-label">Bank</span>
                                    <div v-if="isStatementEditMode" class="bank-picker relative">
                                        <input
                                            type="text"
                                            v-model="bankSearch"
                                            @focus="showBankDropdown = true"
                                            @input="filterBanks"
                                            :placeholder="certificate.bank"
                                            class="edit-input"
                                        />
                                        <div v-if="showBankDropdown" class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto">
                                            <div
                                                v-for="bank in filteredBanks"
                                                :key="bank.id"
                                                @click="selectBank(bank)"
                                                class="px-4 py-2 hover:bg-gray-100 cursor-pointer text-gray-700"
                                            >
                                                {{ bank.name }}
                                            </div>
                                            <div v-if="!filteredBanks.length" class="px-4 py-2 text-gray-500">
                                                No banks found
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="detail-value">{{ certificate.bank }}</div>
                                </div>

                                <div class="detail-field">
                                    <span class="detail-label">Bank Account</span>
                                    <div class="detail-value">{{ isStatementEditMode ? editedCertificate.bankAccount : certificate.bankAccount }}</div>
                                </div>

                                <div class="detail-field">
                                    <span class="detail-label">Date</span>
                                    <div v-if="isStatementEditMode" class="cert-date-edit-wrap">
                                        <input
                                            v-model="statementDateDisplay"
                                            type="text"
                                            class="edit-input cert-date-edit-text"
                                            placeholder="dd/mm/yyyy"
                                            inputmode="numeric"
                                            autocomplete="off"
                                            title="Click to open calendar or type dd/mm/yyyy"
                                            @click="openStatementNativeDatePicker"
                                            @blur="commitStatementDateDisplay"
                                        />
                                        <input
                                            ref="nativeStatementDateInput"
                                            type="date"
                                            class="cert-date-edit-anchor"
                                            :value="editedCertificate.date || ''"
                                            tabindex="-1"
                                            aria-hidden="true"
                                            @change="onStatementNativeDatePicked"
                                        />
                                    </div>
                                    <div v-else class="detail-value">{{ formatDateDisplay(certificate.date) }}</div>
                                </div>

                                <div class="detail-field">
                                    <span class="detail-label">Created By</span>
                                    <div class="detail-value">{{ certificate.created_by?.name || 'N/A' }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="summary-card">
                            <span class="summary-label">Total Income</span>
                            <div class="summary-value success-text">{{ formatNumber(totalIncome) }}</div>
                            <div class="summary-meta">Incoming amount on this statement</div>
                        </div>

                        <div class="summary-card">
                            <span class="summary-label">Total Expense</span>
                            <div class="summary-value danger-text">{{ formatNumber(totalExpense) }}</div>
                            <div class="summary-meta">Outgoing amount on this statement</div>
                        </div>

                        <div class="summary-card">
                            <span class="summary-label">Net Balance</span>
                            <div class="summary-value" :class="netBalance >= 0 ? 'success-text' : 'danger-text'">
                                {{ formatSignedBalance(netBalance) }}
                            </div>
                            <div class="summary-meta">{{ items.length }} transaction{{ items.length === 1 ? '' : 's' }}</div>
                        </div>
                    </div>

                    <DataTableShell compact variant="grid" class="statement-table">
                        <template #header>
                            <tr>
                                <th class="number-column">Nr</th>
                                <th class="client-column">Client</th>
                                <th class="text-right">Expense</th>
                                <th class="text-right">Income</th>
                                <th>Code</th>
                                <th>Reference</th>
                                <th>Comment</th>
                                <th v-if="isEditMode" class="actions-header actions-column">Actions</th>
                            </tr>
                        </template>

                        <template v-if="items.length">
                            <tr v-for="(item, index) in items" :key="item.id">
                                <td class="number-cell">
                                    <div class="cell-primary">#{{ index + 1 }}</div>
                                    <div class="cell-secondary">ID {{ item.id }}</div>
                                </td>
                                <td class="client-cell">
                                    <div v-if="isEditMode" class="client-picker relative">
                                        <input
                                            type="text"
                                            v-model="clientSearch[item.id]"
                                            @focus="initializeClientDropdown(item.id)"
                                            @input="filterClients(item.id)"
                                            :placeholder="getClientName(item)"
                                            class="edit-input"
                                        />
                                        <div v-if="showClientDropdown[item.id]" class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto">
                                            <div
                                                v-for="client in filteredClients[item.id] || []"
                                                :key="client.id"
                                                @click="selectClient(client, item)"
                                                class="px-4 py-2 hover:bg-gray-100 cursor-pointer text-gray-700"
                                                :class="{ 'bg-gray-200': item.client_id === client.id }"
                                            >
                                                {{ client.name }}
                                            </div>
                                            <div v-if="!filteredClients[item.id]?.length" class="px-4 py-2 text-gray-500">
                                                No clients found
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="cell-primary">{{ getClientName(item) || 'N/A' }}</div>
                                </td>
                                <td class="text-right amount-cell">
                                    <input
                                        v-if="isEditMode"
                                        type="number"
                                        v-model="item.expense"
                                        class="edit-input text-right"
                                        @input="markAsModified(item)"
                                    >
                                    <span v-else class="cell-secondary">{{ formatNumber(item.expense) }}</span>
                                </td>
                                <td class="text-right amount-cell">
                                    <input
                                        v-if="isEditMode"
                                        type="number"
                                        v-model="item.income"
                                        class="edit-input text-right"
                                        @input="markAsModified(item)"
                                    >
                                    <span v-else class="cell-secondary">{{ formatNumber(item.income) }}</span>
                                </td>
                                <td>
                                    <input
                                        v-if="isEditMode"
                                        type="text"
                                        v-model="item.code"
                                        class="edit-input"
                                        @input="markAsModified(item)"
                                    >
                                    <span v-else class="cell-secondary">{{ item.code || '—' }}</span>
                                </td>
                                <td>
                                    <input
                                        v-if="isEditMode"
                                        type="text"
                                        v-model="item.reference_to"
                                        class="edit-input"
                                        @input="markAsModified(item)"
                                    >
                                    <span v-else class="cell-secondary">{{ item.reference_to || '—' }}</span>
                                </td>
                                <td class="comment-cell">
                                    <input
                                        v-if="isEditMode"
                                        type="text"
                                        v-model="item.comment"
                                        class="edit-input"
                                        @input="markAsModified(item)"
                                    >
                                    <span v-else class="cell-secondary">{{ item.comment || '—' }}</span>
                                </td>
                                <td v-if="isEditMode" class="actions-cell">
                                    <button class="delete-action-button" @click="deleteItem(item.id)">
                                        <span class="mdi mdi-close"></span>
                                    </button>
                                </td>
                            </tr>
                        </template>

                        <tr v-else>
                            <td :colspan="isEditMode ? 8 : 7" class="empty-cell">
                                No statement transactions added yet.
                            </td>
                        </tr>

                        <template #footer>
                            <tr>
                                <td colspan="2">Totals</td>
                                <td class="text-right">{{ formatNumber(totalExpense) }}</td>
                                <td class="text-right">{{ formatNumber(totalIncome) }}</td>
                                <td :colspan="isEditMode ? 4 : 3" class="footer-balance">
                                    Net {{ formatSignedBalance(netBalance) }}
                                </td>
                            </tr>
                        </template>
                    </DataTableShell>

                    <div v-if="isEditMode && hasModifiedItems" class="table-actions-bar">
                        <button class="toolbar-button success" @click="saveChanges">
                            Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script>
import MainLayout from "@/Layouts/MainLayout.vue";
import axios from "axios";
import { formatDateDdMmYyyy, parseDdMmYyyyToIso } from "@/utils/financeFilters";
import { useToast } from "vue-toastification";
import Header from "@/Components/Header.vue";
import AddItemDialog from "@/Components/AddItemDialog.vue";
import AddCertificateDialog from "@/Components/AddCertificateDialog.vue";
import DataTableShell from "@/Components/DataTableShell.vue";

export default {
    components: {
        DataTableShell,
        AddItemDialog,
        AddCertificateDialog,
        MainLayout,
        Header
    },
    props: {
        certificate: Object,
    },
    computed: {
        hasModifiedItems() {
            return this.modifiedItems.size > 0;
        },
        totalExpense() {
            return this.items.reduce((sum, item) => {
                return sum + (Number(item.expense) || 0);
            }, 0);
        },
        totalIncome() {
            return this.items.reduce((sum, item) => {
                return sum + (Number(item.income) || 0);
            }, 0);
        },
        netBalance() {
            return this.totalIncome - this.totalExpense;
        }
    },
    data() {
        return {
            isEditMode: false,
            modifiedItems: new Set(),
            item: {
                client_id: null,
                certificate_id: this.certificate.id,
                income: 0,
                expense: 0,
                code: '',
                reference_to: '',
                comment: ''
            },
            certificate2: {
                date: null,
                bank: '',
                bankAccount: '',
            },
            items: [],
            clients: [],
            clientSearch: {},
            showClientDropdown: {},
            filteredClients: {},
            isStatementEditMode: false,
            hasStatementChanges: false,
            editedCertificate: {},
            banks: [],
            bankSearch: '',
            showBankDropdown: false,
            filteredBanks: [],
            /** Statement edit: visible dd/mm/yyyy; editedCertificate.date stays yyyy-mm-dd for API */
            statementDateDisplay: '',
        }
    },
    async beforeMount() {
        const response = await axios.get(`/items/${this.item.certificate_id}`);
        this.items = response.data;
        await this.fetchClientsWithStatements();
    },
    methods: {
        addItem() {
            const toast = useToast();
            axios.post('/item', {
                client_id: this.item.client_id,
                certificate_id: this.item.certificate_id,
                income: this.item.income,
                expense: this.item.expense,
                code: this.item.code,
                reference_to: this.item.reference_to,
                comment: this.item.comment
            })
                .then((response) => {
                    toast.success("Item added successfully.");
                })
                .catch((error) => {
                    toast.error("Error adding item!");
                });
        },

        addCertificate() {
            const toast = useToast();
            axios.post('/certificate', {
                date: this.certificate.date,
                bank: this.certificate.bank,
                bankAccount: this.certificate.bankAccount,
            })
                .then((response) => {
                    toast.success("Certificate added successfully.");
                })
                .catch((error) => {
                    toast.error("Error adding certificate!");
                });
        },

        toggleEditMode() {
            if (this.isEditMode && this.hasModifiedItems) {
                this.saveChanges();
            } else {
                this.isEditMode = !this.isEditMode;
                if (!this.isEditMode) {
                    this.refreshItems();
                }
            }
        },
        async refreshItems() {
            try {
                const response = await axios.get(`/items/${this.certificate.id}`);
                this.items = response.data;
            } catch (error) {
                console.error('Error refreshing items:', error);
                const toast = useToast();
                toast.error("Error refreshing items");
            }
        },
        markAsModified(item) {
            this.modifiedItems.add(item.id);
        },
        async deleteItem(itemId) {
            const toast = useToast();
            try {
                await axios.delete(`/items/${itemId}`);
                const response = await axios.get(`/items/${this.certificate.id}`);
                this.items = response.data;
                toast.success("Item deleted successfully");
            } catch (error) {
                toast.error("Error deleting item");
            }
        },
        async saveChanges() {
            const toast = useToast();
            try {
                const modifiedItemsList = this.items.filter(item => this.modifiedItems.has(item.id));
                await Promise.all(modifiedItemsList.map(item =>
                    axios.put(`/items/${item.id}`, {
                        income: item.income,
                        expense: item.expense,
                        code: item.code,
                        reference_to: item.reference_to,
                        comment: item.comment,
                        client_id: item.client_id
                    })
                ));

                const response = await axios.get(`/items/${this.certificate.id}`);
                this.items = response.data;

                toast.success("Changes saved successfully");
                this.modifiedItems.clear();
                this.isEditMode = false;
            } catch (error) {
                console.error('Error saving changes:', error);
                toast.error("Error saving changes: " + (error.response?.data?.message || error.message));
            }
        },
        async fetchClientsWithStatements() {
            try {
                const response = await axios.get('/clients-with-statements');
                this.clients = response.data;
            } catch (error) {
                console.error("Failed to fetch clients:", error);
                const toast = useToast();
                toast.error('Error fetching clients');
            }
        },
        initializeClientDropdown(itemId) {
            this.showClientDropdown[itemId] = true;
            this.filteredClients[itemId] = [...this.clients];
        },
        filterClients(itemId) {
            if (!this.clientSearch[itemId]) {
                this.filteredClients[itemId] = [...this.clients];
                return;
            }

            this.filteredClients[itemId] = this.clients.filter(client =>
                client.name.toLowerCase().includes(this.clientSearch[itemId].toLowerCase())
            );
        },
        selectClient(client, item) {
            item.client_id = client.id;
            item.client = client;
            this.clientSearch[item.id] = client.name;
            this.showClientDropdown[item.id] = false;
            this.markAsModified(item);
        },
        getClientName(item) {
            return item.client?.name || '';
        },
        async fetchBanks() {
            try {
                const response = await axios.get('/banks-list');
                this.banks = response.data;
                this.filteredBanks = [...this.banks];
            } catch (error) {
                console.error("Failed to fetch banks:", error);
                const toast = useToast();
                toast.error('Error fetching banks');
            }
        },
        filterBanks() {
            if (!this.bankSearch) {
                this.filteredBanks = [...this.banks];
                return;
            }

            this.filteredBanks = this.banks.filter(bank =>
                bank.name.toLowerCase().includes(this.bankSearch.toLowerCase())
            );
        },
        selectBank(bank) {
            this.editedCertificate.bank = bank.name;
            this.editedCertificate.bankAccount = bank.bankAccount;
            this.bankSearch = bank.name;
            this.showBankDropdown = false;
            this.markStatementAsModified();
        },
        toggleStatementEditMode() {
            if (this.isStatementEditMode && this.hasStatementChanges) {
                this.saveStatementChanges();
            } else if (this.isStatementEditMode) {
                this.isStatementEditMode = false;
                this.hasStatementChanges = false;
            } else {
                this.isStatementEditMode = true;
                this.editedCertificate = { ...this.certificate };
                this.bankSearch = this.certificate.bank;
                this.syncStatementDateDisplayFromEdited();
            }
        },
        syncStatementDateDisplayFromEdited() {
            if (!this.editedCertificate?.date) {
                this.statementDateDisplay = '';
                return;
            }
            const formatted = formatDateDdMmYyyy(this.editedCertificate.date);
            this.statementDateDisplay = formatted === 'N/A' ? '' : formatted;
        },
        commitStatementDateDisplay() {
            const raw = (this.statementDateDisplay || '').trim();
            if (!raw) {
                this.editedCertificate.date = null;
                return true;
            }
            const iso = parseDdMmYyyyToIso(raw);
            if (!iso) {
                const toast = useToast();
                toast.error('Use date as dd/mm/yyyy (e.g. 14/01/2026).');
                this.syncStatementDateDisplayFromEdited();
                return false;
            }
            this.editedCertificate.date = iso;
            this.statementDateDisplay = formatDateDdMmYyyy(iso);
            return true;
        },
        onStatementNativeDatePicked(event) {
            const v = event.target.value;
            if (!v) {
                return;
            }
            this.editedCertificate.date = v;
            this.statementDateDisplay = formatDateDdMmYyyy(v);
            this.markStatementAsModified();
        },
        openStatementNativeDatePicker() {
            const el = this.$refs.nativeStatementDateInput;
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
        markStatementAsModified() {
            this.hasStatementChanges = true;
        },
        async saveStatementChanges() {
            const toast = useToast();
            if (!this.commitStatementDateDisplay()) {
                return;
            }
            if (!this.editedCertificate.date) {
                toast.error('Please enter a date as dd/mm/yyyy.');
                return;
            }
            try {
                const response = await axios.put(`/certificate/${this.certificate.id}`, {
                    date: this.editedCertificate.date,
                    bank: this.editedCertificate.bank,
                    bankAccount: this.editedCertificate.bankAccount,
                });

                Object.assign(this.certificate, response.data.certificate);

                toast.success("Statement updated successfully");
                this.isStatementEditMode = false;
                this.hasStatementChanges = false;
            } catch (error) {
                console.error('Error saving statement changes:', error);
                toast.error("Error saving statement changes: " + (error.response?.data?.message || error.message));
            }
        },
        formatDateDisplay(value) {
            return formatDateDdMmYyyy(value);
        },
        formatNumber(value) {
            return Number(value).toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        },
        formatSignedBalance(value) {
            if (!value) return this.formatNumber(0);

            const prefix = value > 0 ? '+' : '-';
            return `${prefix}${this.formatNumber(Math.abs(value))}`;
        }
    },
    async mounted() {
        this.handleClickOutside = (event) => {
            if (!event.target.closest('.client-picker')) {
                this.showClientDropdown = {};
            }

            if (!event.target.closest('.bank-picker')) {
                this.showBankDropdown = false;
            }
        };

        document.addEventListener('click', this.handleClickOutside);
        await this.fetchBanks();
    },
    beforeUnmount() {
        if (this.handleClickOutside) {
            document.removeEventListener('click', this.handleClickOutside);
        }
    },
};
</script>

<style scoped lang="scss">
.page-shell {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.statement-hero {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 20px;
}

.statement-title-block {
    min-width: 0;
}

.statement-kicker {
    margin-bottom: 8px;
    color: rgba(255, 255, 255, 0.62);
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}

.statement-title {
    margin: 0;
    font-size: 28px;
    font-weight: 800;
    line-height: 1.15;
    color: $white;
}

.statement-subtitle {
    margin: 8px 0 0;
    color: rgba(255, 255, 255, 0.68);
    font-size: 14px;
}

.page-actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 10px;
}

.page-actions :deep(.add-statement-activator),
.page-actions :deep(.add-item-activator) {
    font-size: inherit;
    padding: 0;
    flex: 0 0 auto;
}

.page-actions :deep(.add-statement-btn),
.page-actions :deep(.add-item-btn) {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 36px;
    padding: 8px 12px;
    margin-top: 0;
    border: 1px solid rgba(96, 165, 250, 0.38);
    border-radius: 8px;
    background: $blue;
    color: $white;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
    box-shadow: none;
    transition: all 0.2s ease;
}

.page-actions :deep(.add-statement-btn:hover),
.page-actions :deep(.add-item-btn:hover) {
    background: rgba(59, 130, 246, 0.92);
    border-color: rgba(96, 165, 250, 0.85);
}

.toolbar-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 36px;
    padding: 8px 12px;
    border: 1px solid rgba(255, 255, 255, 0.14);
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.06);
    color: $white;
    font-size: 12px;
    font-weight: 700;
    transition: all 0.2s ease;
}

.toolbar-button:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.24);
}

.toolbar-button.active {
    border-color: rgba(96, 165, 250, 0.38);
    background: rgba(59, 130, 246, 0.16);
}

.toolbar-button.success {
    border-color: rgba(74, 222, 128, 0.32);
    background: rgba(74, 222, 128, 0.12);
}

.toolbar-button.success:hover {
    background: rgba(74, 222, 128, 0.18);
    border-color: rgba(74, 222, 128, 0.44);
}

.summary-grid {
    display: grid;
    grid-template-columns: 1.5fr repeat(3, minmax(180px, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}

.summary-card {
    padding: 18px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.04);
}

.summary-card--wide {
    min-width: 0;
}

.summary-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 16px;
}

.summary-label {
    display: block;
    color: rgba(255, 255, 255, 0.64);
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.summary-chip {
    padding: 6px 10px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.08);
    color: rgba(255, 255, 255, 0.82);
    font-size: 11px;
    font-weight: 700;
}

.summary-value {
    margin-top: 10px;
    font-size: 26px;
    font-weight: 800;
    color: $white;
}

.summary-meta {
    margin-top: 8px;
    font-size: 12px;
    color: rgba(255, 255, 255, 0.6);
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(180px, 1fr));
    gap: 14px 18px;
}

.detail-field {
    min-width: 0;
}

.detail-label {
    display: block;
    margin-bottom: 6px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: rgba(255, 255, 255, 0.58);
}

.detail-value {
    color: $white;
    font-size: 14px;
    font-weight: 700;
}

.success-text {
    color: #4ade80;
}

.danger-text {
    color: #f87171;
}

.number-column {
    width: 92px;
}

.client-column {
    min-width: 220px;
}

.actions-column {
    width: 90px;
}

.cell-primary {
    font-weight: 700;
    color: $white;
}

.cell-secondary {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.68);
}

.actions-cell {
    text-align: right;
}

.delete-action-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border: 1px solid rgba(248, 113, 113, 0.28);
    border-radius: 8px;
    background: rgba(248, 113, 113, 0.1);
    color: #fca5a5;
    transition: all 0.2s ease;
}

.delete-action-button:hover {
    background: rgba(248, 113, 113, 0.18);
    border-color: rgba(248, 113, 113, 0.4);
}

.empty-cell {
    padding: 32px 16px !important;
    text-align: center;
    color: rgba(255, 255, 255, 0.66);
}

.footer-balance {
    text-align: right;
    color: rgba(255, 255, 255, 0.84);
}

.table-actions-bar {
    display: flex;
    justify-content: flex-end;
    margin-top: 16px;
}

.statement-table :deep(.data-table-head th:not(:last-child)),
.statement-table :deep(.data-table-body td:not(:last-child)),
.statement-table :deep(.data-table-foot td:not(:last-child)) {
    border-right: 1px solid rgba(255, 255, 255, 0.14);
}

.info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    justify-content: center;

}
.rounded{
    border-radius: 3px 3px 0px 0px;
}
.report{
    justify-content: right;
    gap: 25%;
}
.invoice{
    justify-content: center;
}
.circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
}
.flexed{
    font-size: 21px;
    display: flex;
    justify-content: center;
    align-items: center;
}
.popover-content[data-v-19f5b08d]{
    background-color: #2d3748;
}
.fa-close::before{
    color: white;
}
[type='checkbox']:checked{
    border: 1px solid white;
}
.bold {
    font-weight: bold;
}
.header {
    display: flex;
    align-items: center;
}
.dark-gray {
    background-color: $dark-gray;
    justify-content: left;
    align-items: center;
    min-height: 20vh;
    min-width: 0;
    border-radius: 10px;
}
.bt{
    font-size:45px ;
    cursor: pointer;
    padding: 0;
}
.popover {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1000; /* high z-index to be on top of other content */
}

.popover-content {
    width: 30%;
    background: white;
    padding: 20px;
    border-radius: 8px;
    position: relative;
}

.popover-close {
    position: absolute;
    top: 10px;
    right: 10px;
    color: black;
}

.right-column {
    background-color: $background-color;
    color: white;
    overflow-y: auto;
}

.hamburger {
    z-index: 2000;
    background-color: transparent;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #fff; /* Adjust the color to match your layout */
}

.sidebar {
    position: fixed;
    top: 0;
    right: 0;
    bottom: 0;
    width: 350px; /* Width of sidebar */
    background-color: $background-color; /* Sidebar background color */
    z-index: 1000; /* Should be below the overlay */
    overflow-y: auto;
    padding: 20px;
    border: 1px solid $white;
    border-right:none;
    border-radius: 4px 0 0 4px ;
}

.order-history {
    padding: 20px;
}

.close-sidebar {
    position: absolute;
    top: 10px;
    right: 10px;
    background: transparent;
    border: none;
    font-size: 24px;
    color: #fff; /* Adjust close button color */
    cursor: pointer;
}

.is-blurred {
    filter: blur(5px);
}

.content {
    transition: filter 0.3s; /* Smooth transition for the blur effect */
}

.history-subtitle {
    background-color: white;
    color: black;
    padding: 10px;
    margin-bottom: 10px;
    font-weight: bold;
}
.jobImg {
    width: 45px;
    height: 45px;
}
.edit-input {
    width: 100%;
    min-height: 34px;
    padding: 6px 10px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background-color: white;
    color: black;
}

.cert-date-edit-wrap {
    position: relative;
    width: 100%;
    overflow: visible;
}

.cert-date-edit-text {
    width: 100%;
}

.cert-date-edit-anchor {
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
    font-size: 16px;
    box-sizing: border-box;
}

.delete-btn {
    color: red;
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px;
    font-size: 18px;

    &:hover {
        opacity: 0.8;
    }
}

.InvoiceDetails {
    border-bottom: 2px dashed lightgray;
}

.relative {
    position: relative;
}

.absolute {
    position: absolute;
    z-index: 1000;
}

.max-h-60 {
    max-height: 15rem;
}

.overflow-auto {
    overflow: auto;
}

.hover\:bg-gray-100:hover {
    background-color: #f3f4f6;
}

.bg-gray-200 {
    background-color: #e5e7eb;
}

.cursor-pointer {
    cursor: pointer;
}

/* Scrollbar styling */
.overflow-auto::-webkit-scrollbar {
    width: 8px;
}

.overflow-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.overflow-auto::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

.overflow-auto::-webkit-scrollbar-thumb:hover {
    background: #555;
}

@media (max-width: 1200px) {
    .summary-grid {
        grid-template-columns: repeat(2, minmax(220px, 1fr));
    }

    .summary-card--wide {
        grid-column: span 2;
    }
}

@media (max-width: 900px) {
    .statement-hero {
        flex-direction: column;
    }

    .page-actions {
        justify-content: flex-start;
    }
}

@media (max-width: 768px) {
    .summary-grid,
    .detail-grid {
        grid-template-columns: 1fr;
    }

    .summary-card--wide {
        grid-column: span 1;
    }
}
</style>

<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="client" subtitle="allClients" icon="UserLogo.png" link="clients"/>
            <div class="form-container p15">
                <div class="dark-gray p-5 text-white">
                    <div class="form-container p-2 light-gray">
                        <h2 class="sub-title">
                            {{ $t('allClients') }}
                        </h2>
                        <div class="search-container p-3 flex">
                            <input type="text" class="text-black rounded" v-model="search" placeholder="Search by clients name">
                            <div class="centered mr-1 ml-4 ">{{ $t('clientsPerPage') }}</div>
                            <div class="ml-3">
                                <select v-model="perPage" class="rounded text-black" @change="fetchClients">
                                    <option value="5">{{ $t('fivePerPage') }}</option>
                                    <option value="10">{{ $t('tenPerPage') }}</option>
                                    <option value="20">{{ $t('twentyPerPage') }}</option>
                                    <option value="0">{{ $t('showAll') }}</option>
                                </select>
                            </div>
                        </div>
                        <DataTableShell>
                            <template #header>
                                <tr>
                                    <th>{{ $t('company') }}</th>
                                    <th>{{ $t('address') }}</th>
                                    <th>{{ $t('city') }}</th>
                                    <th class="actions-header">{{ $t('contacts') }}</th>
                                    <th class="actions-header">{{ $t('newContact') }}</th>
                                    <th class="actions-header">{{ $t('update') }}</th>
                                    <th class="actions-header">{{ $t('cardStatement') }}</th>
                                    <th class="actions-header">{{ $t('Delete') }}</th>
                                </tr>
                            </template>
                            <tr v-for="client in fetchedClients.data" :key="client.id">
                                <td>{{ client.name }}</td>
                                <td>{{ client.address }}</td>
                                <td>{{ client.city }}</td>
                                <td class="align-center">
                                    <ViewContactsDialog :client="client"/>
                                </td>
                                <td class="align-center">
                                    <AddContactDialog :client="client"/>
                                </td>
                                <td class="align-center">
                                    <UpdateClientDialog :client="client"/>
                                </td>
                                <td class="align-center">
                                    <CardStatementUpdateDialog :client_id="client.id" @dialogOpened="fetchClientData"/>
                                </td>
                                <td class="align-center">
                                    <button
                                        v-if="canDeleteClient(client)"
                                        type="button"
                                        class="delete-client-btn"
                                        @click="openDeleteModal(client)"
                                    >
                                        {{ $t('Delete') }}
                                    </button>
                                    <span v-else class="delete-client-na text-muted" :title="$t('clientCannotDeleteLinked')">—</span>
                                </td>
                            </tr>
                        </DataTableShell>
                        <ClientPagination
                            :pagination="fetchedClients"
                            @page-changed="handlePageChange"
                        />
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showDeleteModal" class="client-delete-overlay" @click.self="closeDeleteModal">
            <div class="client-delete-dialog" @click.stop>
                <div class="client-delete-dialog__header">
                    <h3>{{ $t('clientDeleteModalTitle') }}</h3>
                    <button type="button" class="client-delete-dialog__close" @click="closeDeleteModal" aria-label="Close">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <p class="client-delete-dialog__hint">{{ $t('clientDeleteModalHint') }}</p>
                <p v-if="targetClient" class="client-delete-dialog__name">{{ targetClient.name }}</p>
                <div class="code-inputs">
                    <input
                        ref="codeInput0"
                        class="code-box text-black"
                        type="password"
                        maxlength="1"
                        :value="codeDigits[0]"
                        @input="onCodeInput(0, $event)"
                        @keydown="onCodeKeydown(0, $event)"
                        @paste.prevent="onCodePaste($event)"
                    />
                    <input
                        ref="codeInput1"
                        class="code-box text-black"
                        type="password"
                        maxlength="1"
                        :value="codeDigits[1]"
                        @input="onCodeInput(1, $event)"
                        @keydown="onCodeKeydown(1, $event)"
                    />
                    <input
                        ref="codeInput2"
                        class="code-box text-black"
                        type="password"
                        maxlength="1"
                        :value="codeDigits[2]"
                        @input="onCodeInput(2, $event)"
                        @keydown="onCodeKeydown(2, $event)"
                    />
                    <input
                        ref="codeInput3"
                        class="code-box text-black"
                        type="password"
                        maxlength="1"
                        :value="codeDigits[3]"
                        @input="onCodeInput(3, $event)"
                        @keydown="onCodeKeydown(3, $event)"
                        @keyup.enter="confirmDeleteClient"
                    />
                </div>
                <div class="client-delete-dialog__actions">
                    <button type="button" class="nav-btn" @click="closeDeleteModal">{{ $t('cancel') }}</button>
                    <button type="button" class="nav-btn danger" :disabled="deletingClient" @click="confirmDeleteClient">
                        {{ deletingClient ? '…' : $t('Delete') }}
                    </button>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
<script>
import MainLayout from "@/Layouts/MainLayout.vue";
import PrimaryButton from "@/Components/buttons/PrimaryButton.vue";
import SecondaryButton from "@/Components/buttons/SecondaryButton.vue";
import axios from "axios";
import AddContactDialog from "@/Components/AddContactDialog.vue";
import ViewContactsDialog from "@/Components/ViewContactsDialog.vue";
import ClientPagination from "@/Components/ClientPagination.vue";
import Header from "@/Components/Header.vue";
import UpdateClientDialog from "@/Components/UpdateClientDialog.vue";
import CardStatementUpdateDialog from "@/Components/CardStatementUpdateDialog.vue";
import DataTableShell from "@/Components/DataTableShell.vue";
import { useToast } from "vue-toastification";

export default {
    components: {
        CardStatementUpdateDialog,
        UpdateClientDialog,
        ViewContactsDialog,
        AddContactDialog,
        MainLayout,
        PrimaryButton,
        SecondaryButton,
        ClientPagination,
        Header,
        DataTableShell,
    },
    props: {
        clients: Array,
    },
    data() {
        return {
            editMode: false,
            clientExpanded: null,
            search: '',
            fetchedClients: {
                data: [],
                current_page: 1,
                last_page: 1,
                per_page: 10,
                total: 0
            },
            perPage: 10,
            showDeleteModal: false,
            targetClient: null,
            deletingClient: false,
            codeDigits: ['', '', '', ''],
        };
    },
    methods: {
        canDeleteClient(client) {
            return (
                (client.invoices_count || 0) === 0 &&
                (client.jobs_count || 0) === 0 &&
                (client.fakturas_count || 0) === 0 &&
                (client.priemnice_count || 0) === 0 &&
                (client.individual_orders_count || 0) === 0 &&
                (client.trade_invoices_count || 0) === 0 &&
                (client.stock_realizations_count || 0) === 0 &&
                (client.incoming_fakturas_count || 0) === 0
            );
        },
        openDeleteModal(client) {
            this.targetClient = client;
            this.codeDigits = ['', '', '', ''];
            this.showDeleteModal = true;
            this.$nextTick(() => {
                const first = this.$refs.codeInput0;
                first && first.focus();
            });
        },
        closeDeleteModal() {
            if (this.deletingClient) return;
            this.showDeleteModal = false;
            this.targetClient = null;
            this.codeDigits = ['', '', '', ''];
            for (let i = 0; i < 4; i++) {
                const el = this.$refs[`codeInput${i}`];
                if (el) el.value = '';
            }
        },
        async confirmDeleteClient() {
            if (!this.targetClient || this.deletingClient) return;
            const toast = useToast();
            const pass = this.codeDigits.join('');
            if (pass !== '9632') {
                toast.error('Invalid passcode');
                return;
            }
            let deletedOk = false;
            try {
                this.deletingClient = true;
                await axios.delete(`/clients/${this.targetClient.id}`, { data: { passcode: pass } });
                toast.success('Client deleted successfully');
                this.fetchedClients.data = this.fetchedClients.data.filter((c) => c.id !== this.targetClient.id);
                if (this.fetchedClients.total > 0) {
                    this.fetchedClients.total -= 1;
                }
                deletedOk = true;
            } catch (e) {
                const msg = e?.response?.data?.error || e?.response?.data?.message || 'Failed to delete client';
                toast.error(msg);
            } finally {
                this.deletingClient = false;
            }
            if (deletedOk) {
                this.closeDeleteModal();
            }
        },
        onCodeInput(index, event) {
            const val = (event.target.value || '').replace(/\D/g, '').slice(0, 1);
            this.$set ? this.$set(this.codeDigits, index, val) : (this.codeDigits[index] = val);
            event.target.value = val;
            if (val && index < 3) {
                const next = this.$refs[`codeInput${index + 1}`];
                next && next.focus();
            } else if (!val && index > 0) {
                const prev = this.$refs[`codeInput${index - 1}`];
                prev && prev.focus();
            }
        },
        onCodeKeydown(index, event) {
            if (event.key === 'Backspace' && !event.target.value && index > 0) {
                const prev = this.$refs[`codeInput${index - 1}`];
                prev && prev.focus();
            }
            if (event.key === 'ArrowLeft' && index > 0) {
                const prev = this.$refs[`codeInput${index - 1}`];
                prev && prev.focus();
                event.preventDefault();
            }
            if (event.key === 'ArrowRight' && index < 3) {
                const next = this.$refs[`codeInput${index + 1}`];
                next && next.focus();
                event.preventDefault();
            }
        },
        onCodePaste(event) {
            const raw = (event.clipboardData || window.clipboardData).getData('text') || '';
            const digits = raw.replace(/\D/g, '').slice(0, 4).split('');
            digits.forEach((d, i) => {
                if (i < 4) this.codeDigits[i] = d;
            });
            const last = Math.min(digits.length, 3);
            this.$nextTick(() => {
                const el = this.$refs[`codeInput${last}`];
                el && el.focus();
            });
        },
        async fetchClients(page = 1) {
            try {
                const params = {
                    page,
                    search: this.search,
                    per_page: this.perPage,
                };

                const response = await axios.get('/clients', { params });
                this.fetchedClients = response.data;
            } catch (error) {
                console.error('Error fetching clients:', error);
            }
        },
        handlePageChange(page) {
            this.fetchClients(page);
        },
        async deleteContact(client, contact) {
            try {
                await axios.delete(`/clients/${client.id}/contacts/${contact.id}`);
                const contactIndex = client.contacts.findIndex((c) => c.id === contact.id);
                if (contactIndex !== -1) {
                    client.contacts.splice(contactIndex, 1);
                }
            } catch (error) {
                console.error('Error deleting client:', error);
            }
        },
        fetchClientData() {
        }
    },
    watch: {
        search() {
            this.fetchClients(1);
        },
        perPage() {
            this.fetchClients(1);
        }
    },
    mounted() {
        this.fetchClients();
    }
};
</script>

<style scoped lang="scss">
.data-table-shell :deep(th.actions-header),
.data-table-shell :deep(td.align-center) {
    text-align: center;
}

.delete-client-btn {
    border: none;
    border-radius: 6px;
    padding: 8px 14px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    color: $white;
    background-color: $red;
}
.delete-client-btn:hover {
    background-color: darkred;
}

.delete-client-na {
    color: rgba(255, 255, 255, 0.35);
    font-size: 18px;
}

.centered {
    display: flex;
    justify-content: center;
    align-items: center;
}
.green-text{
    color: $green;
}
.blue{
    background-color: $blue;
}
.green{
    background-color: $green;
}
.header{
    display: flex;
    align-items: center;
}
.dark-gray {
    background-color: $dark-gray;
    justify-content: left;
    align-items: center;
    min-height: 20vh;
    min-width: 80vh;
}
.light-gray{
    background-color: $light-gray;
}

.client-form {
    width: 100%;
    justify-content: left;
    align-items: center;
    min-height: 20vh;
    padding: 5px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}
.page-title {
    font-size: 24px;
    display: flex;
    align-items: center;
    color: $white;
}
.sub-title{
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    color: $white;
}

.image-icon {
    margin-left: 2px;
    max-width: 40px;
}
.form-group {
    display: flex;
    justify-content: right;
    align-items: center;
    width: 300px;
    margin-bottom: 10px;
    color: $white;
}

.label {
    flex: 1;
    text-align: left;
    margin-right: 20px;
}
.button-container{
    display: flex-end;
}
.contact-info {
    display: flex;
    flex-direction: row;
    align-items: center;
}

.client-delete-overlay {
    position: fixed;
    inset: 0;
    z-index: 5000;
    background: rgba(0, 0, 0, 0.55);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
}

.client-delete-dialog {
    background: $light-gray;
    color: $white;
    border-radius: 10px;
    max-width: 420px;
    width: 100%;
    padding: 20px;
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.35);
}

.client-delete-dialog__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.client-delete-dialog__header h3 {
    margin: 0;
    font-size: 18px;
}

.client-delete-dialog__close {
    background: transparent;
    border: none;
    color: $white;
    cursor: pointer;
    font-size: 18px;
    line-height: 1;
}

.client-delete-dialog__hint {
    font-size: 14px;
    opacity: 0.9;
    margin-bottom: 8px;
}

.client-delete-dialog__name {
    font-weight: 700;
    margin-bottom: 16px;
    color: $green;
}

.code-inputs {
    display: flex;
    gap: 10px;
    justify-content: center;
    margin-bottom: 20px;
}

.code-box {
    width: 44px;
    height: 44px;
    text-align: center;
    font-size: 20px;
    border-radius: 6px;
    border: 1px solid #ccc;
}

.client-delete-dialog__actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.nav-btn {
    padding: 8px 16px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    background: rgba(255, 255, 255, 0.12);
    color: $white;
    font-weight: 600;
}

.nav-btn.danger {
    background: $red;
}

.nav-btn.danger:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>

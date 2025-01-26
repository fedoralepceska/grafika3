<template>
    <MainLayout>
        <div class="pl-7 pr-7 flex">
            <div class="sidebar" v-if="isSidebarVisible">
                <button @click="toggleSidebar" class="close-sidebar">
                    <span class="mdi mdi-close"></span>
                </button>
            </div>
            <div class="left-column flex-1" style="width: 25%">
                <div class="flex justify-between">
                    <Header title="statement" subtitle="currentReport" icon="invoice.png" link="statements"/>
                </div>
                <div class="dark-gray p-5 text-white">
                    <div class="report">
                        <div class="flexed">
                            <h1>
                                {{$t('currentReport')}} {{$t('Nr')}} {{certificate.id_per_bank}} - {{certificate.date}}
                            </h1>
                        </div>
                        <div class="justify-end flex gap-6 pb-5 mr-3">
                            <div class="flex justify-between mb-4">
                                <button class="btn green" @click="toggleEditMode">
                                    {{ isEditMode ? $t('Exit Edit Mode') : $t('Edit Mode') }}
                                </button>
                                <button class="btn blue ml-2" @click="toggleStatementEditMode">
                                    {{ isStatementEditMode ? (hasStatementChanges ? 'Save & Exit' : 'Exit Edit Mode') : 'Edit Statement' }}
                                </button>
                            </div>
                            <div class="pr-3">
                                <AddCertificateDialog
                                :certificate="certificate"
                                />
                            </div>
                            <div>
                                <AddItemDialog
                                    :certificate="certificate"
                                />
                            </div>
                        </div>
                    </div>
                    <div class="form-container p-2 light-gray">
                        <div class="InvoiceDetails">
                            <div class="invoice-details flex gap-20 relative mb-2" >
                                <div class="invoice-title bg-white text-black bold p-3 ">{{ certificate.id_per_bank }}/{{new Date(certificate.date).toLocaleDateString('en-US', { year: 'numeric'})}}</div>
                                <div class="info">
                                    <div>Statement</div>
                                    <div class="bold">#{{ certificate?.id_per_bank }}</div>
                                </div>
                                <div class="info">
                                    <div>Bank</div>
                                    <div v-if="isStatementEditMode" class="relative">
                                        <input
                                            type="text"
                                            v-model="bankSearch"
                                            @focus="showBankDropdown = true"
                                            @input="filterBanks"
                                            :placeholder="certificate.bank"
                                            class="edit-input"
                                        />
                                        <div v-if="showBankDropdown"
                                             class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto">
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
                                    <div v-else class="bold">{{certificate.bank}}</div>
                                </div>
                                <div class="info">
                                    <div>Bank Account</div>
                                    <div class="bold">{{ isStatementEditMode ? editedCertificate.bankAccount : certificate.bankAccount }}</div>
                                </div>
                                <div class="info">
                                    <div>Created By</div>
                                    <div class="bold">{{certificate.created_by?.name}}</div>
                                </div>
                                <div class="info">
                                    <div>Date</div>
                                    <div v-if="isStatementEditMode">
                                        <input
                                            type="date"
                                            v-model="editedCertificate.date"
                                            class="edit-input"
                                            @input="markStatementAsModified"
                                        >
                                    </div>
                                    <span v-else class="bold">{{ certificate.date }}</span>
                                </div>
                                <div class="info">
                                    <div>Total Expense</div>
                                    <span class="bold">{{ totalExpense }}</span>
                                </div>
                                <div class="info">
                                    <div>Total Income</div>
                                    <span class="bold">{{ totalIncome }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <table>
                        <tr>
                            <th>ID</th>
                            <th>Statement</th>
                            <th>Item</th>
                            <th>Client</th>
                            <th>Expense</th>
                            <th>Income</th>
                            <th>Code</th>
                            <th>Reference to</th>
                            <th>Comment</th>
                            <th v-if="isEditMode">Actions</th>
                        </tr>
                        <tr v-for="(item, index) in items" :key="item.id">
                            <td>{{item.id}}</td>
                            <td>{{certificate.id_per_bank}}</td>
                            <td>#{{index+1}}</td>
                            <td>
                                <div v-if="isEditMode" class="relative">
                                    <input
                                        type="text"
                                        v-model="clientSearch[item.id]"
                                        @focus="initializeClientDropdown(item.id)"
                                        @input="filterClients(item.id)"
                                        :placeholder="getClientName(item)"
                                        class="edit-input"
                                    />
                                    <div v-if="showClientDropdown[item.id]"
                                         class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto">
                                        <div
                                            v-for="client in filteredClients[item.id] || []"
                                            :key="client.id"
                                            @click="selectClient(client, item)"
                                            class="px-4 py-2 hover:bg-gray-100 cursor-pointer text-gray-700"
                                            :class="{'bg-gray-200': item.client_id === client.id}"
                                        >
                                            {{ client.name }}
                                        </div>
                                        <div v-if="!filteredClients[item.id]?.length" class="px-4 py-2 text-gray-500">
                                            No clients found
                                        </div>
                                    </div>
                                </div>
                                <span v-else>{{item.client.name}}</span>
                            </td>
                            <td>
                                <input v-if="isEditMode"
                                    type="number"
                                    v-model="item.expense"
                                    class="edit-input"
                                    @input="markAsModified(item)">
                                <span v-else>{{formatNumber(item.expense)}}</span>
                            </td>
                            <td>
                                <input v-if="isEditMode"
                                    type="number"
                                    v-model="item.income"
                                    class="edit-input"
                                    @input="markAsModified(item)">
                                <span v-else>{{formatNumber(item.income)}}</span>
                            </td>
                            <td>
                                <input v-if="isEditMode"
                                    type="text"
                                    v-model="item.code"
                                    class="edit-input"
                                    @input="markAsModified(item)">
                                <span v-else>{{item.code}}</span>
                            </td>
                            <td>
                                <input v-if="isEditMode"
                                    type="text"
                                    v-model="item.reference_to"
                                    class="edit-input"
                                    @input="markAsModified(item)">
                                <span v-else>{{item.reference_to}}</span>
                            </td>
                            <td>
                                <input v-if="isEditMode"
                                    type="text"
                                    v-model="item.comment"
                                    class="edit-input"
                                    @input="markAsModified(item)">
                                <span v-else>{{item.comment}}</span>
                            </td>
                            <td v-if="isEditMode">
                                <button class="delete-btn" @click="deleteItem(item.id)">
                                    <span class="mdi mdi-close"></span>
                                </button>
                            </td>
                        </tr>
                    </table>
                    <div class="flex justify-end">
                        <button v-if="isEditMode && hasModifiedItems"
                            class="btn create-order"
                            @click="saveChanges">
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
import Toast, {useToast} from "vue-toastification";
import OrderJobDetails from "@/Pages/Invoice/OrderJobDetails.vue";
import OrderSpreadsheet from "@/Components/OrderSpreadsheet.vue";
import Header from "@/Components/Header.vue";
import AddItemDialog from "@/Components/AddItemDialog.vue";
import AddCertificateDialog from "@/Components/AddCertificateDialog.vue";

export default {
    components: {
        AddItemDialog,
        AddCertificateDialog,
        OrderSpreadsheet,
        OrderJobDetails,
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
        }
    },
    data() {
        return {
            isSidebarVisible: false,
            addItemDialogVisible: false,
            openDialog: false,
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
        }
    },
    async beforeMount() {
        const response = await axios.get(`/items/${this.item.certificate_id}`);
        this.items = response.data;
        await this.fetchClientsWithStatements();
    },
    methods: {
        toggleSidebar() {
            this.isSidebarVisible = !this.isSidebarVisible;
        },
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
            }
        },
        markStatementAsModified() {
            this.hasStatementChanges = true;
        },
        async saveStatementChanges() {
            const toast = useToast();
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
        formatNumber(value) {
            return Number(value).toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
    },
    async mounted() {
        document.addEventListener('click', (e) => {
            // Close dropdowns when clicking outside
            const dropdowns = document.querySelectorAll('.relative');
            dropdowns.forEach(dropdown => {
                if (!dropdown.contains(e.target)) {
                    const itemId = dropdown.closest('tr')?.querySelector('td')?.textContent;
                    if (itemId) {
                        this.showClientDropdown[itemId] = false;
                    }
                }
            });
        });
        await this.fetchBanks();
    },
    beforeUnmount() {
        document.removeEventListener('click', this.handleClickOutside);
    },
};
</script>

<style scoped lang="scss">
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
.orange-text {
    color: $orange;
}
.blue-text {
    color: $blue;
}
.bold {
    font-weight: bold;
}
.green-text {
    color: $green;
}
.light-gray{
    background-color: $light-gray;
}
.ultra-light-gray{
    background-color: $ultra-light-gray;
}
.blue{
    background-color: $blue;
}
.green{
    background-color: $green;
}
.background{
    background-color: $background-color;
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
    min-width: 80vh;
}
.sub-title {
    font-size: 20px;
    font-weight: bold;
    display: flex;
    align-items: center;
    color: $white;
}

.create-order{
    background-color: $green;
    color: white;
}
.btn {
    margin-bottom: 3px;
    padding: 10px 15px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    border-radius: 2px;
}

.add-row {
    background-color: $blue;
    color: white;
}

.InvoiceDetails {
    border-bottom: 2px dashed lightgray;
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
table {
    width: 100%;
    border-collapse: collapse;
    border-spacing: 0;
    margin-bottom: 20px;
    border: 1px solid #ddd;
    border-radius: 5px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

table th, table td {
    padding: 10px;
    border: 1px solid #ddd;
    text-align: center;
}

table th, table tr {
    color: white;
    border-top: 1px solid #ddd;
    border-bottom: 1px solid #ddd;
    background-color: $ultra-light-gray;

}

.edit-input {
    width: 100%;
    padding: 4px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background-color: white;
    color: black;
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
</style>

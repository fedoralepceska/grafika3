<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="Year-End Census" subtitle="Manage fiscal year closures" icon="year-end.png"
                link="year-end-census" />
            <div class="dark-gray p-4 text-white">
                <div class="tabs-container">
                    <button :class="['tab-btn', { active: activeTab === 'orders' }]" @click="activeTab = 'orders'">
                        Orders
                    </button>
                    <button :class="['tab-btn', { active: activeTab === 'invoices' }]" @click="activeTab = 'invoices'">
                        Invoices
                    </button>
                    <button :class="['tab-btn', { active: activeTab === 'materials' }]" @click="activeTab = 'materials'">
                        Materials
                    </button>
                    <button :class="['tab-btn', { active: activeTab === 'bankStatements' }]" @click="activeTab = 'bankStatements'">
                        Bank Statements
                    </button>
                    <button :class="['tab-btn', { active: activeTab === 'clients' }]" @click="activeTab = 'clients'">
                        Clients
                    </button>
                </div>

                <OrdersTab v-if="activeTab === 'orders'" :available-years="availableYears" />
                <InvoicesTab v-if="activeTab === 'invoices'" :available-years="invoiceYears" />
                <MaterialsTab v-if="activeTab === 'materials'" :available-years="materialYears" />
                <BankStatementsTab v-if="activeTab === 'bankStatements'" :available-years="bankStatementYears" />
                <ClientsTab v-if="activeTab === 'clients'" :available-years="clientYears" />
            </div>
        </div>
    </MainLayout>
</template>

<script>
import MainLayout from "@/Layouts/MainLayout.vue";
import Header from "@/Components/Header.vue";
import OrdersTab from "./OrdersTab.vue";
import InvoicesTab from "./InvoicesTab.vue";
import MaterialsTab from "./MaterialsTab.vue";
import BankStatementsTab from "./BankStatementsTab.vue";
import ClientsTab from "./ClientsTab.vue";

export default {
    components: { MainLayout, Header, OrdersTab, InvoicesTab, MaterialsTab, BankStatementsTab, ClientsTab },
    props: {
        availableYears: {
            type: Array,
            default: () => []
        },
        invoiceYears: {
            type: Array,
            default: () => []
        },
        materialYears: {
            type: Array,
            default: () => []
        },
        bankStatementYears: {
            type: Array,
            default: () => []
        },
        clientYears: {
            type: Array,
            default: () => []
        }
    },
    data() {
        return {
            activeTab: 'orders'
        };
    }
};
</script>

<style scoped lang="scss">
.dark-gray {
    background-color: $dark-gray;
    border-radius: 8px;
    min-height: 60vh;
}

.tabs-container {
    display: flex;
    gap: 8px;
    margin-bottom: 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

.tab-btn {
    padding: 10px 20px;
    background: transparent;
    border: none;
    color: rgba(255, 255, 255, 0.6);
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    border-radius: 4px 4px 0 0;
    transition: all 0.2s ease;

    &:hover {
        color: white;
        background: rgba(255, 255, 255, 0.1);
    }

    &.active {
        color: white;
        background: rgba(59, 130, 246, 0.3);
        border-bottom: 2px solid #3b82f6;
    }
}
</style>

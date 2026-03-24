<template>
    <div class="finance-tabs">
        <div class="tabs-scroll">
            <button
                v-for="tab in tabs"
                :key="tab.route"
                type="button"
                class="tab-chip"
                :class="{ active: isActive(tab) }"
                @click="navigate(tab.route)"
            >
                <i :class="tab.icon" aria-hidden="true"></i>
                <span>{{ tab.label }}</span>
            </button>
        </div>

        <button
            v-if="actionLabel && actionRoute"
            type="button"
            class="action-button"
            @click="navigate(actionRoute)"
        >
            <i v-if="actionIcon" :class="actionIcon" aria-hidden="true"></i>
            <span>{{ actionLabel }}</span>
        </button>
    </div>
</template>

<script>
export default {
    props: {
        route: {
            type: String,
            default: '',
        },
        actionLabel: {
            type: String,
            default: '',
        },
        actionRoute: {
            type: String,
            default: '',
        },
        actionIcon: {
            type: String,
            default: '',
        },
    },
    computed: {
        tabs() {
            return [
                { label: this.$t('financeTabs.allInvoices'), route: '/allInvoices', icon: 'fa-solid fa-file-invoice' },
                { label: this.$t('financeTabs.notInvoiced'), route: '/notInvoiced', icon: 'fa-solid fa-clock-rotate-left' },
                { label: this.$t('financeTabs.incomingInvoice'), route: '/incomingInvoice', icon: 'fa-solid fa-file-arrow-down' },
                { label: this.$t('financeTabs.tradeInvoices'), route: '/trade-invoices', icon: 'fa-solid fa-receipt' },
                { label: this.$t('financeTabs.receipts'), route: '/receipt', icon: 'fa-solid fa-box-archive' },
                { label: this.$t('financeTabs.individual'), route: '/individual', icon: 'fa-solid fa-user' },
                { label: this.$t('financeTabs.stockRealization'), route: '/stock-realizations', icon: 'fa-solid fa-boxes-stacked' },
                { label: this.$t('financeTabs.bankStatements'), route: '/statements', icon: 'fa-solid fa-building-columns' },
                { label: this.$t('financeTabs.clientStatements'), route: '/cardStatements', icon: 'fa-solid fa-address-card' },
            ];
        },
        currentRoute() {
            const sourceRoute = this.route || this.$page.url || '';
            const [path] = sourceRoute.split('?');
            return path.replace(/\/$/, '') || '/';
        },
    },
    methods: {
        navigate(route) {
            this.$inertia.visit(route);
        },
        isActive(tab) {
            if (tab.route === '/trade-invoices') {
                return this.currentRoute === tab.route || this.currentRoute.startsWith('/trade-invoices/');
            }

            if (tab.route === '/stock-realizations') {
                return this.currentRoute === tab.route || this.currentRoute.startsWith('/stock-realizations/');
            }

            if (tab.route === '/receipt') {
                return this.currentRoute === tab.route || this.currentRoute.startsWith('/receipt/');
            }

            return this.currentRoute === tab.route;
        },
    },
};
</script>

<style scoped lang="scss">
.finance-tabs {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    margin-bottom: 0;
    padding: 0 12px;
}

.tabs-scroll {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    overflow-x: auto;
    scrollbar-width: thin;
    padding: 10px 12px 0;
    background: transparent;
}

.tab-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 14px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-bottom: none;
    border-radius: 8px 8px 0 0;
    background: rgba(255, 255, 255, 0.04);
    color: rgba(255, 255, 255, 0.78);
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    white-space: nowrap;
    transition: all 0.2s ease;
}

.tab-chip:hover {
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(255, 255, 255, 0.18);
    color: $white;
}

.tab-chip.active {
    background: linear-gradient(135deg, rgba(41, 173, 96, 0.95), rgba(29, 131, 73, 0.95));
    border-color: rgba(41, 173, 96, 1);
    color: $white;
    box-shadow: 0 8px 20px rgba(29, 131, 73, 0.25);
}

.tab-chip i,
.action-button i {
    font-size: 12px;
}

.action-button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 14px;
    border: 1px solid rgba(59, 130, 246, 0.45);
    border-bottom: none;
    border-radius: 8px 8px 0 0;
    background: rgba(59, 130, 246, 0.16);
    color: $white;
    font-size: 13px;
    font-weight: 700;
    white-space: nowrap;
    cursor: pointer;
    transition: all 0.2s ease;
}

.action-button:hover {
    background: rgba(59, 130, 246, 0.26);
    border-color: rgba(96, 165, 250, 0.8);
}

@media (max-width: 900px) {
    .finance-tabs {
        justify-content: flex-start;
        padding: 0;
    }

    .action-button {
        justify-content: center;
        width: 100%;
    }
}
</style>

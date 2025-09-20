<template>
    <div class="redirect-tabs text-white">
        <button @click="navigate('/allInvoices')" :class="{ active: currentRoute === '/allInvoices' }">All Invoices</button>
        <button @click="navigate('/notInvoiced')" :class="{ active: currentRoute === '/notInvoiced' }">Not Invoiced</button>
        <button @click="navigate('/incomingInvoice')" :class="{ active: currentRoute === '/incomingInvoice' }">Incoming Invoice</button>
        <button @click="navigate('/trade-invoices')" :class="{ active: currentRoute === '/trade-invoices' }">Trade Invoices</button>
        <button @click="navigate('/individual')" :class="{ active: currentRoute === '/individual' }">Физичко лице</button>
        <button @click="navigate('/stock-realizations')" :class="{ active: currentRoute === '/stock-realizations' || currentRoute.startsWith('/stock-realizations/') }">Stock Realization</button>
        <button @click="navigate('/statements')" :class="{ active: currentRoute === '/statements' }">Bank Statements</button>
        <button @click="navigate('/cardStatements')" :class="{ active: currentRoute === '/cardStatements' }">Client Card Statements</button>
    </div>
</template>

<script>
export default {
    data() {
        return {
            currentRoute: ''
        };
    },
    methods: {
        navigate(route) {
            this.$inertia.visit(route);
        }
    },
    created() {
        this.currentRoute = this.$page.url;
    },
    watch: {
        '$page.props.route'() {
            this.currentRoute = this.$page.url;
        }
    }
};
</script>

<style scoped lang="scss">
.redirect-tabs {
    display: flex;
    justify-content: center;
}

.redirect-tabs button {
    padding: 10px 20px;
    cursor: pointer;
    border: none;
    background: $light-gray;
    border-radius: 3px;
    margin: 5px;
}

.redirect-tabs button.active {
    background-color: $green;
    color: #fff;
}
</style>

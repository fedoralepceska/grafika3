<template>
    <div
        class="finance-client-name"
        :class="variantClass"
        :title="tooltip"
    >
        <span class="finance-client-name__text">{{ displayText }}</span>
    </div>
</template>

<script>
export default {
    name: 'FinanceClientNameCell',
    props: {
        /** Raw client name (empty shows emptyLabel) */
        name: {
            type: String,
            default: '',
        },
        emptyLabel: {
            type: String,
            default: 'N/A',
        },
        /** Matches finance tables: bold white vs muted secondary */
        variant: {
            type: String,
            default: 'primary',
            validator: (v) => ['primary', 'secondary'].includes(v),
        },
    },
    computed: {
        trimmed() {
            return (this.name || '').trim();
        },
        displayText() {
            return this.trimmed || this.emptyLabel;
        },
        /** Native tooltip with full name (only when there is a real name) */
        tooltip() {
            return this.trimmed || undefined;
        },
        variantClass() {
            return this.variant === 'secondary' ? 'finance-client-name--secondary' : 'finance-client-name--primary';
        },
    },
};
</script>

<style scoped lang="scss">
.finance-client-name {
    min-width: 0;
    max-width: min(280px, 100%);
    cursor: default;
}

.finance-client-name__text {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.finance-client-name--primary {
    font-weight: 700;
    color: #ffffff;
}

.finance-client-name--secondary {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.68);
}
</style>

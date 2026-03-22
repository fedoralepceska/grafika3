<template>
    <div class="fc-pp">
        <label v-if="label" class="fc-label">{{ label }}</label>
        <!-- Keeps buttons aligned with inputs that have labels above -->
        <div v-else class="fc-pp__label-spacer" aria-hidden="true" />
        <div class="fc-pp__row">
            <button type="button" class="fc-pp__btn" @click="$emit('preset', 'this_month')">This month</button>
            <button type="button" class="fc-pp__btn" @click="$emit('preset', 'this_year')">This year</button>
            <button type="button" class="fc-pp__btn fc-pp__btn--ghost" @click="$emit('clear-dates')">Clear dates</button>
        </div>
    </div>
</template>

<script>
export default {
    name: 'FinancePeriodPresets',
    props: {
        label: { type: String, default: 'Quick period' },
    },
    emits: ['preset', 'clear-dates'],
};
</script>

<style scoped lang="scss">
.fc-pp {
    min-width: 0;
}

.fc-label {
    display: block;
    margin-bottom: 4px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: rgba(255, 255, 255, 0.7);
}

.fc-pp__label-spacer {
    height: 15px;
    margin-bottom: 2px;
}

.fc-pp__row {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.fc-pp__btn {
    padding: 6px 10px;
    font-size: 11px;
    font-weight: 700;
    border-radius: 6px;
    border: 1px solid rgba(59, 130, 246, 0.45);
    background: rgba(59, 130, 246, 0.15);
    color: #fff;
    cursor: pointer;
}

.fc-pp__btn:hover {
    background: rgba(59, 130, 246, 0.28);
}

.fc-pp__btn--ghost {
    border-color: rgba(255, 255, 255, 0.2);
    background: rgba(255, 255, 255, 0.06);
}

.fc-pp__btn--ghost:hover {
    background: rgba(255, 255, 255, 0.1);
}
</style>

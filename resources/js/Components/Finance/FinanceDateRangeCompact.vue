<template>
    <div class="fc-dr">
        <label v-if="label" class="fc-label">{{ label }}</label>
        <div class="fc-dr__row">
            <input
                :value="dateFrom"
                type="date"
                class="fc-dr__input text-black"
                @input="$emit('update:dateFrom', $event.target.value)"
                @change="$emit('change')"
            />
            <span class="fc-dr__sep">–</span>
            <input
                :value="dateTo"
                type="date"
                class="fc-dr__input text-black"
                @input="$emit('update:dateTo', $event.target.value)"
                @change="$emit('change')"
            />
        </div>
    </div>
</template>

<script>
export default {
    name: 'FinanceDateRangeCompact',
    props: {
        dateFrom: { type: String, default: '' },
        dateTo: { type: String, default: '' },
        label: { type: String, default: 'Created' },
    },
    emits: ['update:dateFrom', 'update:dateTo', 'change'],
};
</script>

<style scoped lang="scss">
.fc-dr {
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

.fc-dr__row {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: nowrap;
}

.fc-dr__input {
    flex: 1 1 0;
    min-width: 108px;
    max-width: 150px;
    min-height: 34px;
    border-radius: 8px;
    padding: 0 8px;
    font-size: 12px;
}

.fc-dr__sep {
    color: rgba(255, 255, 255, 0.45);
    font-size: 12px;
}
</style>

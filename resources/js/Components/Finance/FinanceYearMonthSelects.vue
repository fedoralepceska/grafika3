<template>
    <div class="fc-ym">
        <div v-if="showFiscalYear" class="fc-ym__field">
            <label class="fc-label">{{ fiscalYearLabel }}</label>
            <select
                :value="fiscalYear"
                class="fc-select text-black"
                @change="onFiscalYear($event.target.value)"
            >
                <option value="">{{ allFiscalLabel }}</option>
                <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
            </select>
        </div>
        <div v-if="showMonth" class="fc-ym__field">
            <label class="fc-label">{{ monthLabel }}</label>
            <select
                :value="month"
                class="fc-select text-black"
                @change="onMonth($event.target.value)"
            >
                <option value="">{{ allMonthLabel }}</option>
                <option v-for="m in 12" :key="m" :value="m">{{ monthNames[m - 1] }}</option>
            </select>
        </div>
    </div>
</template>

<script>
export default {
    name: 'FinanceYearMonthSelects',
    props: {
        fiscalYear: { type: [String, Number], default: '' },
        month: { type: [String, Number], default: '' },
        showFiscalYear: { type: Boolean, default: true },
        showMonth: { type: Boolean, default: true },
        fiscalYearLabel: { type: String, default: 'Fiscal year' },
        monthLabel: { type: String, default: 'Month' },
        allFiscalLabel: { type: String, default: 'All years' },
        allMonthLabel: { type: String, default: 'All months' },
        minYear: { type: Number, default: null },
        maxYear: { type: Number, default: null },
    },
    emits: ['update:fiscalYear', 'update:month', 'change'],
    computed: {
        yearOptions() {
            const max = this.maxYear != null ? this.maxYear : new Date().getFullYear() + 1;
            const min = this.minYear != null ? this.minYear : max - 8;
            const list = [];
            for (let y = max; y >= min; y--) {
                list.push(y);
            }
            return list;
        },
        monthNames() {
            return [
                'Jan',
                'Feb',
                'Mar',
                'Apr',
                'May',
                'Jun',
                'Jul',
                'Aug',
                'Sep',
                'Oct',
                'Nov',
                'Dec',
            ];
        },
    },
    methods: {
        onFiscalYear(val) {
            this.$emit('update:fiscalYear', val === '' ? '' : parseInt(val, 10));
            this.$emit('change');
        },
        onMonth(val) {
            this.$emit('update:month', val === '' ? '' : parseInt(val, 10));
            this.$emit('change');
        },
    },
};
</script>

<style scoped lang="scss">
.fc-ym {
    display: flex;
    gap: 8px;
    flex-wrap: nowrap;
    align-items: flex-end;
}

.fc-ym__field {
    flex: 1 1 0;
    min-width: 100px;
    max-width: 130px;
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

.fc-select {
    width: 100%;
    min-height: 34px;
    border-radius: 8px;
    padding: 0 8px;
    font-size: 13px;
}
</style>

<template>
    <div class="fc-dr">
        <label v-if="label" class="fc-label">{{ label }}</label>
        <div class="fc-dr__row">
            <div class="fc-dr__pick">
                <input
                    v-model="displayFrom"
                    type="text"
                    class="fc-dr__text text-black"
                    placeholder="dd/mm/yyyy"
                    inputmode="numeric"
                    autocomplete="off"
                    title="dd/mm/yyyy — click to open calendar or type"
                    @blur="commitFrom"
                    @click="openPicker('from')"
                />
                <input
                    ref="nativeFrom"
                    type="date"
                    class="fc-dr__anchor"
                    :value="dateFrom || ''"
                    tabindex="-1"
                    aria-hidden="true"
                    @change="onNativeFrom"
                />
            </div>
            <span class="fc-dr__sep">–</span>
            <div class="fc-dr__pick">
                <input
                    v-model="displayTo"
                    type="text"
                    class="fc-dr__text text-black"
                    placeholder="dd/mm/yyyy"
                    inputmode="numeric"
                    autocomplete="off"
                    title="dd/mm/yyyy — click to open calendar or type"
                    @blur="commitTo"
                    @click="openPicker('to')"
                />
                <input
                    ref="nativeTo"
                    type="date"
                    class="fc-dr__anchor"
                    :value="dateTo || ''"
                    tabindex="-1"
                    aria-hidden="true"
                    @change="onNativeTo"
                />
            </div>
        </div>
    </div>
</template>

<script>
import { useToast } from 'vue-toastification';
import { parseDdMmYyyyToIso, formatDateDdMmYyyy, isoYmdToDdMmYyyy } from '@/utils/financeFilters';

export default {
    name: 'FinanceDateRangeCompact',
    props: {
        dateFrom: { type: String, default: '' },
        dateTo: { type: String, default: '' },
        label: { type: String, default: 'Created' },
    },
    emits: ['update:dateFrom', 'update:dateTo', 'change'],
    data() {
        return {
            displayFrom: '',
            displayTo: '',
        };
    },
    watch: {
        dateFrom: {
            immediate: true,
            handler() {
                this.displayFrom = this.isoToDisplay(this.dateFrom);
            },
        },
        dateTo: {
            immediate: true,
            handler() {
                this.displayTo = this.isoToDisplay(this.dateTo);
            },
        },
    },
    methods: {
        isoToDisplay(iso) {
            if (!iso || String(iso).trim() === '') {
                return '';
            }
            const s = String(iso).trim();
            if (/^\d{4}-\d{2}-\d{2}$/.test(s)) {
                return isoYmdToDdMmYyyy(s);
            }
            const formatted = formatDateDdMmYyyy(s);
            return formatted === 'N/A' ? '' : formatted;
        },
        commitFrom() {
            const raw = (this.displayFrom || '').trim();
            if (!raw) {
                this.$emit('update:dateFrom', '');
                this.$emit('change');
                return;
            }
            const iso = parseDdMmYyyyToIso(raw);
            if (!iso) {
                const toast = useToast();
                toast.error('From date: use dd/mm/yyyy (e.g. 14/01/2026).');
                this.displayFrom = this.isoToDisplay(this.dateFrom);
                return;
            }
            this.$emit('update:dateFrom', iso);
            this.displayFrom = formatDateDdMmYyyy(iso);
            this.$emit('change');
        },
        commitTo() {
            const raw = (this.displayTo || '').trim();
            if (!raw) {
                this.$emit('update:dateTo', '');
                this.$emit('change');
                return;
            }
            const iso = parseDdMmYyyyToIso(raw);
            if (!iso) {
                const toast = useToast();
                toast.error('To date: use dd/mm/yyyy (e.g. 14/01/2026).');
                this.displayTo = this.isoToDisplay(this.dateTo);
                return;
            }
            this.$emit('update:dateTo', iso);
            this.displayTo = formatDateDdMmYyyy(iso);
            this.$emit('change');
        },
        onNativeFrom(e) {
            const v = e.target.value;
            this.$emit('update:dateFrom', v || '');
            this.displayFrom = v ? formatDateDdMmYyyy(v) : '';
            this.$emit('change');
        },
        onNativeTo(e) {
            const v = e.target.value;
            this.$emit('update:dateTo', v || '');
            this.displayTo = v ? formatDateDdMmYyyy(v) : '';
            this.$emit('change');
        },
        openPicker(which) {
            const el = which === 'from' ? this.$refs.nativeFrom : this.$refs.nativeTo;
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
    },
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
    align-items: flex-end;
    gap: 6px;
    flex-wrap: nowrap;
}

.fc-dr__pick {
    position: relative;
    flex: 1 1 0;
    min-width: 0;
    max-width: 132px;
    overflow: visible;
}

.fc-dr__text {
    width: 100%;
    min-width: 0;
    min-height: 34px;
    border-radius: 8px;
    padding: 0 8px;
    font-size: 12px;
    border: 1px solid rgba(0, 0, 0, 0.2);
    box-sizing: border-box;
}

.fc-dr__text:focus {
    outline: none;
    border-color: rgba(59, 130, 246, 0.65);
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}

.fc-dr__anchor {
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

.fc-dr__sep {
    flex-shrink: 0;
    padding-bottom: 8px;
    color: rgba(255, 255, 255, 0.45);
    font-size: 12px;
}
</style>

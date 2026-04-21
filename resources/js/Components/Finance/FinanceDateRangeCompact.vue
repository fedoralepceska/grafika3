<template>
    <div class="fc-dr">
        <label v-if="label" class="fc-label">{{ label }}</label>
        <div class="fc-dr__row" :class="{ 'fc-dr__row--single': single }">
            <div class="fc-dr__pick">
                <input
                    v-model="displayFrom"
                    type="text"
                    class="fc-dr__text text-black"
                    placeholder="dd/mm/yyyy"
                    inputmode="numeric"
                    autocomplete="off"
                    title="Type dd/mm/yyyy"
                    maxlength="10"
                    pattern="\d{2}/\d{2}/\d{4}"
                    @input="onDateTyping('from', $event)"
                    @keydown="onDateKeydown('from', $event)"
                    @keyup.enter="commitFrom"
                    @blur="commitFrom"
                />
                <button
                    type="button"
                    class="fc-dr__picker-btn"
                    title="Open calendar"
                    :aria-label="single ? 'Open date calendar' : 'Open from date calendar'"
                    @click="openPicker('from')"
                >
                    <i class="fas fa-calendar-alt" aria-hidden="true" />
                </button>
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
            <span v-if="!single" class="fc-dr__sep">–</span>
            <div v-if="!single" class="fc-dr__pick">
                <input
                    v-model="displayTo"
                    type="text"
                    class="fc-dr__text text-black"
                    placeholder="dd/mm/yyyy"
                    inputmode="numeric"
                    autocomplete="off"
                    title="Type dd/mm/yyyy"
                    maxlength="10"
                    pattern="\d{2}/\d{2}/\d{4}"
                    @input="onDateTyping('to', $event)"
                    @keydown="onDateKeydown('to', $event)"
                    @keyup.enter="commitTo"
                    @blur="commitTo"
                />
                <button
                    type="button"
                    class="fc-dr__picker-btn"
                    title="Open calendar"
                    aria-label="Open to date calendar"
                    @click="openPicker('to')"
                >
                    <i class="fas fa-calendar-alt" aria-hidden="true" />
                </button>
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
        /** One field only — same styling as the “from” cell in range mode (e.g. due-date target). */
        single: { type: Boolean, default: false },
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
                if (this.single) {
                    return;
                }
                this.displayTo = this.isoToDisplay(this.dateTo);
            },
        },
    },
    methods: {
        normalizeDateTyping(rawValue) {
            const digits = String(rawValue || '').replace(/\D/g, '').slice(0, 8);
            if (!digits) {
                return '';
            }
            if (digits.length <= 2) {
                return digits;
            }
            if (digits.length <= 4) {
                return `${digits.slice(0, 2)}/${digits.slice(2)}`;
            }
            return `${digits.slice(0, 2)}/${digits.slice(2, 4)}/${digits.slice(4)}`;
        },
        onDateTyping(which, event) {
            const normalized = this.normalizeDateTyping(event?.target?.value);
            if (which === 'from') {
                this.displayFrom = normalized;
            } else {
                this.displayTo = normalized;
            }
            if (event?.target && event.target.value !== normalized) {
                event.target.value = normalized;
            }
        },
        nextDigitPosition(pos) {
            const digitPositions = [0, 1, 3, 4, 6, 7, 8, 9];
            for (const dp of digitPositions) {
                if (dp > pos) return dp;
            }
            return 10;
        },
        firstDigitPositionAtOrAfter(pos) {
            const digitPositions = [0, 1, 3, 4, 6, 7, 8, 9];
            for (const dp of digitPositions) {
                if (dp >= pos) return dp;
            }
            return -1;
        },
        lastDigitPositionBefore(pos) {
            const digitPositions = [0, 1, 3, 4, 6, 7, 8, 9];
            for (let i = digitPositions.length - 1; i >= 0; i -= 1) {
                if (digitPositions[i] < pos) return digitPositions[i];
            }
            return -1;
        },
        onDateKeydown(which, event) {
            if (event.metaKey || event.ctrlKey || event.altKey) {
                return;
            }
            const input = event.target;
            if (!input) {
                return;
            }
            const current = which === 'from' ? this.displayFrom : this.displayTo;
            if (!/^\d{2}\/\d{2}\/\d{4}$/.test(current || '')) {
                return;
            }
            const start = input.selectionStart;
            const end = input.selectionEnd;
            if (start == null || end == null) {
                return;
            }

            if (event.key === 'Backspace' || event.key === 'Delete') {
                const chars = current.split('');

                if (start !== end) {
                    // Keep mask intact: clear selected digits instead of deleting/shifting characters.
                    for (let pos = start; pos < end; pos += 1) {
                        if (pos >= 0 && pos <= 9 && pos !== 2 && pos !== 5) {
                            chars[pos] = '0';
                        }
                    }
                    const nextValue = chars.join('');
                    if (which === 'from') {
                        this.displayFrom = nextValue;
                    } else {
                        this.displayTo = nextValue;
                    }
                    input.value = nextValue;
                    event.preventDefault();
                    this.$nextTick(() => {
                        input.setSelectionRange(start, start);
                    });
                    return;
                }

                const targetPos = event.key === 'Backspace'
                    ? this.lastDigitPositionBefore(start)
                    : this.firstDigitPositionAtOrAfter(start);
                if (targetPos < 0) {
                    event.preventDefault();
                    return;
                }

                chars[targetPos] = '0';
                const nextValue = chars.join('');
                if (which === 'from') {
                    this.displayFrom = nextValue;
                } else {
                    this.displayTo = nextValue;
                }
                input.value = nextValue;
                event.preventDefault();
                this.$nextTick(() => {
                    input.setSelectionRange(targetPos, targetPos);
                });
                return;
            }

            if (!/^\d$/.test(event.key)) {
                return;
            }

            let replaceAt = start;
            // If a range is selected (for example whole month), overwrite from first digit in selection.
            if (start !== end) {
                replaceAt = this.firstDigitPositionAtOrAfter(start);
            } else if (replaceAt === 2 || replaceAt === 5) {
                replaceAt = this.firstDigitPositionAtOrAfter(replaceAt + 1);
            }

            if (replaceAt < 0 || replaceAt > 9 || replaceAt === 2 || replaceAt === 5) {
                event.preventDefault();
                return;
            }

            const chars = current.split('');
            chars[replaceAt] = event.key;
            const nextValue = chars.join('');

            if (which === 'from') {
                this.displayFrom = nextValue;
            } else {
                this.displayTo = nextValue;
            }
            input.value = nextValue;
            event.preventDefault();

            const nextPos = this.nextDigitPosition(replaceAt);
            this.$nextTick(() => {
                input.setSelectionRange(nextPos, nextPos);
            });
        },
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
                if (!this.dateFrom) {
                    this.displayFrom = '';
                    return;
                }
                this.$emit('update:dateFrom', '');
                this.$emit('change');
                return;
            }
            const iso = parseDdMmYyyyToIso(raw);
            if (!iso) {
                const toast = useToast();
                toast.error(
                    this.single
                        ? 'Date: use dd/mm/yyyy (e.g. 14/01/2026).'
                        : 'From date: use dd/mm/yyyy (e.g. 14/01/2026).',
                );
                this.displayFrom = this.isoToDisplay(this.dateFrom);
                return;
            }
            if (iso === (this.dateFrom || '')) {
                this.displayFrom = formatDateDdMmYyyy(iso);
                return;
            }
            this.$emit('update:dateFrom', iso);
            this.displayFrom = formatDateDdMmYyyy(iso);
            this.$emit('change');
        },
        commitTo() {
            const raw = (this.displayTo || '').trim();
            if (!raw) {
                if (!this.dateTo) {
                    this.displayTo = '';
                    return;
                }
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
            if (iso === (this.dateTo || '')) {
                this.displayTo = formatDateDdMmYyyy(iso);
                return;
            }
            this.$emit('update:dateTo', iso);
            this.displayTo = formatDateDdMmYyyy(iso);
            this.$emit('change');
        },
        onNativeFrom(e) {
            const v = e.target.value;
            if ((v || '') === (this.dateFrom || '')) {
                this.displayFrom = v ? formatDateDdMmYyyy(v) : '';
                return;
            }
            this.$emit('update:dateFrom', v || '');
            this.displayFrom = v ? formatDateDdMmYyyy(v) : '';
            this.$emit('change');
        },
        onNativeTo(e) {
            const v = e.target.value;
            if ((v || '') === (this.dateTo || '')) {
                this.displayTo = v ? formatDateDdMmYyyy(v) : '';
                return;
            }
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

.fc-dr__row--single .fc-dr__pick {
    max-width: 160px;
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
    padding: 0 34px 0 8px;
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

.fc-dr__picker-btn {
    position: absolute;
    right: 6px;
    top: 50%;
    transform: translateY(-50%);
    width: 22px;
    height: 22px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: none;
    border-radius: 6px;
    background: transparent;
    color: rgba(0, 0, 0, 0.65);
    cursor: pointer;
}

.fc-dr__picker-btn:hover {
    background: rgba(59, 130, 246, 0.12);
    color: rgba(0, 0, 0, 0.8);
}

.fc-dr__sep {
    flex-shrink: 0;
    padding-bottom: 8px;
    color: rgba(255, 255, 255, 0.45);
    font-size: 12px;
}
</style>

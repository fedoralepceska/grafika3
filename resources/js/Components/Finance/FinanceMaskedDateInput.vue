<template>
    <div class="fm-date" :class="`fm-date--${variant}`">
        <input
            ref="textInput"
            v-model="displayValue"
            type="text"
            class="fm-date__input"
            :class="inputClass"
            :placeholder="placeholder"
            inputmode="numeric"
            autocomplete="off"
            maxlength="10"
            pattern="\d{2}/\d{2}/\d{4}"
            @input="onDateTyping"
            @keydown="onDateKeydown"
            @keyup.enter="onEnter"
            @blur="commitInput"
        />
        <button
            type="button"
            class="fm-date__picker-btn"
            title="Open calendar"
            aria-label="Open calendar"
            @click="openPicker"
        >
            <i class="fas fa-calendar-alt" aria-hidden="true" />
        </button>
        <input
            ref="nativeInput"
            type="date"
            class="fm-date__anchor"
            :value="modelValue || ''"
            tabindex="-1"
            aria-hidden="true"
            @change="onNativePicked"
        />
    </div>
</template>

<script>
import { useToast } from 'vue-toastification';
import { parseDdMmYyyyToIso, formatDateDdMmYyyy, isoYmdToDdMmYyyy } from '@/utils/financeFilters';

export default {
    name: 'FinanceMaskedDateInput',
    props: {
        modelValue: { type: String, default: '' },
        placeholder: { type: String, default: 'dd/mm/yyyy' },
        inputClass: { type: String, default: '' },
        variant: { type: String, default: 'dark' },
    },
    emits: ['update:modelValue', 'submit', 'invalid'],
    data() {
        return {
            displayValue: '',
        };
    },
    watch: {
        modelValue: {
            immediate: true,
            handler() {
                this.displayValue = this.isoToDisplay(this.modelValue);
            },
        },
    },
    methods: {
        focusInput() {
            this.$refs.textInput?.focus?.();
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
        normalizeDateTyping(rawValue) {
            const digits = String(rawValue || '').replace(/\D/g, '').slice(0, 8);
            if (!digits) return '';
            if (digits.length <= 2) return digits;
            if (digits.length <= 4) return `${digits.slice(0, 2)}/${digits.slice(2)}`;
            return `${digits.slice(0, 2)}/${digits.slice(2, 4)}/${digits.slice(4)}`;
        },
        onDateTyping(event) {
            const normalized = this.normalizeDateTyping(event?.target?.value);
            this.displayValue = normalized;
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
        onDateKeydown(event) {
            if (event.metaKey || event.ctrlKey || event.altKey) return;
            const input = event.target;
            if (!input) return;
            const current = this.displayValue;
            if (!/^\d{2}\/\d{2}\/\d{4}$/.test(current || '')) return;
            const start = input.selectionStart;
            const end = input.selectionEnd;
            if (start == null || end == null) return;

            if (event.key === 'Backspace' || event.key === 'Delete') {
                const chars = current.split('');
                if (start !== end) {
                    for (let pos = start; pos < end; pos += 1) {
                        if (pos >= 0 && pos <= 9 && pos !== 2 && pos !== 5) {
                            chars[pos] = '0';
                        }
                    }
                    const nextValue = chars.join('');
                    this.displayValue = nextValue;
                    input.value = nextValue;
                    event.preventDefault();
                    this.$nextTick(() => input.setSelectionRange(start, start));
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
                this.displayValue = nextValue;
                input.value = nextValue;
                event.preventDefault();
                this.$nextTick(() => input.setSelectionRange(targetPos, targetPos));
                return;
            }

            if (!/^\d$/.test(event.key)) return;

            let replaceAt = start;
            // Typing at the end should replace the last year digit instead of being blocked.
            if (start === 10 && end === 10) {
                replaceAt = 9;
            }
            if (start !== end) {
                replaceAt = this.firstDigitPositionAtOrAfter(start);
            } else if (replaceAt === 2 || replaceAt === 5) {
                replaceAt = this.firstDigitPositionAtOrAfter(replaceAt + 1);
            }
            if (replaceAt === -1) {
                replaceAt = this.lastDigitPositionBefore(start + 1);
            }
            if (replaceAt < 0 || replaceAt > 9 || replaceAt === 2 || replaceAt === 5) {
                event.preventDefault();
                return;
            }

            const chars = current.split('');
            chars[replaceAt] = event.key;
            const nextValue = chars.join('');
            this.displayValue = nextValue;
            input.value = nextValue;
            event.preventDefault();
            const nextPos = this.nextDigitPosition(replaceAt);
            this.$nextTick(() => input.setSelectionRange(nextPos, nextPos));
        },
        commitInput() {
            const raw = (this.displayValue || '').trim();
            if (!raw) {
                if (this.modelValue) {
                    this.$emit('update:modelValue', '');
                }
                this.displayValue = '';
                return true;
            }
            const iso = parseDdMmYyyyToIso(raw);
            if (!iso) {
                const toast = useToast();
                toast.error('Use date as dd/mm/yyyy (e.g. 14/01/2026).');
                this.displayValue = this.isoToDisplay(this.modelValue);
                this.$emit('invalid');
                return false;
            }
            if (iso !== (this.modelValue || '')) {
                this.$emit('update:modelValue', iso);
            }
            this.displayValue = formatDateDdMmYyyy(iso);
            return true;
        },
        onEnter() {
            if (!this.commitInput()) return;
            this.$emit('submit');
        },
        onNativePicked(event) {
            const v = event.target.value || '';
            this.$emit('update:modelValue', v);
            this.displayValue = v ? formatDateDdMmYyyy(v) : '';
        },
        openPicker() {
            const el = this.$refs.nativeInput;
            if (!el) return;
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
.fm-date {
    position: relative;
    width: 100%;
    min-width: 0;
}

.fm-date__input {
    width: 100%;
    padding-right: 34px;
    min-height: 34px;
    padding-left: 8px;
    border-radius: 4px;
    border: 2px solid #3f8fd2;
    background: rgba(255, 255, 255, 0.1);
    color: #ffffff;
    font-size: 14px;
    font-weight: 600;
    box-sizing: border-box;
}

.fm-date__input::placeholder {
    color: rgba(255, 255, 255, 0.7);
}

.fm-date__input:focus {
    outline: none;
    border-color: #7ed957;
    box-shadow: 0 0 0 2px rgba(126, 217, 87, 0.25);
}

.fm-date__picker-btn {
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
    color: rgba(255, 255, 255, 0.86);
    cursor: pointer;
}

.fm-date__picker-btn:hover {
    background: rgba(255, 255, 255, 0.14);
}

.fm-date__anchor {
    position: absolute;
    width: 0;
    height: 0;
    opacity: 0;
    pointer-events: none;
}

.fm-date--light .fm-date__input {
    border: 1px solid rgba(0, 0, 0, 0.14);
    background: rgba(255, 255, 255, 0.98);
    color: #111827;
    border-radius: 10px;
    font-weight: 400;
}

.fm-date--light .fm-date__input::placeholder {
    color: #6b7280;
}

.fm-date--light .fm-date__input:focus {
    border-color: rgba(59, 130, 246, 0.75);
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}

.fm-date--light .fm-date__picker-btn {
    color: rgba(17, 24, 39, 0.6);
}

.fm-date--light .fm-date__picker-btn:hover {
    background: rgba(59, 130, 246, 0.12);
    color: rgba(17, 24, 39, 0.9);
}
</style>

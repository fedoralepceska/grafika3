/**
 * Shared helpers for finance list filters (date range normalization, URL sync).
 */

export function localISODate(d = new Date()) {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
}

export function firstDayOfMonthFromDateString(dateStr) {
    if (!dateStr || dateStr.length < 7) {
        return '';
    }
    return `${dateStr.slice(0, 7)}-01`;
}

/**
 * If only from is set, set to = today. If only to is set, set from = first of that month.
 * Mutates the provided object { dateFrom, dateTo } (string Y-m-d).
 */
export function normalizeDateRangeFields(state) {
    const today = localISODate();
    if (state.dateFrom && !state.dateTo) {
        state.dateTo = today;
    }
    if (state.dateTo && !state.dateFrom) {
        state.dateFrom = firstDayOfMonthFromDateString(state.dateTo);
    }
}

/** First and last calendar day of the month for `d` (local timezone), as Y-m-d strings. */
export function monthBoundsLocalISO(d = new Date()) {
    const y = d.getFullYear();
    const m = d.getMonth();
    const pad = (n) => String(n).padStart(2, '0');
    const last = new Date(y, m + 1, 0).getDate();
    return {
        dateFrom: `${y}-${pad(m + 1)}-01`,
        dateTo: `${y}-${pad(m + 1)}-${pad(last)}`,
    };
}

/**
 * Format a date (ISO string, Y-m-d, or Date) as dd/mm/yyyy. Returns 'N/A' if missing/invalid.
 */
export function formatDateDdMmYyyy(value) {
    if (value === null || value === undefined || value === '') {
        return 'N/A';
    }
    if (typeof value === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(value.trim())) {
        return isoYmdToDdMmYyyy(value.trim());
    }
    const d = value instanceof Date ? value : new Date(value);
    if (Number.isNaN(d.getTime())) {
        return 'N/A';
    }
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    return `${day}/${month}/${year}`;
}

/** Calendar `yyyy-mm-dd` → `dd/mm/yyyy` (no timezone shift). */
export function isoYmdToDdMmYyyy(ymd) {
    if (!ymd || typeof ymd !== 'string') {
        return '';
    }
    const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(ymd.trim());
    if (!m) {
        return '';
    }
    return `${m[3]}/${m[2]}/${m[1]}`;
}

/**
 * Parse `dd/mm/yyyy` or `dd.mm.yyyy` (day/month 1–2 digits) → `yyyy-mm-dd` or null if invalid.
 */
export function parseDdMmYyyyToIso(str) {
    const trimmed = (str || '').trim().replace(/\s+/g, '');
    const m = /^(\d{1,2})[./](\d{1,2})[./](\d{4})$/.exec(trimmed);
    if (!m) {
        return null;
    }
    const day = parseInt(m[1], 10);
    const month = parseInt(m[2], 10);
    const year = parseInt(m[3], 10);
    if (year < 1900 || year > 2100 || month < 1 || month > 12 || day < 1 || day > 31) {
        return null;
    }
    const d = new Date(year, month - 1, day);
    if (d.getFullYear() !== year || d.getMonth() !== month - 1 || d.getDate() !== day) {
        return null;
    }
    return localISODate(d);
}

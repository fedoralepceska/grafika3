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

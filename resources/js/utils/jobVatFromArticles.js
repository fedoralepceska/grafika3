/** Aligns with invoices/outgoing_invoice_v2 and FakturaListTotalsService rateMap */
export const VAT_RATE_MAP = { 1: 18, 2: 5, 3: 10 };

/**
 * @returns {{ kind: 'single', rate: number } | { kind: 'mixed' } | { kind: 'default', rate: number }}
 */
export function getEffectiveVatFromArticles(job) {
    const articles = (job && job.articles) || [];
    const rates = new Set();
    for (let i = 0; i < articles.length; i++) {
        const art = articles[i];
        const tt = Number(art.tax_type ?? 0);
        if (VAT_RATE_MAP[tt] !== undefined) {
            rates.add(VAT_RATE_MAP[tt]);
        }
    }
    if (rates.size === 0) {
        return { kind: 'default', rate: 18 };
    }
    if (rates.size === 1) {
        return { kind: 'single', rate: [...rates][0] };
    }
    return { kind: 'mixed' };
}

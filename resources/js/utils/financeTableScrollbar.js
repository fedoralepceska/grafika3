/**
 * Vertical scrollbar width on a scroll container. The subtotal strip sits outside
 * that box, so its table must use padding-inline-end = this value to match column widths.
 */
export function measureVerticalScrollbarWidth(el) {
    if (!el || typeof el.offsetWidth !== 'number') {
        return 0;
    }
    return Math.max(0, el.offsetWidth - el.clientWidth);
}

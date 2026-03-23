<template>
    <div
        class="data-table-shell"
        :class="{ compact, 'data-table-shell--grid': variant === 'grid' }"
    >
        <div v-if="caption || $slots.caption" class="table-caption">
            <slot name="caption">
                {{ caption }}
            </slot>
        </div>

        <div class="table-scroll">
            <table class="data-table">
                <thead v-if="$slots.header" class="data-table-head">
                    <slot name="header" />
                </thead>
                <tbody class="data-table-body">
                    <slot />
                </tbody>
                <tfoot v-if="$slots.footer" class="data-table-foot">
                    <slot name="footer" />
                </tfoot>
            </table>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        caption: {
            type: String,
            default: '',
        },
        compact: {
            type: Boolean,
            default: false,
        },
        /**
         * 'grid' — dense cells with visible borders (spreadsheet-style), same palette as finance tables.
         * 'default' — softer rows, bottom border only (current behaviour).
         */
        variant: {
            type: String,
            default: 'default',
            validator: (v) => ['default', 'grid'].includes(v),
        },
    },
};
</script>

<style scoped lang="scss">
.data-table-shell {
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    overflow: hidden;
    background: rgba(8, 15, 26, 0.55);
    box-shadow: 0 14px 30px rgba(0, 0, 0, 0.14);
}

.table-caption {
    padding: 12px 16px 0;
    font-size: 13px;
    color: rgba(255, 255, 255, 0.68);
}

.table-scroll {
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    color: $white;
}

.data-table-head :deep(th) {
    padding: 14px 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(255, 255, 255, 0.05);
    color: rgba(255, 255, 255, 0.72);
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    text-align: left;
    white-space: nowrap;
}

.data-table-head :deep(th.text-right),
.data-table-head :deep(th.actions-header),
.data-table-head :deep(th.align-right) {
    text-align: right;
}

.data-table-body :deep(td) {
    padding: 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    vertical-align: middle;
}

.data-table-body :deep(td.text-right),
.data-table-body :deep(td.align-right) {
    text-align: right;
}

.data-table-body :deep(tr:last-child td) {
    border-bottom: none;
}

.data-table-body :deep(tr:nth-child(even)) {
    background: rgba(255, 255, 255, 0.02);
}

.data-table-body :deep(tr:hover) {
    background: rgba(255, 255, 255, 0.05);
}

.data-table-foot :deep(td) {
    padding: 14px 16px;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(255, 255, 255, 0.04);
    font-weight: 700;
}

.data-table-shell.compact .data-table-head :deep(th) {
    padding: 10px 14px;
}

.data-table-shell.compact .data-table-body :deep(td) {
    padding: 10px 14px;
}

.data-table-shell.compact .data-table-foot :deep(td) {
    padding: 10px 14px;
}

/* ─── Dense “grid” variant (professional spreadsheet feel, dark-theme colours) ─── */
.data-table-shell--grid {
    background: rgba(6, 12, 20, 0.75);
}

.data-table-shell--grid .data-table {
    border-collapse: collapse;
    border: 1px solid rgba(255, 255, 255, 0.12);
    font-variant-numeric: tabular-nums;
}

.data-table-shell--grid .data-table-head :deep(th) {
    padding: 8px 10px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(255, 255, 255, 0.07);
    color: rgba(255, 255, 255, 0.78);
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    line-height: 1.3;
    white-space: nowrap;
}

.data-table-shell--grid .data-table-body :deep(td) {
    padding: 6px 10px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    font-size: 12px;
    line-height: 1.35;
    vertical-align: middle;
}

.data-table-shell--grid .data-table-body :deep(tr:nth-child(even)) {
    background: rgba(255, 255, 255, 0.035);
}

.data-table-shell--grid .data-table-body :deep(tr:hover) {
    background: rgba(59, 130, 246, 0.1);
}

.data-table-shell--grid .data-table-body :deep(tr:last-child td) {
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

/* Match header/body grid lines so footer rows (e.g. subtotals) align vertically */
.data-table-shell--grid .data-table-foot :deep(td) {
    padding: 6px 10px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    font-size: 12px;
    line-height: 1.35;
    vertical-align: middle;
    background: rgba(255, 255, 255, 0.06);
}

.data-table-shell--grid.compact .data-table-head :deep(th) {
    padding: 7px 9px;
}

.data-table-shell--grid.compact .data-table-body :deep(td) {
    padding: 5px 9px;
}

.data-table-shell--grid.compact .data-table-foot :deep(td) {
    padding: 5px 9px;
}
</style>

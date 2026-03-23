<template>
    <div v-if="showBar" class="finance-show-more">
        <p v-if="totalCount > 0" class="finance-show-more-meta">
            Showing {{ loadedCount }} of {{ totalCount }}
        </p>
        <button
            v-if="hasMore"
            type="button"
            class="finance-show-more-btn"
            :disabled="loadingMore"
            @click="$emit('load-more')"
        >
            <i v-if="loadingMore" class="fa fa-spinner fa-spin" aria-hidden="true" />
            <span v-if="loadingMore">Loading…</span>
            <span v-else>Show more ({{ chunkSize }})</span>
        </button>
        <p v-else-if="totalCount > 0 && loadedCount > 0" class="finance-show-more-end">All rows loaded</p>
    </div>
</template>

<script>
export default {
    name: 'FinanceShowMore',
    props: {
        pagination: {
            type: Object,
            default: null,
        },
        loadingMore: {
            type: Boolean,
            default: false,
        },
        chunkSize: {
            type: Number,
            default: 20,
        },
    },
    emits: ['load-more'],
    computed: {
        loadedCount() {
            return this.pagination?.data?.length ?? 0;
        },
        totalCount() {
            return this.pagination?.total ?? 0;
        },
        hasMore() {
            const p = this.pagination;
            if (!p || !p.data) {
                return false;
            }
            return p.current_page < p.last_page;
        },
        showBar() {
            return this.totalCount > 0 || this.hasMore;
        },
    },
};
</script>

<style scoped lang="scss">
.finance-show-more {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    padding: 16px 12px 8px;
    text-align: center;
}

.finance-show-more-meta {
    margin: 0;
    font-size: 12px;
    color: rgba(255, 255, 255, 0.55);
}

.finance-show-more-end {
    margin: 0;
    font-size: 12px;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.45);
}

.finance-show-more-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-width: 200px;
    padding: 10px 20px;
    border-radius: 10px;
    border: 1px solid rgba(96, 165, 250, 0.45);
    background: rgba(37, 99, 235, 0.2);
    color: #e8f0fe;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.03em;
    cursor: pointer;
    transition:
        background 0.15s ease,
        border-color 0.15s ease,
        color 0.15s ease;
}

.finance-show-more-btn:hover:not(:disabled) {
    background: rgba(37, 99, 235, 0.35);
    border-color: rgba(147, 197, 253, 0.65);
    color: #fff;
}

.finance-show-more-btn:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}
</style>

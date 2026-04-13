<template>
    <Teleport to="body">
        <div v-if="modelValue" class="nid-portal" role="presentation">
            <div class="nid-scrim" aria-hidden="true" @click="close" />
            <aside
                class="nid-panel"
                role="dialog"
                aria-modal="true"
                aria-labelledby="nid-drawer-title"
                :style="{ width: `${drawerWidthPx}px` }"
                @click.stop
            >
                <div class="nid-layout">
                    <header class="nid-head">
                        <div class="nid-head-text">
                            <h2 id="nid-drawer-title" class="nid-title">
                                {{ displayTitle }}
                            </h2>
                            <p v-if="subtitle" class="nid-subtitle">{{ subtitle }}</p>
                        </div>
                        <div class="nid-head-actions">
                            <button
                                v-if="effectiveId"
                                type="button"
                                class="nid-pdf-btn"
                                title="Open order PDF (hardcopy)"
                                @click="generatePdf"
                            >
                                <i class="fa fa-file-pdf-o" aria-hidden="true" />
                                <span>PDF</span>
                            </button>
                            <button type="button" class="nid-close" aria-label="Close drawer" @click="close">
                                &times;
                            </button>
                        </div>
                    </header>

                    <div class="nid-scroll">
                        <div v-if="detailLoading" class="nid-loading">
                            <i class="fa fa-spinner fa-spin" aria-hidden="true" />
                            <span>Loading order…</span>
                        </div>
                        <div v-else-if="detailError" class="nid-error">{{ detailError }}</div>
                        <template v-else-if="detail">
                            <div class="nid-meta-bar">
                                <span
                                    class="nid-status-badge"
                                    :class="statusBadgeClass(detail.status)"
                                >
                                    {{ detail.status }}
                                </span>
                                <div class="nid-meta-grid">
                                    <div class="nid-meta-item">
                                        <span class="nid-meta-label">Order</span>
                                        <span class="nid-meta-value">#{{ detail.order_number }}/{{ detail.fiscal_year }}</span>
                                    </div>
                                    <div class="nid-meta-item nid-meta-item--grow">
                                        <span class="nid-meta-label">Customer</span>
                                        <span class="nid-meta-value">{{ detail.client?.name || '—' }}</span>
                                    </div>
                                    <div class="nid-meta-item">
                                        <span class="nid-meta-label">Start date</span>
                                        <span class="nid-meta-value">{{ formatCellDate(detail.start_date) }}</span>
                                    </div>
                                    <div class="nid-meta-item">
                                        <span class="nid-meta-label">End date</span>
                                        <span class="nid-meta-value">{{ formatCellDate(detail.end_date) }}</span>
                                    </div>
                                    <div class="nid-meta-item">
                                        <span class="nid-meta-label">Created by</span>
                                        <span class="nid-meta-value">{{ detail.user?.name || '—' }}</span>
                                    </div>
                                </div>
                            </div>

                            <div v-if="detail.LockedNote" class="nid-lock">
                                <span class="nid-lock-label">Lock note</span>
                                <span class="nid-lock-text">{{ detail.LockedNote }}</span>
                            </div>

                            <h3 class="nid-orderlines-title">OrderLines</h3>
                            <div
                                v-for="(job, index) in detail.jobs || []"
                                :key="job.id"
                                class="nid-job-wrap"
                            >
                            <div class="nid-job-chip">#{{ index + 1 }} {{ job.name || '' }}</div>

                                <div class="nid-job-card">
                                    <div class="nid-job-body">
                                        <div class="nid-job-col nid-job-col--files">
                                        <div
                                            class="nid-files-grid"
                                            :class="{ 'nid-files-grid--single': jobFileEntries(job).length <= 1 }"
                                        >
                                            <div
                                                v-for="(entry, fi) in jobFileEntries(job)"
                                                :key="fi"
                                                class="nid-dim-box"
                                            >
                                                <div
                                                    v-if="entry.label"
                                                    class="nid-file-name"
                                                    :title="entry.label"
                                                >
                                                    {{ truncateFileLabel(entry.label) }}
                                                </div>
                                                <template v-if="entry.legacyEmpty">
                                                    <div class="nid-dim-placeholder nid-dim-placeholder--empty">
                                                        <i class="fa fa-file-o" aria-hidden="true" />
                                                        <span>No files</span>
                                                    </div>
                                                    <div class="nid-dim-lines">
                                                        <div>
                                                            Height:
                                                            <strong>{{ entry.height }}</strong>
                                                            mm
                                                        </div>
                                                        <div>
                                                            Width:
                                                            <strong>{{ entry.width }}</strong>
                                                            mm
                                                        </div>
                                                    </div>
                                                </template>
                                                <template v-else>
                                                    <div class="nid-dim-placeholder">
                                                        <i class="fa fa-file-o" aria-hidden="true" />
                                                        <span>{{ entry.label ? 'File' : 'Files' }}</span>
                                                    </div>
                                                    <div class="nid-dim-lines">
                                                        <div>
                                                            Height:
                                                            <strong>{{ entry.height }}</strong>
                                                            mm
                                                        </div>
                                                        <div>
                                                            Width:
                                                            <strong>{{ entry.width }}</strong>
                                                            mm
                                                        </div>
                                                        <div
                                                            v-if="entry.fileAreaDisplay"
                                                            class="nid-dim-file-area"
                                                        >
                                                            File m²:
                                                            <strong class="nid-area">{{ entry.fileAreaDisplay }}</strong>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                        </div>
                                        <div class="nid-job-col nid-job-col--mid">
                                        <div>Machine print: <strong>{{ job.machinePrint || '—' }}</strong></div>
                                        <div>Machine cut: <strong>{{ job.machineCut || '—' }}</strong></div>
                                        <div>Quantity: <strong>{{ job.quantity ?? '—' }}</strong></div>
                                        <div>Copies: <strong>{{ job.copies ?? '—' }}</strong></div>
                                        </div>
                                        <div class="nid-job-col nid-job-col--right">
                                        <div>
                                            Material type:
                                            <strong>{{ materialTypeLabel(job) }}</strong>
                                        </div>
                                        <div>
                                            Department:
                                            <strong>{{ departmentLabel(job) }}</strong>
                                        </div>
                                        <div>
                                            Total m²:
                                            <strong class="nid-area">{{ formatArea(getJobTotalArea(job)) }}</strong>
                                        </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <footer class="nid-foot">
                        <button type="button" class="nid-btn nid-btn--muted" @click="close">Close</button>
                        <button type="button" class="nid-btn nid-btn--primary" @click="openFullPage">
                            <i class="fa fa-external-link" aria-hidden="true" />
                            View in new tab
                        </button>
                    </footer>
                </div>
            </aside>
        </div>
    </Teleport>
</template>

<script>
import axios from 'axios';
import { useToast } from 'vue-toastification';
import { formatDateDdMmYyyy } from '@/utils/financeFilters';

export default {
    name: 'NotInvoicedOrderDrawer',
    props: {
        modelValue: {
            type: Boolean,
            default: false,
        },
        invoice: {
            type: Object,
            default: null,
        },
    },
    emits: ['update:modelValue'],
    data() {
        return {
            drawerWidthPx: 960,
            detail: null,
            detailLoading: false,
            detailError: null,
        };
    },
    computed: {
        effectiveId() {
            return this.invoice?.id ?? this.detail?.id ?? null;
        },
        displayTitle() {
            if (this.detail?.order_number != null) {
                return `Order #${this.detail.order_number}`;
            }
            if (this.invoice?.order_number != null) {
                return `Order #${this.invoice.order_number}`;
            }
            return 'Order';
        },
        subtitle() {
            return this.detail?.invoice_title || this.invoice?.invoice_title || '';
        },
    },
    watch: {
        modelValue(open) {
            if (open) {
                document.body.style.overflow = 'hidden';
                window.addEventListener('keydown', this.onGlobalEscape, true);
                this.loadDetails();
            } else {
                document.body.style.overflow = '';
                window.removeEventListener('keydown', this.onGlobalEscape, true);
                this.detail = null;
                this.detailError = null;
                this.detailLoading = false;
            }
        },
    },
    mounted() {
        this.updateDrawerWidth();
        window.addEventListener('resize', this.updateDrawerWidth, { passive: true });
    },
    beforeUnmount() {
        document.body.style.overflow = '';
        window.removeEventListener('resize', this.updateDrawerWidth);
        window.removeEventListener('keydown', this.onGlobalEscape, true);
    },
    methods: {
        onGlobalEscape(e) {
            if (e.key === 'Escape') {
                this.close();
            }
        },
        updateDrawerWidth() {
            this.drawerWidthPx = Math.min(960, Math.max(300, window.innerWidth - 24));
        },
        close() {
            this.$emit('update:modelValue', false);
        },
        async loadDetails() {
            if (!this.invoice?.id) {
                this.detailError = 'Missing order id.';
                return;
            }
            this.detailLoading = true;
            this.detailError = null;
            this.detail = null;
            try {
                const { data } = await axios.get(`/orders/${this.invoice.id}/details`);
                this.detail = data;
            } catch (e) {
                console.error(e);
                this.detailError = 'Could not load order details.';
                const toast = useToast();
                toast.error(this.detailError);
            } finally {
                this.detailLoading = false;
            }
        },
        generatePdf() {
            const id = this.effectiveId;
            if (!id) {
                return;
            }
            window.open(`/orders/${id}/pdf`, '_blank', 'noopener,noreferrer');
        },
        openFullPage() {
            const id = this.effectiveId;
            if (!id) {
                return;
            }
            window.open(`/orders/${id}`, '_blank', 'noopener,noreferrer');
        },
        formatCellDate(value) {
            if (!value) {
                return '—';
            }
            const s = formatDateDdMmYyyy(value);
            return s === 'N/A' ? '—' : s;
        },
        statusBadgeClass(status) {
            const s = (status || '').toLowerCase();
            if (s.includes('completed')) {
                return 'nid-status-badge--done';
            }
            if (s.includes('progress')) {
                return 'nid-status-badge--progress';
            }
            if (s.includes('not started')) {
                return 'nid-status-badge--pending';
            }
            return '';
        },
        getJobTotalArea(job) {
            if (job.dimensions_breakdown && Array.isArray(job.dimensions_breakdown) && job.dimensions_breakdown.length > 0) {
                let total = 0;
                for (const fileData of job.dimensions_breakdown) {
                    if (fileData.total_area_m2 && typeof fileData.total_area_m2 === 'number') {
                        total += fileData.total_area_m2;
                    }
                }
                return total;
            }
            return job.computed_total_area_m2 || 0;
        },
        formatArea(n) {
            const x = Number(n);
            if (!Number.isFinite(x)) {
                return '0.0000';
            }
            return x.toFixed(4);
        },
        formatMm(val) {
            if (val != null && typeof val === 'number') {
                return val.toFixed(2);
            }
            return '0.00';
        },
        jobFileEntries(job) {
            const br = job.dimensions_breakdown;
            if (br && Array.isArray(br) && br.length > 0) {
                return br.map((fileData, fileIndex) => {
                    const pages = fileData.page_dimensions;
                    let h = 0;
                    let w = 0;
                    if (pages && Array.isArray(pages) && pages[0]) {
                        const p = pages[0];
                        h = p.height_mm != null ? Number(p.height_mm) : 0;
                        w = p.width_mm != null ? Number(p.width_mm) : 0;
                    }
                    const height = Number.isFinite(h) ? h.toFixed(2) : '0.00';
                    const width = Number.isFinite(w) ? w.toFixed(2) : '0.00';
                    const ta = fileData.total_area_m2;
                    let fileAreaDisplay = '';
                    if (typeof ta === 'number' && Number.isFinite(ta)) {
                        fileAreaDisplay = ta.toFixed(4);
                    } else if (ta != null && ta !== '') {
                        const n = parseFloat(ta);
                        if (Number.isFinite(n)) {
                            fileAreaDisplay = n.toFixed(4);
                        }
                    }
                    return {
                        legacyEmpty: false,
                        label: fileData.filename || `File ${fileIndex + 1}`,
                        height,
                        width,
                        fileAreaDisplay,
                    };
                });
            }
            return [
                {
                    legacyEmpty: true,
                    label: null,
                    height: this.formatMm(job.height),
                    width: this.formatMm(job.width),
                },
            ];
        },
        truncateFileLabel(s, maxLen = 26) {
            if (!s || typeof s !== 'string') {
                return '';
            }
            if (s.length <= maxLen) {
                return s;
            }
            const inner = maxLen - 1;
            const head = Math.ceil(inner / 2);
            const tail = Math.floor(inner / 2);
            return `${s.slice(0, head)}…${s.slice(s.length - tail)}`;
        },
        hasLargeFormat(job) {
            if (job.articles && job.articles.length > 0) {
                return job.articles.some((a) => a.largeFormatMaterial);
            }
            return !!job.large_material;
        },
        hasSmallFormat(job) {
            if (job.articles && job.articles.length > 0) {
                return job.articles.some((a) => a.smallMaterial);
            }
            return !!job.small_material;
        },
        departmentLabel(job) {
            if (job.articles && job.articles.length > 0) {
                if (this.hasLargeFormat(job) && this.hasSmallFormat(job)) {
                    return 'Large Format, Small Format';
                }
                if (this.hasLargeFormat(job)) {
                    return 'Large Format';
                }
                if (this.hasSmallFormat(job)) {
                    return 'Small Format';
                }
                return 'Mixed Format';
            }
            if (job.small_material) {
                return 'Small Format';
            }
            if (job.large_material) {
                return 'Large Format';
            }
            return 'Mixed Format';
        },
        materialTypeLabel(job) {
            if (job.articles && job.articles.length > 0) {
                const parts = [];
                for (const article of job.articles) {
                    if (article.categories && article.categories.length > 0) {
                        parts.push(...article.categories.map((c) => c.name));
                    } else if (article.largeFormatMaterial) {
                        parts.push(article.largeFormatMaterial.name);
                    } else if (article.smallMaterial) {
                        parts.push(article.smallMaterial.name);
                    } else if (article.name) {
                        parts.push(article.name);
                    }
                }
                return parts.length ? parts.join(', ') : '—';
            }
            if (job.large_material_id && job.large_material?.name) {
                return job.large_material.name;
            }
            if (job.small_material?.name) {
                return job.small_material.name;
            }
            return '—';
        },
    },
};
</script>

<style scoped lang="scss">
.nid-portal {
    position: fixed;
    inset: 0;
    z-index: 3050;
    pointer-events: auto;
}

.nid-scrim {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
}

.nid-panel {
    position: absolute;
    top: 0;
    right: 0;
    bottom: 0;
    max-width: calc(100vw - 16px);
    display: flex;
    flex-direction: column;
    background: #1a2332;
    color: rgba(255, 255, 255, 0.92);
    box-shadow: -12px 0 48px rgba(0, 0, 0, 0.4);
    overflow: hidden;
}

.nid-layout {
    display: flex;
    flex-direction: column;
    min-height: 0;
    flex: 1 1 auto;
    height: 100%;
}

.nid-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    padding: 14px 16px 12px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    flex-shrink: 0;
}

.nid-head-text {
    min-width: 0;
}

.nid-head-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
}

.nid-title {
    margin: 0 0 4px;
    font-size: 1.1rem;
    font-weight: 700;
    color: $white;
    line-height: 1.25;
}

.nid-subtitle {
    margin: 0;
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.6);
    word-break: break-word;
}

.nid-pdf-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    min-height: 36px;
    padding: 0 12px;
    border-radius: 8px;
    border: 1px solid rgba(248, 113, 113, 0.45);
    background: rgba(185, 28, 28, 0.35);
    color: #fecaca;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    cursor: pointer;
    transition: filter 0.15s ease, background 0.15s ease;
}

.nid-pdf-btn:hover {
    filter: brightness(1.08);
    background: rgba(185, 28, 28, 0.5);
}

.nid-close {
    width: 36px;
    height: 36px;
    margin: 0;
    border: none;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.08);
    color: rgba(255, 255, 255, 0.9);
    font-size: 22px;
    line-height: 1;
    cursor: pointer;
    transition: background 0.15s ease;
}

.nid-close:hover {
    background: rgba(255, 255, 255, 0.16);
}

.nid-scroll {
    flex: 1 1 auto;
    min-height: 0;
    overflow-y: auto;
    padding: 14px 16px 18px;
    -webkit-overflow-scrolling: touch;
}

.nid-loading,
.nid-error {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 24px 8px;
    color: rgba(255, 255, 255, 0.75);
    font-size: 14px;
}

.nid-error {
    color: #fca5a5;
}

.nid-meta-bar {
    background: #e8ecf1;
    color: #111827;
    border-radius: 10px;
    padding: 12px 14px 14px;
    margin-bottom: 12px;
}

.nid-status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    margin-bottom: 10px;
    background: #64748b;
    color: #fff;
}

.nid-status-badge--done {
    background: #15803d;
}

.nid-status-badge--progress {
    background: #1d4ed8;
}

.nid-status-badge--pending {
    background: #a16207;
}

.nid-meta-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 12px 20px;
    align-items: flex-start;
}

.nid-meta-item {
    min-width: 0;
}

.nid-meta-item--grow {
    flex: 1 1 200px;
}

.nid-meta-label {
    display: block;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #64748b;
    margin-bottom: 2px;
}

.nid-meta-value {
    font-size: 14px;
    font-weight: 700;
    word-break: break-word;
}

.nid-lock {
    margin-bottom: 14px;
    padding: 10px 12px;
    border-radius: 8px;
    background: rgba(0, 0, 0, 0.25);
    border: 1px solid rgba(255, 255, 255, 0.12);
    font-size: 13px;
}

.nid-lock-label {
    display: block;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: rgba(255, 255, 255, 0.5);
    margin-bottom: 4px;
}

.nid-lock-text {
    color: rgba(255, 255, 255, 0.88);
    white-space: pre-wrap;
}

.nid-orderlines-title {
    margin: 0 0 10px;
    padding: 8px 4px;
    font-size: 15px;
    font-weight: 700;
    color: rgba(255, 255, 255, 0.95);
    border-bottom: 1px solid rgba(255, 255, 255, 0.12);
}

.nid-job-wrap {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    margin-bottom: 14px;
    border-radius: 10px;
    overflow: hidden;
    &:first-child {
        margin-top: 12px;
    }

    &:not(:first-child) {
        margin-top: 16px;
    }
}

.nid-job-chip {
    flex: 0 0 auto;
    max-width: 100%;
    margin: 0;
    padding: 8px 14px 0;
    border: 0;
    border-bottom: 0;
    border-radius: 8px 8px 0 0;
    background: #7dc068;
    color: #0f172a;
    font-weight: 700;
    font-size: 14px;
    line-height: 1.35;
    word-break: break-word;
}

.nid-job-card {
    align-self: stretch;
    width: 100%;
    box-sizing: border-box;
    margin: 0;
    padding: 10px 12px 12px;
    border: 0;
    border-radius: 0;
    background: #243044;
}

.nid-job-body {
    display: grid;
    grid-template-columns: minmax(140px, 220px) 1fr 1fr;
    column-gap: 16px;
    row-gap: 0;
    padding: 0;
    align-items: start;
}

@media (max-width: 720px) {
    .nid-job-body {
        grid-template-columns: 1fr;
    }
}

.nid-files-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(112px, 1fr));
    gap: 8px;
    align-content: start;
}

.nid-files-grid--single {
    grid-template-columns: 1fr;
}

.nid-file-name {
    margin-bottom: 6px;
    font-size: 11px;
    font-weight: 600;
    line-height: 1.25;
    color: rgba(255, 255, 255, 0.72);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.nid-dim-file-area {
    margin-top: 6px;
    padding-top: 6px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    font-size: 11px;
    color: rgba(255, 255, 255, 0.82);
}

.nid-dim-box {
    background: rgba(0, 0, 0, 0.25);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 8px;
    padding: 10px;
    min-height: 108px;
}

.nid-dim-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    color: rgba(255, 255, 255, 0.55);
    font-size: 12px;
    margin-bottom: 8px;
}

.nid-dim-placeholder--empty {
    color: rgba(255, 255, 255, 0.45);
}

.nid-dim-lines {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.85);
    line-height: 1.5;
}

.nid-job-col--files {
    min-width: 0;
}

.nid-job-col--mid,
.nid-job-col--right {
    font-size: 13px;
    line-height: 1.55;
    color: rgba(255, 255, 255, 0.88);
}

.nid-job-col--mid strong,
.nid-job-col--right strong {
    color: #fff;
    font-weight: 600;
}

.nid-area {
    color: #86efac !important;
}

.nid-foot {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 10px;
    padding: 12px 16px 14px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    flex-shrink: 0;
    background: #1a2332;
}

.nid-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 38px;
    padding: 0 16px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    cursor: pointer;
    border: 1px solid transparent;
    transition: filter 0.15s ease, background 0.15s ease;
}

.nid-btn--muted {
    background: rgba(255, 255, 255, 0.08);
    color: rgba(255, 255, 255, 0.92);
    border-color: rgba(255, 255, 255, 0.18);
}

.nid-btn--muted:hover {
    background: rgba(255, 255, 255, 0.12);
}

.nid-btn--primary {
    background: $blue;
    color: $white;
}

.nid-btn--primary:hover {
    filter: brightness(1.06);
}
</style>

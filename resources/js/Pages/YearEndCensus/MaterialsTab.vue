<template>
    <div class="materials-tab">
        <!-- Year Selector -->
        <div class="year-selector">
            <label>Fiscal Year:</label>
            <select v-model="selectedYear" @change="loadSummary" class="year-select">
                <option v-for="year in availableYears" :key="year" :value="year">
                    {{ year }}
                </option>
            </select>
        </div>

        <div v-if="loading" class="loading">
            <i class="fa fa-spinner fa-spin"></i> Loading...
        </div>

        <template v-else-if="summary">
            <!-- Summary Stats -->
            <div class="summary-card">
                <div class="summary-header">
                    <h3>
                        Materials Summary for {{ selectedYear }}
                        <span v-if="summary.isClosed" class="year-status closed">
                            <i class="fa fa-lock"></i> Closed
                        </span>
                        <span v-else class="year-status open">
                            <i class="fa fa-unlock"></i> Open
                        </span>
                    </h3>
                </div>
                <div class="stats-grid">
                    <div class="stat">
                        <span class="label">Total Articles:</span>
                        <span class="value">{{ summary.stats.total_articles }}</span>
                    </div>
                    <div class="stat">
                        <span class="label">Total Imported:</span>
                        <span class="value imported">{{ formatNumber(summary.stats.total_imported) }}</span>
                    </div>
                    <div class="stat">
                        <span class="label">Total Used:</span>
                        <span class="value used">{{ formatNumber(summary.stats.total_used) }}</span>
                    </div>
                    <div class="stat">
                        <span class="label">Receipts:</span>
                        <span class="value">{{ summary.stats.receipts_count }}</span>
                    </div>
                    <div class="stat">
                        <span class="label">Realizations:</span>
                        <span class="value">{{ summary.stats.realizations_count }}</span>
                    </div>
                </div>
            </div>

            <!-- Materials List -->
            <div class="materials-list-section">
                <div class="section-header">
                    <h3>Materials Balance ({{ summary.materials.length }})</h3>
                    <input 
                        v-model="searchQuery" 
                        type="text" 
                        placeholder="Search materials..."
                        class="search-input"
                    />
                </div>
                <div class="materials-list" v-if="filteredMaterials.length">
                    <div class="material-header">
                        <span class="col-code">Code</span>
                        <span class="col-name">Article Name</span>
                        <span class="col-imported">Imported</span>
                        <span class="col-used">Used</span>
                        <span class="col-balance">Balance</span>
                        <span class="col-adjustment" v-if="!summary.isClosed">Adjustment</span>
                    </div>
                    <div v-for="material in filteredMaterials" :key="material.article_id" class="material-item">
                        <span class="col-code">{{ material.article_code || '-' }}</span>
                        <span class="col-name">{{ material.article_name }}</span>
                        <span class="col-imported">{{ formatNumber(material.imported) }}</span>
                        <span class="col-used">{{ formatNumber(material.used) }}</span>
                        <span class="col-balance" :class="material.balance >= 0 ? 'positive' : 'negative'">
                            {{ formatNumber(material.balance) }}
                        </span>
                        <span class="col-adjustment" v-if="!summary.isClosed">
                            <input 
                                v-model.number="material.adjustment"
                                type="number"
                                step="0.01"
                                class="adjustment-input"
                                placeholder="0"
                            />
                        </span>
                    </div>
                </div>
                <div v-else class="no-materials">
                    <p>No materials found for {{ selectedYear }}</p>
                </div>
            </div>

            <!-- Actions -->
            <div class="actions">
                <button class="btn export-btn" @click="exportSummary" :disabled="exporting">
                    <i v-if="exporting" class="fa fa-spinner fa-spin"></i>
                    {{ exporting ? 'Exporting...' : 'Export PDF' }}
                </button>
                <button 
                    v-if="!summary.isClosed"
                    class="btn close-btn" 
                    @click="closeYear" 
                    :disabled="closing"
                >
                    <i v-if="closing" class="fa fa-spinner fa-spin"></i>
                    {{ closing ? 'Closing...' : 'Close Year & Apply Adjustments' }}
                </button>
            </div>

            <!-- Closure Info -->
            <div v-if="summary.closure" class="closure-info">
                <h4>Year Closure Information</h4>
                <p><strong>Closed on:</strong> {{ formatDate(summary.closure.closed_at) }}</p>
                <p><strong>Closed by:</strong> {{ summary.closure.closed_by_user?.name || 'Unknown' }}</p>
                <p v-if="summary.closure.summary?.adjusted_count">
                    <strong>Adjustments made:</strong> {{ summary.closure.summary.adjusted_count }} materials
                </p>
            </div>
        </template>
    </div>
</template>

<script>
import axios from 'axios';
import { useToast } from 'vue-toastification';

export default {
    props: {
        availableYears: {
            type: Array,
            default: () => []
        }
    },
    data() {
        return {
            selectedYear: null,
            summary: null,
            loading: false,
            exporting: false,
            closing: false,
            searchQuery: '',
        };
    },
    computed: {
        filteredMaterials() {
            if (!this.summary?.materials) return [];
            if (!this.searchQuery) return this.summary.materials;
            
            const query = this.searchQuery.toLowerCase();
            return this.summary.materials.filter(m => 
                m.article_name.toLowerCase().includes(query) ||
                (m.article_code && m.article_code.toLowerCase().includes(query))
            );
        }
    },
    mounted() {
        const currentYear = new Date().getFullYear();
        this.selectedYear = this.availableYears.includes(currentYear) 
            ? currentYear 
            : this.availableYears[0] || currentYear;
        this.loadSummary();
    },
    methods: {
        async loadSummary() {
            if (!this.selectedYear) return;
            this.loading = true;
            try {
                const response = await axios.get(`/api/year-end-census/materials/${this.selectedYear}`);
                this.summary = response.data;
            } catch (error) {
                console.error('Error loading summary:', error);
                useToast().error('Failed to load materials summary');
            } finally {
                this.loading = false;
            }
        },
        async exportSummary() {
            this.exporting = true;
            try {
                const response = await axios.get(
                    `/api/year-end-census/materials/${this.selectedYear}/export`,
                    { responseType: 'blob' }
                );
                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', `year-end-summary-materials-${this.selectedYear}.pdf`);
                document.body.appendChild(link);
                link.click();
                link.remove();
                window.URL.revokeObjectURL(url);
                useToast().success('PDF exported successfully');
            } catch (error) {
                useToast().error('Failed to export PDF');
            } finally {
                this.exporting = false;
            }
        },
        async closeYear() {
            // Collect adjustments
            const adjustments = this.summary.materials
                .filter(m => m.adjustment && m.adjustment !== 0)
                .map(m => ({
                    article_id: m.article_id,
                    adjustment: m.adjustment,
                }));

            const adjustmentCount = adjustments.length;
            const message = adjustmentCount > 0
                ? `This will close the materials year ${this.selectedYear} and apply ${adjustmentCount} adjustment(s) to material quantities. Continue?`
                : `This will close the materials year ${this.selectedYear}. No adjustments will be made. Continue?`;

            if (!confirm(message)) return;

            this.closing = true;
            try {
                const response = await axios.post(
                    `/api/year-end-census/materials/${this.selectedYear}/close`,
                    { adjustments }
                );
                useToast().success(response.data.message);
                this.loadSummary(); // Reload to show closure info
            } catch (error) {
                const msg = error.response?.data?.error || 'Failed to close year';
                useToast().error(msg);
            } finally {
                this.closing = false;
            }
        },
        formatNumber(value) {
            if (value === null || value === undefined) return '0.00';
            return Number(value).toLocaleString('en-US', { 
                minimumFractionDigits: 2, 
                maximumFractionDigits: 2 
            });
        },
        formatDate(dateString) {
            if (!dateString) return 'N/A';
            return new Date(dateString).toLocaleDateString('en-GB', {
                day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
            });
        }
    }
};
</script>

<style scoped lang="scss">
.materials-tab { padding: 10px; }

.year-selector { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
.year-selector label { font-weight: 500; }
.year-select { padding: 8px 16px; border-radius: 4px; border: 1px solid rgba(255,255,255,0.3); background: rgba(255,255,255,0.1); color: white; font-size: 14px; cursor: pointer; }
.year-select option { background: #2d3748; color: white; }

.loading { text-align: center; padding: 40px; color: rgba(255,255,255,0.7); }

.summary-card { background: rgba(255,255,255,0.08); border-radius: 8px; padding: 20px; margin-bottom: 20px; }
.summary-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
.summary-header h3 { margin: 0; font-size: 16px; display: flex; align-items: center; gap: 12px; }

.year-status { font-size: 12px; padding: 4px 10px; border-radius: 12px; }
.year-status.closed { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
.year-status.open { background: rgba(34, 197, 94, 0.2); color: #22c55e; }

.stats-grid { display: flex; gap: 15px; flex-wrap: wrap; }
.stat { background: rgba(255,255,255,0.05); padding: 12px 16px; border-radius: 6px; min-width: 140px; }
.stat .label { display: block; font-size: 11px; color: rgba(255,255,255,0.6); margin-bottom: 4px; }
.stat .value { font-size: 20px; font-weight: bold; }
.stat .value.imported { color: #3b82f6; }
.stat .value.used { color: #f59e0b; }

.materials-list-section { background: rgba(255,255,255,0.05); border-radius: 8px; padding: 20px; margin-bottom: 20px; }
.section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
.section-header h3 { margin: 0; font-size: 16px; }
.search-input { padding: 8px 12px; border-radius: 4px; border: 1px solid rgba(255,255,255,0.3); background: rgba(255,255,255,0.1); color: white; font-size: 13px; width: 200px; }
.search-input::placeholder { color: rgba(255,255,255,0.5); }

.materials-list { max-height: 400px; overflow-y: auto; }
.material-header, .material-item { display: flex; align-items: center; padding: 10px; gap: 10px; }
.material-header { font-weight: bold; font-size: 11px; color: rgba(255,255,255,0.6); border-bottom: 1px solid rgba(255,255,255,0.2); text-transform: uppercase; }
.material-item { border-bottom: 1px solid rgba(255,255,255,0.1); font-size: 13px; }
.material-item:last-child { border-bottom: none; }
.material-item:hover { background: rgba(255,255,255,0.05); }

.col-code { width: 80px; flex-shrink: 0; color: rgba(255,255,255,0.6); }
.col-name { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.col-imported { width: 100px; text-align: right; color: #3b82f6; }
.col-used { width: 100px; text-align: right; color: #f59e0b; }
.col-balance { width: 100px; text-align: right; font-weight: 500; }
.col-balance.positive { color: #22c55e; }
.col-balance.negative { color: #ef4444; }
.col-adjustment { width: 100px; }

.adjustment-input { 
    width: 100%; 
    padding: 6px 8px; 
    border-radius: 4px; 
    border: 1px solid rgba(255,255,255,0.3); 
    background: rgba(255,255,255,0.1); 
    color: white; 
    font-size: 13px; 
    text-align: right;
}
.adjustment-input:focus { outline: none; border-color: #3b82f6; }

.no-materials { text-align: center; padding: 20px; color: rgba(255,255,255,0.6); }

.actions { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; }
.btn { padding: 10px 20px; border: none; border-radius: 6px; font-weight: 500; cursor: pointer; transition: all 0.2s ease; display: flex; align-items: center; gap: 8px; }
.btn:disabled { opacity: 0.5; cursor: not-allowed; }
.btn.export-btn { background: #10b981; color: white; }
.btn.export-btn:hover:not(:disabled) { background: #059669; }
.btn.close-btn { background: #ef4444; color: white; }
.btn.close-btn:hover:not(:disabled) { background: #dc2626; }

.closure-info { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 8px; padding: 15px; }
.closure-info h4 { margin: 0 0 10px 0; color: #ef4444; }
.closure-info p { margin: 5px 0; font-size: 13px; }
</style>

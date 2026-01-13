<template>
    <div class="bank-statements-tab">
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
            <!-- Summary Stats with Year Status -->
            <div class="summary-card">
                <div class="summary-header">
                    <h3>Bank Statements Summary for {{ selectedYear }}</h3>
                    <div v-if="summary.isClosed" class="year-status-inline closed">
                        <span class="status-badge closed-badge">● Closed</span>
                        <span class="closure-info">{{ formatDate(summary.closure.closed_at) }} by {{ summary.closure.closed_by_user?.name || 'Unknown' }}</span>
                    </div>
                    <div v-else class="year-status-inline open">
                        <span class="status-badge open-badge">○ Open</span>
                    </div>
                </div>
                <div class="stats-grid">
                    <div class="stat">
                        <span class="label">Total Statements:</span>
                        <span class="value">{{ summary.stats.total }}</span>
                    </div>
                    <div class="stat active">
                        <span class="label">Active:</span>
                        <span class="value">{{ summary.stats.active }}</span>
                    </div>
                    <div class="stat archived">
                        <span class="label">Archived:</span>
                        <span class="value">{{ summary.stats.archived }}</span>
                    </div>
                </div>
            </div>

            <!-- Statements List -->
            <div class="statements-list-container" v-if="summary.statements && summary.statements.length">
                <h3>Statements ({{ summary.statements.length }})</h3>
                <div class="statements-list">
                    <div v-for="statement in summary.statements" :key="statement.id" class="statement-item">
                        <span class="statement-number">#{{ statement.id_per_bank }}/{{ statement.fiscal_year }}</span>
                        <span class="bank">{{ statement.bank }}</span>
                        <span class="bank-account">{{ statement.bankAccount }}</span>
                        <span class="date">{{ formatDateShort(statement.date) }}</span>
                        <span class="created-by">{{ statement.created_by || 'N/A' }}</span>
                        <span :class="['status', statement.archived ? 'archived' : 'active']">
                            {{ statement.archived ? 'Archived' : 'Active' }}
                        </span>
                    </div>
                </div>
            </div>
            <div v-else class="no-statements">
                <p>No bank statements found for {{ selectedYear }}</p>
            </div>

            <!-- Actions -->
            <div class="actions">
                <button class="btn archive-btn" @click="archiveStatements" :disabled="summary.isClosed || archiving || summary.stats.active === 0">
                    <i v-if="archiving" class="fa fa-spinner fa-spin"></i>
                    {{ archiving ? 'Archiving...' : 'Archive All Statements' }}
                </button>
                <button class="btn export-btn" @click="exportSummary" :disabled="exporting">
                    <i v-if="exporting" class="fa fa-spinner fa-spin"></i>
                    {{ exporting ? 'Exporting...' : 'Export Year Summary PDF' }}
                </button>
                <button v-if="!summary.isClosed" class="btn close-btn" @click="showConfirmModal = true" :disabled="closing">
                    <i v-if="closing" class="fa fa-spinner fa-spin"></i>
                    {{ closing ? 'Closing...' : `Close Fiscal Year ${selectedYear}` }}
                </button>
            </div>
        </template>

        <!-- Confirmation Modal -->
        <div v-if="showConfirmModal" class="modal-overlay" @click="showConfirmModal = false">
            <div class="modal-content" @click.stop>
                <h3>Close Fiscal Year {{ selectedYear }} for Bank Statements?</h3>
                <p>This action will:</p>
                <ul>
                    <li>Archive all {{ summary.stats.active }} active statements</li>
                    <li>Mark the year as officially closed</li>
                    <li>Prevent new statements from being created for this year</li>
                </ul>
                <p><strong>This action cannot be undone.</strong></p>
                <div class="modal-actions">
                    <button class="btn cancel-btn" @click="showConfirmModal = false">Cancel</button>
                    <button class="btn confirm-btn" @click="closeYear">Confirm Close</button>
                </div>
            </div>
        </div>
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
            archiving: false,
            exporting: false,
            closing: false,
            showConfirmModal: false
        };
    },
    mounted() {
        const currentYear = new Date().getFullYear();
        this.selectedYear = this.availableYears.includes(currentYear - 1) 
            ? currentYear - 1 
            : this.availableYears[0] || currentYear;
        this.loadSummary();
    },
    methods: {
        async loadSummary() {
            if (!this.selectedYear) return;
            this.loading = true;
            try {
                const response = await axios.get(`/api/year-end-census/bank-statements/${this.selectedYear}`);
                this.summary = response.data;
            } catch (error) {
                console.error('Error loading summary:', error);
                useToast().error('Failed to load bank statements summary');
            } finally {
                this.loading = false;
            }
        },
        async archiveStatements() {
            this.archiving = true;
            try {
                const response = await axios.post(`/api/year-end-census/bank-statements/${this.selectedYear}/archive`);
                useToast().success(response.data.message);
                await this.loadSummary();
            } catch (error) {
                useToast().error(error.response?.data?.error || 'Failed to archive statements');
            } finally {
                this.archiving = false;
            }
        },
        async exportSummary() {
            this.exporting = true;
            try {
                const response = await axios.get(`/api/year-end-census/bank-statements/${this.selectedYear}/export`, { responseType: 'blob' });
                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', `year-end-summary-bank-statements-${this.selectedYear}.pdf`);
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
            this.showConfirmModal = false;
            this.closing = true;
            try {
                const response = await axios.post(`/api/year-end-census/bank-statements/${this.selectedYear}/close`);
                useToast().success(response.data.message);
                await this.loadSummary();
            } catch (error) {
                useToast().error(error.response?.data?.error || 'Failed to close year');
            } finally {
                this.closing = false;
            }
        },
        formatDate(dateString) {
            if (!dateString) return 'N/A';
            return new Date(dateString).toLocaleDateString('en-GB', {
                day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
            });
        },
        formatDateShort(dateString) {
            if (!dateString) return 'N/A';
            return new Date(dateString).toLocaleDateString('en-GB', {
                day: '2-digit', month: '2-digit', year: 'numeric'
            });
        }
    }
};
</script>

<style scoped lang="scss">
.bank-statements-tab { padding: 10px; }
.year-selector { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
.year-selector label { font-weight: 500; }
.year-select { padding: 8px 16px; border-radius: 4px; border: 1px solid rgba(255,255,255,0.3); background: rgba(255,255,255,0.1); color: white; font-size: 14px; cursor: pointer; }
.year-select option { background: #2d3748; color: white; }
.loading { text-align: center; padding: 40px; color: rgba(255,255,255,0.7); }

.summary-card { background: rgba(255,255,255,0.08); border-radius: 8px; padding: 20px; margin-bottom: 20px; }
.summary-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px; }
.summary-header h3 { margin: 0; font-size: 16px; }

.year-status-inline { display: flex; align-items: center; gap: 10px; }
.year-status-inline .closure-info { font-size: 12px; color: rgba(255,255,255,0.7); }
.status-badge { display: inline-block; padding: 4px 10px; border-radius: 12px; font-weight: 500; font-size: 12px; }
.status-badge.closed-badge { background: rgba(34,197,94,0.2); color: #22c55e; }
.status-badge.open-badge { background: rgba(245,158,11,0.2); color: #fbbf24; }

.stats-grid { display: flex; gap: 20px; flex-wrap: wrap; }
.stat { background: rgba(255,255,255,0.05); padding: 15px 20px; border-radius: 6px; min-width: 140px; }
.stat .label { display: block; font-size: 12px; color: rgba(255,255,255,0.6); margin-bottom: 5px; }
.stat .value { font-size: 24px; font-weight: bold; }
.stat.active .value { color: #22c55e; }
.stat.archived .value { color: #9ca3af; }

.statements-list-container { background: rgba(255,255,255,0.05); border-radius: 8px; padding: 20px; margin-bottom: 20px; }
.statements-list-container h3 { margin: 0 0 15px 0; font-size: 16px; }
.statements-list { max-height: 300px; overflow-y: auto; }
.statement-item { display: flex; align-items: center; gap: 15px; padding: 10px; border-bottom: 1px solid rgba(255,255,255,0.1); }
.statement-item:last-child { border-bottom: none; }
.statement-item .statement-number { font-weight: bold; min-width: 80px; }
.statement-item .bank { min-width: 120px; }
.statement-item .bank-account { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: rgba(255,255,255,0.7); }
.statement-item .date { min-width: 100px; color: rgba(255,255,255,0.7); }
.statement-item .created-by { min-width: 100px; color: rgba(255,255,255,0.7); }
.statement-item .status { padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 500; }
.statement-item .status.active { background: rgba(34,197,94,0.2); color: #22c55e; }
.statement-item .status.archived { background: rgba(156,163,175,0.2); color: #9ca3af; }

.no-statements { background: rgba(255,255,255,0.05); border-radius: 8px; padding: 20px; text-align: center; margin-bottom: 20px; }
.no-statements p { margin: 0; color: rgba(255,255,255,0.7); }

.actions { display: flex; gap: 12px; flex-wrap: wrap; }
.btn { padding: 10px 20px; border: none; border-radius: 6px; font-weight: 500; cursor: pointer; transition: all 0.2s ease; display: flex; align-items: center; gap: 8px; }
.btn:disabled { opacity: 0.5; cursor: not-allowed; }
.btn.archive-btn { background: #6366f1; color: white; }
.btn.archive-btn:hover:not(:disabled) { background: #4f46e5; }
.btn.export-btn { background: #10b981; color: white; }
.btn.export-btn:hover:not(:disabled) { background: #059669; }
.btn.close-btn { background: #ef4444; color: white; }
.btn.close-btn:hover:not(:disabled) { background: #dc2626; }
.btn.cancel-btn { background: #6b7280; color: white; }
.btn.cancel-btn:hover { background: #4b5563; }
.btn.confirm-btn { background: #ef4444; color: white; }
.btn.confirm-btn:hover { background: #dc2626; }

.modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; z-index: 1000; }
.modal-content { background: #1f2937; border-radius: 12px; padding: 24px; max-width: 450px; width: 90%; }
.modal-content h3 { margin: 0 0 15px 0; color: white; }
.modal-content p { color: rgba(255,255,255,0.8); margin: 10px 0; }
.modal-content ul { color: rgba(255,255,255,0.7); margin: 10px 0; padding-left: 20px; }
.modal-content li { margin: 5px 0; }
.modal-actions { display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px; }
</style>

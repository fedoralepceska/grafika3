<template>
    <div class="clients-tab">
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
                        Clients Summary for {{ selectedYear }}
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
                        <span class="label">Total Clients:</span>
                        <span class="value">{{ summary.stats.total_clients }}</span>
                    </div>
                    <div class="stat">
                        <span class="label">We Owe (clients):</span>
                        <span class="value positive">{{ summary.stats.clients_we_owe }}</span>
                    </div>
                    <div class="stat">
                        <span class="label">They Owe Us:</span>
                        <span class="value negative">{{ summary.stats.clients_owe_us }}</span>
                    </div>
                    <div class="stat">
                        <span class="label">Total We Owe:</span>
                        <span class="value positive">{{ formatNumber(summary.stats.total_we_owe) }}</span>
                    </div>
                    <div class="stat">
                        <span class="label">Total They Owe:</span>
                        <span class="value negative">{{ formatNumber(summary.stats.total_they_owe) }}</span>
                    </div>
                </div>
                <div class="status-stats">
                    <span class="status-badge pending">Pending: {{ summary.stats.pending_count }}</span>
                    <span class="status-badge ready">Ready: {{ summary.stats.ready_count }}</span>
                    <span class="status-badge closed">Closed: {{ summary.stats.closed_count }}</span>
                </div>
            </div>

            <!-- Clients List -->
            <div class="clients-list-section">
                <div class="section-header">
                    <h3>Client Balances ({{ filteredEntries.length }})</h3>
                    <input 
                        v-model="searchQuery" 
                        type="text" 
                        placeholder="Search clients..."
                        class="search-input"
                    />
                </div>
                <div class="clients-list" v-if="filteredEntries.length">
                    <div class="client-header">
                        <span class="col-name">Client Name</span>
                        <span class="col-initial">Initial</span>
                        <span class="col-balance">Final Balance</span>
                        <span class="col-direction">Direction</span>
                        <span class="col-status">Status</span>
                        <span class="col-actions">Actions</span>
                    </div>
                    <div 
                        v-for="entry in filteredEntries" 
                        :key="entry.id" 
                        class="client-item"
                        :class="{ adjusted: entry.is_adjusted }"
                    >
                        <span class="col-name">
                            {{ entry.client_name }}
                            <i v-if="entry.is_adjusted" class="fa fa-edit adjusted-icon" title="Manually adjusted"></i>
                        </span>
                        <span class="col-initial">{{ formatNumber(entry.initial_balance) }}</span>
                        <span class="col-balance" :class="entry.balance_direction">
                            {{ formatNumber(entry.final_balance) }}
                        </span>
                        <span class="col-direction">
                            <span v-if="entry.balance_direction === 'we_owe'" class="direction-badge we-owe">
                                We Owe
                            </span>
                            <span v-else class="direction-badge they-owe">
                                They Owe
                            </span>
                        </span>
                        <span class="col-status">
                            <span :class="['status-pill', entry.status]">{{ formatStatus(entry.status) }}</span>
                        </span>
                        <span class="col-actions">
                            <button class="btn-icon" @click="openBreakdown(entry)" title="View breakdown">
                                <i class="fa fa-eye"></i>
                            </button>
                            <button 
                                v-if="entry.status === 'pending'" 
                                class="btn-icon ready" 
                                @click="markReady(entry)"
                                title="Mark ready to close"
                            >
                                <i class="fa fa-check"></i>
                            </button>
                            <button 
                                v-if="entry.status === 'ready_to_close'" 
                                class="btn-icon revert" 
                                @click="revertToPending(entry)"
                                title="Revert to pending"
                            >
                                <i class="fa fa-undo"></i>
                            </button>
                        </span>
                    </div>
                </div>
                <div v-else class="no-clients">
                    <p>No clients found for {{ selectedYear }}</p>
                </div>
            </div>

            <!-- Actions -->
            <div class="actions">
                <button class="btn export-btn" @click="exportSummary" :disabled="exporting">
                    <i v-if="exporting" class="fa fa-spinner fa-spin"></i>
                    {{ exporting ? 'Exporting...' : 'Export PDF' }}
                </button>
                <button 
                    v-if="!summary.isClosed && summary.stats.pending_count > 0"
                    class="btn mark-all-btn" 
                    @click="markAllReady" 
                    :disabled="markingAll"
                >
                    <i v-if="markingAll" class="fa fa-spinner fa-spin"></i>
                    {{ markingAll ? 'Marking...' : `Mark All Ready (${summary.stats.pending_count})` }}
                </button>
                <button 
                    v-if="!summary.isClosed && summary.stats.ready_count > 0 && summary.stats.pending_count === 0"
                    class="btn close-btn" 
                    @click="closeYear" 
                    :disabled="closing"
                >
                    <i v-if="closing" class="fa fa-spinner fa-spin"></i>
                    {{ closing ? 'Closing...' : `Close Year (All ${summary.stats.total_clients} clients ready)` }}
                </button>
                <div v-if="!summary.isClosed && summary.stats.pending_count > 0 && summary.stats.ready_count > 0" class="close-warning">
                    <i class="fa fa-exclamation-triangle"></i>
                    {{ summary.stats.pending_count }} client(s) still pending. Mark all ready to enable year closure.
                </div>
            </div>

            <!-- Closure Info -->
            <div v-if="summary.closure" class="closure-info">
                <h4>Year Closure Information</h4>
                <p><strong>Closed on:</strong> {{ formatDate(summary.closure.closed_at) }}</p>
                <p><strong>Closed by:</strong> {{ summary.closure.closed_by_user?.name || 'Unknown' }}</p>
                <p v-if="summary.closure.summary?.closed_count">
                    <strong>Clients closed:</strong> {{ summary.closure.summary.closed_count }}
                </p>
            </div>
        </template>

        <!-- Breakdown Modal -->
        <ClientBreakdownModal 
            v-if="showBreakdownModal"
            :client-id="selectedClientId"
            :year="selectedYear"
            :is-closed="summary?.isClosed"
            @close="closeBreakdownModal"
            @updated="loadSummary"
        />

        <!-- Close Year Confirmation Dialog -->
        <div v-if="showCloseYearDialog" class="modal-overlay" @click.self="showCloseYearDialog = false">
            <div class="confirmation-dialog">
                <div class="dialog-header">
                    <h3>Close Fiscal Year {{ selectedYear }}</h3>
                    <button class="close-btn" @click="showCloseYearDialog = false">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <div class="dialog-content">
                    <div class="warning-icon">
                        <i class="fa fa-exclamation-triangle"></i>
                    </div>
                    <p class="dialog-message">
                        You are about to close the fiscal year <strong>{{ selectedYear }}</strong> for all 
                        <strong>{{ summary?.stats.total_clients }}</strong> clients.
                    </p>
                    <div class="dialog-details">
                        <p>This action will:</p>
                        <ul>
                            <li>Update each client's initial balance for the next year</li>
                            <li>Mark all client entries as permanently closed</li>
                            <li>Prevent any further modifications to this year's data</li>
                        </ul>
                    </div>
                    <p class="dialog-warning">
                        <strong>Warning:</strong> This action cannot be undone.
                    </p>
                </div>
                <div class="dialog-actions">
                    <button class="btn cancel-btn" @click="showCloseYearDialog = false" :disabled="closing">
                        Cancel
                    </button>
                    <button class="btn confirm-btn" @click="confirmCloseYear" :disabled="closing">
                        <i v-if="closing" class="fa fa-spinner fa-spin"></i>
                        {{ closing ? 'Closing...' : 'Yes, Close Year' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>


<script>
import axios from 'axios';
import { useToast } from 'vue-toastification';
import ClientBreakdownModal from './ClientBreakdownModal.vue';

export default {
    components: { ClientBreakdownModal },
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
            markingAll: false,
            searchQuery: '',
            showBreakdownModal: false,
            showCloseYearDialog: false,
            selectedClientId: null,
        };
    },
    computed: {
        filteredEntries() {
            if (!this.summary?.entries) return [];
            if (!this.searchQuery) return this.summary.entries;
            
            const query = this.searchQuery.toLowerCase();
            return this.summary.entries.filter(e => 
                e.client_name.toLowerCase().includes(query)
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
                const response = await axios.get(`/api/year-end-census/clients/${this.selectedYear}`);
                this.summary = response.data;
            } catch (error) {
                console.error('Error loading summary:', error);
                useToast().error('Failed to load clients summary');
            } finally {
                this.loading = false;
            }
        },
        async exportSummary() {
            this.exporting = true;
            try {
                const response = await axios.get(
                    `/api/year-end-census/clients/${this.selectedYear}/export`,
                    { responseType: 'blob' }
                );
                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', `year-end-summary-clients-${this.selectedYear}.pdf`);
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
            const readyCount = this.summary.stats.ready_count;
            const totalCount = this.summary.stats.total_clients;
            
            if (readyCount !== totalCount) {
                useToast().error('All clients must be marked as ready before closing the year');
                return;
            }

            // Show confirmation dialog instead of window.confirm
            this.showCloseYearDialog = true;
        },
        async confirmCloseYear() {
            this.closing = true;
            try {
                const response = await axios.post(`/api/year-end-census/clients/${this.selectedYear}/close`);
                useToast().success(response.data.message);
                this.showCloseYearDialog = false;
                this.loadSummary();
            } catch (error) {
                const msg = error.response?.data?.error || 'Failed to close year';
                useToast().error(msg);
            } finally {
                this.closing = false;
            }
        },
        async markAllReady() {
            const pendingCount = this.summary.stats.pending_count;
            const message = `This will mark all ${pendingCount} pending clients as ready to close. Continue?`;

            if (!confirm(message)) return;

            this.markingAll = true;
            try {
                const response = await axios.post(`/api/year-end-census/clients/${this.selectedYear}/mark-all-ready`);
                useToast().success(response.data.message);
                this.loadSummary();
            } catch (error) {
                const msg = error.response?.data?.error || 'Failed to mark clients as ready';
                useToast().error(msg);
            } finally {
                this.markingAll = false;
            }
        },
        async markReady(entry) {
            try {
                await axios.put(`/api/year-end-census/clients/${this.selectedYear}/${entry.client_id}`, {
                    status: 'ready_to_close'
                });
                useToast().success(`${entry.client_name} marked as ready to close`);
                this.loadSummary();
            } catch (error) {
                useToast().error('Failed to update status');
            }
        },
        async revertToPending(entry) {
            try {
                await axios.put(`/api/year-end-census/clients/${this.selectedYear}/${entry.client_id}`, {
                    status: 'pending'
                });
                useToast().success(`${entry.client_name} reverted to pending`);
                this.loadSummary();
            } catch (error) {
                useToast().error('Failed to update status');
            }
        },
        openBreakdown(entry) {
            this.selectedClientId = entry.client_id;
            this.showBreakdownModal = true;
        },
        closeBreakdownModal() {
            this.showBreakdownModal = false;
            this.selectedClientId = null;
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
        },
        formatStatus(status) {
            const map = {
                'pending': 'Pending',
                'ready_to_close': 'Ready',
                'closed': 'Closed'
            };
            return map[status] || status;
        }
    }
};
</script>


<style scoped lang="scss">
.clients-tab { padding: 10px; }

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

.stats-grid { display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 15px; }
.stat { background: rgba(255,255,255,0.05); padding: 12px 16px; border-radius: 6px; min-width: 140px; }
.stat .label { display: block; font-size: 11px; color: rgba(255,255,255,0.6); margin-bottom: 4px; }
.stat .value { font-size: 20px; font-weight: bold; }
.stat .value.positive { color: #22c55e; }
.stat .value.negative { color: #ef4444; }

.status-stats { display: flex; gap: 10px; }
.status-badge { padding: 4px 12px; border-radius: 12px; font-size: 12px; }
.status-badge.pending { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }
.status-badge.ready { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
.status-badge.closed { background: rgba(239, 68, 68, 0.2); color: #ef4444; }

.clients-list-section { background: rgba(255,255,255,0.05); border-radius: 8px; padding: 20px; margin-bottom: 20px; }
.section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
.section-header h3 { margin: 0; font-size: 16px; }
.search-input { padding: 8px 12px; border-radius: 4px; border: 1px solid rgba(255,255,255,0.3); background: rgba(255,255,255,0.1); color: white; font-size: 13px; width: 200px; }
.search-input::placeholder { color: rgba(255,255,255,0.5); }

.clients-list { max-height: 400px; overflow-y: auto; }
.client-header, .client-item { display: flex; align-items: center; padding: 10px; gap: 10px; }
.client-header { font-weight: bold; font-size: 11px; color: rgba(255,255,255,0.6); border-bottom: 1px solid rgba(255,255,255,0.2); text-transform: uppercase; }
.client-item { border-bottom: 1px solid rgba(255,255,255,0.1); font-size: 13px; }
.client-item:last-child { border-bottom: none; }
.client-item:hover { background: rgba(255,255,255,0.05); }
.client-item.adjusted { background: rgba(251, 191, 36, 0.05); }

.col-name { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: flex; align-items: center; gap: 6px; }
.col-initial { width: 100px; text-align: right; }
.col-balance { width: 120px; text-align: right; font-weight: 500; }
.col-balance.we_owe { color: #22c55e; }
.col-balance.they_owe { color: #ef4444; }
.col-direction { width: 80px; text-align: center; }
.col-status { width: 80px; text-align: center; }
.col-actions { width: 100px; display: flex; gap: 6px; justify-content: center; }

.adjusted-icon { color: #fbbf24; font-size: 11px; }

.direction-badge { padding: 2px 8px; border-radius: 10px; font-size: 11px; }
.direction-badge.we-owe { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
.direction-badge.they-owe { background: rgba(239, 68, 68, 0.2); color: #ef4444; }

.status-pill { padding: 2px 8px; border-radius: 10px; font-size: 11px; }
.status-pill.pending { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }
.status-pill.ready_to_close { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
.status-pill.closed { background: rgba(239, 68, 68, 0.2); color: #ef4444; }

.btn-icon { background: rgba(255,255,255,0.1); border: none; padding: 6px 8px; border-radius: 4px; color: white; cursor: pointer; }
.btn-icon:hover { background: rgba(255,255,255,0.2); }
.btn-icon.ready { color: #22c55e; }
.btn-icon.revert { color: #fbbf24; }

.no-clients { text-align: center; padding: 20px; color: rgba(255,255,255,0.6); }

.actions { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; }
.btn { padding: 10px 20px; border: none; border-radius: 6px; font-weight: 500; cursor: pointer; transition: all 0.2s ease; display: flex; align-items: center; gap: 8px; }
.btn:disabled { opacity: 0.5; cursor: not-allowed; }
.btn.export-btn { background: #10b981; color: white; }
.btn.export-btn:hover:not(:disabled) { background: #059669; }
.btn.mark-all-btn { background: #3b82f6; color: white; }
.btn.mark-all-btn:hover:not(:disabled) { background: #2563eb; }
.btn.close-btn { background: #ef4444; color: white; }
.btn.close-btn:hover:not(:disabled) { background: #dc2626; }

.close-warning { 
    display: flex; align-items: center; gap: 8px; 
    color: #fbbf24; font-size: 13px; 
    background: rgba(251, 191, 36, 0.1); 
    padding: 8px 12px; border-radius: 6px; 
}

.closure-info { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 8px; padding: 15px; }
.closure-info h4 { margin: 0 0 10px 0; color: #ef4444; }
.closure-info p { margin: 5px 0; font-size: 13px; }

/* Confirmation Dialog */
.modal-overlay {
    position: fixed; top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0, 0, 0, 0.7); display: flex; align-items: center; justify-content: center; z-index: 1000;
}
.confirmation-dialog {
    background: #1f2937; border-radius: 12px; width: 90%; max-width: 500px; color: white; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
}
.dialog-header {
    display: flex; justify-content: space-between; align-items: center; padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.1);
}
.dialog-header h3 { margin: 0; font-size: 18px; }
.dialog-header .close-btn { background: none; border: none; color: rgba(255,255,255,0.6); font-size: 18px; cursor: pointer; padding: 0; }
.dialog-header .close-btn:hover { color: white; }

.dialog-content { padding: 20px; }
.warning-icon { text-align: center; margin-bottom: 15px; }
.warning-icon i { font-size: 48px; color: #fbbf24; }
.dialog-message { font-size: 16px; text-align: center; margin-bottom: 20px; line-height: 1.5; }
.dialog-details { background: rgba(255,255,255,0.05); padding: 15px; border-radius: 6px; margin-bottom: 15px; }
.dialog-details p { margin: 0 0 10px 0; font-size: 14px; font-weight: 500; }
.dialog-details ul { margin: 0; padding-left: 20px; }
.dialog-details li { margin: 5px 0; font-size: 13px; color: rgba(255,255,255,0.8); }
.dialog-warning { text-align: center; color: #ef4444; font-size: 14px; margin: 15px 0 0 0; }

.dialog-actions { display: flex; gap: 10px; padding: 20px; border-top: 1px solid rgba(255,255,255,0.1); }
.dialog-actions .btn { flex: 1; padding: 12px 20px; font-size: 14px; }
.cancel-btn { background: rgba(255,255,255,0.1); color: white; }
.cancel-btn:hover:not(:disabled) { background: rgba(255,255,255,0.2); }
.confirm-btn { background: #ef4444; color: white; }
.confirm-btn:hover:not(:disabled) { background: #dc2626; }
</style>

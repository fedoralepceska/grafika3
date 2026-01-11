<template>
    <div class="invoices-tab">
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
                    <h3>Invoice Summary for {{ selectedYear }}</h3>
                </div>
                <div class="stats-grid">
                    <div class="stat">
                        <span class="label">Total Invoices:</span>
                        <span class="value">{{ summary.stats.total }}</span>
                    </div>
                    <div class="stat revenue">
                        <span class="label">Total Revenue:</span>
                        <span class="value">{{ formatCurrency(summary.stats.total_revenue) }}</span>
                    </div>
                    <div class="stat">
                        <span class="label">Total VAT:</span>
                        <span class="value">{{ formatCurrency(summary.stats.total_vat) }}</span>
                    </div>
                    <div class="stat">
                        <span class="label">Orders Invoiced:</span>
                        <span class="value">{{ summary.stats.orders_count }}</span>
                    </div>
                </div>
            </div>

            <!-- Invoices List -->
            <div class="invoices-list-section">
                <h3>Invoices ({{ summary.invoices.length }})</h3>
                <div class="invoices-list" v-if="summary.invoices.length">
                    <div class="invoice-header">
                        <span class="col-number">Invoice #</span>
                        <span class="col-date">Date</span>
                        <span class="col-client">Client</span>
                        <span class="col-orders">Orders</span>
                        <span class="col-amount">Amount</span>
                    </div>
                    <div v-for="invoice in summary.invoices" :key="invoice.id" class="invoice-item">
                        <span class="col-number">#{{ invoice.faktura_number }}/{{ invoice.fiscal_year }}</span>
                        <span class="col-date">{{ formatDate(invoice.created_at) }}</span>
                        <span class="col-client">{{ invoice.client_name || 'N/A' }}</span>
                        <span class="col-orders">{{ invoice.orders_count }} order(s)</span>
                        <span class="col-amount">{{ formatCurrency(invoice.total_amount) }}</span>
                    </div>
                </div>
                <div v-else class="no-invoices">
                    <p>No invoices for {{ selectedYear }}</p>
                </div>
            </div>

            <!-- Actions -->
            <div class="actions">
                <button class="btn export-btn" @click="exportSummary" :disabled="exporting">
                    <i v-if="exporting" class="fa fa-spinner fa-spin"></i>
                    {{ exporting ? 'Exporting...' : 'Export Year Summary PDF' }}
                </button>
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
            exporting: false
        };
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
                const response = await axios.get(`/api/year-end-census/invoices/${this.selectedYear}`);
                this.summary = response.data;
            } catch (error) {
                console.error('Error loading summary:', error);
                useToast().error('Failed to load invoice summary');
            } finally {
                this.loading = false;
            }
        },
        async exportSummary() {
            this.exporting = true;
            try {
                const response = await axios.get(`/api/year-end-census/invoices/${this.selectedYear}/export`, { responseType: 'blob' });
                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', `year-end-summary-invoices-${this.selectedYear}.pdf`);
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
        formatDate(dateString) {
            if (!dateString) return 'N/A';
            return new Date(dateString).toLocaleDateString('en-GB', {
                day: '2-digit', month: '2-digit', year: 'numeric'
            });
        },
        formatCurrency(amount) {
            if (amount === null || amount === undefined) return '0.00 ден.';
            return Number(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ден.';
        }
    }
};
</script>

<style scoped lang="scss">
.invoices-tab { padding: 10px; }
.year-selector { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
.year-selector label { font-weight: 500; }
.year-select { padding: 8px 16px; border-radius: 4px; border: 1px solid rgba(255,255,255,0.3); background: rgba(255,255,255,0.1); color: white; font-size: 14px; cursor: pointer; }
.year-select option { background: #2d3748; color: white; }
.loading { text-align: center; padding: 40px; color: rgba(255,255,255,0.7); }

.summary-card { background: rgba(255,255,255,0.08); border-radius: 8px; padding: 20px; margin-bottom: 20px; }
.summary-header { margin-bottom: 15px; }
.summary-header h3 { margin: 0; font-size: 16px; }

.stats-grid { display: flex; gap: 20px; flex-wrap: wrap; }
.stat { background: rgba(255,255,255,0.05); padding: 15px 20px; border-radius: 6px; min-width: 160px; }
.stat .label { display: block; font-size: 12px; color: rgba(255,255,255,0.6); margin-bottom: 5px; }
.stat .value { font-size: 24px; font-weight: bold; }
.stat.revenue .value { color: #22c55e; }

.invoices-list-section { background: rgba(255,255,255,0.05); border-radius: 8px; padding: 20px; margin-bottom: 20px; }
.invoices-list-section h3 { margin: 0 0 15px 0; font-size: 16px; }

.invoices-list { max-height: 400px; overflow-y: auto; }
.invoice-header, .invoice-item { display: flex; align-items: center; padding: 10px; gap: 10px; }
.invoice-header { font-weight: bold; font-size: 12px; color: rgba(255,255,255,0.6); border-bottom: 1px solid rgba(255,255,255,0.2); }
.invoice-item { border-bottom: 1px solid rgba(255,255,255,0.1); font-size: 13px; }
.invoice-item:last-child { border-bottom: none; }
.invoice-item:hover { background: rgba(255,255,255,0.05); }

.col-number { min-width: 100px; font-weight: bold; }
.col-date { min-width: 100px; }
.col-client { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.col-orders { min-width: 100px; text-align: center; }
.col-amount { min-width: 120px; text-align: right; color: #22c55e; font-weight: 500; }

.no-invoices { text-align: center; padding: 20px; color: rgba(255,255,255,0.6); }

.actions { display: flex; gap: 12px; flex-wrap: wrap; }
.btn { padding: 10px 20px; border: none; border-radius: 6px; font-weight: 500; cursor: pointer; transition: all 0.2s ease; display: flex; align-items: center; gap: 8px; }
.btn:disabled { opacity: 0.5; cursor: not-allowed; }
.btn.export-btn { background: #10b981; color: white; }
.btn.export-btn:hover:not(:disabled) { background: #059669; }
</style>

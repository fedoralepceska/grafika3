<template>
    <div class="modal-overlay" @click.self="$emit('close')">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Client Balance Breakdown</h2>
                <button class="close-btn" @click="$emit('close')">
                    <i class="fa fa-times"></i>
                </button>
            </div>

            <div v-if="loading" class="loading">
                <i class="fa fa-spinner fa-spin"></i> Loading breakdown...
            </div>

            <template v-else-if="breakdown">
                <div class="client-info">
                    <h3>{{ breakdown.client_name || 'Client' }}</h3>
                    <div class="year-badge">{{ year }}</div>
                </div>

                <div class="breakdown-sections">
                    <!-- Initial Balance -->
                    <div class="section initial">
                        <div class="section-header">
                            <span>Initial Balance</span>
                            <span class="amount" :class="breakdown.initial_balance >= 0 ? 'positive' : 'negative'">
                                {{ formatNumber(breakdown.initial_balance) }}
                            </span>
                        </div>
                    </div>

                    <!-- Output Invoices (They Owe) -->
                    <div class="section">
                        <div class="section-header" @click="toggleSection('output')">
                            <span>
                                <i :class="['fa', expandedSections.output ? 'fa-chevron-down' : 'fa-chevron-right']"></i>
                                Output Invoices (Faktura)
                            </span>
                            <span class="amount negative">{{ formatNumber(breakdown.output_invoices?.total || 0) }}</span>
                        </div>
                        <div v-if="expandedSections.output && breakdown.output_invoices?.items?.length" class="section-items">
                            <div v-for="item in breakdown.output_invoices.items" :key="'out-' + item.id" class="item">
                                <span>{{ item.number }} - {{ item.date }}</span>
                                <span>{{ formatNumber(item.amount) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Trade Invoices (They Owe) -->
                    <div class="section">
                        <div class="section-header" @click="toggleSection('trade')">
                            <span>
                                <i :class="['fa', expandedSections.trade ? 'fa-chevron-down' : 'fa-chevron-right']"></i>
                                Trade Invoices
                            </span>
                            <span class="amount negative">{{ formatNumber(breakdown.trade_invoices?.total || 0) }}</span>
                        </div>
                        <div v-if="expandedSections.trade && breakdown.trade_invoices?.items?.length" class="section-items">
                            <div v-for="item in breakdown.trade_invoices.items" :key="'trade-' + item.id" class="item">
                                <span>{{ item.number }} - {{ item.date }}</span>
                                <span>{{ formatNumber(item.amount) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Statement Expenses (They Owe) -->
                    <div class="section">
                        <div class="section-header" @click="toggleSection('expenses')">
                            <span>
                                <i :class="['fa', expandedSections.expenses ? 'fa-chevron-down' : 'fa-chevron-right']"></i>
                                Statement Expenses
                            </span>
                            <span class="amount negative">{{ formatNumber(breakdown.statement_expenses?.total || 0) }}</span>
                        </div>
                        <div v-if="expandedSections.expenses && breakdown.statement_expenses?.items?.length" class="section-items">
                            <div v-for="item in breakdown.statement_expenses.items" :key="'exp-' + item.id" class="item">
                                <span>{{ item.date }} {{ item.comment ? '- ' + item.comment : '' }}</span>
                                <span>{{ formatNumber(item.amount) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Incoming Invoices (We Owe) -->
                    <div class="section">
                        <div class="section-header" @click="toggleSection('incoming')">
                            <span>
                                <i :class="['fa', expandedSections.incoming ? 'fa-chevron-down' : 'fa-chevron-right']"></i>
                                Incoming Invoices
                            </span>
                            <span class="amount positive">{{ formatNumber(breakdown.incoming_invoices?.total || 0) }}</span>
                        </div>
                        <div v-if="expandedSections.incoming && breakdown.incoming_invoices?.items?.length" class="section-items">
                            <div v-for="item in breakdown.incoming_invoices.items" :key="'inc-' + item.id" class="item">
                                <span>{{ item.date }}</span>
                                <span>{{ formatNumber(item.amount) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Statement Income (We Owe) -->
                    <div class="section">
                        <div class="section-header" @click="toggleSection('income')">
                            <span>
                                <i :class="['fa', expandedSections.income ? 'fa-chevron-down' : 'fa-chevron-right']"></i>
                                Statement Income
                            </span>
                            <span class="amount positive">{{ formatNumber(breakdown.statement_income?.total || 0) }}</span>
                        </div>
                        <div v-if="expandedSections.income && breakdown.statement_income?.items?.length" class="section-items">
                            <div v-for="item in breakdown.statement_income.items" :key="'sinc-' + item.id" class="item">
                                <span>{{ item.date }} {{ item.comment ? '- ' + item.comment : '' }}</span>
                                <span>{{ formatNumber(item.amount) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary -->
                <div class="summary-section">
                    <div class="summary-row">
                        <span>Calculated Balance:</span>
                        <span :class="breakdown.calculated_balance >= 0 ? 'positive' : 'negative'">
                            {{ formatNumber(breakdown.calculated_balance) }}
                        </span>
                    </div>
                    
                    <!-- Adjustment Input -->
                    <div v-if="!isClosed && breakdown.status !== 'closed'" class="adjustment-section">
                        <h4>Balance Adjustments</h4>
                        <div class="adjustment-inputs">
                            <div class="adjustment-field">
                                <label>Add Amount (+):</label>
                                <input 
                                    v-model.number="adjustmentPlus"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="adjustment-input"
                                    placeholder="0.00"
                                />
                                <span class="field-help">Amount they owe us more</span>
                            </div>
                            <div class="adjustment-field">
                                <label>Subtract Amount (-):</label>
                                <input 
                                    v-model.number="adjustmentMinus"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="adjustment-input"
                                    placeholder="0.00"
                                />
                                <span class="field-help">Amount to deduct</span>
                            </div>
                        </div>
                        <div class="adjustment-actions">
                            <button 
                                class="btn save-btn" 
                                @click="saveAdjustment"
                                :disabled="saving"
                            >
                                {{ saving ? 'Saving...' : 'Apply Adjustment' }}
                            </button>
                            <button 
                                v-if="breakdown.is_adjusted"
                                class="btn clear-btn" 
                                @click="clearAdjustment"
                                :disabled="saving"
                            >
                                Clear Adjustments
                            </button>
                        </div>
                    </div>

                    <div class="summary-row final">
                        <span>Final Balance:</span>
                        <span :class="breakdown.final_balance >= 0 ? 'positive' : 'negative'">
                            {{ formatNumber(breakdown.final_balance) }}
                            <span class="direction-label">
                                ({{ breakdown.balance_direction === 'we_owe' ? 'We Owe Them' : 'They Owe Us' }})
                            </span>
                        </span>
                    </div>

                    <div class="status-row">
                        <span>Status: </span>
                        <span :class="['status-pill', breakdown.status]">{{ formatStatus(breakdown.status) }}</span>
                        <template v-if="!isClosed && breakdown.status !== 'closed'">
                            <button 
                                v-if="breakdown.status === 'pending'"
                                class="btn status-btn ready"
                                @click="updateStatus('ready_to_close')"
                                :disabled="updatingStatus"
                            >
                                Mark Ready to Close
                            </button>
                            <button 
                                v-if="breakdown.status === 'ready_to_close'"
                                class="btn status-btn revert"
                                @click="updateStatus('pending')"
                                :disabled="updatingStatus"
                            >
                                Revert to Pending
                            </button>
                        </template>
                    </div>

                    <!-- Export Individual PDF -->
                    <div class="export-section">
                        <button 
                            class="btn export-individual-btn" 
                            @click="exportIndividualPDF"
                            :disabled="exportingPDF"
                        >
                            <i v-if="exportingPDF" class="fa fa-spinner fa-spin"></i>
                            {{ exportingPDF ? 'Exporting...' : 'Export Client PDF' }}
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>

<script>
import axios from 'axios';
import { useToast } from 'vue-toastification';

export default {
    props: {
        clientId: { type: Number, required: true },
        year: { type: Number, required: true },
        isClosed: { type: Boolean, default: false }
    },
    emits: ['close', 'updated'],
    data() {
        return {
            breakdown: null,
            loading: true,
            saving: false,
            updatingStatus: false,
            exportingPDF: false,
            adjustmentPlus: 0,
            adjustmentMinus: 0,
            expandedSections: {
                output: false,
                trade: false,
                expenses: false,
                incoming: false,
                income: false
            }
        };
    },
    mounted() {
        this.loadBreakdown();
    },
    methods: {
        async loadBreakdown() {
            this.loading = true;
            try {
                const response = await axios.get(`/api/year-end-census/clients/${this.year}/${this.clientId}`);
                this.breakdown = response.data;
                
                // Parse existing adjustment into plus/minus
                if (this.breakdown.adjusted_balance !== null) {
                    const adjustment = this.breakdown.adjusted_balance - this.breakdown.calculated_balance;
                    // Negative adjustment means they owe us more (plus field)
                    // Positive adjustment means we reduce what they owe (minus field)
                    if (adjustment < 0) {
                        this.adjustmentPlus = Math.abs(adjustment);
                        this.adjustmentMinus = 0;
                    } else if (adjustment > 0) {
                        this.adjustmentPlus = 0;
                        this.adjustmentMinus = adjustment;
                    } else {
                        this.adjustmentPlus = 0;
                        this.adjustmentMinus = 0;
                    }
                } else {
                    this.adjustmentPlus = 0;
                    this.adjustmentMinus = 0;
                }
            } catch (error) {
                useToast().error('Failed to load breakdown');
                this.$emit('close');
            } finally {
                this.loading = false;
            }
        },
        async saveAdjustment() {
            // Calculate the net adjustment
            // Plus means we ADD to their debt (make balance MORE negative = they owe us more)
            // Minus means we SUBTRACT from their debt (make balance LESS negative = reduce what they owe)
            const netAdjustment = -(this.adjustmentPlus || 0) + (this.adjustmentMinus || 0);
            const newAdjustedBalance = this.breakdown.calculated_balance + netAdjustment;

            this.saving = true;
            try {
                await axios.put(`/api/year-end-census/clients/${this.year}/${this.clientId}`, {
                    adjusted_balance: newAdjustedBalance
                });
                useToast().success('Adjustment saved');
                this.loadBreakdown();
                this.$emit('updated');
            } catch (error) {
                useToast().error('Failed to save adjustment');
            } finally {
                this.saving = false;
            }
        },
        async clearAdjustment() {
            this.saving = true;
            try {
                await axios.put(`/api/year-end-census/clients/${this.year}/${this.clientId}`, {
                    adjusted_balance: null
                });
                this.adjustmentPlus = 0;
                this.adjustmentMinus = 0;
                useToast().success('Adjustment cleared');
                this.loadBreakdown();
                this.$emit('updated');
            } catch (error) {
                useToast().error('Failed to clear adjustment');
            } finally {
                this.saving = false;
            }
        },
        async exportIndividualPDF() {
            this.exportingPDF = true;
            try {
                const response = await axios.get(
                    `/api/year-end-census/clients/${this.year}/${this.clientId}/export`,
                    { responseType: 'blob' }
                );
                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;
                const clientName = this.breakdown.client_name || 'client';
                link.setAttribute('download', `client-breakdown-${clientName}-${this.year}.pdf`);
                document.body.appendChild(link);
                link.click();
                link.remove();
                window.URL.revokeObjectURL(url);
                useToast().success('PDF exported successfully');
            } catch (error) {
                useToast().error('Failed to export PDF');
            } finally {
                this.exportingPDF = false;
            }
        },
        async updateStatus(newStatus) {
            this.updatingStatus = true;
            try {
                await axios.put(`/api/year-end-census/clients/${this.year}/${this.clientId}`, {
                    status: newStatus
                });
                useToast().success('Status updated');
                this.loadBreakdown();
                this.$emit('updated');
            } catch (error) {
                useToast().error('Failed to update status');
            } finally {
                this.updatingStatus = false;
            }
        },
        toggleSection(section) {
            this.expandedSections[section] = !this.expandedSections[section];
        },
        formatNumber(value) {
            if (value === null || value === undefined) return '0.00';
            return Number(value).toLocaleString('en-US', { 
                minimumFractionDigits: 2, 
                maximumFractionDigits: 2 
            });
        },
        formatStatus(status) {
            const map = { 'pending': 'Pending', 'ready_to_close': 'Ready to Close', 'closed': 'Closed' };
            return map[status] || status;
        }
    }
};
</script>


<style scoped lang="scss">
.modal-overlay {
    position: fixed; top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0, 0, 0, 0.7); display: flex; align-items: center; justify-content: center; z-index: 1000;
}
.modal-content {
    background: #1f2937; border-radius: 12px; width: 90%; max-width: 700px; max-height: 90vh; overflow-y: auto; color: white;
}
.modal-header {
    display: flex; justify-content: space-between; align-items: center; padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.1);
}
.modal-header h2 { margin: 0; font-size: 18px; }
.close-btn { background: none; border: none; color: rgba(255,255,255,0.6); font-size: 18px; cursor: pointer; }
.close-btn:hover { color: white; }

.loading { text-align: center; padding: 40px; color: rgba(255,255,255,0.7); }

.client-info { padding: 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
.client-info h3 { margin: 0; }
.year-badge { background: rgba(59, 130, 246, 0.2); color: #3b82f6; padding: 4px 12px; border-radius: 12px; font-size: 14px; }

.breakdown-sections { padding: 0 20px; }
.section { border-bottom: 1px solid rgba(255,255,255,0.1); }
.section.initial { background: rgba(255,255,255,0.05); margin: 0 -20px; padding: 0 20px; }
.section-header { display: flex; justify-content: space-between; padding: 12px 0; cursor: pointer; }
.section-header:hover { background: rgba(255,255,255,0.02); margin: 0 -20px; padding: 12px 20px; }
.section-header i { width: 16px; margin-right: 8px; font-size: 12px; }
.section-items { padding: 0 0 12px 24px; }
.item { display: flex; justify-content: space-between; padding: 6px 0; font-size: 13px; color: rgba(255,255,255,0.7); }

.amount { font-weight: 500; }
.amount.positive { color: #22c55e; }
.amount.negative { color: #ef4444; }

.summary-section { padding: 20px; background: rgba(255,255,255,0.05); margin: 20px; border-radius: 8px; }
.summary-row { display: flex; justify-content: space-between; padding: 8px 0; }
.summary-row.final { font-size: 18px; font-weight: bold; border-top: 1px solid rgba(255,255,255,0.2); padding-top: 12px; margin-top: 8px; }
.direction-label { font-size: 12px; font-weight: normal; opacity: 0.7; }

.adjustment-section { padding: 16px 0; border-top: 1px solid rgba(255,255,255,0.1); margin-top: 12px; }
.adjustment-section h4 { margin: 0 0 12px 0; font-size: 14px; color: #3b82f6; }
.adjustment-inputs { display: flex; gap: 20px; margin-bottom: 12px; }
.adjustment-field { flex: 1; }
.adjustment-field label { display: block; font-size: 12px; margin-bottom: 4px; color: rgba(255,255,255,0.8); }
.adjustment-input { width: 100%; padding: 8px 12px; border-radius: 4px; border: 1px solid rgba(255,255,255,0.3); background: rgba(255,255,255,0.1); color: white; font-size: 14px; }
.field-help { display: block; font-size: 10px; color: rgba(255,255,255,0.5); margin-top: 2px; }
.adjustment-actions { display: flex; gap: 10px; }

.export-section { padding-top: 12px; border-top: 1px solid rgba(255,255,255,0.1); margin-top: 12px; }

.adjustment-row { display: flex; align-items: center; gap: 10px; padding: 12px 0; border-top: 1px solid rgba(255,255,255,0.1); margin-top: 8px; }
.adjustment-row label { font-size: 14px; }
.adjustment-input { flex: 1; padding: 8px 12px; border-radius: 4px; border: 1px solid rgba(255,255,255,0.3); background: rgba(255,255,255,0.1); color: white; font-size: 14px; }

.status-row { display: flex; align-items: center; gap: 12px; padding-top: 12px; border-top: 1px solid rgba(255,255,255,0.1); margin-top: 12px; }
.status-pill { padding: 4px 12px; border-radius: 12px; font-size: 12px; }
.status-pill.pending { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }
.status-pill.ready_to_close { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
.status-pill.closed { background: rgba(239, 68, 68, 0.2); color: #ef4444; }

.btn { padding: 8px 16px; border: none; border-radius: 6px; font-size: 13px; cursor: pointer; }
.btn:disabled { opacity: 0.5; cursor: not-allowed; }
.btn.save-btn { background: #3b82f6; color: white; }
.btn.clear-btn { background: rgba(255,255,255,0.1); color: white; }
.btn.export-individual-btn { background: #10b981; color: white; }
.btn.status-btn { margin-left: auto; }
.btn.status-btn.ready { background: #22c55e; color: white; }
.btn.status-btn.revert { background: #fbbf24; color: #1f2937; }
</style>

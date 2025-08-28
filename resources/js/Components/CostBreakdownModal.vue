<template>
    <div v-if="visible" class="cost-breakdown-modal" @click="emitClose">
        <div class="cost-breakdown-content" @click.stop>
            <button @click="emitClose" class="cost-breakdown-close-btn">
                <i class="fa fa-times"></i>
            </button>

            <div class="cost-breakdown-header">
                <h3>Cost Breakdown</h3>
                <p class="job-name">{{ job?.name }}</p>
            </div>

            <div class="cost-breakdown-body">
                <div class="breakdown-section">
                    <h4>Job Details</h4>
                    <div class="breakdown-grid">
                        <div class="breakdown-item">
                            <span class="label">Quantity:</span>
                            <span class="value">{{ job?.quantity }}</span>
                        </div>
                        <div class="breakdown-item">
                            <span class="label">Copies:</span>
                            <span class="value">{{ job?.copies }}</span>
                        </div>
                        <div class="breakdown-item">
                            <span class="label">Total Area:</span>
                            <span class="value">{{ (parseFloat(job?.total_area_m2) || 0).toFixed(4) }} m²</span>
                        </div>
                        <div class="breakdown-item">
                            <span class="label">Pricing Method:</span>
                            <span class="value">{{ job?.catalogItem?.by_copies ? 'By Copies' : 'By Quantity' }}</span>
                        </div>
                    </div>
                </div>

                <div class="breakdown-section">
                    <h4>Pricing Summary</h4>
                    <div class="pricing-summary">
                        <div class="pricing-item">
                            <span class="pricing-label">Sale Price:</span>
                            <span class="pricing-value sale-price">{{ ((job?.salePrice || job?.price) && !isNaN(parseFloat(job?.salePrice || job?.price))) ? parseFloat(job?.salePrice || job?.price).toFixed(2) : '0.00' }} ден.</span>
                        </div>
                        <div class="pricing-item">
                            <span class="pricing-label">Cost Price:</span>
                            <span class="pricing-value cost-price">{{ (Number.isFinite(parseFloat((costBreakdown.total_cost ?? job?.price)))) ? parseFloat((costBreakdown.total_cost ?? job?.price)).toFixed(2) : '0.00' }} ден.</span>
                        </div>
                        <div v-if="job?.salePrice && !isNaN(parseFloat(job.salePrice)) && (parseFloat(job.salePrice) !== parseFloat(job?.price || 0))" class="pricing-item">
                            <span class="pricing-label">Profit Margin:</span>
                            <span class="pricing-value profit-margin">{{ (parseFloat(job?.salePrice || 0) - parseFloat(job?.price || 0)).toFixed(2) }} ден.</span>
                        </div>
                    </div>
                </div>

                <div class="breakdown-section">
                    <h4>Cost Calculation</h4>
                    <div v-if="Array.isArray(costBreakdown.component_breakdown) && costBreakdown.component_breakdown.length > 0">
                        <div class="component-breakdown">
                            <div v-for="(component, index) in (costBreakdown.component_breakdown || [])" :key="index" class="component-item">
                                <div class="component-header">
                                    <span class="component-name">{{ component.article_name || 'Unknown Article' }}</span>
                                    <span class="component-cost">{{ (parseFloat(component.total_cost) || 0).toFixed(2) }} ден.</span>
                                </div>
                                <div class="component-details">
                                    <div class="detail-row">
                                        <span class="detail-label">Catalog Quantity:</span>
                                        <span class="detail-value">{{ component.catalog_quantity || 0 }}</span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Actual Required:</span>
                                        <span class="detail-value">{{ (parseFloat(component.actual_required) || 0).toFixed(4) }} {{ component.unit_type || 'units' }}</span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Article Price:</span>
                                        <span class="detail-value">{{ (parseFloat(component.article_price) || 0).toFixed(2) }} ден.</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="total-cost">
                            <span class="total-label">Total Cost:</span>
                            <span class="total-value">{{ (parseFloat(costBreakdown.total_cost) || parseFloat(job?.price) || 0).toFixed(2) }} ден.</span>
                        </div>
                    </div>
                    <div class="no-breakdown" v-else>
                        <p>No detailed cost breakdown available. Using stored cost price.</p>
                        <div class="stored-cost">
                            <span class="label">Stored Cost:</span>
                            <span class="value">{{ (parseFloat(job?.price) || 0).toFixed(2) }} ден.</span>
                        </div>
                    </div>
                </div>

                <div class="breakdown-section">
                    <h4>Formulas Used</h4>
                    <div class="formula-display">
                        <p v-if="job?.catalogItem?.by_copies">
                            <strong>Copies-based Pricing:</strong><br>
                            Copies × Total Area (m²) × Component Article Quantity per m² × Article Price
                        </p>
                        <p v-else>
                            <strong>Quantity-based Pricing:</strong><br>
                            Quantity × Component Article Quantity × Article Price
                        </p>
                        <hr />
                        <p v-if="job?.catalogItem?.by_copies">
                            <strong>Material Deduction:</strong><br>
                            Quantity Consumed = Copies × Total Area (m²) × Article Qty per m²
                        </p>
                        <p v-else>
                            <strong>Material Deduction:</strong><br>
                            Quantity Consumed = Quantity × Article Qty (per piece or unit)
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';
import { useToast } from 'vue-toastification';

export default {
    name: 'CostBreakdownModal',
    props: {
        visible: { type: Boolean, default: false },
        job: { type: Object, default: null }
    },
    emits: ['close'],
    data() {
        return {
            costBreakdown: {
                total_cost: 0,
                component_breakdown: [],
                material_deduction: []
            }
        };
    },
    watch: {
        visible(val) {
            if (val && this.job && this.job.id) {
                this.fetchCostBreakdown();
            }
        },
        job: {
            handler(newVal) {
                if (this.visible && newVal && newVal.id) {
                    this.fetchCostBreakdown();
                }
            },
            deep: true
        }
    },
    methods: {
        emitClose() {
            this.$emit('close');
        },
        async fetchCostBreakdown() {
            const toast = useToast();
            try {
                const response = await axios.post('/jobs/recalculate-cost', {
                    job_id: this.job.id,
                    total_area_m2: parseFloat(this.job.total_area_m2) || 0,
                    quantity: parseInt(this.job.quantity) || 1,
                    copies: parseInt(this.job.copies) || 1
                });

                const componentBreakdown = Array.isArray(response.data.component_breakdown) ? response.data.component_breakdown : [];
                const materialDeduction = Array.isArray(response.data.material_deduction) ? response.data.material_deduction : [];

                this.costBreakdown = {
                    total_cost: parseFloat(response.data.price) || 0,
                    component_breakdown: componentBreakdown,
                    material_deduction: materialDeduction
                };
            } catch (error) {
                console.error('Failed to get cost breakdown:', error);
                toast.error('Failed to load cost breakdown details');
                this.costBreakdown = {
                    total_cost: parseFloat(this.job?.price) || 0,
                    component_breakdown: [],
                    material_deduction: []
                };
            }
        }
    }
};
</script>

<style scoped lang="scss">
.cost-breakdown-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 2000;
}

.cost-breakdown-content {
    background: $dark-gray;
    border-radius: 8px;
    padding: 20px;
    max-width: 800px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.35);
    border: 1px solid rgba(255,255,255,0.15);
}

.cost-breakdown-close-btn {
    position: absolute;
    top: 15px;
    right: 20px;
    background: $red;
    border: 2px solid $white;
    color: $white;
    width: 36px;
    height: 36px;
    border-radius: 50%;
}

.cost-breakdown-header { text-align: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid $white; }
.cost-breakdown-header h3 { margin: 0 0 10px 0; color: $white; font-size: 24px; font-weight: bold; }
.job-name { margin: 0; color: $blue; font-size: 16px; font-style: italic; }
.cost-breakdown-body { display: flex; flex-direction: column; gap: 25px; }

.breakdown-section { background: $ultra-light-gray; border-radius: 8px; padding: 20px; border-left: 4px solid $blue; }
.breakdown-section h4 { margin: 0 0 15px 0; color: $white; font-size: 18px; font-weight: bold; border-bottom: 1px solid rgba(255,255,255,0.15); padding-bottom: 8px; }
.breakdown-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
.breakdown-item { display: flex; justify-content: space-between; align-items: center; padding: 10px; background: $dark-gray; border-radius: 6px; border: 1px solid rgba(255,255,255,0.1); }
.breakdown-item .label { font-weight: 600; color: $white; }
.breakdown-item .value { font-weight: bold; color: $white; }

.pricing-summary { display: flex; flex-direction: column; gap: 12px; }
.pricing-item { display: flex; justify-content: space-between; align-items: center; padding: 12px; background: $dark-gray; border-radius: 6px; border: 1px solid rgba(255,255,255,0.1); }
.pricing-label { font-weight: 600; color: $white; }
.pricing-value { font-weight: bold; font-size: 16px; color: $white; }
.pricing-value.sale-price { color: $light-green; }
.pricing-value.cost-price { color: #ffd700; }
.pricing-value.profit-margin { color: #1ba5e4; }

.component-breakdown { display: flex; flex-direction: column; gap: 15px; }
.component-item { background: $dark-gray; border-radius: 6px; border: 1px solid rgba(255,255,255,0.1); overflow: hidden; }
.component-header { display: flex; justify-content: space-between; align-items: center; padding: 12px 15px; background: $blue; color: $white; }
.component-name { font-weight: bold; }
.component-cost { font-weight: bold; }
.component-details { padding: 15px; color: $white; }
.detail-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
.detail-label { font-weight: 600; color: $white; }
.detail-value { font-weight: 500; color: $white; }
.total-cost { display: flex; justify-content: space-between; align-items: center; padding: 15px; background: rgba(0,0,0,0.25); border-radius: 6px; margin-top: 15px; border: 2px solid $light-green; color: $white; }
.total-label { font-weight: bold; }
.total-value { font-weight: bold; }

.no-breakdown, .no-deduction { text-align: center; padding: 20px; color: $light-gray; font-style: italic; }
.stored-cost { display: flex; justify-content: space-between; align-items: center; padding: 15px; background: rgba(255,255,255,0.05); border-radius: 6px; margin-top: 15px; border: 1px solid rgba(255,255,255,0.1); color: $white; }

.material-breakdown { display: flex; flex-direction: column; gap: 15px; }
.material-item { background: $dark-gray; border-radius: 6px; border: 1px solid rgba(255,255,255,0.1); overflow: hidden; }
.material-header { display: flex; justify-content: space-between; align-items: center; padding: 12px 15px; background: #fff3cd; border-bottom: 1px solid rgba(255,255,255,0.1); color: #333; }
.material-name { font-weight: bold; }
.material-quantity { font-weight: bold; }
.material-details { padding: 15px; color: $white; }

.formula-display { background: $dark-gray; padding: 15px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.1); color: $white; }
.formula-display p { margin: 0 0 8px 0; line-height: 1.6; }
.formula-display strong { color: $light-green; }
</style>

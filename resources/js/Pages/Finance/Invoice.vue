<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <div class="sidebar" v-if="isSidebarVisible">
                <button @click="toggleSidebar" class="close-sidebar">
                    <span class="mdi mdi-close"></span>
                </button>
            </div>
            <div class="invoice-container">
                <div class="invoice-header">
                    <Header title="invoice2" subtitle="invoiceEdit" icon="invoice.png" link="allInvoices"/>
                    <div class="invoice-actions">
                        <button class="btn btn-edit-mode" @click="toggleEditMode">
                            {{ isEditMode ? 'Exit Edit Mode' : 'Enter Edit Mode' }}
                            <i class="fas fa-edit"></i>
                        </button>
                        <!-- <button class="btn comment-order" @click="toggleSpreadsheetMode">
                            {{ spreadsheetMode ? 'Detail View' : 'Spreadsheet View' }}
                            <i class="fa-regular fa-table"></i>
                        </button> -->
                        <div>
                            <UpdateDialogComment :invoice="invoice"/>
                        </div>
                        <button class="btn generate-invoice" @click="printInvoice">
                            Print Invoice <i class="fa-solid fa-file-invoice-dollar"></i>
                        </button>
                    </div>
                </div>

                <!-- Client Information Row -->
                <div class="client-info-section">
                    <div class="client-info">
                        <div class="client-label">Client</div>
                        <div class="client-name">{{invoice[0]?.client}}</div>
                    </div>
                </div>

                <!-- Invoice Information Row -->
                <div class="invoice-info-minimal">
                    <div class="info-item">
                        <span class="info-label">Invoice ID</span>
                        <span class="info-value">{{ invoice[0]?.fakturaId }}/{{new Date(invoice[0]?.created).toLocaleDateString('en-US', { year: 'numeric'})}}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Created by</span>
                        <span class="info-value">{{invoice[0]?.createdBy}}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Date Created</span>
                        <span class="info-value">
                            <div v-if="isEditMode && editingDate" class="date-edit-container">
                                <input 
                                    v-model="dateEdit"
                                    @keyup.enter="saveDate"
                                    class="date-input"
                                    type="date"
                                />
                                <button @click="saveDate" class="save-btn" title="Save">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button @click="cancelEditDate" class="cancel-btn" title="Cancel">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div v-else class="date-display" @click="startEditDate">
                                {{new Date(invoice[0]?.created).toLocaleDateString('en-US', { year: 'numeric', month: '2-digit', day: '2-digit' })}}
                                <i v-if="isEditMode" class="fas fa-edit edit-icon"></i>
                            </div>
                        </span>
                    </div>
                    <div class="info-item comment" v-if="invoice[0]?.faktura_comment">
                        <span class="info-label">Comment</span>
                        <span class="info-value">{{invoice[0]?.faktura_comment}}</span>
                    </div>
                </div>
                <!-- All Orders Container -->
                <div class="orders-container">
                    <div class="section-header">
                        <h3 class="section-title">Orders & Jobs</h3>
                    </div>
                    
                    <div v-for="(invoiceData, index) in invoice" :key="index" class="order-section">
                        <!-- Order Separator (except for first order) -->
                        <div  class="order-separator">
                            <div class="separator-line"></div>
                            <span class="separator-text">Order {{ index + 1 }}</span>
                            <div class="separator-line"></div>
                        </div>

                        <!-- Order Header -->
                        <div class="order-header">
                            <div class="order-details">
                                <div class="detail-item title-item">
                                    <span class="label">Title:</span>
                                    <div class="title-content">
                                        <div v-if="isEditMode && editingTitle[invoiceData.id]" class="title-edit-container">
                                            <input 
                                                v-model="titleEdits[invoiceData.id]"
                                                @keyup.enter="saveTitle(invoiceData)"
                                                class="title-input"
                                                type="text"
                                            />
                                            <button @click="saveTitle(invoiceData)" class="save-btn" title="Save">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button @click="cancelEditTitle(invoiceData)" class="cancel-btn" title="Cancel">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                        <div v-else class="invoice-title" @click="startEditTitle(invoiceData)">
                                          {{ invoiceData.invoice_title }}
                                            <i v-if="isEditMode" class="fas fa-edit edit-icon"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <span class="label">Order:</span>
                                    <span>#{{ invoiceData?.id }}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="label">{{ $t('startDate') }}:</span>
                                    <span>{{ invoiceData?.start_date }}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="label">{{ $t('endDate') }}:</span>
                                    <span>{{ invoiceData?.end_date }}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="label">Created By:</span>
                                    <span>{{ invoiceData.user }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Jobs for this Order -->
                        <div class="jobs-section">
                            <!-- Edit Mode - Professional Job Editing -->
                            <div v-if="isEditMode && !spreadsheetMode" class="jobs-edit-mode">
                                <InvoiceJobEdit 
                                    v-for="job in invoiceData.jobs" 
                                    :key="job.id"
                                    :job="job"
                                    :faktura-id="invoiceData.fakturaId"
                                    @job-updated="onJobUpdated"
                                />
                            </div>
                            
                            <!-- Detail View Mode -->
                            <div v-else-if="!spreadsheetMode" class="jobs-detail-mode">
                                <div v-for="(job, jobIndex) in invoiceData.jobs" :key="job.id" class="job-card">
                                    <div class="job-header">
                                        <div class="job-title">
                                            #{{ jobIndex + 1 }} {{ job.name }}
                                        </div>
                                    </div>
                                    <div class="job-details-grid">
                                        <div class="detail-item">
                                            <span class="label">{{ $t('height') }}:</span>
                                            <span class="value">{{ formatDimension(job.height) }} mm</span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="label">{{ $t('width') }}:</span>
                                            <span class="value">{{ formatDimension(job.width) }} mm</span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="label">{{ $t('quantity') }}:</span>
                                            <span class="value">{{ job.quantity }}</span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="label">{{ $t('copies') }}:</span>
                                            <span class="value">{{ job.copies }}</span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="label">{{ $t('totalm') }}<sup>2</sup>:</span>
                                            <span class="value">{{ formatArea(job.computed_total_area_m2) }}</span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="label">{{ $t('salePrice') }}:</span>
                                            <span class="value price">{{ formatPrice(job.salePrice) }} ден.</span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="label">{{ $t('jobPrice') }}:</span>
                                            <span class="value total-price">{{ formatPrice(job.totalPrice) }} ден.</span>
                                        </div>
                                    </div>
                                    <div class="material-info" v-if="getMaterialInfo(job)">
                                        <span class="label">{{ $t('material') }}:</span>
                                        <span class="value">{{ getMaterialInfo(job) }}</span>
                                    </div>
                                    <div class="shipping-info" v-if="job.shippingInfo">
                                        <span class="label">{{ $t('shippingTo') }}:</span>
                                        <span class="value">{{ job.shippingInfo }}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Spreadsheet Mode -->
                            <div v-else class="spreadsheet-mode">
                                <OrderSpreadsheet :invoice="invoiceData"/>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Trade Items Section - At the Bottom -->
                <div class="trade-items-section" v-if="isEditMode || (tradeItems && tradeItems.length > 0)">
                    <TradeItemsEdit 
                        v-if="isEditMode"
                        :trade-items="tradeItems || []"
                        :faktura-id="faktura.id"
                        @trade-items-updated="onTradeItemsUpdated"
                    />
                    <div v-else class="trade-items-display">
                        <div class="section-header">
                            <h3 class="section-title">Trade Items</h3>
                        </div>
                        <div class="trade-items-list">
                            <div v-for="item in tradeItems" :key="item.id" class="trade-item-card">
                                <div class="item-info">
                                    <div class="item-name">{{ item.article_name }}</div>
                                    <div class="item-code" v-if="item.article_code">Article Code: {{ item.article_code }}</div>
                                </div>
                                <div class="item-details">
                                    <span>Qty: {{ item.quantity }}</span>
                                    <span>@ {{ formatPrice(getTradeItemUnitPrice(item)) }} ден.</span>
                                    <span class="total">{{ formatPrice(getTradeItemTotalWithVat(item)) }} ден.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script>
import MainLayout from "@/Layouts/MainLayout.vue";
import axios from "axios";
import Toast, {useToast} from "vue-toastification";
import OrderJobDetails from "@/Pages/Invoice/OrderJobDetails.vue";
import OrderSpreadsheet from "@/Components/OrderSpreadsheet.vue";
import Header from "@/Components/Header.vue";
import UpdateDialogComment from "@/Components/UpdateDialogComment.vue";
import InvoiceJobEdit from "@/Components/InvoiceJobEdit.vue";
import TradeItemsEdit from "@/Components/TradeItemsEdit.vue";

export default {
    components: {
        OrderSpreadsheet,
        OrderJobDetails,
        MainLayout,
        Header,
        UpdateDialogComment,
        InvoiceJobEdit,
        TradeItemsEdit
    },
    props: {
        invoice: Object,
        faktura: Object,
        tradeItems: Array,
    },
    computed:{
        getStatusColorClass() {
            const invoiceStatus = this.invoiceData.status;
            if (invoiceStatus === "Not started yet") {
                return "orange-text";
            } else if (invoiceStatus === "In progress") {
                return "blue-text";
            } else if (invoiceStatus === "Completed") {
                return "green-text";
            }
        },
    },
    data() {
        return {
            showImagePopover: false,
            selectedJob: null,
            isSidebarVisible: false,
            spreadsheetMode: false,
            isEditMode: false,
            backgroundColor: null,
            openDialog: false,
            editingTitle: {},
            titleEdits: {},
            editingDate: false,
            dateEdit: '',
            toast: useToast()
        }
    },
    mounted() {
        // Component mounted
    },
    methods: {
        toggleSidebar() {
            this.isSidebarVisible = !this.isSidebarVisible;
        },
        toggleSpreadsheetMode(){
            this.spreadsheetMode = !this.spreadsheetMode;
        },
        toggleEditMode() {
            this.isEditMode = !this.isEditMode;
            if (!this.isEditMode) {
                // Clear any editing states when exiting edit mode
                this.editingTitle = {};
                this.titleEdits = {};
                this.editingDate = false;
                this.dateEdit = '';
            }
        },
        generatePdf(invoiceId) {
            window.open(`/orders/${invoiceId}/pdf`, '_blank');
        },
        getStatusClass(status) {
            if (status === "Not started yet") {
                return "status-orange";
            } else if (status === "In progress") {
                return "status-blue";
            } else if (status === "Completed") {
                return "status-green";
            }
            return "";
        },
        formatPrice(price) {
            if (!price && price !== 0) return '0.00';
            return typeof price === 'number' ? price.toFixed(2) : '0.00';
        },
        
        getTradeItemUnitPrice(item) {
            return Number(item.unit_price || 0);
        },
        
        getTradeItemTotalWithVat(item) {
            // Calculate total price from unit_price and quantity
            const totalPrice = Number(item.quantity || 0) * Number(item.unit_price || 0);
            
            // Calculate VAT amount
            const vatAmount = totalPrice * (Number(item.vat_rate || 0) / 100);
            
            // Return total with VAT
            return totalPrice + vatAmount;
        },
        formatDimension(dimension) {
            if (!dimension && dimension !== 0) return '0.00';
            return typeof dimension === 'number' ? dimension.toFixed(2) : '0.00';
        },
        formatArea(area) {
            if (!area && area !== 0) return '0.0000';
            return typeof area === 'number' ? area.toFixed(4) : '0.0000';
        },
        getMaterialInfo(job) {
            if (job.articles && job.articles.length > 0) {
                return job.articles.map(article => {
                    const unit = article.in_square_meters ? 'm²' : 
                               article.in_pieces ? 'ком.' : 
                               article.in_kilograms ? 'кг' : 
                               article.in_meters ? 'м' : 'ед.';
                    return `${article.name} (${article.pivot.quantity} ${unit})`;
                }).join(', ');
            } else if (job.large_material) {
                return job.large_material.name;
            } else if (job.small_material) {
                return job.small_material.name;
            }
            return null;
        },
        startEditTitle(invoiceData) {
            if (!this.isEditMode) return;
            
            this.editingTitle[invoiceData.id] = true;
            this.titleEdits[invoiceData.id] = invoiceData.invoice_title;
            
            // Focus the input in the next tick
            this.$nextTick(() => {
                const input = document.querySelector('.title-input');
                if (input) {
                    input.focus();
                    input.select();
                }
            });
        },
        async saveTitle(invoiceData) {
            if (!this.titleEdits[invoiceData.id] || this.titleEdits[invoiceData.id] === invoiceData.invoice_title) {
                this.cancelEditTitle(invoiceData);
                return;
            }

            try {
                const response = await axios.put(
                    `/invoice/${invoiceData.fakturaId}/invoice/${invoiceData.id}/title`,
                    { invoice_title: this.titleEdits[invoiceData.id] }
                );

                if (response.data.invoice) {
                    invoiceData.invoice_title = this.titleEdits[invoiceData.id];
                    this.toast.success('Invoice title updated successfully');
                }

                this.cancelEditTitle(invoiceData);

            } catch (error) {
                console.error('Error updating title:', error);
                this.toast.error('Failed to update invoice title');
                this.cancelEditTitle(invoiceData);
            }
        },
        cancelEditTitle(invoiceData) {
            this.editingTitle[invoiceData.id] = false;
            delete this.titleEdits[invoiceData.id];
        },
        startEditDate() {
            if (!this.isEditMode) return;
            
            this.editingDate = true;
            // Convert the current date to YYYY-MM-DD format for the date input
            const currentDate = new Date(this.invoice[0]?.created);
            this.dateEdit = currentDate.toISOString().split('T')[0];
            
            // Focus the input in the next tick
            this.$nextTick(() => {
                const input = document.querySelector('.date-input');
                if (input) {
                    input.focus();
                }
            });
        },
        async saveDate() {
            if (!this.dateEdit) {
                this.cancelEditDate();
                return;
            }

            try {
                const response = await axios.put(
                    `/invoice/${this.invoice[0].fakturaId}/date`,
                    { created: this.dateEdit }
                );

                if (response.data.success) {
                    // Update the invoice data
                    this.invoice[0].created = this.dateEdit;
                    this.toast.success('Invoice date updated successfully');
                }

                this.cancelEditDate();

            } catch (error) {
                console.error('Error updating date:', error);
                this.toast.error('Failed to update invoice date');
                this.cancelEditDate();
            }
        },
        cancelEditDate() {
            this.editingDate = false;
            this.dateEdit = '';
        },
        onJobUpdated(updatedJob) {
            // Find and update the job in the invoice data
            this.invoice.forEach(invoiceData => {
                const jobIndex = invoiceData.jobs.findIndex(job => job.id === updatedJob.id);
                if (jobIndex !== -1) {
                    invoiceData.jobs[jobIndex] = updatedJob;
                }
            });
        },
        onTradeItemsUpdated(updatedTradeItems) {
            // Update trade items - they belong to the faktura, not individual invoices
            this.tradeItems = updatedTradeItems;
        },
        async printInvoice() {
            const toast = useToast();
            const selectedId = this.invoice[0].id;

            try {
                const response = await axios.post('/outgoing/invoice', { invoiceIds: [selectedId], generated: true, debug: true }, {
                    responseType: 'json',
                    validateStatus: () => true,
                });

                if (response.headers['content-type']?.includes('application/json')) {
                    console.log('Print data sent to PDF:', response.data);
                    const hasTrade = Array.isArray(response.data?.invoices) && response.data.invoices.some(inv => (inv.trade_items || []).length > 0);
                    if (!hasTrade) {
                        toast.error('No trade items found in print payload.');
                    } else {
                        toast.success('Trade items present in print payload. Switching to PDF...');
                    }
                    // Now request the actual PDF (non-debug)
                    const pdfResp = await axios.post('/outgoing/invoice', { invoiceIds: [selectedId], generated: true }, { responseType: 'blob' });
                    const blob = new Blob([pdfResp.data], { type: 'application/pdf' });
                    const url = window.URL.createObjectURL(blob);
                    window.open(url, '_blank');
                } else {
                    const blob = new Blob([response.data], { type: 'application/pdf' });
                    const url = window.URL.createObjectURL(blob);
                    window.open(url, '_blank');
                }
            } catch (error) {
                console.error('Error generating invoices:', error);
                toast.error('An error occurred while generating the invoice. Please try again.');
            }
        },
    },
};
</script>

<style scoped lang="scss">
// Color Variables from existing design
$background-color: #1a2732;
$gray: #3c4e59;
$dark-gray: #2a3946;
$light-gray: #54606b;
$ultra-light-gray: #77808b;
$white: #ffffff;
$black: #000000;
$green: #408a0b;
$light-green: #81c950;
$blue: #0073a9;
$red: #9e2c30;
$orange: #a36a03;

// Modern Invoice Styles
.invoice-container {
    margin: 0 auto;
}

.invoice-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    @media (max-width: 768px) {
        flex-direction: column;
        gap: 20px;
        align-items: stretch;
    }
}

.invoice-actions {
    display: flex;
    gap: 22px;
    align-items: center;

    @media (max-width: 768px) {
        justify-content: center;
        flex-wrap: wrap;
    }
}

.btn {
    padding: 10px 16px;
    border: none;
    border-radius: 2px;
    cursor: pointer;
    font-weight: bold;
    font-size: 14px;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;

    &:hover {
        opacity: 0.9;
    }

    i {
        font-size: 14px;
    }
}

.btn-edit-mode {
    background-color: $orange;
    color: $white;

    &:hover {
        background-color: darken($orange, 10%);
    }
}

.comment-order {
    background-color: $blue;
    color: $white;

    &:hover {
        background-color: darken($blue, 10%);
    }
}

.generate-invoice {
    background-color: $green;
    color: $white;

    &:hover {
        background-color: darken($green, 10%);
    }
}

// Client Information Section
.client-info-section {
    background: linear-gradient(135deg, #51A8B1 0%, darken(#51A8B1, 20%) 100%);
    border-radius: 8px 8px 0 0;
    padding: 20px 24px;
    margin-bottom: 0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.client-info {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
}

.client-label {
    font-size: 14px;
    color: rgba($white, 0.8);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.client-name {
    font-size: 24px;
    color: $white;
    font-weight: 700;
    text-align: center;
}

// Minimal Invoice Information Section
.invoice-info-minimal {
    background: rgba($white, 0.25);
    padding: 16px 24px;
    margin-bottom: 0;
    border: 1px solid rgba($white, 0.1);
    border-top: none;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;

    @media (max-width: 768px) {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
}

.info-item {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;

    &.comment {
        flex-basis: 100%;
        margin-top: 8px;
        padding-top: 12px;
        border-top: 1px solid rgba($white, 0.1);
    }
}

.info-label {
    font-size: 12px;
    color: rgba($white, 0.6);
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
}

.info-value {
    font-size: 14px;
    color: $white;
    font-weight: 600;

    .date-display {
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s ease;
        padding: 4px 8px;
        border-radius: 4px;

        &:hover {
            background: rgba($white, 0.1);
        }

        .edit-icon {
            font-size: 12px;
            opacity: 0.6;
        }
    }

    .date-edit-container {
        display: flex;
        align-items: center;
        gap: 8px;
        width: 100%;
        max-width: 250px;
    }

    .date-input {
        font-size: 14px;
        color: $white;
        background: rgba($white, 0.1);
        border: 2px solid $blue;
        border-radius: 4px;
        padding: 6px 8px;
        font-weight: 600;
        flex: 1;

        &:focus {
            outline: none;
            border-color: $light-green;
            box-shadow: 0 0 0 2px rgba($light-green, 0.3);
        }

        // Style the date picker
        &::-webkit-calendar-picker-indicator {
            filter: invert(1);
            cursor: pointer;
        }
    }

    .save-btn, .cancel-btn {
        padding: 6px 8px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 28px;
        height: 28px;

        &:hover {
            transform: scale(1.05);
        }
    }

    .save-btn {
        background-color: $green;
        color: $white;

        &:hover {
            background-color: darken($green, 10%);
        }
    }

    .cancel-btn {
        background-color: $red;
        color: $white;

        &:hover {
            background-color: darken($red, 10%);
        }
    }
}

// Orders Container
.orders-container {
    background: rgba($white, 0.2);
    border-radius: 0 0 3px 3px;
    padding: 24px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    border: 1px solid $light-gray;
}

.order-section {
    &:not(:last-child) {
        margin-bottom: 30px;
    }
}

.order-separator {
    display: flex;
    align-items: center;
    margin: 10px 0;
    
    .separator-line {
        flex: 1;
        height: 1px;
        background: $white;
    }
    
    .separator-text {
        padding: 0 20px;
        color: $white;
        font-weight: bold;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
}

.order-header {
    background: $white; 
    border-radius: 3px;
    padding: 5px;
    border: 1px solid rgba($light-gray, 0.3);
}

.order-info {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

// Title item specific styles
.title-item {
    .title-content {
        .invoice-title {
            font-size: 16px;
            font-weight: bold;
            color: $black;
            cursor: pointer;
            padding: 0;
            border-radius: 3px;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;

            .edit-icon {
                font-size: 14px;
                opacity: 0.6;
            }
        }

    .title-edit-container {
        display: flex;
        align-items: center;
        gap: 8px;
        width: 100%;
        max-width: 350px;
    }

    .title-input {
        font-size: 16px;
        font-weight: bold;
        color: $black;
        border: 2px solid $blue;
        border-radius: 3px;
        padding: 4px 8px;
        background: $white;
        flex: 1;

        &:focus {
            outline: none;
            border-color: $light-green;
            box-shadow: 0 0 0 2px rgba($light-green, 0.3);
        }
    }
    }
}

.order-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 4px;

    .label {
        font-size: 12px;
        color: $ultra-light-gray;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .value {
        font-size: 14px;
        color: $white;
        font-weight: bold;

        &.status {
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            text-align: center;
            max-width: fit-content;
        }

        &.status-orange {
            background-color: rgba($orange, 0.2);
            color: $orange;
        }

        &.status-blue {
            background-color: rgba($blue, 0.2);
            color: $blue;
        }

        &.status-green {
            background-color: rgba($green, 0.2);
            color: $green;
        }

        &.price {
            color: $blue;
            font-weight: bold;
        }

        &.total-price {
            color: greenyellow;
            font-weight: bold;
            font-size: 16px;
        }
    }
}

// Jobs Section
.jobs-section {
    background: rgba($light-gray, 0.02);
    border-radius: 3px;
    margin-top: 10px;
    border: 1px solid rgba($light-gray, 0.2);
}

.trade-items-section {
    background: $light-gray;
    border-radius: 3px;
    padding: 24px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    border: 1px solid $light-gray;
}

.section-title {
    color: white;
    font-size: 20px;
    font-weight: bold;
    margin: 0;
}

// Job Cards (Detail View)
.job-card {
    background: $dark-gray;
    border: 1px solid $light-gray;
    border-radius: 3px;
    padding: 20px;
    transition: all 0.2s ease;

    &:hover {
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        border-color: $ultra-light-gray;
    }
}

.job-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    padding: 10px;
    background-color: #7DC068;

    @media (max-width: 768px) {
        flex-direction: column;
        gap: 12px;
        align-items: flex-start;
    }
}

.job-title {
    font-size: 16px;
    font-weight: 600;
    color: $black;
}

.job-image {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 6px;
    border: 2px solid #e2e8f0;
}

.job-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 12px;
    
}

.material-info, .shipping-info {
    grid-column: 1 / -1;
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding: 12px;
    background: rgba(white, 0.15);
    border-radius: 6px;
    margin-top: 12px;

    span {
        color: white;
    }
}

// Trade Items Display
.trade-items-display {
    .trade-items-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .trade-item-card {
        background: rgba(white, 0.15);
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;

        @media (max-width: 768px) {
            flex-direction: column;
            gap: 12px;
            align-items: stretch;
        }
    }

    .item-info {
        .item-name {
            font-weight: 600;
            color: $white;
            margin-bottom: 4px;
        }

        .item-code {
            font-size: 12px;
            color: $white;
        }
    }

    .item-details {
        display: flex;
        gap: 16px;
        align-items: center;
        font-size: 14px;
        color: $white;

        @media (max-width: 768px) {
            justify-content: space-between;
        }

        .total {
            font-weight: 600;
            color: greenyellow;
        }
    }
}

// Utility Classes
.bold {
    font-weight: bold;
}

.rounded {
    border-radius: 12px 12px 0 0;
}

// Legacy Styles (preserved for compatibility)
.sidebar {
    position: fixed;
    top: 0;
    right: 0;
    bottom: 0;
    width: 350px;
    background-color: #2d3748;
    z-index: 1000;
    overflow-y: auto;
    padding: 20px;
    border: 1px solid #e2e8f0;
    border-right: none;
    border-radius: 4px 0 0 4px;
}

.close-sidebar {
    position: absolute;
    top: 10px;
    right: 10px;
    background: transparent;
    border: none;
    font-size: 24px;
    color: #fff;
    cursor: pointer;
}

// Spreadsheet styles
table {
    width: 100%;
    border-collapse: collapse;
    border-spacing: 0;
    margin-bottom: 20px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

table th, table td {
    padding: 12px;
    border: 1px solid #e2e8f0;
    text-align: center;
    font-size: 14px;
}

table th {
    color: white;
    background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);
    font-weight: 600;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 0.5px;
}

table tr:nth-child(even) {
    background-color: #f8fafc;
}

table tr:hover {
    background-color: #edf2f7;
}

// Responsive Design
@media (max-width: 1200px) {
    .invoice-container {
        padding: 0 16px;
    }
}

@media (max-width: 768px) {
    .order-details {
        grid-template-columns: 1fr;
    }

    .job-details-grid {
        grid-template-columns: 1fr;
    }

    .invoice-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .btn {
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .invoice-container {
        padding: 0 12px;
    }

    .order-header, .jobs-section, .trade-items-section {
        padding: 16px;
    }

    .job-card {
        padding: 16px;
    }
}
</style>

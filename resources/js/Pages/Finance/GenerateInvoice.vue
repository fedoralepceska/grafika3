<template>
    <MainLayout>
        <div class="pl-7 pr-7 flex">
            <div class="sidebar" v-if="isSidebarVisible">
                <button @click="toggleSidebar" class="close-sidebar">
                    <span class="mdi mdi-close"></span>
                </button>
            </div>
            <div class="left-column flex-1" style="width: 25%">
                <div class="flex justify-between">
                    <Header title="invoice2" subtitle="invoiceGeneration" icon="invoice.png" link="notInvoiced"/>
                    <div class="flex pt-4">
                        <div class="buttons pt-3 flex pr-2">
                            <input type="text" id="comment" class="rounded" v-model="comment" placeholder="Add Invoice Comment" style="width: 500px; height: 45px"/>
                        </div>
                        <div class="buttons pt-3">
                            <button class="btn comment-order" @click="toggleSpreadsheetMode">
                                {{ spreadsheetMode ?  'Edit' : 'Exit Edit Mode' }}
                                <i class="fa-regular fa-edit"></i>
                            </button>
                            <button class="btn blue" @click="showTradeItemsModal = true">
                                Add Trade Items <i class="fa-solid fa-plus"></i>
                            </button>
                            <button class="btn orange" @click="previewInvoice">
                                Preview <i class="fa-solid fa-eye"></i>
                            </button>
                            <button  class="btn generate-invoice" @click="generateInvoice">Generate Invoice <i class="fa-solid fa-file-invoice-dollar"></i></button>
                        </div>
                    </div>
                </div>
                <div class="dark-gray p-5 text-white" v-for="(invoiceData, invoiceId) in invoiceData" :key="invoiceId">
                    <div class="form-container p-2 light-gray">
                        <div class="InvoiceDetails">
                            <div class="invoice-details flex gap-20 relative" >
                                <div class="invoice-title bg-white text-black bold p-3 ">{{ invoiceData?.invoice_title }}</div>
                                <div class="info">
                                    <div>Order</div>
                                    <div class="bold">#{{ invoiceData?.id }}</div>
                                </div>
                                <div class="info">
                                    <div>Customer</div>
                                    <div class="bold">{{ invoiceData.client }}</div>
                                </div>
                                <div class="info">
                                    <div>{{ $t('startDate') }}</div>
                                    <div class="bold">{{ invoiceData?.start_date }}</div>
                                </div>
                                <div class="info">
                                    <div>{{ $t('endDate') }}</div>
                                    <div class="bold">{{ invoiceData?.end_date }}</div>
                                </div>
                                <div class="info">
                                    <div>Created By</div>
                                    <div class="bold">{{ invoiceData.user }}</div>
                                </div>
                                <div class="info">
                                    <div>Status</div>
                                    <span class="bold green-text">{{ invoiceData.status }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-container  light-gray mt-2">
                        <div class="sub-title pl-2 ">{{$t('orderLines')}}</div>
                        <div v-for="(job, index) in invoiceData.jobs" v-if="spreadsheetMode">
                            <div class="jobDetails p-2">
                                <div class="border">
                                    <div class="flex gap-10 pb-3">
                                        <div class="invoice-title bg-white text-black bold p-3">
                                            #{{index+1}} {{job.name}}
                                        </div>
                                            <img :src="`/storage/uploads/${job.file}`" alt="Job Image" class="jobImg thumbnail"/>
                                        <div>{{job.file}}</div>
                                                                    <div>{{$t('height')}}: <span class="bold">{{(job.height && typeof job.height === 'number') ? job.height.toFixed(2) : '0.00'}} mm</span> </div>
                            <div>{{$t('width')}}: <span class="bold">{{(job.width && typeof job.width === 'number') ? job.width.toFixed(2) : '0.00'}} mm</span> </div>
                                        <div>{{$t('quantity')}}: <span class="bold">{{job.quantity}}</span> </div>
                                        <div>{{$t('copies')}}: <span class="bold">{{job.copies}}</span> </div>
                                        <div>
                                            {{$t('material')}}:
                                            <span class="bold">
                                            <span v-if="job.articles && job.articles.length > 0">
                                                <span v-for="(article, index) in job.articles" :key="article.id">
                                                    {{ article.name }} ({{ article.pivot.quantity }} {{ getUnitText(article) }})
                                                    <span v-if="index < job.articles.length - 1">, </span>
                                                </span>
                                            </span>
                                            <span v-else-if="job.large_material_id">{{ job.large_material?.name }}</span>
                                            <span v-else>{{ job?.small_material?.name }}</span>
                                         </span>
                                        </div>
                                        <div>{{$t('totalm')}}<sup>2</sup>: <span class="bold">{{(job.computed_total_area_m2 && typeof job.computed_total_area_m2 === 'number') ? job.computed_total_area_m2.toFixed(4) : '0.0000'}}</span></div>
                                    </div>
                                        <OrderJobDetails :job="job" :invoice-id="invoiceData.id"/>
                                    <div class="jobInfo relative pt-3">
                                        <div class="jobShippingInfo">
                                            <div class=" bg-white text-black bold ">
                                                <div class="flex" style="align-items: center;">
                                                    <img src="/images/shipping.png" class="w-10 h-10 pr-1" alt="Shipping">
                                                    {{$t('Shipping')}}
                                                </div>
                                                <div class="ultra-light-gray p-2 text-white">
                                                    {{$t('shippingTo')}}: <span class="bold">{{job.shippingInfo}}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="jobPriceInfo absolute right-0 bottom-10 bg-white text-black bold">
                                            <div class="p-2">
                                                {{$t('salePrice')}}: <span class="bold">{{(job.salePrice && typeof job.salePrice === 'number') ? job.salePrice.toFixed(2) : '0.00'}} ден.</span>
                                            </div>
                                        </div>
                                        <div class="jobPriceInfo absolute right-0 bottom-0 bg-white text-black bold">
                                            <div class="p-2">
                                                {{$t('jobPrice')}}: <span class="bold">{{(job.totalPrice && typeof job.totalPrice === 'number') ? job.totalPrice.toFixed(2) : '0.00'}} ден.</span>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-center" v-else>
                            <OrderSpreadsheet :invoice="invoiceData"/>
                        </div>
                    </div>
                </div>

                <!-- Trade Items Section -->
                <div class="dark-gray p-5 text-white mt-4" v-if="tradeItems.length > 0">
                    <div class="form-container p-2 light-gray">
                        <div class="sub-title pl-2">Trade Items</div>
                        <table class="trade-items-table">
                            <thead>
                                <tr>
                                    <th>Article</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Total</th>
                                    <th>VAT</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, index) in tradeItems" :key="index">
                                    <td>{{ item.article_name }}</td>
                                    <td>{{ item.quantity }}</td>
                                    <td>{{ formatNumber(item.unit_price) }} ден</td>
                                    <td>{{ formatNumber(item.total_price) }} ден</td>
                                    <td>{{ formatNumber(item.vat_amount) }} ден</td>
                                    <td>
                                        <button @click="removeTradeItem(index)" class="btn delete">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="trade-items-total">
                            <strong>Trade Items Total: {{ formatNumber(tradeItemsTotal) }} ден (incl. VAT)</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trade Items Modal -->
        <div v-if="showTradeItemsModal" class="modal-overlay" @click="closeModal">
            <div class="modal-content" @click.stop>
                <div class="modal-header">
                    <h3>Add Trade Items</h3>
                    <button @click="closeModal" class="close-btn">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Select Article:</label>
                        <select v-model="selectedArticle" class="form-control">
                            <option value="">Choose an article...</option>
                            <option v-for="article in availableTradeArticles" :key="article.id" :value="article">
                                {{ article.article.name }} ({{ article.article.code }}) - Stock: {{ article.quantity }} - Price: {{ formatNumber(article.selling_price || 0) }} ден
                            </option>
                        </select>
                    </div>
                    <div class="form-group" v-if="selectedArticle">
                        <label>Quantity:</label>
                        <input type="number" v-model.number="tradeItemQuantity" :max="selectedArticle.quantity" min="1" class="form-control">
                        <small class="form-help-text">Available: {{ selectedArticle.quantity }}</small>
                    </div>
                    <div class="form-group" v-if="selectedArticle">
                        <label>Unit Price:</label>
                        <input type="number" v-model.number="tradeItemPrice" step="0.01" min="0" class="form-control">
                    </div>
                    <div class="form-group" v-if="selectedArticle && tradeItemQuantity && tradeItemPrice">
                        <div class="price-breakdown">
                            <div>Subtotal: {{ formatNumber(tradeItemQuantity * tradeItemPrice) }} ден</div>
                            <div>VAT (18%): {{ formatNumber((tradeItemQuantity * tradeItemPrice) * 0.18) }} ден</div>
                            <div><strong>Total: {{ formatNumber((tradeItemQuantity * tradeItemPrice) * 1.18) }} ден</strong></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button @click="addTradeItem" :disabled="!canAddTradeItem" class="btn green">Add Item</button>
                    <button @click="closeModal" class="btn delete">Cancel</button>
                </div>
            </div>
        </div>

        <!-- Preview Modal -->
        <div v-if="showPreviewModalFlag" class="modal-overlay" @click="closePreviewModal">
            <div class="preview-modal-content" @click.stop>
                <div class="modal-header">
                    <h3>Invoice Preview</h3>
                    <button @click="closePreviewModal" class="close-btn">&times;</button>
                </div>
                <div class="preview-modal-body">
                    <iframe 
                        :src="previewPdfUrl" 
                        width="100%" 
                        height="600px" 
                        frameborder="0"
                        title="Invoice Preview">
                    </iframe>
                </div>
                <div class="modal-footer">
                    <button @click="closePreviewModal" class="btn delete">Close Preview</button>
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

export default {
    components: {
        OrderSpreadsheet,
        OrderJobDetails,
        MainLayout,
        Header },
    props: {
        invoiceData: Object,
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
        tradeItemsTotal() {
            return this.tradeItems.reduce((total, item) => total + (item.total_price + item.vat_amount), 0);
        },
        canAddTradeItem() {
            return this.selectedArticle && 
                   this.tradeItemQuantity > 0 && 
                   this.tradeItemQuantity <= this.selectedArticle.quantity &&
                   this.tradeItemPrice > 0;
        }
    },
    data() {
        return {
            showImagePopover: false,
            selectedJob: null,
            isSidebarVisible: false,
            spreadsheetMode: true,
            backgroundColor: null,
            openDialog: false,
            comment: '',
            showTradeItemsModal: false,
            availableTradeArticles: [],
            selectedArticle: null,
            tradeItemQuantity: 1,
            tradeItemPrice: 0,
            tradeItems: [],
            showPreviewModalFlag: false,
            previewPdfUrl: null
        }
    },
    methods: {
        toggleSidebar() {
            this.isSidebarVisible = !this.isSidebarVisible;
        },
        toggleSpreadsheetMode(){
            this.spreadsheetMode = !this.spreadsheetMode;
        },
        generatePdf(invoiceId) {
            window.open(`/orders/${invoiceId}/pdf`, '_blank');
        },
        async loadTradeArticles() {
            try {
                const response = await axios.get('/trade-articles');
                this.availableTradeArticles = response.data.data || [];
            } catch (error) {
                console.error('Error loading trade articles:', error);
            }
        },
        closeModal() {
            this.showTradeItemsModal = false;
            this.selectedArticle = null;
            this.tradeItemQuantity = 1;
            this.tradeItemPrice = 0;
        },
        addTradeItem() {
            if (!this.canAddTradeItem) return;
            
            const subtotal = this.tradeItemQuantity * this.tradeItemPrice;
            const vatAmount = subtotal * 0.18;
            
            this.tradeItems.push({
                article_id: this.selectedArticle.article.id,
                article_name: this.selectedArticle.article.name,
                article_code: this.selectedArticle.article.code,
                quantity: this.tradeItemQuantity,
                unit_price: this.tradeItemPrice,
                total_price: subtotal,
                vat_rate: 18.00,
                vat_amount: vatAmount
            });
            
            this.closeModal();
        },
        removeTradeItem(index) {
            this.tradeItems.splice(index, 1);
        },
        formatNumber(number) {
            return Number(number).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        getUnitText(article) {
            if (article.in_square_meters) return 'm²';
            if (article.in_pieces) return 'ком.';
            if (article.in_kilograms) return 'кг';
            if (article.in_meters) return 'м';
            return 'ед.';
        },
        async previewInvoice() {
            const toast = useToast();
            try {
                const orderIds = Object.values(this.invoiceData).map(order => order.id);
                
                const response = await axios.post('/preview-invoice', {
                    orders: orderIds,
                    comment: this.comment,
                    trade_items: this.tradeItems
                }, {
                    responseType: 'blob' // Important for PDF response
                });
                
                // Create blob URL for iframe display
                const blob = new Blob([response.data], { type: 'application/pdf' });
                const url = window.URL.createObjectURL(blob);
                
                // Show preview in modal with iframe
                this.showPreviewModal(url);
                
            } catch (error) {
                let serverMessage = 'Internal Server Error';
                try {
                    if (error?.response?.data instanceof Blob) {
                        const text = await error.response.data.text();
                        try {
                            const parsed = JSON.parse(text);
                            serverMessage = parsed.error || parsed.message || text;
                        } catch (_) {
                            serverMessage = text;
                        }
                    } else if (error?.response?.data) {
                        const data = error.response.data;
                        serverMessage = data.error || data.message || JSON.stringify(data);
                    }
                } catch (_) {}
                console.error('Error generating preview:', serverMessage, error);
                toast.error(`Error generating preview: ${serverMessage}`);
            }
        },
        showPreviewModal(pdfUrl) {
            this.previewPdfUrl = pdfUrl;
            this.showPreviewModalFlag = true;
        },
        closePreviewModal() {
            this.showPreviewModalFlag = false;
            if (this.previewPdfUrl) {
                window.URL.revokeObjectURL(this.previewPdfUrl);
                this.previewPdfUrl = null;
            }
        },
        async generateInvoice() {
            const toast = useToast();
            try {
                const orderIds = Object.values(this.invoiceData).map(order => order.id);
                
                const response = await axios.post('/generate-invoice', {
                    orders: orderIds,
                    comment: this.comment,
                    trade_items: this.tradeItems
                }, {
                    responseType: 'blob' // Important for PDF response
                });

                // Open PDF in new tab
                const blob = new Blob([response.data], { type: 'application/pdf' });
                const url = window.URL.createObjectURL(blob);
                window.open(url, '_blank');

                toast.success('Invoice generated successfully');
            } catch (error) {
                console.error('Error generating invoice:', error);
                toast.error('Error generating invoice!');
            }
        }
    },
    mounted() {
        this.loadTradeArticles();
        
        // Watch for changes in selected article to auto-fill price
        this.$watch('selectedArticle', (newArticle) => {
            if (newArticle && newArticle.selling_price) {
                this.tradeItemPrice = newArticle.selling_price;
            }
        });
      }
  };
  </script>

<style scoped lang="scss">
// Import color variables
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

.circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
}
.flexed{
    justify-content: center;
    align-items: center;
}
.popover-content[data-v-19f5b08d]{
    background-color: #2d3748;
}
.fa-close::before{
    color: white;
}
[type='checkbox']:checked{
    border: 1px solid white;
}
.orange-text {
    color: $orange;
}
.blue-text {
    color: #1ba5e4;
}
.bold {
    font-weight: bold;
}
.green-text {
    color: $green;
}
.light-gray{
    background-color: $light-gray;
}
.ultra-light-gray{
    background-color: $ultra-light-gray;
}
.blue{
    background-color: $blue;
}
.green{
    background-color: $green;
}
.background{
    background-color: $background-color;
}
.header {
    display: flex;
    align-items: center;
}
.dark-gray {
    background-color: $dark-gray;
    justify-content: left;
    align-items: center;
    min-height: 20vh;
    min-width: 80vh;
}
.sub-title {
    font-size: 20px;
    font-weight: bold;
    display: flex;
    align-items: center;
    color: $white;
}
.jobShippingInfo{
    max-width: 300px;
    border: 1px solid  ;
}
.jobPriceInfo{
    max-height: 40px;
    max-width: 30%;
}
.right{
    gap: 34.9rem;
}
.btn {
    margin-right: 4px;
    padding: 10px 15px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    color: white;
    border-radius: 2px;
}
.btn2{
    font-size: 14px;
    margin-right: 4px;
    padding: 7px 10px;
    border: none;
    cursor: pointer;
    color: white;
    background-color: $blue;
    border-radius: 2px;
}
.btns{
    position: absolute;
    top: -11px;
    right: 0;
    padding: 0;
}
.comment-order{
    background-color: $blue;
    color: white;
}
.generate-invoice{
    background-color: $green;
    color: white;
}
.InvoiceDetails{
    border-bottom: 2px dashed lightgray;
}
.bt{
    font-size:45px ;
    cursor: pointer;
    padding: 0;
}
.popover {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1000; /* high z-index to be on top of other content */
}

.popover-content {
    width: 30%;
    background: white;
    padding: 20px;
    border-radius: 8px;
    position: relative;
}

.popover-close {
    position: absolute;
    top: 10px;
    right: 10px;
    color: black;
}

.right-column {
    background-color: $background-color;
    color: white;
    overflow-y: auto;
}

.hamburger {
    z-index: 2000;
    background-color: transparent;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #fff; /* Adjust the color to match your layout */
}

.sidebar {
    position: fixed;
    top: 0;
    right: 0;
    bottom: 0;
    width: 350px; /* Width of sidebar */
    background-color: $background-color; /* Sidebar background color */
    z-index: 1000; /* Should be below the overlay */
    overflow-y: auto;
    padding: 20px;
    border: 1px solid $white;
    border-right:none;
    border-radius: 4px 0 0 4px ;
}

.order-history {
    padding: 20px;
}

.close-sidebar {
    position: absolute;
    top: 10px;
    right: 10px;
    background: transparent;
    border: none;
    font-size: 24px;
    color: #fff; /* Adjust close button color */
    cursor: pointer;
}

.is-blurred {
    filter: blur(5px);
}

.content {
    transition: filter 0.3s; /* Smooth transition for the blur effect */
}

.history-subtitle {
    background-color: white;
    color: black;
    padding: 10px;
    margin-bottom: 10px;
    font-weight: bold;
}
.jobImg {
    width: 45px;
    height: 45px;
}
/*
spreadheet style
*/
table {
    width: 100%;
    border-collapse: collapse;
    border-spacing: 0;
    margin-bottom: 20px;
    border: 1px solid #ddd;
    border-radius: 5px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

table th, table td {
    padding: 10px;
    border: 1px solid #ddd;
    text-align: center;
}

table th {
    color: white;
    border-top: 1px solid #ddd;
    border-bottom: 1px solid #ddd;
    background-color: $ultra-light-gray;
}

/* Trade Items Styles */
.trade-items-table {
    width: 100%;
    margin-top: 10px;
    border-collapse: collapse;
}

.trade-items-table th,
.trade-items-table td {
    padding: 8px;
    border: 1px solid #ddd;
    text-align: center;
}

.trade-items-table th {
    background-color: $ultra-light-gray;
    color: white;
}

.trade-items-total {
    text-align: right;
    margin-top: 10px;
    padding: 10px;
    background-color: $dark-gray;
    border-radius: 4px;
}

/* Modal Styles */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.modal-content {
    background-color: $dark-gray;
    border: 1px solid $ultra-light-gray;
    border-radius: 8px;
    width: 90%;
    max-width: 600px;
    max-height: 80vh;
    overflow-y: auto;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid $ultra-light-gray;
    background-color: $dark-gray;
}

.modal-header h3 {
    color: $white;
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
}

.close-btn {
    background: none;
    border: none;
    color: $white;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0.25rem;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    transition: background-color 0.2s;
}

.close-btn:hover {
    background-color: $ultra-light-gray;
}

.modal-body {
    padding: 1.5rem;
    color: $white;
    background-color: $dark-gray;
}

.modal-footer {
    padding: 1rem 1.5rem;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    border-top: 1px solid $ultra-light-gray;
    background-color: $dark-gray;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    color: $white;
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 0.375rem;
}

.form-control {
    width: 100%;
    padding: 0.5rem 0.75rem;
    border: 1px solid $ultra-light-gray;
    border-radius: 4px;
    background-color: $light-gray;
    color: $white;
    font-size: 0.875rem;
    transition: border-color 0.15s, box-shadow 0.15s;
}

.form-control:focus {
    outline: none;
    border-color: $blue;
    box-shadow: 0 0 0 2px rgba($blue, 0.2);
}

.form-control::placeholder {
    color: $ultra-light-gray;
}

.form-control option {
    background-color: $light-gray;
    color: $white;
}

.form-help-text {
    display: block;
    color: $ultra-light-gray;
    font-size: 0.75rem;
    margin-top: 0.25rem;
    font-style: italic;
}

.price-breakdown {
    background-color: $light-gray;
    padding: 12px;
    border-radius: 4px;
    border: 1px solid $ultra-light-gray;
    margin-top: 8px;
}

.price-breakdown div {
    margin-bottom: 4px;
    color: $white;
}

.price-breakdown div:last-child {
    margin-bottom: 0;
    font-weight: bold;
    color: $white;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    font-weight: bold;
    cursor: pointer;
    transition: background-color 0.2s;
    font-size: 0.875rem;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn.green {
    background-color: $green;
    color: white;
}

.btn.green:hover:not(:disabled) {
    background-color: darken($green, 10%);
}

.btn.delete {
    background-color: $red;
    color: white;
}

.btn.delete:hover:not(:disabled) {
    background-color: darken($red, 10%);
}

.btn.orange {
    background-color: $orange;
    color: white;
}

.btn.orange:hover:not(:disabled) {
    background-color: darken($orange, 10%);
}

/* Preview Modal Styles */
.preview-modal-content {
    background-color: $dark-gray;
    border: 1px solid $ultra-light-gray;
    border-radius: 8px;
    width: 95%;
    max-width: 1200px;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
}

.preview-modal-body {
    padding: 0;
    background-color: $dark-gray;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.preview-modal-body iframe {
    border: none;
    border-radius: 4px;
    background-color: white;
}
</style>

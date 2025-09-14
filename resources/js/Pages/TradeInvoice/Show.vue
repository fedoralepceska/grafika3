<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="tradeInvoice" subtitle="viewTradeInvoice" icon="Materials.png" link="trade-invoices"/>
            <div class="dark-gray p-5 text-white">
                <div class="form-container p-2 light-gray overflow-x-auto">
                    <div class="title-row">
                        <h2 class="sub-title">Invoice #{{ invoice.invoice_number }}</h2>
                        <div class="flex gap-2">
                            <button class="btn blue" @click="$inertia.visit(`/trade-invoices/${invoice.id}/edit`)">Edit</button>
                            <button class="btn purple" @click="openPdf">PDF</button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div class="p-3 bg-gray-700 rounded">
                            <div class="bold mb-2">Client</div>
                            <div>{{ invoice.client?.name }}</div>
                        </div>
                        <div class="p-3 bg-gray-700 rounded">
                            <div class="bold mb-2">Warehouse</div>
                            <div>{{ invoice.warehouse?.name }}</div>
                        </div>
                        <div class="p-3 bg-gray-700 rounded">
                            <div class="bold mb-2">Date</div>
                            <div>{{ invoice.invoice_date }}</div>
                        </div>
                        <div class="p-3 bg-gray-700 rounded">
                            <div class="bold mb-2">Status</div>
                            <div>{{ invoice.status }}</div>
                        </div>
                    </div>

                    <table class="excel-table">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Code</th>
                            <th>Article</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>VAT %</th>
                            <th>Amount</th>
                            <th>VAT</th>
                            <th>Total</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="(it, idx) in invoice.items" :key="it.id">
                            <td>{{ idx + 1 }}</td>
                            <td>{{ it.article?.code }}</td>
                            <td>{{ it.article?.name }}</td>
                            <td>{{ formatNumber(it.quantity) }}</td>
                            <td>{{ formatNumber(it.unit_price) }}</td>
                            <td>{{ taxTypePercent(it.tax_type) }}%</td>
                            <td>{{ formatNumber(it.line_total) }}</td>
                            <td>{{ formatNumber(it.vat_amount) }}</td>
                            <td>{{ formatNumber(Number(it.line_total) + Number(it.vat_amount)) }}</td>
                        </tr>
                        </tbody>
                    </table>

                    <div class="flex justify-end mt-4">
                        <div>
                            <div>Subtotal: {{ formatNumber(invoice.subtotal) }}</div>
                            <div>VAT: {{ formatNumber(invoice.vat_amount) }}</div>
                            <div class="bold">Total: {{ formatNumber(invoice.total_amount) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script>
import MainLayout from "@/Layouts/MainLayout.vue";
import Header from "@/Components/Header.vue";

export default {
    components: { MainLayout, Header },
    props: {
        invoice: Object,
    },
    methods: {
        formatNumber(n) {
            return Number(n || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        taxTypePercent(t) {
            const map = { 1: 18, 2: 5, 3: 10 };
            return map[t] ?? 0;
        },
        openPdf() {
            window.open(`/trade-invoices/${this.invoice.id}/pdf`, '_blank');
        }
    }
}
</script>

<style scoped>
.bold { font-weight: 700; }
.title-row { display:flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.btn { padding: 8px 12px; border: none; border-radius: 2px; font-weight: 700; }
.blue { background: #3b82f6; color: white; }
.purple { background: #8b5cf6; color: white; }
.excel-table { width: 100%; border-collapse: collapse; color: white; }
.excel-table th, .excel-table td { border: 1px solid #374151; padding: 6px; text-align: center; }
</style>


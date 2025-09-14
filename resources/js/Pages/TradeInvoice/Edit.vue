<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="tradeInvoice" subtitle="editTradeInvoice" icon="Materials.png" link="trade-invoices"/>
            <div class="dark-gray p-5 text-white">
                <div class="form-container p-2 light-gray overflow-x-auto">
                    <div class="title-row">
                        <h2 class="sub-title">Edit Invoice #{{ form.invoice_number }}</h2>
                        <div class="flex gap-2">
                            <button class="btn purple" @click="openPdf">PDF</button>
                            <button class="btn green" @click="save">Save</button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <div class="bold mb-1">Client</div>
                            <select class="rounded text-black w-full" v-model="form.client_id">
                                <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>
                        <div>
                            <div class="bold mb-1">Warehouse</div>
                            <select class="rounded text-black w-full" v-model="form.warehouse_id">
                                <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                            </select>
                        </div>
                        <div>
                            <div class="bold mb-1">Date</div>
                            <input type="date" class="rounded text-black w-full" v-model="form.invoice_date"/>
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
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="(it, idx) in form.items" :key="it._key">
                            <td>{{ idx + 1 }}</td>
                            <td>
                                <select class="table-input" v-model="it.article_id" @change="onArticleChange(idx)">
                                    <option :value="null">Select</option>
                                    <option v-for="a in articles" :key="a.id" :value="a.id">{{ a.code }}</option>
                                </select>
                            </td>
                            <td>{{ it.article?.name }}</td>
                            <td><input class="table-input" type="number" min="0.00001" step="0.00001" v-model.number="it.quantity" @input="recalc(idx)"/></td>
                            <td><input class="table-input" type="number" min="0.01" step="0.01" v-model.number="it.unit_price" @input="recalc(idx)"/></td>
                            <td>
                                <select class="table-input" v-model.number="it.tax_type" @change="recalc(idx)">
                                    <option :value="1">18%</option>
                                    <option :value="2">5%</option>
                                    <option :value="3">10%</option>
                                    <option :value="0">0%</option>
                                </select>
                            </td>
                            <td>{{ formatNumber(it.line_total) }}</td>
                            <td>{{ formatNumber(it.vat_amount) }}</td>
                            <td>{{ formatNumber(itemTotal(it)) }}</td>
                            <td><button class="btn delete btn-sm" @click="remove(idx)">Remove</button></td>
                        </tr>
                        </tbody>
                    </table>

                    <div class="flex justify-end mt-4">
                        <div>
                            <div>Subtotal: {{ formatNumber(totals.subtotal) }}</div>
                            <div>VAT: {{ formatNumber(totals.vat) }}</div>
                            <div class="bold">Total: {{ formatNumber(totals.total) }}</div>
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
import axios from 'axios';

export default {
    components: { MainLayout, Header },
    props: {
        invoice: Object,
        clients: Array,
        warehouses: Array,
    },
    data() {
        return {
            form: this.normalize(this.invoice),
            articles: [],
        };
    },
    computed: {
        totals() {
            let subtotal = 0, vat = 0;
            this.form.items.forEach(it => { subtotal += Number(it.line_total||0); vat += Number(it.vat_amount||0); });
            return { subtotal, vat, total: subtotal + vat };
        }
    },
    mounted() {
        this.fetchArticles();
    },
    methods: {
        normalize(inv) {
            const items = (inv.items||[]).map(it => ({ ...it, _key: `${it.id||Math.random()}`, article: it.article }));
            return {
                id: inv.id,
                invoice_number: inv.invoice_number,
                client_id: inv.client_id,
                warehouse_id: inv.warehouse_id,
                invoice_date: this.formatDateForInput(inv.invoice_date),
                items,
            };
        },
        formatNumber(n) {
            return Number(n||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});
        },
        formatDateForInput(dateString) {
            if (!dateString) return '';
            // Convert date to YYYY-MM-DD format for HTML date input
            const date = new Date(dateString);
            if (isNaN(date.getTime())) return '';
            return date.toISOString().split('T')[0];
        },
        taxPercent(t) { const m={1:18,2:5,3:10}; return m[t]??0; },
        itemTotal(it) { return Number(it.line_total||0)+Number(it.vat_amount||0); },
        recalc(idx) {
            const it=this.form.items[idx];
            const line = Number(it.quantity||0)*Number(it.unit_price||0);
            const vat = line*(this.taxPercent(it.tax_type)/100);
            this.$set ? (this.$set(it,'line_total',line), this.$set(it,'vat_amount',vat)) : (it.line_total=line,it.vat_amount=vat);
        },
        async fetchArticles(){
            if(!this.form.warehouse_id) return;
            const res = await axios.get(`/trade-invoices/${this.form.warehouse_id}/available-articles`);
            this.articles = res.data.map(a=>({id:a.id, code:a.code, name:a.name, price:a.selling_price, tax_type:a.tax_type}));
        },
        onArticleChange(idx){
            const it=this.form.items[idx];
            const a=this.articles.find(x=>x.id===it.article_id);
            if(a){ 
                it.article={ 
                    id: a.id,
                    name: a.name, 
                    code: a.code 
                }; 
                it.unit_price=a.price; 
                it.tax_type=a.tax_type; 
                this.recalc(idx); 
            }
        },
        remove(idx){ this.form.items.splice(idx,1); },
        async save(){
            const payload={
                client_id:this.form.client_id,
                warehouse_id:this.form.warehouse_id,
                invoice_date:this.form.invoice_date,
                notes: this.invoice.notes || '',
                items:this.form.items.map(it=>({ article_id:it.article_id, quantity:it.quantity, unit_price:it.unit_price, tax_type:it.tax_type }))
            };
            await axios.put(`/trade-invoices/${this.form.id}`, payload);
            this.$inertia.visit('/trade-invoices');
        },
        openPdf(){ window.open(`/trade-invoices/${this.form.id}/pdf`, '_blank'); }
    }
}
</script>

<style scoped>
.bold { font-weight: 700; }
.title-row { display:flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.btn { padding: 8px 12px; border: none; border-radius: 2px; font-weight: 700; }
.green { background: #408a0b; color: white; }
.purple { background: #8b5cf6; color: white; }
.delete { background: #9e2c30; color:white; border:none; }
.btn-sm { padding: 4px 8px; font-size: 12px; }
.excel-table { width: 100%; border-collapse: collapse; color: white; }
.excel-table th, .excel-table td { border: 1px solid #374151; padding: 6px; text-align: center; }
.table-input { background: transparent; color: white; border: 1px solid #374151; padding: 4px; }
</style>


<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="material" subtitle="materialList" icon="Materials.png" link="materials/small"/>
            <div class="form-container p15">
                <div class="dark-gray p-5 text-white">
                    <div class="form-container p-2 light-gray overflow-x-auto">
                        <h2 class="sub-title">
                            {{ $t('listOfSmallMaterials') }}
                        </h2>
                        <div class="controls-section">
                            <div class="controls-grid">
                                <div class="control-item">
                                    <label class="control-label">Search Materials</label>
                                    <input 
                                        type="text" 
                                        class="control-input" 
                                        v-model="searchQuery" 
                                        @keyup="fetchSmallMaterials(1)" 
                                        placeholder="Search by Material Name">
                                </div>
                                <div class="control-item">
                                    <label class="control-label">{{ $t('perPage') }}</label>
                                    <select v-model="filterStatus" class="control-select" @change="fetchSmallMaterials(1)">
                                        <option value="20">20</option>
                                        <option value="40">40</option>
                                        <option value="100">100</option>
                                    </select>
                                </div>
                                <div class="control-item">
                                    <label class="control-label">{{ $t('Unit') }}</label>
                                    <select v-model="unitFilter" class="control-select" @change="fetchSmallMaterials(1)">
                                        <option value="">All Units</option>
                                        <option value="meters">Meters (m)</option>
                                        <option value="kilograms">Kilograms (kg)</option>
                                        <option value="pieces">Pieces (pcs)</option>
                                        <option value="square_meters">Square Meters (m²)</option>
                                    </select>
                                </div>
                                <div class="control-item">
                                    <label class="control-label">Quantity Range</label>
                                    <div class="quantity-range">
                                        <input 
                                            type="number" 
                                            class="control-input quantity-input" 
                                            v-model="quantityMin" 
                                            @input="fetchSmallMaterials(1)"
                                            placeholder="Min">
                                        <span class="range-separator">-</span>
                                        <input 
                                            type="number" 
                                            class="control-input quantity-input" 
                                            v-model="quantityMax" 
                                            @input="fetchSmallMaterials(1)"
                                            placeholder="Max">
                                    </div>
                                </div>
                                <div class="control-item clear-btn-container">
                                    <button @click="clearFilters" class="clear-filters-btn">
                                        <svg class="clear-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                        Clear All
                                    </button>
                                </div>
                            </div>
                            <div class="print-buttons">
                                <button @click="printMaterials" class="btn create-order">
                                    {{ $t('printMaterials') }} <i class="fa-solid fa-print"></i>
                                </button>
                                <button @click="printAllMaterials" class="btn create-order2">
                                    {{ $t('printAllMaterials') }} <i class="fa-solid fa-print"></i>
                                </button>
                                <button @click="openResetModal" class="btn reset-qty-btn">
                                    Reset Quantity to 0 <i class="fa-solid fa-rotate-left"></i>
                                </button>
                            </div>
                        </div>
                        <div>
                            <table class="excel-table mb-3">
                                <thead>
                                <tr>
                                    <th style="width: 45px;">{{$t('Nr')}}</th>
                                    <th>{{$t('material')}}<div class="resizer" @mousedown="initResize($event, 1)"></div></th>
                                    <th>{{$t('Quantity')}}<div class="resizer" @mousedown="initResize($event, 3)"></div></th>
                                    <th style="width: 80px;">{{$t('Unit')}}<div class="resizer" @mousedown="initResize($event, 2)"></div></th>
                                    <th style="width: 70px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="m in smallMaterials.data" :key="m.id" class="clickable-row" @click="goToArticle(m)" :title="m?.article?.name ? `Open article: ${m.article.name}` : 'No linked article'">
                                    <th>{{ m?.id }}</th>
                                    <th>{{ m?.name }}</th>
                                    <th>{{ m?.quantity }}</th>
                                    <th v-if="m.article">{{ getUnit(m) }}</th>
                                    <th v-else>/</th>
                                    <th>
                                        <button class="btn action-btn" @click.stop="openQuantityModal(m)" title="Update quantity" aria-label="Update quantity">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </th>
                                </tr>
                                </tbody>
                            </table>
                            <Pagination :pagination="smallMaterials" @pagination-change-page="fetchSmallMaterials"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="showQuantityModal" class="modal-backdrop" @click.self="closeQuantityModal">
            <div class="modal-card">
                <template v-if="quantityModalStep === 1">
                    <h3 class="modal-title">Update quantity</h3>
                    <p class="modal-text">
                        Set a new quantity for <strong>{{ targetMaterial?.name || 'this material' }}</strong>.
                    </p>
                    <input
                        ref="quantityInput"
                        v-model="editedQuantity"
                        type="number"
                        min="0"
                        step="1"
                        class="quantity-modal-input"
                        placeholder="Enter new quantity"
                    />
                    <div class="modal-actions">
                        <button class="btn modal-cancel" @click="closeQuantityModal" :disabled="updatingQuantity">Cancel</button>
                        <button class="btn modal-confirm" @click="goToQuantityCodeStep" :disabled="updatingQuantity">
                            Continue
                        </button>
                    </div>
                </template>
                <template v-else>
                    <h3 class="modal-title">Confirm quantity update</h3>
                    <p class="modal-text">
                        New quantity: <strong>{{ editedQuantity }}</strong>
                    </p>
                    <p class="modal-text">Enter the 4-digit code to confirm.</p>
                    <div class="code-inputs" @paste.prevent="onQuantityCodePaste">
                        <input
                            v-for="idx in 4"
                            :key="idx"
                            :ref="'quantityCodeInput' + (idx - 1)"
                            type="password"
                            inputmode="numeric"
                            maxlength="1"
                            class="code-box"
                            :value="quantityCodeDigits[idx - 1]"
                            @input="onQuantityCodeInput(idx - 1, $event)"
                            @keydown="onQuantityCodeKeydown(idx - 1, $event)"
                        />
                    </div>
                    <div class="modal-actions">
                        <button class="btn modal-cancel" @click="goToQuantityInputStep" :disabled="updatingQuantity">Back</button>
                        <button class="btn modal-confirm" @click="confirmQuantityUpdate" :disabled="updatingQuantity">
                            {{ updatingQuantity ? 'Updating...' : 'Update quantity' }}
                        </button>
                    </div>
                </template>
            </div>
        </div>
        <div v-if="showResetModal" class="modal-backdrop" @click.self="closeResetModal">
            <div class="modal-card">
                <h3 class="modal-title">Reset all small materials</h3>
                <p class="modal-text">Enter 4-digit code to reset all quantities to 0.</p>
                <div class="code-inputs" @paste.prevent="onCodePaste">
                    <input
                        v-for="idx in 4"
                        :key="idx"
                        :ref="'codeInput' + (idx - 1)"
                        type="password"
                        inputmode="numeric"
                        maxlength="1"
                        class="code-box"
                        :value="codeDigits[idx - 1]"
                        @input="onCodeInput(idx - 1, $event)"
                        @keydown="onCodeKeydown(idx - 1, $event)"
                    />
                </div>
                <div class="modal-actions">
                    <button class="btn modal-cancel" @click="closeResetModal" :disabled="resetting">Cancel</button>
                    <button class="btn modal-confirm" @click="confirmReset" :disabled="resetting">
                        {{ resetting ? 'Resetting...' : 'Confirm reset' }}
                    </button>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script>
import MainLayout from "@/Layouts/MainLayout.vue";
import axios from "axios";
import Pagination from "@/Components/Pagination.vue";
import Header from "@/Components/Header.vue";
import { useToast } from "vue-toastification";

export default {
    components: {
        MainLayout,
        Pagination,
        Header
    },
    data() {
        return {
            startX: 0,
            startWidth: 0,
            columnIndex: -1,
            smallMaterials: {},
            searchQuery: '',
            filterStatus: 20,
            perPage: 20,
            unitFilter: '',
            quantityMin: '',
            quantityMax: '',
            showResetModal: false,
            codeDigits: ['', '', '', ''],
            resetting: false,
            showQuantityModal: false,
            quantityCodeDigits: ['', '', '', ''],
            updatingQuantity: false,
            targetMaterial: null,
            editedQuantity: '',
            quantityModalStep: 1,
        };
    },
    mounted() {
        this.fetchSmallMaterials();
    },
    methods: {
        goToArticle(material) {
            const articleId = material?.article?.id || material?.article_id;
            if (!articleId) {
                return; // No linked article
            }
            this.$inertia.visit(`/articles/${articleId}/view?from=small_materials`);
        },
        initResize(event, index) {
            this.startX = event.clientX;
            this.startWidth = event.target.parentElement.offsetWidth;
            this.columnIndex = index;

            document.documentElement.addEventListener('mousemove', this.resizeColumn);
            document.documentElement.addEventListener('mouseup', this.stopResize);
        },
        resizeColumn(event) {
            const diffX = event.clientX - this.startX;
            const newWidth = this.startWidth + diffX;
            const ths = document.querySelectorAll('.excel-table th');

            if (this.columnIndex >= 0 && this.columnIndex < ths.length) {
                ths[this.columnIndex].style.width = `${newWidth}px`;
            }
        },
        stopResize() {
            document.documentElement.removeEventListener('mousemove', this.resizeColumn);
            document.documentElement.removeEventListener('mouseup', this.stopResize);
        },
        async fetchSmallMaterials(page = 1) {
            const params = {
                page,
                per_page: this.filterStatus,
                search_query: this.searchQuery,
                unit_filter: this.unitFilter,
                quantity_min: this.quantityMin,
                quantity_max: this.quantityMax,
            };
            try {
                const response = await axios.get('/materials/small', { params });
                this.smallMaterials = response.data;
            } catch (error) {
                console.error('Error fetching small materials:', error);
            }
        },
        searchMaterials() {
            this.fetchSmallMaterials();
        },
        clearFilters() {
            this.unitFilter = '';
            this.quantityMin = '';
            this.quantityMax = '';
            this.searchQuery = '';
            this.fetchSmallMaterials(1);
        },
        async printMaterials() {
            const params = {
                search_query: this.searchQuery,
                per_page: this.filterStatus,
            };
            const url = `/materials/pdf?${new URLSearchParams(params).toString()}`;
            window.open(url, '_blank');
        },
        async printAllMaterials() {
            const url = `/materials/all-pdf`;
            window.open(url, '_blank');
        },
        openQuantityModal(material) {
            this.targetMaterial = material;
            this.editedQuantity = material?.quantity ?? 0;
            this.quantityCodeDigits = ['', '', '', ''];
            this.quantityModalStep = 1;
            this.showQuantityModal = true;
            this.$nextTick(() => {
                this.$refs.quantityInput?.focus();
            });
        },
        closeQuantityModal(force = false) {
            if (this.updatingQuantity && !force) return;
            this.showQuantityModal = false;
            this.targetMaterial = null;
            this.editedQuantity = '';
            this.quantityCodeDigits = ['', '', '', ''];
            this.quantityModalStep = 1;
            for (let i = 0; i < 4; i++) {
                const el = this.getQuantityCodeInputRef(i);
                if (el) el.value = '';
            }
        },
        goToQuantityCodeStep() {
            const toast = useToast();
            const quantity = Number(this.editedQuantity);
            if (!Number.isInteger(quantity) || quantity < 0) {
                toast.error('Enter a valid quantity.');
                return;
            }

            this.quantityModalStep = 2;
            this.$nextTick(() => {
                const first = this.getQuantityCodeInputRef(0);
                if (first) first.focus();
            });
        },
        goToQuantityInputStep() {
            if (this.updatingQuantity) return;
            this.quantityModalStep = 1;
            this.quantityCodeDigits = ['', '', '', ''];
            for (let i = 0; i < 4; i++) {
                const el = this.getQuantityCodeInputRef(i);
                if (el) el.value = '';
            }
            this.$nextTick(() => {
                this.$refs.quantityInput?.focus();
            });
        },
        getQuantityCodeInputRef(index) {
            const ref = this.$refs[`quantityCodeInput${index}`];
            return Array.isArray(ref) ? ref[0] : ref;
        },
        onQuantityCodeInput(index, event) {
            const val = (event.target.value || '').replace(/\D/g, '').slice(0, 1);
            this.quantityCodeDigits.splice(index, 1, val);
            event.target.value = val;
            if (val && index < 3) {
                const next = this.getQuantityCodeInputRef(index + 1);
                if (next) next.focus();
            } else if (!val && index > 0) {
                const prev = this.getQuantityCodeInputRef(index - 1);
                if (prev) prev.focus();
            }
        },
        onQuantityCodeKeydown(index, event) {
            if (event.key === 'Backspace' && !event.target.value && index > 0) {
                const prev = this.getQuantityCodeInputRef(index - 1);
                if (prev) prev.focus();
            }
            if (event.key === 'ArrowLeft' && index > 0) {
                const prev = this.getQuantityCodeInputRef(index - 1);
                if (prev) prev.focus();
                event.preventDefault();
            }
            if (event.key === 'ArrowRight' && index < 3) {
                const next = this.getQuantityCodeInputRef(index + 1);
                if (next) next.focus();
                event.preventDefault();
            }
        },
        onQuantityCodePaste(event) {
            const text = (event.clipboardData || window.clipboardData).getData('text');
            const digits = (text || '').replace(/\D/g, '').slice(0, 4).split('');
            for (let i = 0; i < 4; i++) {
                const d = digits[i] || '';
                this.quantityCodeDigits.splice(i, 1, d);
                const input = this.getQuantityCodeInputRef(i);
                if (input) input.value = d;
            }
            const nextIndex = Math.min(digits.length, 3);
            const next = this.getQuantityCodeInputRef(nextIndex);
            if (next) next.focus();
        },
        async confirmQuantityUpdate() {
            if (!this.targetMaterial || this.updatingQuantity) return;

            const toast = useToast();
            const quantity = Number(this.editedQuantity);
            if (!Number.isInteger(quantity) || quantity < 0) {
                toast.error('Enter a valid quantity.');
                return;
            }

            const passcode = this.quantityCodeDigits.join('');
            if (!passcode || passcode.length !== 4) {
                toast.error('Enter 4-digit code.');
                return;
            }
            if (passcode !== '9632') {
                toast.error('Invalid code.');
                return;
            }

            this.updatingQuantity = true;
            try {
                await axios.put(`/materials/small/${this.targetMaterial.id}/quantity`, {
                    quantity,
                    passcode,
                });
                await this.fetchSmallMaterials(this.smallMaterials?.current_page || 1);
                this.closeQuantityModal(true);
                toast.success('Small material quantity updated.');
            } catch (error) {
                const msg = error?.response?.data?.error || 'Failed to update quantity.';
                toast.error(msg);
            } finally {
                this.updatingQuantity = false;
            }
        },
        openResetModal() {
            this.showResetModal = true;
            this.codeDigits = ['', '', '', ''];
            this.$nextTick(() => {
                const first = this.getCodeInputRef(0);
                if (first) first.focus();
            });
        },
        closeResetModal(force = false) {
            if (this.resetting && !force) return;
            this.showResetModal = false;
            this.codeDigits = ['', '', '', ''];
            for (let i = 0; i < 4; i++) {
                const el = this.getCodeInputRef(i);
                if (el) el.value = '';
            }
        },
        getCodeInputRef(index) {
            const ref = this.$refs[`codeInput${index}`];
            return Array.isArray(ref) ? ref[0] : ref;
        },
        onCodeInput(index, event) {
            const val = (event.target.value || '').replace(/\D/g, '').slice(0, 1);
            this.codeDigits.splice(index, 1, val);
            event.target.value = val;
            if (val && index < 3) {
                const next = this.getCodeInputRef(index + 1);
                if (next) next.focus();
            } else if (!val && index > 0) {
                const prev = this.getCodeInputRef(index - 1);
                if (prev) prev.focus();
            }
        },
        onCodeKeydown(index, event) {
            if (event.key === 'Backspace' && !event.target.value && index > 0) {
                const prev = this.getCodeInputRef(index - 1);
                if (prev) prev.focus();
            }
            if (event.key === 'ArrowLeft' && index > 0) {
                const prev = this.getCodeInputRef(index - 1);
                if (prev) prev.focus();
                event.preventDefault();
            }
            if (event.key === 'ArrowRight' && index < 3) {
                const next = this.getCodeInputRef(index + 1);
                if (next) next.focus();
                event.preventDefault();
            }
        },
        onCodePaste(event) {
            const text = (event.clipboardData || window.clipboardData).getData('text');
            const digits = (text || '').replace(/\D/g, '').slice(0, 4).split('');
            for (let i = 0; i < 4; i++) {
                const d = digits[i] || '';
                this.codeDigits.splice(i, 1, d);
                const input = this.getCodeInputRef(i);
                if (input) input.value = d;
            }
            const nextIndex = Math.min(digits.length, 3);
            const next = this.getCodeInputRef(nextIndex);
            if (next) next.focus();
        },
        async confirmReset() {
            if (this.resetting) return;
            const toast = useToast();
            const passcode = this.codeDigits.join('');
            if (!passcode || passcode.length !== 4) {
                toast.error('Enter 4-digit code.');
                return;
            }
            if (passcode !== '9632') {
                toast.error('Invalid code.');
                return;
            }
            this.resetting = true;
            try {
                await axios.post('/materials/small/reset-quantities', { passcode });
                await this.fetchSmallMaterials(1);
                this.closeResetModal(true);
                toast.success('All small material quantities are reset to 0.');
            } catch (error) {
                const msg = error?.response?.data?.error || 'Failed to reset quantities.';
                toast.error(msg);
            } finally {
                this.resetting = false;
            }
        },
        getUnit(material) {
            if (material) {
                if (material.article.in_meters) {
                    return 'meters';
                } else if (material.article.in_square_meters) {
                    return 'square meters';
                } else if (material.article.in_kilograms) {
                    return 'kilograms';
                } else if (material.article.in_pieces) {
                    return 'pieces';
                }
            }
            return '';
        }
    },
};
</script>

<style scoped lang="scss">
.clickable-row {
    cursor: pointer;
}

.clickable-row:hover {
    background-color: rgba(255, 255, 255, 0.06);
}

.centered {
    display: flex;
    justify-content: center;
    align-items: center;
}
.filters{
    justify-content: space-between;
}
select{
    width: 240px;
}
.buttF{
    padding-top: 23.5px;
}
.btn {
    padding: 9px 12px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    border-radius: 2px;
}
.create-order{
    background-color: $blue;
    color: white;
}
.create-order1{
    background-color: $blue;
    color: white;
}
.create-order2{
    background-color: $green;
    color: white;
}

.centered {
    display: flex;
    justify-content: center;
    align-items: center;
}
.delete{
    border: none;
    color: white;
    background-color: $red;
}
.delete:hover{
    background-color: darkred;
}
.green-text{
    color: $green;
}
.blue{
    background-color: $blue;
    border: none;
    color: white;
}
.blue:hover{
    background-color: cornflowerblue;
}
.green{
    background-color: $green;
}
.header{
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
.light-gray{
    background-color: $light-gray;
}

.client-form {
    width: 100%;
    justify-content: left;
    align-items: center;
    min-height: 20vh;
    padding: 5px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}
.page-title {
    font-size: 24px;
    display: flex;
    align-items: center;
    color: $white;
}
.sub-title{
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    color: $white;
}

.image-icon {
    margin-left: 2px;
    max-width: 40px;
}
.form-group {
    display: flex;
    justify-content: right;
    align-items: center;
    width: 300px;
    margin-bottom: 10px;
    color: $white;
}

.label {
    flex: 1;
    text-align: left;
    margin-right: 20px;
}
.button-container{
    display: flex;
    justify-content: flex-end;
}
.excel-table {
    border-collapse: collapse;
    width: 100%;
    color: white;
    table-layout: fixed;
}
.excel-table th,
.excel-table td {
    border: 1px solid #dddddd;
    padding: 4px;
    text-align: center;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
    position: relative;
}
.excel-table th {
    min-width: 50px;
}
.excel-table tr:nth-child(even) {
    background-color: $ultra-light-gray;
}
.resizer {
    width: 5px;
    height: 100%;
    position: absolute;
    right: 0;
    top: 0;
    cursor: col-resize;
    user-select: none;
    background-color: transparent;
}
.info {
    border: 2px solid white;
    min-width: 90vh;
    max-width: 100vh;
}
.contact-info {
    display: flex;
    flex-direction: row;
    align-items: center;
}

/* Enhanced Controls Section */
.controls-section {
    padding: 5px;
    margin-bottom: 10px;
}

.controls-grid {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 2fr auto;
    gap: 20px;
    align-items: end;
    margin-bottom: 15px;
}

.control-item {
    display: flex;
    flex-direction: column;
}

.clear-btn-container {
    align-self: end;
}

.control-label {
    display: block;
    color: #ffffff;
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 8px;
    opacity: 0.9;
}

.control-input, 
.control-select {
    border: 2px solid rgba(255, 255, 255, 0.2);
    border-radius: 6px;
    background: rgba(255, 255, 255, 0.95);
    color: #333;
    font-size: 14px;
    transition: all 0.2s ease;
    padding: 8px 12px;
}

.control-select {
    cursor: pointer;
}

.control-input:focus, 
.control-select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.control-select:hover {
    border-color: rgba(255, 255, 255, 0.3);
}

.quantity-range {
    display: flex;
    align-items: center;
    gap: 8px;
}

.quantity-input {
    flex: 1;
    min-width: 80px;
}

.range-separator {
    color: #ffffff;
    font-weight: bold;
    opacity: 0.7;
}

.clear-filters-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 12px 16px;
    background: $red;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.clear-filters-btn:hover {
    background: darkred;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(239, 68, 68, 0.2);
}

.clear-icon {
    width: 14px;
    height: 14px;
}

.print-buttons {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}

.reset-qty-btn {
    background-color: $red;
    color: white;
}

.reset-qty-btn:hover {
    background-color: darkred;
}

.action-btn {
    background-color: $blue;
    color: white;
    width: 34px;
    height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
}

.action-btn:hover {
    background-color: cornflowerblue;
}

.modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.65);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1100;
}

.modal-card {
    width: min(460px, 92vw);
    background: #1f2937;
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 10px;
    padding: 20px;
    color: #fff;
}

.modal-title {
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 6px;
}

.modal-text {
    opacity: 0.9;
    margin-bottom: 14px;
}

.quantity-modal-input {
    width: 100%;
    margin-bottom: 14px;
    padding: 10px 12px;
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.25);
    background: rgba(255, 255, 255, 0.95);
    color: #111827;
}

.code-inputs {
    display: flex;
    gap: 8px;
    justify-content: center;
    margin-bottom: 16px;
}

.code-box {
    width: 42px;
    height: 42px;
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.3);
    text-align: center;
    font-size: 20px;
    background: rgba(255, 255, 255, 0.1);
    color: white;
}

.modal-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.modal-cancel {
    background: #4b5563;
    color: white;
}

.modal-confirm {
    background: $red;
    color: white;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .controls-grid {
        grid-template-columns: 1fr 1fr 1fr;
        gap: 16px;
    }
    
    .clear-btn-container {
        grid-column: span 3;
        justify-self: end;
    }
}

@media (max-width: 768px) {
    .controls-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    
    .clear-btn-container {
        grid-column: span 1;
        justify-self: stretch;
    }
    
    .clear-filters-btn {
        justify-content: center;
    }
    
    .print-buttons {
        justify-content: center;
    }
}
</style>

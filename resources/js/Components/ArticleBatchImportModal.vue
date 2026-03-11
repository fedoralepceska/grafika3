<template>
    <div>
        <button
            type="button"
            @click="openModal"
            class="import-excel-btn"
        >
            <i class="fa-solid fa-file-excel"></i> {{ $t('importFromExcel') || 'Import from Excel' }}
        </button>

        <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
            <div class="modal-content article-import-modal">
                <div class="modal-header">
                    <h3 class="modal-title">{{ $t('batchArticleImport') || 'Batch Article Import' }}</h3>
                    <button type="button" @click="closeModal" class="close-button" aria-label="Close">
                        <i class="fa-solid fa-times"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <!-- Step 1: Drop file -->
                    <div v-if="!parsedFile" class="drop-section">
                        <div
                            class="drop-zone"
                            :class="{ 'drop-zone-active': isDragging, 'drop-zone-error': parseError }"
                            @dragover.prevent="isDragging = true"
                            @dragleave.prevent="isDragging = false"
                            @drop.prevent="onDrop"
                        >
                            <input
                                ref="fileInput"
                                type="file"
                                accept=".xlsx,.xls"
                                class="drop-input"
                                @change="onFileSelect"
                            >
                            <i class="fa-solid fa-file-excel drop-icon"></i>
                            <p class="drop-text">Drop your Excel file here or click to browse</p>
                            <p class="drop-hint">.xlsx or .xls, max 50MB</p>
                            <p v-if="parseError" class="drop-error">{{ parseError }}</p>
                        </div>
                    </div>

                    <!-- Step 2: Column mapping -->
                    <div v-else-if="!previewRows.length && parsedFile" class="mapping-section">
                        <p class="section-label">{{ $t('mapColumns') || 'Map Excel columns to article fields' }}</p>
                        <div class="mapping-grid">
                            <div class="mapping-row">
                                <label>{{ $t('name') || 'Article name' }} <span class="required">*</span></label>
                                <select v-model.number="mapping.name_col" class="mapping-select">
                                    <option value="">-- {{ $t('selectColumn') || 'Select column' }} --</option>
                                    <option v-for="(h, idx) in headers" :key="idx" :value="idx">
                                        {{ h || `Column ${idx + 1}` }}
                                    </option>
                                </select>
                            </div>
                            <div class="mapping-row">
                                <label>{{ $t('pprice') || 'Purchase price' }}</label>
                                <select v-model.number="mapping.purchase_price_col" class="mapping-select">
                                    <option :value="null">-- {{ $t('selectColumn') || 'Select column' }} --</option>
                                    <option v-for="(h, idx) in headers" :key="idx" :value="idx">
                                        {{ h || `Column ${idx + 1}` }}
                                    </option>
                                </select>
                            </div>
                            <div class="mapping-row">
                                <label>{{ $t('price') || 'Price' }}</label>
                                <select v-model.number="mapping.price_col" class="mapping-select">
                                    <option :value="null">-- {{ $t('selectColumn') || 'Select column' }} --</option>
                                    <option v-for="(h, idx) in headers" :key="idx" :value="idx">
                                        {{ h || `Column ${idx + 1}` }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-actions">
                            <button type="button" class="btn secondary" @click="resetFile">{{ $t('changeFile') || 'Change file' }}</button>
                            <button type="button" class="btn primary" :disabled="mapping.name_col === '' || mapping.name_col === null || loading" @click="loadPreview">
                                <i v-if="loading" class="fa-solid fa-spinner fa-spin"></i>
                                {{ $t('preview') || 'Preview' }}
                            </button>
                        </div>
                    </div>

                    <!-- Step 3: Preview table + Execute -->
                    <div v-else class="preview-section">
                        <p class="section-label">
                            {{ $t('previewImport') || 'Preview' }}: {{ toCreateCount }} {{ $t('toCreate') || 'to create' }}, {{ duplicateCount }} {{ $t('duplicates') || 'duplicates' }}
                        </p>
                        <div class="table-wrap">
                            <p v-if="previewRows.length === 0" class="preview-empty-msg">
                                {{ $t('noRowsToPreview') || 'No data rows to preview. Check that the name column has values and the file has data rows.' }}
                            </p>
                            <table v-else class="excel-table preview-table">
                                <thead>
                                    <tr>
                                        <th>{{ $t('Code') }}</th>
                                        <th>{{ $t('article') || 'Name' }}</th>
                                        <th>{{ $t('pprice') || 'Purchase price' }}</th>
                                        <th>{{ $t('price') }}</th>
                                        <th>{{ $t('VAT') }}</th>
                                        <th>{{ $t('Unit') }}</th>
                                        <th>{{ $t('Product') }}/{{ $t('Service') }}</th>
                                        <th>{{ $t('Format') }}</th>
                                        <th>{{ $t('Barcode') }}</th>
                                        <th>{{ $t('comment') }}</th>
                                        <th>{{ $t('height') }}</th>
                                        <th>{{ $t('width') }}</th>
                                        <th>{{ $t('length') }}</th>
                                        <th>{{ $t('weight') }}</th>
                                        <th>{{ $t('color') }}</th>
                                        <th>{{ $t('fprice') || 'Factory price' }}</th>
                                        <th>{{ $t('Categories') || 'Categories' }}</th>
                                        <th>{{ $t('status') || 'Status' }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(row, index) in previewRows"
                                        :key="index"
                                        :class="{ 'row-duplicate': row.is_duplicate }"
                                    >
                                        <td>{{ row.code || '—' }}</td>
                                        <td>{{ row.name }}</td>
                                        <td>{{ formatNum(row.purchase_price) }}</td>
                                        <td>{{ formatNum(row.price_1) }}</td>
                                        <td>{{ row.tax_label }}</td>
                                        <td>{{ row.unit }}</td>
                                        <td>{{ row.type }}</td>
                                        <td class="cell-edit">
                                            <div class="format-dropdown-wrap" v-if="row.is_duplicate">
                                                <span class="cell-empty">—</span>
                                            </div>
                                            <div v-else class="format-dropdown-wrap" v-click-outside="closeFormatDropdown">
                                                <button
                                                    type="button"
                                                    class="format-dropdown-trigger"
                                                    :class="{ 'format-dropdown-open': openFormatDropdown === index }"
                                                    @click.stop="toggleFormatDropdown(index)"
                                                >
                                                    <span class="format-dropdown-label">{{ formatLabel(rowOverrides[index].format_type) }}</span>
                                                    <i class="fa-solid fa-chevron-down format-dropdown-arrow"></i>
                                                </button>
                                                <div v-show="openFormatDropdown === index" class="format-dropdown-panel" @click.stop>
                                                    <button type="button" class="format-dropdown-option" :class="{ active: rowOverrides[index].format_type === '2' }" @click="setFormat(index, '2')">{{ $t('Large') || 'Large' }}</button>
                                                    <button type="button" class="format-dropdown-option" :class="{ active: rowOverrides[index].format_type === '1' }" @click="setFormat(index, '1')">{{ $t('Small') || 'Small' }}</button>
                                                    <button type="button" class="format-dropdown-option" :class="{ active: rowOverrides[index].format_type === '3' }" @click="setFormat(index, '3')">{{ $t('Other') || 'Other' }}</button>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="cell-edit">
                                            <input v-model="rowOverrides[index].barcode" type="text" class="cell-input" :disabled="row.is_duplicate" />
                                        </td>
                                        <td class="cell-edit">
                                            <input v-model="rowOverrides[index].comment" type="text" class="cell-input" :disabled="row.is_duplicate" />
                                        </td>
                                        <td class="cell-edit">
                                            <input v-model="rowOverrides[index].height" type="text" class="cell-input cell-input-num" :disabled="row.is_duplicate" />
                                        </td>
                                        <td class="cell-edit">
                                            <input v-model="rowOverrides[index].width" type="text" class="cell-input cell-input-num" :disabled="row.is_duplicate" />
                                        </td>
                                        <td class="cell-edit">
                                            <input v-model="rowOverrides[index].length" type="text" class="cell-input cell-input-num" :disabled="row.is_duplicate" />
                                        </td>
                                        <td class="cell-edit">
                                            <input v-model="rowOverrides[index].weight" type="text" class="cell-input cell-input-num" :disabled="row.is_duplicate" />
                                        </td>
                                        <td class="cell-edit">
                                            <input v-model="rowOverrides[index].color" type="text" class="cell-input" :disabled="row.is_duplicate" />
                                        </td>
                                        <td class="cell-edit">
                                            <input v-model="rowOverrides[index].factory_price" type="text" class="cell-input cell-input-num" :disabled="row.is_duplicate" />
                                        </td>
                                        <td class="cell-edit">
                                            <div class="category-dropdown-wrap" v-if="row.is_duplicate">
                                                <span class="cell-empty">—</span>
                                            </div>
                                            <div v-else class="category-dropdown-wrap" v-click-outside="closeCategoryDropdown">
                                                <button
                                                    type="button"
                                                    class="category-dropdown-trigger"
                                                    :class="{ 'category-dropdown-open': openCategoryDropdown === index }"
                                                    @click.stop="toggleCategoryDropdown(index)"
                                                >
                                                    <span v-if="(rowOverrides[index].category_ids || []).length === 0" class="category-placeholder">—</span>
                                                    <span v-else class="category-selected">{{ (rowOverrides[index].category_ids || []).length }} {{ $t('Categories') || 'categories' }}</span>
                                                    <i class="fa-solid fa-chevron-down category-dropdown-arrow"></i>
                                                </button>
                                                <div v-show="openCategoryDropdown === index" class="category-dropdown-panel" @click.stop>
                                                    <div class="category-dropdown-list">
                                                        <label v-for="cat in categoriesForFormat(rowOverrides[index].format_type)" :key="cat.id" class="category-dropdown-item">
                                                            <input
                                                                type="checkbox"
                                                                :value="cat.id"
                                                                v-model="rowOverrides[index].category_ids"
                                                            >
                                                            <span>{{ cat.name }}</span>
                                                        </label>
                                                    </div>
                                                    <p v-if="categoriesForFormat(rowOverrides[index].format_type).length === 0" class="category-dropdown-empty">{{ $t('noCategories') || 'No categories' }} ({{ $t('format') || 'format' }})</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span v-if="row.is_duplicate" class="badge duplicate">{{ $t('duplicate') || 'Duplicate' }}</span>
                                            <span v-else class="badge create">{{ $t('toCreate') || 'To create' }}</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="modal-actions">
                            <button type="button" class="btn secondary" @click="backToMapping">{{ $t('backToMapping') || 'Back to mapping' }}</button>
                            <button
                                type="button"
                                class="btn primary"
                                :disabled="toCreateCount === 0 || executing"
                                @click="executeImport"
                            >
                                <i v-if="executing" class="fa-solid fa-spinner fa-spin"></i>
                                {{ $t('execute') || 'Execute' }} ({{ toCreateCount }})
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="resultMessage" class="modal-footer result-footer">
                    <p :class="resultSuccess ? 'result-success' : 'result-error'">{{ resultMessage }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';
import { useToast } from 'vue-toastification';

export default {
    name: 'ArticleBatchImportModal',
    emits: ['import-done'],
    data() {
        return {
            showModal: false,
            isDragging: false,
            parseError: null,
            file: null,
            parsedFile: null,
            headers: [],
            sampleRows: [],
            mapping: {
                name_col: '',
                purchase_price_col: null,
                price_col: null,
            },
            loading: false,
            previewRows: [],
            rowOverrides: [],
            categories: [],
            executing: false,
            resultMessage: null,
            resultSuccess: false,
            openCategoryDropdown: null,
            openFormatDropdown: null,
        };
    },
    directives: {
        clickOutside: {
            mounted(el, binding) {
                el._clickOutside = (e) => {
                    if (!el.contains(e.target)) binding.value(e);
                };
                document.addEventListener('click', el._clickOutside);
            },
            unmounted(el) {
                document.removeEventListener('click', el._clickOutside);
            },
        },
    },
    computed: {
        toCreateCount() {
            return this.previewRows.filter(r => !r.is_duplicate).length;
        },
        duplicateCount() {
            return this.previewRows.filter(r => r.is_duplicate).length;
        },
    },
    methods: {
        async openModal() {
            this.showModal = true;
            this.resetState();
            try {
                const { data } = await axios.get('/api/article-categories');
                this.categories = data || [];
            } catch (_) {
                this.categories = [];
            }
        },
        closeModal() {
            this.showModal = false;
            this.resetState();
        },
        resetState() {
            this.file = null;
            this.parsedFile = null;
            this.headers = [];
            this.sampleRows = [];
            this.mapping = { name_col: '', purchase_price_col: null, price_col: null };
            this.previewRows = [];
            this.rowOverrides = [];
            this.openCategoryDropdown = null;
            this.openFormatDropdown = null;
            this.parseError = null;
            this.resultMessage = null;
            if (this.$refs.fileInput) {
                this.$refs.fileInput.value = '';
            }
        },
        resetFile() {
            this.parsedFile = null;
            this.file = null;
            this.headers = [];
            this.sampleRows = [];
            this.previewRows = [];
            this.rowOverrides = [];
            this.mapping = { name_col: '', purchase_price_col: null, price_col: null };
            this.parseError = null;
            if (this.$refs.fileInput) {
                this.$refs.fileInput.value = '';
            }
        },
        onDrop(e) {
            this.isDragging = false;
            const f = e.dataTransfer?.files?.[0];
            if (f && (f.name.endsWith('.xlsx') || f.name.endsWith('.xls'))) {
                this.parseFile(f);
            } else {
                this.parseError = 'Please drop an Excel file (.xlsx or .xls).';
            }
        },
        onFileSelect(e) {
            const f = e.target?.files?.[0];
            if (f) {
                this.parseFile(f);
            }
        },
        async parseFile(file) {
            this.parseError = null;
            this.file = file;
            this.loading = true;
            try {
                const form = new FormData();
                form.append('file', file);
                const { data } = await axios.post('/articles/import/parse', form, {
                    headers: { 'Content-Type': 'multipart/form-data', 'Accept': 'application/json' },
                });
                this.headers = data.headers || [];
                this.sampleRows = data.rows || [];
                this.parsedFile = true;
                if (this.headers.length) {
                    this.mapping.name_col = 0;
                    const last = this.headers.length - 1;
                    if (last >= 1) {
                        this.mapping.purchase_price_col = 1;
                        this.mapping.price_col = 1;
                    }
                }
            } catch (err) {
                this.parseError = err.response?.data?.message || err.message || 'Failed to parse file.';
            } finally {
                this.loading = false;
            }
        },
        async loadPreview() {
            if (!this.file) return;
            this.loading = true;
            this.resultMessage = null;
            try {
                const form = new FormData();
                form.append('file', this.file);
                form.append('mapping[name_col]', this.mapping.name_col);
                if (this.mapping.purchase_price_col != null && this.mapping.purchase_price_col !== '') {
                    form.append('mapping[purchase_price_col]', this.mapping.purchase_price_col);
                }
                if (this.mapping.price_col != null && this.mapping.price_col !== '') {
                    form.append('mapping[price_col]', this.mapping.price_col);
                }
                const { data } = await axios.post('/articles/import/preview', form, {
                    headers: { 'Content-Type': 'multipart/form-data', 'Accept': 'application/json' },
                });
                this.previewRows = data.rows || [];
                this.rowOverrides = (data.rows || []).map(() => ({
                    format_type: '2',
                    barcode: '',
                    comment: '',
                    height: '',
                    width: '',
                    length: '',
                    weight: '',
                    color: '',
                    factory_price: '',
                    category_ids: [],
                }));
            } catch (err) {
                const toast = useToast();
                toast.error(err.response?.data?.message || err.message || 'Preview failed.');
            } finally {
                this.loading = false;
            }
        },
        toggleCategoryDropdown(index) {
            this.openCategoryDropdown = this.openCategoryDropdown === index ? null : index;
            this.openFormatDropdown = null;
        },
        closeCategoryDropdown() {
            this.openCategoryDropdown = null;
        },
        toggleFormatDropdown(index) {
            this.openFormatDropdown = this.openFormatDropdown === index ? null : index;
            this.openCategoryDropdown = null;
        },
        closeFormatDropdown() {
            this.openFormatDropdown = null;
        },
        setFormat(index, value) {
            this.rowOverrides[index].format_type = value;
            const allowed = this.categoriesForFormat(value).map(c => c.id);
            this.rowOverrides[index].category_ids = (this.rowOverrides[index].category_ids || []).filter(id => allowed.includes(id));
            this.openFormatDropdown = null;
        },
        categoriesForFormat(formatType) {
            const formatTypeMap = { '1': 'small', '2': 'large' };
            const categoryType = formatTypeMap[formatType];
            if (categoryType) {
                return this.categories.filter(cat => cat.type === categoryType);
            }
            return this.categories;
        },
        formatLabel(value) {
            if (value === '1') return this.$t('Small') || 'Small';
            if (value === '2') return this.$t('Large') || 'Large';
            if (value === '3') return this.$t('Other') || 'Other';
            return this.$t('Large') || 'Large';
        },
        backToMapping() {
            this.previewRows = [];
            this.rowOverrides = [];
            this.openCategoryDropdown = null;
            this.openFormatDropdown = null;
            this.resultMessage = null;
        },
        async executeImport() {
            if (!this.file || this.toCreateCount === 0) return;
            this.executing = true;
            this.resultMessage = null;
            try {
                const form = new FormData();
                form.append('file', this.file);
                form.append('mapping[name_col]', this.mapping.name_col);
                if (this.mapping.purchase_price_col != null && this.mapping.purchase_price_col !== '') {
                    form.append('mapping[purchase_price_col]', this.mapping.purchase_price_col);
                }
                if (this.mapping.price_col != null && this.mapping.price_col !== '') {
                    form.append('mapping[price_col]', this.mapping.price_col);
                }
                this.rowOverrides.forEach((o, i) => {
                    form.append(`overrides[${i}][format_type]`, o.format_type || '2');
                    form.append(`overrides[${i}][barcode]`, o.barcode || '');
                    form.append(`overrides[${i}][comment]`, o.comment || '');
                    form.append(`overrides[${i}][height]`, o.height !== undefined && o.height !== null ? String(o.height) : '');
                    form.append(`overrides[${i}][width]`, o.width !== undefined && o.width !== null ? String(o.width) : '');
                    form.append(`overrides[${i}][length]`, o.length !== undefined && o.length !== null ? String(o.length) : '');
                    form.append(`overrides[${i}][weight]`, o.weight !== undefined && o.weight !== null ? String(o.weight) : '');
                    form.append(`overrides[${i}][color]`, o.color || '');
                    form.append(`overrides[${i}][factory_price]`, o.factory_price !== undefined && o.factory_price !== null ? String(o.factory_price) : '');
                    (o.category_ids || []).forEach(id => form.append(`overrides[${i}][category_ids][]`, id));
                });
                const { data } = await axios.post('/articles/import/execute', form, {
                    headers: { 'Content-Type': 'multipart/form-data', 'Accept': 'application/json' },
                });
                this.resultSuccess = true;
                this.resultMessage = `${data.message} Created: ${data.created}, Skipped (duplicates): ${data.skipped}.`;
                const toast = useToast();
                toast.success(this.resultMessage);
                this.$emit('import-done');
                setTimeout(() => {
                    this.closeModal();
                }, 2000);
            } catch (err) {
                this.resultSuccess = false;
                this.resultMessage = err.response?.data?.message || err.message || 'Import failed.';
                const toast = useToast();
                toast.error(this.resultMessage);
            } finally {
                this.executing = false;
            }
        },
        formatNum(val) {
            if (val == null || val === '') return '—';
            const n = Number(val);
            return isNaN(n) ? val : n.toFixed(2);
        },
    },
};
</script>

<style scoped lang="scss">
.import-excel-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    min-width: 140px;
    justify-content: center;
    background: #0d9488;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
}
.import-excel-btn:hover {
    background: #0f766e;
}

.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 12px;
}

.article-import-modal {
    width: 98vw;
    max-width: 98vw;
    height: 96vh;
    max-height: 96vh;
    min-height: 85vh;
    display: flex;
    flex-direction: column;
    background: $light-gray;
    border-radius: 8px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    background: $dark-gray;
    color: white;
    border-radius: 8px 8px 0 0;
}

.modal-title {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
}

.close-button {
    background: none;
    border: none;
    color: white;
    font-size: 20px;
    cursor: pointer;
    padding: 4px;
    opacity: 0.9;
}
.close-button:hover {
    opacity: 1;
}

.modal-body {
    padding: 20px;
    overflow-y: auto;
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 0;
}

.drop-zone {
    border: 2px dashed rgba(255, 255, 255, 0.4);
    border-radius: 8px;
    padding: 40px 20px;
    text-align: center;
    cursor: pointer;
    position: relative;
    transition: border-color 0.2s, background 0.2s;
}
.drop-zone:hover,
.drop-zone-active {
    border-color: #3b82f6;
    background: rgba(59, 130, 246, 0.08);
}
.drop-zone-error {
    border-color: #ef4444;
    background: rgba(239, 68, 68, 0.08);
}
.drop-input {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer;
    width: 100%;
    height: 100%;
}
.drop-icon {
    font-size: 48px;
    color: #0d9488;
    margin-bottom: 12px;
}
.drop-text {
    color: #fff;
    margin: 0 0 4px 0;
    font-size: 16px;
}
.drop-hint {
    color: rgba(255, 255, 255, 0.6);
    margin: 0;
    font-size: 13px;
}
.drop-error {
    color: #ef4444;
    margin-top: 12px;
    font-size: 14px;
}

.section-label,
.mapping-note {
    color: #fff;
    margin-bottom: 16px;
    font-size: 14px;
}
.mapping-note {
    opacity: 0.85;
    font-size: 13px;
    margin-top: 8px;
}
.required {
    color: #f87171;
}
.mapping-grid {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 16px;
}
.mapping-row {
    display: grid;
    grid-template-columns: 160px 1fr;
    align-items: center;
    gap: 12px;
}
.mapping-row label {
    color: #fff;
    font-size: 14px;
}
.mapping-select {
    padding: 8px 12px;
    border-radius: 6px;
    border: 1px solid rgba(255, 255, 255, 0.3);
    background: rgba(255, 255, 255, 0.95);
    color: #333;
    font-size: 14px;
    min-width: 200px;
}

.table-wrap {
    overflow-x: auto;
    overflow-y: auto;
    margin-bottom: 16px;
    max-height: 65vh;
    min-height: 320px;
}
.preview-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.preview-table th,
.preview-table td {
    border: 1px solid rgba(255, 255, 255, 0.25);
    padding: 8px 10px;
    text-align: left;
    background: rgba(255, 255, 255, 0.05);
    color: #fff;
}
.preview-table th {
    background: $dark-gray;
    font-weight: 600;
    white-space: nowrap;
}
.preview-empty-msg {
    color: rgba(255, 255, 255, 0.85);
    padding: 24px 16px;
    margin: 0;
    font-size: 14px;
}
.preview-section {
    display: flex;
    flex-direction: column;
    min-height: 0;
    flex: 1;
}
.preview-section .table-wrap {
    flex: 1 1 0;
    min-height: 320px;
    max-height: 70vh;
}
.preview-table tr.row-duplicate td {
    background: rgba(239, 68, 68, 0.15);
}
.preview-table td.cell-empty {
    color: rgba(255, 255, 255, 0.4);
    font-style: italic;
}
.cell-edit {
    padding: 4px 6px !important;
    vertical-align: middle;
}
.cell-input {
    width: 100%;
    min-width: 60px;
    max-width: 140px;
    padding: 4px 6px;
    font-size: 12px;
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 4px;
    background: rgba(255, 255, 255, 0.12);
    color: #fff;
}
.cell-input:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    background: rgba(255, 255, 255, 0.06);
}
.cell-input::placeholder {
    color: rgba(255, 255, 255, 0.4);
}
.cell-select {
    cursor: pointer;
    max-width: 100px;
}

/* Format dropdown – matches category/modal design */
.format-dropdown-wrap {
    position: relative;
    min-width: 90px;
    max-width: 120px;
}
.format-dropdown-trigger {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 6px;
    width: 100%;
    padding: 6px 8px;
    font-size: 12px;
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 4px;
    background: rgba(255, 255, 255, 0.12);
    color: #fff;
    cursor: pointer;
    text-align: left;
    transition: border-color 0.2s, background 0.2s;
}
.format-dropdown-trigger:hover {
    background: rgba(255, 255, 255, 0.18);
    border-color: rgba(255, 255, 255, 0.45);
}
.format-dropdown-trigger.format-dropdown-open {
    border-color: #0d9488;
    background: rgba(13, 148, 136, 0.2);
}
.format-dropdown-label {
    color: #fff;
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.format-dropdown-arrow {
    font-size: 10px;
    opacity: 0.8;
    flex-shrink: 0;
}
.format-dropdown-panel {
    position: absolute;
    left: 0;
    top: 100%;
    margin-top: 4px;
    min-width: 120px;
    border: 1px solid rgba(255, 255, 255, 0.25);
    border-radius: 6px;
    background: $dark-gray;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    z-index: 10;
    overflow: hidden;
}
.format-dropdown-option {
    display: block;
    width: 100%;
    padding: 8px 12px;
    font-size: 13px;
    color: #fff;
    background: transparent;
    border: none;
    cursor: pointer;
    text-align: left;
    transition: background 0.15s;
}
.format-dropdown-option:hover {
    background: rgba(255, 255, 255, 0.1);
}
.format-dropdown-option.active {
    background: rgba(13, 148, 136, 0.35);
    color: #5eead4;
}

/* Category dropdown – matches modal design */
.category-dropdown-wrap {
    position: relative;
    min-width: 100px;
    max-width: 140px;
}
.category-dropdown-trigger {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 6px;
    width: 100%;
    padding: 6px 8px;
    font-size: 12px;
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 4px;
    background: rgba(255, 255, 255, 0.12);
    color: #fff;
    cursor: pointer;
    text-align: left;
    transition: border-color 0.2s, background 0.2s;
}
.category-dropdown-trigger:hover {
    background: rgba(255, 255, 255, 0.18);
    border-color: rgba(255, 255, 255, 0.45);
}
.category-dropdown-trigger.category-dropdown-open {
    border-color: #0d9488;
    background: rgba(13, 148, 136, 0.2);
}
.category-placeholder {
    color: rgba(255, 255, 255, 0.5);
}
.category-selected {
    color: #fff;
}
.category-dropdown-arrow {
    font-size: 10px;
    opacity: 0.8;
    flex-shrink: 0;
}
.category-dropdown-panel {
    position: absolute;
    left: 0;
    top: 100%;
    margin-top: 4px;
    min-width: 180px;
    max-height: 200px;
    overflow-y: auto;
    border: 1px solid rgba(255, 255, 255, 0.25);
    border-radius: 6px;
    background: $dark-gray;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    z-index: 10;
}
.category-dropdown-list {
    padding: 6px 0;
}
.category-dropdown-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px;
    cursor: pointer;
    font-size: 13px;
    color: #fff;
    margin: 0;
}
.category-dropdown-item:hover {
    background: rgba(255, 255, 255, 0.1);
}
.category-dropdown-item input[type="checkbox"] {
    width: 14px;
    height: 14px;
    accent-color: #0d9488;
    cursor: pointer;
}
.category-dropdown-empty {
    padding: 12px;
    margin: 0;
    font-size: 13px;
    color: rgba(255, 255, 255, 0.6);
}

.badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}
.badge.duplicate {
    background: rgba(239, 68, 68, 0.3);
    color: #fca5a5;
}
.badge.create {
    background: rgba(34, 197, 94, 0.3);
    color: #86efac;
}

.modal-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    flex-wrap: wrap;
}
.btn {
    padding: 10px 18px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    border: none;
    transition: opacity 0.2s;
}
.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
.btn.secondary {
    background: rgba(255, 255, 255, 0.2);
    color: #fff;
}
.btn.secondary:hover:not(:disabled) {
    background: rgba(255, 255, 255, 0.3);
}
.btn.primary {
    background: #0d9488;
    color: white;
}
.btn.primary:hover:not(:disabled) {
    background: #0f766e;
}

.modal-footer.result-footer {
    padding: 12px 20px;
    background: rgba(0, 0, 0, 0.2);
    border-radius: 0 0 8px 8px;
}
.result-success {
    color: #86efac;
    margin: 0;
    font-size: 14px;
}
.result-error {
    color: #fca5a5;
    margin: 0;
    font-size: 14px;
}

</style>

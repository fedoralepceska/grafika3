<template>
    <div>
        <!-- Button to open dialog -->
        <button @click="openDialog" class="bg-white/40 inline-flex items-center px-4 py-2 border border-transparent white-hover rounded-md font-semibold text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-50">
            <i class="fa-solid fa-chart-line"></i> Import Summary
        </button>

        <!-- Modal Dialog -->
        <div v-if="dialog" class="modal-overlay" @click="closeDialog">
            <div class="modal-content" @click.stop>
                <div class="modal-header">
                    <h3 class="text-h5 text-white">{{ $t('materialImportSummary') }}</h3>
                    <button @click="closeDialog" class="close-button">
                        <i class="fa-solid fa-times"></i>
                    </button>
                </div>
                
                <div class="modal-body">
                    <div v-if="loading" class="text-center text-white">
                        <i class="fa-solid fa-spinner fa-spin fa-2x"></i>
                        <p>Loading import summary...</p>
                    </div>
                    <div v-else-if="materialSummary.length > 0">
                        <table class="excel-table">
                            <thead>
                            <tr class="first-row">
                                <th style="width: 20px">#</th>
                                <th style="width: 80px;">{{$t('Code')}}<div class="resizer" @mousedown="initResize($event, 1)"></div></th>
                                <th>{{$t('articleName')}}<div class="resizer" @mousedown="initResize($event, 2)"></div></th>
                                <th style="width: 100px;">{{$t('Format')}}<div class="resizer" @mousedown="initResize($event, 3)"></div></th>
                                <th style="width: 80px;">{{$t('Dimensions')}}<div class="resizer" @mousedown="initResize($event, 4)"></div></th>
                                <th style="width: 100px;">{{$t('TotalImported')}}<div class="resizer" @mousedown="initResize($event, 5)"></div></th>
                                <th style="width: 100px;">{{$t('CurrentStock')}}<div class="resizer" @mousedown="initResize($event, 6)"></div></th>
                                <th style="width: 120px;">{{$t('TotalValue')}} (.ден)<div class="resizer" @mousedown="initResize($event, 7)"></div></th>
                                <th style="width: 120px;">{{$t('AvgPrice')}} (.ден)<div class="resizer" @mousedown="initResize($event, 8)"></div></th>
                                <th style="width: 100px;">{{$t('LastImport')}}<div class="resizer" @mousedown="initResize($event, 9)"></div></th>
                            </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(material, index) in materialSummary" :key="material.article_id">
                                    <th>{{index + 1}}</th>
                                    <th>{{material.code}}</th>
                                    <th>{{material.name}}</th>
                                    <th>{{getFormatTypeName(material.format_type)}}</th>
                                    <th>{{formatDimensions(material.width, material.height)}}</th>
                                    <th>{{formatQuantity(material.total_imported)}}</th>
                                    <th>{{formatQuantity(material.current_stock)}}</th>
                                    <th>{{formatNumber(material.total_value)}}</th>
                                    <th>{{formatNumber(material.average_price)}}</th>
                                    <th>{{formatDate(material.last_import_date)}}</th>
                                </tr>
                            </tbody>
                        </table>
                        
                        <!-- Summary Statistics -->
                        <div class="summary-stats mt-4 p-4 bg-dark-gray rounded">
                            <h3 class="text-white mb-3">{{ $t('SummaryStatistics') }}</h3>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="stat-item">
                                    <div class="stat-label">{{ $t('TotalMaterials') }}</div>
                                    <div class="stat-value">{{materialSummary.length}}</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-label">{{ $t('TotalImportedQuantity') }}</div>
                                    <div class="stat-value">{{formatQuantity(totalImportedQuantity)}}</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-label">{{ $t('TotalValue') }}</div>
                                    <div class="stat-value">{{formatNumber(totalValue)}} .ден</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-label">{{ $t('CurrentStockValue') }}</div>
                                    <div class="stat-value">{{formatNumber(currentStockValue)}} .ден</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center text-white">
                        <p>No import data available.</p>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button @click="closeDialog" class="btn red">{{ $t('close') }}</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";

export default {
    data() {
        return {
            dialog: false,
            loading: false,
            materialSummary: [],
        };
    },
    computed: {
        totalImportedQuantity() {
            return this.materialSummary.reduce((total, material) => total + parseFloat(material.total_imported || 0), 0);
        },
        totalValue() {
            return this.materialSummary.reduce((total, material) => total + parseFloat(material.total_value || 0), 0);
        },
        currentStockValue() {
            return this.materialSummary.reduce((total, material) => {
                const stockValue = parseFloat(material.current_stock || 0) * parseFloat(material.average_price || 0);
                return total + stockValue;
            }, 0);
        }
    },
    methods: {
        openDialog() {
            this.dialog = true;
            this.loadMaterialSummary();
        },
        closeDialog() {
            this.dialog = false;
        },
        handleEscapeKey(event) {
            if (event.key === 'Escape') {
                this.closeDialog();
            }
        },
        async loadMaterialSummary() {
            this.loading = true;
            try {
                const response = await axios.get('/api/material-import-summary');
                this.materialSummary = response.data;
            } catch (error) {
                console.error('Error loading material summary:', error);
            } finally {
                this.loading = false;
            }
        },
        getFormatTypeName(formatType) {
            switch (formatType) {
                case 1:
                    return 'Small Format';
                case 2:
                    return 'Large Format';
                case 3:
                    return 'Other';
                default:
                    return 'Unknown';
            }
        },
        formatDimensions(width, height) {
            if (width && height) {
                return `${width} × ${height}`;
            } else if (width) {
                return `${width}`;
            } else if (height) {
                return `${height}`;
            }
            return '-';
        },
        formatQuantity(quantity) {
            const num = Number(quantity);
            if (Number.isInteger(num)) {
                return num.toString();
            }
            return num.toFixed(5).replace(/\.?0+$/, '');
        },
        formatNumber(number) {
            return Number(number).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        formatDate(dateString) {
            if (!dateString) return '-';
            return new Date(dateString).toLocaleDateString('en-GB');
        },
        initResize(event, index) {
            const startX = event.clientX;
            const startWidth = event.target.parentElement.offsetWidth;

            const resizeColumn = (e) => {
                const diffX = e.clientX - startX;
                const newWidth = startWidth + diffX;
                event.target.parentElement.style.width = `${newWidth}px`;
            };

            const stopResize = () => {
                document.documentElement.removeEventListener('mousemove', resizeColumn);
                document.documentElement.removeEventListener('mouseup', stopResize);
            };

            document.documentElement.addEventListener('mousemove', resizeColumn);
            document.documentElement.addEventListener('mouseup', stopResize);
        }
    },
    mounted() {
        document.addEventListener('keydown', this.handleEscapeKey);
    },
    beforeUnmount() {
        document.removeEventListener('keydown', this.handleEscapeKey);
    }
};
</script>

<style scoped lang="scss">
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.modal-content {
    background-color: $light-gray;
    border-radius: 8px;
    width: 90%;
    max-width: 1400px;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid $ultra-light-gray;
}

.close-button {
    background: none;
    border: none;
    color: white;
    font-size: 20px;
    cursor: pointer;
    padding: 5px;
}

.close-button:hover {
    opacity: 0.7;
}

.modal-body {
    padding: 20px 24px;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    padding: 20px 24px;
    border-top: 1px solid $ultra-light-gray;
}

.white {
    background-color: $white;
    color: black;
    &-hover:hover {
        background-color: darken($white, 25%);
    }
}

.red {
    background-color: $red;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
}

.btn {
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
}

table {
    color: white;
    width: 100%;
    border-collapse: collapse;
}

table th, table td {
    padding: 8px;
    border: 1px solid $ultra-light-gray;
    text-align: center;
}

table th {
    background-color: $ultra-light-gray;
    font-weight: bold;
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

.summary-stats {
    background-color: $dark-gray;
    border-radius: 8px;
}

.stat-item {
    text-align: center;
    padding: 15px;
    background-color: $ultra-light-gray;
    border-radius: 4px;
}

.stat-label {
    font-size: 12px;
    color: $white;
    opacity: 0.8;
    margin-bottom: 5px;
}

.stat-value {
    font-size: 18px;
    font-weight: bold;
    color: $white;
}

.grid {
    display: grid;
    gap: 1rem;
}

.grid-cols-2 {
    grid-template-columns: repeat(2, 1fr);
}

@media (min-width: 768px) {
    .md\\:grid-cols-4 {
        grid-template-columns: repeat(4, 1fr);
    }
}

.gap-4 {
    gap: 1rem;
}

.mt-4 {
    margin-top: 1rem;
}

.mb-3 {
    margin-bottom: 0.75rem;
}

.text-center {
    text-align: center;
}

.text-white {
    color: white;
}

.text-h5 {
    font-size: 1.25rem;
    font-weight: bold;
}
</style>

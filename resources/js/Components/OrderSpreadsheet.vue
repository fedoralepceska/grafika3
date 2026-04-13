<template>
    <div class="spreadsheet-container">
        <div class="controls-wrapper flex justify-end">
            <button
                type="button"
                class="rounded text-black py-2 px-2 m-1"
                :class="{ 'bg-white': !editMode, 'green text-white': editMode }"
                @click="toggleEditMode"
            >
                {{ editMode ? 'Exit Edit Mode \u2612' : 'Edit Mode \u2610' }}
            </button>
            <button
                v-if="editMode"
                type="button"
                class="blue rounded text-white py-2 px-5 m-1"
                :disabled="saveLoading"
                @click="saveChanges"
            >
                Save Changes
                <v-icon class="mdi mdi-check" />
            </button>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th class="w-[5%]">Line</th>
                        <th class="w-[13%]">Job Name</th>
                        <th class="w-[7%]">Qty</th>
                        <th class="w-[7%]">Copies</th>
                        <th class="w-[9%]">Dims</th>
                        <th class="w-[11%]">Ship Date</th>
                        <th class="w-[13%]">Address</th>
                        <th class="w-[9%]">Status</th>
                        <th class="w-[7%]">Unit</th>
                        <th class="w-[7%]" v-if="canViewPrice">Job Price</th>
                        <th class="w-[8%]" v-if="canViewPrice">Sale</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(job, index) in invoice.jobs" :key="job.id || `job-${index}`">
                        <td>#{{ index + 1 }}</td>
                        <td class="job-name">{{ job.file }}</td>
                        <td v-if="editMode">
                            <input
                                v-model.number="job.editableQuantity"
                                type="number"
                                min="1"
                                step="1"
                                class="text-black w-full"
                            />
                        </td>
                        <td v-else>
                            {{ job.quantity }}
                        </td>
                        <td v-if="editMode">
                            <input
                                v-model.number="job.editableCopies"
                                type="number"
                                min="1"
                                step="1"
                                class="text-black w-full"
                            />
                        </td>
                        <td v-else>
                            {{ job.copies }}
                        </td>
                        <td>
                            {{
                                job.computed_total_area_m2 && typeof job.computed_total_area_m2 === 'number'
                                    ? job.computed_total_area_m2.toFixed(4) + 'm²'
                                    : '0.0000m²'
                            }}
                        </td>
                        <td>{{ invoice?.end_date }}</td>
                        <td class="address">{{ job.shippingInfo }}</td>
                        <td>{{ job.status }}</td>
                        <td>{{ job?.small_material?.small_format_material?.price_per_unit }}.ден</td>
                        <td v-if="canViewPrice">
                            {{
                                job.totalPrice && typeof job.totalPrice === 'number'
                                    ? job.totalPrice.toFixed(2)
                                    : '0.00'
                            }}.ден
                        </td>
                        <td v-if="canViewPrice && editMode">
                            <input
                                v-model="job.editableSalePrice"
                                type="text"
                                inputmode="decimal"
                                class="text-black w-full"
                            />
                        </td>
                        <td v-else-if="canViewPrice">{{ job.salePrice }}.ден</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script>
import axios from 'axios';
import { useToast } from 'vue-toastification';

export default {
    props: {
        job: Object,
        invoice: Object,
        canViewPrice: Boolean,
    },
    emits: ['jobs-saved'],
    data() {
        return {
            editMode: false,
            saveLoading: false,
        };
    },
    methods: {
        toggleEditMode() {
            if (this.editMode) {
                this.clearEditState();
                this.editMode = false;
                return;
            }
            this.editMode = true;
            const jobs = this.invoice?.jobs || [];
            jobs.forEach((job) => {
                job.editableQuantity = this.normalizePositiveInt(job.quantity, 1);
                job.editableCopies = this.normalizePositiveInt(job.copies, 1);
                job.editableSalePrice =
                    job.salePrice != null && job.salePrice !== '' ? String(job.salePrice) : '';
                job._spreadsheetEditSnapshot = {
                    quantity: job.editableQuantity,
                    copies: job.editableCopies,
                    salePrice: job.editableSalePrice,
                };
            });
        },
        clearEditState() {
            (this.invoice?.jobs || []).forEach((job) => {
                job.editableQuantity = null;
                job.editableCopies = null;
                job.editableSalePrice = null;
                delete job._spreadsheetEditSnapshot;
            });
        },
        normalizePositiveInt(value, fallback) {
            const n = parseInt(value, 10);
            if (!Number.isFinite(n) || n < 1) {
                return fallback;
            }
            return n;
        },
        parseSaleInput(raw) {
            const n = parseFloat(String(raw ?? '').replace(',', '.'));
            return Number.isFinite(n) ? n : null;
        },
        effectiveCatalogItemId(job) {
            return (
                job.effective_catalog_item_id ??
                job.catalog_item_id ??
                this.invoice?.catalog_item_id ??
                null
            );
        },
        effectiveClientId(job) {
            return job.effective_client_id ?? job.client_id ?? this.invoice?.client_id ?? null;
        },
        applyJobUpdate(localJob, serverJob) {
            if (!serverJob) {
                return;
            }
            const keys = [
                'quantity',
                'copies',
                'salePrice',
                'price',
                'total_area_m2',
                'dimensions_breakdown',
                'computed_total_area_m2',
                'effective_catalog_item_id',
                'effective_client_id',
            ];
            keys.forEach((k) => {
                if (Object.prototype.hasOwnProperty.call(serverJob, k) && serverJob[k] !== undefined) {
                    localJob[k] = serverJob[k];
                }
            });
        },
        async saveChanges() {
            if (!this.editMode) {
                return;
            }
            const toast = useToast();
            const jobs = this.invoice?.jobs || [];
            this.saveLoading = true;
            let savedAny = false;
            let hadError = false;

            try {
                for (const job of jobs) {
                    const q = parseInt(String(job.editableQuantity), 10);
                    const c = parseInt(String(job.editableCopies), 10);
                    if (!Number.isFinite(q) || q < 1 || !Number.isFinite(c) || c < 1) {
                        toast.error('Quantity and copies must be whole numbers ≥ 1.');
                        hadError = true;
                        break;
                    }

                    const snap = job._spreadsheetEditSnapshot || {};
                    const qtyChanged = q !== Number(job.quantity);
                    const copiesChanged = c !== Number(job.copies);
                    const saleUserEdited =
                        String(job.editableSalePrice ?? '').trim() !== String(snap.salePrice ?? '').trim();

                    if (!qtyChanged && !copiesChanged && !saleUserEdited) {
                        continue;
                    }

                    const payload = {};
                    if (qtyChanged) {
                        payload.quantity = q;
                    }
                    if (copiesChanged) {
                        payload.copies = c;
                    }
                    if (saleUserEdited) {
                        const sp = this.parseSaleInput(job.editableSalePrice);
                        if (sp === null) {
                            toast.error('Sale price must be a valid number.');
                            hadError = true;
                            break;
                        }
                        payload.salePrice = sp;
                    }

                    const catId = this.effectiveCatalogItemId(job);
                    const clientId = this.effectiveClientId(job);
                    if ((qtyChanged || copiesChanged) && catId && clientId) {
                        payload.catalog_item_id = catId;
                        payload.client_id = clientId;
                    }

                    try {
                        const { data } = await axios.put(`/jobs/${job.id}`, payload);
                        if (data?.job) {
                            this.applyJobUpdate(job, data.job);
                            savedAny = true;
                        }
                    } catch (error) {
                        console.error('Error updating job:', error);
                        hadError = true;
                        const msg =
                            error?.response?.data?.details ||
                            error?.response?.data?.error ||
                            error?.response?.data?.message ||
                            'Failed to update job';
                        toast.error(msg);
                        break;
                    }
                }

                if (!hadError) {
                    if (savedAny) {
                        toast.success('Jobs updated.');
                        this.$emit('jobs-saved');
                    } else {
                        toast.info('No changes to save.');
                    }
                    this.clearEditState();
                    this.editMode = false;
                }
            } finally {
                this.saveLoading = false;
            }
        },
    },
};
</script>

<style scoped lang="scss">
.spreadsheet-container {
    width: 100%;
    max-width: 100%;
    margin: 0 auto;
    padding: 0 1rem;
}

.controls-wrapper {
    margin-bottom: 1rem;
}

.table-wrapper {
    width: 100%;
}

table {
    width: 100%;
    border-collapse: collapse;
    border-spacing: 0;
    margin-bottom: 5px;
    border: 1px solid #ddd;
    border-radius: 5px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    table-layout: fixed;
}

table th,
table td {
    padding: 8px 4px;
    border: 1px solid #ddd;
    text-align: center;
    overflow: hidden;
    text-overflow: ellipsis;
    word-wrap: break-word;
}

table th {
    color: white;
    border-top: 1px solid #ddd;
    border-bottom: 1px solid #ddd;
    background-color: $ultra-light-gray;
    font-size: 0.9rem;
}

.job-name,
.address {
    white-space: normal;
}

@media screen and (max-width: 1200px) {
    table th,
    table td {
        font-size: 0.85rem;
        padding: 6px 3px;
    }
}

@media screen and (max-width: 992px) {
    table th,
    table td {
        font-size: 0.8rem;
        padding: 4px 2px;
    }
}

@media screen and (max-width: 768px) {
    table th,
    table td {
        font-size: 0.75rem;
        padding: 3px 2px;
    }

    .controls-wrapper button {
        font-size: 0.8rem;
        padding: 4px 8px;
    }
}

input {
    max-width: 100%;
    padding: 2px;
    box-sizing: border-box;
}

.blue {
    background-color: $blue;
}

.green {
    background-color: green;
}
</style>

<template>
    <div class="spreadsheet-container">
        <div class="controls-wrapper flex justify-end">
            <button @click="toggleEditMode" class="rounded text-black py-2 px-2 m-1"
                    :class="{ 'bg-white': !editMode, 'green text-white': editMode }">
                {{ editMode ? 'Exit Edit Mode \u2612' : 'Edit Mode \u2610' }}
            </button>
            <button @click="saveChanges()" v-if="editMode" class="blue rounded text-white py-2 px-5 m-1">Save Changes<v-icon class="mdi mdi-check"></v-icon></button>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                <tr>
                    <th class="w-[5%]">Line</th>
                    <th class="w-[15%]">Job Name</th>
                    <th class="w-[8%]">Qty</th>
                    <th class="w-[10%]">Dims</th>
                    <th class="w-[12%]">Ship Date</th>
                    <th class="w-[15%]">Address</th>
                    <th class="w-[10%]">Status</th>
                    <th class="w-[8%]">Unit</th>
                    <th class="w-[8%]" v-if="canViewPrice">Job Price</th>
                    <th class="w-[9%]">Sale</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="(job,index) in invoice.jobs">
                    <td>#{{index+1}}</td>
                    <td class="job-name">{{ job.file}}</td>
                    <td v-if="editMode">
                        <input type="text" class="text-black w-full" v-model="job.editableQuantity" />
                    </td>
                    <td v-else>
                        {{job.quantity}}
                    </td>
                    <td>{{job.height.toFixed(2)}}x{{job.width.toFixed(2)}}</td>
                    <td>{{ invoice?.end_date}}</td>
                    <td class="address">{{job.shippingInfo}}</td>
                    <td>{{job.status}}</td>
                    <td>{{ job?.small_material?.small_format_material?.price_per_unit }}.ден</td>
                    <td v-if="canViewPrice">{{job.totalPrice.toFixed(2)}}.ден</td>
                    <td v-if="editMode">
                        <input type="text" class="text-black w-full" v-model="job.editableSalePrice" />
                    </td>
                    <td v-else>
                        {{job.salePrice}}.ден
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script>
import axios from "axios";

export default {
    props: {
        job: Object,
        invoice: Object,
        canViewPrice: Boolean
    },
    data(){
        return {
            editMode: false,

        }
    },
    methods: {
        toggleEditMode() {
            this.editMode = !this.editMode;
            if (this.editMode) {
                this.invoice.forEach((job) => {
                    if (!job.editableQuantity) {
                        job.editableQuantity = job.quantity;
                    }
                    if (!job.editableSalePrice) {
                        job.editableSalePrice = job.salePrice;
                    }
                });
            }
        },
        async saveChanges() {
            if (!this.editMode) {
                // Exit edit mode
                return;
            }

            // Handle the save changes action
            const promises = this.invoice.jobs.map(async (job) => {
                if (
                    job.editableQuantity !== job.quantity ||
                    job.editableSalePrice !== job.salePrice
                ) {
                    try {
                        const response = await axios.put(`/jobs/${job.id}`, {
                            quantity: job.editableQuantity,
                            salePrice: job.editableSalePrice
                        });
                        // Update the material with the response data
                        job.quantity = response.data.quantity;
                        job.salePrice = response.data.salePrice;
                    } catch (error) {
                        console.error('Error updating job:', error);
                    }
                }
            });

            await Promise.all(promises);

            // Reset the editable fields and exit edit mode
            this.invoice.jobs.forEach((job) => {
                job.editableQuantity = null;
                job.editableSalePrice = null;
            });
            this.editMode = false;
            window.location.reload();
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

table th, table td {
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


.job-name, .address {
    white-space: normal;
}

// Responsive text sizing
@media screen and (max-width: 1200px) {
    table th, table td {
        font-size: 0.85rem;
        padding: 6px 3px;
    }
}

@media screen and (max-width: 992px) {
    table th, table td {
        font-size: 0.8rem;
        padding: 4px 2px;
    }
}

@media screen and (max-width: 768px) {
    table th, table td {
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

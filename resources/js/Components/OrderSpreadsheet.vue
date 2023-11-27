<template>
    <div class="spreadsheet">
        <div class="flex justify-end">
            <button @click="toggleEditMode" class="rounded text-black py-2 px-2 m-1"
                    :class="{ 'bg-white': !editMode, 'green text-white': editMode }">
                {{ editMode ? 'Exit Edit Mode \u2612' : 'Edit Mode \u2610' }}
            </button>
            <button @click="saveChanges()" v-if="editMode" class="blue rounded text-white py-2 px-5 m-1">Save Changes<v-icon class="mdi mdi-check"></v-icon></button>
        </div>
        <table>
            <thead>
            <tr>
                <th>Job Line</th>
                <th>Job Name</th>
                <th >Quantity</th>
                <th>Dims (mm)</th>
                <th>Scheduled Ship Date</th>
                <th>Shipping Address</th>
                <th>Job Status</th>
                <th>Unit Price</th>
                <th>Job Price</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="(job,index) in invoice.jobs" >
                <td>#{{index+1}}</td>
                <td>{{ job.file}}</td>
                <td v-if="editMode">
                    <input type="text" class="text-black" v-model="job.editableQuantity" />
                </td>
                <td v-else>
                    {{job.quantity}}
                </td>
                <td>{{job.height}}x{{job.width}}</td>
                <td>{{ invoice?.end_date}}</td>
                <td>{{job.shippingInfo}}</td>
                <td>{{job.status}}</td>
                <td>{{ job?.small_material?.small_format_material?.price_per_unit }}.ден</td>
                <td>{{job.totalPrice.toFixed(2)}}.ден</td>
            </tr>
            </tbody>
        </table>
    </div>
</template>

<script>
import axios from "axios";

export default {
    props: {
        job: Object,
        invoice: Object,
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
                    job.editableQuantity !== job.quantity
                ) {
                    try {
                        const response = await axios.put(`/jobs/${job.id}`, {
                            quantity: job.editableQuantity,
                        });
                        // Update the material with the response data
                        job.quantity = response.data.quantity;
                    } catch (error) {
                        console.error('Error updating job:', error);
                    }
                }
            });

            await Promise.all(promises);

            // Reset the editable fields and exit edit mode
            this.invoice.jobs.forEach((job) => {
                job.editableQuantity = null;
            });
            this.editMode = false;
            window.location.reload();
        },
    },
};
</script>

<style scoped lang="scss">
table {
    min-width: 191vh;
    max-width: 191vh;
    border-collapse: collapse;
    border-spacing: 0;
    margin-bottom: 5px;
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
.blue{
    background-color: $blue;
}
.green{
    background-color: green;
}
</style>

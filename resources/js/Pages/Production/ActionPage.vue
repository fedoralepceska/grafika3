<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="action" subtitle="actionInfo" icon="task.png" link="production"/>
            <div v-for="(invoice,index) in invoices" class="main">
                <div :class="['container', 'flex', 'gap-20', 'relative', 'p-2', { 'red': invoice.onHold }]">
                <div class="order bg-white text-black bold p-3" style="min-width: 20vh" @click="navigateToOrder(invoice.id)" ><strong>{{invoice.invoice_title}}</strong></div>
                    <div class="info">
                        <div>Order</div>
                        <div class="bold">#{{ invoice.id }}</div>
                    </div>
                    <div class="info">
                        <div>Customer</div>
                        <div class="bold">{{invoice.client_name}}</div>
                    </div>
                    <div class="info">
                        <div>{{ $t('End Date') }}</div>
                        <div class="bold">{{ invoice?.end_date }}</div>
                    </div>
                    <div class="info">
                        <div>Created By</div>
                        <div class="bold">{{ invoice.user_name }}</div>
                    </div>
                    <div class="info">
                        <div>Current Step</div>
                        <div class="bold">{{actionId.startsWith('Machine') ? $t(`machinePrint.${actionId}`) : $t(`actions.${actionId}`)}}</div>
                    </div>
                    <div class="btns">
                        <div class="bt" @click="viewJobs(index)"><i class="fa-solid fa-bars"></i></div>
                    </div>
                </div>
                <div v-if="jobViewMode===index">
                    <table>
                        <thead>
                        <tr :class="[{
                            'red' :  invoice.onHold
                        }]" v-if="invoice.onHold">
                            <td colspan="9">
                                <i class="fa-solid fa-ban"></i>
                                    THIS ORDER IS ON HOLD
                                <i class="fa-solid fa-ban"></i></td> <!-- Adjust colspan based on the number of columns in your table -->
                        </tr>
                        <tr>
                                <th>LN</th>
                                <th>Img</th>
                                <th>Qty</th>
                                <th>Copies</th>
                                <th>Height</th>
                                <th>Width</th>
                                <th>Print</th>
                                <th>Cut</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody v-for="(job, jobIndex) in invoice.jobs" :key="jobIndex">
<!--
                        Bi trebalo da e invoice.jobs za da gi dava samo za toj invoice #TODO
-->
                            <tr v-if="invoice.comment && !acknowledged && !job.hasNote">
                                <td colspan="9" class="orange">
                                    <button @click="openModal">
                                    <i class="fa-solid fa-arrow-down"></i>
                                    Read Notes before you can process this
                                    <i class="fa-solid fa-arrow-down"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr :class="{
                                'orange2' :  invoice.comment && !acknowledged && !job.hasNote
                            }">
                                <td class="bg-white !text-black"><strong>#{{jobIndex+1}}</strong></td>
                                <td class="flex">
                                    <button @click="toggleImagePopover(job)">
                                        <img :src="getImageUrl(invoice.id, job.id)" alt="Job Image" class="jobImg thumbnail"/>
                                    </button>
                                    <div v-if="showImagePopover" class="popover">
                                        <div class="popover-content bg-gray-700">
                                            <img :src="getImageUrl(invoice.id, job.id)" alt="Job Image" />
                                            <button @click="toggleImagePopover(null)" class="popover-close"><icon class="fa fa-close"/></button>
                                        </div>
                                    </div>
                                    {{job.file}}</td>
                                <td>{{job.quantity}}</td>
                                <td>{{job.copies}}</td>
                                <td>{{job.height}}</td>
                                <td>{{job.width}}</td>
                                <td>{{$t(`machinePrint.${job.machinePrint}`)}}</td>
                                <td>{{$t(`machineCut.${job.machineCut}`)}}</td>
                                <td>
                                    <button :class="['bg-white', 'text-black', 'p-2', 'rounded', 'mr-2', { 'disabled' : invoice.onHold }]" @click="startJob(job)" :disabled="invoice.onHold"><strong>Start job <i class="fa-regular fa-clock"></i>0min </strong></button>
                                    <button :class="['red', 'p-2', 'rounded', { 'disabled' : invoice.onHold }]" @click="endJob(job)" :disabled="invoice.onHold"><strong>End job</strong></button>
                                </td>
                            </tr>

                        </tbody>
                    </table>
                    <CommentModal
                        v-if="showModal"
                        :comment="invoices[index].comment"
                        :closeModal="closeModal"
                        :invoice="invoices[index]"
                        :showModal="showModal"
                        @modal="updateModal"
                    />
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script>
import MainLayout from "@/Layouts/MainLayout.vue";
import Header from "@/Components/Header.vue";
import CommentModal from "@/Components/CommentModal.vue";
import axios from "axios";

export default {
    name: 'ActionPage',
    components: {
        MainLayout,
        Header,
        CommentModal
    },
    props: {
        actionId: String
    },
    data() {
        return {
            invoices: [],
            jobs: [],
            id: null,
            jobViewMode: null,
            showModal: false,
            acknowledged: false,
            showImagePopover: false,
        };
    },
    created() {
        this.fetchJobs();
    },
    methods: {
        fetchJobs() {
            const url = `/actions/${this.actionId}/jobs`;
            axios.get(url)
                .then(response => {
                    this.jobs = response.data?.jobs;
                    this.invoices = response.data?.invoices;
                    this.id = response.data?.actionId;
                })
                .catch(error => {
                    console.error('There was an error fetching the jobs:', error);
                });
        },
        viewJobs(index) {
            this.jobViewMode = this.jobViewMode === index ? null : index;
        },
        getImageUrl(invoiceId, jobId) {
            return `/storage/uploads/${this.invoices.find(i => i.id === invoiceId).jobs.find(j => j.id === jobId).file}`
        },
        openModal(index) {
            this.showModal = true;
            this.selectedInvoiceIndex = index;
        },

        closeModal() {
            this.showModal = false;
        },

        acknowledge() {
            // Should we update the invoice.comment to be null or no ? #TODO
            // this.showModal = false;
            // this.acknowledged = true;
            axios.put(`/orders/${this.invoice.id}`, {
                comment: null,
            });
        },
        updateModal(values) {
            this.showModal = values[0];
            window.location.reload()
            this.acknowledged = values[1];
        },
        async startJob(job) {
            const action = job.actions.find(a => a.name === this.actionId);
            await axios.put(`/actions/${action.id}`, {
                status: 'In progress',
            });
            console.log(job.id);
            await axios.put(`/jobs/${job.id}`, {
                status: 'In progress',
            });
            // Find the invoice that contains this job
            const invoiceWithJob = this.invoices.find(invoice => invoice.jobs.some(j => j.id === job.id));

            if (invoiceWithJob) {
                // Check if all jobs in the invoice are completed
                const allJobsCompleted = invoiceWithJob.jobs.some(j => j.status === 'In progress');
                if (allJobsCompleted || job) {
                    // Update the invoice status
                    await axios.put(`/orders/${invoiceWithJob.id}`, {
                        status: 'In progress',
                    });
                    await axios.post('/jobs/start-job', {
                        job,
                        invoice: invoiceWithJob
                    });
                }
            }
        },
        async endJob(job) {
            try {
                // Update the job status first
                const action = job.actions.find(a => a.name === this.actionId);
                await axios.put(`/actions/${action.id}`, {
                    status: 'Completed',
                });
                if (job.actions.every(a => a.status === 'Completed')) {
                    await axios.put(`/jobs/${job.id}`, {
                        status: 'Completed',
                    });
                }

                // Find the invoice that contains this job
                const invoiceWithJob = this.invoices.find(invoice => invoice.jobs.some(j => j.id === job.id));

                if (invoiceWithJob) {
                    // Check if all jobs in the invoice are completed
                    const allJobsCompleted = invoiceWithJob.jobs.every(j => j.actions.every(a => a.status === 'Completed'));
                    if (allJobsCompleted) {
                        // Update the invoice status
                        await axios.put(`/orders/${invoiceWithJob.id}`, {
                            status: 'Completed',
                        });
                        await axios.post('/jobs/end-job', {
                            job,
                            invoice: invoiceWithJob
                        });
                    }
                }
            } catch (error) {
                console.error("Error in ending job:", error);
            }
        },

        getOverallInvoiceStatus(invoice) {
            const jobStatuses = invoice.jobs.map((job) => job.status);
            if (jobStatuses.includes('In progress')) {
                return 'In Progress';
            } else if (jobStatuses.every((status) => status === 'Completed')) {
                return 'Completed';
            } else {
                return 'Not started yet';
            }
        },
        updateInvoiceStatus() {
            this.invoices.forEach(invoice => {
                const newStatus = this.getOverallInvoiceStatus(invoice); // Assuming this method needs the current invoice

                axios.put(`/orders/${invoice.id}`, { status: newStatus })
                    .then(response => {
                        // Handle successful update
                        // Optionally, you can update some status in your Vue data to reflect this
                    })
                    .catch(error => {
                        console.error("Failed to update invoice status:", error);
                    });
            });
        },
        getOverallJobStatus() {
            const jobActionStatuses = this.jobs.flatMap((job) => job.actions.map((action) => action.status.toLowerCase().trim()));
            if (jobActionStatuses.includes('in progress')) {
                return 'In Progress';
            } else if (jobActionStatuses.every((status) => status === 'completed')) {
                return 'Completed';
            } else {
                return 'Not started yet';
            }
        },
        updateJobStatus() {
            const newStatus = this.getOverallJobStatus();

            // Loop through all jobs and update their statuses
            this.jobs.forEach(job => {
                const jobId = job.id;

                // Make a PUT request to update the job status
                axios.put(`/jobs/${jobId}`, {
                    status: newStatus,
                })
                    .then(response => {
                        // Handle the response if needed
                    })
                    .catch(error => {
                        // Handle the error if the update fails
                        console.error(`Failed to update job ${jobId} status:`, error);
                    });
            });
        },
        toggleImagePopover(job) {
            this.selectedJob = job;
            this.showImagePopover = !this.showImagePopover;
        },
        navigateToOrder(orderID) {
            this.$inertia.visit(`/orders/${orderID}`);
        },
    },
    beforeMount() {
        this.updateInvoiceStatus()
        this.updateJobStatus()
    }
}
</script>
<style scoped lang="scss">
.order{
    cursor: pointer;
}
.order:hover{
    background-color: #dddddd;
}
.main{
    background-color: $light-gray;
}
.info{
    color: white;
}
.red{
    background-color: $red;
}
.red2{
    background-color: rgba(198, 40, 40, 0.5);;
}
.orange{
    background-color: orange;
}
.orange2 {
    background-color: rgba(255, 167, 38, 0.6); /* Adjust the alpha value as needed */
}
.container{
    margin-bottom:10px
}
.btns{
    position: absolute;
    padding-right: 10px;
    right: 0;
}
.bold{
    font-weight: bolder;
}
.bt {
    font-size: 30px;
    cursor: pointer;
    padding: 0;
    color: white;
}
.jobImg {
    width: 45px;
    height: 45px;
}
table{
    width: 100%;
    background-color: $light-gray;
    margin-bottom: 10px;
}
table, th, td{
    border: 1px solid $ultra-light-gray;
    color: white;
    align-items: center;
    justify-content: center;
    text-align: center;
}
td{
    padding-top: 10px;
    padding-bottom: 10px;
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
    background: #2d3748;
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
.disabled {
    opacity: 0.35;
}
</style>

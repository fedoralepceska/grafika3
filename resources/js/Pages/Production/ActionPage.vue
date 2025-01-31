<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="action" subtitle="actionInfo" icon="task.png" link="production"/>
            <div v-for="(invoice,index) in invoices" class="main" >
                <div class="container" :class="['container', 'flex', 'p-2', { 'red': invoice.onHold }]">
                    <div class="content">
                        <div class="order bg-white text-black bold p-3" style="min-width: 20vh" @click="navigateToOrder(invoice.id)">
                            <strong>{{ invoice.invoice_title }}</strong>
                        </div>
                        <div class="info">
                            <div>{{ $t('order') }}</div>
                            <div class="bold">#{{ invoice.id }}</div>
                        </div>
                        <div class="info">
                            <div>{{ $t('customer') }}</div>
                            <div class="bold">{{ invoice.client_name }}</div>
                        </div>
                        <div class="info">
                            <div>{{ $t('endDate') }}</div>
                            <div class="bold">{{ invoice?.end_date }}</div>
                        </div>
                        <div class="info">
                            <div>{{ $t('createdBy') }}</div>
                            <div class="bold">{{ invoice.user_name }}</div>
                        </div>
                        <div class="info">
                            <div>{{ $t('currentStep') }}</div>
                            <div class="bold">{{ actionId.startsWith('Machine') ? $t(`machinePrint.${actionId}`) : actionId }}</div>
                        </div>
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
                            <td :colspan="9 + (hasWaitingJobs ? 1 : 0) + (hasNextSteps ? 1 : 0)">
                                <i class="fa-solid fa-ban"></i>
                                {{ $t('thisOrderIsOnHold') }}
                                <i class="fa-solid fa-ban"></i>
                            </td>
                        </tr>
                        <tr>
                                <th>{{ $t('ln') }}</th>
                                <th>{{ $t('img') }}</th>
                                <th>{{ $t('qty') }}</th>
                                <th>{{ $t('copies') }}</th>
                                <th>{{ $t('height') }}</th>
                                <th>{{ $t('width') }}</th>
                                <th>{{ $t('print') }}</th>
                                <th>{{ $t('cut') }}</th>
                                <th>{{ $t('action') }}</th>
                                <th v-if="hasWaitingJobs">{{ $t('waitingStatus') }}</th>
                                <th v-if="hasNextSteps">{{ $t('nextStep') }}</th>
                            </tr>
                        </thead>
                        <tbody v-for="(job, jobIndex) in invoice.jobs" :key="jobIndex">
                            <tr v-if="invoice.comment && !acknowledged && !job.hasNote">
                                <td :colspan="9 + (hasWaitingJobs ? 1 : 0) + (hasNextSteps ? 1 : 0)" class="orange">
                                    <button @click="openModal">
                                    <i class="fa-solid fa-arrow-down"></i>
                                        {{ $t('readNotes') }}
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
                                            <img :src="getImageUrl(invoice.id, selectedJob.id)" alt="Job Image" />
                                            <button @click="toggleImagePopover(null)" class="popover-close"><icon class="text-white fa fa-close"/></button>
                                        </div>
                                    </div>
                                    {{job.file}}
                                </td>
                                <td>{{job.quantity}}</td>
                                <td>{{job.copies}}</td>
                                <td>{{job.height.toFixed(2)}}</td>
                                <td>{{job.width.toFixed(2)}}</td>
                                <td>{{ job.machinePrint }}</td>
                                <td>{{ job.machineCut }}</td>
                                <td>
                                    <template v-if="job.actions.find(a => a.name === actionId)?.status === 'Completed'">
                                        <div class="completed-status">
                                            <i class="fa-solid fa-check"></i> {{ $t('completed') }}
                                        </div>
                                    </template>
                                    <template v-else>
                                        <button
                                            style="min-width: 230px; max-width: 230px"
                                            :class="[
                                                'bg-white',
                                                'text-black',
                                                'p-2',
                                                'rounded',
                                                'mr-2',
                                                {
                                                    'disabled': invoice.onHold ||
                                                               jobDisabledStatus[getActionId(job).id] ||
                                                               !isPreviousActionCompleted(job)
                                                }
                                            ]"
                                            @click="startJob(job)"
                                            :disabled="invoice.onHold ||
                                                               jobDisabledStatus[getActionId(job).id] ||
                                                               !isPreviousActionCompleted(job)"
                                        >
                                            <strong>
                                                {{ $t('startJob') }}
                                                <i class="fa-regular fa-clock"></i>
                                                <span>{{ elapsedTimes[getActionId(job).id] }}</span>
                                            </strong>
                                        </button>
                                        <button
                                            v-if="canEndJob(job)"
                                            :class="['red', 'p-2', 'rounded', { 'disabled' : invoice.onHold }]"
                                            @click="endJob(job)"
                                            :disabled="invoice.onHold"
                                        >
                                            <strong>{{ $t('endJob') }}</strong>
                                        </button>
                                    </template>
                                </td>
                                <td v-if="hasWaitingJobs" class="waiting-status-cell">
                                    <template v-if="!isPreviousActionCompleted(job)">
                                        <div class="waiting-status">
                                            <a
                                                :href="`/actions/${job.actions[getActionId(job).index - 1].name}`"
                                                class="action-link"
                                                :style="{ color: getActionStatus(job, getActionId(job).index - 1).color }"
                                            >
                                                {{ job.actions[getActionId(job).index - 1].name }}
                                                <span class="status-badge"
                                                      :style="{ backgroundColor: getActionStatus(job, getActionId(job).index - 1).color }">
                                                    {{ getActionStatus(job, getActionId(job).index - 1).text }}
                                                </span>
                                            </a>
                                        </div>
                                    </template>
                                </td>
                                <td v-if="hasNextSteps" class="next-action">
                                    <template v-if="getActionId(job).index < job.actions.length - 1">
                                        <div class="next-step">
                                            <a
                                                :href="`/actions/${job.actions[getActionId(job).index + 1].name}`"
                                                class="next-action-link"
                                            >
                                                {{ job.actions[getActionId(job).index + 1].name }}
                                            </a>
                                        </div>
                                    </template>
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
import {useToast} from "vue-toastification";

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
            selectedJob: null,
            invoices: [],
            jobs: [],
            id: null,
            jobViewMode: null,
            showModal: false,
            acknowledged: false,
            showImagePopover: false,
            timers: {},
            elapsedTimes: {},
            jobDisabledStatus: {},
        };
    },
    created() {
        this.fetchJobs();
    },
    beforeMount() {
        this.updateInvoiceStatus()
        this.updateJobStatus()
        for (const [key, startTimeStr] of Object.entries(localStorage)) {
            if (key.startsWith('timer_')) {
                const jobId = key.split('_')[1];
                const startTime = parseInt(startTimeStr);
                const elapsedTime = Math.floor((new Date().getTime() - startTime) / 1000);
                this.elapsedTimes[jobId] = this.formatElapsedTime(elapsedTime);
                this.startTimer(jobId);
            }
        }
        this.jobDisabledStatus = JSON.parse(localStorage.getItem('jobDisabledStatus')) || {};

    },
    computed: {
        getActionStatus() {
            return (job, index) => {
                const action = job.actions[index];
                return {
                    color: this.getStatusColor(action?.status),
                    text: action?.status || 'Not started'
                };
            };
        },
        hasWaitingJobs() {
            let hasWaiting = false;

            for (const invoice of this.invoices) {
                for (const job of invoice.jobs) {
                    const { index } = this.getActionId(job);
                    const action = job.actions.find(a => a.name === this.actionId);
                    const previousAction = job.actions[index - 1];

                    // Check if this specific job is waiting
                    if (index > 0 &&
                        previousAction &&
                        previousAction.status !== 'Completed' &&
                        action?.status !== 'Completed') {
                        hasWaiting = true;
                        break;
                    }
                }
                if (hasWaiting) break;
            }

            return hasWaiting;
        },
        hasNextSteps() {
            return this.invoices.some(invoice =>
                invoice.jobs.some(job => {
                    const { index } = this.getActionId(job);
                    return index < job.actions.length - 1;
                })
            );
        }
    },
    methods: {
        getActionId(job) {
            const actionIndex = job?.actions?.findIndex(action => action?.name === this?.actionId);
            const action = job?.actions?.[actionIndex];
            return {
                id: action?.id,
                index: actionIndex
            };
        },

        isPreviousActionCompleted(job) {
            const { index } = this.getActionId(job);
            if (index <= 0) return true; // First action is always enabled

            const previousAction = job.actions[index - 1];
            return previousAction?.status === 'Completed';
        },

        getStatusColor(status) {
            switch(status?.toLowerCase()) {
                case 'completed':
                    return '#4CAF50';  // Green
                case 'in progress':
                    return '#2196F3';  // Blue
                default:
                    return '#FFA000';  // Amber for not started/default
            }
        },

        canEndJob(job) {
            const { id: actionId } = this.getActionId(job);
            return job.started || this.timers[actionId] !== undefined;
        },

        fetchJobs() {
            const url = `/actions/${this.actionId}/jobs`;
            axios.get(url)
                .then(response => {
                    this.jobs = response.data?.jobs?.actions?.filter(a => a?.name === this?.actionId);
                    this.invoices = response.data?.invoices;
                    this.invoices = this.invoices?.map(invoice => {
                        return {
                            ...invoice, // Keep other properties of the invoice
                            originalJobs: invoice?.jobs,
                            jobs: invoice.jobs?.filter(job =>
                                job?.actions?.some(action => action.name === this.actionId)
                            ) || [] // Filter jobs for this specific invoice
                        };
                    });

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
            if (!this.isPreviousActionCompleted(job)) {
                const toast = useToast();
                toast.error("Previous action must be completed first");
                return;
            }

            const { id: actionId } = this.getActionId(job);

            // Check if job is already started
            if (this.jobDisabledStatus[actionId]) {
                return;
            }

            this.startTimer(actionId, job);
            job.started = true;
            this.jobDisabledStatus[actionId] = true;

            // Persist jobDisabledStatus to localStorage
            localStorage.setItem('jobDisabledStatus', JSON.stringify(this.jobDisabledStatus));

            try {
                await axios.put(`/actions/${actionId}`, {
                    status: 'In progress',
                });

                await axios.put(`/jobs/${job.id}`, {
                    status: 'In progress',
                });

                // Find the invoice that contains this job
                const invoiceWithJob = this.invoices.find(invoice =>
                    invoice.jobs.some(j => j.id === job.id)
                );

                if (invoiceWithJob) {
                    // Check if all jobs in the invoice are completed
                    const allJobsCompleted = invoiceWithJob.jobs.some(j => j.status === 'In progress');
                    if (allJobsCompleted || job) {
                        // Update the invoice status
                        await axios.put(`/orders/${invoiceWithJob.id}`, {
                            status: 'In progress',
                        });
                        await axios.post('/jobs/start-job', {
                            job: job.id,
                            invoice: invoiceWithJob.id,
                            action: actionId
                        });
                    }
                }
            } catch (error) {
                console.error('Error starting job:', error);
                const toast = useToast();
                toast.error("Error starting job");
            }
        },
        startTimer(actionId, job) {
            const storedStartTimeStr = localStorage.getItem(`timer_${actionId}`);

            if (storedStartTimeStr) {
                // Resume existing timer
                const startTime = parseInt(storedStartTimeStr);
                this.timers[actionId] = setInterval(() => {
                    const elapsedTime = Math.floor((new Date().getTime() - startTime) / 1000);
                    this.elapsedTimes[actionId] = this.formatElapsedTime(elapsedTime);
                }, 1000);
            } else {
                // Create a new timer
                const startTime = new Date().getTime();
                this.timers[actionId] = setInterval(() => {
                    // Calculate elapsed time
                    const elapsedTime = Math.floor((new Date().getTime() - startTime) / 1000);
                    // Update elapsed time for this job
                    this.elapsedTimes[actionId] = this.formatElapsedTime(elapsedTime);
                }, 1000);
                localStorage.setItem(`timer_${actionId}`, startTime.toString());
            }
        },
        formatElapsedTime(elapsedTime) {
            const minutes = Math.floor(elapsedTime / 60);
            const seconds = elapsedTime % 60;
            return `${minutes}min ${seconds}sec`;
        },

        async endJob(job) {
            try {
                const { id: actionId, index: currentIndex } = this.getActionId(job);

                // Store the elapsed time before clearing
                const finalTime = this.elapsedTimes[actionId];

                // Immediately update the action status in the local data
                const action = job.actions.find(a => a.name === this.actionId);
                if (action) {
                    action.status = 'Completed';
                }

                // Clear all job status
                this.endTimer(job);
                job.started = false;
                delete this.jobDisabledStatus[actionId];
                localStorage.setItem('jobDisabledStatus', JSON.stringify(this.jobDisabledStatus));

                // Make API calls after UI update
                await axios.put(`/actions/${actionId}`, {
                    status: 'Completed',
                });

                // Find the invoice containing the job
                const invoiceWithJob = this.invoices.find(invoice =>
                    invoice.originalJobs.some(j => j.id === job.id)
                );

                // Check if all actions in the job are completed
                const allActionsCompleted = job.actions.every(a =>
                    a.id === actionId || a.status === 'Completed'
                );

                if (allActionsCompleted) {
                    // Mark the job as completed
                    await axios.put(`/jobs/${job.id}`, {
                        status: 'Completed',
                    });

                    // Check if all jobs in the invoice are completed
                    const allJobsCompleted = invoiceWithJob.originalJobs
                        .filter(j => j.id !== job.id)
                        .every(j => j.status === 'Completed');

                    if (allJobsCompleted) {
                        // Mark the invoice as completed
                        await axios.put(`/orders/${invoiceWithJob.id}`, {
                            status: 'Completed',
                        });
                    }
                }

                // Post analytics data
                await axios.post('/insert-analytics', {
                    job,
                    invoice: invoiceWithJob,
                    action: actionId,
                    time_spent: finalTime,
                });

                // Show success toast and handle redirection
                const toast = useToast();
                toast.success(`Job finished for ${finalTime}`, {
                    timeout: 2000,
                    onClose: () => {
                        // Check if there's a next action and if it exists in the job's actions
                        const hasNextAction = currentIndex < job.actions.length - 1 && job.actions[currentIndex + 1];

                        if (hasNextAction) {
                            // Redirect to next action
                            const nextAction = job.actions[currentIndex + 1];
                            this.$inertia.visit(`/actions/${nextAction.name}`);
                        } else {
                            // If no next action exists, redirect to orders page
                            this.$inertia.visit(`/orders/${invoiceWithJob.id}`);
                        }
                    }
                });

            } catch (error) {
                console.error('Error ending job:', error);
                const toast = useToast();
                toast.error("Error ending job");

                // Revert the status if the API call failed
                const action = job.actions.find(a => a.name === this.actionId);
                if (action) {
                    action.status = 'In progress';
                }
            }
        },

        endTimer(job) {
            const { id: actionId } = this.getActionId(job);
            clearInterval(this.timers[actionId]);
            delete this.timers[actionId];
            delete this.elapsedTimes[actionId];
            localStorage.removeItem(`timer_${actionId}`);
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
}
</script>
<style scoped lang="scss">
.main {
    margin-bottom: 10px;
    display: flex;
    flex-direction: column;
    flex-grow: 1;
    height: auto;
    width: 100%;
    overflow: hidden;
}


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
    background-color: rgba(255, 167, 38, 0.6);
}

.container {
    display: flex;
    align-items: center;
    width: 100%;
    min-width: 100%;
    flex-grow: 1;
    padding: 10px;
}


.content {
    display: flex;
    flex-wrap: wrap;
    gap: 60px;
    flex: 1;
}

.btns {
    display: flex;
    align-items: center;
    margin-left: auto;
    flex-shrink: 0;
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

.waiting-status-cell {
    min-width: 150px;
}

.waiting-status {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    color: #ffffff80;
}

.action-link {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
    text-decoration: underline;
    cursor: pointer;

    &:hover {
        opacity: 0.8;
    }
}

.status-badge {
    font-size: 0.8em;
    padding: 2px 8px;
    border-radius: 12px;
    color: white;
}

.next-action {
    min-width: 120px;
    color: #ffffff80;
}

.next-action-link {
    color: #ffffff;
    text-decoration: underline;
    cursor: pointer;

    &:hover {
        color: #ffffffdd;
    }
}

.next-step {
    display: flex;
    justify-content: center;
    align-items: center;
}

.completed-status {
    background-color: #4CAF50;
    color: white;
    padding: 10px;
    border-radius: 4px;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;

    i {
        font-size: 1.2em;
    }
}
</style>

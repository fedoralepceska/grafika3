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
                                <td class="image-cell">
                                    <!-- Multiple file thumbnails for new system -->
                                    <template v-if="hasMultipleFiles(job)">
                                        <div class="thumbnail-grid">
                                            <div 
                                                v-for="(file, fileIndex) in getJobFiles(job)" 
                                                :key="fileIndex" 
                                                class="thumbnail-container"
                                                @click="openPreviewModal(job, fileIndex)"
                                            >
                                                <img 
                                                    :src="getThumbnailUrl(job.id, fileIndex)" 
                                                    :alt="`File ${fileIndex + 1}`" 
                                                    class="jobImg thumbnail"
                                                    @error="handleThumbnailError($event, fileIndex)"
                                                />
                                                <div class="thumbnail-number">{{ fileIndex + 1 }}</div>
                                            </div>
                                        </div>
                                        <div class="file-count">{{ getJobFiles(job).length }} files</div>
                                    </template>
                                    
                                    <!-- Legacy single file support -->
                                    <template v-else>
                                        <button @click="toggleImagePopover(job)">
                                            <img :src="getImageUrl(invoice.id, job.id)" alt="Job Image" class="jobImg thumbnail"/>
                                        </button>
                                        <div class="file-name">{{job.file}}</div>
                                    </template>
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
                                    <template v-else-if="job.actions.find(a => a.name === actionId)?.status === 'In progress'">
                                        <div class="in-progress-status">
                                            <i class="fa-solid fa-clock"></i> {{ $t('inProgress') }}
                                            <div class="timer-display">{{ elapsedTimes[getActionId(job).id] }}</div>
                                        </div>
                                        <button
                                            v-if="canEndJob(job)"
                                            :class="['red', 'p-2', 'rounded', { 'disabled' : invoice.onHold }]"
                                            @click="endJob(job)"
                                            :disabled="invoice.onHold"
                                        >
                                            <strong>{{ $t('endJob') }}</strong>
                                        </button>
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

            <!-- Legacy image popover -->
            <div v-if="showImagePopover && selectedJob" class="popover">
                <div class="popover-content bg-gray-700">
                    <img :src="'/storage/uploads/' + selectedJob.file" alt="Job Image" />
                    <button @click="toggleImagePopover(null)" class="popover-close">
                        <i class="text-white fa fa-close"></i>
                    </button>
                </div>
            </div>

            <!-- PDF Preview Modal for multiple files -->
            <div v-if="showPreviewModal" class="preview-modal" @click="closePreviewModal">
                <div class="preview-modal-content" @click.stop>
                    <div class="preview-modal-header">
                        <h3>File {{ currentFileIndex + 1 }} of {{ getJobFiles(currentJob).length }}</h3>
                        <button @click="closePreviewModal" class="close-btn">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                    <div class="preview-modal-body">
                        <iframe 
                            v-if="currentJob && currentFileIndex !== null"
                            :src="getOriginalFileUrl(currentJob.id, currentFileIndex)" 
                            class="pdf-iframe"
                        ></iframe>
                    </div>
                    <div class="preview-modal-footer" v-if="getJobFiles(currentJob).length > 1">
                        <button 
                            @click="previousFile" 
                            :disabled="currentFileIndex === 0"
                            class="nav-btn"
                        >
                            <i class="fa fa-chevron-left"></i> Previous
                        </button>
                        <span class="file-counter">{{ currentFileIndex + 1 }} / {{ getJobFiles(currentJob).length }}</span>
                        <button 
                            @click="nextFile" 
                            :disabled="currentFileIndex === getJobFiles(currentJob).length - 1"
                            class="nav-btn"
                        >
                            Next <i class="fa fa-chevron-right"></i>
                        </button>
                    </div>
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
            showPreviewModal: false,
            currentFileIndex: null,
            currentJob: null,
        };
    },
    created() {
        this.fetchJobs();
        
        // Set up periodic sync of action statuses
        this.syncInterval = setInterval(() => {
            // Get unique action IDs from all jobs
            const actionIds = new Set();
            this.invoices.forEach(invoice => {
                invoice.jobs.forEach(job => {
                    const action = job.actions.find(a => a.name === this.actionId);
                    if (action && action.id) {
                        actionIds.add(action.id);
                    }
                });
            });
            
            // Sync each action status
            actionIds.forEach(actionId => {
                this.syncActionStatus(actionId);
            });
        }, 5000); // Sync every 5 seconds
    },
    beforeMount() {
        // Load job disabled status from localStorage
        this.jobDisabledStatus = JSON.parse(localStorage.getItem('jobDisabledStatus')) || {};
        
        // Note: Timer restoration is now handled in restoreTimerState() after fetching jobs
        // This ensures we sync with the actual server state rather than just localStorage
    },
    
    beforeUnmount() {
        // Clean up intervals
        if (this.syncInterval) {
            clearInterval(this.syncInterval);
        }
        
        // Clean up timers
        Object.keys(this.timers).forEach(actionId => {
            clearInterval(this.timers[actionId]);
        });
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
            console.log('Fetching jobs from:', url);
            axios.get(url)
                .then(response => {
                    console.log('Jobs response:', response.data);
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
                    
                    console.log('Processed invoices:', this.invoices);
                    
                    // After loading jobs, restore timer state for actions that are in progress
                    this.restoreTimerState();
                    
                    // Debug the current state
                    this.debugCurrentState();
                })
                .catch(error => {
                    console.error('There was an error fetching the jobs:', error);
                });
        },
        viewJobs(index) {
            this.jobViewMode = this.jobViewMode === index ? null : index;
        },
        getImageUrl(invoiceId, jobId) {
            const job = this.invoices.find(i => i.id === invoiceId)?.jobs.find(j => j.id === jobId);
            return `/storage/uploads/${job?.file}`
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

            // Check if job is already started locally
            if (this.jobDisabledStatus[actionId]) {
                // If locally disabled, check server status to sync
                await this.syncActionStatus(actionId);
                return;
            }

            try {
                console.log('Starting job with data:', {
                    jobId: job.id,
                    actionId: actionId,
                    invoiceId: this.invoices.find(invoice => 
                        invoice.jobs.some(j => j.id === job.id)
                    )?.id
                });

                // Start the action on the server
                const response = await axios.post('/jobs/start-job', {
                    job: job.id,
                    invoice: this.invoices.find(invoice => 
                        invoice.jobs.some(j => j.id === job.id)
                    )?.id,
                    action: actionId
                });

                console.log('Start job response:', response);
                console.log('Response data:', response.data);
                console.log('Response status:', response.status);

                if (response.data.success) {
                    console.log('Job start successful, updating UI...');
                    
                    // Start timer with server time
                    this.startTimer(actionId, response.data.started_at);
                    job.started = true;
                    this.jobDisabledStatus[actionId] = true;

                    // Update local action status
                    const action = job.actions.find(a => a.id === actionId);
                    if (action) {
                        action.status = 'In progress';
                        action.started_at = response.data.started_at;
                        console.log('Updated action status:', action);
                    }

                    // Persist jobDisabledStatus to localStorage
                    localStorage.setItem('jobDisabledStatus', JSON.stringify(this.jobDisabledStatus));
                    
                    // Show success toast
                    const toast = useToast();
                    toast.success('Job started successfully');
                    console.log('Success toast shown');
                } else {
                    console.log('Job start failed - success is false');
                    // Handle case where success is false
                    const toast = useToast();
                    toast.error(response.data.error || "Failed to start job");
                }
            } catch (error) {
                console.error('=== ERROR STARTING JOB ===');
                console.error('Error object:', error);
                console.error('Error message:', error.message);
                console.error('Error response:', error.response);
                console.error('Error response data:', error.response?.data);
                console.error('Error response status:', error.response?.status);
                console.error('Error response headers:', error.response?.headers);
                console.error('Error stack:', error.stack);
                console.error('=== END ERROR ===');
                
                const toast = useToast();
                toast.error(error.response?.data?.error || "Error starting job");
            }
        },
        startTimer(actionId, startTime = null) {
            let startTimeMs;
            
            if (startTime) {
                // Use server-provided start time - convert to local timezone for accurate calculation
                const serverDate = new Date(startTime);
                startTimeMs = serverDate.getTime();
                localStorage.setItem(`timer_${actionId}`, startTimeMs.toString());
                
                // Calculate initial elapsed time immediately
                const initialElapsedTime = Math.floor((new Date().getTime() - startTimeMs) / 1000);
                this.elapsedTimes[actionId] = this.formatElapsedTime(initialElapsedTime);
                console.log(`Timer started for action ${actionId}`);
                console.log(`Server start time: ${startTime}`);
                console.log(`Local start time: ${serverDate.toLocaleString()}`);
                console.log(`Initial elapsed: ${this.elapsedTimes[actionId]}`);
            } else {
                // Resume existing timer from localStorage
                const storedStartTimeStr = localStorage.getItem(`timer_${actionId}`);
                if (storedStartTimeStr) {
                    startTimeMs = parseInt(storedStartTimeStr);
                    
                    // Calculate initial elapsed time immediately
                    const initialElapsedTime = Math.floor((new Date().getTime() - startTimeMs) / 1000);
                    this.elapsedTimes[actionId] = this.formatElapsedTime(initialElapsedTime);
                    console.log(`Timer resumed for action ${actionId} with stored start time, initial elapsed: ${this.elapsedTimes[actionId]}`);
                } else {
                    // Fallback to current time
                    startTimeMs = new Date().getTime();
                    localStorage.setItem(`timer_${actionId}`, startTimeMs.toString());
                    this.elapsedTimes[actionId] = this.formatElapsedTime(0);
                    console.log(`Timer started for action ${actionId} with current time, initial elapsed: ${this.elapsedTimes[actionId]}`);
                }
            }

            this.timers[actionId] = setInterval(() => {
                const elapsedTime = Math.floor((new Date().getTime() - startTimeMs) / 1000);
                this.elapsedTimes[actionId] = this.formatElapsedTime(elapsedTime);
            }, 1000);
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

                // End the action on the server
                const response = await axios.post('/jobs/end-job', {
                    job: job.id,
                    invoice: this.invoices.find(invoice => 
                        invoice.jobs.some(j => j.id === job.id)
                    )?.id,
                    action: actionId,
                    time_spent: finalTime
                });

                if (response.data.success) {
                    // Update local action status
                    const action = job.actions.find(a => a.id === actionId);
                    if (action) {
                        action.status = 'Completed';
                        action.ended_at = response.data.ended_at;
                    }

                    // Clear timer and status
                    this.endTimer(actionId);
                    job.started = false;
                    delete this.jobDisabledStatus[actionId];
                    localStorage.setItem('jobDisabledStatus', JSON.stringify(this.jobDisabledStatus));

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
                                const invoiceWithJob = this.invoices.find(invoice =>
                                    invoice.jobs.some(j => j.id === job.id)
                                );
                                this.$inertia.visit(`/orders/${invoiceWithJob.id}`);
                            }
                        }
                    });
                }
            } catch (error) {
                console.error('Error ending job:', error);
                const toast = useToast();
                toast.error(error.response?.data?.error || "Error ending job");
            }
        },

        endTimer(actionId) {
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
        toggleImagePopover(job) {
            this.selectedJob = job;
            this.showImagePopover = !this.showImagePopover;
        },
        navigateToOrder(orderID) {
            this.$inertia.visit(`/orders/${orderID}`);
        },
        hasMultipleFiles(job) {
            // Check if job has originalFile array (new system) or if it's legacy single file
            return Array.isArray(job.originalFile) && job.originalFile.length > 0;
        },
        getJobFiles(job) {
            // Return originalFile array for new system, or create array from legacy file
            if (Array.isArray(job.originalFile)) {
                return job.originalFile;
            }
            return job.file ? [job.file] : [];
        },
        getThumbnailUrl(jobId, fileIndex) {
            return `/jobs/${jobId}/view-thumbnail/${fileIndex}`;
        },
        handleThumbnailError(event, fileIndex) {
            // Show PDF icon as fallback for thumbnail errors
            event.target.src = '/images/pdf.png';
            console.log(`Thumbnail loading failed for file index ${fileIndex}`);
        },
        openPreviewModal(job, fileIndex) {
            this.currentFileIndex = fileIndex;
            this.currentJob = job;
            this.showPreviewModal = true;
        },
        closePreviewModal() {
            this.showPreviewModal = false;
            this.currentFileIndex = null;
            this.currentJob = null;
        },
        previousFile() {
            if (this.currentFileIndex > 0) {
                this.currentFileIndex--;
            }
        },
        nextFile() {
            if (this.currentFileIndex < this.getJobFiles(this.currentJob).length - 1) {
                this.currentFileIndex++;
            }
        },
        getOriginalFileUrl(jobId, fileIndex) {
            return `/jobs/${jobId}/view-original-file/${fileIndex}`;
        },
        
        debugCurrentState() {
            console.log('=== DEBUG CURRENT STATE ===');
            console.log('Timers:', this.timers);
            console.log('Elapsed times:', this.elapsedTimes);
            console.log('Job disabled status:', this.jobDisabledStatus);
            console.log('Invoices:', this.invoices);
            this.invoices.forEach((invoice, invoiceIndex) => {
                console.log(`Invoice ${invoiceIndex}:`, invoice.id);
                invoice.jobs.forEach((job, jobIndex) => {
                    const action = job.actions.find(a => a.name === this.actionId);
                    console.log(`  Job ${jobIndex}:`, {
                        id: job.id,
                        actionId: action?.id,
                        actionStatus: action?.status,
                        started: job.started,
                        hasTimer: !!this.timers[action?.id],
                        isDisabled: this.jobDisabledStatus[action?.id],
                        started_at: action?.started_at,
                        started_at_type: typeof action?.started_at
                    });
                });
            });
            console.log('=== END DEBUG ===');
        },
        
        restoreTimerState() {
            console.log('Restoring timer state...');
            // Check all jobs for actions that are in progress and restore their timers
            this.invoices.forEach(invoice => {
                invoice.jobs.forEach(job => {
                    const action = job.actions.find(a => a.name === this.actionId);
                    if (action && action.status === 'In progress' && action.started_at) {
                        const actionId = action.id;
                        console.log(`Found in-progress action: ${actionId} for job: ${job.id}`);
                        console.log(`Action started_at: ${action.started_at}`);
                        console.log(`Action started_at type: ${typeof action.started_at}`);
                        console.log(`Current time: ${new Date().toISOString()}`);
                        
                        // Calculate expected elapsed time
                        const serverDate = new Date(action.started_at);
                        const startTimeMs = serverDate.getTime();
                        const currentTimeMs = new Date().getTime();
                        const expectedElapsedSeconds = Math.floor((currentTimeMs - startTimeMs) / 1000);
                        console.log(`Server start time: ${action.started_at}`);
                        console.log(`Local start time: ${serverDate.toLocaleString()}`);
                        console.log(`Current local time: ${new Date().toLocaleString()}`);
                        console.log(`Start time in ms: ${startTimeMs}`);
                        console.log(`Current time in ms: ${currentTimeMs}`);
                        console.log(`Expected elapsed time: ${expectedElapsedSeconds} seconds (${Math.floor(expectedElapsedSeconds / 60)}min ${expectedElapsedSeconds % 60}sec)`);
                        
                        // If timer doesn't exist for this action, start it
                        if (!this.timers[actionId]) {
                            console.log(`Starting timer for action: ${actionId}`);
                            this.startTimer(actionId, action.started_at);
                            this.jobDisabledStatus[actionId] = true;
                            job.started = true;
                        } else {
                            console.log(`Timer already exists for action: ${actionId}`);
                        }
                    }
                });
            });
            
            // Persist the restored job disabled status
            localStorage.setItem('jobDisabledStatus', JSON.stringify(this.jobDisabledStatus));
            console.log('Timer state restoration completed');
        },
        
        async syncActionStatus(actionId) {
            try {
                console.log(`Syncing action status for actionId: ${actionId}`);
                const response = await axios.get(`/action/${actionId}/status`);
                if (response.data.success) {
                    const actionData = response.data.action;
                    console.log(`Action status data:`, actionData);
                    
                    // Update all jobs that have this action
                    this.invoices.forEach(invoice => {
                        invoice.jobs.forEach(job => {
                            const action = job.actions.find(a => a.id === actionId);
                            if (action) {
                                const oldStatus = action.status;
                                action.status = actionData.status;
                                action.started_at = actionData.started_at;
                                action.ended_at = actionData.ended_at;
                                
                                console.log(`Updated action ${actionId} status from ${oldStatus} to ${actionData.status}`);
                                
                                // Update timer if action is in progress
                                if (actionData.is_in_progress && !this.timers[actionId]) {
                                    console.log(`Starting timer for in-progress action: ${actionId}`);
                                    this.startTimer(actionId, actionData.started_at);
                                    this.jobDisabledStatus[actionId] = true;
                                    job.started = true;
                                }
                                
                                // Clear timer if action is completed
                                if (actionData.is_completed && this.timers[actionId]) {
                                    console.log(`Clearing timer for completed action: ${actionId}`);
                                    this.endTimer(actionId);
                                    delete this.jobDisabledStatus[actionId];
                                    job.started = false;
                                }
                            }
                        });
                    });
                    
                    // Persist job disabled status changes
                    localStorage.setItem('jobDisabledStatus', JSON.stringify(this.jobDisabledStatus));
                }
            } catch (error) {
                console.error('Error syncing action status:', error);
            }
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

.in-progress-status {
    background-color: #2196F3;
    color: white;
    padding: 10px;
    border-radius: 4px;
    font-weight: bold;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 5px;
    margin-bottom: 10px;

    i {
        font-size: 1.2em;
    }

    .timer-display {
        font-size: 0.9em;
        opacity: 0.9;
    }
}

.image-cell {
    min-width: 120px;
    padding: 10px;
}

.thumbnail-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    justify-content: center;
    margin-bottom: 5px;
}

.thumbnail-container {
    position: relative;
    width: 45px;
    height: 45px;
    overflow: hidden;
    border-radius: 4px;
    cursor: pointer;
    border: 2px solid transparent;
    transition: border-color 0.2s;

    &:hover {
        border-color: #fff;
    }

    img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .thumbnail-number {
        position: absolute;
        top: 2px;
        right: 2px;
        background-color: rgba(0, 0, 0, 0.8);
        color: white;
        padding: 1px 4px;
        border-radius: 2px;
        font-size: 10px;
        font-weight: bold;
    }
}

.file-count {
    font-size: 11px;
    text-align: center;
    color: #ffffff80;
}

.file-name {
    font-size: 11px;
    text-align: center;
    color: #ffffff80;
    margin-top: 5px;
    word-break: break-all;
}

.preview-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    background-color: rgba(0, 0, 0, 0.8);
    z-index: 1000;

    .preview-modal-content {
        background-color: #2d3748;
        padding: 20px;
        border-radius: 8px;
        width: 90%;
        max-width: 900px;
        max-height: 90vh;
        position: relative;
        color: white;

        .preview-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            border-bottom: 1px solid #4a5568;
            padding-bottom: 10px;

            h3 {
                margin: 0;
                color: white;
            }

            .close-btn {
                background: none;
                border: none;
                color: white;
                font-size: 1.5em;
                cursor: pointer;
                padding: 5px;
                border-radius: 4px;
                
                &:hover {
                    background-color: rgba(255, 255, 255, 0.1);
                }
            }
        }

        .preview-modal-body {
            height: 60vh;
            position: relative;

            .pdf-iframe {
                width: 100%;
                height: 100%;
                border: none;
                border-radius: 4px;
            }
        }

        .preview-modal-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 2px;
            
            .nav-btn {
                background-color: #4a5568;
                border: none;
                color: white;
                font-size: 1em;
                cursor: pointer;
                padding: 8px 16px;
                border-radius: 4px;
                transition: background-color 0.2s;

                &:hover:not(:disabled) {
                    background-color: #2d3748;
                }

                &:disabled {
                    opacity: 0.5;
                    cursor: not-allowed;
                }
            }

            .file-counter {
                margin: 0 10px;
                font-weight: bold;
                color: white;
            }
        }
    }
}
</style>


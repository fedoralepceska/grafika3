<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="action" subtitle="actionInfo" icon="task.png" link="production"/>
            
            <!-- Pre-search loading indicator -->
            <div v-if="isPreSearching" class="pre-search-loading">
                <div class="loading-content">
                    <i class="fa fa-search fa-spin"></i>
                    <span>Searching for your job<span class="animated-dots">...</span></span>
                </div>
            </div>
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <input
                        type="text"
                        v-model="search"
                        @input="onSearchInput"
                        placeholder="Search orders, clients, users, #id"
                        class="bg-white text-black px-3 py-2 rounded"
                        style="min-width: 280px"
                    />
                </div>
                <div class="text-white">
                    <span>Page {{ pagination.current_page }} / {{ pagination.last_page }}</span>
                </div>
            </div>
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
                        <tbody v-for="(job, jobIndex) in invoice.jobs" :key="jobIndex" :data-job-id="job.id">
                            <tr v-if="invoice.comment && !acknowledged && hasNoteForCurrentAction(job)" :data-job-id="job.id">
                                <td :colspan="9 + (hasWaitingJobs ? 1 : 0) + (hasNextSteps ? 1 : 0)" class="orange">
                                    <button @click="openModal">
                                    <i class="fa-solid fa-arrow-down"></i>
                                        {{ $t('readNotes') }}
                                    <i class="fa-solid fa-arrow-down"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr :class="{
                                'orange2' :  invoice.comment && !acknowledged && hasNoteForCurrentAction(job)
                            }" :data-job-id="job.id">
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
                                        <!-- Admin bypass for stuck jobs (in progress but missing started_at) -->
                                        <div v-if="isAdmin && !job.actions.find(a => a.name === actionId)?.started_at" class="mt-2">
                                            <div class="text-yellow-400 text-xs mb-2 flex items-center justify-center gap-2">
                                                <i class="fa-solid fa-triangle-exclamation"></i>
                                                <span>Job is stuck (missing start time)</span>
                                            </div>
                                            <button
                                                class="bg-red-600 text-white p-2 rounded flex items-center gap-2 mx-auto"
                                                @click="adminEndJob(job, invoice.id)"
                                                :disabled="invoice.onHold"
                                            >
                                                <i class="fa-solid fa-user-shield"></i>
                                                <strong>Admin: End Stuck Job</strong>
                                            </button>
                                        </div>
                                        <button
                                            v-if="canEndJob(job)"
                                            :class="['red', 'p-2', 'rounded', { 'disabled' : invoice.onHold || !canCurrentUserEndJob(job) }]"
                                            @click="endJob(job)"
                                            :disabled="invoice.onHold || !canCurrentUserEndJob(job)"
                                        >
                                            <strong>{{ $t('endJob') }}</strong>
                                        </button>
                                        <div v-if="canEndJob(job) && !canCurrentUserEndJob(job)" class="text-xs text-gray-400 mt-1">
                                            Started by another user
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

            <div class="flex items-center justify-between mt-4">
                <div class="text-white">Total: {{ pagination.total }}</div>
                <div class="pagination">
                    <button class="page-btn" @click="prevPage" :disabled="pagination.current_page <= 1">Prev</button>
                    <button
                        v-for="p in pageButtons"
                        :key="`p-${p}`"
                        class="page-btn"
                        :class="{ active: p === pagination.current_page, dots: p === '...' }"
                        @click="goToPage(p)"
                        :disabled="p === '...' || p === pagination.current_page"
                    >
                        {{ p }}
                    </button>
                    <button class="page-btn" @click="nextPage" :disabled="pagination.current_page >= pagination.last_page">Next</button>
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
import useRoleCheck from '@/Composables/useRoleCheck';

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
            currentUserId: null,
            search: '',
            searchDebounce: null,
            pagination: {
                current_page: 1,
                last_page: 1,
                per_page: 10,
                total: 0,
            },
            targetJobInfo: null, // Store target job info from URL params
            isSearchingForJob: false, // Prevent infinite loops when searching for jobs
            isPreSearching: false, // Show loading state during pre-search
            isAdmin: false,
            _roleCheck: null,
        };
    },
    async created() {
        // Initialize role check and keep isAdmin reactive
        const role = useRoleCheck();
        this._roleCheck = role;
        this.$watch(() => role.isAdmin.value, (val) => {
            this.isAdmin = !!val;
        }, { immediate: true });
        // Check for URL parameters first and find the correct page BEFORE loading jobs
        const shouldPreSearch = await this.checkUrlParams();
        
        if (shouldPreSearch) {
            // Don't fetch jobs yet - we'll do it after finding the correct page
            console.log('Pre-searching for job, will fetch jobs after finding correct page');
        } else {
            // No URL params, fetch jobs normally
            this.fetchJobs();
        }
    },
    beforeMount() {
        // Load job disabled status from localStorage
        this.jobDisabledStatus = JSON.parse(localStorage.getItem('jobDisabledStatus')) || {};
        
        // Note: Timer restoration is now handled in restoreTimerState() after fetching jobs
        // This ensures we sync with the actual server state rather than just localStorage
    },
    
    beforeUnmount() {
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
        },
        pageButtons() {
            const buttons = [];
            const current = this.pagination.current_page || 1;
            const last = this.pagination.last_page || 1;
            const delta = 1; // pages around current
            let start = Math.max(1, current - delta);
            let end = Math.min(last, current + delta);
            if (start > 1) {
                buttons.push(1);
                if (start > 2) buttons.push('...');
            }
            for (let p = start; p <= end; p++) buttons.push(p);
            if (end < last) {
                if (end < last - 1) buttons.push('...');
                buttons.push(last);
            }
            return buttons;
        }
    },
    methods: {
        async adminEndJob(job, invoiceIdExplicit = null) {
            try {
                const { id: actionId } = this.getActionId(job);
                const invoiceId = invoiceIdExplicit ?? this.invoices.find(invoice => invoice.jobs.some(j => j.id === job.id))?.id;
                const nowIso = new Date().toISOString();

                const response = await axios.post('/jobs/admin-end-job', {
                    job: job.id,
                    invoice: invoiceId,
                    action: actionId,
                    action_name: this.actionId,
                    started_at: nowIso,
                    ended_at: nowIso,
                });

                if (response.data?.success) {
                    const action = job.actions.find(a => a.id === actionId);
                    if (action) {
                        action.status = 'Completed';
                        action.started_at = nowIso;
                        action.ended_at = nowIso;
                    }
                    this.endTimer(actionId);
                    job.started = false;
                    delete this.jobDisabledStatus[actionId];
                    localStorage.setItem('jobDisabledStatus', JSON.stringify(this.jobDisabledStatus));

                    const toast = useToast();
                    toast.success('Admin ended stuck job successfully');
                } else {
                    const toast = useToast();
                    toast.error(response.data?.error || 'Failed to end job as admin');
                }
            } catch (error) {
                console.error('Error in adminEndJob:', error);
                const toast = useToast();
                toast.error(error.response?.data?.error || 'Error ending job as admin');
            }
        },
        hasNoteForCurrentAction(job) {
            const action = job?.actions?.find(a => a?.name === this.actionId);
            return action && (action.hasNote === 1 || action.hasNote === true);
        },
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

        canCurrentUserEndJob(job) {
            const { id: actionId } = this.getActionId(job);
            const action = job.actions.find(a => a.id === actionId);
            
            // Check if the current user started this action
            if (action && action.started_by && this.currentUserId) {
                return action.started_by === this.currentUserId;
            }
            
            // If no started_by info or no current user ID, allow the action (server will validate)
            return true;
        },



        fetchJobs() {
            const params = new URLSearchParams();
            params.set('page', this.pagination.current_page?.toString() || '1');
            params.set('per_page', this.pagination.per_page?.toString() || '10');
            if (this.search) params.set('search', this.search);
            const url = `/actions/${this.actionId}/jobs?${params.toString()}`;
            console.log('Fetching jobs from:', url);
            axios.get(url)
                .then(response => {
                    console.log('Jobs response:', response.data);
                    this.invoices = response.data?.invoices || [];
                    this.pagination = response.data?.pagination || this.pagination;

                    this.id = response.data?.actionId;
                    this.currentUserId = response.data?.currentUserId;
                    
                    console.log('Processed invoices:', this.invoices);
                    
                    // After loading jobs, restore timer state for actions that are in progress
                    this.restoreTimerState();
                    
                    // Debug the current state
                    this.debugCurrentState();
                    
                    // Try to open target job if URL params were provided
                    // Only open if we're not in the middle of pre-searching
                    if (!this.isSearchingForJob) {
                        this.openTargetJob();
                    }
                })
                .catch(error => {
                    console.error('There was an error fetching the jobs:', error);
                });
        },
        onSearchInput() {
            if (this.searchDebounce) clearTimeout(this.searchDebounce);
            this.searchDebounce = setTimeout(() => {
                this.pagination.current_page = 1;
                this.fetchJobs();
            }, 300);
        },
        nextPage() {
            if (this.pagination.current_page < this.pagination.last_page) {
                this.pagination.current_page += 1;
                this.fetchJobs();
            }
        },
        prevPage() {
            if (this.pagination.current_page > 1) {
                this.pagination.current_page -= 1;
                this.fetchJobs();
            }
        },
        goToPage(p) {
            if (p === '...') return;
            const last = this.pagination.last_page || 1;
            if (typeof p === 'number' && p >= 1 && p <= last && p !== this.pagination.current_page) {
                this.pagination.current_page = p;
                this.fetchJobs();
            }
        },
        async checkUrlParams() {
            // Check if URL has job and invoice parameters
            const urlParams = new URLSearchParams(window.location.search);
            const targetJobId = urlParams.get('job');
            const targetInvoiceId = urlParams.get('invoice');
            
            console.log('Checking URL params:', { targetJobId, targetInvoiceId });
            
            if (targetJobId && targetInvoiceId) {
                // Store the target job info to open after jobs are loaded
                this.targetJobInfo = {
                    jobId: parseInt(targetJobId),
                    invoiceId: parseInt(targetInvoiceId)
                };
                console.log('Target job info set:', this.targetJobInfo);
                
                // Pre-search for the job to find the correct page BEFORE loading any content
                const foundPage = await this.preSearchForJob(parseInt(targetJobId), parseInt(targetInvoiceId));
                
                if (foundPage) {
                    console.log('Pre-search successful: Job found on page', foundPage);
                    // Set the correct page and fetch jobs
                    this.pagination.current_page = foundPage;
                    await this.fetchJobs();
                    return false; // Don't fetch jobs again
                } else {
                    console.log('Pre-search failed: Will search after loading jobs');
                    // Fallback to normal search after jobs are loaded
                    return true; // Fetch jobs normally
                }
            }
            
            return false; // No URL params, fetch jobs normally
        },
        async preSearchForJob(jobId, invoiceId) {
            try {
                this.isPreSearching = true;
                console.log('preSearchForJob: Pre-searching for job', jobId, 'in invoice', invoiceId);
                
                // Strategy 1: Try to find the page directly using a smart search
                let foundPage = await this.smartPageSearch(invoiceId, jobId);
                
                if (!foundPage) {
                    // Strategy 2: Fallback to systematic page search
                    console.log('preSearchForJob: Smart search failed, trying systematic search...');
                    foundPage = await this.systematicPageSearch(invoiceId, jobId);
                }
                
                if (foundPage) {
                    console.log('preSearchForJob: Found job on page', foundPage);
                    return foundPage;
                } else {
                    console.log('preSearchForJob: Could not find job on any page');
                    return null;
                }
                
            } catch (error) {
                console.error('preSearchForJob: Error:', error);
                return null;
            } finally {
                this.isPreSearching = false;
            }
        },
        async smartPageSearch(invoiceId, jobId) {
            try {
                console.log('smartPageSearch: Using smart search strategy');
                
                // Try to find the page using search API first (most efficient)
                const searchParams = new URLSearchParams();
                searchParams.set('search', invoiceId.toString());
                searchParams.set('per_page', '1000');
                
                const response = await axios.get(`/actions/${this.actionId}/jobs?${searchParams.toString()}`);
                const searchResults = response.data?.invoices || [];
                
                // Check if our invoice is in the search results
                const targetInvoice = searchResults.find(invoice => invoice.id === invoiceId);
                
                if (targetInvoice) {
                    console.log('smartPageSearch: Found invoice in search results, calculating page...');
                    // Calculate which page this invoice would be on based on its position
                    return await this.calculateInvoicePage(invoiceId);
                }
                
                return null;
            } catch (error) {
                console.error('smartPageSearch: Error:', error);
                return null;
            }
        },
        async systematicPageSearch(invoiceId, jobId) {
            try {
                console.log('systematicPageSearch: Searching through pages systematically');
                
                // Get total pages from a quick API call
                const response = await axios.get(`/actions/${this.actionId}/jobs?page=1&per_page=1`);
                const totalPages = response.data?.pagination?.last_page || 1;
                console.log('systematicPageSearch: Total pages to search:', totalPages);
                
                // Search through pages efficiently (limit to prevent excessive API calls)
                const maxPagesToSearch = Math.min(totalPages, 15);
                
                for (let page = 1; page <= maxPagesToSearch; page++) {
                    console.log(`systematicPageSearch: Checking page ${page}/${maxPagesToSearch}`);
                    
                    const params = new URLSearchParams();
                    params.set('page', page.toString());
                    params.set('per_page', '10'); // Use smaller per_page for faster responses
                    
                    const pageResponse = await axios.get(`/actions/${this.actionId}/jobs?${params.toString()}`);
                    const pageInvoices = pageResponse.data?.invoices || [];
                    
                    // Check if our invoice is on this page
                    const invoiceIndex = pageInvoices.findIndex(invoice => invoice.id === invoiceId);
                    if (invoiceIndex !== -1) {
                        console.log(`systematicPageSearch: Found invoice on page ${page}`);
                        return page;
                    }
                }
                
                return null;
            } catch (error) {
                console.error('systematicPageSearch: Error:', error);
                return null;
            }
        },
        async calculateInvoicePage(invoiceId) {
            try {
                console.log('calculateInvoicePage: Calculating page for invoice', invoiceId);
                
                // Get the first page to understand the structure
                const response = await axios.get(`/actions/${this.actionId}/jobs?page=1&per_page=10`);
                const firstPageInvoices = response.data?.invoices || [];
                const perPage = response.data?.pagination?.per_page || 10;
                
                // If invoice ID is less than the first invoice on page 1, it's on page 1
                if (firstPageInvoices.length > 0 && invoiceId <= firstPageInvoices[0].id) {
                    return 1;
                }
                
                // Estimate the page based on invoice ID and per_page
                // This is a rough calculation but should work in most cases
                const estimatedPage = Math.ceil(invoiceId / perPage);
                console.log('calculateInvoicePage: Estimated page:', estimatedPage);
                
                // Verify the estimated page
                const verifyResponse = await axios.get(`/actions/${this.actionId}/jobs?page=${estimatedPage}&per_page=${perPage}`);
                const verifyInvoices = verifyResponse.data?.invoices || [];
                
                const invoiceIndex = verifyInvoices.findIndex(invoice => invoice.id === invoiceId);
                if (invoiceIndex !== -1) {
                    console.log(`calculateInvoicePage: Verified invoice on page ${estimatedPage}`);
                    return estimatedPage;
                }
                
                // If estimation failed, try nearby pages
                for (let offset = -1; offset <= 1; offset++) {
                    const nearbyPage = estimatedPage + offset;
                    if (nearbyPage > 0) {
                        const nearbyResponse = await axios.get(`/actions/${this.actionId}/jobs?page=${nearbyPage}&per_page=${perPage}`);
                        const nearbyInvoices = nearbyResponse.data?.invoices || [];
                        
                        const nearbyIndex = nearbyInvoices.findIndex(invoice => invoice.id === invoiceId);
                        if (nearbyIndex !== -1) {
                            console.log(`calculateInvoicePage: Found invoice on nearby page ${nearbyPage}`);
                            return nearbyPage;
                        }
                    }
                }
                
                return null;
            } catch (error) {
                console.error('calculateInvoicePage: Error:', error);
                return null;
            }
        },
        async testSearchFunctionality(jobId, invoiceId) {
            try {
                console.log('=== TESTING SEARCH FUNCTIONALITY ===');
                console.log('Testing search for job:', jobId, 'invoice:', invoiceId);
                
                // Test 1: Direct search by invoice ID
                const searchParams1 = new URLSearchParams();
                searchParams1.set('search', invoiceId.toString());
                searchParams1.set('per_page', '1000');
                
                console.log('Testing search URL 1:', `/actions/${this.actionId}/jobs?${searchParams1.toString()}`);
                const response1 = await axios.get(`/actions/${this.actionId}/jobs?${searchParams1.toString()}`);
                console.log('Search response 1:', response1.data);
                
                // Test 2: Search by job ID
                const searchParams2 = new URLSearchParams();
                searchParams2.set('search', jobId.toString());
                searchParams2.set('per_page', '1000');
                
                console.log('Testing search URL 2:', `/actions/${this.actionId}/jobs?${searchParams2.toString()}`);
                const response2 = await axios.get(`/actions/${this.actionId}/jobs?${searchParams2.toString()}`);
                console.log('Search response 2:', response2.data);
                
                // Test 3: Check current pagination info
                console.log('Current pagination:', this.pagination);
                
                console.log('=== END TESTING ===');
                
            } catch (error) {
                console.error('Error testing search functionality:', error);
            }
        },
        openTargetJob() {
            if (!this.targetJobInfo || !this.invoices.length) {
                console.log('openTargetJob: No target job info or no invoices loaded yet');
                return;
            }
            
            // Prevent infinite loops when searching for jobs
            if (this.isSearchingForJob) {
                console.log('openTargetJob: Already searching for job, skipping...');
                return;
            }
            
            const { jobId, invoiceId } = this.targetJobInfo;
            console.log('openTargetJob: Looking for job', jobId, 'in invoice', invoiceId);
            
            // Find the invoice index that contains the target job
            const invoiceIndex = this.invoices.findIndex(invoice => 
                invoice.id === invoiceId && 
                invoice.jobs.some(job => job.id === jobId)
            );
            
            console.log('openTargetJob: Found invoice at index', invoiceIndex);
            
            if (invoiceIndex !== -1) {
                // Job found on current page - open it
                this.openJobOnCurrentPage(invoiceIndex, jobId);
            } else {
                // Job not found on current page - this shouldn't happen with pre-search
                // but fallback to search if needed
                console.log('openTargetJob: Job not found on current page, this shouldn\'t happen with pre-search');
                console.log('openTargetJob: Current invoices:', this.invoices.map(inv => inv.id));
                console.log('openTargetJob: Target invoice:', invoiceId);
                
                // Try to search as fallback
                this.searchForJobAcrossPages(jobId, invoiceId);
            }
        },
        openJobOnCurrentPage(invoiceIndex, jobId) {
            // Open the job view for this invoice
            this.jobViewMode = invoiceIndex;
            console.log('openJobOnCurrentPage: Set jobViewMode to', invoiceIndex);
            
            // Clear the target job info
            this.targetJobInfo = null;
            
            // Clear URL parameters to clean up the URL
            this.clearUrlParams();
            
            // Add focus styling and scroll to the specific job row after a short delay
            setTimeout(() => {
                this.focusJobWithAnimation(jobId);
            }, 100);
        },
        async searchForJobAcrossPages(jobId, invoiceId) {
            try {
                this.isSearchingForJob = true;
                console.log('searchForJobAcrossPages: Searching for job', jobId, 'in invoice', invoiceId);
                
                // Try multiple search strategies to find the job
                let foundPage = null;
                
                // Strategy 1: Search by invoice ID (more reliable)
                console.log('Strategy 1: Searching by invoice ID...');
                foundPage = await this.searchByInvoiceId(invoiceId);
                
                if (!foundPage) {
                    // Strategy 2: Search by job ID
                    console.log('Strategy 2: Searching by job ID...');
                    foundPage = await this.searchByJobId(jobId, invoiceId);
                }
                
                if (!foundPage) {
                    // Strategy 3: Search through pages systematically
                    console.log('Strategy 3: Searching through pages systematically...');
                    foundPage = await this.searchThroughPages(invoiceId, jobId);
                }
                
                if (!foundPage) {
                    // Strategy 4: Try common pages (fallback)
                    console.log('Strategy 4: Trying common pages as fallback...');
                    foundPage = await this.tryCommonPages(invoiceId, jobId);
                }
                
                if (foundPage) {
                    console.log('searchForJobAcrossPages: Found job on page', foundPage);
                    // Navigate to the correct page
                    this.pagination.current_page = foundPage;
                    await this.fetchJobs();
                    
                    // After fetching, the openTargetJob will be called again and should find the job
                    // But we need to preserve the target job info and reset the searching flag
                    this.targetJobInfo = { jobId, invoiceId };
                    this.isSearchingForJob = false;
                } else {
                    console.warn('searchForJobAcrossPages: Job not found with any strategy');
                    this.handleJobNotFound(jobId, invoiceId);
                }
                
            } catch (error) {
                console.error('searchForJobAcrossPages: Error searching for job:', error);
                this.handleJobNotFound(jobId, invoiceId);
            } finally {
                this.isSearchingForJob = false;
            }
        },
        async searchByInvoiceId(invoiceId) {
            try {
                console.log('searchByInvoiceId: Searching for invoice', invoiceId);
                
                // Search by invoice ID - this should be more reliable
                const searchParams = new URLSearchParams();
                searchParams.set('search', invoiceId.toString());
                searchParams.set('per_page', '1000'); // Get maximum results
                
                const response = await axios.get(`/actions/${this.actionId}/jobs?${searchParams.toString()}`);
                const searchResults = response.data?.invoices || [];
                
                // Check if our invoice is in the search results
                const targetInvoice = searchResults.find(invoice => invoice.id === invoiceId);
                
                if (targetInvoice) {
                    console.log('searchByInvoiceId: Found invoice in search results');
                    // Now find which page this invoice is on in normal pagination
                    return await this.findPageForInvoice(invoiceId);
                }
                
                return null;
            } catch (error) {
                console.error('searchByInvoiceId: Error:', error);
                return null;
            }
        },
        async searchByJobId(jobId, invoiceId) {
            try {
                console.log('searchByJobId: Searching for job', jobId);
                
                // Try different search patterns for job ID
                const searchPatterns = [
                    jobId.toString(),
                    `#${jobId}`,
                    `Job ${jobId}`,
                    `job ${jobId}`
                ];
                
                for (const pattern of searchPatterns) {
                    const searchParams = new URLSearchParams();
                    searchParams.set('search', pattern);
                    searchParams.set('per_page', '1000');
                    
                    const response = await axios.get(`/actions/${this.actionId}/jobs?${searchParams.toString()}`);
                    const searchResults = response.data?.invoices || [];
                    
                    // Find the invoice containing our job
                    const targetInvoice = searchResults.find(invoice => 
                        invoice.id === invoiceId && 
                        invoice.jobs.some(job => job.id === jobId)
                    );
                    
                    if (targetInvoice) {
                        console.log('searchByJobId: Found job with pattern:', pattern);
                        return await this.findPageForInvoice(invoiceId);
                    }
                }
                
                return null;
            } catch (error) {
                console.error('searchByJobId: Error:', error);
                return null;
            }
        },
        async searchThroughPages(invoiceId, jobId) {
            try {
                console.log('searchThroughPages: Searching through pages systematically');
                
                // Get total pages from current pagination
                const totalPages = this.pagination.last_page || 1;
                console.log('searchThroughPages: Total pages to search:', totalPages);
                
                // Search through pages more efficiently
                let foundPage = null;
                const maxPagesToSearch = Math.min(totalPages, 20); // Limit to prevent excessive API calls
                
                for (let page = 1; page <= maxPagesToSearch; page++) {
                    console.log(`searchThroughPages: Checking page ${page}/${maxPagesToSearch}`);
                    
                    const params = new URLSearchParams();
                    params.set('page', page.toString());
                    params.set('per_page', this.pagination.per_page.toString());
                    
                    const response = await axios.get(`/actions/${this.actionId}/jobs?${params.toString()}`);
                    const pageInvoices = response.data?.invoices || [];
                    
                    // Check if our invoice is on this page
                    const invoiceIndex = pageInvoices.findIndex(invoice => invoice.id === invoiceId);
                    if (invoiceIndex !== -1) {
                        foundPage = page;
                        console.log('searchThroughPages: Found invoice on page', page);
                        break;
                    }
                    
                    // Add a small delay to prevent overwhelming the server
                    await new Promise(resolve => setTimeout(resolve, 100));
                }
                
                return foundPage;
            } catch (error) {
                console.error('searchThroughPages: Error:', error);
                return null;
            }
        },
        async tryCommonPages(invoiceId, jobId) {
            try {
                console.log('tryCommonPages: Trying common pages as fallback');
                
                // Try common pages where jobs might be located
                const commonPages = [2, 3, 4, 5]; // Common pages to check
                
                for (const page of commonPages) {
                    console.log(`tryCommonPages: Checking page ${page}`);
                    
                    const params = new URLSearchParams();
                    params.set('page', page.toString());
                    params.set('per_page', this.pagination.per_page.toString());
                    
                    const response = await axios.get(`/actions/${this.actionId}/jobs?${params.toString()}`);
                    const pageInvoices = response.data?.invoices || [];
                    
                    // Check if our invoice is on this page
                    const invoiceIndex = pageInvoices.findIndex(invoice => invoice.id === invoiceId);
                    if (invoiceIndex !== -1) {
                        console.log(`tryCommonPages: Found invoice on page ${page}`);
                        return page;
                    }
                }
                
                return null;
            } catch (error) {
                console.error('tryCommonPages: Error:', error);
                return null;
            }
        },
        async findPageForInvoice(invoiceId) {
            try {
                console.log('findPageForInvoice: Finding page for invoice', invoiceId);
                
                // Search through pages to find the invoice
                let foundPage = null;
                const totalPages = this.pagination.last_page || 1;
                const maxPagesToSearch = Math.min(totalPages, 10); // Limit search
                
                for (let page = 1; page <= maxPagesToSearch; page++) {
                    const params = new URLSearchParams();
                    params.set('page', page.toString());
                    params.set('per_page', this.pagination.per_page.toString());
                    
                    const response = await axios.get(`/actions/${this.actionId}/jobs?${params.toString()}`);
                    const pageInvoices = response.data?.invoices || [];
                    
                    // Check if our invoice is on this page
                    const invoiceIndex = pageInvoices.findIndex(invoice => invoice.id === invoiceId);
                    if (invoiceIndex !== -1) {
                        foundPage = page;
                        console.log('findPageForInvoice: Found invoice on page', page);
                        break;
                    }
                }
                
                return foundPage;
            } catch (error) {
                console.error('findPageForInvoice: Error:', error);
                return null;
            }
        },
        async findInvoicePage(invoiceId, jobId) {
            try {
                console.log('findInvoicePage: Finding page for invoice', invoiceId);
                
                // Search through pages to find the invoice
                let foundPage = null;
                let currentPage = 1;
                const maxPagesToSearch = 10; // Limit search to prevent infinite loops
                
                while (currentPage <= maxPagesToSearch && !foundPage) {
                    const params = new URLSearchParams();
                    params.set('page', currentPage.toString());
                    params.set('per_page', this.pagination.per_page.toString());
                    
                    const response = await axios.get(`/actions/${this.actionId}/jobs?${params.toString()}`);
                    const pageInvoices = response.data?.invoices || [];
                    
                    // Check if our invoice is on this page
                    const invoiceIndex = pageInvoices.findIndex(invoice => invoice.id === invoiceId);
                    if (invoiceIndex !== -1) {
                        foundPage = currentPage;
                        console.log('findInvoicePage: Found invoice on page', currentPage);
                        break;
                    }
                    
                    currentPage++;
                    
                    // Stop if we've reached the last page
                    if (currentPage > (response.data?.pagination?.last_page || 1)) {
                        break;
                    }
                }
                
                if (foundPage) {
                    // Navigate to the correct page
                    console.log('findInvoicePage: Navigating to page', foundPage);
                    this.pagination.current_page = foundPage;
                    await this.fetchJobs();
                    
                    // After fetching, the openTargetJob will be called again and should find the job
                    // But we need to preserve the target job info and reset the searching flag
                    this.targetJobInfo = { jobId, invoiceId };
                    this.isSearchingForJob = false;
                } else {
                    console.warn('findInvoicePage: Could not find invoice on any page');
                    this.handleJobNotFound(jobId, invoiceId);
                }
                
            } catch (error) {
                console.error('findInvoicePage: Error finding invoice page:', error);
                this.handleJobNotFound(jobId, invoiceId);
            }
        },
        handleJobNotFound(jobId, invoiceId) {
            console.warn(`Could not find job ${jobId} in invoice ${invoiceId}`);
            // Clear the target job info if not found
            this.targetJobInfo = null;
            // Clear URL parameters even if job not found
            this.clearUrlParams();
            // Reset the searching flag
            this.isSearchingForJob = false;
            
            // Show error toast
            const toast = useToast();
            toast.error(`Job #${jobId} not found. It may have been deleted or moved.`);
        },
        clearUrlParams() {
            // Clear URL parameters to clean up the URL
            const url = new URL(window.location);
            url.search = '';
            window.history.replaceState({}, '', url);
        },
        focusJobWithAnimation(jobId) {
            try {
                console.log('focusJobWithAnimation: Focusing job', jobId);
                
                // Find the parent main container that holds the job
                const jobElement = document.querySelector(`[data-job-id="${jobId}"]`);
                if (!jobElement) {
                    console.warn('focusJobWithAnimation: Job element not found');
                    return;
                }
                
                // Find the parent div with class="main" that contains this job
                const mainContainer = jobElement.closest('.main');
                if (!mainContainer) {
                    console.warn('focusJobWithAnimation: Main container not found');
                    return;
                }
                
                // Remove any existing focus styling
                this.removeJobFocus();
                
                // Add focus styling to the main container
                mainContainer.classList.add('job-container-focused');
                
                // Scroll to the main container smoothly
                mainContainer.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'center' 
                });
                
                // Remove focus styling after animation completes
                setTimeout(() => {
                    this.removeJobFocus();
                }, 3000); // Keep focus for 3 seconds
                
                console.log('focusJobWithAnimation: Main container focused successfully');
                
            } catch (error) {
                console.error('focusJobWithAnimation: Error:', error);
            }
        },
        removeJobFocus() {
            // Remove focus styling from main container with smooth animation
            const containerFocused = document.querySelector('.job-container-focused');
            if (containerFocused) {
                // Add focus-out animation class
                containerFocused.classList.add('focus-out');
                
                // Remove all focus classes after animation completes
                setTimeout(() => {
                    containerFocused.classList.remove('job-container-focused', 'focus-out');
                }, 400); // Match the focus-out animation duration
            }
        },
        scrollToJob(jobId) {
            // Find the job element and scroll to it
            const jobElement = document.querySelector(`[data-job-id="${jobId}"]`);
            if (jobElement) {
                jobElement.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'center' 
                });
                console.log(`Scrolled to job ${jobId}`);
            } else {
                console.warn(`Job element with ID ${jobId} not found in DOM`);
            }
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

                    // Ensure we always have a started_at
                    const ensuredStartTime = response.data.started_at || new Date().toISOString();
                    this.startTimer(actionId, ensuredStartTime);
                    job.started = true;
                    this.jobDisabledStatus[actionId] = true;

                    // Update local action status
                    const action = job.actions.find(a => a.id === actionId);
                    if (action) {
                        action.status = 'In progress';
                        action.started_at = ensuredStartTime;
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

/* Pagination styles */
.pagination {
    padding-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.page-btn {
    background-color: #2d3748;
    color: #fff;
    border: 1px solid #4a5568;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.15s ease;
}
.page-btn:hover:not(:disabled) {
    background-color: #3b475a;
}
.page-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
.page-btn.active {
    background-color: #2563eb;
    border-color: #2563eb;
}
.page-btn.dots {
    cursor: default;
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

/* Pre-search loading styles */
.pre-search-loading {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background-color: rgba(0, 0, 0, 0.8);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.loading-content {
    background-color: #2d3748;
    padding: 30px;
    border-radius: 8px;
    color: white;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 15px;
}

.loading-content i {
    font-size: 2em;
    color: #4299e1;
}

.loading-content span {
    font-size: 1.2em;
    font-weight: 500;
}

.animated-dots {
    display: inline-block;
    animation: dotAnimation 1.5s infinite;
}

@keyframes dotAnimation {
    0%, 20% {
        opacity: 0;
        transform: translateY(0);
    }
    50% {
        opacity: 1;
        transform: translateY(-2px);
    }
    80%, 100% {
        opacity: 0;
        transform: translateY(0);
    }
}

/* Main container focus animation styles */
.job-container-focused{
    animation: containerFocusIn 0.6s ease-out forwards;
    position: relative;
    z-index: 10;
    transition: all 0.3s ease;
}

.job-container-focused::after {
    content: '';
    position: absolute;
    top: -3px;
    left: -3px;
    right: -3px;
    bottom: -3px;
    border: 3px solid #4299e1;
    border-radius: inherit;
    z-index: 1;
    animation: borderGlow 2s ease-in-out infinite;
    pointer-events: none;
}

.job-container-focused::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    border: 1px solid rgba(66, 153, 225, 0.6);
    border-radius: inherit;
    z-index: 1;
    animation: innerGlow 2s ease-in-out infinite;
    pointer-events: none;
}



@keyframes jobFocusIn {
    0% {
        transform: scale(1);
        background-color: transparent;
    }
    50% {
        transform: scale(1.01);
        background-color: rgba(66, 153, 225, 0.08);
    }
    100% {
        transform: scale(1);
        background-color: rgba(66, 153, 225, 0.05);
    }
}



@keyframes containerFocusIn {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.01);
    }
    100% {
        transform: scale(1);
    }
}



@keyframes containerGlow {
    0%, 100% {
        opacity: 0.3;
        transform: translateX(-100%);
    }
    50% {
        opacity: 0.6;
        transform: translateX(100%);
    }
}

@keyframes borderGlow {
    0%, 100% {
        opacity: 0.8;
        box-shadow: 
            0 0 5px rgba(66, 153, 225, 0.8),
            0 0 10px rgba(66, 153, 225, 0.6),
            0 0 15px rgba(66, 153, 225, 0.4),
            0 0 20px rgba(66, 153, 225, 0.2);
    }
    50% {
        opacity: 1;
        box-shadow: 
            0 0 8px rgba(66, 153, 225, 1),
            0 0 15px rgba(66, 153, 225, 0.8),
            0 0 25px rgba(66, 153, 225, 0.6),
            0 0 35px rgba(66, 153, 225, 0.4);
    }
}

@keyframes innerGlow {
    0%, 100% {
        opacity: 0.4;
        box-shadow: inset 0 0 5px rgba(66, 153, 225, 0.3);
    }
    50% {
        opacity: 0.8;
        box-shadow: inset 0 0 10px rgba(66, 153, 225, 0.6);
    }
}

/* Focus out animation */
.job-container-focused.focus-out {
    animation: containerFocusOut 0.4s ease-in forwards;
}

@keyframes containerFocusOut {
    0% {
        transform: scale(1);
    }
    100% {
        transform: scale(1);
    }
}
</style>


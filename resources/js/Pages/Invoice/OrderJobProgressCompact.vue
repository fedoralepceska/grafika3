<template>
    <div class="compact-progress">
        <!-- ACTIONS Label -->
        <div v-if="showLabel" class="actions-label">
            {{ labelText }} ⏷
        </div>
        
        <div class="progress-line" v-if="actions(job.id).length > 0">
            <div class="step" v-for="action in actions(job.id)" :key="action.name">
                <div
                    class="dot"
                    :class="{
                        'dark-gray': action.status === 'Not started yet',
                        'green': action.status === 'Completed',
                        'blue': action.status === 'In progress',
                        'interactive': action.status !== 'Completed' && clickable,
                        'disabled': action.status === 'Completed' || !clickable
                    }"
                    :title="getDotTitle(action)"
                    @click.stop="clickable && action.status !== 'Completed' ? navigateToAction(action.name) : null"
                >
                    <span v-if="action.status === 'Completed'">&#10003;</span>
                </div>
                <div class="label" :title="action.name">{{ action.name }}</div>
            </div>
        </div>
        <div v-else class="no-actions">
            <span class="text-gray-400 text-sm">No actions defined</span>
        </div>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    name: 'OrderJobProgressCompact',
    props: {
        /** The job object containing actions and other job information */
        job: { type: Object, required: true },
        /** Invoice ID for navigation (can be null for display-only mode) */
        invoiceId: { type: [Number, String], required: false, default: null },
        /** Whether the component should be clickable and allow navigation */
        clickable: { type: Boolean, default: true },
        /** Whether to show the ACTIONS label above the progress line */
        showLabel: { type: Boolean, default: false },
        /** Text to display in the label when showLabel is true */
        labelText: { type: String, default: 'ACTIONS' },
    },
    data() {
        return {
            newJobs: [],
        };
    },
    beforeMount() {
        this.fetchJobs();
        if (this.clickable) {
            console.log('OrderJobProgressCompact mounted with job:', this.job, 'invoiceId:', this.invoiceId);
        }
    },
    methods: {
        async fetchJobs() {
            try {
                const response = await axios.get('/jobs');
                if (response.data && Array.isArray(response.data)) {
                    this.newJobs = response.data;
                } else {
                    console.warn('Unexpected response format from /jobs endpoint');
                    this.newJobs = [];
                }
            } catch (error) {
                console.error('Failed to fetch jobs:', error);
                this.newJobs = [];
            }
        },
        actions(id) {
            try {
                if (this.clickable) {
                    console.log('Processing actions for job:', id, 'job object:', this.job);
                }
                if (this.job && this.job.actions && Array.isArray(this.job.actions) && this.job.actions.length > 0) {
                    if (this.clickable) {
                        console.log('Found actions in job object:', this.job.actions);
                    }
                    return this.job.actions.map(action => ({
                        name: action.name || 'Unknown Action',
                        status: action.status || 'Not started yet',
                    }));
                }
                const job = this.newJobs.find(j => j.id === id);
                if (job && job.actions && Array.isArray(job.actions) && job.actions.length > 0) {
                    if (this.clickable) {
                        console.log('Found actions in newJobs:', job.actions);
                    }
                    return job.actions.map(action => ({
                        name: action.name || 'Unknown Action',
                        status: action.status || 'Not started yet',
                    }));
                }
                if (this.clickable) {
                    console.log('No actions found for job:', id);
                }
                return [];
            } catch (error) {
                console.error('Error processing actions:', error);
                return [];
            }
        },
        navigateToAction(actionName) {
            try {
                // Build URL with query parameters to identify the specific job
                if (!this.job.id || !this.invoiceId) {
                    console.warn('Missing job ID or invoice ID for navigation');
                    return;
                }
                const url = `/actions/${actionName}?job=${this.job.id}&invoice=${this.invoiceId}`;
                window.location.href = url;
            } catch (error) {
                console.error('Error navigating to action:', error);
            }
        },
        getCompletionStatus() {
            const jobActions = this.actions(this.job.id);
            if (!jobActions || jobActions.length === 0) {
                return 'not_started';
            }
            const allCompleted = jobActions.every(action => action.status === 'Completed');
            if (allCompleted) {
                return 'completed';
            }
            const anyInProgress = jobActions.some(action => action.status === 'In progress');
            if (anyInProgress) {
                return 'in_progress';
            }
            return 'not_started';
        },
        getDotTitle(action) {
            if (!this.clickable) {
                return `${action.name} - ${action.status}`;
            }
            if (action.status === 'Completed') {
                return `${action.name} - Completed (Click disabled)`;
            }
            return `${action.name} - Click to go to action`;
        }
    },
};
</script>

<style scoped lang="scss">

 .compact-progress {
    width: 100%;

}

.actions-label {
    color: #10B981; /* green color to match the original ACTIONS styling */
    font-weight: bold;
    padding: 4px 4px 4px 4px;
    width: 100%;
    text-align: left;
    margin-bottom: 8px;
    cursor: default;
}

.progress-line {
    position: relative;
    display: flex;
    align-items: flex-start; // align dots at top so line matches dot center
    justify-content: space-between;
    gap: 6px;
    padding: 4px 8px 20px; // reduced padding for tighter spacing
    overflow: hidden; // ensure children never render outside container
}

// Geometry variables for alignment
$dot-size: 16px;
$container-vertical-padding: 6px;
$container-horizontal-padding: 10px;

.progress-line::before {
    content: '';
    position: absolute;
    left: calc(#{$container-horizontal-padding} + (#{$dot-size} / 2)); // align with center of first dot
    right: calc(#{$container-horizontal-padding} + (#{$dot-size} / 2)); // align with center of last dot
    top: calc(#{$container-vertical-padding} + (#{$dot-size} / 2)); // vertically align to center of dots
    height: 2px;
    background-color: #6B7280; /* gray-500 */
    z-index: 0;
}

.step {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
    flex: 1 1 0; // evenly distribute space; dots stay centered in their segment
    min-width: $dot-size; // never shrink below dot
}

.dot {
    box-sizing: border-box;
    width: $dot-size;
    height: $dot-size;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #D3D3D3;
    color: #fff;
    font-size: 10px;
    line-height: 1;
}

.interactive {
    cursor: pointer;
}

.disabled {
    cursor: not-allowed;
    pointer-events: none;
    border: 2px solid #9CA3AF;
    background-color: #6B7280;
}

.interactive {
    cursor: pointer;
    transition: all 0.2s ease;
}

.interactive:hover {
    transform: scale(1.1);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
}

/* When not clickable, remove hover effects */
.compact-progress .dot.disabled:hover {
    transform: none;
    box-shadow: none;
}

.blue { background-color: #3B82F6; } /* blue-500 */
.green { background-color: #10B981; } /* emerald-500 */
.dark-gray { background-color: #374151; } /* gray-700 */

.label {
    position: absolute;
    top: calc(#{$dot-size} + 4px);
    left: -40px; // allow label to center under dot while not affecting spacing
    right: -40px;
    color: #E5E7EB; /* gray-200 for better contrast */
    font-size: 10px;
    line-height: 1.15;
    padding: 0 4px;
    text-align: center;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: normal; // allow wrapping
    word-break: break-word;
    hyphens: auto;
}

@media (max-width: 480px) {
    .label {
        max-width: 64px;
    }
}

.no-actions {
    text-align: center;
    padding: 20px 10px;
    color: #9CA3AF; /* gray-400 */
    font-style: italic;
}
</style>



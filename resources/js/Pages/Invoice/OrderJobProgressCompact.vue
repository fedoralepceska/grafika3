<template>
    <div class="compact-progress">
        <div class="progress-line" v-if="actions(job.id).length > 0">
            <div class="step" v-for="action in actions(job.id)" :key="action.name">
                <div
                    class="dot"
                    :class="{
                        'dark-gray': action.status === 'Not started yet',
                        'green': action.status === 'Completed',
                        'blue': action.status === 'In progress',
                        'interactive': action.status !== 'Completed',
                        'disabled': action.status === 'Completed'
                    }"
                    :title="action.status === 'Completed' ? `${action.name} - Completed (Click disabled)` : `${action.name} - Click to go to action`"
                    @click.stop="action.status !== 'Completed' ? navigateToAction(action.name) : null"
                >
                    <span v-if="action.status === 'Completed'">&#10003;</span>
                </div>
                <div class="label" :title="action.name">{{ action.name }}</div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    name: 'OrderJobProgressCompact',
    props: {
        job: { type: Object, required: true },
        invoiceId: { type: Number, required: true },
    },
    data() {
        return {
            newJobs: [],
        };
    },
    beforeMount() {
        this.fetchJobs();
    },
    methods: {
        async fetchJobs() {
            try {
                const response = await axios.get('/jobs');
                this.newJobs = response.data;
            } catch (error) {
                console.error('Failed to fetch jobs:', error);
            }
        },
        actions(id) {
            if (this.job && this.job.actions && this.job.actions.length > 0) {
                return this.job.actions.map(action => ({
                    name: action.name,
                    status: action.status,
                }));
            }
            const job = this.newJobs.find(j => j.id === id);
            if (job && job.actions && job.actions.length > 0) {
                return job.actions.map(action => ({
                    name: action.name,
                    status: action.status,
                }));
            }
            return [];
        },
        navigateToAction(actionName) {
            // Build URL with query parameters to identify the specific job
            if (!this.job.id || !this.invoiceId) {
                console.warn('Missing job ID or invoice ID for navigation');
                return;
            }
            const url = `/actions/${actionName}?job=${this.job.id}&invoice=${this.invoiceId}`;
            window.location.href = url;
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
    },
};
</script>

<style scoped lang="scss">

 .compact-progress {
    width: 100%;
}

.progress-line {
    position: relative;
    display: flex;
    align-items: flex-start; // align dots at top so line matches dot center
    justify-content: space-between;
    gap: 8px;
    padding: 6px 10px 30px; // bottom padding to make room for two-line labels
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
    background-color: $background-color;
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

.blue { background-color: $blue; }
.green { background-color: $green; }
.dark-gray { background-color: $dark-gray; }

.label {
    position: absolute;
    top: calc(#{$dot-size} + 6px);
    left: -40px; // allow label to center under dot while not affecting spacing
    right: -40px;
    color: $white;
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
</style>



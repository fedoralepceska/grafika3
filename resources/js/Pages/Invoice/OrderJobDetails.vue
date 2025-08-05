<template>
    <div class="pt-1 pl-2 pr-2">
        <div class="flex gap-8 line">
            <div class="completed line">
                <div class="action">
                    <div
                        class="flexed"
                        :class="{
                            'circle': true,
                            'dark-gray': job.status === 'Not started yet',
                            'green': job.status === 'Completed',
                            'blue': job.status === 'In progress',
                            'disabled' : true,
                        }"
                    >
                        <span v-if="job.status === 'Completed'">&#10003;</span>
                    </div>
                    <span>Start</span>
                </div>
            </div>
            <div v-for="action in actions(job.id)" :key="action.name">
                <div class="action">
                    <div
                        class="flexed"
                        style="cursor: pointer"
                        :class="{
                            'circle': true,
                            'dark-gray': action.status === 'Not started yet',
                            'green': action.status === 'Completed',
                            'blue': action.status === 'In progress',
                        }"
                        @click="navigateToAction(action.name)"
                    >
                        <span v-if="action.status === 'Completed'">&#10003;</span>
                    </div>
                    <span>{{ action.name }}</span>
                </div>
            </div>
            <div class="completed line">
                <div class="action">
                    <div
                        class="flexed"
                        :class="{
                            'circle': true,
                            'dark-gray': getCompletionStatus() === 'not_started',
                            'blue': getCompletionStatus() === 'in_progress',
                            'green': getCompletionStatus() === 'completed',
                            'disabled' : true,
                        }"
                    >
                        <span v-if="getCompletionStatus() === 'completed'">&#10003;</span>
                    </div>
                    <span>Completed</span>
                </div>
            </div>
        </div>
    </div>
</template>


<script>
import axios from "axios";

export default {
    props: {
        job: Object,
        circle: Boolean,
        blue: Boolean,
        green: Boolean,
        background: Boolean,
    },
    data(){
        return {
            newJobs: []
        }
    },
    beforeMount() {
        this.fetchJobs()
    },
    methods: {
        async fetchJobs() {
            try {
                let response = await axios.get('/jobs');
                this.newJobs = response.data;
            } catch (error) {
                console.error("Failed to fetch jobs:", error);
            }
        },
        actions(id) {
            // First try to get actions from the job prop (from invoice data)
            if (this.job && this.job.actions && this.job.actions.length > 0) {
                return this.job.actions.map(action => ({
                    name: action.name,
                    status: action.status,
                }));
            }
            
            // Fallback to fetching from API
            const job = this.newJobs.find(job => job.id === id);
            if (job) {
                const jobActions = job.actions;

                if (jobActions && jobActions.length > 0) {
                    return jobActions.map(action =>({
                        name: action.name,
                        status: action.status,
                    }));
                }
            }
            return false; // Return a default value if there are no actions for the job
        },
        navigateToAction(actionName) {
                window.location.href = `/actions/${actionName}`;
        },
        getCompletionStatus() {
            const jobActions = this.actions(this.job.id);
            // If no actions exist, return not_started
            if (!jobActions || jobActions.length === 0) {
                return 'not_started';
            }
            // Check if all actions are completed
            const allCompleted = jobActions.every(action => action.status === 'Completed');
            if (allCompleted) {
                return 'completed';
            }
            // Check if any action is in progress
            const anyInProgress = jobActions.some(action => action.status === 'In progress');
            if (anyInProgress) {
                return 'in_progress';
            }
            // In all other cases, return not_started (idle/dark-gray)
            return 'not_started';
        },
    },
};
</script>

<style scoped lang="scss">
.line {
    display: flex;
    align-items: center;
    position: relative;
    justify-content: space-between;

    // This will create the line
    &:before {
        content: '';
        position: absolute;
        top: 20px; // Adjust this value to match the vertical center of your circles
        left: 4px;
        right: 40px;
        height: 2px; // Thickness of the line
        background-color: $background-color; // Color of the line
        z-index: 0; // Ensure it's behind the circles
    }

    .action {
        // This will position the circle properly over the line
        position: relative;
        z-index: 1;
    }

    .circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        border: 2px solid #D3D3D3; // Border color similar to the line if needed

        // Checkmark or other content goes here
        span {
            color: #fff; // Adjust as necessary
        }
    }
}
.disabled {
    cursor: not-allowed;
    pointer-events: none;
}
.flexed{
    display: flex;
    justify-content: center;
    align-content: center;
    align-items: center;
    cursor: default;
}
.action, .completed{
    display: flex;
    flex-direction: column;
    align-items: center;
}
.circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
}
.blue {
    background-color: $blue;
}
.green {
    background-color: $green;
}
.dark-gray {
    background-color: $dark-gray;
}

</style>

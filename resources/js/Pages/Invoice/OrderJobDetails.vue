<template>
    <div class="pt-1 pl-2 pr-2">
        <div class="flex gap-8 line">
            <div class="completed line">
                <div class="action">
                    <div class="flexed" :class="{
                            'circle': true,
                            'dark-gray': job.status === 'Not started yet',
                            'green': job.status === 'Completed' || 'In progress',
                             }">
                        <span v-if="job.status === 'Completed'">&#10003;</span>
                    </div>
                    <span>Start</span>
                </div>
            </div>
            <div v-for="action in actions(job.id)" :key="action">
                <div class="action">
                    <div class="flexed" :class="{
                        'circle': true,
                        'dark-gray': action.status === 'Not started yet',
                        'green': action.status === 'Completed',
                        'blue': action.status === 'In progress'
                      }">
                        <span v-if="action.status === 'Completed'">&#10003;</span>
                    </div>
                        <span>{{ $t(`actions.${action.name}`) }}</span>
                </div>
            </div>
            <div class="completed line ">
                <div class="action">
                    <div class="flexed" :class="{
                        'circle': true,
                        'dark-gray': job.status === 'Not started yet',
                        'blue': job.status === 'In progress',
                        'green': job.status === 'Completed',
                      }">
                        <span v-if="job.status === 'Completed'">&#10003;</span>
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
            const job = this.newJobs.find(job => job.id === id);
            // Check if the job exists
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

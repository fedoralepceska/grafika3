<template>
    <div>
        {{ actionId }}
        <p>{{ jobs }}</p>
    </div>
</template>

<script>
export default {
    name: 'ActionPage',
    props: {
        actionId: String
    },
    data() {
        return {
            jobs: [] // This will hold jobs related to the action
        };
    },
    created() {
        this.fetchJobs();
    },
    methods: {
        fetchJobs() {
            // Fetch the jobs from your API
            const url = `/actions/${this.actionId}/jobs`;
            axios.get(url)
                .then(response => {
                    this.jobs = response.data;
                    console.log(response, this.jobs);
                })
                .catch(error => {
                    console.error('There was an error fetching the jobs:', error);
                });
        }
    }
}
</script>

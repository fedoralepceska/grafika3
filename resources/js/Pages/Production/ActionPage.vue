<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="action" subtitle="actionInfo" icon="List.png"/>
            <div class="grid-container">
                Jobs: {{jobs}} <br>
                Invoices: {{invoices}} <br>
                Action: {{id}} <br>
            </div>
        </div>
    </MainLayout>
</template>

<script>
import MainLayout from "@/Layouts/MainLayout.vue";
import Header from "@/Components/Header.vue";

export default {
    name: 'ActionPage',
    components: {
        MainLayout,
        Header
    },
    props: {
        actionId: String
    },
    data() {
        return {
            invoices: [],
            jobs: [],
            id: null
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
                    console.log(response);
                    this.jobs = response.data?.jobs;
                    this.invoices = response.data?.invoices;
                    this.id = response.data?.actionId;
                })
                .catch(error => {
                    console.error('There was an error fetching the jobs:', error);
                });
        }
    }
}
</script>

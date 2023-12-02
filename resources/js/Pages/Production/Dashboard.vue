<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="production" subtitle="dashboard" icon="List.png"/>
            <div class="grid-container">
                <div v-for="item in jobActionStatusCounts" :key="item.name" class="grid-item">
                    <span class="circle-badge">
                        <v-badge :content="item.secondaryCount" color="grey lighten-1" overlap>
                            <template v-slot:badge>
                                <span class="sub-badge">{{ item.secondaryCount }}</span>
                            </template>
                            <span class="primary-count">{{ item.total }}</span>
                        </v-badge>
                    </span>
                    <span>{{ $t(`actions.${item.name}`) }}</span>
                </div>
            </div>
        </div>
    </MainLayout>
</template>


<script>
import axios from 'axios';
import MainLayout from "@/Layouts/MainLayout.vue";
import Header from "@/Components/Header.vue";

export default {
    components: {
        MainLayout,
        Header
    },
    data() {
        return {
            jobActionStatusCounts: {},
        };
    },
    created() {
        this.fetchJobActionStatusCounts();
    },
    methods: {
        async fetchJobActionStatusCounts() {
            try {
                const response = await axios.get('/job-action-status-counts');
                this.jobActionStatusCounts = response.data;
                console.log(response.data)
            } catch (error) {
                console.error(error);
            }
        },
    }
}
</script>
<style scoped>
.grid-container {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr)); /* Use minmax for flexible but bounded sizing */
    grid-gap: 10px; /* Adjust the gap between items */
    padding: 0; /* Remove padding or set it appropriately */
    margin: 0 auto; /* Center the container with automatic horizontal margins */
    max-width: calc(100vw - 14px); /* Account for the total padding if any */
    box-sizing: border-box; /* Include padding in width calculations */
    place-items: center; /* Center items along both the block and inline (row and column) axis */
}

.grid-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin: 0;
    width: auto;
}

.circle-badge {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100px;
    height: 100px;
    border: 2px solid grey;
    border-radius: 50%;
    background-color: #FFF;
}

.primary-count {
    font-size: 2em;
    z-index: 1;
}

.sub-badge {
    position: absolute;
    transform: translate(50%, -50%);
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background-color: #FFF;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    color: grey;
    border: 2px solid grey;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}
</style>


<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="production" subtitle="dashboard" icon="List.png"/>
            <div class="grid-container">
                <div v-for="item in jobActionStatusCounts" :key="item.name" class="grid-item">
                    <span class="circle-badge">
                        <v-badge class="inner" :content="item.secondaryCount" color="#FFFFFF00" overlap offset-x="-45" offset-y="-20" >
                            <template v-slot:badge>
                                <div :class="['sub-badge', { 'double-width': item.onHoldCount }]">
                                    <i class="fa-solid fa-spinner"></i>
                                    <div>{{ item.secondaryCount }}</div>
                                    <div v-if="item.onHoldCount" class="flex">
                                        <i class="fa-solid fa-ban red"></i>
                                        <div class="red">{{ item.onHoldCount }}</div>
                                    </div>
                                </div>
                            </template>
                            <span class="primary-count">{{ item.total }}</span>
                        </v-badge>
                    </span>
                    <span class="text-white">{{ $t(`actions.${item.name}`) }}</span>
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
<style scoped lang="scss">
.grid-container {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr)); /* Use minmax for flexible but bounded sizing */
    grid-gap: 10px; /* Adjust the gap between items */
    padding: 25px; /* Remove padding or set it appropriately */
    margin: 16px auto; /* Center the container with automatic horizontal margins */
    max-width: calc(100vw - 14px); /* Account for the total padding if any */
    box-sizing: border-box; /* Include padding in width calculations */
    place-items: center; /* Center items along both the block and inline (row and column) axis */
    background-color: $dark-gray;
}


.grid-item {
    padding: 25px;
    display: flex;
    flex-direction: column;
    align-items: center;
    margin: 0;
    width: auto;
}

.circle-badge{
    padding: 15px;
    border-radius: 50%;
    display: inline-block;
    background: $blue; // for browsers which do not support gradient
    background: -webkit-linear-gradient(-50deg,$blue, #BBDEFB);
    background: -o-linear-gradient(-50deg,$blue, #BBDEFB);
    background: -moz-linear-gradient(-50deg,$blue, #BBDEFB);
    background: linear-gradient(-50deg,$blue, #BBDEFB) ;
    cursor: pointer;
}
.inner{
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 120px;
    height: 120px;
    background: $background-color;
    border-radius: 50%;
    transition: width 0.3s, height 0.3s;
}
.inner:hover {
    width: 130px;
    height: 130px;
}

.primary-count {
    font-size: 3.2em;
    z-index: 1;
    color: white;
    cursor: pointer;
}

.sub-badge {
    display: flex;
    flex-direction: row;
    position: absolute;
    transform: translate(50%, -50%);
    width: 42px;
    height: 30px;
    border-radius: 25px;
    background-color: #FFF;
    gap: 5px;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    color: #EF6C00;
}
.double-width {
    width: 84px;
}
.flex {
    display: flex;
    flex-direction: row;
    gap: 5px;
}
.red {
    color: $red;
}
</style>


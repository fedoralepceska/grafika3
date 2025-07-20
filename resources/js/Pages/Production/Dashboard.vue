<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="production" subtitle="dashboard" icon="dashboard.png" link="production"/>
            <div class="grid-container">
                <div v-for="item in jobActionStatusCounts" :key="item.name" class="grid-item">
                    <div class="badge-container">
                        <span class="circle-badge" @click="navigateToAction(item.name)">
                            <div class="inner">
                                <span class="primary-count">{{ item.total }}</span>
                            </div>
                        </span>
                        <div v-if="item.secondaryCount" class="secondary-badge flex2 bg-white rounded pl-2 pr-2 orange">
                                <i class="fa-solid fa-spinner"></i>
                                <div>{{ item.secondaryCount }}</div>
                        </div>
                        <div v-if="item.onHoldCount" class="onhold-badge flex2 bg-white rounded pl-2 pr-2 ">
                                <i class="fa-solid fa-ban red"></i>
                                <div class="red">{{ item.onHoldCount }}</div>
                        </div>
                        <div v-if="item.onRushCount" class="rush-badge flex2 text-white rounded pl-2 pr-2 blinking " style="background-color: #9e2c30">
                            <span class="mdi mdi-fire"></span>
                            <div> {{ item.onRushCount }}</div>
                        </div>
                    </div>
                    <span class="text-white">{{ item.name }}</span>
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
            jobMachinesCut: {},
        };
    },
    created() {
        this.fetchJobActionStatusCounts();
    },
    methods: {
        async fetchJobActionStatusCounts() {
            try {
                const response = await axios.get('/job-action-status-counts');
                this.jobActionStatusCounts = response.data.sort((a, b) => {
                    const nameA = a.name.toLowerCase();
                    const nameB = b.name.toLowerCase();

                    const isDostavaA = nameA.includes('достава') || nameA.includes('dostava');
                    const isDostavaB = nameB.includes('достава') || nameB.includes('dostava');

                    if (isDostavaA && !isDostavaB) return 1;  // a goes after b
                    if (!isDostavaA && isDostavaB) return -1; // a goes before b
                    if (isDostavaA && isDostavaB) return 0;   // same, keep order

                    return nameA.localeCompare(nameB); // regular alphabetical sort
                });
            } catch (error) {
                console.error(error);
            }
        },
        navigateToAction(actionId) {
            this.$inertia.visit(`/actions/${actionId}`);
        },
        navigateToMachine(machineId) {
            this.$inertia.visit(`/machines/${machineId}`);
        }
    }
}
</script>
<style scoped lang="scss">
.blinking {
    animation: blink 1s ease infinite;
}

@keyframes blink {
    0% {
        opacity: 1;
    }
    50% {
        opacity: 0;
    }
    100% {
        opacity: 1;
    }
}
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
.flex2 {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 5px;
}
.rounded{
    border-radius: 25px;
}
.orange{
    color: #EF6C00;
}
.red {
    color: $red;
}
.badge-container {
    position: relative;
}

.secondary-badge{
    position: absolute;
    top: 20px;
    right: -15px;
    transform: translate(50%, -50%);
}
.onhold-badge{
    position: absolute;
    top:53px;
    right:-27px;
    transform: translate(50%, -50%);
}
.rush-badge{
    position: absolute;
    top:86px;
    right:-26px;
    transform: translate(50%, -50%);
}
</style>


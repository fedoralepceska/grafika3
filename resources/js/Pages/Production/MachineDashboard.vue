<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <div class="flex justify-between">
                <Header title="Machines" subtitle="Dashboard" icon="machines.png" link="machines"/>
                <div class="flex pt-4">
                    <div class="flex gap-2 pt-3">
                        <button class="btn"><ViewMachinesDialog :machinesCut="jobMachinesCut" :machinesPrint="jobMachinesPrint"/></button>
                        <button class="btn"><AddMachineDialog /></button>
                    </div>
                </div>
            </div>
            <div class="grid-container">
                <div v-for="item in jobMachinesPrint" :key="item.name" class="grid-item bg" @click="navigateToMachine(item.name)" >
                    <div class="machine">
                        <div class="machineName text-white">
                            {{ item.name }}
                        </div>
                        <div class="machineInfo">
                            <div>{{ $t('totalActiveJobs') }}: {{item.total}}</div>
                            <div>{{ $t('totalPendingJobs') }}: {{item.secondaryCount}}</div>
                            <div>{{ $t('totalJobsOnHold') }}: {{item.onHoldCount}}</div>
                            <div v-if="item.onRushCount" class="red blinking">{{ $t('highPriorityJobs') }}: {{item.onRushCount}}</div>
                        </div>
                    </div>
                    <div class="status">
                        <div v-if="item.total > 0" class="status-circle" style="background-color: #408a0b;">
                            {{ $t('online') }}
                        </div>
                        <div v-else class="status-circle" style="background-color: #9e2c30;">
                            {{ $t('offline') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>


<script>
import axios from 'axios';
import MainLayout from "@/Layouts/MainLayout.vue";
import Header from "@/Components/Header.vue";
import AddMachineDialog from "@/Components/AddMachineDialog.vue";
import ViewMachinesDialog from "@/Components/ViewMachinesDialog.vue";
import TabsWrapperV2 from "@/Components/tabs/TabsWrapperV2.vue";
import TabV2 from "@/Components/tabs/TabV2.vue";

export default {
    components: {
        TabV2,
        TabsWrapperV2,
        MainLayout,
        Header,
        AddMachineDialog,
        ViewMachinesDialog
    },
    data() {
        return {
            jobMachinesCut: {},
            jobMachinesPrint: {},
            selectedMachineType: 'printing',
        };
    },
    created() {
        this.fetchJobMachinesCounts();
    },
    methods: {
        async fetchJobMachinesCounts() {
            try {
                const printResponse = await axios.get('/job-machine-print-counts');
                const cutResponse = await axios.get('/job-machine-cut-counts');

                this.jobMachinesPrint = printResponse.data;
                this.jobMachinesCut = cutResponse.data;
            } catch (error) {
                console.error(error);
            }
        },
        navigateToMachine(machineId) {
            this.$inertia.visit(`/actions/${machineId}`);
        },
        selectMachineType(type) {
            this.selectedMachineType = type;
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
.type{
    border: 2px $green solid;
    border-radius: 3px;
    margin-left: 5px;
    color: white;
    transition: background-color 0.3s;
    cursor: pointer;
}
.type:hover{
    background-color: #3c4e59;
}
.bg{
    background-color: $background-color;
}
.status-circle{
    width: 75px;
    height: 75px;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    color: white;
}
.machineName{
    font-size: 25px;
    font-weight: bold;
}
.machineInfo{
    font-size: 14px;
    font-weight: lighter;
}
.machine{
    text-align: left;
    color: #9ca3af;
}
.status{
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-left: 40px;
}
.grid-item {
    cursor: pointer;
    padding: 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 0;
    width: 400px;
    height: 180px;
    transition: background-color 0.3s;
}
.grid-item:hover{
    background-color: $gray;
}

.light-gray{
    background-color: $light-gray;
}
.selected-type {
    background-color: $dark-gray;
}
.red {
    color: $red;
}
.btn {
    padding: 9px 12px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    border-radius: 2px;
}

</style>


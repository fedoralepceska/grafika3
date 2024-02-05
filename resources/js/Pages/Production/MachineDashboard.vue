<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="production" subtitle="Machines" icon="dashboard.png" link="machines"/>
              <div class="flex">
                <div class="text-white">
                    Select machine type
                </div>
                <div class="flex px-3">
                    <div @click="selectMachineType('printing')" :class="{ 'selected-type': selectedMachineType === 'printing' }" class="type light-gray px-3">Printing <span class="mdi mdi-printer-outline"></span></div>
                    <div @click="selectMachineType('cutting')" :class="{ 'selected-type': selectedMachineType === 'cutting' }" class="type light-gray px-3">Cutting <span class="mdi mdi-box-cutter"></span></div>
                </div>
              </div>
<!--
            PrintingMachines
-->
            <div v-if="selectedMachineType==='printing'" class="grid-container">
                <div v-for="item in jobMachinesPrint" :key="item.name" class="grid-item bg" @click="navigateToMachine(item.name)" >
                    <div class="machine">
                        <div class="machineName text-white">
                            {{ $t(`machinePrint.${item.name}`) }}
                        </div>
                        <div class="machineInfo">
                            <div>Total active jobs: {{item.total}}</div>
                            <div>Total pending jobs: {{item.secondaryCount}}</div>
                            <div>Total jobs ON HOLD: {{item.onHoldCount}}</div>
                        </div>
                    </div>
                    <div class="status">
                        <div v-if="item.total > 0" class="status-circle" style="background-color: #408a0b;">
                            Online
                        </div>
                        <div v-else class="status-circle" style="background-color: #9e2c30;">
                            Offline
                        </div>
                    </div>
                </div>
            </div>
<!--
            Cutting Machines
-->
            <div v-if="selectedMachineType==='cutting'" class="grid-container">

            </div>
        </div>
    </MainLayout>
</template>


<script>
import axios from 'axios';
import MainLayout from "@/Layouts/MainLayout.vue";
import Header from "@/Components/Header.vue";
import TabsWrapper from "@/Components/tabs/TabsWrapper.vue";
import Tab from "@/Components/tabs/Tab.vue";
import TabsWrapperV2 from "@/Components/tabs/TabsWrapperV2.vue";
import TabV2 from "@/Components/tabs/TabV2.vue";

export default {
    components: {
        TabV2,
        TabsWrapperV2,
        MainLayout,
        Header,
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
                const response = await axios.get('/job-machine-counts');
                this.jobMachinesPrint = response.data?.machinePrintCounts;
                this.jobMachinesCut = response.data?.machineCutCounts;
            } catch (error) {
                console.error(error);
            }
        },
        navigateToMachine(machineId) {
            this.$inertia.visit(`/machines/${machineId}`);
        },
        selectMachineType(type) {
            this.selectedMachineType = type;
        }
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

</style>


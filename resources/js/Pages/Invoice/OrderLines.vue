<template>
    <table class="border" v-if="$props.jobs?.length > 0">
        <tbody>
        <template v-if="updatedJobs.length === 0">
            <tr v-for="(job, index) in $props.jobs" :key="index">
                <!--FILE INFO BEFORE SYNCING-->
                <div class=" text-white">
                    <td class="text-black bg-gray-200 font-weight-black">#{{ index + 1 }}</td>
                    <td> Name: <span class="bold">{{ job.file }}</span></td>
                    <td>ID: <span class="bold">{{ job.id }}</span></td>

                    <td>{{ $t('width') }}: <span class="bold">{{ job.width.toFixed(2) }}mm</span></td>
                    <td>{{ $t('height') }}: <span class="bold">{{ job.height.toFixed(2) }}mm</span></td>
                    <td>{{$t('Quantity')}}: <span class="bold">{{ job.quantity }}</span></td>
                    <td>{{$t('Copies')}}: <span class="bold">{{ job.copies }}</span></td>
                </div>


                <div class="flex text-white">
                    <td><img :src="getImageUrl(job.id)" alt="Job Image" class="jobImg thumbnail" /></td>

                </div>

                <div class="flex">
                    <td class="flex items-center bg-gray-200 text-black">
                        <img src="/images/shipping.png" class="w-10 h-10 pr-1" alt="Shipping">
                        {{ $t('Shipping') }}: </td>
                </div>

            </tr>
        </template>
        <template v-else>
            <tr v-for="(job, index) in updatedJobs" :key="index">
                <!--ORDER INDEX, NAME AND ADDITIONAL INFO-->
                <div class="text-white">
                    <td class="text-black bg-gray-200 font-weight-black "><span class="bold">#{{ index + 1 }}</span></td>
                    <td> Name: <span class="bold">{{ job.file }}</span></td>
                    <td>ID: <span class="bold">{{ job.id }}</span></td>
                    <td>{{ $t('width') }}: <span class="bold">{{ job.width }}</span> </td>
                    <td>{{ $t('height') }}: <span class="bold">{{ job.height }}</span></td>
                    <td>{{$t('Quantity')}}: <span class="bold">{{ job.quantity }}</span></td>
                    <td>{{$t('Copies')}}: <span class="bold">{{ job.copies }}</span></td>
                </div>

                <!--FILE INFO-->
                <div class="flex text-white">
                    <td><img :src="getImageUrl(job.id)" alt="Job Image" class="jobImg thumbnail" /></td>
                    <td>
                        <div v-if="job.machinePrint">
                            {{  $t('machineP') }} : <span class="bold"> {{$t(`machinePrint.${job.machinePrint}`) }}</span>
                        </div>
                    </td>
                    <td>
                        <div v-if="job.machineCut">
                            {{  $t('machineC') }} : <span class="bold"> {{$t(`machineCut.${job.machineCut}`) }}</span>
                        </div>
                    </td>
                    <td>
                        <div v-if="job.materials">
                            {{  $t('materialLargeFormat') }} : <span class="bold"> {{$t(`materials.${job.materials}`) }}</span>
                        </div>
                        <div v-if="job.materialsSmall">
                            {{  $t('materialSmallFormat') }} : <span class="bold"> {{$t(`materialsSmall.${job.materialsSmall}`) }}</span>
                        </div>
                    </td>
                </div>
                <!-- ACTIONS -->
                <div>
                    <div class="pl-20 pr-14" v-if="actions(job.id)">
                        <div class="jobInfo mt-3 mb-5 bg-gray-800">
                            <div class="green p-1 pl-1 text-white bg-gray-700" @click="toggleActions" style="cursor: pointer">
                                {{$t('ACTIONS')}}
                                <button class="toggle-button" >&#9207;</button>
                            </div>
                            <transition name="slide-fade">
                                <div v-if="showActions" class="ultra-light-green text-white pl-1 pt-1 pb-1">
                                    <div v-for="(action,index) in actions(job.id)" :key="action">
                                        <span>{{index+1}}. <strong>{{ $t(`actions.${action}`) }}</strong></span>
                                    </div>
                                </div>
                            </transition>
                        </div>
                    </div>
                    <template v-else>
                        <span></span>
                    </template>
                </div>
                <!--SHIPPING INFO-->
                <div class="flex">
                    <td class="flex items-center bg-gray-200 text-black">
                        <img src="/images/shipping.png" class="w-10 h-10 pr-1" alt="Shipping">
                        {{ $t('Shipping') }}:  <strong> {{ job.shippingInfo }}</strong></td>
                    <div class="jobPriceInfo absolute right-0 bottom-0 bg-white text-black bold">
                        <div class="p-2">
                            {{$t('jobPrice')}}: <span class="bold">{{job.totalPrice.toFixed(2)}} ден.</span>
                        </div>
                    </div>
                </div>
            </tr>
        </template>
        </tbody>
    </table>
</template>

<script>
export default {
    name: "OrderLines",

    props: {
        jobs: Array,
        updatedJobs: Array
    },

    data() {
        return {
            showActions: false
        }
    },

    methods: {
        getImageUrl(id) {
            return `/storage/uploads/${this.$props.jobs.find(j => j.id === id).file}`
        },

        toggleActions() {
            this.showActions = !this.showActions;
        },

        actions(id) {
            const job = this.updatedJobs.find(job => job.id === id);
            console.log(job)
            // Check if the job exists
            if (job) {
                const jobActions = job.actions;
                console.log(jobActions.map(action => action.name));
                if (jobActions && jobActions.length > 0) {
                    return jobActions.map(action => action.name);
                }
            }
            else return false; // Return a default value if there are no actions for the job
        },
    }
}
</script>

<style scoped lang="scss">
input[data-v-81b90cf3], select[data-v-81b90cf3]{
    width: 25vh;
    border-radius: 3px;
}
.bold {
    font-weight: bolder;
}

.slide-fade-enter-active, .slide-fade-leave-active {
    transition: max-height 0.5s ease-in-out;
}

.slide-fade-enter-to, .slide-fade-leave-from {
    overflow: hidden;
    max-height: 1000px;
}
.slide-fade-enter-from, .slide-fade-leave-to {
    overflow: hidden;
    max-height: 0;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

th, td {

    padding: 10px;
    text-align: center;

}
tr {
    margin-bottom: 5px;
    border: 1px solid white;
}
th {

    background-color: #f0f0f0;
}

.jobImg {
    width: 60px;
    height: 60px;
    margin: 0 auto;
    display: flex;
}
.thumbnail {
    top:-50px;
    left:-35px;
    display:block;
    z-index:999;
    cursor: pointer;
    -webkit-transition-property: all;
    -webkit-transition-duration: 0.3s;
    -webkit-transition-timing-function: ease;
}
.thumbnail:hover {
    transform: scale(4);
}

input, select {
    height: 36px;
}

.text {
    display: flex;
    flex-direction: column;
    padding: 10px;
}
</style>

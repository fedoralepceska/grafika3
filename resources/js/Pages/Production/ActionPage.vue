<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="action" subtitle="actionInfo" icon="List.png"/>
            <div class="grid-container">
                Jobs: {{jobs}} <br>
                Invoices: {{invoices}} <br>
                Action: {{id}} <br>
            </div>
            <div v-for="(invoice,index) in invoices" class="main">
                <div class="container flex gap-20 relative p-2">
                <div class="bg-white text-black bold p-3" style="min-width: 20vh"><strong>{{invoice.invoice_title}}</strong></div>
                <div class="info">
                    <div>Order</div>
                    <div class="bold">#{{ invoice.id }}</div>
                </div>
                <div class="info">
                    <div>Customer</div>
                    <div class="bold">{{invoice.client_id}}</div>
<!--
                    The Clients name should be fetched #TODO
-->
                </div>
                <div class="info">
                    <div>{{ $t('End Date') }}</div>
                    <div class="bold">{{ invoice?.end_date }}</div>
                </div>
                <div class="info">
                    <div>Created By</div>
                    <div class="bold">{{ invoice.created_by }}</div>
<!--
                    The users name should be fetched #TODO
-->
                </div>
                <div class="info">
                    <div>Current Step</div>
                    <div class="bold">{{$t(`actions.${actionId}`)}}</div>
                </div>
                <div class="btns">
                    <div class="bt" @click="viewJobs(index)"><i class="fa-solid fa-bars"></i></div>
                </div>
                </div>
                <div v-if="jobViewMode===index">
                    <table>
                        <thead>
                            <tr>
                                <th>LN</th>
                                <th>Img</th>
                                <th>Qty</th>
                                <th>Copies</th>
                                <th>Height</th>
                                <th>Width</th>
                                <th>Print</th>
                                <th>Cut</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody v-for="(job, jobIndex) in jobs" :key="jobIndex">
<!--
                        Bi trebalo da e invoice.jobs za da gi dava samo za toj invoice #TODO
-->
                            <tr v-if="invoice.comment && !acknowledged">
                                <td colspan="9" class="orange">
                                    <button @click="openModal">
                                    <i class="fa-solid fa-arrow-down"></i>
                                    Read Notes before you can process this
                                    <i class="fa-solid fa-arrow-down"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr :class="{
                                'orange2' :  invoices.comment !== null && !acknowledged
                            }">
                                <td class="bg-white !text-black"><strong>#{{jobIndex+1}}</strong></td>
                                <td class="flex">
                                    <img :src="getImageUrl(job.id)" alt="Job Image" class="jobImg thumbnail"/>
                                    {{job.file}}</td>
                                <td>{{job.quantity}}</td>
                                <td>{{job.copies}}</td>
                                <td>{{job.height}}</td>
                                <td>{{job.width}}</td>
                                <td>{{$t(`machinePrint.${job.machinePrint}`)}}</td>
                                <td>{{$t(`machineCut.${job.machineCut}`)}}</td>
                                <td>
                                    <button class="bg-white text-black p-2 rounded mr-2"><strong>Start job <i class="fa-regular fa-clock"></i>0min </strong></button>
                                    <button class="red p-2 rounded" :disabled="true"><strong>End job</strong></button>
                                </td>
                            </tr>

                        </tbody>
                    </table>
                    <CommentModal
                        v-if="showModal"
                        :comment="invoices[index].comment"
                        :closeModal="closeModal"
                        :acknowledge="acknowledge"
                        :showModal="showModal"
                    />
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script>
import MainLayout from "@/Layouts/MainLayout.vue";
import Header from "@/Components/Header.vue";
import CommentModal from "@/Components/CommentModal.vue";

export default {
    name: 'ActionPage',
    components: {
        MainLayout,
        Header,
        CommentModal
    },
    props: {
        actionId: String
    },
    data() {
        return {
            invoices: [],
            jobs: [],
            id: null,
            jobViewMode: null,
            showModal: false,
            acknowledged: false
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
        },
        viewJobs(index) {
            this.jobViewMode = this.jobViewMode === index ? null : index;
        },
        getImageUrl(id) {
            return `/storage/uploads/${this.jobs.find(j => j.id === id).file}`
        },
        openModal(index) {
            this.showModal = true;
            this.selectedInvoiceIndex = index;
        },

        closeModal() {
            this.showModal = false;
        },

        acknowledge() {
            // Should we update the invoice.comment to be null or no ? #TODO
            this.showModal = false;
            this.acknowledged = true;
            this.$set(this.invoices[this.selectedInvoiceIndex], "comment", null);
        },
    }
}
</script>
<style scoped lang="scss">
.main{
    background-color: $light-gray;
}
.info{
    color: white;
}
.red{
    background-color: $red;
}
.orange{
    background-color: orange;
}
.orange2 {
    background-color: rgba(255, 167, 38, 0.6); /* Adjust the alpha value as needed */
}
.container{
    background-color: $light-gray;
    margin-bottom:10px
}
.btns{
    position: absolute;
    padding-right: 10px;
    right: 0;
}
.bold{
    font-weight: bolder;
}
.bt {
    font-size: 30px;
    cursor: pointer;
    padding: 0;
    color: white;
}
.jobImg {
    width: 45px;
    height: 45px;
}
table{
    width: 100%;
    background-color: $light-gray;
    margin-bottom: 10px;
}
table, th, td{
    border: 1px solid $ultra-light-gray;
    color: white;
    align-items: center;
    justify-content: center;
    text-align: center;
}
td{
    padding-top: 10px;
    padding-bottom: 10px;
}
</style>

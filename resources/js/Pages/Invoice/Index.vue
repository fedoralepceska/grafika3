<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="invoice" subtitle="ViewAllInvoices" icon="List.png" link="orders"/>
            <div class="dark-gray p-2 text-white">
                <div class="form-container p-2 ">
                    <h2 class="sub-title">
                        {{ $t('listOfAllOrders') }}
                    </h2>
                    <div class="filter-container flex gap-4 pb-10">
                        <div class="search flex gap-2">
                            <input v-model="searchQuery" placeholder="Enter order number or order name" class="text-black" style="width: 50vh; border-radius: 3px" @keyup.enter="searchInvoices" />
                            <button class="btn create-order1" @click="searchInvoices">Search</button>
                        </div>
                        <div class="flex gap-2">
                        <div class="status">
                            <label class="pr-3">Filter orders</label>
                            <select v-model="filterStatus" class="text-black" >
                                <option value="All" hidden>Status</option>
                                <option value="All">All</option>
                                <option value="Not started yet">Not started yet</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Completed">Completed</option>
                            </select>
                        </div>
                        <div class="client">
                            <select v-model="filterClient" class="text-black">
                                <option value="All" hidden>Clients</option>
                                <option value="All">All Clients</option>
                                <option v-for="client in uniqueClients" :key="client">{{ client.name }}</option>
                            </select>
                        </div>
                        <div class="date">
                            <select v-model="sortOrder" class="text-black">
                                <option value="desc" hidden>Date</option>
                                <option value="desc">Newest to Oldest</option>
                                <option value="asc">Oldest to Newest</option>
                            </select>
                        </div>
                            <button @click="applyFilter" class="btn create-order1">Filter</button>

                        </div>
                        <div class="button flex gap-3">
                            <button @click="navigateToCreateOrder" class="btn create-order">
                                Create Order <i class="fa fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div v-if="invoices.data">
                        <div class="border mb-2" v-for="invoice in invoices.data" :key="invoice.id">
                            <div class="bg-white text-black flex justify-between" style="line-height: normal">
                                <div class="p-2 bold" style="font-size: 16px">{{invoice.invoice_title}}</div>
                                <div class="flex" style="font-size: 12px">
                                    <button class="flex items-center p-1" @click="viewJobs(invoice.id)">
                                        <i v-if="iconStates[invoice.id]" class="fa-solid fa-angles-up bg-gray-300 p-2 rounded" aria-hidden="true"></i>
                                        <i v-else class="fa-solid fa-angles-down bg-gray-300 p-2 rounded" aria-hidden="true"></i>
                                    </button>
                                    <button class="flex items-center p-1" @click="viewInvoice(invoice.id)">
                                    <i class="fa fa-eye bg-gray-300 p-2 rounded" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="flex gap-40 pl-2 pt-1" style="line-height: initial">
                                <div class="info">
                                    <div>Order</div>
                                    <div class="bold">#{{invoice.id}}</div>
                                </div>
                                <div class="info min-w-80 no-wrap">
                                    <div>Customer</div>
                                    <div class="bold ellipsis">{{ invoice.client.name }}</div>
                                </div>
                                <div class="info">
                                    <div>End Date</div>
                                    <div  class="bold">{{invoice.end_date}}</div>
                                </div>
                                <div class="info">
                                    <div>Created By</div>
                                    <div  class="bold">{{ invoice.user.name }}</div>
                                </div>
                                <div class="info">
                                    <div>Status</div>
                                    <div :class="getStatusColorClass(invoice.status)" class="bold" >{{invoice.status}}</div>
                                </div>
                                <div v-if="invoice.LockedNote" class="info locked">
                                    <ViewLockDialog :invoice="invoice"/>
                                </div>
                            </div>
                                <div v-if="currentInvoiceId" class="job-details-container" :class="{ active: currentInvoiceId === invoice.id }">
                                    <div v-if="currentInvoiceId===invoice.id" class="bgJobs text-white p-2 bold" style="line-height: normal;">
                                        Jobs for Order #{{invoice.id}} {{invoice.invoice_title}}
                                    </div>
                                    <div v-if="currentInvoiceId===invoice.id" class="jobInfo border-b" v-for="(job,index) in invoice.jobs">
                                            <div class=" jobInfo flex justify-between gap-1">
                                            <div class="text-white bold p-1 ellipsis-file">
                                                #{{index+1}} 
                                                <template v-if="hasMultipleFiles(job)">
                                                    {{ getJobFiles(job).length }} files
                                                </template>
                                                <template v-else-if="hasSingleNewFile(job)">
                                                    {{ getFileName(getJobFiles(job)[0]) }}
                                                </template>
                                                <template v-else>
                                                    {{job.file}}
                                                </template>
                                            </div>
                                            <div class="p-1 img">
                                                <!-- Multiple files preview (2 or more files) -->
                                                <template v-if="hasMultipleFiles(job)">
                                                    <div class="multiple-files-preview" @click="toggleFilesModal(job, index)">
                                                        <div class="files-placeholder">
                                                            <i class="fa fa-files-o"></i>
                                                            <span class="files-count">+{{ getJobFiles(job).length }}</span>
                                                        </div>
                                                    </div>
                                                </template>
                                                <!-- Single file preview (new system with 1 file) -->
                                                <template v-else-if="hasSingleNewFile(job)">
                                                    <img :src="getThumbnailUrl(job.id, 0)" alt="Job Image" class="multiple-files-preview jobImg thumbnail" @click="openFilePreview(job, 0)" @error="handleThumbnailError($event, 0)"/>
                                                </template>
                                                <!-- Legacy single file preview -->
                                                <template v-else>
                                                    <img :src="getImageUrl(job.id)" alt="Job Image" class="jobImg thumbnail" @click="openSingleFileModal(job)"/>
                                                </template>
                                            </div>
                                            <div class="p-1 w-150">{{$t('Height')}}: <span class="bold">{{job.height.toFixed(2)}}</span> </div>
                                            <div class="p-1 w-150">{{$t('Width')}}: <span class="bold">{{job.width.toFixed(2)}}</span> </div>
                                            <div class="p-1 w-150">{{$t('Quantity')}}: <span class="bold">{{job.quantity}}</span> </div>
                                            <div class="p-1 w-150">{{$t('Copies')}}: <span class="bold">{{job.copies}}</span> </div>
                                            </div>
                                        <div class="ultra-light-gray pt-4">
                                        <OrderJobDetails :job="job"/>
                                        </div>
                                    </div>
                                </div>
                        </div>
                    </div>
                </div>
                <Pagination :pagination="invoices"/>
            </div>

            <!-- Multiple Files Modal -->
            <div v-if="showFilesModal" class="files-modal" @click="closeFilesModal">
                <div class="files-modal-content" @click.stop>
                    <div class="files-modal-header">
                        <h3>Files for Job #{{ currentJobIndex + 1 }}</h3>
                        <button @click="closeFilesModal" class="close-btn">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                    <div class="files-modal-body">
                        <div class="thumbnail-grid">
                            <div 
                                v-for="(file, fileIndex) in getJobFiles(currentJob)" 
                                :key="fileIndex" 
                                class="thumbnail-item"
                                @click="openFilePreview(currentJob, fileIndex)"
                            >
                                <img 
                                    :src="getThumbnailUrl(currentJob.id, fileIndex)" 
                                    :alt="`File ${fileIndex + 1}`" 
                                    class="thumbnail-image"
                                    @error="handleThumbnailError($event, fileIndex)"
                                />
                                <div class="thumbnail-number">{{ fileIndex + 1 }}</div>
                                <div class="file-name">{{ getFileName(file) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Single File Modal -->
            <div v-if="showSingleFileModal" class="single-file-modal" @click="closeSingleFileModal">
                <div class="single-file-modal-content" @click.stop>
                    <div class="single-file-modal-header">
                        <h3>Job File Preview</h3>
                        <button @click="closeSingleFileModal" class="close-btn">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                    <div class="single-file-modal-body">
                        <img :src="getImageUrl(currentSingleJob?.id)" :alt="currentSingleJob?.file" class="single-file-image"/>
                    </div>
                </div>
            </div>

            <!-- PDF Preview Modal -->
            <div v-if="showPdfModal" class="pdf-modal" @click="closePdfModal">
                <div class="pdf-modal-content" @click.stop>
                    <div class="pdf-modal-header">
                        <h3>File {{ currentFileIndex + 1 }} of {{ getJobFiles(currentJob).length }}</h3>
                        <button @click="closePdfModal" class="close-btn">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                    <div class="pdf-modal-body">
                        <iframe 
                            v-if="currentJob && currentFileIndex !== null"
                            :src="getOriginalFileUrl(currentJob.id, currentFileIndex)" 
                            class="pdf-iframe"
                        ></iframe>
                    </div>
                    <div class="pdf-modal-footer" v-if="getJobFiles(currentJob).length > 1">
                        <button 
                            @click="previousFile" 
                            :disabled="currentFileIndex === 0"
                            class="nav-btn"
                        >
                            <i class="fa fa-chevron-left"></i> Previous
                        </button>
                        <span class="file-counter">{{ currentFileIndex + 1 }} / {{ getJobFiles(currentJob).length }}</span>
                        <button 
                            @click="nextFile" 
                            :disabled="currentFileIndex === getJobFiles(currentJob).length - 1"
                            class="nav-btn"
                        >
                            Next <i class="fa fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script>
import MainLayout from "@/Layouts/MainLayout.vue";
import Header from "@/Components/Header.vue";
import Pagination from "@/Components/Pagination.vue"
import axios from 'axios';
import {reactive} from "vue";
import OrderJobDetails from "@/Pages/Invoice/OrderJobDetails.vue";
import ViewLockDialog from "@/Components/ViewLockDialog.vue";

export default {
    components: {Header, MainLayout,Pagination,OrderJobDetails, ViewLockDialog },
    props:{
      invoices:Object,
    },
    data() {
        return {
            searchQuery: '',
            filterStatus: 'All',
            filterClient: 'All',
            sortOrder: 'desc',
            localInvoices: [],
            uniqueClients:[],
            currentInvoiceId: null,
            iconStates : reactive({}),
            showFilesModal: false,
            showSingleFileModal: false,
            showPdfModal: false,
            currentJob: null,
            currentJobIndex: null,
            currentSingleJob: null,
            currentFileIndex: null,
        };
    },
    mounted() {
        this.localInvoices = this.invoices.data.slice();
        this.fetchUniqueClients()
        this.invoices.data.forEach(invoice => {
            this.iconStates[invoice.id] = false;
        });
    },
    methods: {
        getImageUrl(id) {
            const currentInvoice = this.invoices.data.find(invoice => invoice.id === this.currentInvoiceId);
            if (currentInvoice && currentInvoice.jobs) {
                const job = currentInvoice.jobs.find(j => j.id === id);
                    return `/storage/uploads/${job.file}`;
            }
        },
        viewJobs(invoiceId) {
            if (this.currentInvoiceId && this.currentInvoiceId !== invoiceId) {
                this.iconStates[this.currentInvoiceId] = false;
                this.currentInvoiceId = null;
            }

            // Toggle the icon state for the clicked invoice
            this.currentInvoiceId = this.currentInvoiceId === invoiceId ? null : invoiceId;
            this.iconStates[invoiceId] = !this.iconStates[invoiceId];
        },
        getStatusColorClass(status) {
            if (status === "Not started yet") {
                return "orange";
            } else if (status === "In progress") {
                return "blue-text";
            } else if (status === "Completed") {
                return "green-text";
            }
        },
        async applyFilter() {
            try {
                const response = await axios.get('/orders', {
                    params: {
                        searchQuery: encodeURIComponent(this.searchQuery),
                        status: this.filterStatus,
                        sortOrder: this.sortOrder,
                        client: this.filterClient,
                    },
                });
                this.localInvoices = response.data;
                let redirectUrl = '/orders';
                if (this.searchQuery) {
                    redirectUrl += `?searchQuery=${encodeURIComponent(this.searchQuery)}`;
                }
                if (this.filterStatus) {
                    redirectUrl += `${this.searchQuery ? '&' : '?'}status=${this.filterStatus}`;
                }
                if (this.sortOrder) {
                    redirectUrl += `${this.searchQuery || this.filterStatus ? '&' : '?'}sortOrder=${this.sortOrder}`;
                }
                if (this.filterClient) {
                    redirectUrl += `${this.searchQuery || this.filterStatus || this.sortOrder ? '&' : '?'}client=${this.filterClient}`;
                }

                this.$inertia.visit(redirectUrl);
            } catch (error) {
                console.error(error);
            }
        },
        async searchInvoices() {
            try {
                const response = await axios.get(`?searchQuery=${encodeURIComponent(this.searchQuery)}`);
                this.localInvoices = response.data;
                this.$inertia.visit(`/orders?searchQuery=${this.searchQuery}`);
            } catch (error) {
                console.error(error);
            }
        },
        async fetchUniqueClients() {
            try {
                const response = await axios.get('/unique-clients');
                this.uniqueClients = response.data;
            } catch (error) {
                console.error(error);
            }
        },
        viewInvoice(id) {
            this.$inertia.visit(`/orders/${id}`);
        },
        navigateToCreateOrder(){
            this.$inertia.visit(`/orders/create`);
        },
        hasMultipleFiles(job) {
            // Check if job has originalFile array (new system) and has 2 or more files
            return Array.isArray(job.originalFile) && job.originalFile.length > 1;
        },
        hasSingleNewFile(job) {
            // Check if job has originalFile array (new system) with exactly 1 file
            return Array.isArray(job.originalFile) && job.originalFile.length === 1;
        },
        getJobFiles(job) {
            // Return originalFile array for new system, or create array from legacy file
            if (Array.isArray(job.originalFile)) {
                return job.originalFile;
            }
            return job.file ? [job.file] : [];
        },
        toggleFilesModal(job, index) {
            this.currentJob = job;
            this.currentJobIndex = index;
            this.showFilesModal = true;
        },
        openSingleFileModal(job) {
            this.currentSingleJob = job;
            this.showSingleFileModal = true;
        },
        closeFilesModal() {
            this.showFilesModal = false;
            this.currentJob = null;
            this.currentJobIndex = null;
        },
        closeSingleFileModal() {
            this.showSingleFileModal = false;
            this.currentSingleJob = null;
        },
        closePdfModal() {
            this.showPdfModal = false;
            this.currentJob = null;
            this.currentFileIndex = null;
        },
        openFilePreview(job, fileIndex) {
            this.currentJob = job;
            this.currentFileIndex = fileIndex;
            this.showFilesModal = false;
            this.showPdfModal = true;
        },
        getThumbnailUrl(jobId, fileIndex) {
            return `/jobs/${jobId}/view-thumbnail/${fileIndex}`;
        },
        getOriginalFileUrl(jobId, fileIndex) {
            return `/jobs/${jobId}/view-original-file/${fileIndex}`;
        },
        handleThumbnailError(event, fileIndex) {
            // Show PDF icon as fallback for thumbnail errors
            event.target.src = '/images/pdf.png';
            console.log(`Thumbnail loading failed for file index ${fileIndex}`);
        },
        getFileName(filePath) {
            // Extract filename from path
            if (typeof filePath === 'string') {
                return filePath.split('/').pop() || filePath;
            }
            return 'Unknown file';
        },
        previousFile() {
            if (this.currentFileIndex > 0) {
                this.currentFileIndex--;
            }
        },
        nextFile() {
            if (this.currentFileIndex < this.getJobFiles(this.currentJob).length - 1) {
                this.currentFileIndex++;
            }
        },
    },
};
</script>
<style scoped lang="scss">
.info {

    min-width: 0;
    display: flex;
    flex-direction: column;
    justify-content: center;

}
.filter-container{
    justify-content: space-between;
}
.jobInfo{

    align-items: center;
}
.locked{
    display: flex;
    justify-content: center;
}
.img{
    width: 60px;
    height: 60px;
}

.jobImg {
    cursor: pointer;
}
select{
    width: 25vh;
    border-radius: 3px;
}
.orange{
    color: $orange;
}
.blue-text{
    color: $blue;
}
.bold{
    font-weight: bold;
}
.green-text{
    color: $green;
}
.blue{
    background-color: $blue;
}
.green{
    background-color: $green;
}
.green:hover{
    background-color: green;
}
.header{
    display: flex;
    align-items: center;
}
.bgJobs{
    background-color: $ultra-light-gray;
}
.dark-gray {
    background-color: $dark-gray;
    justify-content: left;
    align-items: center;
    min-height: 20vh;
    min-width: 80vh;
}
.ultra-light-gray{
    background-color: $ultra-light-gray;
}
.sub-title{
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    color: $white;
}

.button-container{
    display: flex;
    justify-content: end;
}
.btn {
    padding: 9px 12px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    border-radius: 2px;
}
.create-order1{
    background-color: $blue;
    color: white;
}
.create-order{
    background-color: $green;
    color: white;
}
.job-details-container {
    max-height: 0;
    opacity: 0;
    overflow: hidden;
    transition: max-height 0.3s ease-in-out, opacity 0.3s ease-in-out;
    will-change: max-height, opacity;
}

.job-details-container.active {
    max-height: 550px;
    opacity: 1;
}
.min-w-80 {
    min-width: 320px;
    flex-shrink: 0;
    display: block;
}
.ellipsis {
    width: 100%;
    text-overflow: ellipsis;
    white-space: nowrap;
    overflow: hidden;
    display: inline-block;
    max-width: 100%;
}
.ellipsis-file {
    text-overflow: ellipsis;
    white-space: nowrap;
    overflow: hidden;
    display: inline-block;
    width: 150px;
}
.w-150 {
    width: 150px;
}

.multiple-files-preview {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #4a5568;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.2s;
    
    &:hover {
        background-color: $light-gray;
    }
}

.files-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    color: white;
    font-size: 12px;
    
    i {
        font-size: 24px;
        margin-bottom: 4px;
    }
}

.files-count {
    font-weight: bold;
    font-size: 10px;
}

/* Files Modal Styles */
.files-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    background-color: rgba(0, 0, 0, 0.8);
    z-index: 1000;
}

.files-modal-content {
    background-color: #2d3748;
    padding: 20px;
    border-radius: 8px;
    width: 70%;
    max-width: 600px;
    max-height: 80vh;
    position: relative;
    color: white;
}

.files-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    border-bottom: 1px solid #4a5568;
    padding-bottom: 10px;

    h3 {
        margin: 0;
        color: white;
    }

    .close-btn {
        background: none;
        border: none;
        color: white;
        font-size: 1.5em;
        cursor: pointer;
        padding: 5px;
        border-radius: 4px;
        
        &:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
    }
}

.files-modal-body {
    overflow-y: auto;
    max-height: 50vh;
}

.thumbnail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 15px;
    padding: 10px 0;
}

.thumbnail-item {
    position: relative;
    cursor: pointer;
    text-align: center;
    background-color: #4a5568;
    border-radius: 8px;
    padding: 10px;
    transition: transform 0.2s;

    &:hover {
        transform: scale(1.05);
        background-color: $light-gray;
    }
}

.thumbnail-image {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 4px;
    margin-bottom: 8px;
}

.thumbnail-number {
    position: absolute;
    top: 12px;
    right: 12px;
    background-color: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
}

.file-name {
    font-size: 11px;
    color: #ffffff80;
    word-break: break-all;
    margin-top: 4px;
}

/* Single File Modal Styles */
.single-file-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    background-color: rgba(0, 0, 0, 0.8);
    z-index: 1000;
}

.single-file-modal-content {
    background-color: #2d3748;
    padding: 20px;
    border-radius: 8px;
    width: 60%;
    max-width: 500px;
    position: relative;
    color: white;
}

.single-file-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;

    h3 {
        margin: 0;
        color: white;
    }

    .close-btn {
        background: none;
        border: none;
        color: white;
        font-size: 1.5em;
        cursor: pointer;
        padding: 5px;
        border-radius: 4px;
        
        &:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
    }
}

.single-file-modal-body {
    text-align: center;
}

.single-file-image {
    max-width: 100%;
    max-height: 60vh;
    border-radius: 4px;
}

/* PDF Modal Styles */
.pdf-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    background-color: rgba(0, 0, 0, 0.8);
    z-index: 1000;
}

.pdf-modal-content {
    background-color: #2d3748;
    padding: 20px;
    border-radius: 8px;
    width: 90%;
    max-width: 900px;
    max-height: 90vh;
    position: relative;
    color: white;
}

.pdf-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    border-bottom: 1px solid #4a5568;
    padding-bottom: 10px;

    h3 {
        margin: 0;
        color: white;
    }

    .close-btn {
        background: none;
        border: none;
        color: white;
        font-size: 1.5em;
        cursor: pointer;
        padding: 5px;
        border-radius: 4px;
        
        &:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
    }
}

.pdf-modal-body {
    height: 60vh;
    position: relative;
}

.pdf-iframe {
    width: 100%;
    height: 100%;
    border: none;
    border-radius: 4px;
}

.pdf-modal-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 15px;
    padding-top: 2px;

    .nav-btn {
        background-color: #4a5568;
        border: none;
        color: white;
        font-size: 1em;
        cursor: pointer;
        padding: 8px 16px;
        border-radius: 4px;
        transition: background-color 0.2s;

        &:hover:not(:disabled) {
            background-color: #2d3748;
        }

        &:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
    }

    .file-counter {
        margin: 0 10px;
        font-weight: bold;
        color: white;
    }
}
</style>


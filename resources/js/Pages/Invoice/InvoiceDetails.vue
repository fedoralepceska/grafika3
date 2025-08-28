<template>
    <MainLayout>
        <div class="pl-7 pr-7 flex">
            <div class="sidebar" v-if="isSidebarVisible">
                <!-- Your sidebar content goes here -->
                <button @click="toggleSidebar" class="close-sidebar">
                    <span class="mdi mdi-close"></span>
                </button>
                <div class="right-column">
                    <div class="order-history">
                        <OrderHistory :invoice="invoice"/>
                    </div>
                </div>
            </div>
            <div class="left-column flex-1" style="width: 25%">
                <div class="flex justify-between">
                    <Header title="invoice" subtitle="InvoiceReview" icon="List.png" link="orders"/>
                    <div class="flex pt-4">
                        <div class="buttons pt-3">
                            <button class="btn go-back" @click="goBack">Go Back <span class="mdi mdi-arrow-left"></span></button>
                            <button class="btn download-order" @click="downloadAllProofs">Download All Proofs <span class="mdi mdi-cloud-download"></span></button>
                            <button v-if="!invoice.LockedNote" class="btn"><AddLockNoteDialog :invoice="invoice"/></button>
                            <button v-if="invoice.LockedNote" class="btn lock-order" @click="unlockOrder(invoice.id)">Unlock Order <span class="mdi mdi-lock-open"></span></button>
                            <button class="btn re-order"  @click="reorder()">Re-Order <span class="mdi mdi-content-copy"></span></button>
                            <button class="btn go-to-steps" @click="navigateToAction()">Go To Steps <span class="mdi mdi-arrow-right-bold-outline"></span> </button>
                            <button v-if="!isSidebarVisible" @click="toggleSidebar" class="hamburger">
                                <span class="mdi mdi-menu"></span>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="flex pb-2 justify-end">
                    <label class="btn2"><span class="mdi mdi-image"></span> Revised Art Complete <input type="checkbox" class="blue border-white text-amber" v-model="revisedArtCompleteChecked"></label>
                    <label class="btn2"><span class="mdi mdi-fire"></span> RUSH <input type="checkbox" class="blue border-white text-amber" v-model="rushChecked"></label>
                    <label class="btn2"><span class="mdi mdi-pause"></span> ON HOLD <input type="checkbox" class="blue border-white text-amber" v-model="onHoldChecked"></label>
                    <label class="btn2"><span class="mdi mdi-thumb-up-outline"></span> Must Be Perfect <input type="checkbox" class="blue border-white text-amber" v-model="mustBePerfectChecked"></label>
                    <label class="btn2"><span class="mdi mdi-box-cutter"></span> Rip First <input type="checkbox" class="blue border-white text-amber" v-model="ripFirstChecked"></label>
                    <label class="btn2"><span class="mdi mdi-image"></span> Revised Art <input type="checkbox" class="blue border-white text-amber" v-model="revisedArtChecked"></label>
                    <label class="btn2"><span class="mdi mdi-image"></span> Additional Art <input type="checkbox" class="blue border-white text-amber" v-model="additionalArtChecked"></label>
                    <label class="btn2"><span class="mdi mdi-flag-outline"></span> Flags <input type="checkbox" class="blue border-white text-amber"></label>
                </div>
                <div class="dark-gray p-5 text-white">
                    
                        <div class="flex gap-2 ">
                         <div class="invoice-title rounded-t-md bg-white text-black bold p-3 w-fit ">{{ invoice?.invoice_title }}</div>
                         <div class="flex items-end">
                             <div class="status-badge" :style="{ background: getStatusColor }">
                                 {{ invoice?.status }}
                             </div>
                         </div>
                         <div class="flex gap-1 items-end">
                        <div v-if="invoice.perfect" class="ticket-note-perfect">Must Be Perfect</div>
                        <div v-if="invoice.onHold" class="ticket-note-hold">On Hold</div>
                        <div v-if="invoice.revisedArt" class="ticket-note-revisedArt">Revised Art</div>
                        <div v-if="invoice.ripFirst" class="ticket-note-ripFirst">Rip First</div>
                        <div v-if="invoice.revisedArtComplete" class="ticket-note-revisedArtComplete">Revised Art Complete</div>
                        <div v-if="invoice.rush" class="ticket-note-rush">Rush</div>
                        <div v-if="invoice.additionalArt" class="ticket-note-additionalArt">Additional Art</div>
                    </div>
                        </div>
                    
                    <div class="form-container p-2 light-gray" :style="invoice.perfect ? { 'background-color': '#d88f0b' } : {}">
                        <div class="InvoiceDetails">
                            <div class="invoice-details flex gap-5 relative" >
                                <div class="info">
                                    <div>Order</div>
                                    <div class="bold">#{{ invoice?.id }}</div>
                                </div>
                                <div class="info">
                                    <div>Customer</div>
                                    <div class="bold">{{ invoice.client.name }}</div>
                                </div>
                                <div class="info">
                                    <div>{{ $t('Start Date') }}</div>
                                    <div class="bold">{{ invoice?.start_date }}</div>
                                </div>
                                <div class="info">
                                    <div>{{ $t('End Date') }}</div>
                                    <div class="bold">{{ invoice?.end_date }}</div>
                                </div>
                                <div class="info">
                                    <div>Created By</div>
                                    <div class="bold">{{ invoice.user.name }}</div>
                                </div>
                                <div class="btns flex gap-2">
                                    <div class="bt"><i class="fa-regular fa-pen-to-square"></i></div>
                                    <div class="bt" @click="toggleSpreadsheetMode"
                                         :class="{'text-white': spreadsheetMode, 'green-text': !spreadsheetMode}"
                                    ><i class="fa-solid fa-table"></i></div>
                                    <div class="bt" @click="toggleJobProcessMode"
                                        :class="{'text-white': !jobProcessMode, 'green-text': jobProcessMode}"
                                    ><i class="fa-solid fa-list-check"></i></div>
                                    <div class="bt"><i class="fa-regular fa-eye"></i></div>
                                    <AddNoteDialog :invoice="invoice" ref="addNoteDialog" />
                                    <div class="bt"><i class="fa-regular fa-solid fa-file-pdf fa-sm" @click="generatePdf(invoice.id)"></i></div>
                                </div>
                                <AddNoteDialog v-if="openDialog" :invoice="invoice" ref="addNoteDialog" />
                            </div>

                        </div>
                    </div>
                    <div class="form-container  light-gray mt-2">
                        <div class="sub-title pl-2 ">{{$t('OrderLines')}}</div>
                        <div v-for="(job,index) in invoice.jobs" v-if="spreadsheetMode">
                            <div class="jobDetails p-2">
                                <div class="border">
                                    <div class="flex gap-6 items-start">
                                        <div class="invoice-title text-black bold flex flex-col gap-2">
                                            <div class="flex gap-2 bg-[#7DC068] p-3 w-[18rem] truncate">
                                                #{{index+1}} {{job.name}}
                                            </div>
                                            <!-- Cutting Files Preview Buttons -->
                                        <div v-if="job.cuttingFiles && job.cuttingFiles.length > 0" class="p-2 cutting-files-preview flex gap-1 border border-solid border-white rounded-md ml-2 ">
                                            <div class="text-white">Cutting Files:</div>
                                            <button
                                                    v-for="(cuttingFile, cuttingIndex) in job.cuttingFiles"
                                                    :key="cuttingIndex"
                                                    class="cutting-file-btn"
                                                    @click="previewCuttingFile(job.id, cuttingIndex)"
                                                >
                                                    <i class="fa fa-scissors"></i>
                                                    {{ getCuttingFileExtension(cuttingFile).toUpperCase() }} {{ cuttingIndex + 1 }}
                                                    <span class="preview-hint">Preview</span>
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <!-- Dimensions Display with Breakdown Support -->
                                        <div v-if="job.dimensions_breakdown && Array.isArray(job.dimensions_breakdown) && job.dimensions_breakdown.length > 0">
                                            <!-- New system: Show dimensions from breakdown -->
                                            <div v-for="(fileData, fileIndex) in job.dimensions_breakdown" :key="fileIndex" class="dimensions-breakdown">
                                                <div class="file-dimensions-header">
                                                    <span class="file-name">{{ fileData.filename || `File ${fileIndex + 1}` }}</span>
                                                </div>
                                                <div class="dimensions-content">
                                                    <!-- Thumbnail for this specific file -->
                                                    <div class="file-thumbnail">
                                                        <button 
                                                            @click="toggleImagePopover(job, fileIndex)"
                                                            class="thumbnail-btn"
                                                        >
                                                            <img 
                                                                :src="getThumbnailUrl(job.id, fileIndex)" 
                                                                :alt="'Thumbnail ' + (fileIndex + 1)"
                                                                class="jobImg thumbnail"
                                                                @error="handleThumbnailError($event, fileIndex)"
                                                            />
                                                            <span class="thumbnail-number">{{ fileIndex + 1 }}</span>
                                                        </button>
                                                    </div>
                                                    
                                                    <!-- Dimensions info -->
                                                    <div v-if="fileData.page_dimensions && Array.isArray(fileData.page_dimensions)" class="page-dimensions">
                                                        <div v-for="(pageDimension, pageIndex) in fileData.page_dimensions" :key="pageIndex" class="page-dimension flex flex-col">
                                                            <div class="flex gap-2">
                                                                <div>{{$t('Height')}}: <span class="bold">{{ pageDimension.height_mm ? pageDimension.height_mm.toFixed(2) : '0.00' }} mm</span></div>
                                                                <div>{{$t('Width')}}: <span class="bold">{{ pageDimension.width_mm ? pageDimension.width_mm.toFixed(2) : '0.00' }} mm</span></div>
                                                            </div>
                                                            <div v-if="pageDimension.area_m2" class="border-t pt-2">{{$t('Area')}}: <span class="bold text-green-400">{{ pageDimension.area_m2.toFixed(6) }} m²</span></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div v-else>
                                            <!-- Legacy system: Show single dimensions with thumbnail -->
                                            <div class="dimensions-breakdown">
                                                <div class="dimensions-content">
                                                    <!-- Thumbnail for legacy system -->
                                                    <div class="file-thumbnail">
                                                        <button v-if="job.file && job.file !== 'placeholder.jpeg'" @click="toggleImagePopover(job, 0)" class="thumbnail-btn">
                                                            <img :src="getLegacyImageUrl(job)" alt="Job Image" class="jobImg thumbnail"/>
                                                        </button>
                                                        <div v-else class="no-files-placeholder">
                                                            <i class="fa fa-file-o"></i>
                                                            <span>No files</span>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Legacy dimensions -->
                                                    <div class="page-dimensions">
                                                        <div>{{$t('Height')}}: <span class="bold">{{(job.height && typeof job.height === 'number') ? job.height.toFixed(2) : '0.00'}} mm</span></div>
                                                        <div>{{$t('Width')}}: <span class="bold">{{(job.width && typeof job.width === 'number') ? job.width.toFixed(2) : '0.00'}} mm</span></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    
                                        <div class="flex p-2 gap-2 flex-col">
                                            <div class="flex gap-2 flex-col">
                                            <div>Machine Print: <span class="bold">{{job.machinePrint}}</span></div>
                                            <div>Machine Cut: <span class="bold">{{job.machineCut}}</span></div>
                                        </div>
                                            <div>{{$t('Quantity')}}: <span class="bold">{{job.quantity}}</span></div>
                                            <div>{{$t('Copies')}}: <span class="bold">{{job.copies}}</span></div>
                                        </div>
                                    
                                    <div class="flex p-2 gap-2 flex-col">
                                        <!-- Material Type Display (like PDF) -->
                                        <div class="">
                                            {{$t('Material Type')}}:
                                            <span class="bold">
                                            <span v-if="job.articles && job.articles.length > 0">
                                                <span v-for="(article, index) in job.articles" :key="article.id">
                                                    <!-- Show material categories if available, otherwise fall back to material name -->
                                                    <span v-if="article.categories && article.categories.length > 0">
                                                        <span v-for="(category, catIndex) in article.categories" :key="category.id">
                                                            {{ category.name }}
                                                            <span v-if="catIndex < article.categories.length - 1">, </span>
                                                        </span>
                                                    </span>
                                                    <span v-else-if="article.largeFormatMaterial">
                                                        {{ article.largeFormatMaterial.name }}
                                                    </span>
                                                    <span v-else-if="article.smallMaterial">
                                                        {{ article.smallMaterial.name }}
                                                    </span>
                                                    <span v-else>
                                                        {{ article.name }}
                                                    </span>
                                                    <span v-if="index < job.articles.length - 1">, </span>
                                                </span>
                                            </span>
                                            <span v-else-if="job.large_material_id">{{ job.large_material?.name }}</span>
                                            <span v-else>{{ job?.small_material?.name }}</span>
                                         </span>
                                        </div>
                                        

                                        
                                        <!-- Department Display -->
                                        <div>
                                            {{$t('Department')}}:
                                            <span class="bold">
                                                <span v-if="job.articles && job.articles.length > 0">
                                                    <!-- Determine department based on article types -->
                                                    <span v-if="hasLargeAndSmallFormat(job)">Large Format, Small Format</span>
                                                    <span v-else-if="hasLargeFormat(job)">Large Format</span>
                                                    <span v-else-if="hasSmallFormat(job)">Small Format</span>
                                                    <span v-else>Mixed Format</span>
                                                </span>
                                                <span v-else-if="job.small_material">Small Format</span>
                                                <span v-else-if="job.large_material">Large Format</span>
                                                <span v-else>Mixed Format</span>
                                            </span>
                                        </div>
                                        <div>{{$t('totalm')}}<sup>2</sup>: <span class="bold">
                                            <span class="text-green-400" v-if="job.dimensions_breakdown && Array.isArray(job.dimensions_breakdown) && job.dimensions_breakdown.length > 0">
                                                <!-- Calculate total area from dimensions breakdown -->
                                                {{ getJobTotalArea(job).toFixed(6) }}
                                            </span>
                                            <span class="text-green-400" v-else>
                                                {{(job.computed_total_area_m2 && typeof job.computed_total_area_m2 === 'number') ? job.computed_total_area_m2.toFixed(4) : '0.0000'}}
                                            </span>
                                        </span></div>
                                        <div v-if="job.copies > 1">{{$t('Total with Copies')}}: <span class="bold">
                                            {{ (getJobTotalArea(job) * job.copies).toFixed(6) }} m²
                                        </span></div>
                                        </div>
                                   
                                    </div>
                                    <div v-if="jobProcessMode" class="m-6">
                                        <OrderJobDetails :job="job" :invoice-id="invoice.id"/>
                                    </div>
                                    <div class="jobInfo relative pt-14">
                                        <div class="jobShippingInfo" style="line-height: normal">
                                            <div v-if="job.shippingInfo" class="bg-white text-black bold">
                                                <!-- <div class="flex" style="align-items: center;">
                                                    <img src="/images/shipping.png" class="w-10 h-10 pr-1" alt="Shipping">
                                                    {{$t('Shipping')}}
                                                </div> -->
                                                <div class="ultra-light-gray p-2 text-white border border-solid border-white">
                                                    {{$t('shippingTo')}}: <span class="bold">{{job.shippingInfo}}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div v-if="canViewPrice" class="jobPriceInfo bg-white absolute right-0 bottom-0 text-black">
                                            <div>
                                                {{$t('jobPrice')}}: <span class="bold">{{ (Number.isFinite(parseFloat((job.salePrice ?? job.totalPrice ?? job.price)))) ? parseFloat((job.salePrice ?? job.totalPrice ?? job.price)).toFixed(2) : '0.00' }} ден.</span>
                                            </div>
                                            <div>
                                                {{$t('jobPriceCost')}}: <span class="bold">{{ (Number.isFinite(parseFloat((job.price ?? job.totalPrice)))) ? parseFloat((job.price ?? job.totalPrice)).toFixed(2) : '0.00' }} ден.</span>
                                                <button 
                                                    @click="showCostBreakdown(job)" 
                                                    class="info-btn ml-2"
                                                    title="Show cost breakdown"
                                                >
                                                    <i class="fa fa-info-circle"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-center" v-else>
                            <OrderSpreadsheet :invoice="invoice" :canViewPrice="spreadsheetMode ? true : canViewPrice" />
                                                 </div>
                     </div>
                 </div>
             </div>
         </div>

         <!-- Image Popover Modal -->
         <div v-if="showImagePopover" class="popover">
             <div class="popover-content bg-gray-700">
                 <iframe 
                     v-if="selectedJob && selectedFileIndex !== null && hasMultipleFiles(selectedJob)"
                     :src="getOriginalFileUrl(selectedJob.id, selectedFileIndex)" 
                     class="pdf-preview"
                     frameborder="0"
                 >
                     <p>Your browser does not support PDFs. <a :href="getOriginalFileUrl(selectedJob.id, selectedFileIndex)" target="_blank">Download the PDF</a>.</p>
                 </iframe>
                 <img 
                     v-else-if="selectedJob" 
                     :src="getLegacyImageUrl(selectedJob)" 
                     alt="Job Image" 
                 />
                 <button @click="toggleImagePopover(null)" class="popover-close">
                     <i class="fa fa-close"></i>
                 </button>
             </div>
         </div>

         <!-- Cost Breakdown Modal -->
         <CostBreakdownModal 
            :visible="showCostBreakdownModal" 
            :job="selectedJob" 
            @close="closeCostBreakdownModal" 
        />
     </MainLayout>
</template>

<script>
import MainLayout from "@/Layouts/MainLayout.vue";
import axios from "axios";
import {useToast} from "vue-toastification";
import OrderJobDetails from "@/Pages/Invoice/OrderJobDetails.vue";
import OrderSpreadsheet from "@/Components/OrderSpreadsheet.vue";
import Header from "@/Components/Header.vue";
import OrderHistory from "@/Pages/Invoice/OrderHistory.vue";
import AddNoteDialog from "@/Components/AddNoteDialog.vue";
import AddLockNoteDialog from "@/Components/AddLockNoteDialog.vue";
import CostBreakdownModal from "@/Components/CostBreakdownModal.vue";
import useRoleCheck from '@/Composables/useRoleCheck';
import { computed } from 'vue';


export default {
    setup() {
        const { isRabotnik } = useRoleCheck();
        
        const isRabotnikComputed = computed(() => isRabotnik.value);
        
        return {
            isRabotnikComputed
        };
    },
    components: {
        AddNoteDialog,
        AddLockNoteDialog,
        OrderHistory,
        OrderSpreadsheet,
        OrderJobDetails,
        MainLayout,
        Header,
        CostBreakdownModal },
    props: {
        invoice: Object,
        canViewPrice: Boolean
    },
    data() {
        return {
            showImagePopover: false,
            selectedJob: null,
            selectedFileIndex: null,
            isSidebarVisible: false,
            spreadsheetMode:true,
            jobProcessMode:false,
            backgroundColor: null,
            openDialog: false,
            showCostBreakdownModal: false,
            costBreakdown: {}
        }
    },
    computed: {
        getStatusColorClass() {
            const invoiceStatus = this.invoice.status;
            if (invoiceStatus === "Not started yet") {
                return "orange-text";
            } else if (invoiceStatus === "In progress") {
                return "blue-text";
            } else if (invoiceStatus === "Completed") {
                return "green-text";
            }
        },
        getStatusBadgeClass() {
            const invoiceStatus = this.invoice.status;
            switch (invoiceStatus) {
                case 'Not started yet':
                    return 'status-not-started';
                case 'In progress':
                    return 'status-in-progress';
                case 'Completed':
                    return 'status-completed';
                default:
                    return 'status-default';
            }
        },
        getStatusColor() {
            const invoiceStatus = this.invoice.status;
            switch(invoiceStatus) {
                case 'Not started yet':
                    return "linear-gradient(135deg, #f59e0b 0%, #d97706 100%)";
                case 'In progress':
                    return "linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)";
                case 'Completed':
                    return "linear-gradient(135deg, #10b981 0%, #059669 100%)";
                default:
                    return "linear-gradient(135deg, #6b7280 0%, #4b5563 100%)";
            }
        },
        mustBePerfectChecked: {
            get() {
                return this.invoice.perfect === 1
            },
            set(value) {
                this.backgroundColor = "#a36a03";
                this.invoice.perfect = value;
                axios.put(`/orders/${this.invoice.id}`, {
                    perfect: value,
                });
            }
        },
        onHoldChecked: {
            get() {
                return this.invoice.onHold === 1;
            },
            set(value) {
                this.invoice.onHold = value;
                axios.put(`/orders/${this.invoice.id}`, {
                    onHold: value,
                });
            }
        },
        ripFirstChecked: {
            get() {
                return this.invoice.ripFirst === 1
            },
            set(value) {
                this.backgroundColor = "#a36a03";
                this.invoice.ripFirst = value;
                axios.put(`/orders/${this.invoice.id}`, {
                    ripFirst: value,
                });
            }
        },
        revisedArtChecked: {
            get() {
                return this.invoice.revisedArt === 1
            },
            set(value) {
                this.backgroundColor = "#a36a03";
                this.invoice.revisedArt = value;
                axios.put(`/orders/${this.invoice.id}`, {
                    revisedArt: value,
                });
            }
        },
        revisedArtCompleteChecked: {
            get() {
                return this.invoice.revisedArtComplete === 1
            },
            set(value) {
                this.backgroundColor = "#a36a03";
                this.invoice.revisedArtComplete = value;
                axios.put(`/orders/${this.invoice.id}`, {
                    revisedArtComplete: value,
                });
            }
        },
        additionalArtChecked: {
            get() {
                return this.invoice.additionalArt === 1
            },
            set(value) {
                this.backgroundColor = "#a36a03";
                this.invoice.additionalArt = value;
                axios.put(`/orders/${this.invoice.id}`, {
                    additionalArt: value,
                });
            }
        },
        rushChecked: {
            get() {
                return this.invoice.rush === 1
            },
            set(value) {
                this.backgroundColor = "#a36a03";
                this.invoice.rush = value;
                axios.put(`/orders/${this.invoice.id}`, {
                    rush: value,
                });
            }
        },
        shouldShowPrice() {
            return !this.isRabotnikComputed;
        },
        
        // Get total area for a job (with breakdown support)
        getJobTotalArea() {
            return (job) => {
                if (job.dimensions_breakdown && Array.isArray(job.dimensions_breakdown) && job.dimensions_breakdown.length > 0) {
                    // Use dimensions breakdown
                    let totalArea = 0;
                    for (const fileData of job.dimensions_breakdown) {
                        if (fileData.total_area_m2 && typeof fileData.total_area_m2 === 'number') {
                            totalArea += fileData.total_area_m2;
                        }
                    }
                    return totalArea;
                } else {
                    // Fallback to legacy computed_total_area_m2
                    return job.computed_total_area_m2 || 0;
                }
            };
        }
    },
    methods: {
        // Legacy method for old single-file system
        getLegacyImageUrl(job) {
            return `/storage/uploads/${job.file}`;
        },
        
        // New method for multiple file thumbnails (add cache-busting)
        getThumbnailUrl(jobId, fileIndex) {
            const url = route('jobs.viewThumbnail', { jobId: jobId, fileIndex: fileIndex });
            const ts = Date.now();
            return `${url}?t=${ts}`;
        },
        
        // Get original file URL for PDF preview
        getOriginalFileUrl(jobId, fileIndex) {
            return route('jobs.viewOriginalFile', { jobId: jobId, fileIndex: fileIndex });
        },
        
        // Check if job has multiple files (new system)
        hasMultipleFiles(job) {
            return job.originalFile && Array.isArray(job.originalFile) && job.originalFile.length > 0;
        },
        
        // Handle thumbnail loading errors
        handleThumbnailError(event, fileIndex) {
            console.warn('Thumbnail failed to load for file index:', fileIndex);
            // Could add fallback logic here if needed
        },
        
        toggleImagePopover(job, fileIndex = null) {
            this.selectedJob = job;
            this.selectedFileIndex = fileIndex;
            this.showImagePopover = !this.showImagePopover;
        },
        toggleSidebar() {
            this.isSidebarVisible = !this.isSidebarVisible;
        },
        toggleSpreadsheetMode(){
            this.spreadsheetMode = !this.spreadsheetMode;
        },
        toggleJobProcessMode(){
            this.jobProcessMode = !this.jobProcessMode;
        },
        async downloadAllProofs() {
            const toast = useToast();
            
            try {
                toast.info('Preparing download...');
                
                // Collect all files from all jobs
                const allFiles = [];
                
                for (const job of this.invoice.jobs) {
                    // New system: Multiple files
                    if (this.hasMultipleFiles(job)) {
                        for (let fileIndex = 0; fileIndex < job.originalFile.length; fileIndex++) {
                            allFiles.push({
                                jobId: job.id,
                                jobName: job.name,
                                fileIndex: fileIndex,
                                isMultiple: true
                            });
                        }
                    }
                    // Legacy system: Single file
                    else if (job.file && job.file !== 'placeholder.jpeg') {
                        allFiles.push({
                            jobId: job.id,
                            jobName: job.name,
                            filePath: job.file,
                            isMultiple: false
                        });
                    }
                }
                
                if (allFiles.length === 0) {
                    toast.warning('No files found to download');
                    return;
                }
                
                // Try bulk download endpoint first
                try {
                    const response = await axios.post('/orders/download-all-files', {
                        invoiceId: this.invoice.id,
                        clientName: this.invoice.client.name,
                        files: allFiles
                    }, {
                        responseType: 'blob'
                });

                    // Create and download the zip file
                const fileURL = window.URL.createObjectURL(new Blob([response.data]));
                const fileLink = document.createElement('a');
                    fileLink.href = fileURL;
                    fileLink.setAttribute('download', `Invoice_${this.invoice.client.name}_${this.invoice.id}_AllFiles.zip`);
                    document.body.appendChild(fileLink);
                    fileLink.click();
                    fileLink.remove();
                    
                    toast.success(`Downloaded ${allFiles.length} files successfully`);
                    
                } catch (bulkError) {
                    console.warn('Bulk download failed, trying individual downloads:', bulkError);
                    
                    // Fallback: Download files individually
                    toast.info('Downloading files individually...');
                    let downloadCount = 0;
                    
                    for (const file of allFiles) {
                        try {
                            let downloadUrl;
                            let filename;
                            
                            if (file.isMultiple) {
                                // New system: Download from R2 via backend
                                downloadUrl = `/jobs/${file.jobId}/download-original-file`;
                                filename = `${file.jobName}_${file.fileIndex + 1}.pdf`;
                                
                                const response = await axios.post(downloadUrl, {
                                    file_index: file.fileIndex
                                }, {
                                    responseType: 'blob'
                                });
                                
                                const fileURL = window.URL.createObjectURL(new Blob([response.data]));
                                const fileLink = document.createElement('a');
                fileLink.href = fileURL;
                                fileLink.setAttribute('download', filename);
                document.body.appendChild(fileLink);
                                fileLink.click();
                                fileLink.remove();
                                
                            } else {
                                // Legacy system: Download from local storage
                                downloadUrl = `/storage/uploads/${file.filePath}`;
                                filename = `${file.jobName}_${file.filePath}`;
                                
                                const fileLink = document.createElement('a');
                                fileLink.href = downloadUrl;
                                fileLink.setAttribute('download', filename);
                                fileLink.target = '_blank';
                                document.body.appendChild(fileLink);
                fileLink.click();
                                fileLink.remove();
                            }
                            
                            downloadCount++;
                            
                        } catch (fileError) {
                            console.error(`Failed to download file for job ${file.jobName}:`, fileError);
                        }
                    }
                    
                    if (downloadCount > 0) {
                        toast.success(`Downloaded ${downloadCount} of ${allFiles.length} files`);
                    } else {
                        toast.error('Failed to download any files');
                    }
                }
                
            } catch (error) {
                console.error('Download error:', error);
                toast.error('There was an error downloading the files');
            }
        },
        generatePdf(invoiceId) {
            window.open(`/orders/${invoiceId}/pdf`, '_blank');
        },
        reorder() {
            const invoiceData = this.invoice;
            this.$inertia.visit('/orders/create', {
                data: {
                    invoiceData
                }
            });
        },
        navigateToAction(){
            const firstInProgressAction = this.invoice?.jobs
                .flatMap(job => job?.actions)
                .find(action => action?.status === 'In Progress' || action?.status === 'Not started yet');
            return this.$inertia.visit(`/actions/${firstInProgressAction?.name}`);
        },
        async unlockOrder(invoiceId) {
            try {
                const response = await axios.put('/orders/update-locked-note', {
                    id: invoiceId,
                    comment: null, // Set comment to null to unlock
                });

                if (response.status === 200) {
                    // Handle successful update (e.g., display success message)
                    let toast = useToast();
                    this.invoice.LockedNote = null;
                    toast.success('Order successfully unlocked.')
                } else {
                    // Handle errors (e.g., display error message)
                    let toast = useToast();
                    toast.error('Failed to unlock order:', response.data);
                }
            } catch (error) {
                // Handle unexpected errors
                console.error('Error unlocking order:', error);
            }
        },
        previewCuttingFile(jobId, fileIndex) {
            const url = route
                ? route('jobs.viewCuttingFile', { jobId, fileIndex })
                : `/jobs/${jobId}/view-cutting-file/${fileIndex}`;
            window.open(url, '_blank');
        },
        getCuttingFileExtension(filePath) {
            return filePath.split('.').pop() || '';
        },
        
        // Calculate total area from dimensions breakdown
        calculateTotalAreaFromBreakdown(job) {
            if (!job.dimensions_breakdown || !Array.isArray(job.dimensions_breakdown)) {
                return 0;
            }
            
            let totalArea = 0;
            
            for (const fileData of job.dimensions_breakdown) {
                if (fileData.total_area_m2 && typeof fileData.total_area_m2 === 'number') {
                    totalArea += fileData.total_area_m2;
                }
            }
            
            return totalArea;
        },
        
        // Department determination helpers
        hasLargeFormat(job) {
            if (job.articles && job.articles.length > 0) {
                return job.articles.some(article => article.largeFormatMaterial);
            }
            return !!job.large_material;
        },
        
        hasSmallFormat(job) {
            if (job.articles && job.articles.length > 0) {
                return job.articles.some(article => article.smallMaterial);
            }
            return !!job.small_material;
        },
        
        hasLargeAndSmallFormat(job) {
            return this.hasLargeFormat(job) && this.hasSmallFormat(job);
        },
        
        goBack() {
            // Use browser's back functionality
            if (window.history.length > 1) {
                window.history.back();
            } else {
                // Fallback to a default route if no history
                this.$inertia.visit('/orders');
            }
        },
        showCostBreakdown(job) {
            this.selectedJob = job;
            this.showCostBreakdownModal = true;
            this.fetchCostBreakdown(job.id);
        },
        async fetchCostBreakdown(jobId) {
            const toast = useToast();
            try {
                const response = await axios.post('/jobs/recalculate-cost', {
                    job_id: jobId,
                    total_area_m2: parseFloat(this.selectedJob.total_area_m2) || 0,
                    quantity: parseInt(this.selectedJob.quantity) || 1,
                    copies: parseInt(this.selectedJob.copies) || 1
                });
                
                // Ensure we have valid data
                const componentBreakdown = Array.isArray(response.data.component_breakdown) ? response.data.component_breakdown : [];
                const materialDeduction = Array.isArray(response.data.material_deduction) ? response.data.material_deduction : [];
                
                this.costBreakdown = {
                    total_cost: parseFloat(response.data.price) || 0,
                    component_breakdown: componentBreakdown,
                    material_deduction: materialDeduction
                };
            } catch (error) {
                console.error('Failed to get cost breakdown:', error);
                toast.error('Failed to load cost breakdown details');
                // Use stored job data as fallback
                this.costBreakdown = {
                    total_cost: parseFloat(this.selectedJob.price) || 0,
                    component_breakdown: [],
                    material_deduction: []
                };
            }
        },
        closeCostBreakdownModal() {
            this.showCostBreakdownModal = false;
            this.selectedJob = null;
            this.costBreakdown = {};
        }

    },
};
</script>

<style scoped lang="scss">
.circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
}
.ticket-note-perfect {
    background-color: #d88f0b; /* Gold background */
    color: $white;
    font-weight: bold;
    text-transform: uppercase;
    border: 2px dashed white; /* Ticket-like dashed border */
    border-bottom: none;
    border-radius: 3px 3px 0 0 ;
    padding: 4px;
    text-align: center;
    width: max-content;
}
.ticket-note-hold {
    background-color: $red; /* Gold background */
    color: $white;
    font-weight: bold;
    text-transform: uppercase;
    border: 2px dashed white; /* Ticket-like dashed border */
    border-bottom: none;
    border-radius: 3px 3px 0 0 ;
    padding: 4px;
    text-align: center;
    width: max-content;
}
.ticket-note-ripFirst {
    background-color: $green; /* Gold background */
    color: $white;
    font-weight: bold;
    text-transform: uppercase;
    border: 2px dashed white; /* Ticket-like dashed border */
    border-bottom: none;
    border-radius: 3px 3px 0 0 ;
    padding: 4px;
    text-align: center;
    width: max-content;
}
.ticket-note-revisedArt {
    background-color: $blue; /* Gold background */
    color: $white;
    font-weight: bold;
    text-transform: uppercase;
    border: 2px dashed white; /* Ticket-like dashed border */
    border-bottom: none;
    border-radius: 3px 3px 0 0 ;
    padding: 4px;
    text-align: center;
    width: max-content;
}
.ticket-note-revisedArtComplete {
    background-color: $light-green; /* Gold background */
    color: $white;
    font-weight: bold;
    text-transform: uppercase;
    border: 2px dashed white; /* Ticket-like dashed border */
    border-bottom: none;
    border-radius: 3px 3px 0 0 ;
    padding: 4px;
    text-align: center;
    width: max-content;
}
.ticket-note-rush {
    background-color: skyblue; /* Gold background */
    color: $white;
    font-weight: bold;
    text-transform: uppercase;
    border: 2px dashed white; /* Ticket-like dashed border */
    border-bottom: none;
    border-radius: 3px 3px 0 0 ;
    padding: 4px;
    text-align: center;
    width: max-content;
}
.ticket-note-additionalArt {
    background-color: mediumpurple; /* Gold background */
    color: $white;
    font-weight: bold;
    text-transform: uppercase;
    border: 2px dashed white; /* Ticket-like dashed border */
    border-bottom: none;
    border-radius: 3px 3px 0 0 ;
    padding: 4px;
    text-align: center;
    width: max-content;
}
.flexed{
    justify-content: center;
    align-items: center;
}
.popover-content[data-v-19f5b08d]{
    background-color: #2d3748;
}
.fa-close::before{
    color: white;
}
[type='checkbox']:checked{
    border: 1px solid white;
}
.orange-text {
    color: $orange;
}
.blue-text {
    color: #1ba5e4;
}
.bold {
    font-weight: bolder;
    font-family: Tahoma, sans-serif;
}
.green-text {
    color: $green;
}
.light-gray{
    background-color: $light-gray;
}
.ultra-light-gray{
    background-color: $ultra-light-gray;
}
.blue{
    background-color: $blue;
}
.green{
    background-color: $green;
}
.background{
    background-color: $background-color;
}
.header {
    display: flex;
    align-items: center;
}
.dark-gray {
    background-color: $dark-gray;
    justify-content: left;
    align-items: center;
    min-height: 20vh;
    min-width: 80vh;
}
.sub-title {
    font-size: 20px;
    font-weight: bold;
    display: flex;
    align-items: center;
    color: $white;
}
.jobShippingInfo{
    max-width: 300px;
    
}
.jobPriceInfo{
    max-width: 30%;
    position: absolute;
    right: 0;
    bottom: 0;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 4px;
    padding: 8px 10px;
    border-radius: 6px 0 0 0;
    box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    z-index: 5;
}
.right{
    gap: 34.9rem;
}
.btn {
    margin-right: 4px;
    padding: 10px 15px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    border-radius: 2px;
}
.btn2{
    font-size: 13px;
    margin-right: 4px;
    padding: 7px 10px;
    border: none;
    cursor: pointer;
    color: white;
    background-color: $blue;
    border-radius: 2px;
}
.btns{
    position: absolute;
    top: -6px;
    right: 0;
    padding: 0;
}
.lock-order, .download-order, .re-order{
    background-color: $blue;
    color: white;
}
.go-to-steps, .go-back{
    background-color: $orange;
    color: white;
}
.InvoiceDetails{
    border-bottom: 2px dashed lightgray;
}
.bt{
    font-size:35px ;
    cursor: pointer;
    padding: 0;
}
.popover {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1000; /* high z-index to be on top of other content */
}

.popover-content {
    width: 30%;
    background: white;
    padding: 20px;
    border-radius: 8px;
    position: relative;
}

.popover-close {
    position: absolute;
    top: 10px;
    right: 10px;
    padding: 1px 4px;
    border-radius: 2px;
    background-color: $red;
}

.right-column {
    background-color: $background-color;
    color: white;
    overflow-y: auto;
}

.hamburger {
    z-index: 2000;
    background-color: transparent;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #fff; /* Adjust the color to match your layout */
}

.sidebar {
    position: fixed;
    top: 0;
    right: 0;
    bottom: 0;
    width: 350px; /* Width of sidebar */
    background-color: $background-color; /* Sidebar background color */
    z-index: 1000; /* Should be below the overlay */
    overflow-y: auto;
    padding: 20px;
    border: 1px solid $white;
    border-right:none;
    border-radius: 4px 0 0 4px ;
}

.order-history {
    padding: 20px;
}

.close-sidebar {
    position: absolute;
    top: 10px;
    right: 10px;
    background: transparent;
    border: none;
    font-size: 24px;
    color: #fff; /* Adjust close button color */
    cursor: pointer;
}

.is-blurred {
    filter: blur(5px);
}

.content {
    transition: filter 0.3s; /* Smooth transition for the blur effect */
}

.history-subtitle {
    background-color: white;
    color: black;
    padding: 10px;
    margin-bottom: 10px;
    font-weight: bold;
}
.jobImg {
    height: 45px;
    margin: 0 1rem;
}

/* New thumbnail system styles */
.job-thumbnails-container {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.thumbnails-grid {
    display: flex;
    gap: 0.25rem;
    flex-wrap: wrap;
}

.thumbnail-btn {
    position: relative;
    background: none;
    border: none;
    padding: 0;
    cursor: pointer;
    border-radius: 4px;
    overflow: hidden;
}

.thumbnail-btn:hover {
    transform: scale(1.05);
    transition: transform 0.2s;
}

.thumbnail-number {
    position: absolute;
    top: 2px;
    right: 2px;
    background-color: rgba(0, 0, 0, 0.7);
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: bold;
}

.no-files-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 5px;
    color: #999;
    font-size: 12px;
}

.no-files-placeholder i {
    font-size: 24px;
    margin-bottom: 0.5rem;
}

/* Enhanced PDF preview styles */
.pdf-preview {
    width: 80vw;
    height: 80vh;
    max-width: 1000px;
    max-height: 800px;
}

.popover-content {
    max-width: 90vw;
    max-height: 90vh;
    display: flex;
    align-items: center;
    justify-content: center;
}
/*
spreadheet style
*/
table {
    width: 100%;
    border-collapse: collapse;
    border-spacing: 0;
    margin-bottom: 20px;
    border: 1px solid #ddd;
    border-radius: 5px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

table th, table td {
    padding: 10px;
    border: 1px solid #ddd;
    text-align: center;
}

table th {
    color: white;
    border-top: 1px solid #ddd;
    border-bottom: 1px solid #ddd;
    background-color: $ultra-light-gray;

}

[v-cloak] {
    display: none;
}
.cutting-files-preview {
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: $dark-gray;
}
.cutting-file-btn {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    background: #ff6b35;
    color: white;
    border: none;
    border-radius: 4px;
    padding: 4px 10px;
    cursor: pointer;
    font-size: 0.9rem;
}
.cutting-file-btn .fa-scissors {
    margin-right: 4px;
}
.preview-hint {
    font-size: 0.7em;
    opacity: 0.7;
    margin-left: 4px;
}

/* Dimensions breakdown styles */
.dimensions-breakdown {
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 8px;
    margin: 4px 0;
    background-color: $dark-gray;
}

.dimensions-content {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.file-thumbnail {
    flex-shrink: 0;
}

.page-dimensions {
    flex: 1;
}

.file-dimensions-header {
    margin-bottom: 4px;
    padding-bottom: 4px;
    border-bottom: 1px solid #e2e8f0;
}

.file-name {
    font-weight: 600;
    font-size: 0.9em;
}

.page-dimensions {
    margin: 4px 0;
}

.page-dimension {
    display: flex;
    margin: 2px 0;
    padding: 2px 0;
    font-size: 0.85em;
}

.file-total-area {
    margin-top: 6px;
    padding-top: 4px;
    border-top: 1px solid #e2e8f0;
    font-weight: 600;
    color: #059669;
}

/* Status Badge Styles */
.status-badge {
    padding: 0.375rem 0.75rem;
    border-radius: 10px 10px 0 0;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    display: inline-block;
    align-items: end;
    white-space: nowrap;
    min-width: fit-content;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    color: white;
}

/* Cost Breakdown Modal Styles */
.cost-breakdown-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.cost-breakdown-content {
    background: white;
    border-radius: 8px;
    padding: 20px;
    max-width: 800px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.cost-breakdown-close-btn {
    position: absolute;
    top: 15px;
    right: 20px;
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
    padding: 5px;
    border-radius: 50%;
    transition: background-color 0.2s;
}

.cost-breakdown-close-btn:hover {
    background-color: #f0f0f0;
}

.cost-breakdown-header {
    text-align: center;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid #e0e0e0;
}

.cost-breakdown-header h3 {
    margin: 0 0 10px 0;
    color: #333;
    font-size: 24px;
    font-weight: bold;
}

.job-name {
    margin: 0;
    color: #666;
    font-size: 16px;
    font-style: italic;
}

.cost-breakdown-body {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.breakdown-section {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    border-left: 4px solid #007bff;
}

.breakdown-section h4 {
    margin: 0 0 15px 0;
    color: #333;
    font-size: 18px;
    font-weight: bold;
    border-bottom: 1px solid #dee2e6;
    padding-bottom: 8px;
}

.breakdown-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.breakdown-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    background: white;
    border-radius: 6px;
    border: 1px solid #e9ecef;
}

.breakdown-item .label {
    font-weight: 600;
    color: #495057;
}

.breakdown-item .value {
    font-weight: bold;
    color: #007bff;
}

.pricing-summary {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.pricing-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: white;
    border-radius: 6px;
    border: 1px solid #e9ecef;
}

.pricing-label {
    font-weight: 600;
    color: #495057;
}

.pricing-value {
    font-weight: bold;
    font-size: 16px;
}

.pricing-value.sale-price {
    color: #28a745;
}

.pricing-value.cost-price {
    color: #dc3545;
}

.pricing-value.profit-margin {
    color: #17a2b8;
}

.component-breakdown {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.component-item {
    background: white;
    border-radius: 6px;
    border: 1px solid #e9ecef;
    overflow: hidden;
}

.component-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    background: #e3f2fd;
    border-bottom: 1px solid #e9ecef;
}

.component-name {
    font-weight: bold;
    color: #1976d2;
}

.component-cost {
    font-weight: bold;
    color: #2e7d32;
    font-size: 18px;
}

.component-details {
    padding: 15px;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.detail-row:last-child {
    margin-bottom: 0;
}

.detail-label {
    font-weight: 600;
    color: #495057;
}

.detail-value {
    font-weight: 500;
    color: #333;
}

.total-cost {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: #e8f5e8;
    border-radius: 6px;
    margin-top: 15px;
    border: 2px solid #4caf50;
}

.total-label {
    font-weight: bold;
    color: #2e7d32;
    font-size: 18px;
}

.total-value {
    font-weight: bold;
    color: #2e7d32;
    font-size: 20px;
}

.no-breakdown, .no-deduction {
    text-align: center;
    padding: 20px;
    color: #666;
    font-style: italic;
}

.stored-cost {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: #fff3cd;
    border-radius: 6px;
    margin-top: 15px;
    border: 1px solid #ffeaa7;
}

.stored-sale-price {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: #e8f5e8;
    border-radius: 6px;
    margin-top: 10px;
    border: 1px solid #4caf50;
}

.material-breakdown {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.material-item {
    background: white;
    border-radius: 6px;
    border: 1px solid #e9ecef;
    overflow: hidden;
}

.material-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    background: #fff3cd;
    border-bottom: 1px solid #e9ecef;
}

.material-name {
    font-weight: bold;
    color: #856404;
}

.material-quantity {
    font-weight: bold;
    color: #d63384;
    font-size: 16px;
}

.material-details {
    padding: 15px;
}

.formula-display {
    background: white;
    padding: 15px;
    border-radius: 6px;
    border: 1px solid #e9ecef;
}

.formula-display p {
    margin: 0;
    line-height: 1.6;
    color: #333;
}

.formula-display strong {
    color: #007bff;
}

/* Info button styles */
.info-btn {
    background: none;
    border: none;
    color: #007bff;
    cursor: pointer;
    padding: 2px 6px;
    border-radius: 50%;
    transition: all 0.2s;
    font-size: 14px;
}

.info-btn:hover {
    background-color: #e3f2fd;
    transform: scale(1.1);
}

.info-btn:active {
    transform: scale(0.95);
}
</style>

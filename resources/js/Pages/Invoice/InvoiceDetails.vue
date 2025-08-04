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
                    <div class="flex gap-1">
                        <div v-if="invoice.perfect" class="ticket-note-perfect">Must Be Perfect</div>
                        <div v-if="invoice.onHold" class="ticket-note-hold">On Hold</div>
                        <div v-if="invoice.revisedArt" class="ticket-note-revisedArt">Revised Art</div>
                        <div v-if="invoice.ripFirst" class="ticket-note-ripFirst">Rip First</div>
                        <div v-if="invoice.revisedArtComplete" class="ticket-note-revisedArtComplete">Revised Art Complete</div>
                        <div v-if="invoice.rush" class="ticket-note-rush">Rush</div>
                        <div v-if="invoice.additionalArt" class="ticket-note-additionalArt">Additional Art</div>
                    </div>
                    <div class="form-container p-2 light-gray" :style="invoice.perfect ? { 'background-color': '#d88f0b' } : {}">
                        <div class="InvoiceDetails">
                            <div class="invoice-details flex gap-5 relative" >
                                <div class="invoice-title bg-white text-black bold p-3 ">{{ invoice?.invoice_title }}</div>
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
                                <div class="info">
                                    <div>{{ $t('Status') }}</div>
                                    <div>
                                        <!--
                                                                            WE SHOULD BE CHECKING IF JOB STATUS IS COMPLETED TODO
                                        -->
                                        <span class="bold" style="text-shadow: darkred " :class="getStatusColorClass">{{ invoice?.status }}</span>
                                    </div>
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
                                    <div class=" flex gap-10">
                                        <div class="invoice-title bg-[#7DC068] text-black bold p-3">
                                            #{{index+1}} {{job.name}}
                                        </div>
                                        <!-- Show multiple thumbnails or single legacy image -->
                                        <div class="job-thumbnails-container">
                                            <!-- New system: Multiple files with thumbnails -->
                                            <div v-if="job.originalFile && Array.isArray(job.originalFile) && job.originalFile.length > 0" class="thumbnails-grid">
                                                <button 
                                                    v-for="(file, fileIndex) in job.originalFile" 
                                                    :key="fileIndex"
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
                                            <!-- Legacy system: Single file -->
                                            <button v-else-if="job.file && job.file !== 'placeholder.jpeg'" @click="toggleImagePopover(job, 0)">
                                                <img :src="getLegacyImageUrl(job)" alt="Job Image" class="jobImg thumbnail"/>
                                        </button>
                                            <!-- No files -->
                                            <div v-else class="no-files-placeholder">
                                                <i class="fa fa-file-o"></i>
                                                <span>No files</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Enhanced preview modal -->
                                        <div v-if="showImagePopover" class="popover">
                                            <div class="popover-content bg-gray-700">
                                                <!-- PDF Preview for new system -->
                                                <iframe 
                                                    v-if="selectedJob && selectedFileIndex !== null && hasMultipleFiles(selectedJob)"
                                                    :src="getOriginalFileUrl(selectedJob.id, selectedFileIndex)" 
                                                    class="pdf-preview"
                                                    frameborder="0"
                                                >
                                                    <p>Your browser does not support PDFs. <a :href="getOriginalFileUrl(selectedJob.id, selectedFileIndex)" target="_blank">Download the PDF</a>.</p>
                                                </iframe>
                                                <!-- Legacy image preview -->
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
                                        <div>{{job.file}}</div>
                                        <div>{{$t('Height')}}: <span class="bold">{{job.height.toFixed(2)}} mm</span> </div>
                                        <div>{{$t('Width')}}: <span class="bold">{{job.width.toFixed(2)}} mm</span> </div>
                                        <div>{{$t('Quantity')}}: <span class="bold">{{job.quantity}}</span> </div>
                                        <div>{{$t('Copies')}}: <span class="bold">{{job.copies}}</span> </div>
                                    </div>
                                    <div class="flex p-2 gap-10">
                                        <div class="">
                                            {{$t('Material')}}:
                                            <span class="bold">
                                            <span v-if="job.large_material_id">{{ job.large_material?.name }}</span>
                                            <span v-else>{{ job?.small_material?.name }}</span>
                                         </span>
                                        </div>
                                        <div>{{$t('totalm')}}<sup>2</sup>: <span class="bold">{{((job.height * job.width) / 1000000).toFixed(4)}}</span></div>
                                   <!-- Cutting Files Preview Buttons -->
                                   <div v-if="job.cuttingFiles && job.cuttingFiles.length > 0" class="cutting-files-preview flex gap-1">
                                    <div>Cutting Files:</div>
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
                                    <div v-if="jobProcessMode">
                                        <OrderJobDetails :job="job"/>
                                    </div>
                                    <div class="jobInfo relative">
                                        <div class="jobShippingInfo" style="line-height: normal">
                                            <div class=" bg-white text-black bold ">
                                                <!-- <div class="flex" style="align-items: center;">
                                                    <img src="/images/shipping.png" class="w-10 h-10 pr-1" alt="Shipping">
                                                    {{$t('Shipping')}}
                                                </div> -->
                                                <div class="ultra-light-gray p-2 text-white">
                                                    {{$t('shippingTo')}}: <span class="bold">{{job.shippingInfo}}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div v-if="canViewPrice" class="jobPriceInfo absolute right-0 bottom-0 bg-white text-black bold">
                                            <div class="p-2">
                                                {{$t('jobPrice')}}: <span class="bold" v-if="job.price !== null">{{Number(job.price).toFixed(2)}} ден.</span>
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
        Header },
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
            openDialog: false
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
        }
    },
    methods: {
        // Legacy method for old single-file system
        getLegacyImageUrl(job) {
            return `/storage/uploads/${job.file}`;
        },
        
        // New method for multiple file thumbnails
        getThumbnailUrl(jobId, fileIndex) {
            return route('jobs.viewThumbnail', { jobId: jobId, fileIndex: fileIndex });
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
    border: 1px solid  ;
}
.jobPriceInfo{
    max-height: 40px;
    max-width: 30%;
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
.go-to-steps{
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
    padding: 1rem;
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
</style>

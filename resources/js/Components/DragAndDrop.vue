<template>
    <div class="FileBox light-gray">
        <TabsWrapper>
            <Tab title="Art" icon="mdi-file-image">
                <div class="flex pb-1 justify-center gap-4">
                    <div class="drop-zone text-white" @dragover.prevent @drop="handleFileDrop">
                        <p>{{ $t('dragAndDrop') }}</p>

                        <input type="file" accept=".pdf, .tiff, .tif" @change="handleFileBrowse" style="display: none;" ref="fileInput" multiple />

                    </div>
                    <div class="ultra-light-gray p-1 rounded d-flex">
                        <div class="text-white pb-10">{{ jobs.length ? `${jobs.length} jobs selected` : 'No files selected..' }}</div>
                        <button @click="browseForFiles" class="bg-white rounded text-black py-2 px-5">{{ $t('browse') }}</button>
                    </div>
                </div>
                <div class="ultra-light-gray rounded flex  justify-between text-center m-6 ">
                    <div class="text-white flex-wrap align-center d-flex p-2">Files: {{ jobs.length }}
                        Uploaded: {{ calculateTotalFileSize() }}MB<br>
                    </div>
                    <div class="position-relative p-2">
                        <button @mouseover="showPopover = true"
                                @mouseout="showPopover = false"
                                class="bg-white rounded text-black py-2 px-5">Details</button>
                        <div v-if="showPopover" class="popover">
                            <div v-for="job in jobs" :key="job.file.name">
                                {{ job.file.name }} ({{ jobSize(job.fileSize) }}MB)
                            </div>
                        </div>
                    </div>
                </div>
            </Tab>
            <Tab title="Notes" icon="mdi-chat" class="text">
                <span class="text-white">Add your notes here:</span>
                <textarea v-model="localComment" @input="updateComment"></textarea>
            </Tab>
        </TabsWrapper>
    </div>
</template>

<script>
import { useToast } from 'vue-toastification';
import Tab from "@/Components/tabs/Tab.vue";
import TabsWrapper from "@/Components/tabs/TabsWrapper.vue";
import pdfjsLib from 'pdfjs-dist';

export default {
    name: "DragAndDrop",
    components: {TabsWrapper,Tab},

    props: {
        invoiceComment: String
    },

    data() {
        return {
            jobs: [],
            showPopover: false,
            localComment: this.invoiceComment,
        };
    },
    watch: {
        invoiceComment: function(newVal) {
            this.localComment = newVal;
        }
    },
    methods: {
        updateComment() {
            this.$emit('commentUpdated', this.localComment);
        },
        async createJob(imageFile) {
            try {
                const formData = new FormData();
                formData.append('file', imageFile); // Append the image file

                const response = await axios.post('/jobs', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    },
                });

                return response.data.job;
            } catch (error) {
                console.log('test', error);
                return error;
            }
        },
        handleFileDrop(event) {
            const toast = useToast();

            event.preventDefault();
            const files = event.dataTransfer.files;

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                // Check if the file is a PDF
                if (file.type === 'application/pdf' || file.type === 'image/tiff') {
                    this.convertPDFToImage(file);
                } else {
                    // Handle file type not supported (not a PDF)
                    toast.error('Only PDF and TIFF files are supported.');
                }
            }
        },

        handleFileBrowse(event) {
            const toast = useToast();
            const files = event.target.files;

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                // Check if the file is a PDF or tiff
                if (file.type === 'application/pdf' || file.type === 'image/tiff') {
                    this.convertPDFToImage(file);
                } else {
                    // Handle file type not supported (not a PDF)
                    toast.error('Only PDF and TIFF files are supported.');
                }
            }
        },

        browseForFiles() {
            this.$refs.fileInput.click();

        },

        calculateTotalFileSize() {
            let size = 0;
            this.jobs.forEach(j => size += j.fileSize);
            return this.jobSize(size);
        },

        jobSize(size) {
            const megabyteToByte = 1048576;
            size/=megabyteToByte;
            return size.toFixed(2);
        },

        async convertPDFToImage(file) {
            const reader = new FileReader();

            reader.onload = async (event) => {
                const fileData = event.target.result;

                // Use createJob to convert the PDF to an image
                const tempJob = await this.createJob(file);

                // Calculate PDF dimensions
                const response = await axios.get(`/jobs/${tempJob.id}/image-dimensions`);

                await axios.put(`/jobs/${tempJob.id}`, {
                    width: response.data.width,
                    height: response.data.height
                });


                const job = {
                    imageData: fileData, // Save the file data for the image
                    file: tempJob.file, // Save the PDF file
                    width: response.data.width,
                    height: response.data.height,
                    id: tempJob?.id, // Add other job details as needed
                    materials: tempJob?.large_material_id,
                    materialsSmall: tempJob?.small_material_id,
                    machinePrint: tempJob?.machinePrint,
                    machinesCut: tempJob?.machineCut,
                    quantity: tempJob?.quantity,
                    copies: tempJob?.copies,
                    fileSize: file.size
                };

                this.jobs.push(job);
            };

            reader.readAsDataURL(file);
        },
    },
};
</script>

<style scoped lang="scss">
.dark-gray{
    background-color: $dark-gray;
}
.ultra-light-gray{
    background-color: $ultra-light-gray;
}

.FileBox{
    min-height: 41vh;
    border-radius: 5px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

.drop-zone {
    display: flex;
    border: 3px dashed #ccc;
    border-radius: 10px;
    align-items: center;
    font-size: 18px;
    justify-content: center;
    width: 320px;
    height: 150px;
    background-color: $ultra-light-gray;
}



.d-flex {
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;
}

.text {
    display: flex;
    flex-direction: column;
    padding: 10px;
}

.popover {
    border: 1px solid #ccc;
    padding: 10px;
    right: 1px;
    position: absolute;
    background-color: white;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
}

</style>

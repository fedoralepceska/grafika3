<template>
    <div class="FileBox light-gray">
        <TabsWrapper>
            <Tab title="Art" icon="mdi-file-image">
                <div class="flex pb-1 justify-center gap-4">
                    <!-- Drop Area -->
                    <div class="drop-zone-container">
                        <div
                            class="drop-zone text-white"
                            v-if="!uploading"
                            @dragover.prevent
                            @drop="handleFileDrop"
                        >
                            <p>{{ $t('dragAndDrop') }}</p>
                            <input
                                type="file"
                                accept=".pdf, .tiff, .tif"
                                @change="handleFileBrowse"
                                style="display: none;"
                                ref="fileInput"
                                multiple
                            />
                        </div>
                        <div
                            v-else
                            class="uploading-animation flex flex-col items-center justify-center"
                        >
                            <p class="uploading-text text-white">Uploading...</p>
                            <img src="/images/Loading.gif" alt="Loading" class="loading-gif" style="width: 35px; height: 35px"/>
                        </div>
                    </div>

                    <!-- Job Count and Browse Button -->
                    <div class="ultra-light-gray p-1 rounded d-flex">
                        <div class="text-white pb-10">
                            {{ fileJobs.length ? `${fileJobs.length} jobs selected` : 'No files selected..' }}
                        </div>
                        <button @click="browseForFiles" class="bg-white rounded text-black py-2 px-5">
                            {{ $t('browse') }}
                        </button>
                    </div>
                </div>

                <!-- File Size and Details -->
                <div class="fbox ultra-light-gray rounded flex justify-between text-center m-6">
                    <div class="text-white flex-wrap align-center d-flex p-2">
                        Files: {{ fileJobs.length }} Uploaded: {{ calculateTotalFileSize() }}MB<br>
                    </div>
                    <div class="position-relative p-2">
                        <button
                            @mouseover="showPopover = true"
                            @mouseout="showPopover = false"
                            class="bg-white rounded text-black py-2 px-5"
                        >
                            Details
                        </button>
                        <div v-if="showPopover" class="popover">
                            <div v-for="job in fileJobs" :key="job.file.name">
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

---

### Script Adjustments

```javascript
<script>
import { useToast } from 'vue-toastification';
import Tab from "@/Components/tabs/Tab.vue";
import TabsWrapper from "@/Components/tabs/TabsWrapper.vue";

export default {
    name: "DragAndDrop",
    components: { TabsWrapper, Tab },

    props: {
        invoiceComment: String,
        initialJobs: Array,
    },

    data() {
        return {
            jobs: Array.isArray(this.initialJobs) ? [...this.initialJobs] : [],
            showPopover: false,
            localComment: this.invoiceComment,
            uploading: false, // Track uploading state
        };
    },

    computed: {
        // Filter jobs that have a file
        fileJobs() {
            return this.jobs.filter(job => job.file && job.file !== 'placeholder.jpeg');
        },
    },

    watch: {
        invoiceComment(newVal) {
            this.localComment = newVal;
        },
        jobs: {
            handler(newJobs) {
                this.$emit('update:jobs', newJobs);
            },
            deep: true,
        },
    },

    emits: ['update:jobs'],

    methods: {
        async createJob(imageFile) {
            this.uploading = true; // Start uploading
            try {
                const formData = new FormData();
                formData.append('file', imageFile);

                const response = await axios.post('/jobs', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    },
                });

                this.uploading = false; // Finish uploading
                return response.data.job;
            } catch (error) {
                this.uploading = false; // Handle failure
                throw error;
            }
        },

        handleFileDrop(event) {
            const toast = useToast();

            event.preventDefault();
            const files = event.dataTransfer.files;

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                if (file.type === 'application/pdf') {
                    this.convertPDFToImage(file);
                } else {
                    toast.error('Only PDF files are supported.');
                }
            }
        },

        handleFileBrowse(event) {
            const toast = useToast();
            const files = event.target.files;

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                if (file.type === 'application/pdf') {
                    this.convertPDFToImage(file);
                } else {
                    toast.error('Only PDF files are supported.');
                }
            }
        },

        browseForFiles() {
            this.$refs.fileInput.click();
        },

        calculateTotalFileSize() {
            return this.fileJobs.reduce((size, job) => size + job.fileSize, 0).toFixed(2);
        },

        jobSize(size) {
            const megabyteToByte = 1048576;
            return (size / megabyteToByte).toFixed(2);
        },

        async convertPDFToImage(file) {
            try {
                const tempJob = await this.createJob(file);

                const response = await axios.get(`/jobs/${tempJob.id}/image-dimensions`);
                await axios.put(`/jobs/${tempJob.id}`, {
                    width: response.data.width,
                    height: response.data.height,
                });

                this.jobs.push({
                    file: tempJob.file,
                    width: response.data.width,
                    height: response.data.height,
                    id: tempJob.id,
                    fileSize: file.size,
                });
            } catch (error) {
                console.error('Error creating job:', error);
            }
        },

        updateComment() {
            this.$emit('commentUpdated', this.localComment);
        },

        handleCatalogJobs(catalogJobs) {
            catalogJobs.forEach(job => {
                this.jobs.push({
                    ...job,
                    isPlaceholder: true, // Mark jobs created from catalog
                });
            });
        },

        async handlePlaceholderFileDrop(event, job) {
            const file = event.target.files[0];
            if (!file) return;

            try {
                const tempJob = await this.createJob(file);

                const index = this.jobs.findIndex(j => j.id === job.id);
                if (index !== -1) {
                    this.jobs[index] = {
                        ...job,
                        file: tempJob.file,
                        isPlaceholder: false,
                        needsFile: false,
                    };
                }

                const toast = useToast();
                toast.success('File uploaded successfully');
            } catch (error) {
                const toast = useToast();
                toast.error('Failed to upload file');
            }
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
.fbox{
    margin-bottom: 45.5px;
}
.FileBox{
    min-height: 90%;
    max-height: 600px;
    border-radius: 5px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    margin: 0 auto;
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

.uploading-animation {
    width: 320px;
    height: 150px;
    background-color: #ccc;
    border-radius: 10px;
    display: flex;
    justify-content: center;
    align-items: center;
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

.placeholder-container {
    position: relative;
    width: 150px;
    height: 150px;
    margin: 10px;
}

.placeholder-image {
    width: 100%;
    height: 100%;
    border: 2px dashed $gray;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: $ultra-light-gray;
    cursor: pointer;
    position: relative;
    overflow: hidden;

    span {
        color: $white;
        text-align: center;
    }
}

.placeholder-input {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
}

.placeholder-info {
    text-align: center;
    color: $white;
    margin-top: 5px;
}
</style>

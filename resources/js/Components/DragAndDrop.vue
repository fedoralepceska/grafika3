<template>
    <div class="FileBox light-gray">
        <TabsWrapper>
            <Tab title="Art" icon="mdi-file-image">
                <div class="flex pb-1 justify-center gap-4">
                    <div class="drop-zone text-white" @dragover.prevent @drop="handleFileDrop">
                        <p>{{ $t('dragAndDrop') }}</p>

                        <input type="file" accept=".jpg, .jpeg, .png" @change="handleFileBrowse" style="display: none;" ref="fileInput" multiple />

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
                                {{ job.file.name }} ({{ jobSize(job.file.size) }}MB)
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
import Tab from "@/Components/Tab.vue";
import TabsWrapper from "@/Components/TabsWrapper.vue";
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
        async createJob(imageFile, width, height) {
            try {
                const formData = new FormData();
                formData.append('file', imageFile); // Append the image file
                formData.append('width', width);
                formData.append('height', height);

                const response = await axios.post('/jobs', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    },
                });

                return response.data.job;
            } catch (error) {
                return error;
            }
        },
        handleFileDrop(event) {
            event.preventDefault();
            const files = event.dataTransfer.files;

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                this.calculateImageDimensions(file);
            }
        },
        handleFileBrowse(event) {
            const files = event.target.files;

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                this.calculateImageDimensions(file);
            }
        },

        browseForFiles() {
            this.$refs.fileInput.click();

        },

        calculateTotalFileSize() {
            let size = 0;
            this.jobs.forEach(j => size += j.file.size);
            return this.jobSize(size);
        },

        jobSize(size) {
            const megabyteToByte = 1048576;
            size/=megabyteToByte;
            return size.toFixed(2);
        },

        calculateImageDimensions(file) {
            const reader = new FileReader();

            reader.onload = (event) => {
                const imageData = event.target.result;

                const img = new Image();
                img.src = imageData;

                img.onload = async () => {
                    const width = img.width;
                    const height = img.height;

                    const tempJob = await this.createJob(file, width, height);

                    console.log(tempJob);

                    const job = {
                        imageData: imageData,
                        width: width,
                        height: height,
                        file: file,
                        id: tempJob?.id,
                        materials: tempJob?.materials,
                        materialsSmall: tempJob?.materialsSmall,
                        machinePrint: tempJob?.machinePrint,
                        machinesCut: tempJob?.machineCut
                    };

                    this.jobs.push(job); // Correct variable name
                };
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

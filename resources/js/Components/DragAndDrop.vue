<template>
    <div class="FileBox dark-gray">
        <div class="drop-zone text-white" @dragover.prevent @drop="handleFileDrop">
            <p>{{ $t('dragAndDrop') }}</p>

            <input type="file" accept=".jpg, .jpeg, .png" @change="handleFileBrowse" style="display: none;" ref="fileInput" />



        </div>
        <div class="pt-5 d-flex ">
            <button @click="browseForFiles" class="dark-gray text-white border rounded-corners py-2 px-5 ">{{ $t('browse') }}</button>
        </div>
    </div>
</template>

<script>
import { useToast } from 'vue-toastification';
export default {
    name: "DragAndDrop",
    data() {
        return {
            jobs: [],
        };
    },
    methods: {
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

                const createdJob = response.data.job;
                return createdJob;
            } catch (error) {
                // Handle errors
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

                    const job = {
                        imageData: imageData,
                        width: width,
                        height: height,
                        file: file,
                        id: tempJob.id,
                        materials: tempJob.materials
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

.FileBox{
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

.drop-zone {
    display: flex;
    border: 5px dashed #ccc;
    border-radius: 25px;
    align-items: center;
    font-size: 25px;
    justify-content: center;
    width: 450px;
    height: 250px;
    background-color: $light-gray;
}

.rounded-corners {
    border-radius: 15px;
}

.d-flex {
    display: flex;
    justify-content: center;
    align-items: center;
}

</style>

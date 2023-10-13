<template>
    <div class="drop-zone" @dragover.prevent @drop="handleFileDrop">
        <p>Drag and drop files here</p>
        <ul>
            <li v-for="(job, index) in jobs" :key="index">
                <img :src="job.imageData" alt="Job Image" />
                <span>Width: {{ job.width }}</span>
                <span>Height: {{ job.height }}</span>
                <!-- Add options form here -->
            </li>
        </ul>
    </div>
</template>

<script>
export default {
    name: "DragAndDrop",
    data() {
        return {
            jobs: [],
        };
    },
    methods: {
        handleFileDrop(event) {
            event.preventDefault();
            const files = event.dataTransfer.files;

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                this.calculateImageDimensions(file);
            }
        },
        calculateImageDimensions(file) {
            const reader = new FileReader();

            reader.onload = (event) => {
                const imageData = event.target.result;

                const img = new Image();
                img.src = imageData;

                img.onload = () => {
                    const width = img.width;
                    const height = img.height;

                    const job = {
                        imageData: imageData,
                        width: width,
                        height: height,
                    };

                    this.jobs.push({
                        imageData: imageData, // Store the image data
                        width: width,
                        height: height,
                    });
                };
            };

            reader.readAsDataURL(file);
        },
    },
};
</script>

<style scoped>
.drop-zone {
    border: 2px dashed #ccc;
    padding: 20px;
    text-align: center;
}
</style>

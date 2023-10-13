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
                // Handle the response as needed
            } catch (error) {
                console.error('Error creating job:', error);
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
        calculateImageDimensions(file) {
            const reader = new FileReader();

            reader.onload = (event) => {
                const imageData = event.target.result;

                const img = new Image();
                img.src = imageData;

                img.onload = () => {
                    const width = img.width;
                    const height = img.height;

                    this.createJob(file, width, height);

                    const job = {
                        imageData: imageData,
                        width: width,
                        height: height,
                        file: file
                    };

                    this.jobs.push({
                        imageData: imageData, // Store the image data
                        width: width,
                        height: height,
                        file: file
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

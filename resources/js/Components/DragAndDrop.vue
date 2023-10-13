<template>
    <div class="drop-zone text-white" @dragover.prevent @drop="handleFileDrop">
        <p>Drag and drop files here</p>
    </div>

    <table v-if="jobs.length > 0">
        <thead>
        <tr>
            <th>Image</th>
            <th>Width</th>
            <th>Height</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="(job, index) in jobs" :key="index">
            <td><img :src="job.imageData" alt="Job Image" class="jobImg" /></td>
            <td>{{ job.width }}</td>
            <td>{{ job.height }}</td>
            <!-- Add options form here -->
        </tr>
        </tbody>
    </table>
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
                        file: file,
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

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

th, td {
    border: 1px solid #ccc;
    padding: 8px;
    text-align: center;
}

th {
    background-color: #f0f0f0;
}

.jobImg {
    width: 50px;
    height: 50px;
    display: block;
    margin: 0 auto;
}

</style>

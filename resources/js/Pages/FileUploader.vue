<template>
    <div>
        <input type="file" @change="handleFileSelected" />
        <button @click="uploadChunks" :disabled="file === null">Upload</button>
        <progress v-if="uploadProgress" :value="uploadProgress" max="100">
            {{ uploadProgress }}%
        </progress>
        <div v-if="uploadedFiles.length">
            Uploaded Files:
            <ul>
                <li v-for="file in uploadedFiles" :key="file.name">
                    {{ file.name }}
                </li>
            </ul>
        </div>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    data() {
        return {
            file: null,
            chunkSize: 1024 * 1024, // 1 MB
            totalChunks: 0,
            uploadProgress: 0,
            uploadedFiles: [],
        };
    },
    methods: {
        handleFileSelected(event) {
            this.file = event.target.files[0];
            this.totalChunks = Math.ceil(this.file.size / this.chunkSize);
        },
        async uploadChunks() {
            for (let i = 0; i < this.totalChunks; i++) {
                const chunk = await this.readChunk(i);
                const formData = new FormData();
                formData.append('chunk_index', i);
                formData.append('total_chunks', this.totalChunks);
                formData.append('filename', this.file.name);
                formData.append('file', chunk);

                try {
                    const response = await axios.post('/upload/chunks', formData, {
                        onUploadProgress: (progressEvent) => {
                            this.uploadProgress = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                        },
                    });
                    // Handle successful upload
                    if (i === this.totalChunks - 1) {
                        this.uploadedFiles.push({ name: this.file.name }); // Add to uploaded files list
                    }
                } catch (error) {
                    // Handle errors
                    console.error(error);
                }
            }
            this.uploadProgress = 0; // Reset progress
            this.file = null; // Clear file selection
        },
        async readChunk(chunkIndex) {
            const reader = new FileReader();
            const start = chunkIndex * this.chunkSize;
            const end = Math.min(start + this.chunkSize, this.file.size);
            const chunk = this.file.slice(start, end);
            await new Promise((resolve) => {
                reader.onload = () => {
                    resolve(reader.result);
                };
                reader.readAsDataURL(chunk);
            });
            return chunk;
        },
    },
};
</script>

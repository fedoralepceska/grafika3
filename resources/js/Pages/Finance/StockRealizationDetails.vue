<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="stock_realization_details" subtitle="StockRealizationDetails" icon="warehouse.png" :link="'/stock-realizations'"/>
            <div class="dark-gray p-2 text-white">
                <div class="form-container p-4">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h2 class="sub-title">Stock Realization #{{ stockRealization.id }}</h2>
                            <p class="text-gray-300">{{ stockRealization.invoice_title }}</p>
                        </div>
                        <div class="flex gap-3">
                            <button 
                                v-if="!stockRealization.is_realized" 
                                @click="realizeStock"
                                :disabled="realizing"
                                class="btn create-order"
                            >
                                <i :class="[realizing ? 'fa fa-spinner fa-spin' : 'fa fa-check']"></i>
                                {{ realizing ? 'Realizing...' : 'Realize Stock' }}
                            </button>
                            <button @click="goBack" class="btn cancel">
                                <i class="fa fa-arrow-left"></i>
                                Back to List
                            </button>
                        </div>
                    </div>

                    <!-- Stock Realization Info -->
                    <div class="bg-gray-800 p-4 rounded mb-6">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <label class="text-gray-400">Client</label>
                                <p class="text-white font-bold">{{ stockRealization.client.name }}</p>
                            </div>
                            <div>
                                <label class="text-gray-400">Start Date</label>
                                <p class="text-white">{{ formatDate(stockRealization.start_date) }}</p>
                            </div>
                            <div>
                                <label class="text-gray-400">End Date</label>
                                <p class="text-white">{{ formatDate(stockRealization.end_date) }}</p>
                            </div>
                            <div>
                                <label class="text-gray-400">Status</label>
                                <span :class="[getStatusColorClass(stockRealization.is_realized), 'font-bold', 'px-2 py-1 rounded']">
                                    {{ stockRealization.is_realized ? 'Realized' : 'Pending' }}
                                </span>
                            </div>
                            <div v-if="stockRealization.is_realized">
                                <label class="text-gray-400">Realized At</label>
                                <p class="text-white">{{ formatDateTime(stockRealization.realized_at) }}</p>
                            </div>
                            <div v-if="stockRealization.realized_by">
                                <label class="text-gray-400">Realized By</label>
                                <p class="text-white">{{ stockRealization.realized_by.name }}</p>
                            </div>
                        </div>
                        <div v-if="stockRealization.comment" class="mt-4">
                            <label class="text-gray-400">Comment</label>
                            <p class="text-white">{{ stockRealization.comment }}</p>
                        </div>
                    </div>

                    <!-- Jobs List -->
                    <div class="mb-6">
                        <h3 class="text-xl font-bold mb-4">Jobs</h3>
                        <div v-for="job in stockRealization.jobs" :key="job.id" class="bg-gray-800 p-4 rounded mb-4">
                            <div class="flex justify-between items-center mb-3">
                                <h4 class="text-lg font-semibold">{{ job.name }}</h4>
                                <button 
                                    v-if="!stockRealization.is_realized"
                                    @click="editJob(job)"
                                    class="btn btn-sm bg-blue-600 hover:bg-blue-700"
                                >
                                    <i class="fa fa-edit"></i> Edit
                                </button>
                            </div>

                            <!-- Job Details -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                                <div>
                                    <label class="text-gray-400">Quantity</label>
                                    <p class="text-white font-bold">{{ job.quantity }}</p>
                                </div>
                                <div>
                                    <label class="text-gray-400">Copies</label>
                                    <p class="text-white font-bold">{{ job.copies }}</p>
                                </div>
                                <div v-if="job.total_area_m2">
                                    <label class="text-gray-400">Total Area (m²)</label>
                                    <p class="text-white">{{ parseFloat(job.total_area_m2).toFixed(4) }}</p>
                                </div>
                                <div v-if="job.width && job.height">
                                    <label class="text-gray-400">Dimensions</label>
                                    <p class="text-white">{{ job.width }}cm × {{ job.height }}cm</p>
                                </div>
                            </div>

                            <!-- Materials -->
                            <div v-if="job.small_material || job.large_material" class="mb-4">
                                <h5 class="font-semibold mb-2">Legacy Materials</h5>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div v-if="job.small_material">
                                        <label class="text-gray-400">Small Material</label>
                                        <p class="text-white">{{ job.small_material.name || 'Unknown' }}</p>
                                    </div>
                                    <div v-if="job.large_material">
                                        <label class="text-gray-400">Large Material</label>
                                        <p class="text-white">{{ job.large_material.name || 'Unknown' }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Articles -->
                            <div v-if="job.articles && job.articles.length > 0">
                                <h5 class="font-semibold mb-2">Articles</h5>
                                <div class="space-y-2">
                                    <div 
                                        v-for="articleData in job.articles" 
                                        :key="articleData.id"
                                        class="bg-gray-700 p-3 rounded flex justify-between items-center"
                                    >
                                        <div>
                                            <p class="text-white font-semibold">{{ articleData.article.name }}</p>
                                            <p class="text-gray-400 text-sm">Code: {{ articleData.article.code || 'N/A' }}</p>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <div class="text-right">
                                                <p class="text-white">{{ parseFloat(articleData.quantity).toFixed(2) }} {{ articleData.unit_type }}</p>
                                                <p class="text-gray-400 text-sm">{{ articleData.source }}</p>
                                            </div>
                                            <button 
                                                v-if="!stockRealization.is_realized"
                                                @click="editArticle(job, articleData)"
                                                class="btn btn-xs bg-blue-600 hover:bg-blue-700"
                                            >
                                                <i class="fa fa-edit"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Job Modal -->
        <div v-if="editingJob" class="modal-overlay" @click="closeEditModal">
            <div class="modal-content" @click.stop>
                <h3 class="text-xl font-bold mb-4">Edit Job: {{ editingJob.name }}</h3>
                <form @submit.prevent="updateJob">
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">Quantity</label>
                            <input 
                                v-model.number="editForm.quantity" 
                                type="number" 
                                min="1" 
                                class="w-full p-2 border rounded text-black"
                                required
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Copies</label>
                            <input 
                                v-model.number="editForm.copies" 
                                type="number" 
                                min="1" 
                                class="w-full p-2 border rounded text-black"
                                required
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Width (cm)</label>
                            <input 
                                v-model.number="editForm.width" 
                                type="number" 
                                step="0.01" 
                                min="0" 
                                class="w-full p-2 border rounded text-black"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Height (cm)</label>
                            <input 
                                v-model.number="editForm.height" 
                                type="number" 
                                step="0.01" 
                                min="0" 
                                class="w-full p-2 border rounded text-black"
                            />
                        </div>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" @click="closeEditModal" class="btn cancel">Cancel</button>
                        <button type="submit" :disabled="updating" class="btn create-order">
                            {{ updating ? 'Updating...' : 'Update Job' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Article Modal -->
        <div v-if="editingArticle" class="modal-overlay" @click="closeArticleModal">
            <div class="modal-content" @click.stop>
                <h3 class="text-xl font-bold mb-4">Edit Article: {{ editingArticle.article.name }}</h3>
                <form @submit.prevent="updateArticle">
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2">Quantity</label>
                        <input 
                            v-model.number="articleForm.quantity" 
                            type="number" 
                            step="0.01" 
                            min="0" 
                            class="w-full p-2 border rounded text-black"
                            required
                        />
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" @click="closeArticleModal" class="btn cancel">Cancel</button>
                        <button type="submit" :disabled="updating" class="btn create-order">
                            {{ updating ? 'Updating...' : 'Update Article' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </MainLayout>
</template>

<script>
import MainLayout from "@/Layouts/MainLayout.vue";
import Header from "@/Components/Header.vue";
import axios from 'axios';
import { ref, reactive } from "vue";

export default {
    components: {
        MainLayout,
        Header,
    },
    props: {
        stockRealization: Object,
    },
    setup(props) {
        const realizing = ref(false);
        const updating = ref(false);
        const editingJob = ref(null);
        const editingArticle = ref(null);
        const editingJobRef = ref(null);
        
        const editForm = reactive({
            quantity: 0,
            copies: 0,
            width: 0,
            height: 0,
        });

        const articleForm = reactive({
            quantity: 0,
        });

        return {
            realizing,
            updating,
            editingJob,
            editingArticle,
            editingJobRef,
            editForm,
            articleForm,
        };
    },
    methods: {
        async realizeStock() {
            if (this.realizing) return;
            
            this.realizing = true;
            
            try {
                const response = await axios.post(`/stock-realizations/${this.stockRealization.id}/realize`);
                
                if (response.data.message) {
                    this.$toast.success('Stock realization completed successfully!');
                    this.$inertia.reload();
                } else {
                    this.$toast.error('Failed to complete stock realization');
                }
            } catch (error) {
                console.error('Error realizing stock:', error);
                this.$toast.error(error.response?.data?.error || 'Failed to complete stock realization');
            } finally {
                this.realizing = false;
            }
        },
        editJob(job) {
            this.editingJob = job;
            this.editingJobRef = job;
            this.editForm.quantity = job.quantity;
            this.editForm.copies = job.copies;
            this.editForm.width = job.width || 0;
            this.editForm.height = job.height || 0;
        },
        editArticle(job, articleData) {
            this.editingArticle = articleData;
            this.editingJobRef = job;
            this.articleForm.quantity = parseFloat(articleData.quantity);
        },
        async updateJob() {
            if (this.updating) return;
            
            this.updating = true;
            
            try {
                const response = await axios.put(
                    `/stock-realizations/${this.stockRealization.id}/jobs/${this.editingJob.id}`,
                    this.editForm
                );
                
                if (response.data.message) {
                    this.$toast.success('Job updated successfully!');
                    this.$inertia.reload();
                    this.closeEditModal();
                } else {
                    this.$toast.error('Failed to update job');
                }
            } catch (error) {
                console.error('Error updating job:', error);
                this.$toast.error(error.response?.data?.error || 'Failed to update job');
            } finally {
                this.updating = false;
            }
        },
        async updateArticle() {
            if (this.updating) return;
            
            this.updating = true;
            
            try {
                const response = await axios.put(
                    `/stock-realizations/${this.stockRealization.id}/jobs/${this.editingJobRef.id}/articles/${this.editingArticle.id}`,
                    this.articleForm
                );
                
                if (response.data.message) {
                    this.$toast.success('Article updated successfully!');
                    this.$inertia.reload();
                    this.closeArticleModal();
                } else {
                    this.$toast.error('Failed to update article');
                }
            } catch (error) {
                console.error('Error updating article:', error);
                this.$toast.error(error.response?.data?.error || 'Failed to update article');
            } finally {
                this.updating = false;
            }
        },
        closeEditModal() {
            this.editingJob = null;
            this.editingJobRef = null;
        },
        closeArticleModal() {
            this.editingArticle = null;
            this.editingJobRef = null;
        },
        goBack() {
            this.$inertia.visit('/stock-realizations');
        },
        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        },
        formatDateTime(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },
        getStatusColorClass(isRealized) {
            return isRealized ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800';
        },
    },
};
</script>

<style scoped>
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.7);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.modal-content {
    background-color: white;
    color: black;
    padding: 2rem;
    border-radius: 8px;
    max-width: 500px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
}

.btn-xs {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}
</style>

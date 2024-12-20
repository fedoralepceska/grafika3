<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <Header title="Catalog" subtitle="createNewCatalogItem" icon="List.png" link="catalog"/>


        <div class="dark-gray p-2 text-white">
            <div class="form-container p-2 ">

                    <h2 class="sub-title">Catalog item Creation</h2>

            <form @submit.prevent="submit" class="space-y-6 w-full rounded-lg">
                <!-- Basic Information -->
                <div class="grid grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <label class="text-white">Name</label>
                            <input
                                v-model="form.name"
                                type="text"
                                class="w-full mt-1 rounded"
                                required
                            />
                        </div>

                        <div>
                            <label class="text-white">Machine Print</label>
                            <select
                                v-model="form.machinePrint"
                                class="w-full mt-1 rounded"
                            >
                                <option value="">Select Machine</option>
                                <option v-for="machine in machinesPrint"
                                        :key="machine.id"
                                        :value="machine.name">
                                    {{ machine.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="text-white">Machine Cut</label>
                            <select
                                v-model="form.machineCut"
                                class="w-full mt-1 rounded"
                            >
                                <option value="">Select Machine</option>
                                <option v-for="machine in machinesCut"
                                        :key="machine.id"
                                        :value="machine.name">
                                    {{ machine.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="text-white">Category</label>
                            <select
                                v-model="form.category"
                                class="w-full mt-1 rounded"
                            >
                                <option value="">Select Machine</option>
                                <option v-for="category in categories"
                                        :key="category"
                                        :value="category"
                                >
                                    {{ category.replace('_', ' ').split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ') }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="text-white">Large Format Material</label>
                            <select
                                v-model="form.large_material_id"
                                class="w-full mt-1 rounded"
                                :disabled="form.small_material_id !== null"
                            >
                                <option value="">Select Material</option>
                                <option v-for="material in largeMaterials"
                                        :key="material.id"
                                        :value="material.id">
                                    {{ material.article?.name }} ({{ material.article?.code }})
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="text-white">Small Format Material</label>
                            <select
                                v-model="form.small_material_id"
                                class="w-full mt-1 rounded"
                                :disabled="form.large_material_id !== null"
                            >
                                <option value="">Select Material</option>
                                <option v-for="material in smallMaterials"
                                        :key="material.id"
                                        :value="material.id">
                                    {{ material.article?.name }} ({{ material.article?.code }})
                                </option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-white">Quantity</label>
                                <input
                                    v-model="form.quantity"
                                    type="number"
                                    min="1"
                                    class="w-full mt-1 rounded"
                                    required
                                />
                            </div>
                            <div>
                                <label class="text-white">Copies</label>
                                <input
                                    v-model="form.copies"
                                    type="number"
                                    min="1"
                                    class="w-full mt-1 rounded"
                                    required
                                />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 pt-9">
                            <div>
                                <Checkbox name="is_for_offer" v-model:checked="form.is_for_offer" />
                                <label class="text-white ml-2">For Offer</label>
                            </div>
                            <div>
                                <Checkbox name="is_for_sales" v-model:checked="form.is_for_sales" />
                                <label class="text-white ml-2">For Sales</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- File Section -->
                <div class="file-upload mt-6">
                    <h3 class="text-white text-lg font-semibold mb-4">File Upload</h3>
                    <div
                        class="upload-area"
                        @dragover.prevent
                        @drop.prevent="handleDrop"
                        @click="triggerFileInput"
                    >
                        <input
                            type="file"
                            id="file-input"
                            class="hidden"
                            @change="handleFileInput"
                            accept=".pdf, .png, .jpg, .jpeg"
                        />
                        <div v-if="!previewUrl" class="placeholder-content">
                            <div class="upload-icon">
                                <span class="mdi mdi-cloud-upload text-4xl"></span>
                            </div>
                            <p class="upload-text">Drag and drop your file here</p>
                            <p class="upload-text-sub">or click to browse</p>
                            <p class="file-types">Supported formats: PDF, PNG, JPG, JPEG</p>
                        </div>
                        <div v-else class="preview-container">
                            <img
                                v-if="isImage"
                                :src="previewUrl"
                                alt="Preview"
                                class="preview-image"
                            />
                            <div v-else class="pdf-preview">
                                <span class="mdi mdi-file-pdf text-4xl"></span>
                                <span class="pdf-name">{{ fileName }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Actions Section -->
                <div class="mt-6">
                    <h3 class="text-white text-lg font-semibold mb-4">Actions</h3>
                    <div class="space-y-4">
                        <div v-for="(action, index) in form.actions" :key="index"
                             class="flex items-center space-x-4 light-gray p-4 rounded">
                            <div class="flex-1">
                                <select
                                    :value="action.selectedAction"
                                    @input="(e) => handleActionSelect(e, action)"
                                    class="w-full rounded option"
                                    :class="{ 'border-red-500': !action.selectedAction }"
                                    required
                                >
                                    <option class="option" value="">Select Action</option>
                                    <option v-for="availableAction in availableActions"
                                            :key="availableAction.id"
                                            :value="availableAction.id"
                                            :selected="action.selectedAction === availableAction.id"
                                            class="option"
                                    >
                                        {{ availableAction.name }}
                                    </option>
                                </select>
                            </div>
                            <div v-if="action.showQuantity" class="w-32">
                                <input
                                    v-if="action.showQuantity"
                                    v-model="action.quantity"
                                    type="number"
                                    min="0"
                                    class="w-full rounded option"
                                    :class="{ 'border-red-500': action.showQuantity && (!action.quantity || action.quantity <= 0) }"
                                    placeholder="Quantity"
                                    required
                                />
                            </div>
                            <button
                                type="button"
                                @click="removeAction(index)"
                                class="text-red-500 hover:text-red-700"
                            >
                                <span class="mdi mdi-delete"></span>
                            </button>
                        </div>

                        <button
                            type="button"
                            @click="addAction"
                            class="text-green-500 hover:text-green-700"
                        >
                            <span class="mdi mdi-plus-circle"></span> Add Action
                        </button>
                    </div>
                </div>

                <div class="flex justify-end space-x-4">
                    <button type="submit" class="btn btn-primary">
                        Create Catalog Item
                    </button>
                </div>
            </form>
            </div>
        </div>
        </div>
    </MainLayout>
</template>

<script>
import MainLayout from '@/Layouts/MainLayout.vue';
import Header from '@/Components/Header.vue';
import { Link } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import Checkbox from '@/Components/inputs/Checkbox.vue';

export default {
    components: {
        MainLayout,
        Header,
        Link,
        Checkbox
    },

    props: {
        actions: Array,
        largeMaterials: Array,
        smallMaterials: Array,
        machinesPrint: Array,
        machinesCut: Array,
    },

    data() {
        return {
            form: {
                name: '',
                machinePrint: '',
                machineCut: '',
                large_material_id: null,
                small_material_id: null,
                quantity: 1,
                copies: 1,
                actions: [],
                is_for_offer: false,
                is_for_sales: true,
                category: '',
                file: 'placeholder.jpeg',
            },
            categories: ['material', 'article', 'small_format'],
            previewUrl: null, // URL for file preview
            fileName: '', // Stores the file name
        }
    },

    computed: {
        availableActions() {
            return this.actions.filter(action => {
                return !this.form.actions.some(selectedAction =>
                    selectedAction.selectedAction === action.id
                )
            })
        }
    },

    methods: {
        addAction() {
            this.form.actions.push({
                selectedAction: '',
                quantity: 0,
                showQuantity: false
            })
        },

        handleActionSelect(event, action) {
            action.selectedAction = event.target.value;
            this.handleActionChange(action);
        },

        handleActionChange(action) {
            if (!action.selectedAction) {
                action.showQuantity = false;
                return;
            }
            const selectedAction = this.actions.find(a => a.id === parseInt(action.selectedAction));

            if (!selectedAction?.name) {
                console.error('Selected action has no name:', selectedAction);
                return;
            }

            action.action_id = {
                id: selectedAction.id,
                name: selectedAction.name
            };
            action.status = 'Not started yet';
            action.showQuantity = selectedAction?.isMaterialized ?? false;

            if (!action.showQuantity) {
                action.quantity = 0;
            }
        },

        removeAction(index) {
            this.form.actions.splice(index, 1)
        },
        async handleDrop(event) {
            const file = event.dataTransfer.files[0];
            this.processFile(file);
        },
        async handleFileInput(event) {
            const file = event.target.files[0];
            this.processFile(file);
        },
        processFile(file) {
            if (!file) return;

            // Store file and generate preview URL
            this.form.file = file;
            this.fileName = file.name;

            // Check file type for preview
            if (file.type.startsWith("image/")) {
                this.isImage = true;
                this.previewUrl = URL.createObjectURL(file);
            } else if (file.type === "application/pdf") {
                this.isImage = false;
                this.previewUrl = "/storage/uploads/placeholder.jpeg"; // Placeholder for PDFs
            }
        },
        isImage() {
            return this.form.file && this.form.file.type.startsWith("image/");
        },
        validateActions() {
            if (this.form.actions.length === 0) {
                return 'At least one action is required';
            }

            for (let i = 0; i < this.form.actions.length; i++) {
                const action = this.form.actions[i];
                if (!action.selectedAction) {
                    return 'All actions must have a selected type';
                }
                if (action.showQuantity && (!action.quantity || action.quantity <= 0)) {
                    return 'Quantity is required for materialized actions';
                }
            }
            return null;
        },
        async submit() {
            const toast = useToast();
            
            // Validate actions before submission
            const actionError = this.validateActions();
            if (actionError) {
                toast.error(actionError);
                return;
            }

            const formData = new FormData();

            // Append fields
            Object.entries(this.form).forEach(([key, value]) => {
                if (key !== 'actions' && key !== 'file') {
                    formData.append(key, value);
                }
            });

            // Append file
            if (this.form.file instanceof File) {
                formData.append('file', this.form.file);
            }

            // Serialize actions
            this.form.actions.forEach((action, index) => {
                formData.append(`actions[${index}][id]`, action.selectedAction);
                formData.append(`actions[${index}][quantity]`, action.quantity || 0);
                formData.append(`actions[${index}][isMaterialized]`, action.showQuantity || false);
            });

            try {
                const response = await axios.post(route('catalog.store'), formData, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                });

                toast.success('Catalog item created successfully');
                this.$inertia.visit(route('catalog.index'));
            } catch (error) {
                if (error.response?.data?.errors) {
                    Object.values(error.response.data.errors).forEach(errors => {
                        errors.forEach(error => toast.error(error));
                    });
                } else {
                    toast.error('An error occurred while creating the catalog item');
                }
            }
        },
        triggerFileInput() {
            document.getElementById("file-input").click();
        },
    },

    mounted() {
        this.addAction(); // Add first action row by default
    }
}
</script>

<style scoped lang="scss">
.form-container {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    justify-content: space-between;
}
.dark-gray {
    background-color: $dark-gray;
    justify-content: left;
    align-items: center;
    min-height: 20vh;
    min-width: 80vh;
}
.light-gray{
    background-color: $light-gray;
}
.sub-title{
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    color: $white;
}
.btn {
    padding: 9px 12px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    border-radius: 2px;
    background-color: $green;
}

.option, input, option, select {
    color: black;
}
.preview-image {
    width: 100px;
    height: 100px;
}

.file-upload {
    width: 100%;
    max-width: 400px;
}

.upload-area {
    background-color: $light-gray;
    border: 2px dashed $ultra-light-gray;
    border-radius: 8px;
    padding: 2rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;

    &:hover {
        border-color: $green;
        background-color: rgba($light-gray, 0.7);
    }
}

.placeholder-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    color: $white;
}

.upload-icon {
    color: $ultra-light-gray;
    margin-bottom: 1rem;
}

.upload-text {
    font-size: 1.1rem;
    font-weight: 500;
}

.upload-text-sub {
    font-size: 0.9rem;
    color: $ultra-light-gray;
}

.file-types {
    font-size: 0.8rem;
    color: $ultra-light-gray;
    margin-top: 1rem;
}

.preview-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
}

.preview-image {
    max-width: 200px;
    max-height: 200px;
    border-radius: 4px;
    object-fit: contain;
}

.pdf-preview {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    color: $white;

    .mdi-file-pdf {
        color: #ff4444;
    }

    .pdf-name {
        font-size: 0.9rem;
        word-break: break-all;
        max-width: 200px;
    }
}

</style>

<template>
    <v-row>
        <v-dialog
            v-model="dialog"
            persistent
            max-width="600"
            class="height"
            @keydown.esc="closeDialog"
        >
            <template v-slot:activator="{ props }">
                <button v-bind="props" class="btn btn-secondary">{{ $t('viewSubcategories') }}</button>
            </template>
            <v-card class="height background">
                <v-card-title>
                    <span class="text-h5 text-white">{{ $t('manageSubcategories') }}</span>
                </v-card-title>
                <v-card-text>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-gray-700 rounded">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 text-left text-white">{{ $t('name') }}</th>
                                    <th class="px-6 py-3 text-right text-white">{{ $t('actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="subcategory in subcategories" :key="subcategory.id" class="border-t border-gray-600">
                                    <td class="px-6 py-4 text-white">
                                        <div v-if="editingId === subcategory.id">
                                            <input
                                                v-model="editingName"
                                                type="text"
                                                class="rounded text-black w-full"
                                                @keyup.enter="updateSubcategory(subcategory)"
                                                @keyup.esc="cancelEdit"
                                            />
                                        </div>
                                        <div v-else>
                                            {{ subcategory.name }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div v-if="editingId === subcategory.id">
                                            <button
                                                @click="updateSubcategory(subcategory)"
                                                class="text-green-500 hover:text-green-700 mr-2"
                                            >
                                                <span class="mdi mdi-check"></span>
                                            </button>
                                            <button
                                                @click="cancelEdit"
                                                class="text-red-500 hover:text-red-700"
                                            >
                                                <span class="mdi mdi-close"></span>
                                            </button>
                                        </div>
                                        <div v-else>
                                            <button
                                                @click="startEdit(subcategory)"
                                                class="text-blue-500 hover:text-blue-700 mr-2"
                                            >
                                                <span class="mdi mdi-pencil"></span>
                                            </button>
                                            <button
                                                @click="deleteSubcategory(subcategory)"
                                                class="text-red-500 hover:text-red-700"
                                            >
                                                <span class="mdi mdi-delete"></span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </v-card-text>
                <v-card-actions class="flexSpace gap-4">
                    <v-spacer></v-spacer>
                    <SecondaryButton @click="closeDialog" class="red">
                        {{ $t('close') }}
                    </SecondaryButton>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-row>
</template>

<script>
import SecondaryButton from "@/Components/buttons/SecondaryButton.vue";
import { useToast } from 'vue-toastification';

export default {
    components: {
        SecondaryButton
    },
    
    emits: ['updated', 'deleted'],
    
    data() {
        return {
            dialog: false,
            subcategories: [],
            editingId: null,
            editingName: '',
            loading: false
        }
    },
    
    watch: {
        dialog(newVal) {
            if (newVal) {
                this.fetchSubcategories();
            }
        }
    },
    
    methods: {
        async fetchSubcategories() {
            try {
                const response = await axios.get(route('subcategories.index'));
                this.subcategories = response.data;
            } catch (error) {
                const toast = useToast();
                toast.error(this.$t('errorFetchingSubcategories'));
            }
        },
        
        startEdit(subcategory) {
            this.editingId = subcategory.id;
            this.editingName = subcategory.name;
        },
        
        cancelEdit() {
            this.editingId = null;
            this.editingName = '';
        },
        
        async updateSubcategory(subcategory) {
            const toast = useToast();
            
            try {
                const response = await axios.put(route('subcategories.update', subcategory.id), {
                    name: this.editingName
                });
                
                const index = this.subcategories.findIndex(s => s.id === subcategory.id);
                this.subcategories[index] = response.data;
                
                this.cancelEdit();
                toast.success(this.$t('subcategoryUpdated'));
                this.$emit('updated', response.data);
            } catch (error) {
                if (error.response?.data?.errors) {
                    Object.values(error.response.data.errors).forEach(errors => {
                        errors.forEach(error => toast.error(error));
                    });
                } else {
                    toast.error(this.$t('errorUpdatingSubcategory'));
                }
            }
        },
        
        async deleteSubcategory(subcategory) {
            const toast = useToast();
           
            try {
                await axios.delete(route('subcategories.destroy', subcategory.id));
                this.subcategories = this.subcategories.filter(s => s.id !== subcategory.id);
                toast.success(this.$t('subcategoryDeleted'));
                this.$emit('deleted', subcategory.id);
            } catch (error) {
                toast.error(this.$t('errorDeletingSubcategory'));
            }
        },
        
        closeDialog() {
            this.dialog = false;
            this.cancelEdit();
        }
    }
}
</script>

<style scoped lang="scss">
.height {
    height: calc(100vh - 100px);
}
.background {
    background-color: $light-gray;
}
.flexSpace {
    display: flex;
    justify-content: space-between;
}
.red {
    background-color: $red;
    color: white;
    border: none;
}
.btn {
    padding: 9px 12px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    border-radius: 2px;
    background-color: $blue;
    color: white;
}
</style> 
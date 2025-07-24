<template>
    <div class="d-flex justify-center align-center">
        <v-row>
            <v-dialog
                v-model="dialog"
                persistent
                max-width="500"
                class="height"
                @keydown.esc="closeDialog"
            >
                <v-card class="height background">
                    <v-card-title>
                        <span class="text-h5 text-white">Update Refinement</span>
                    </v-card-title>
                    <v-card-text>
                        <div v-if="showAddRefinementForm">
                            <form>
                                <div class="p-1 d-flex justify-center">
                                    <div class="flex text-white items-center">
                                        <div class="items-center">
                                            <div class="form-group gap-3">
                                                <label for="newRefinementName" class="width100">Name</label>
                                                <input
                                                    type="text"
                                                    id="newRefinementName"
                                                    class="text-black rounded"
                                                    v-model="updatingRefinement.name"
                                                    style="width: 50vh"
                                                />
                                            </div>
                                            <div class="form-group gap-3">
                                                <label for="isMaterialRefinement">
                                                    Material Refinement
                                                    <input
                                                        type="checkbox"
                                                        class="rounded"
                                                        id="isMaterialRefinement"
                                                        v-model="updatingRefinement.isMaterialRefinement"
                                                        style="padding: 8px"
                                                    />
                                                </label>
                                            </div>
                                            <div class="form-group gap-3">
                                                <label for="materialSelect" class="width100">Material</label>
                                                <select
                                                    id="materialSelect"
                                                    v-model="updatingRefinement.materials"
                                                    class="text-black rounded"
                                                    style="width: 50vh"
                                                    :key="updatingRefinement.materials?.id || 'no-material'"
                                                >
                                                    <option value="" disabled>Select a material</option>
                                                    <option v-for="material in availableMaterials" :value="material" :key="material.id">
                                                        {{ material.name }}
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </v-card-text>
                    <v-card-actions class="flexSpace gap-4">
                        <v-spacer></v-spacer>
                        <SecondaryButton @click="closeDialog" class="red">Close</SecondaryButton>
                        <SecondaryButton @click="updateItem()" class="green">Update</SecondaryButton>
                    </v-card-actions>
                </v-card>
            </v-dialog>
        </v-row>
    </div>
</template>

<script>
import SecondaryButton from "@/Components/buttons/SecondaryButton.vue";
import PrimaryButton from "@/Components/buttons/PrimaryButton.vue";
import { useToast } from "vue-toastification";
import axios from "axios";

export default {
    components: {
        PrimaryButton,
        SecondaryButton
    },
    data() {
        return {
            dialog: false,
            showAddRefinementForm: false,
            newRefinement: {
                name: "",
                isMaterialRefinement: false,
                materials: [],
            },
            updatingRefinement: {},
            availableMaterials: [],
        };
    },
    props: {
        refinement: Object,
    },
    methods: {
        async openDialog() {
            // Check if refinement is selected
            if (!this.refinement) {
                console.warn('No refinement selected');
                return;
            }
            
            // Always ensure materials are loaded first, even if they were loaded before
            await this.generateMaterials();
            
            this.updatingRefinement = Object.assign({}, this.refinement);
            
            // Set the current material selection based on the refinement's material relationships
            if (this.refinement.small_material) {
                // Find the exact material in availableMaterials to ensure proper binding
                const material = this.availableMaterials.find(m => m.id === this.refinement.small_material.id);
                this.updatingRefinement.materials = material || this.refinement.small_material;
                this.updatingRefinement.isMaterialRefinement = this.refinement.isMaterialized === 1;
                console.log('Setting small material:', this.updatingRefinement.materials);
            } else if (this.refinement.large_format_material) {
                // Find the exact material in availableMaterials to ensure proper binding
                const material = this.availableMaterials.find(m => m.id === this.refinement.large_format_material.id);
                this.updatingRefinement.materials = material || this.refinement.large_format_material;
                this.updatingRefinement.isMaterialRefinement = this.refinement.isMaterialized === 1;
                console.log('Setting large material:', this.updatingRefinement.materials);
            }
            
            console.log('Refinement data:', this.refinement);
            console.log('Updating refinement:', this.updatingRefinement);
            console.log('Available materials:', this.availableMaterials);
            
            this.showAddRefinementForm = true;
            this.dialog = true;
        },
        closeDialog() {
            this.dialog = false;
            this.showAddRefinementForm = false;
            this.updatingRefinement = {};
            // Don't emit refinement-updated when just closing - only emit on actual updates
        },

        async updateItem() {
            const toast = useToast();
            try {
                const updatedObject = {};
                updatedObject.name = this.updatingRefinement.name;
                updatedObject.isMaterialRefinement = this.updatingRefinement.isMaterialRefinement;
                
                // Determine material type based on the selected material
                if (this.updatingRefinement.materials) {
                    // Check if it's a small material (has small_format_material_id) or large material
                    if (this.updatingRefinement.materials.small_format_material_id !== undefined && 
                        this.updatingRefinement.materials.small_format_material_id !== null) {
                        updatedObject.material_type = 'SmallMaterial';
                    } else {
                        updatedObject.material_type = 'LargeFormatMaterial';
                    }
                    updatedObject.material_id = this.updatingRefinement.materials.id;
                } else {
                    // If no material is selected, set default values
                    updatedObject.material_type = null;
                    updatedObject.material_id = null;
                }
                
                console.log('Sending update data:', updatedObject);
                const response = await axios.put(`/refinements/${this.refinement.id}`, updatedObject);
                toast.success(response.data.message);
                
                // Emit the event only after successful update
                this.$emit('refinement-updated');
                
                this.closeDialog();
            } catch (error) {
                toast.error("Error updating refinement!");
                console.error(error);
            }
        },
        handleEscapeKey(event) {
            if (event.key === 'Escape') {
                this.closeDialog();
            }
        },
        async generateMaterials() {
            const large = await axios.get('/materials/large/all');
            const small = await axios.get('/materials/small/all');
            this.availableMaterials = [...large.data, ...small.data];
        },
    },
    async mounted() {
        await this.generateMaterials();
        document.addEventListener('keydown', this.handleEscapeKey);
    },
    watch: {
        dialog(newVal) {
            if (newVal && this.refinement) {
                // Ensure form is properly initialized when dialog opens
                this.$nextTick(() => {
                    this.updatingRefinement = Object.assign({}, this.refinement);
                    
                    if (this.refinement.small_material) {
                        const material = this.availableMaterials.find(m => m.id === this.refinement.small_material.id);
                        this.updatingRefinement.materials = material || this.refinement.small_material;
                        this.updatingRefinement.isMaterialRefinement = this.refinement.isMaterialized === 1;
                    } else if (this.refinement.large_format_material) {
                        const material = this.availableMaterials.find(m => m.id === this.refinement.large_format_material.id);
                        this.updatingRefinement.materials = material || this.refinement.large_format_material;
                        this.updatingRefinement.isMaterialRefinement = this.refinement.isMaterialized === 1;
                    }
                });
            }
        },
        refinement: {
            handler(newRefinement) {
                if (newRefinement && this.dialog) {
                    // If dialog is open and refinement changes, update the form
                    this.$nextTick(() => {
                        this.updatingRefinement = Object.assign({}, newRefinement);
                        
                        if (newRefinement.small_material) {
                            const material = this.availableMaterials.find(m => m.id === newRefinement.small_material.id);
                            this.updatingRefinement.materials = material || newRefinement.small_material;
                            this.updatingRefinement.isMaterialRefinement = newRefinement.isMaterialized === 1;
                        } else if (newRefinement.large_format_material) {
                            const material = this.availableMaterials.find(m => m.id === newRefinement.large_format_material.id);
                            this.updatingRefinement.materials = material || newRefinement.large_format_material;
                            this.updatingRefinement.isMaterialRefinement = newRefinement.isMaterialized === 1;
                        }
                    });
                }
            },
            deep: true
        }
    },
};
</script>

<style scoped lang="scss">
.form-group {
    display: flex;
    justify-content: left;
    align-items: center;
    width: 450px;
    color: $white;
    padding-bottom: 12px;
}
.width100 {
    width: 100px;
}
select{
    color: black;
    width: 225px;
    border-radius: 3px;
}
.type{
    display: flex;
    justify-content: space-evenly;
}
.label-input-group {
    display: flex;
    flex-direction: column;
}
input {
    margin: 0 !important;
}
.btn {
    padding: 9px 12px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    border-radius: 2px;
    background-color: $blue;
    margin-top: 12px;
}

.height {
    height: 50vh;
}
.background {
    background-color: $light-gray;
}
.flexSpace {
    display: flex;
    justify-content: space-around;
}
input {
    margin: 12px 0;
}
.red {
    background-color: $red;
    color: white;
    border: none;
}
.green {
    background-color: $green;
    color: white;
    border: none;
}
.redBackground{
    background-color: $red;
}
.greenBackground{
    background-color: $green;
}
</style>

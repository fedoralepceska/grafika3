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
                <template v-slot:activator="{ props }">
                    <div v-bind="props" class="bt">
                        <button class="btn" @click="openAddItemForm">Update</button>
                    </div>
                </template>
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
                                                    disabled
                                                    type="text"
                                                    id="newRefinementName"
                                                    class="text-black rounded"
                                                    :value="refinement.name"
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
                                                        :checked="refinement.isMaterialized"
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
                                                >
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
        openDialog() {
            this.updatingRefinement = Object.assign({}, this.refinement);
            this.dialog = true;
        },
        closeDialog() {
            this.dialog = false;
            this.updatingRefinement = {};
            window.location.reload();
        },
        openAddItemForm() {
            this.showAddRefinementForm = true;
        },
        async updateItem() {
            const toast = useToast();
            try {
                const updatedObject = {};
                updatedObject.material_type = this?.updatingRefinement?.materials?.small_format_material_id === null ? 'SmallMaterial' : 'LargeFormatMaterial';
                updatedObject.material_id = this?.updatingRefinement?.materials?.id;
                const response = await axios.put(`/refinements/${this.refinement.id}`, updatedObject);
                toast.success(response.data.message);
                this.closeDialog();
            } catch (error) {
                toast.error("Error updating article!");
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

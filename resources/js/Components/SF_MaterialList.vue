<template>
    <div class="head">
        <Header title="material" subtitle="SmallFormatMaterials" icon="Materials.png" link="materials-small-format"/>
        <div class="button">
            <button @click="navigateToAddSmallMaterial" class="btn add-material">
                Add New Material <i class="fa fa-plus"></i>
            </button>
        </div>
    </div>
    <div class="dark-gray p-5 text-white">
        <div class="form-container p-2 light-gray">
            <h2 class="sub-title">
                {{ $t('listOfSmallFormat') }}
            </h2>

            <div class="flex justify-end">
                <button @click="toggleEditFormatMode" class="bg-white rounded text-black py-2 px-5 m-1 ">{{ editModeFormat ? 'Exit Edit Mode' : 'Edit Mode' }}</button>
                <button @click="saveChanges()" v-if="editModeFormat" class="blue rounded text-white py-2 px-5 m-1">Save Changes<v-icon class="mdi mdi-check"></v-icon></button>
            </div>

            <table>
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Quantity</th>
                    <th v-if="materials[0].price_per_unit">Price Per Unit</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="material in materials" :key="material.id">
                    <td>{{ material.name }}</td>
                    <td v-if="editModeFormat">
                        <input type="text" class="text-black" v-model="material.editableQuantity" />
                    </td>
                    <td v-else>
                        {{material.quantity}}
                    </td>
                    <template v-if="material.price_per_unit">
                        <td v-if="editModeFormat">
                            <input type="text" v-model="material.editablePricePerUnit"  class="text-black"/>
                        </td>
                        <td v-else>
                            {{material.price_per_unit}}
                        </td>
                    </template>
                    <td class="centered">
                        <SecondaryButton @click="deleteMaterial(material)" class="delete">Delete</SecondaryButton>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
    <Header title="material" subtitle="smallMaterial" icon="Materials.png"/>
    <div class="dark-gray p-5 text-white mb-4">
        <div class="form-container p-2 light-gray">
            <h2 class="sub-title">
                {{ $t('listOfSmallMaterials') }}
            </h2>

            <div class="flex justify-end">
                <button @click="toggleEditMaterialMode" class="bg-white rounded text-black py-2 px-5 m-1 ">{{ editModeMaterial ? 'Exit Edit Mode' : 'Edit Mode' }}</button>
                <button @click="saveChangesSmall()" v-if="editModeMaterial" class="blue rounded text-white py-2 px-5 m-1">Save Changes<v-icon class="mdi mdi-check"></v-icon></button>
            </div>

            <table>
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Quantity</th>
                    <th>Material - Small Format</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="material in smallMaterials" :key="material.id">
                    <td>{{ material.name }}</td>
                    <td v-if="editModeMaterial">
                        <input type="text" class="text-black" v-model="material.editableQuantity" />
                    </td>
                    <td v-else>
                        {{material.quantity}}
                    </td>
                    <td style="font-weight: bolder">{{ material.small_format_material.name }}</td>
                    <td class="centered">
                        <SecondaryButton @click="deleteMaterialSmall(material)" class="delete">Delete</SecondaryButton>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script>
import PrimaryButton from "@/Components/buttons/PrimaryButton.vue";
import SecondaryButton from "@/Components/buttons/SecondaryButton.vue";
import axios from "axios";
import {useToast} from "vue-toastification";
import Header from "@/Components/Header.vue";


export default {
    components: { SecondaryButton, PrimaryButton, Header },
    props: {
        materials: Array,
        smallMaterials: Array
    },
    data() {
        return {
            editModeFormat: false,
            editModeMaterial: false
        };
    },
    methods: {
        toggleEditMaterialMode() {
            this.editModeMaterial = !this.editModeMaterial;
            if (this.editModeMaterial) {
                this.materials.forEach((material) => {
                    if (!material.editableQuantity) {
                        material.editableQuantity = material.quantity;
                    }
                    if (!material.editablePricePerUnit) {
                        material.editablePricePerUnit = material.price_per_unit;
                    }
                });
            }
        },
        toggleEditFormatMode() {
            this.editModeFormat = !this.editModeFormat;
            if (this.editModeFormat) {
                this.materials.forEach((material) => {
                    if (!material.editableQuantity) {
                        material.editableQuantity = material.quantity;
                    }
                    if (!material.editablePricePerUnit) {
                        material.editablePricePerUnit = material.price_per_unit;
                    }
                });
            }
        },
        navigateToAddSmallMaterial(){
            this.$inertia.visit(`/smallFormat/materials/create`);
        },
        async deleteMaterial(material) {
            const confirmed = confirm('Are you sure you want to delete this material?');
            if (!confirmed) {
                return;
            }

            try {
                if (material.price_per_unit) {
                    await axios.delete(`/materials-small-format/${material.id}`);
                }
                else {
                    await axios.delete(`/materials-small/${material.id}`);
                }
                // Remove the material from the materials array
                const index = this.materials.findIndex((m) => m.id === material.id);
                if (index !== -1) {
                    this.materials.splice(index, 1);
                }
            } catch (error) {
                console.error('Error deleting material:', error);
            }
            window.location.reload();
        },
        async deleteMaterialSmall(material) {
            const toast = useToast();
            const confirmed = confirm('Are you sure you want to delete this material?');
            if (!confirmed) {
                return;
            }

            try {
                await axios.delete(`/materials-small/${material.id}`);
                // Remove the material from the materials array
                const index = this.smallMaterials.findIndex((m) => m.id === material.id);
                if (index !== -1) {
                    this.smallMaterials.splice(index, 1);
                }
                toast.success("Material successfully deleted!");
            } catch (error) {
                toast.error("Cannot delete material. Job with this material currently exists!");
                console.error('Error deleting material:', error);
            }
            // window.location.reload();
        },
        async saveChanges() {
            if (!this.editModeFormat) {
                // Exit edit mode
                return;
            }
            // Handle the save changes action
            const promises = this.materials.map(async (material) => {
                if (material.price_per_unit && (
                    material.editableQuantity !== material.quantity ||
                    material.editablePricePerUnit !== material.price_per_unit)
                ) {
                    try {
                        const response = await axios.put(`/materials-small-format/${material.id}`, {
                            quantity: material.editableQuantity,
                            price_per_unit: material.editablePricePerUnit,
                        });
                        // Update the material with the response data
                        material.quantity = response.data.quantity;
                        material.price_per_unit = response.data.price_per_unit;
                    } catch (error) {
                        console.error('Error updating material:', error);
                    }
                }
            });

            await Promise.all(promises);

            // Reset the editable fields and exit edit mode
            this.materials.forEach((material) => {
                material.editableQuantity = null;
                material.editablePricePerUnit = null;
            });
            this.editModeFormat = false;
            window.location.reload();
        },
        async saveChangesSmall() {
            if (!this.editModeMaterial) {
                // Exit edit mode
                return;
            }
            // Handle the save changes action
            const promises = this.smallMaterials.map(async (material) => {
                try {
                    const response = await axios.put(`/materials-small/${material.id}`, {
                        quantity: material.editableQuantity,
                    });
                    // Update the material with the response data
                    material.quantity = response.data.quantity;
                } catch (error) {
                    console.error('Error updating material:', error);
                }
            });

            await Promise.all(promises);

            // Reset the editable fields and exit edit mode
            this.smallMaterials.forEach((material) => {
                material.editableQuantity = null;
            });
            this.editModeMaterial = false;
            window.location.reload();
        },
    },
};
</script>
<style scoped lang="scss">
[type='text'], input:where(:not([type])), [type='email'], [type='url'],
[type='password'], [type='number'], [type='date'], [type='datetime-local'],
[type='month'], [type='search'], [type='tel'], [type='time'], [type='week'],
[multiple], textarea, select{
    border-radius: 3px;
}
.centered{
    text-align: center;
}
.delete{
    border: none;
    color: white;
    background-color: $red;
}
.delete:hover{
    background-color: darkred;
}
.green-text{
    color: $green;
}
.blue{
    background-color: $blue;
}
.green{
    background-color: $green;
}
.green:hover{
    background-color: green;
}
.header{
    display: flex;
    align-items: center;
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

.client-form {
    width: 100%;
    justify-content: left;
    align-items: center;
    min-height: 20vh;
    padding: 5px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

.sub-title{
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    color: $white;
}

.form-group {
    display: flex;
    justify-content: right;
    align-items: center;
    width: 300px;
    margin-bottom: 10px;
    color: $white;
}

.label {
    flex: 1;
    text-align: left;
    margin-right: 20px;
}
.button-container{
    display: flex;
    justify-content: end;
}
table {
    width: 100%;
    border-collapse: collapse;
    border-spacing: 0;
    margin-bottom: 20px;
    border: 1px solid #ddd;
    border-radius: 5px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

table th, table td {
    padding: 10px;
    text-align: left;
    border: 1px solid #ddd;

}

table th {
    font-weight: bold;
    color: white;
    border-top: 1px solid #ddd;
    border-bottom: 1px solid #ddd;
    background-color: $ultra-light-gray;

}
.head{
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.btn {
    padding: 9px 12px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    border-radius: 2px;
}
.add-material{
    background-color: $blue;
    color: white;
}
</style>


<template>
    <div class="header pt-10 pb-4">
        <div class="left mr-3">
            <img src="/images/Materials.png" alt="MaterialLogo" class="image-icon" />
        </div>
        <div class="right">
            <h1 class="page-title">{{ $t('material') }}</h1>
            <h3 class="text-white"> <span class="green-text">{{ $t('material') }}</span> / {{ $t('SmallFormatMaterials') }}</h3>
        </div>
    </div>
    <div class="dark-gray p-5 text-white">
        <div class="form-container p-2 light-gray">
            <h2 class="sub-title">
                {{ $t('listOfSmallFormat') }}
            </h2>

            <div class="flex justify-end">
                <button @click="toggleEditMode" class="bg-white rounded text-black py-2 px-5 m-1 ">{{ editMode ? 'Exit Edit Mode' : 'Edit Mode' }}</button>
                <button @click="saveChanges()" v-if="editMode" class="blue rounded text-white py-2 px-5 m-1">Save Changes<v-icon class="mdi mdi-check"></v-icon></button>
            </div>

            <table>
                <thead>
                <tr>
                    <th>Name</th>
                    <th >Quantity</th>
                    <th>Price Per Unit</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="material in materials" :key="material.id">
                    <td>{{ material.name }}</td>
                    <td v-if="editMode">
                        <input type="text" class="text-black" v-model="material.editableQuantity" />
                    </td>
                    <td v-else>
                        {{material.quantity}}
                    </td>
                    <td v-if="editMode">
                        <input type="text" v-model="material.editablePricePerUnit"  class="text-black"/>
                    </td>
                    <td v-else>
                        {{material.price_per_unit}}
                    </td>
                    <td class="centered">
                        <SecondaryButton @click="deleteMaterial(material)" class="delete">Delete</SecondaryButton>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script>
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import axios from "axios";


export default {
    components: { SecondaryButton, PrimaryButton },
    props: {
        materials: Array,
    },
    data() {
        return {
            editMode: false,
        };
    },
    methods: {
        toggleEditMode() {
            this.editMode = !this.editMode;
            if (this.editMode) {
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
        async deleteMaterial(material) {
            const confirmed = confirm('Are you sure you want to delete this material?');
            if (!confirmed) {
                return;
            }

            try {
                await axios.delete(`/materials/${material.id}`);
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
        async saveChanges() {
            if (!this.editMode) {
                // Exit edit mode
                return;
            }

            // Handle the save changes action
            const promises = this.materials.map(async (material) => {
                if (
                    material.editableQuantity !== material.quantity ||
                    material.editablePricePerUnit !== material.price_per_unit
                ) {
                    try {
                        const response = await axios.put(`/materials/${material.id}`, {
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
            this.editMode = false;
            window.location.reload();
        },
    },
};
</script>
<style scoped lang="scss">
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
.page-title {
    font-size: 24px;
    display: flex;
    align-items: center;
    color: $white;
}
.sub-title{
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    color: $white;
}

.image-icon {
    margin-left: 2px;
    max-width: 40px;
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
</style>


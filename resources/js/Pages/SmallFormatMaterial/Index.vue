<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <div class="dark-gray material-list">
                <div class="form-container p15">
                    <SF_MaterialList :materials="materials" />
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script>
import SF_MaterialList from "@/Components/SF_MaterialList.vue";
import MainLayout from "@/Layouts/MainLayout.vue";

export default {
    components: {
        SF_MaterialList,MainLayout
    },
    props: {
        materials: Array, // Pass the list of materials as a prop
    },
};
</script>

<style scoped lang="scss">
/* Your styling here... */
</style>

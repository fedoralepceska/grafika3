<template>
    <MainLayout>
        <div class="pl-7 pr-7">
            <div class="dark-gray material-list">
                <div class="form-container p15">
                    <LF_MaterialList :materials="materials" />
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script>
import MainLayout from "@/Layouts/MainLayout.vue";
import LF_MaterialList from "@/Components/LF_MaterialList.vue";

export default {
    components: {
        LF_MaterialList,MainLayout
    },
    props: {
        materials: Array, // Pass the list of materials as a prop
    },
};
</script>

<style scoped lang="scss">
/* Your styling here... */
</style>

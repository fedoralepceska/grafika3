<template>
    <div class="background">
        <!-- KEEP THIS SLOT -->
        <!-- THE MENU GOES INSIDE THIS DIV -->
        <slot/>
    </div>
</template>

<script>
export default {
    name: "MainLayout"
}
</script>

<style scoped lang="scss">
    .background {
        background-color: $background-color;
        height: max-content;
    }
</style>

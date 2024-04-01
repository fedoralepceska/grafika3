<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="main flex flex-col sm:justify-center items-center pt-6 sm:pt-0 background">
        <div
            class=" w-full sm:max-w-md  px-6 py-9 dark-gray shadow-md overflow-hidden"
        >
            <Link href="/" class="app">
                <ApplicationLogo class="w-50 h-20 " />
            </Link>
            <slot />
        </div>
    </div>

</template>

<style scoped lang="scss">
.background {
    background-color: $background-color;
    background-image: url('/public/images/background.png'); /* Replace with your image path */
    background-size: cover; /* Stretch image to cover the container */
    background-position: center;
}
.dark-gray{
    background-color: $dark-gray;
}
.app{
    display: flex;
    justify-content: center;
}
.main{
    height: 100vh ;
    display: flex;
    flex-direction: column;
    justify-content: center; /* Vertically center the form */
    align-items: flex-end; /* Align the form to the right side */
}
</style>

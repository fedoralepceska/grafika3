<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="main min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 background">
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
}
.dark-gray{
    background-color: $dark-gray;
}
.app{
    display: flex;
    justify-content: center;
}
.main{
    display: flex;
    flex-direction: column;
    justify-content: center; /* Vertically center the form */
    align-items: flex-end; /* Align the form to the right side */
}
</style>

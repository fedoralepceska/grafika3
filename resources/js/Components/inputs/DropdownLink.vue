<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Link
        :href="href"
        class="dark-gray block w-full px-4 py-2 text-left text-sm leading-5 text-white hover:text-gray-400 focus:outline-none focus:bg-gray-400 transition duration-150 ease-in-out"
    >
        <slot />
    </Link>
</template>

<style scoped lang="scss">
.dark-gray {
    background-color: $dark-gray;
    height: max-content;
}
</style>
